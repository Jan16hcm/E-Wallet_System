<?php 
    require_once("../modules/usertype.php");
    require_once("../modules/db_connection.php");
    $admin = $_SESSION["verified"];
    if($admin != 3) {
        header("Location: Login.php");
    } else {
        include("../src/header.php");   

        echo"Admin page";
    }
?>