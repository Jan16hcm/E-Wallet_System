<?php
    //session_start();
    $error = '';
    $email = '';
    $phonenum = '';
    $information_entered = '';
    if (isset($_POST['email']) || isset($_POST['phonenum'])) {
        if(isset($_POST['email'])){
            $email = $_POST['email'];
            if (empty($email)) {
                $error = 'Please enter your email';
            } else if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
                $error = 'This is not a valid email address';
            } else {
                $information_entered = '0';
            }
        }

        if(isset($_POST['phonenum']) && empty($information_entered)){
            $phonenum = filter_var($_POST['phonenum'], FILTER_SANITIZE_NUMBER_INT);
            if (empty($phonenum)) {
                $error = 'Please enter your phone number';
            } else if ($phonenum < 5 || $phonenum > 15) {
                $error = 'This is not a valid phone number';
            } else {
                $information_entered = '1';
            }
        }

        if(!empty($information_entered)) {
            $conn = new mysqli("localhost", "root", "", "database");
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $result = $conn->query("SELECT * FROM user");
            if ($result->num_rows > 0) { //check if database is not empty
                while($row = $result->fetch_assoc()) { 
                    if($row["email"] == $email || $row["phonenum"] == $phonenum) {
                        $information_entered = "+" . $information_entered;
                    }
                }
            }
            $conn->close();
            if($information_entered[0] != "+"){
                if($information_entered[0] == "0"){
                    $error = 'Email you entered is not available in database';
                } else{
                    $error = 'Phone number you entered is not available in database';
                }
            }
        }
        
        //otp here
        if($information_entered == "+0"){
            $otp=strval(rand(100000,999999));
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
    <strong>Dear '. $user . ',</strong>
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
</html>'
//This template is made Redwan one from Ocoxe.
//https://www.ocoxe.com
            if(mail($email, "verify-account-otp", $message, "From: FakeBank@gmail.com")){
                //echo 'mail sent';
                $information_entered = "+0+";
            } else {
                $error = 'Failed to send mail, please try again in a few minutes';
            }
        }

        if($information_entered == "+1"){
            //Requires knowing the carrier of the recipient.
            //Carrier: viettell...
            $carrierGateway = "Viet"
            $to = $phonenum . "@" . $carrierGateway;
            $otp = strval(rand(100000,999999));
            $message = wordwrap("Your OTP is: " . $otp . "\n>Do not forward or give this code to anyone\nValid for 1 minute", 70);
            if(mail($email, "verify-account-otp (this get ignored)", $message, "From: FakeBank@gmail.com")){
                //echo 'sms sent';
                $information_entered = "+1+";
            } else {
                $error = 'Failed to send sms, please try again in a few minutes';
            }
        }
    }
?>

<DOCTYPE html>
<html lang="en">
<head>
    <title>Conform OTP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <h3 class="text-center text-secondary mt-5 mb-3">Send OTP</h3>
            <form method="post" action="" class="border rounded w-100 mb-5 mx-auto px-3 pt-3 bg-light">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input name="email" id="email" type="text" class="form-control" placeholder="Email address">
                </div>
                <div class="form-group">
                    <label for="phonenum">Phone number</label>
                    <input name="phonenum" id="phonenum" type="text" class="form-control" placeholder="Phone Number">
                </div>
                <div class="form-group">
                    <p>If your email/phone number exists in the database, you will receive an otp from the server.</p>
                </div>
                <div class="form-group">
                    <?php
                        if (!empty($error)) {
                            echo "<div class='alert alert-danger'>$error</div>";
                        }
                    ?>
                    <button class="btn btn-success px-5" type="submit">Send OTP</button>
                </div>
            </form>
            <p>Enter OTP here:</p>
            <br>
            <table>
              <tr>
                <th>
                  <label for="otp_in">OTP</label>
                  <input name="otp_in" id="otp_in" type="text">
                </th>
              </tr>
            </table>
            <button>Conform</button>
        </div>
    </div>
</div>

</body>
</html>
