<?php
include_once("../modules/db_connection.php");
include_once("../modules/usertype.php");
include_once("../modules/isValidDate.php");
include_once("../modules/formatMoney.php");
include_once("../modules/getTodayWithdrawCount.php");
include_once("../modules/generateIdCode.php");
include_once("../modules/isValidCard.php");//this is getting long

$usertype = usertype();
$card_num = '';//String
$expire = '';
$cvv = '';
$amount = 0;
$note = '';
$error = checkuser($usertype);

if (isset($_POST['card_num']) && isset($_POST['expire']) && isset($_POST['cvv']) && 
    isset($_POST['amount']) && $_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    $card_num = trim($_POST['card_number'] ?? '');
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
            }

            if (!empty($selfPhone)) {
                $status = 1; //no need approve in deposit
                $transfer_type = "Deposit";
                $selfFeeBear = false;
                $id = generateIdCode($selfPhone, 2);
                $date = date('Y-m-d H:i:s');
                //selfFeeBear is false because 5% fee is not applied
                $dep = $con->prepare("INSERT INTO history (id, user_phone, transfer_type, card_num, expiration, CVV, date_transfer, money, note, status, selfFeeBear) VALUES (?, ?, ?, ?, ?, ?, $date, ?, ?, $status, $selfFeeBear)");
                $dep->bind_param("ssssssds", $id, $selfPhone, $transfer_type, $card_num, $expire, $cvv, $amount, $note);

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
                        $dep = $con->prepare("update history status = ?, date_confirm = ? where id = ?");
                        $dep->bind_param("iss", $status, $canceldate, $id);
                        if(!$dep->execute()){
                            $error = 'Failed to update user balance, failed cancelled the deposit. It seem like god want you to lose money';
                            //i don't know how to handle this case
                        }
                    } else {
                        //complete success
                        //should show something on screen

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


</div>
<div id="error-msg" class="error-alert<?= empty($error) ? ' is-invisible' : '' ?>" role="alert">
    <svg viewBox="0 0 24 24" width="16" height="16">
        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" />
        <path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </svg>
    <span><?= !empty($error) ? htmlspecialchars($error) : '&nbsp;' ?></span>
</div>

<?php include("../src/footer.php"); ?>
