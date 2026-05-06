<?php
include_once("../modules/db_connection.php");
include_once("../modules/usertype.php");
include_once("../modules/isValidDate.php");
include_once("../modules/formatMoney.php");
include_once("../modules/getTodayWithdrawCount.php");
include_once("../modules/isValidCard.php");//this is getting long

$usertype = usertype();
$card_num = '';//String
$expire = '';
$cvv = '';
$amount = 0;
$note = '';
$error = '';

if ($usertype != 1 && $usertype != 3) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST'){
        $error = 'Please wait for verification before using this feature';
    } else {
        $error = 'This function is only for verified account';
    }
}

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
                    $error = "Can\'t find user account";
                }
            }

            if (!empty($selfPhone)) {
                //$status = 1; //no need approve in deposit
                $date = date('Y-m-d H:i:s');
                //selfFeeBear is false because 5% fee is not applied
                $dep = $con->prepare("INSERT INTO history (user_phone, transfer_type, card_num, expiration, CVV, date_transfer, money, note, status, fee_bearer) VALUES (?, Deposit, ?, ?, ?, " . $date . ", ?, ?, 1, false)");
                $dep->bind_param("ssssds", $selfPhone, $card_num, $expire, $cvv, $amount, $note);

                if(!$dep->execute()){
                    $error = 'Failed to save in deposit history';
                } else {
                    $dep = $con->prepare("update user set money = money + ? where phonenum = ?");
                    $dep->bind_param("ds", $amount, $selfPhone);

                    if(!$dep->execute()){
                        $error = 'Failed to update user balance, cancelled the deposit';
                        //write cancel to history
                        $canceldate = date('Y-m-d H:i:s');
                        $dep = $con->prepare("update history status = 0, date_confirm = ? where user_phone = ? and amount = ? and date_transfer = ?");
                        $dep->bind_param("ssds", $canceldate, $selfPhone, $amount, $date);
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
?>