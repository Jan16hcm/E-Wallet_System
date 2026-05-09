<?php
include_once("../modules/db_connection.php");
include_once("../modules/usertype.php");
include_once("../modules/formatMoney.php");
include_once("../modules/receipt.php");
include_once("../modules/generateCode.php");

$usertype = usertype();
$step = (int)$_GET['step'] ?? 1;
//?? is use the rightside value if leftside is null
$recipientPhone = '';
$amount = 0;
$note = '';
$selfFeeBear = false;//false -> recipient bear 5% fee; true -> sender bear 5% fee
$error = checkuser($usertype);

if (isset($_POST['recipientPhone']) && isset($_POST['amount']) && $step == 1 && empty($error)){
    $recipientPhone = trim($_POST['recipientPhone'] ?? '');
    $amount = str_replace(',','',($_POST['amount'] ?? '0'));//double and float are the same type
    $note = trim($_POST['note'] ?? 'no note');//note can be empty;  
    $selfFeeBear = $_POST['selfFeeBear'];

    if (empty($_POST['recipientPhone'])) {
        $error = 'Please enter the recipient phone number';
    } else if (empty($_POST['amount'])) {
        $error = 'Please enter the amount to transfer';
    } else if (empty($selfFeeBear)) {
        $error = 'Please choose wheather to bear the 5% fee yourself or the ricipieint';
    } else if (!is_numeric($amount)) {
        $error = 'This is not a valid number';
    } else {
        $amount = floatval($amount);
        if ($amount > 50000) {
            $con = connect_db();
            $result = $con->query("SELECT email, phonenum, name, money FROM user");
            $transfer_yourself = false;
            $found = false;
            $recipientName = '';
            $selfName = '';
            $selfamount = 0;
            $selfPhone = '';

            if ($result->num_rows > 0) {    //check if database is not empty
                while($row = $result->fetch_assoc()) { //check duplicate
                    if($row["email"] == $_SESSION['email']) {
                        $selfamount = $row['money'];//get self money
                        $selfPhone = $row['phonenum'];
                        $selfName = $row['name'];
                        if($selfPhone == $recipientPhone) {
                            $transfer_yourself = true;
                            $error = 'Warning: you may give ' . formatMoney($amount*0.05) . '\$ to owner of MeoMeo by transfering to yourself';
                            break;
                        }
                    }
                    if($row["phonenum"] == $recipientPhone && !$transfer_yourself) {
                        $recipientName = $row["name"];
                        $found = true;
                    }
                }
            }

            $con->close();
            if ($found) {
                $selfDeduct = $selfFeeBear ? $amount*1.05 : $amount;
                $recipientGet = $selfFeeBear ? $amount : $amount*0.95;
                if($selfamount < $selfDeduct){
                    $error = 'Insufficient balance. You need ' . formatMoney($selfDeduct) . ' (including 5% fee) but have ' . formatMoney($selfamount);
                } else {
                    $otp = sprintf('%06d', random_int(0, 999999));
                    $expire = time() + 60;//a time in future
                    $_SESSION['otp'] = $otp;
                    $_SESSION['otp_expire'] = $expire;

                    if(send_otp_email($otp, $_SESSION['email'], $selfName)){
                        $error = 'Failed to send mail, please try again later';
                    } else {
                        $_SESSION['transfer'] = ['amount' => $amount,
                                                'selfFeeBear' => $selfFeeBear ? 1 : 0,//int for bool
                                                'note' => $note,
                                                'recipientName' => $recipientName,
                                                'selfName'=> $selfName,
                                                'recipientPhone' => $recipientPhone,
                                                'selfPhone' => $selfPhone,
                                                'recipientGet' => $recipientGet,
                                                'selfDeduct' => $selfDeduct];
                        $step = 2;
                        /*
                        header('Location: transfer.php?step=2');
                        exit;
                        */
                    }
                }
            } else if (!$transfer_yourself) {
                $error = 'Recipient not found';
            }
        } else {
            $error = 'Minimum transfer amount is 50,000 VND';
        }
    }
}

if($step == 2 && isset($_SESSION['otp']) && isset($_SESSION['transfer']) && empty($error)) {
    if (empty($_SESSION['transfer'])) {//useless ?
        header('Location: transfer.php');
        exit;
    }

    $otp = strval($_SESSION['otp']);
    $expire = (int)$_SESSION['otp_expire'];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if(isset($_POST['otp_in6'])){
            $otp_in = $_POST['otp_in1'] . $_POST['otp_in2'] . $_POST['otp_in3'] . $_POST['otp_in4'] . $_POST['otp_in5'] . $_POST['otp_in6'];
            if(strcmp($otp_in, $otp) != 0) {
                $error = 'Wrong OTP code';
            } else if(time() > $expire){
                $error = 'OTP code expired';
                unset($_SESSION['otp']);
                unset($_SESSION['otp_expire']);
            } else {
                unset($_SESSION['otp']);
                unset($_SESSION['otp_expire']);
                
                $t = $_SESSION['transfer']; //to shorten lonnnng name
                $status = $t['amount'] > 5000000 ? 2 : 1; //5 milion need approve
                //1 == completed == Approved; 2 == Pending
                $transfer_type = "Transfer";
                $date = date('Y-m-d H:i:s'); // current date/time 
                //MySQL expects: YYYY-MM-DD HH:MM:SS
                $id = generateIdCode($t['selfPhone'], 1);

                $con = connect_db();
                $tran = $con->prepare("INSERT INTO history (id, user_phone, receiver_phone, transfer_type, date_transfer, money, note, status, selfFeeBear) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $tran->bind_param("sssssdsii", $id, $t['selfPhone'], $t['recipientPhone'], $transfer_type, $date, $t['amount'], $t['note'], $status, $t['selfFeeBear']);
                //bool => integer (i) if sql TINYINT(1) or string (s)
                if(!$tran->execute()){
                    $error = 'Failed to save in transfer history';
                } else if($status == 1){
                    $tran = $con->prepare("UPDATE user SET money = money - ? where phonenum = ?");
                    $tran->bind_param("ds", $t['selfDeduct'], $t['selfPhone']);
                    if(!$tran->execute()){
                        $error = 'Failed to update user balance, transferal cancelled';

                        //write cancel to history
                        $status = 0;
                        $canceldate = date('Y-m-d H:i:s');
                        $withd = $con->prepare("UPDATE history SET status = ?, date_confirm = ? where id = ?");
                        $withd->bind_param("iss", $status, $canceldate, $id);
                        if(!$withd->execute()){
                            $error = 'Failed to update user balance, failed cancelled the withdrawal. It seem like god want you to have free money';
                            //i don't know how to handle this case
                        }
                        unset($_SESSION['transfer']);
                    } else {

                        $tran = $con->prepare("update user set money = money + ? where phonenum = ?");
                        $tran->bind_param("ds", $t['recipientGet'], $t['recipientPhone']);
                        $tran->execute();//shouldn't fail if the first one work?

                        //get recipient email and balance
                        $email = '';
                        $recipientMoney = 0;
                        $tran = $con->prepare('select email, money from user where phonenum = ?');
                        $tran->bind_param("s", $t['recipientPhone']);
                        $tran->execute();
                        $tran->bind_result($email, $recipientMoney);
                        $tran->fetch();

                        $recipientMoney = floatval($recipientMoney); //incase
                        $tran->close();
                        $con->close();

                        if(!send_receipt(formatMoney($t['recipientGet']), formatMoney($recipientMoney), $email, $t['selfName'], $t['recipientName'], $t['note'])){
                            $error = 'Transfer done, but failed to send receipt email to recipient';
                        }
                            $step = 3;
                            unset($_SESSION['transfer']);
                            //complete success
                            //should show something on screen
                        
                    } 
                } else {
                    // $status == 2: pending approval
                    unset($_SESSION['transfer']);
                    $step = 3;
                }

                if (isset($tran)) {
                    // close safely only if not already closed above
                    $tran->close();
                    $con->close();
                }
            }
        }
    }
}
include("../src/header.php");
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
  body { background: #f8f9fa; }
  .page-wrapper { max-width: 580px; margin: 48px auto; padding: 0 16px; }
  .card-panel {
    background: #fff; border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,.08); padding: 32px;
  }
  .page-title { font-size: 22px; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
  .page-sub   { font-size: 14px; color: #6c757d; margin-bottom: 28px; }
  .form-label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
  .form-control, .form-select {
    border-radius: 10px; border: 1.5px solid #e5e7eb;
    font-size: 14px; padding: 10px 14px; transition: border-color .2s;
  }
  .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); }
  .input-icon-wrap { position: relative; }
  .input-icon-wrap .bi {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: #9ca3af; font-size: 15px;
  }
  .input-icon-wrap .form-control { padding-left: 40px; }
  .btn-transfer {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; border: none; border-radius: 10px;
    padding: 12px; font-size: 15px; font-weight: 600;
    width: 100%; margin-top: 8px; cursor: pointer; transition: opacity .2s;
  }
  .btn-transfer:hover { opacity: .9; }
  .divider { border-top: 1px solid #f0f0f0; margin: 20px 0; }
  .info-note {
    background: #f5f3ff; border-radius: 10px;
    padding: 14px 16px; font-size: 13px; color: #4338ca; margin-bottom: 24px;
  }
  .error-alert {
    display: flex; align-items: center; gap: 8px;
    background: #fef2f2; color: #b91c1c;
    border: 1px solid #fecaca; border-radius: 10px;
    padding: 10px 14px; font-size: 13px; margin-bottom: 18px;
  }
  .error-alert.is-invisible { display: none; }
  /* OTP boxes */
  .otp-wrap { display: flex; gap: 10px; justify-content: center; margin: 24px 0; }
  .otp-wrap input {
    width: 48px; height: 56px; text-align: center;
    font-size: 22px; font-weight: 700;
    border: 2px solid #e5e7eb; border-radius: 10px;
    outline: none; transition: border-color .2s;
  }
  .otp-wrap input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); }
  /* Fee radio */
  .fee-option { border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 12px 16px; cursor: pointer; transition: border-color .2s, background .2s; }
  .fee-option:has(input:checked) { border-color: #6366f1; background: #f5f3ff; }
  /* Success/Pending */
  .result-box { text-align: center; padding: 32px 0; }
  .result-icon {
    width: 64px; height: 64px; border-radius: 50%;
    display: inline-flex; align-items: center;
    justify-content: center; font-size: 28px; margin-bottom: 16px;
  }
  #feePreview { background: #f9fafb; border-radius: 10px; border: 1px solid #e5e7eb; padding: 14px 16px; font-size: 13px; margin-bottom: 18px; display: none; }
  #recipientBadge { font-size: 13px; margin-top: 6px; min-height: 20px; }
</style>

<div class="page-wrapper">
  <div class="card-panel">

    <?php if ($step == 3): ?>
    <!-- ── Step 3: Success ── -->
    <div class="result-box">
      <div class="result-icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-check-lg"></i></div>
      <h5 class="fw-bold mb-1" style="color:#1a1a2e">Transfer Submitted!</h5>
      <p class="text-muted" style="font-size:14px">
        Your transfer has been processed.<br>
        A receipt has been sent to the recipient.
      </p>
      <div class="d-flex gap-2 justify-content-center mt-3">
        <a href="transfer.php"    class="btn btn-outline-secondary btn-sm">New Transfer</a>
        <a href="Home.php"        class="btn btn-primary btn-sm">Dashboard</a>
        <a href="transactions.php" class="btn btn-outline-secondary btn-sm">History</a>
      </div>
    </div>

    <?php elseif ($step == 2): ?>
    <!-- ── Step 2: OTP Verification ── -->
    <div class="page-title"><i class="bi bi-shield-check me-2" style="color:#6366f1"></i>Verify Transfer</div>
    <div class="page-sub">Enter the 6-digit OTP sent to your email. It expires in 60 seconds.</div>

    <div id="error-msg" class="error-alert<?= empty($error) ? ' is-invisible' : '' ?>" role="alert">
      <svg viewBox="0 0 24 24" width="16" height="16">
        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
        <path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <span><?= !empty($error) ? htmlspecialchars($error) : '&nbsp;' ?></span>
    </div>

    <?php if (isset($_SESSION['transfer'])): $t = $_SESSION['transfer']; ?>
    <div style="background:#f9fafb;border-radius:10px;padding:14px 16px;font-size:13px;margin-bottom:20px">
      <div class="d-flex justify-content-between mb-1">
        <span class="text-muted">To</span>
        <span class="fw-semibold"><?= htmlspecialchars($t['recipientName']) ?> (<?= htmlspecialchars($t['recipientPhone']) ?>)</span>
      </div>
      <div class="d-flex justify-content-between mb-1">
        <span class="text-muted">Amount</span>
        <span class="fw-semibold"><?= formatMoney($t['amount']) ?></span>
      </div>
      <div class="d-flex justify-content-between">
        <span class="text-muted">You pay</span>
        <span class="fw-bold" style="color:#1a1a2e"><?= formatMoney($t['selfDeduct']) ?></span>
      </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="transfer.php?step=2">
      <label class="form-label text-center d-block">Enter OTP Code</label>
      <div class="otp-wrap">
        <input name="otp_in1" type="text" maxlength="1" autocomplete="off">
        <input name="otp_in2" type="text" maxlength="1" autocomplete="off">
        <input name="otp_in3" type="text" maxlength="1" autocomplete="off">
        <input name="otp_in4" type="text" maxlength="1" autocomplete="off">
        <input name="otp_in5" type="text" maxlength="1" autocomplete="off">
        <input name="otp_in6" type="text" maxlength="1" autocomplete="off">
      </div>
      <button type="submit" class="btn-transfer">
        <i class="bi bi-shield-check me-2"></i>Confirm Transfer
      </button>
    </form>
    <div class="divider"></div>
    <div class="text-center" style="font-size:13px;color:#6c757d">
      <a href="transfer.php" class="text-decoration-none" style="color:#6366f1">← Cancel and go back</a>
    </div>

    <?php else: ?>
    <!-- ── Step 1: Transfer Form ── -->
    <div class="page-title"><i class="bi bi-arrow-left-right me-2" style="color:#6366f1"></i>Transfer Money</div>
    <div class="page-sub">Send funds to another MeoMeo Wallet user instantly</div>

    <div class="info-note">
      <i class="bi bi-info-circle-fill me-1"></i>
      A <strong>5% fee</strong> applies. Minimum transfer: 50,001 VND.
      Transfers over 5,000,000 VND need admin approval.
    </div>

    <div id="error-msg" class="error-alert<?= empty($error) ? ' is-invisible' : '' ?>" role="alert">
      <svg viewBox="0 0 24 24" width="16" height="16">
        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
        <path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <span><?= !empty($error) ? htmlspecialchars($error) : '&nbsp;' ?></span>
    </div>

    <form method="POST" action="transfer.php?step=1">

      <!-- Recipient Phone -->
      <div class="mb-3">
        <label class="form-label">Recipient Phone Number <span class="text-danger">*</span></label>
        <div class="input-icon-wrap">
          <i class="bi bi-phone"></i>
          <input type="text" name="recipientPhone" id="recipientPhone" class="form-control"
                 placeholder="e.g. 0901234567"
                 value="<?= htmlspecialchars($recipientPhone) ?>" required>
        </div>
        <div id="recipientBadge"></div>
      </div>

      <!-- Amount -->
      <div class="mb-3">
        <label class="form-label">Amount (VND) <span class="text-danger">*</span></label>
        <div class="input-icon-wrap">
          <i class="bi bi-cash-stack"></i>
          <input type="text" name="amount" id="amountInput" class="form-control"
                 placeholder="e.g. 200,000 (min 50,001)"
                 value="<?= $amount > 0 ? htmlspecialchars(number_format($amount, 0, '.', ',')) : '' ?>" required>
        </div>
      </div>

      <!-- Fee bearer -->
      <div class="mb-3">
        <label class="form-label">Who pays the 5% fee? <span class="text-danger">*</span></label>
        <div class="d-flex gap-2">
          <label class="fee-option flex-fill d-flex align-items-center gap-2">
            <input type="radio" name="selfFeeBear" value="1" <?= ($selfFeeBear == '1') ? 'checked' : '' ?> required>
            <div>
              <div class="fw-semibold" style="font-size:13px">I pay the fee</div>
              <div class="text-muted" style="font-size:12px">Recipient gets full amount</div>
            </div>
          </label>
          <label class="fee-option flex-fill d-flex align-items-center gap-2">
            <input type="radio" name="selfFeeBear" value="0" <?= ($selfFeeBear == '0') ? 'checked' : '' ?>>
            <div>
              <div class="fw-semibold" style="font-size:13px">Recipient pays fee</div>
              <div class="text-muted" style="font-size:12px">They receive 95% of amount</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Fee preview -->
      <div id="feePreview"></div>

      <!-- Note -->
      <div class="mb-4">
        <label class="form-label">Note <span class="text-muted fw-normal">(optional)</span></label>
        <textarea name="note" class="form-control" rows="2"
                  placeholder="Add a message..."><?= htmlspecialchars($note) ?></textarea>
      </div>

      <button type="submit" class="btn-transfer">
        <i class="bi bi-send me-2"></i>Continue to Verify
      </button>
    </form>
    <?php endif; ?>

  </div>
</div>
<?php include("../src/footer.php"); ?>
<script>
//OTP auto-advance, backspace navigation 
const otpInputs = document.querySelectorAll('.otp-wrap input');
otpInputs.forEach((input, i) => {
    input.addEventListener('input', () => {
        if (input.value.length === input.maxLength && otpInputs[i + 1]) {
            otpInputs[i + 1].focus();
        }
    });
    input.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && input.value.length === 0 && otpInputs[i - 1]) {
            otpInputs[i - 1].focus();
        }
    });
});
//amount formatter
const amountInput = document.getElementById('amountInput');
const feePreview = document.getElementById('feePreview');
const feeRadios = document.querySelectorAll('input[name="selfFeeBear"]');

function updateFeePreview() {
    if (!amountInput || !feePreview) return;
    const raw = amountInput.value.replace(/[^0-9]/g, '');
    if (!raw) { 
        feePreview.style.display = 'none'; 
        return; 
    }
    const num = parseInt(raw);
    const selfPays = document.querySelector('input[name="selfFeeBear"]:checked')?.value === '1';
    const youPay = selfPays ? num*1.05 : num;
    const theyGet = selfPays ? num : num*0.95;
    const fmt = v => Math.round(v).toLocaleString('vi-VN') + ' VND';

    feePreview.style.display = 'block';
    feePreview.innerHTML = `
      <div class="d-flex justify-content-between mb-1">
        <span class="text-muted">Transfer amount</span><span class="fw-semibold">${fmt(num)}</span>
      </div>
      <div class="d-flex justify-content-between mb-1">
        <span class="text-muted">Fee (5%)</span><span class="fw-semibold text-danger">${fmt(num * 0.05)}</span>
      </div>
      <div class="d-flex justify-content-between mb-1">
        <span class="text-muted">Recipient gets</span><span class="fw-semibold text-success">${fmt(theyGet)}</span>
      </div>
      <hr class="my-2">
      <div class="d-flex justify-content-between">
        <span class="fw-bold">You pay</span>
        <span class="fw-bold" style="color:#1a1a2e">${fmt(youPay)}</span>
      </div>`;
}

if (amountInput) {
    amountInput.addEventListener('input', function () {
        const raw = this.value.replace(/[^0-9]/g, '');
        this.value = raw ? parseInt(raw, 10).toLocaleString('vi-VN') : '';
        updateFeePreview();
    });
}
feeRadios.forEach(r => r.addEventListener('change', updateFeePreview));

//live recipient lookup
const phoneInput = document.getElementById('recipientPhone');
const recipientBadge = document.getElementById('recipientBadge');
if (phoneInput && recipientBadge) {
    let timeout;
    phoneInput.addEventListener('input', function () {
        clearTimeout(timeout);
        const phone = this.value.trim();
        if (phone.length >= 8) {
            timeout = setTimeout(() => {
                fetch('../modules/filter_account.php?phone=' + encodeURIComponent(phone))
                    .then(r => r.json())
                    .then(data => {
                        if (data.found) {
                            recipientBadge.innerHTML =
                                '<span style="color:#16a34a;font-size:13px"><i class="bi bi-check-circle-fill"></i> ' + data.name + '</span>';
                        } else {
                            recipientBadge.innerHTML =
                                '<span style="color:#b91c1c;font-size:13px"><i class="bi bi-x-circle-fill"></i> User not found</span>';
                        }
                    }).catch(() => {});
            }, 500);
        } else {
            recipientBadge.innerHTML = '';
        }
    });
}
</script>