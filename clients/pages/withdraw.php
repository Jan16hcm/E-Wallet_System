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
        $error = 'Please enter the amount to withdraw';
    } else if (!is_numeric($amount)) {
        $error = 'This is not a valid number to withdrawal';
    } else if (getTodayWithdrawCount($_SESSION['email']) >= 2) {
        $error = 'You can only make 2 withdrawals per day. Please try again tomorrow';
    } else {
        $amount = floatval($amount);
        $date_error = isValidDate($expire);
        $valid_error = isValidWithdrawCard($card_num, $expire, $cvv);
        
        if ($amount <= 0) {
            //put link to deposit page here -----------------
            $error = 'Please visit the <a href="">deposit page</a> if you want to deposit';
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
                    $error = "Can\'t find user account";
                }
            }

            if (!empty($selfPhone)) {
                if ($selfamount < $totalDeduct) {
                    $error = 'Insufficient balance. You need ' . formatMoney($totalDeduct) . ' (including 5% fee) but have ' . formatMoney($selfamount);
                } else {
                    $status = $amount > 5000000 ? 2 : 1; //5 milion need approve
                    $date = date('Y-m-d H:i:s'); // current date/time
                    //selfFeeBear is true because 5% fee is applied to user who withdraw
                    $withd = $con->prepare("INSERT INTO history (user_phone, transfer_type, card_num, expiration, CVV, date_transfer, money, note, status, fee_bearer) VALUES (?, Withdraw, ?, ?, ?, " . $date . ", ?, ?, " . $status . ", true)");
                    //maybe should rename expiration to expire
                    $withd->bind_param("ssssds", $selfPhone, $card_num, $expire, $cvv, $totalDeduct, $note);

                    if(!$withd->execute()){
                        $error = 'Failed to save in withdrawal history';
                    } else if ($status == 1) {
                        $withd = $con->prepare("update user set money = money - ? where phonenum = ?");
                        $withd->bind_param("ds", $totalDeduct, $selfPhone);

                        if(!$withd->execute()){
                            $error = 'Failed to update user balance, cancelled the withdrawal';
                            //write cancel to history
                            $canceldate = date('Y-m-d H:i:s');
                            $withd = $con->prepare("update history status = 0, date_confirm = ? where user_phone = ? and amount = ? and date_transfer = ?");
                            $withd->bind_param("ssds", $canceldate, $selfPhone, $totalDeduct, $date);
                            if(!$withd->execute()){
                                $error = 'Failed to update user balance, failed cancelled the withdrawal. It seem like god want you to have free money';
                                //i don't know how to handle this case
                            }
                        } else {
                            //complete success
                            //should show something on screen

                        }
                    }
                }
            }
            $withd->close();
            $con->close();
        }
    }
}
?>