<?php
    require_once("db_connection.php");
    function usertype() {
        //put this in places where login is required
        if (empty($_SESSION['email'])) {
            header('Location: ../pages/Login.php');
            exit;
        }
        $con = connect_db();
        $email = $_SESSION['email'];

        $stmt = $con->prepare("SELECT verified FROM user WHERE email = ?");
        $stmt->bind_param("s", $email); // "s" nghĩa là data type là string
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) { //check if database is not empty
            $row = $result->fetch_assoc();
            $_SESSION["verified"] = $row["verified"];
            $re = $row["verified"];
            
            $result->close();
            $con->close();
            return $re;
        }

        $stmt->close();
        $result->close();
        return null;// if user is not found
    }
    function checkuser(int $usertype){
        //user -1, 0, 2, 4
        $error = '';
        if ($usertype != 1 && $usertype != 3) {
            if($usertype == 4){
                $error = 'Your account is disabled, please contact the admin';
                if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                    $error = 'Please contact the admin to reactive the account to use this feature';
                }
            } else {
                $error = 'This function is only for verified account';
                if ($_SERVER['REQUEST_METHOD'] == 'POST'){
                    $error = 'Please wait for verification before using this feature';
                }
            }
        }
        return $error;
    }
?>