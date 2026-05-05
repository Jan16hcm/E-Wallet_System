<?php
    include_once("../modules/db_connection.php");
    include_once("../modules/send_otp.php");

    $error = '';
    $email = '';
    //$phonenum = '';
    //$information_type = -1;
    //$carrier = '';
    /*
    -1: no information entered
    0: email entered
    1: phone number entered 
    2: email entered is in database
    3: phone number entered is in database
    */
    $otp = '';
    if(isset($_SESSION['otp'])){
        $otp = strval($_SESSION['otp']);
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if(isset($_POST['otp_in6'])){
                $otp_in = $_POST['otp_in1'] . $_POST['otp_in2'] . $_POST['otp_in3'] . $_POST['otp_in4'] . $_POST['otp_in5'] . $_POST['otp_in6'];
                if(strcmp($otp_in, $otp) != 0) {
                    $error = 'Wrong OTP code';
                } else if($expire < time()){
                    $error = 'OTP code expired';
                    unset($_SESSION['otp']);
                    unset($_SESSION['otp_expire']);
                } else {
                    //success
                    $_SESSION['otp'] = "SUC";
                    unset($_SESSION['otp_expire']);
                    header('Location: Home.php');//unset otp in reset password
                    exit();
                }
            }
        }
    } else {
        $otp = sprintf('%06d', random_int(0, 999999));
        $expire = date('Y-m-d H:i:s', time() + 60);//a time in future
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expire'] = $expire;
    }

    if (isset($_POST['email'])) {
        $email = $_POST['email'];
        if (empty($email)) {
            $error = 'Please enter your email';
        } else if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
            $error = 'This is not a valid email address';
        } else {

        /*
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

        if($information_type != -1) {*/

            $con = connect_db();
            $findEmail = false;
            $name = '';
            $result = $con->query("SELECT email, name FROM user");

            if ($result->num_rows > 0) { //check if database is not empty
                while($row = $result->fetch_assoc()) { 
                    if($row["email"] == $email) {
                        //$information_type += 2;
                        $findEmail = true;
                        $name = $row["name"];
                        break;
                    }
                    /*
                    if($row["phonenum"] == $phonenum && $information_type == 1) {
                        $information_type += 2;
                        break;
                    }*/
                }
            }
            $con->close();
            if ($findEmail) {
                $_SESSION["email"] = $email;
                if(send_otp_email($otp, $email, $name)){
                    //echo "success";
                } else {
                    $error = 'Failed to send mail, please try again in a few minutes';
                }
            } else {
                $error = 'Email you entered is not available in database';
            }

            /*
            if($information_type > 1){
                if($information_type == 2){
                    $error = 'Email you entered is not available in database';
                } else {
                    $error = 'Phone number you entered is not available in database';
                }
            }*/
        }
        
        //otp here
        /*
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
        */
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/register.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/css/forgot.css">

    <title>Forgot Password</title>
</head>

<body>
    <?php include("../src/headerOutSide.php"); ?>
    <link rel="stylesheet" href="../assets/css/forgot.css">

    <body>
        <div class="container" style="max-width: 500px; margin: 80px auto;">
            <button class="btn btn-secondary mt-3 opacity-75 d-flex align-items-center justify-content-center"
                onclick="location.href='Login.php'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-box-arrow-left" viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                        d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0z" />
                    <path fill-rule="evenodd"
                        d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708z" />
                </svg>
                <span class="ms-2">Back to Login</span>
            </button>

            <div class="card p-4 mt-4 shadow-sm">
                <h2 class="mt-2">Forgot Password</h2>
                <p class="text-muted mb-4">Please enter your email to reset your password.</p>

                <form action="ResetPassword.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Your Email</label>
                        <input type="email" class="form-control" id="username" name="username" placeholder="Enter your email" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                    <?php
                        if (!empty($error)) {
                            echo "<div class='alert alert-danger'>$error</div>";
                        }
                    ?>
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
    </body>

    <?php include("../src/footer.php"); ?>
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
