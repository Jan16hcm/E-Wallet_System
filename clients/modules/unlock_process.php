<?php
session_start();
require_once 'db_connection.php';

// check if admin is logged in
if (!isset($_SESSION['verified']) || $_SESSION['verified'] != 3) {
    die("Access denied!");
}

if (isset($_GET['phone'])) {
    $phone_to_unlock = $_GET['phone'];

    // Reset verified to 1 (Active), reset abnormal_login to 0, remove locked_time
    $unlock_stmt = $conn->prepare("UPDATE `user` SET `verified` = 1, `abnormal_login` = 0, `locked_time` = NULL WHERE `phonenum` = ?");
    $unlock_stmt->bind_param("s", $phone_to_unlock);
    
    if ($unlock_stmt->execute()) {
        echo "<script>
            alert('Account unlocked successfully!');
            window.location.href = 'admin_dashboard.php';
        </script>";
    } else {
        echo "Error unlocking account!";
    }
}
?>