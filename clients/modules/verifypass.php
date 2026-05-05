<?php
    require_once("db_connection.php");
    function verifypass(String $pass, String $e_or_p, bool $isEmail) {
        $con = connect_db();
        if($isEmail) {
            $res = $con->prepare("select pass, email, phonenum, name from user where email = ? and abnormal_login < 7");
            //get email where email = email
        } else {
            $res = $con->prepare("select pass, email, phonenum, name from user where phonenum = ? and abnormal_login < 7");
        }
        $res->bind_param("s", $e_or_p);
        $res->execute();
        $real_res = $res->get_result(); // fixed: get_result() is a method of the statement object, not the result object
        if ($real_res->num_rows > 0) {    //check if database is not empty
            $row = $real_res->fetch_assoc();
    
            if(password_verify($pass, $row["pass"])) {
                $_SESSION["email"] = $row["email"];
                $_SESSION["phonenum"] = $row["phonenum"];
                $_SESSION["name"] = $row["name"];
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