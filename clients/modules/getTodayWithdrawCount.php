<?php
require_once("db_connection.php");
function getTodayWithdrawCount(string $email)
{
    $con = connect_db();
    $stmt = $con->prepare("select phonenum from user where email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user_phone = '';
    $stmt->bind_result($user_phone);
    $stmt->fetch();//done get user phonenum

    /*
    //should not use this
    if(empty($user_phone)){
        $stmt->close();
        return -1;
    }
    */

    $stmt = $con->prepare("SELECT COUNT(*) FROM `history` WHERE `user_phone` = ? AND `transfer_type` = 'Withdraw' AND DATE(date_transfer) = CURDATE() AND status != 0");
    //DATE(a) removes the time part and keeps only YYYY-MM-D
    //CURDATE() function in MySQL returns the current date in the format YYYY-MM-DD (string) 
    $stmt->bind_param("s", $user_phone);
    $stmt->execute();
    $count = 0;
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $con->close();
    return (int) $count;
}
?>