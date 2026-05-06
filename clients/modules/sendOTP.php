<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/PHPMailer-master/src/Exception.php';
require '../vendor/PHPMailer-master/src/PHPMailer.php';
require '../vendor/PHPMailer-master/src/SMTP.php';

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
function sendRegistrationEmail($email, $name, $password) {
    $subject = "Welcome to MeoMeo Wallet - Account Created";
    $content = "
        <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
            <h3 style='color: #0d6efd;'>Welcome, $name!</h3>
            <p>Your account has been created successfully. You can now login using either your <b>Email</b> or <b>Phone Number</b>.</p>
            <div style='background: #f4f4f4; padding: 10px; border-radius: 5px; margin: 15px 0;'>
                <p style='margin: 0;'>Your temporary password is: <b style='font-size: 18px; color: #0d6efd;'>$password</b></p>
            </div>
            <p style='font-size: 13px; color: #666;'>Please change your password after logging in for the first time.</p>
        </div>";
    
    return sendEmail($email, $name, $subject, $content);
}
function sendOTPEmail($email, $recipientName, $otpCode) {
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
            <p>This code is valid for <b>5 minutes</b>. For security reasons, please do not share this code with anyone.</p>
            <p>If you did not request this, please ignore this email or contact our support team immediately.</p>
            <hr style='border: 0; border-top: 1px solid #eeeeee; margin: 20px 0;'>
            <p style='font-size: 12px; color: #858796; text-align: center;'>
                &copy; 2026 MeoMeo E-Wallet System. All rights reserved.
            </p>
        </div>";
    $altBody = "Your verification code is: $otpCode. This code is valid for 5 minutes.";
    return sendEmail($email, $recipientName, $subject, $content, $altBody);
}


?>