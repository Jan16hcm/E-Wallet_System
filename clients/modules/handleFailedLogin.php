<?php
    require_once("db_connection.php");
    function handleFailedLogin(DateTime $time, bool $add_attem, String $e_or_p, bool $isEmail){
        /* add attempt count and locked_time to user database depending on $add_attem
           using email or phonenum depending on $isEmail
        */ 
        //call new DateTime() to input into this function ($time)
        $con = connect_db();
        $attem_num = 0;
        $locked_time = '';
        $res = '';
        if($isEmail) {
            $res = $con->prepare("SELECT abnormal_login, locked_time from user where email = ?");
        } else {
            $res = $con->prepare("SELECT abnormal_login, locked_time from user where phonenum = ?");
        }
        $res->bind_param("s", $e_or_p);
        $res->execute();
        $real_res = $res->get_result();

        if ($real_res->num_rows > 0) {
            $row = $real_res->fetch_assoc();
            $attem_num = $row["abnormal_login"];
            $locked_time = $row["locked_time"];
            $real_res->close();
            $res->close();
        } else {
            $res->close();
            return ['Wrong email/phone number', -2]; // Dung co ghi cho nguoi dung biet la empty database by Khai
        }

        if ($attem_num > 6) { //this if is useless
            $con->close();
            $con->close();
            return ['Account has been locked due to entering the wrong password many times, please contact the administrator for support.', -1];
        }

        if ($add_attem) {
            if($isEmail) { // Ong quen boc $locked_time vao` ' ' khi update, nen no se bi loi khi $locked_time = '' (first 3 attempt)
                $res = $con->prepare("update user set abnormal_login = " . ($attem_num + 1) . ", locked_time = '" . $locked_time . "' where email = ?");
            } else {
                $res = $con->prepare("update user set abnormal_login = " . ($attem_num + 1) . ", locked_time = '" . $locked_time . "' where phonenum = ?");
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
            // $time_passed = $time->getTimestamp() - $locked_time->getTimestamp();
            $locked_time_obj = empty($locked_time) ? new DateTime() : new DateTime($locked_time);
            $time_passed = $time->getTimestamp() - $locked_time_obj->getTimestamp();
            if($time_passed < 60){
                $diff = 60 - $time_passed;
                return ['Account is currently locked, please try again in ' . $diff . ' seconds', $time_passed];
            }
        }
        //( First 3 attempt or ( > 60 second passed in attempt 4,5,6 ) ) return -3
        return ['',-4];
    }
?>