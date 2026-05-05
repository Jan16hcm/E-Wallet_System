<?php
    require_once("db_connection.php");
    function usertype() {
        //put this in places where login is required
        if (empty($_SESSION['email'])) {
            header('Location: /../pages/Login.php');
            exit;
        }
        $con = connect_db();
        $result = $con->query("select verified from user where email = " . $_SESSION['email']);
        if ($result->num_rows > 0) { //check if database is not empty
            $row = $result->fetch_assoc();
            $re = $row["verified"];
            $result->close();
            $con->close();
            return $re;
        }
    }
?>