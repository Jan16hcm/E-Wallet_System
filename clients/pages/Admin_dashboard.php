<?php 
    session_start();
    if($_SESSION['verified'] != 3) {
        echo "khong phai admin khong cho truy cap";
        
    } else {
        echo"Admin page";
    }
?>