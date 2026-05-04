<?php 
    session_start();
    //include this file at the start of every php
    // require_once("../modules/db_connection.php");
    function connect_db(){
        $con = new mysqli("localhost", "root", "", "fakebank");
        if ($con->connect_error) {
            die("Connection failed: " . $con->connect_error);
        }
        $con->set_charset("utf8mb4");
        return $con;
    }
?>
