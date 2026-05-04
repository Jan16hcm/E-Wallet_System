<?php
    require_once("db_connection.php");
    function verifypass(String $pass, String $e_or_p, bool $isEmail) {
        $con = connect_db();
        if($isEmail) {
            $res = $con->prepare("select pass, email from user where email = ? and abnormal_login < 7");
            //get email where email = email
        } else {
            $res = $con->prepare("select pass, email from user where phonenum = ? and abnormal_login < 7");
        }
        $res->bind_param("s", $e_or_p);
        $res->execute();
        if ($res->num_rows > 0) {    //check if database is not empty
            $real_res = $res->get_result();
            $row = $real_res->fetch_assoc();

            if(password_verify($pass, $row["pass"])) {
                $_SESSION["phonenum"] = $row["phonenum"];
                $_SESSION["name"] = $row["name"];
                $_SESSION["email"] = $row["email"];
                $real_res->close();
                $res->close();
                $con->close();
                return true;
            }

            $real_res->close();
        }
        $con->close();
        $res->close();
        return false;
    }
?>