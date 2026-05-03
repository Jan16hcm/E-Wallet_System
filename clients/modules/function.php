<?php
    session_start();
    function connect_db(){
        $con = new mysqli("localhost", "root", "", "databasename");
        if ($con->connect_error) {
            die("Connection failed: " . $con->connect_error);
        }
        return $con;
    }
    function selectfromuserbyemail(String $obj, String $email, String $condition){//what to select using unique email
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
            $re = 0 == $row["verified"];
            $con->close();
            return $re;
        }
    }
    function handleFailedLogin(DateTime $time){
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
        //Requires knowing the carrier of the recipient.
        //Carrier: viettell...
        $to = $phonenum . "@" . $carrier;
        $message = "Your OTP is: " . $otp . "\n>Do not forward or give this code to anyone\nValid for 1 minute";
        if(mail($to, "verify-account-otp (this get ignored)", $message, "From: FakeBank@gmail.com")){
            return true;
        } 
        return false; 
    }
?>
