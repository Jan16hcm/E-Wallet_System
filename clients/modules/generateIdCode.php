<?php
function generateIdCode(String $phonenum, int $type) {
    //return a unique 32 char string depending on time, phonenum, type of transaction
    /*    
    $year = date("Y");
    $month = date("m");
    $day = date("d");
    $hour = date("H");
    $minute = date("i");
    $second = date("s");
    */
    $code = substr($phonenum, 0, 3) . date("smi") . substr($phonenum, 2, 2) . date("Hd") . substr($phonenum, -strlen($phonenum) - 5) . date("Y");
    //crackable (just hard), no randomness
    if ($type == 1) {//Transfer
        return str_pad('T' . $code, 32, '0', STR_PAD_RIGHT);
    }
    if ($type == 2) {//Deposit
        return str_pad('D' . $code, 32, '0', STR_PAD_RIGHT);
    }
    if ($type == 3) {//Withdraw
        return str_pad('W' . $code, 32, '0', STR_PAD_RIGHT);
    }
    //if ($type == 4) {//Buycard
        return str_pad('C' . $code, 32, '0', STR_PAD_RIGHT);
    //}
}
function generateCardCode(String $phonenum, string $carrierCode) {
    //return int phone card code (10-digit sequence with the first 5 digits being the carrier code)
    //can get duplicate
    //date("z")	The day of the year (starting from 0)	0 through 365
    //date("L") Whether it's a leap year 1 if it is a leap year, 0 otherwise.
    $number = (intval(date("z")) + intval(date("H")) + intval(date("m")) + intval(date("s")) + intval(date("y")) + intval(date("L"))) . "";
    //number max = 365 + 23 + 59 + 59 + 99 + 1 = 606
    return (int)($carrierCode . substr($phonenum, 2, 2) . str_pad($number, 3, '9', STR_PAD_LEFT));
}
?>