<?php
require_once("db_connection.php");
function handleFailedLogin(DateTime $time, string $e_or_p, bool $isEmail)
{
    /* add attempt count and locked_time to user database depending on $add_attem
       using email or phonenum depending on $isEmail
    */
    //call new DateTime() to input into this function ($time)
    $con = connect_db();
    $attem_num = 0;
    $locked_time = '';
    $res = '';
    $user_type = 1;
    if ($isEmail) {
        $res = $con->prepare("SELECT abnormal_login, locked_time, verified from user where email = ?");
    } else {
        $res = $con->prepare("SELECT abnormal_login, locked_time, verified from user where phonenum = ?");
    }
    $res->bind_param("s", $e_or_p);
    $res->execute();
    $real_res = $res->get_result();
    if ($real_res->num_rows === 0) {
        password_verify('dummy', '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012'); // Avoid Timing Attack
        return ['Invalid email/phone number or password', -2];
    }
    $row = $real_res->fetch_assoc();
    $attem_num = (int) $row["abnormal_login"];
    $locked_time = $row["locked_time"];
    $user_type = (int) $row["verified"];
    $res->close();

    if ($user_type === 3) { // Admin never locked
        return ['Wrong password', -4];
    }

    if ($attem_num === 3 && !empty($locked_time)) {
        $locked_time_obj = new DateTime($locked_time);
        $time_passed = $time->getTimestamp() - $locked_time_obj->getTimestamp();

        if ($time_passed < 60) {
            $diff = 60 - $time_passed;
            return ["Account is currently locked, please try again in $diff seconds", $time_passed];
        }
    }

    if ($attem_num >= 6) {
        return ['Account has been locked due to entering the wrong password many times, please contact the administrator for support.', -1];
    }
    $new_attem = $attem_num + 1;
    $new_locked_time = $locked_time;

    if ($new_attem === 3) {
        $new_locked_time = $time->format('Y-m-d H:i:s');
    }
    if ($new_attem === 6) {
        $new_locked_time = $time->format('Y-m-d H:i:s');
    }

    $updateQuery = $isEmail ? "UPDATE `user` SET `abnormal_login` = ?, `locked_time` = ? WHERE `email` = ?"
        : "UPDATE `user` SET `abnormal_login` = ?, `locked_time` = ? WHERE phonenum = ?";
    $stmt = $con->prepare($updateQuery);
    $stmt->bind_param("iss", $new_attem, $new_locked_time, $e_or_p);
    $stmt->execute();
    $stmt->close();

    if ($new_attem == 3) {
        return ['Account is currently locked, please try again in 60 seconds', 0];
    }
    if ($new_attem >= 6) {
        $updateQuery = $isEmail
            ? "UPDATE `user` SET `abnormal_login` = ?, `locked_time` = ?, `verified` = 4 WHERE `email` = ?"
            : "UPDATE `user` SET `abnormal_login` = ?, `locked_time` = ?, `verified` = 4 WHERE `phonenum` = ?";
        $stmt = $con->prepare($updateQuery);
        $stmt->bind_param("iss", $new_attem, $new_locked_time, $e_or_p);
        $stmt->execute();
        $stmt->close();
        return ['Account has been locked...', -1];
    }

    return ['Invalid email/phone number or password', -4];
}
?>