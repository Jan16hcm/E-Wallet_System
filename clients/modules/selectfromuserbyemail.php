<?php
require_once("db_connection.php");
function selectfromuserbyemail(string $obj, string $email, string $condition, bool $haveCondition)
{//what to select using unique email
    $con = connect_db();
    if ($haveCondition) {
        $result = $con->prepare("select ? from user where email = ? and ?");
        $result->bind_param("sss", $obj, $email, $condition);
        $result->execute();
        $num_row = $result->num_rows;
        $con->close();
        $result->close();
        return $num_row == 0;//true -> failed condition
    }
    $result = $con->prepare("select ? from user where email = ?");
    $result->bind_param("ss", $obj, $email);
    $result->execute();
    $num_row = $result->num_rows;
    $con->close();
    $result->close();
    return $num_row == 0;//true -> no data
}
?>