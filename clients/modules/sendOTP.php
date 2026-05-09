<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/PHPMailer-master/src/Exception.php';
require '../../vendor/PHPMailer-master/src/PHPMailer.php';
require '../../vendor/PHPMailer-master/src/SMTP.php';

function sendEmail($recipientEmail, $recipientName, $subject, $content, $altBody = '')
{
    $mail = new PHPMailer(true);

    try {
        //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host = 'smtp.hostinger.com';                     //Set the SMTP server to send through
        $mail->SMTPAuth = true;                                   //Enable SMTP authentication
        $mail->Username = 'noreply@meomeo.baby';                     //SMTP username
        $mail->Password = '#Binsuong74128';                               //SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
        $mail->Port = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
        $mail->CharSet = 'UTF-8';

        //Recipients
        $mail->setFrom('noreply@meomeo.baby', 'MeoMeo Team');
        $mail->addAddress($recipientEmail, $recipientName);
        // $mail->addAddress('tranhoangkhai271625@gmail.com', 'Tran Hoang Khai');     //Add a recipient

        // $mail->addAddress('ellen@example.com');               //Name is optional
        // $mail->addReplyTo('info@example.com', 'Information'); // Neu muon nhieu nguoi nhan thi them addAddress
        // $mail->addCC('cc@example.com');
        // $mail->addBCC('bcc@example.com');

        //Attachments
        // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
        // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = $content;
        $mail->AltBody = $altBody ? $altBody : strip_tags($content);
        $mail->send();
        // echo 'Message has been sent';
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
function sendRegistrationEmail($email, $phonenum, $name, $password) {
    $subject = "Welcome to MeoMeo Wallet - Get Started Now!";
    $link_page = "http://meomeo.baby/clients/pages/Login.php";
    $brand_color = "#7C3AED";
    
    $content = "
        <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: auto; border: 1px solid #e0e0e0; border-radius: 16px; overflow: hidden;'>
            <div style='background-color: #1A1530; padding: 30px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>MeoMeo Wallet</h1>
                <p style='color: #B8AED4; margin-top: 10px;'>Your financial journey starts here!</p>
            </div>

            <div style='padding: 40px 30px; background-color: #ffffff; color: #333;'>
                <h2 style='color: $brand_color; margin-top: 0;'>Hi $name,</h2>
                <p style='line-height: 1.6;'>Welcome to the family! Your account has been successfully created. You can now manage your assets, track spending, and make transfers with ease.</p>
                
                <div style='background-color: #F8F7FF; border-left: 4px solid $brand_color; padding: 20px; margin: 25px 0;'>
                    <p style='margin: 0 0 10px 0; font-weight: bold; color: #555;'>Login Credentials:</p>
                    <p style='margin: 5px 0;'><b>Username:</b> $email (or $phonenum)</p>
                    <p style='margin: 5px 0;'><b>Temporary Password:</b> <span style='color: $brand_color; font-size: 18px; font-weight: bold;'>$password</span></p>
                </div>

                <h3 style='font-size: 18px; color: #333;'>Quick Start Guide:</h3>
                <ol style='line-height: 1.8; padding-left: 20px;'>
                    <li><b>Log in:</b> Access your dashboard using the credentials above.</li>
                    <li><b>Secure your account:</b> Go to settings and change your temporary password immediately.</li>
                    <li><b>Explore:</b> Link your cards and start your first transaction!</li>
                </ol>

                <div style='text-align: center; margin-top: 35px;'>
                    <a href='$link_page' style='background-color: $brand_color; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>Go to My Wallet</a>
                </div>
            </div>

            <div style='background-color: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eeeeee;'>
                <p style='font-size: 12px; color: #888; margin: 0;'>
                    If you did not create this account, please ignore this email or contact support.<br><br>
                    &copy; 2026 MeoMeo Wallet Team. All rights reserved.
                </p>
            </div>
        </div>
    ";

    return sendEmail($email, $name, $subject, $content);
}
function sendOTPEmail($email, $recipientName, $otpCode) {
    $minute = 1;//all otp is 1 minute
    $subject = 'Your OTP Verification Code';
    $content = "
        <div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <h2 style='color: #4e73df;'>MeoMeo E-Wallet</h2>
            </div>
            <p>Hi <b>$recipientName</b>,</p>
            <p>You are receiving this email because a verification request was made for your account.</p>
            <div style='background-color: #f8f9fc; padding: 15px; text-align: center; border-radius: 5px; margin: 20px 0;'>
                <span style='font-size: 24px; font-weight: bold; letter-spacing: 5px; color: #1cc88a;'>$otpCode</span>
            </div>
            <p>This code is valid for <b>$minute minutes</b>. For security reasons, please do not share this code with anyone.</p>
            <p>If you did not request this, please ignore this email or contact our support team immediately.</p>
            <hr style='border: 0; border-top: 1px solid #eeeeee; margin: 20px 0;'>
            <p style='font-size: 12px; color: #858796; text-align: center;'>
                &copy; 2026 MeoMeo E-Wallet System. All rights reserved.
            </p>
        </div>";
    $altBody = "Your verification code is: $otpCode. This code is valid for $minute minutes.";
    return sendEmail($email, $recipientName, $subject, $content, $altBody);
}


?>
