<?php
    require_once("db_connection.php");
    function handleFailedLogin(DateTime $time, bool $add_attem, String $e_or_p, bool $isEmail){
        /* add attempt count and locktime to user database depending on $add_attem
           using email or phonenum depending on $isEmail
        */ 
        //call new DateTime() to input into this function ($time)
        $con = connect_db();
        $attem_num = 0;
        $locktime = '';
        $res = '';
        if($isEmail) {
            $res = $con->prepare("SELECT abnormal_login, locktime from user where email = ?");
        } else {
            $res = $con->prepare("SELECT abnormal_login, locktime from user where phonenum = ?");
        }
        $res->bind_param("s", $e_or_p);
        $res->execute();

        if ($res->num_rows > 0) {
            $real_res = $res->get_result();
            $row = $real_res->fetch_assoc();
            $attem_num = $row["abnormal_login"];
            $locktime = $row["locktime"];
            $real_res->close();
            $res->close();
        } else {
            $res->close();
            return ['Empty database or wrong email/phone number', -2];
        }

        if ($attem_num > 6) { //this if is useless
            $con->close();
            $con->close();
            return ['Account has been locked due to entering the wrong password many times, please contact the administrator for support.', -1];
        }

        if ($add_attem) {
            if($isEmail) {
                $res = $con->prepare("update user set abnormal_login = " . ($attem_num + 1) . ", locktime = " . $locktime . " where email = ?");
            } else {
                $res = $con->prepare("update user set abnormal_login = " . ($attem_num + 1) . ", locktime = " . $locktime . " where phonenum = ?");
            }
            $res->bind_param("s", $e_or_p);

            if(!$res->execute()) {
                $res->close();
                $con->close();
                return ['Error in database, please try again later', -3];
            }
        }

        $con->close();
        if ($attem_num > 3) {
            $time_passed = $time->getTimestamp() - $locktime->getTimestamp();
            if($time_passed < 60){
                $diff = 60 - $time_passed;
                return ['Account is currently locked, please try again in ' . $diff . ' seconds', $time_passed];
            }
        }
        //( First 3 attempt or ( > 60 second passed in attempt 4,5,6 ) ) return -3
        return ['',-4];
    }
?>