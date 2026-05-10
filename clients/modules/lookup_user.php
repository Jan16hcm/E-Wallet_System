<?php
require_once("db_connection.php");

header('Content-Type: application/json');

$phone = $_GET['phone'] ?? '';
$response = ['found' => false, 'name' => ''];

if (!empty($phone)) {
    $con = connect_db();
    $stmt = $con->prepare("SELECT `name` FROM `user` WHERE `phonenum` = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->bind_result($name);
    if ($stmt->fetch()) {
        $response['found'] = true;
        $response['name'] = $name;
    }
    $stmt->close();
    $con->close();
}

echo json_encode($response);
?>
