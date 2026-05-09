<?php
include_once("../modules/db_connection.php");
include_once("../modules/usertype.php");
include_once("../modules/isValidDate.php");
include_once("../modules/formatMoney.php");
include_once("../modules/getTodayWithdrawCount.php");
include_once("../modules/generateCode.php");
include_once("../modules/isValidCard.php");//this is getting long

$usertype = usertype();
$card_num = '';//String
$expire = '';
$cvv = '';
$amount = 0;
$note = '';
$success = false;
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
        $error = 'Please enter the amount to deposit';
    } else if (!is_numeric($amount)) {
        $error = 'This is not a valid number to deposit';
    } else {
        $amount = floatval($amount);
        $date_error = isValidDate($expire);
        $valid_error = isValidDepositCard($card_num, $expire, $cvv, $amount);
        
        if ($amount <= 0) {
            //put link to deposit page here -----------------
            $error = 'Please visit the <a href="withdraw.php">withdraw page</a> if you want to withdraw';
        } else if (!empty($date_error)) {
            $error = $date_error;
        } else if (!empty($valid_error)) {
            $error = $valid_error;
        } else {
            //$totalDeduct = $amount*1.00;//0% fee
            $selfPhone = '';
            $con = connect_db();
            $dep = $con->prepare("SELECT phonenum FROM user where email = ?");
            $dep->bind_param("s", $_SESSION["email"]);
            
            if(!$dep->execute()){
                $error = 'Error in database, please try again later';
            } else {
                $dep->bind_result($selfPhone);
                if (!$dep->fetch()) {
                    //Bound variable (selfPhone) keep it last successfully fetched values - they are not reset to null automatically
                    $error = 'User account not found';
                }
                $dep->free_result();
            }

            if (!empty($selfPhone)) {
                $status = 1; //no need approve in deposit
                $transfer_type = "Deposit";
                $selfFeeBear = 0;//int for bool
                $id = generateIdCode($selfPhone, 2);
                $date = date('Y-m-d H:i:s');
                //selfFeeBear is false because 5% fee is not applied
                $dep = $con->prepare("INSERT INTO history (id, user_phone, transfer_type, card_num, expiration, CVV, date_transfer, money, note, status, selfFeeBear) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $dep->bind_param("sssssssdsii", $id, $selfPhone, $transfer_type, $card_num, $expire, $cvv, $date, $amount, $note, $status, $selfFeeBear);

                if(!$dep->execute()){
                    $error = 'Failed to save in deposit history';
                } else {
                    $dep = $con->prepare("update user set money = money + ? where phonenum = ?");
                    $dep->bind_param("ds", $amount, $selfPhone);

                    if(!$dep->execute()){
                        $error = 'Failed to update user balance, cancelled the deposit';
                        //write cancel to history
                        $status = 0;
                        $canceldate = date('Y-m-d H:i:s');
                        $dep = $con->prepare("UPDATE history SET status = ?, date_confirm = ? where id = ?");
                        $dep->bind_param("iss", $status, $canceldate, $id);
                        if(!$dep->execute()){
                            $error = 'Failed to update user balance, failed cancelled the deposit. It seem like god want you to lose money';
                            //i don't know how to handle this case
                        }
                    } else {
                        //complete success
                        //should show something on screen
                        $success = true;
                    }
                }
            }
            $dep->close();
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
  .form-control:focus, .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,.15);
  }
  .input-icon-wrap { position: relative; }
  .input-icon-wrap .bi {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: #9ca3af; font-size: 15px;
  }
  .input-icon-wrap .form-control { padding-left: 40px; }
  .btn-deposit {
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: #fff; border: none; border-radius: 10px;
    padding: 12px; font-size: 15px; font-weight: 600;
    width: 100%; margin-top: 8px; cursor: pointer;
    transition: opacity .2s;
  }
  .btn-deposit:hover { opacity: .9; }
  .divider { border-top: 1px solid #f0f0f0; margin: 20px 0; }
  .info-note {
    background: #eff6ff; border-radius: 10px;
    padding: 14px 16px; font-size: 13px; color: #1d4ed8; margin-bottom: 24px;
  }
  .success-box {
    text-align: center; padding: 32px 0;
  }
  .success-box .check-icon {
    width: 64px; height: 64px; border-radius: 50%;
    background: #dcfce7; display: inline-flex;
    align-items: center; justify-content: center; font-size: 28px;
    color: #16a34a; margin-bottom: 16px;
  }
  .error-alert {
    display: flex; align-items: center; gap: 8px;
    background: #fef2f2; color: #b91c1c;
    border: 1px solid #fecaca; border-radius: 10px;
    padding: 10px 14px; font-size: 13px; margin-bottom: 18px;
  }
  .error-alert.is-invisible { display: none; }
</style>

<div class="page-wrapper">
  <div class="card-panel">

    <?php if ($success): ?>
    <!-- ── Success state ── -->
    <div class="success-box">
      <div class="check-icon"><i class="bi bi-check-lg"></i></div>
      <h5 class="fw-bold mb-1" style="color:#1a1a2e">Deposit Successful!</h5>
      <p class="text-muted" style="font-size:14px">
        <strong><?= htmlspecialchars(formatMoney($amount)) ?></strong> has been added to your wallet.
      </p>
      <div class="d-flex gap-2 justify-content-center mt-3">
        <a href="deposit.php"   class="btn btn-outline-secondary btn-sm">Deposit Again</a>
        <a href="Home.php"      class="btn btn-primary btn-sm">Go to Dashboard</a>
        <a href="transactions.php" class="btn btn-outline-secondary btn-sm">View History</a>
      </div>
    </div>

    <?php else: ?>
    <!-- ── Deposit Form ── -->
    <div class="page-title"><i class="bi bi-arrow-down-circle-fill me-2" style="color:#3b82f6"></i>Deposit Money</div>
    <div class="page-sub">Add funds to your MeoMeo Wallet using a credit card</div>

    <div class="info-note">
      <i class="bi bi-info-circle-fill me-1"></i>
      No fee on deposits &mdash; the full amount is credited to your wallet instantly.
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
                 placeholder="e.g. 500,000"
                 value="<?= $amount > 0 ? htmlspecialchars(number_format($amount, 0, '.', ',')) : '' ?>" required>
        </div>
      </div>

      <!-- Note -->
      <div class="mb-4">
        <label class="form-label">Note <span class="text-muted fw-normal">(optional)</span></label>
        <textarea name="note" class="form-control" rows="2"
                  placeholder="Add a note for this deposit..."><?= htmlspecialchars($note) ?></textarea>
      </div>

      <button type="submit" class="btn-deposit">
        <i class="bi bi-arrow-down-circle me-2"></i>Deposit Funds
      </button>
    </form>

    <div class="divider"></div>
    <div class="text-center" style="font-size:13px;color:#6c757d">
      Want to withdraw instead?
      <a href="withdraw.php" class="text-decoration-none" style="color:#3b82f6">Go to Withdraw</a>
    </div>
    <?php endif; ?>

  </div>
</div>
<?php include("../src/footer.php"); ?>
<script>
// Format amount with commas as user types
const amountInput = document.getElementById('amountInput');
if (amountInput) {
    amountInput.addEventListener('input', function () {
        let raw = this.value.replace(/[^0-9]/g, '');
        this.value = raw ? parseInt(raw, 10).toLocaleString('vi-VN') : '';
    });
}
</script>