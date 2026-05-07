<?php
function generateIdCode(int $phonenum, int $type) {
    /*    
    $year = date("Y");
    $month = date("m");
    $day = date("d");
    $hour = date("H");
    $minute = date("i");
    $second = date("s");
    */
    $code = substr($phonenum, 0, 3) . date("smi") . substr($phonenum, 2, 2) . date("Hd") . substr($phonenum, -strlen($phonenum) - 5) . date("Y");
    //crackable (just hard), no randomess
    if ($type == 1) {//Transfer
        return 'T' . $code;
    }
    if ($type == 2) {//Deposit
        return 'D' . $code;
    }
    if ($type == 3) {//Withdraw
        return 'W' . $code;
    }
    //if ($type == 4) {//Buycard
        return 'C' . $code;
    //}
}
?>