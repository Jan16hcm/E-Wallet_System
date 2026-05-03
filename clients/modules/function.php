<?php
    session_start();
    function connect_db(){
        $con = new mysqli("localhost", "root", "", "databasename");
        if ($con->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        return $con;
    }
    function selectfromuserbyemail($obj, $email, $condition){//what to select using unique email
        $con = connect_db();
        $result = $con->query("select " . $obj . " from user where email = " . $email . " and " . $condition);
        $num_row = $result->num_rows;
        $con->close();
        return $num_row == 0;//true -> user failed condition or no data
    }
    function usertype() {
        if (empty($_SESSION['email'])) {
            header('Location: /../login.php');
            exit;
        }
        $con = connect_db();
        $result = $con->query("select verified from user where email = " . $_SESSION['email']);
        if ($result->num_rows > 0) { //check if database is not empty
            $row = $result->fetch_assoc();
            return = $row["verified"];
            $con->close();
            exit;
        }
    }
    function handleFailedLogin($time){
        //call new DateTime() to input into this function ($time)
        $con = connect_db();
        $result = $con->query("select abnormal_login from user where email = " . $_SESSION['email']);
        $attem_num = 0;
        if ($result->num_rows > 0) { //check if database is not empty
            $row = $result->fetch_assoc();
            $attem_num = $row["verified"];
        }
        if ($attem_num >= 6) {
            return ['Account has been locked due to entering the wrong password many times, please contact the administrator for support.', -1];
        }
        if(!$con->query("update user set abnormal_login = " . $attem_num . " where email = " . $_SESSION['email'])) {
            $con->close();
            return ['Error in database, please try again later', -2];
        }
        $con->close();
        if ($attem_num >= 3) {
            $now = new DateTime();
            $time_passed = $now->getTimestamp() - $time->getTimestamp();
            if($time_passed < 60){
                $diff = 60 - $time_passed;
                return ['Account is currently locked, please try again in ' . $diff . ' seconds', $time_passed];
            }
        }
        //( First 3 attempt or ( > 60 second passed in attempt 4,5,6 ) ) return -3
        return ['',-3];
    }
    function filter_account($type){
        //1: account wait for activation
        //2: activated account
        //3: disabled account
        //4: locked account
        $con = connect_db();
        $result = null;
        if($type == 1){//accounts waiting for activation
            $result = $con->query("select * from user where verified = -1 or verified = 2");
        }
        if($type == 2){//activated account
            $result = $con->query("select * from user where verified = 1");
        }
        if($type == 3){//disabled account
            $result = $con->query("select * from user where verified = 0");
        }
        if($type == 4){//locked account
            $result = $con->query("select * from user where abnormal_login >= 6");
        }
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } 
        return "error";
    }
?>
