<?php
require_once("db_connection.php");

header('Content-Type: application/json');

$phone = $_GET['phone'] ?? '';
$response = ['found' => false, 'name' => ''];

if (!empty($phone)) {
    $con = connect_db();
    $stmt = $con->prepare("SELECT `name`, `verified`, `abnormal_login`, `locked_time` FROM `user` WHERE `phonenum` = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->bind_result($name, $verified, $abnormal_login, $locked_time);
    if ($stmt->fetch()) {
        if ($verified != 3 && $verified != 4 && $abnormal_login < 6 && empty($locked_time)) {
            $response['found'] = true;
            $response['name'] = $name;
        }
    }
    $stmt->close();
    $con->close();
}

echo json_encode($response);
?>
