<?php
include_once("../modules/db_connection.php");
include_once("../modules/handleFailedLogin.php");
include_once("../modules/verifypass.php");
include_once("../modules/usertype.php");

$error = $_SESSION['login_error'] ?? '';
$e_or_p = $_SESSION['login_e_or_p'] ?? '';
unset($_SESSION['login_error'], $_SESSION['login_e_or_p']);

$pass = '';
$do_timeout = false;
$lock = 0;
$isEmail = false;

if (isset($_SESSION['email'])) {
    handleLoginRedirect();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_submit'])) {
    $e_or_p = $_POST['e_or_p'];
    $pass = $_POST['pass'];
    $isEmail = str_contains($e_or_p, '@');

    if (empty($e_or_p)) {
        $error = 'Please enter your email or phone number';
    } else if (empty($pass)) {
        $error = 'Please enter your password';
    } else if (strlen($pass) < 6) { // Use strlen to compare the length
        $error = 'Your password length must be at least 6 characters';
    } else {
        if ($isEmail) {
            if (filter_var($e_or_p, FILTER_VALIDATE_EMAIL) == false) {
                $error = 'This is not a valid email address';
            } else {
                if (verifypass($pass, $e_or_p, $isEmail)) {
                    //echo "correct pass, move to pofile";

                    handleLoginRedirect();
                }
                //if failed password verify, it will continue below:
                $do_timeout = true;
                $error = 'Invalid email/phone number or password';//it could be because attem_num > 3 in 60 sec or attem_num > 6
                $lock = handleFailedLogin(new DateTime(), false, $e_or_p, $isEmail);
            }
        } else {
            if (strlen($e_or_p) < 5 || strlen($e_or_p) > 15 ) {
                $error = 'A phone number length must be between 5 and 15';
            } else if (!ctype_digit($e_or_p)) { // Dung` ctype_digit de kiem tra xem chuoi co chi chua so hay khong, nen no se tu dong tra ve false neu co ky tu dac biet nhu dau + o dau so dien thoai
                $error = 'A phone number should only contain numbers';
            } else {
                if (verifypass($pass, $e_or_p, $isEmail)) {

                    handleLoginRedirect();
                }
                $do_timeout = true;
                $error = 'Wrong password';
                $lock = handleFailedLogin(new DateTime(), false, $e_or_p, $isEmail);
            }
        }
    }
    if ($do_timeout) {
        if ($lock[1] > -2) { // include -1 and > 0
            $error = $lock[0];
        } else {
            //add time out here
            $lock = handleFailedLogin(new DateTime(), true, $e_or_p, $isEmail);
            if ($lock[1] > -2) {
                $error = $lock[0];
            }
        }
    }
    $_SESSION['login_error'] = $error;
    $_SESSION['login_e_or_p'] = $e_or_p;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}


function handleLoginRedirect()
{ // Viet ra 1 ham` roi tai su dung
    $type = usertype();
    if ($type == 3) {
        header('Location: Admin_dashboard.php');
    } else if ($type == 0) {
        header('Location: WaitingApproval.php');
    } else if ($type == 2) {
        header('Location: UpdateInformation.php');
    } else if ($type == 4) {
        header('Location: DisableAccount.php');
    } else {
        header('Location: Home.php');
    }
    exit();
}
include("../src/headerOutSide.php");
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="../assets/css/login.css">

<main class="login-page-wrapper">
    <form action="" method="POST" novalidate class="w-100 d-flex justify-content-center">

        <div class="login-box">

            <div class="login-form-side">

                <div class="brand-mark">
                    <div class="brand-icon">

                        <i class="fa-solid fa-wallet fs-4"></i>
                    </div>
                    <span class="brand-name">MeoMeo Wallet</span>
                </div>

                <h1 class="form-title">Welcome back</h1>
                <p class="form-subtitle">Login to MeoMeo hehe</p>

                <label for="e_or_p" class="form-label-custom">Email or Phone Number</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </span>
                    <input type="text" class="form-input" id="e_or_p" name="e_or_p" placeholder="Email/Phone Number"
                        value="<?= htmlspecialchars($e_or_p) ?>" autocomplete="username" required>
                </div>

                <label for="pass" class="form-label-custom">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                    </span>
                    <input type="password" class="form-input" id="pass" name="pass" placeholder="Type your password"
                        autocomplete="current-password" required>
                    <button type="button" class="toggle-pass" id="toggle-pass" title="Hiện/ẩn mật khẩu">
                        <svg id="eye-show" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg id="eye-hide" viewBox="0 0 24 24" style="display:none">
                            <path
                                d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24M1 1l22 22" />
                        </svg>
                    </button>
                </div>

                <div id="error-msg" class="error-alert<?= empty($error) ? ' is-invisible' : '' ?>" role="alert">
                    <svg viewBox="0 0 24 24" width="16" height="16">
                        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" />
                        <path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    <span><?= !empty($error) ? htmlspecialchars($error) : '&nbsp;' ?></span>
                </div>
                <button type="submit" name="login_submit" class="btn-primary-custom">
                    <svg viewBox="0 0 24 24">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                        <polyline points="10 17 15 12 10 7" />
                        <line x1="15" y1="12" x2="3" y2="12" />
                    </svg>
                    Sign in
                </button>

                <button type="button" onclick="location.href='ForgotPassword.php'" class="btn-ghost-custom">Forgot
                    Password?</button>

                <div class="divider"><span>Or</span></div>

                <button type="button" onclick="location.href='Register.php'" class="btn-signup-custom">
                    <svg viewBox="0 0 24 24">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <line x1="19" y1="8" x2="19" y2="14" />
                        <line x1="22" y1="11" x2="16" y2="11" />
                    </svg>
                    Sign Up
                </button>

            </div>
            <div class="login-image-side">
                <img src="../assets/img/meomeoBackground.jpg" alt="E-Wallet background">
            </div>

        </div>
    </form>
</main>

<script>
    /* Toggle password visibility */
    const toggleBtn = document.getElementById('toggle-pass');
    const passInput = document.getElementById('pass');
    const eyeShow = document.getElementById('eye-show');
    const eyeHide = document.getElementById('eye-hide');

    toggleBtn.addEventListener('click', () => {
        const isText = passInput.type === 'text';
        passInput.type = isText ? 'password' : 'text';
        eyeShow.style.display = isText ? 'block' : 'none';
        eyeHide.style.display = isText ? 'none' : 'block';
    });

    const errorEl = document.getElementById('error-msg');
    ['e_or_p', 'pass'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => {
            errorEl.classList.add('is-invisible');
        });
    });
</script>

<?php include("../src/footer.php"); ?>