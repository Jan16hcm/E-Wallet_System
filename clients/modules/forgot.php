<?php
    include_once("../modules/function.php");
    $error = '';
    $email = '';
    $phonenum = '';
    $information_type = -1;
    $carrier = '';
    /*
    -1: no information entered
    0: email entered
    1: phone number entered 
    2: email entered is in database
    3: phone number entered is in database
    */
    $otp = 0;
    if(isset($_SESSION['otp'])){
        $otp = strval($_SESSION['otp']);
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if(isset($_POST['otp_in6'])){
                $otp_in = $_POST['otp_in1'] . $_POST['otp_in2'] . $_POST['otp_in3'] . $_POST['otp_in4'] . $_POST['otp_in5'] . $_POST['otp_in6'];
                if(strval($otp_in) == $otp){
                    //sucess
                    $_SESSION['otp'] = "SUC";
                    header('Location: pofile.php');
                    exit();
                }
            }
        }
    } else {
        $otp=strval(rand(100000,999999));
        $_SESSION['otp'] = $otp;
    }

    if (isset($_POST['email']) || isset($_POST['phonenum'])) {
        if(isset($_POST['email'])){
            $email = $_POST['email'];
            if (empty($email)) {
                $error = 'Please enter your email';
            } else if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
                $error = 'This is not a valid email address';
            } else {
                $information_type = 0;
            }
        }

        if(isset($_POST['phonenum']) && $information_type != 0){
            $phonenum = filter_var($_POST['phonenum'], FILTER_SANITIZE_NUMBER_INT);
            if (empty($phonenum)) {
                $error = 'Please enter your phone number';
            } else if ($phonenum < 5 || $phonenum > 15) {
                $error = 'This is not a valid phone number';
            } else {
                $information_type = 1;
            }
        }

        if($information_type != -1) {
            $con = connect_db();

            $result = $con->query("SELECT email, phonenum FROM account");
            if ($result->num_rows > 0) { //check if database is not empty
                while($row = $result->fetch_assoc()) { 
                    if($row["email"] == $email && $information_type == 0) {
                        $information_type += 2;
                        break;
                    }
                    if($row["phonenum"] == $phonenum && $information_type == 1) {
                        $information_type += 2;
                        break;
                    }
                }
            }
            $con->close();

            if($information_type > 1){
                if($information_type == 2){
                    $error = 'Email you entered is not available in database';
                } else{
                    $error = 'Phone number you entered is not available in database';
                }
            }
        }
        
        //otp here
        if($information_type == 2){
            if(send_otp_email($otp, $email, $name)){
                $information_type += 2;
            } else {
                $error = 'Failed to send mail, please try again in a few minutes';
            }
        }

        if($information_type == 3){
            if(send_otp_sms($otp, $phonenum, $carrier)){
                //echo 'sms sent';
                $information_type += 2;
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

                    <label for="carrier">Carrier</label>
                    <select name="carrier" id="carrier" required class="form-control">
                        <option value="txt.att.net" selected>ATT</option> 
                        <option value="" disabled>More is not being supported</option>
                    </select>
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
            <br>
            <label for="otp_in">Enter OTP here:</label>
            <table>
                <tr>
                    <th><input name="otp_in1" id="otp_in" type="text" maxlength="1"></th>
                    <th><input name="otp_in2" id="otp_in" type="text" maxlength="1"></th>
                    <th><input name="otp_in3" id="otp_in" type="text" maxlength="1"></th>
                    <th><input name="otp_in4" id="otp_in" type="text" maxlength="1"></th>
                    <th><input name="otp_in5" id="otp_in" type="text" maxlength="1"></th>
                    <th><input name="otp_in6" id="otp_in" type="text" maxlength="1"></th>
                </tr>
            </table>
        </div>
    </div>
</div>

</body>
<script>
    const inputs = document.querySelectorAll("table input");

    inputs.forEach((input, index) => {
        input.addEventListener("input", () => {
            if (input.value.length === input.maxLength) {
                const nextInput = inputs[index + 1];
                if (nextInput) {
                    nextInput.focus();
                }
            }
        });

        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && input.value.length === 0) {
                const prevInput = inputs[index - 1];
                if (prevInput) {
                    prevInput.focus();
                }
            }
        });
    });
</script>
</html>
