<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//include this file at the start of every php
// require_once("../modules/db_connection.php");
function connect_db()
{
    // Avoid Hardcode credentials in the future
    // $env = parse_ini_file(__DIR__ . '/../../.env'); 
    // $con = new mysqli(
    //     $env['DB_HOST'],
    //     $env['DB_USER'],
    //     $env['DB_PASS'],
    //     $env['DB_NAME']
    // );
    $con = new mysqli("localhost", "root", "", "fakebank");
    if ($con->connect_error) {
        error_log("DB connection failed: " . $con->connect_error); // log server-side
        die("Service unavailable"); // message chung chung cho user
    }
    $con->set_charset("utf8mb4");
    return $con;
}
?>