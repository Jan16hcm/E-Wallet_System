<?php
    require_once("db_connection.php");
    function filter_account(int $type){
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