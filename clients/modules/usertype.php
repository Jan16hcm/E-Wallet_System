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
            $re = $row["verified"];
            
            $result->close();
            $con->close();
            return $re;
        }

        $stmt->close();
        $result->close();
        return null;// if user is not found
    }
?>