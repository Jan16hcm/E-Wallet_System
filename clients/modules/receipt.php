<?php 
// the money is formated before passed into this function
    function send_receipt(String $formatedMoneysent, String $formatedMoneytotal, String $email, String $sendername, String $name, String $note){
    //"Once the money transfer is confirmed as successful, the recipient will 
    //receive an automatic email notifying about the receipt and balance fluctuations"
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
    <strong>Dear '. htmlspecialchars($name) . ',</strong>
    <p><strong>'. htmlspecialchars($sendername) . '</strong> has sent you <h3>' . $formatedMoneysent . '</h3></p>
    <br/>
    <p>Note: ' . htmlspecialchars($note) . '</p>
    <br/>
    <p>Your new balance: <strong>' . $formatedMoneytotal . '</strong></p>
    <br/>
      <strong>Thank you for using MeoMeo.</strong>
      <br/>
      Best regards,
      <br/>
      <strong>MeoMeo</strong>
    </p>
    <hr style="border: none; border-top: 0.5px solid #131111" />
    <div class="footer">
      <p>
        For more information about MeoMeo and your account, please contact the hotline <strong>18001008</strong>
      </p>
    </div>
  </div>
  <div style="text-align: center">
    <div class="email-info">
      &copy; 2026 MeoMeo. All rights reserved.
    </div>
  </div>
</body>
</html>';
//This template is made Redwan one from Ocoxe.
//https://www.ocoxe.com
        if(mail($email, "MeoMeo - You Received Money!", $message, "From: FakeBank@gmail.com")){
            return true;
        } 
        return false;
    }
?>