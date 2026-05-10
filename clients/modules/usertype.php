<?php
require_once("db_connection.php");
function usertype()
{
    //put this in places where login is required
    if (empty($_SESSION['email'])) {
        header('Location: ../pages/Login.php');
        exit;
    }
    $con = connect_db();
    $email = $_SESSION['email'];

    $stmt = $con->prepare("SELECT verified, abnormal_login, locked_time FROM user WHERE email = ?");
    $stmt->bind_param("s", $email); // "s" nghĩa là data type là string
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) { //check if database is not empty
        $row = $result->fetch_assoc();

        // If locked due to abnormal login or admin, treat as disabled (4)
        if ($row['abnormal_login'] >= 6 || !empty($row['locked_time'])) {
            $row['verified'] = 4;
        }

        $_SESSION["verified"] = $row["verified"];
        $re = $row["verified"];

        $stmt->close();
        $result->close();
        $con->close();
        return $re;
    }

    $stmt->close();
    $result->close();
    header('Location: ../pages/Login.php');
    exit();// if user is not found
}
function checkuser(int $usertype)
{
    //user -1, 0, 2, 4
    $error = '';
    if ($usertype != 1 && $usertype != 3) {
        if ($usertype == 4) {
            $error = 'Your account is disabled, please contact the admin';
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $error = 'Please contact the admin to reactive the account to use this feature';
            }
        } else if ($usertype == 2) {
            $error = 'Please update your ID card information to use this feature';
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $error = 'This feature requires an updated and verified ID card. Please visit your profile.';
            }
        } else {
            $error = 'This function is only for verified account';
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $error = 'Please wait for verification before using this feature';
            }
        }
    }
    return $error;
}

function redirectHome()
{
    $type = (int) ($_SESSION['verified'] ?? usertype());
    switch ($type) {
        case -1:
            header('Location: ChangePassword.php');
            break;
        case 0:
        case 2:
            header('Location: Profile.php');
            break;
        case 3:
            header('Location: Admin_dashboard.php');
            break;
        case 4:
            unset($_SESSION['email'], $_SESSION['name'], $_SESSION['verified']);
            $_SESSION['login_error'] = 'Your account has been disabled.';
            header('Location: Login.php');
            break;
        default:
            header('Location: Home.php');
            break;
    }
    exit();
}
?>