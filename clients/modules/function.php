<?php
    session_start();
    function connect_db(){
        $con = new mysqli("localhost", "root", "", "fakebank");
        if ($con->connect_error) {
            die("Connection failed: " . $con->connect_error);
        }
        $con->set_charset("utf8mb4");
        return $con;
    }
    function selectfromuserbyemail(String $obj, String $email, String $condition, bool $haveCondition){//what to select using unique email
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
    function usertype() {
        if (empty($_SESSION['email'])) {
            header('Location: /../login.php');
            exit;
        }
        $con = connect_db();
        $result = $con->query("select verified from user where email = " . $_SESSION['email']);
        if ($result->num_rows > 0) { //check if database is not empty
            $row = $result->fetch_assoc();
            $re = 0 == $row["verified"];
            $con->close();
            return $re;
        }
    }

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
                $_SESSION["email"] = $row["email"];
                $real_res->close();
                $res->close();
                $con->close();
                header('Location: Home.php');
            }

            $real_res->close();
        }
        $con->close();
        $res->close();
        $error = 'Wrong password';
        $lock = handleFailedLogin(new DateTime(), false, $e_or_p, $isEmail);
        return true;
    }
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
            return ['Empty database or wrong email/phone number', 0];
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
                return ['Error in database, please try again later', -2];
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
        return ['',-3];
    }
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

    function send_otp_email(String $otp, String $email, String $name){
    //template from https://github.com/Redwiat/otp-verification-email-template/blob/main/Email/otp-verification-email-template.html
        $message = '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title></title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
      color: #333;
      background-color: #fff; 
    }
    .container {
      margin: 0 auto;
      width: 100%;
      max-width: 600px;
      padding: 0 0px;
      padding-bottom: 10px;
      border-radius: 5px;
      line-height: 1.8;
    }
    .header {
      border-bottom: 1px solid #eee;
    }
    .header a {
      font-size: 1.4em;
      color: #000;
      text-decoration: none;
      font-weight: 600;
    }
    .content {
      min-width: 700px;
      overflow: auto;
      line-height: 2;
    }
    .otp {
      background: linear-gradient(to right, #00bc69 0, #00bc88 50%, #00bca8 100%);
      margin: 0 auto;
      width: max-content;
      padding: 0 10px;
      color: #fff;
      border-radius: 4px;
    }
    .footer {
      color: #aaa;
      font-size: 0.8em;
      line-height: 1;
      font-weight: 300;
    }
    .email-info {
      color: #666666;
      font-weight: 400;
      font-size: 13px;
      line-height: 18px;
      padding-bottom: 6px;
    }
    .email-info a {
      text-decoration: none;
      color: #00bc69;
    }
  </style>
</head>
<body>
  <div class="container">
    <strong>Dear '. $name . ',</strong>
    <p>We have received a verify request for your Fakebank account. For security purposes, please verify your identity by providing the following One-Time Password (OTP).
        <br/>
        <b>Your One-Time Password (OTP) verification code is:</b>
    </p>
    <h2 class="otp">' . $otp . '</h2>
    <p style="font-size: 0.9em">
      <strong>One-Time Password (OTP) is valid for 1 minute.</strong>
      <br/><br/>
      If you did not initiate this login request, please disregard this message. Please ensure the confidentiality of your OTP and do not share it with anyone.<br />
      <strong>Do not forward or give this code to anyone.</strong>
      <br/>
      <strong>Thank you for using FakeBank.</strong>
      <br/>
      Best regards,
      <br/>
      <strong>FakeBank</strong>
    </p>
    <hr style="border: none; border-top: 0.5px solid #131111" />
    <div class="footer">
      <p>
        For more information about FakeBank and your account, please contact the hotline <strong>18001008</strong>
      </p>
    </div>
  </div>
  <div style="text-align: center">
    <div class="email-info">
      &copy; 2026 FakeBank. All rights reserved.
    </div>
  </div>
</body>
</html>';
//This template is made Redwan one from Ocoxe.
//https://www.ocoxe.com
        if(mail($email, "verify-account-otp", $message, "From: FakeBank@gmail.com")){
            return true;
        } 
        return false;
    }
    function send_otp_sms(String $otp, String $phonenum, String $carrier){
        //Requires knowing the carrier of the recipient. //not working
        //Carrier: viettell...
        $to = $phonenum . "@" . $carrier;
        $message = "Your OTP is: " . $otp . "\n>Do not forward or give this code to anyone\nValid for 1 minute";
        if(mail($to, "verify-account-otp (this get ignored)", $message, "From: FakeBank@gmail.com")){
            return true;
        } 
        return false; 
    }
?>
