<?php
include_once("../modules/db_connection.php");
include_once("../modules/usertype.php");
include_once("../modules/isValidDate.php");
include_once("../modules/formatMoney.php");
include_once("../modules/generateCode.php");
include_once("../modules/getTodayWithdrawCount.php");
include_once("../modules/isValidCard.php");//this is getting long

$usertype = usertype();
$card_num = '';//String
$expire = '';
$cvv = '';
$amount = 0;
$note = '';
$success = false;
$pendingApproval = false;
$error = checkuser($usertype);

if (isset($_POST['card_num']) && isset($_POST['expire']) && isset($_POST['cvv']) && 
    isset($_POST['amount']) && $_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    $card_num = trim($_POST['card_num'] ?? '');
    $expire = trim($_POST['expire'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $amount = str_replace(',', '', $_POST['amount'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if (empty($_POST['card_num'])) {
        $error = 'Please enter the card number';
    } else if (empty($_POST['expire'])) {
        $error = 'Please the expiration date';
    } else if (empty($_POST['cvv'])) {
        $error = 'Please enter the cvv number';
    } else if (empty($_POST['amount'])) {
        $error = 'Please enter the amount to withdraw';
    } else if (!is_numeric($amount)) {
        $error = 'This is not a valid number to withdraw';
    } else if (getTodayWithdrawCount($_SESSION['email']) >= 2) {
        $error = 'You can only make 2 withdrawals per day. Please try again tomorrow';
    } else {
        $amount = floatval($amount);
        $date_error = isValidDate($expire);
        $valid_error = isValidWithdrawCard($card_num, $expire, $cvv);
        
        if ($amount <= 0) {
            //put link to deposit page here -----------------
            $error = 'Please visit the <a href="deposit.php">deposit page</a> if you want to deposit';
        } else if ($amount % 50000 != 0) {
            $error = 'Withdrawal amount must be a multiple of 50,000 VND.';
        } else if (!empty($date_error)) {
            $error = $date_error;
        } else if (!empty($valid_error)) {
            $error = $valid_error;
        } else {
            $totalDeduct = $amount*1.05;//5% fee
            $selfamount = 0;
            $selfPhone = '';
            $con = connect_db();
            $withd = $con->prepare("SELECT phonenum, money FROM user where email = ?");
            $withd->bind_param("s", $_SESSION["email"]);

            if(!$withd->execute()){
                $error = 'Error in database, please try again later';
            } else {
                $withd->bind_result($selfPhone, $selfamount);
                if (!$withd->fetch()) {
                    //Bound variable (selfPhone) keep it last successfully fetched values - they are not reset to null automatically
                    $error = 'User account not found';
                }
                $withd->free_result();
            }

            if (!empty($selfPhone)) {
                if ($selfamount < $totalDeduct) {
                    $error = 'Insufficient balance. You need ' . formatMoney($totalDeduct) . ' (including 5% fee) but have ' . formatMoney($selfamount);
                } else {
                    $status = $amount > 5000000 ? 2 : 1; //5 milion need approve
                    $transfer_type = "Withdraw";
                    $selfFeeBear = 1;//int for bool
                    //selfFeeBear is true because 5% fee is applied to user who withdraw
                    $id = generateIdCode($selfPhone, 3);
                    $date = date('Y-m-d H:i:s'); // current date/time

                    $withd = $con->prepare("INSERT INTO history (id, user_phone, transfer_type, card_num, expiration, CVV, date_transfer, money, note, status, selfFeeBear) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $withd->bind_param("sssssssdsii", $id, $selfPhone, $transfer_type, $card_num, $expire, $cvv, $date, $totalDeduct, $note, $status, $selfFeeBear);

                    if(!$withd->execute()){
                        $error = 'Failed to save in withdrawal history';
                    } else if ($status == 1) {
                        $withd = $con->prepare("update user set money = money - ? where phonenum = ?");
                        $withd->bind_param("ds", $totalDeduct, $selfPhone);

                        if(!$withd->execute()){
                            $error = 'Failed to update user balance, cancelled the withdrawal';
                            //write cancel to history
                            $status = 0;
                            $canceldate = date('Y-m-d H:i:s');
                            $withd = $con->prepare("UPDATE history SET status = ?, date_confirm = ? where id = ?");
                            $withd->bind_param("iss", $status, $canceldate, $id);
                            if(!$withd->execute()){
                                $error = 'Failed to update user balance, failed cancelled the withdrawal. It seem like god want you to have free money';
                                //i don't know how to handle this case
                            }
                        } else {
                            //complete success
                            //should show something on screen
                            $success = true;
                        }
                    } else {
                        // $status == 2: large withdrawal, pending admin approval
                        $pendingApproval = true;
                    }
                }
            }
            $withd->close();
            $con->close();
        }
    }
}
include("../src/header.php");
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
  body { background: #f8f9fa; }
  .page-wrapper { max-width: 560px; margin: 48px auto; padding: 0 16px; }
  .card-panel {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,.08);
    padding: 32px;
  }
  .page-title { font-size: 22px; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
  .page-sub   { font-size: 14px; color: #6c757d; margin-bottom: 28px; }
  .form-label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
  .form-control, .form-select {
    border-radius: 10px;
    border: 1.5px solid #e5e7eb;
    font-size: 14px;
    padding: 10px 14px;
    transition: border-color .2s;
  }
  .form-control:focus { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.15); }
  .input-icon-wrap { position: relative; }
  .input-icon-wrap .bi {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: #9ca3af; font-size: 15px;
  }
  .input-icon-wrap .form-control { padding-left: 40px; }
  .btn-withdraw {
    background: linear-gradient(135deg, #ef4444, #f97316);
    color: #fff; border: none; border-radius: 10px;
    padding: 12px; font-size: 15px; font-weight: 600;
    width: 100%; margin-top: 8px; cursor: pointer;
    transition: opacity .2s;
  }
  .btn-withdraw:hover { opacity: .9; }
  .divider { border-top: 1px solid #f0f0f0; margin: 20px 0; }
  .fee-note {
    background: #fff7ed; border-radius: 10px;
    padding: 14px 16px; font-size: 13px; color: #c2410c; margin-bottom: 24px;
  }
  .limit-note {
    background: #fafafa; border: 1px solid #e5e7eb; border-radius: 10px;
    padding: 14px 16px; font-size: 13px; color: #6b7280; margin-bottom: 24px;
  }
  .success-box { text-align: center; padding: 32px 0; }
  .success-box .check-icon {
    width: 64px; height: 64px; border-radius: 50%;
    background: #dcfce7; display: inline-flex;
    align-items: center; justify-content: center; font-size: 28px;
    color: #16a34a; margin-bottom: 16px;
  }
  .pending-box { text-align: center; padding: 32px 0; }
  .pending-box .clock-icon {
    width: 64px; height: 64px; border-radius: 50%;
    background: #fef9c3; display: inline-flex;
    align-items: center; justify-content: center; font-size: 28px;
    color: #a16207; margin-bottom: 16px;
  }
  .error-alert {
    display: flex; align-items: center; gap: 8px;
    background: #fef2f2; color: #b91c1c;
    border: 1px solid #fecaca; border-radius: 10px;
    padding: 10px 14px; font-size: 13px; margin-bottom: 18px;
  }
  .error-alert.is-invisible { display: none; }
  #feePreview {
    background: #f9fafb; border-radius: 10px;
    border: 1px solid #e5e7eb; padding: 14px 16px;
    font-size: 13px; margin-bottom: 18px; display: none;
  }
</style>

<div class="page-wrapper">
  <div class="card-panel">

    <?php if ($success): ?>
    <!-- ── Success ── -->
    <div class="success-box">
      <div class="check-icon"><i class="bi bi-check-lg"></i></div>
      <h5 class="fw-bold mb-1" style="color:#1a1a2e">Withdrawal Successful!</h5>
      <p class="text-muted" style="font-size:14px">
        <strong><?= htmlspecialchars(formatMoney($amount)) ?></strong> withdrawn
        (total deducted: <strong><?= htmlspecialchars(formatMoney($amount * 1.05)) ?></strong> incl. 5% fee).
      </p>
      <div class="d-flex gap-2 justify-content-center mt-3">
        <a href="withdraw.php"    class="btn btn-outline-secondary btn-sm">Withdraw Again</a>
        <a href="Home.php"        class="btn btn-primary btn-sm">Dashboard</a>
        <a href="transactions.php" class="btn btn-outline-secondary btn-sm">View History</a>
      </div>
    </div>

    <?php elseif ($pendingApproval): ?>
    <!-- ── Pending approval (>5M VND) ── -->
    <div class="pending-box">
      <div class="clock-icon"><i class="bi bi-hourglass-split"></i></div>
      <h5 class="fw-bold mb-1" style="color:#1a1a2e">Pending Admin Approval</h5>
      <p class="text-muted" style="font-size:14px">
        Withdrawals over 5,000,000 VND require admin approval.<br>
        Your request for <strong><?= htmlspecialchars(formatMoney($amount)) ?></strong> has been submitted.
      </p>
      <div class="d-flex gap-2 justify-content-center mt-3">
        <a href="Home.php"        class="btn btn-primary btn-sm">Dashboard</a>
        <a href="transactions.php" class="btn btn-outline-secondary btn-sm">View History</a>
      </div>
    </div>

    <?php else: ?>
    <!-- ── Withdraw Form ── -->
    <div class="page-title"><i class="bi bi-arrow-up-circle-fill me-2" style="color:#ef4444"></i>Withdraw Money</div>
    <div class="page-sub">Transfer funds from your wallet to a bank card</div>

    <div class="fee-note">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      A <strong>5% fee</strong> applies to all withdrawals. Amounts must be multiples of 50,000 VND.
      Withdrawals over 5,000,000 VND require admin approval.
    </div>

    <div class="limit-note">
      <i class="bi bi-info-circle me-1"></i>
      You may withdraw up to <strong>2 times per day</strong>. Only card <code>111111</code> is accepted.
    </div>

    <div id="error-msg" class="error-alert<?= empty($error) ? ' is-invisible' : '' ?>" role="alert">
      <svg viewBox="0 0 24 24" width="16" height="16">
        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
        <path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <span><?= !empty($error) ? $error : '&nbsp;' ?></span>
    </div>

    <form method="POST" action="" autocomplete="off">

      <!-- Card Number -->
      <div class="mb-3">
        <label class="form-label">Card Number <span class="text-danger">*</span></label>
        <div class="input-icon-wrap">
          <i class="bi bi-credit-card"></i>
          <input type="text" name="card_num" class="form-control" placeholder="6-digit card number"
                 maxlength="6" pattern="\d{6}"
                 value="<?= htmlspecialchars($card_num) ?>" required>
        </div>
      </div>

      <!-- Expiry & CVV -->
      <div class="row g-3 mb-3">
        <div class="col-7">
          <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
          <div class="input-icon-wrap">
            <i class="bi bi-calendar3"></i>
            <input type="text" name="expire" class="form-control" placeholder="MM/DD/YYYY"
                   value="<?= htmlspecialchars($expire) ?>" required>
          </div>
        </div>
        <div class="col-5">
          <label class="form-label">CVV <span class="text-danger">*</span></label>
          <div class="input-icon-wrap">
            <i class="bi bi-shield-lock"></i>
            <input type="text" name="cvv" class="form-control" placeholder="3 digits"
                   maxlength="3" pattern="\d{3}"
                   value="<?= htmlspecialchars($cvv) ?>" required>
          </div>
        </div>
      </div>

      <!-- Amount -->
      <div class="mb-3">
        <label class="form-label">Amount (VND) <span class="text-danger">*</span></label>
        <div class="input-icon-wrap">
          <i class="bi bi-cash-stack"></i>
          <input type="text" name="amount" id="amountInput" class="form-control"
                 placeholder="e.g. 500,000 (multiples of 50,000)"
                 value="<?= $amount > 0 ? htmlspecialchars(number_format($amount, 0, '.', ',')) : '' ?>"
                 required>
        </div>
      </div>

      <!-- Fee preview -->
      <div id="feePreview">
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Withdrawal amount</span>
          <span id="previewAmount" class="fw-semibold">—</span>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Fee (5%)</span>
          <span id="previewFee" class="fw-semibold text-danger">—</span>
        </div>
        <hr class="my-2">
        <div class="d-flex justify-content-between">
          <span class="fw-bold">Total deducted</span>
          <span id="previewTotal" class="fw-bold" style="color:#1a1a2e">—</span>
        </div>
      </div>

      <!-- Note -->
      <div class="mb-4">
        <label class="form-label">Note <span class="text-muted fw-normal">(optional)</span></label>
        <textarea name="note" class="form-control" rows="2"
                  placeholder="Add a note for this withdrawal..."><?= htmlspecialchars($note) ?></textarea>
      </div>

      <button type="submit" class="btn-withdraw">
        <i class="bi bi-arrow-up-circle me-2"></i>Withdraw Funds
      </button>
    </form>

    <div class="divider"></div>
    <div class="text-center" style="font-size:13px;color:#6c757d">
      Want to add funds instead?
      <a href="deposit.php" class="text-decoration-none" style="color:#3b82f6">Go to Deposit</a>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include("../src/footer.php"); ?>

<script>
const amountInput = document.getElementById('amountInput');
const feePreview = document.getElementById('feePreview');

if (amountInput) {
    amountInput.addEventListener('input', function () {
        let raw = this.value.replace(/[^0-9]/g, '');
        if (!raw) { 
            feePreview.style.display = 'none'; 
            this.value = ''; 
            return; 
        }
        const num = parseInt(raw, 10);
        this.value = num.toLocaleString('vi-VN');

        const fee = num*0.05;
        const total = num + fee;
        const fmt = v => v.toLocaleString('vi-VN') + ' VND';

        document.getElementById('previewAmount').textContent = fmt(num);
        document.getElementById('previewFee').textContent = fmt(fee);
        document.getElementById('previewTotal').textContent = fmt(total);
        feePreview.style.display = 'block';
    });
}
</script>