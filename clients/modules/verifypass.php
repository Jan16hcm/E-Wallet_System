<?php
require_once("db_connection.php");
function verifypass(string $pass, string $e_or_p, bool $isEmail)
{
    $con = connect_db();
    if ($isEmail) {
        $res = $con->prepare("SELECT `pass`, `email`, `phonenum`, `name`, `money` FROM `user` WHERE `email` = ? and (`abnormal_login` < 6 and `abnormal_login` != 3) and `verified` != 4");
        $res1 = $con->prepare("SELECT card_num FROM history WHERE user_phone = ? ORDER BY date_transfer desc limit 1");
        //get email where email = email
    } else {
        $res = $con->prepare("SELECT `pass`, `email`, `phonenum`, `name`, `money` FROM `user` WHERE `phonenum` = ? and (`abnormal_login` < 6 and `abnormal_login` != 3) and `verified` != 4");
    }
    $res->bind_param("s", $e_or_p);
    $res->execute();
    $real_res = $res->get_result(); // fixed: get_result() is a method of the statement object, not the result object
    if ($real_res->num_rows > 0) {    //check if database is not empty
        $row = $real_res->fetch_assoc();

        if (password_verify($pass, $row["pass"])) {
            $_SESSION["email"] = $row["email"];
            $_SESSION["phonenum"] = $row["phonenum"];
            $_SESSION["name"] = $row["name"];
            $_SESSION["money"] = $row["money"];

            $res = $con->prepare("SELECT `card_num` FROM `history` WHERE `user_phone` = ? ORDER BY `date_transfer` desc limit 1");
            $_SESSION["card_num"] = $row["card_num"] ?? '';
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