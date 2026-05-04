<?php
session_start();
require_once 'db_connection.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phonenum = $_POST['phonenum'];
    $password = $_POST['pass'];

    $sql = "SELECT * FROM `user` WHERE `phonenum` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $phonenum);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Admin never locke
        if ($user['verified'] == 3) {
            if (password_verify($password, $user['pass'])) {
                $_SESSION['user_phone'] = $user['phonenum'];
                $_SESSION['verified'] = $user['verified'];
                header("Location: ../pages/Admin_dashboard.php");
                exit();
            } else {
                die("Wrong admin password!");
            }
        }

        // Disable account overtime
        if ($user['verified'] == 4) {
            die("Account has been locked due to entering the wrong password many times, please contact the administrator for support");
        }

        $session_lock_key = 'temp_lock_' . $phonenum;
        if (isset($_SESSION[$session_lock_key])) {
            $time_passed = time() - $_SESSION[$session_lock_key];
            if ($time_passed < 60) { 
                die("Account is currently locked, please try again in 1 minute");
            } else {
                unset($_SESSION[$session_lock_key]); 
            }
        }

        
        if (password_verify($password, $user['pass'])) {
            // Đăng nhập thành công -> Reset số lần sai về 0
            $reset_login = $conn->prepare("UPDATE `user` SET `abnormal_login` = 0 WHERE `phonenum` = ?");
            $reset_login->bind_param("s", $phonenum);
            $reset_login->execute();

            $_SESSION['user_phone'] = $user['phonenum'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['verified'] = $user['verified'];
            
            $status = $user['verified'];
            // if ($status == 3) {
            //     // Admin
            //     header("Location: ../pages/Admin_dashboard.php");
            //     exit();
            
            if ($status == 1) {
                header("Location: ../pages/Home.php");
                exit();
            } elseif ($status == 0) {
                // User waiting for approval
                header("Location: ../pages/Waiting_approval.php");
                exit();
            } elseif ($status == 2) {
                // update cmnd
                header("Location: ../pages/Update_cmnd.php");
                exit();
            }

        } else {
            $fails = $user['abnormal_login'] + 1; // update wrong times
            
            if ($fails == 6) {
                // Disable account overtime
                $lock_stmt = $conn->prepare("UPDATE `user` SET `abnormal_login` = ?, `verified` = 4, `locked_time` = NOW() WHERE `phonenum` = ?");
                $lock_stmt->bind_param("is", $fails, $phonenum);
                $lock_stmt->execute();
                
                die("Account has been locked due to entering the wrong password many times, please contact the administrator for support");

            } elseif ($fails == 3) {
                $_SESSION[$session_lock_key] = time();
                
                $update_fails = $conn->prepare("UPDATE `user` SET `abnormal_login` = ? WHERE `phonenum` = ?");
                $update_fails->bind_param("is", $fails, $phonenum);
                $update_fails->execute();
                
                die("Account is currently locked, please try again in 1 minute");

            } else {
                $update_fails = $conn->prepare("UPDATE `user` SET `abnormal_login` = ? WHERE `phonenum` = ?");
                $update_fails->bind_param("is", $fails, $phonenum);
                $update_fails->execute();
                
                echo "Wrong password! (Failed attempts: $fails/6)";
            }
        }
    } else {
        echo "Account not found!";
    }
}
?>