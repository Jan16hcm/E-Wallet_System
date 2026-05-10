<?php
include_once("../modules/db_connection.php");
define('CARRIERS', ['Viettel' => '11111', 'Mobifone' => '22222', 'Vinaphone' => '33333']);
define('CARD_DENOMINATIONS', [10000, 20000, 50000, 100000]);
function generateIdCode(string $phonenum, int $type)
{
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
function generateCardCode(string $carrierCode)
{
    // Return a 10-digit sequence: first 5 are carrier code, next 5 are random
    $randomPart = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
    return $carrierCode . $randomPart;
}
/*
$count = 0;
$con = connect_db();
$stmt = $con->prepare("SELECT count(*) FROM phonecard WHERE LEFT(code, 5) = ?");//code here is int
$stmt->bind_param("s", $carrierCode);
if(!$stmt->execute()){
    $stmt->close();
    $con->close();
    return ['Error in database, please try again later', -1];
} else {
    $stmt->bind_result($count);
    if (!$stmt->fetch()) {
        $count = 0;
    }

    if($count == 100000){//already have 1111199999
        $stmt = $con->query("SELECT id FROM history WHERE date_transfer IS NOT NULL ORDER BY date_transfer ASC LIMIT 1");
        $row = $stmt->fetch_assoc();
        $oldest_id = $row["id"];

        $stmt = $con->prepare("SELECT code FROM phonecard WHERE id = ? LIMIT 1");//only 1
        $stmt->bind_param("s", $oldest_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();

        $stmt = $con->prepare("DELETE FROM history WHERE id = ?");
        $stmt->bind_param("s", $oldest_id);
        $stmt->execute();

        $stmt = $con->prepare("DELETE FROM phonecard WHERE id = ?");
        $stmt->bind_param("s", $oldest_id);
        $stmt->execute();
    }
}
$stmt->close();
$con->close();
return $count;
*/

?>