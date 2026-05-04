<?php
    include_once("../modules/db_connection.php");
    include_once("../modules/handleFailedLogin.php");
    include_once("../modules/verifypass.php");
    $e_or_p = '';
    $pass = '';
    $error = '';
    $do_timeout = false;
    $lock = 0;
    $isEmail = false;
    $abnormal_login = 0;

    if (isset($_POST['e_or_p']) && isset($_POST['pass'])){
        $e_or_p = $_POST['e_or_p'];
        $pass = $_POST['pass'];
        $isEmail = str_contains($e_or_p,'@');

        if (empty($e_or_p)) {
            $error = 'Please enter your email or phone number';
        } else if (empty($pass)) {
            $error = 'Please enter your password';
        } else if ($pass < 6) {
            $error = 'Your password length must be greater than 5';
        } else {
            if ($isEmail) {
                if (filter_var($e_or_p, FILTER_VALIDATE_EMAIL) == false) {
                    $error = 'This is not a valid email address';
                } else {
                    if(verifypass($pass, $e_or_p, $isEmail)){
                        //echo "correct pass, move to pofile";
                        header('Home.php');
                    }
                    //if failed password verify, it will continue below:
                    $do_timeout = true;
                    $error = 'Wrong password';//it could be because attem_num > 3 in 60 sec or attem_num > 6
                    $lock = handleFailedLogin(new DateTime(), false, $e_or_p, $isEmail);
                }
            } else {
                if ($e_or_p < 5 || $e_or_p > 15) {
                    $error = 'This is not a valid phone number';
                } else {
                    if(verifypass($pass, $e_or_p, $isEmail)){
                        header('Home.php');
                    }
                    $do_timeout = true;
                    $error = 'Wrong password';
                    $lock = handleFailedLogin(new DateTime(), false, $e_or_p, $isEmail);
                }
            }
        }
    }

    if($do_timeout) {
        if($lock[1] > -2){ // include -1 and > 0
            $error = $lock[0];
        } else {
            //add time out here
            $lock = handleFailedLogin(new DateTime(), true, $e_or_p, $isEmail);
            if($lock[1] > -2){
                $error = $lock[0];
            }
        }
    } 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/css/login.css">
    <title>Login</title>
</head>

<?php include("../src/header.php") ?>
<body>
    <form action="Home.php">
        <!-- <div class="container-fluid"> -->
            <div class="login-container" style="scrollbar-width: none;">
                <div class="row login-box">
                    <div id="loginMenu" class="col-lg-6 p-4">
                        <h3 class="mb-4 text-center">Sign in</h3>

                        <div class="mb-4">
                            <label for="e_or_p" class="form-label fs-5">Phone Number</label>
                            <input type="text" class="form-control p-3 fs-5" placeholder="Type your Phone Number" id="e_or_p" value="e_or_p">
                        </div>

                        <div class="mb-3">
                            <label for="pass" class="form-label fs-5">Password</label>
                            <input type="password" class="form-control p-3 fs-5" placeholder="Type your password here" id="pass" value="pass">
                        </div>

                        <button type="submit"
                            class="btn btn-success w-100 py-2 mt-3 fs-5 d-flex align-items-center justify-content-center"
                            id="btn-signin">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                class="bi bi-box-arrow-in-right me-2" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0z" />
                                <path fill-rule="evenodd"
                                    d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
                            </svg>
                            <span>Sign in</span>
                        </button>

                        <button type="button" onclick="location.href='ForgotPassword.php'"
                            class="btn btn-outline-secondary w-100 py-2 mt-2 mb-0 d-flex align-items-center justify-content-center"
                            id="btn-forget" style="border:none;">
                            <span>Forgot Password?</span>
                        </button>


                        <div class="text-center mt-1 text-muted fs-6">
                            <span>
                                <hr class="hr-text" data-content="Or">
                            </span>
                        </div>

                        <button type="button" onclick="location.href='Register.php'" 
                            class="btn btn-primary w-100 py-2 mt-2 d-flex align-items-center justify-content-center"
                            id="btn-signup">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                class="bi bi-person-plus me-2" viewBox="0 0 16 16">
                                <path
                                    d="M6 8c1.657 0 3-1.343 3-3S7.657 2 6 2s-3 1.343-3 3 1.343 3 3 3zM11.5 8a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2A.5.5 0 0 1 11.5 8zM11.5 10a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z" />
                                <path fill-rule="evenodd"
                                    d="M13.854 9.146a.5.5 0 0 1 .146.354v4a.5.5 0 0 1-.5.5h-4a.5.5 0 0 1-.354-.146l-4-4A.5.5 0 0 1 .146 9l4-4A.5.5 0 0 1 .707 .707l4-4A.5.5 0 1 1 .707 .707L9 .707l4-4A.5.5 0 1 1 .707 .707l4-4z" />
                            </svg>
                            <span>Sign Up</span>
                        </button>
                    </div>

                    <div id="loginBackground" class="col-md-6 d-none d-lg-block p-0">
                        <img src="../assets/img/meomeoBackground.jpg" alt="Meo Meo Login background"
                            class="login-image">
                    </div>
                </div>
            </div>
        <!-- </div> -->
    </form>
</body>
    <?php
    include("../src/footer.php");
    ?>
</html>