<?php
include_once("../modules/db_connection.php");
include_once("../modules/handleFailedLogin.php");
include_once("../modules/verifypass.php");
include_once("../modules/usertype.php");

$error = $_SESSION['login_error'] ?? '';
$e_or_p = $_SESSION['login_e_or_p'] ?? '';
unset($_SESSION['login_error'], $_SESSION["error"]);

$pass = '';
$lock = 0;
$isEmail = false;
$lock_seconds = 0;

if (isset($_SESSION['email'])) {
    handleLoginRedirect();
}
if (!empty($e_or_p)) {
    $con = connect_db();
    $isEmail = str_contains($e_or_p, '@');
    $query = $isEmail ? "SELECT abnormal_login, locked_time FROM user WHERE email = ?"
        : "SELECT abnormal_login, locked_time FROM user WHERE phonenum = ?";

    $stmt = $con->prepare($query);
    $stmt->bind_param("s", $e_or_p);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res && $res['abnormal_login'] >= 3 && $res['abnormal_login'] < 4 && !empty($res['locked_time'])) {
        $locked_time = new DateTime($res['locked_time']);
        $now = new DateTime();
        $diff = $now->getTimestamp() - $locked_time->getTimestamp();

        if ($diff < 60) {
            $lock_seconds = 60 - $diff;
            // Reset time after refresh page even ctrl + f5
            $error = "Account is currently locked, please try again in " . $lock_seconds . " seconds";
        }
    }
    $stmt->close();
}
// Khi render form, generate token: Avoid Attacker create fake web rồi login được vào web thiệt giống như thầy Mạnh thực hành
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_submit'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['login_error'] = 'Invalid request';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

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
        if ($isEmail && filter_var($e_or_p, FILTER_VALIDATE_EMAIL) == false) {
            $error = 'This is not a valid email address';
        } else if ($isEmail == false && (strlen($e_or_p) < 5 || strlen($e_or_p) > 15)) {
            $error = 'A phone number length must be between 5 and 15';
        } else if ($isEmail == false && (!ctype_digit($e_or_p))) {
            $error = 'A phone number should only contain numbers';
        } else {
            if (verifypass($pass, $e_or_p, $isEmail)) {
                $con = connect_db();
                $resetQuery = $isEmail ? "UPDATE user SET `abnormal_login` = 0, `locked_time` = NULL WHERE `email` = ?"
                    : "UPDATE user SET `abnormal_login` = 0, `locked_time` = NULL WHERE `phonenum` = ?";
                $stmt = $con->prepare($resetQuery);
                $stmt->bind_param("s", $e_or_p);
                $stmt->execute();
                $stmt->close();
                handleLoginRedirect();
            } else {
                $lock = handleFailedLogin(new DateTime(), $e_or_p, $isEmail);
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
    switch ($type) {
        case -1:
            header('Location: ChangePassword.php');
            break;
        case 0:
            header('Location: Profile.php');
            break;
        case 2:
            header('Location: Profile.php');
            break;
        case 3:
            header('Location: Admin_dashboard.php');
            break;
        default:
            header('Location: Home.php');
            break;
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
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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
                <button type="submit" name="login_submit" id="btn-login" class="btn-primary-custom">
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
    document.addEventListener('DOMContentLoaded', function () {
        const btnLogin = document.getElementById('btn-login');
        const errorMsg = document.getElementById('error-msg');

        let seconds = <?php echo (int) $lock_seconds; ?>;

        if (seconds >= 1) {
            const timer = setInterval(() => {
                seconds--;
                if (seconds >= 1) {
                    if (errorMsg.querySelector('span')) {
                        errorMsg.querySelector('span').innerText = `Account is currently locked, please try again in ${seconds} seconds`;
                    }
                } else {
                    clearInterval(timer);
                    errorMsg.classList.add('is-invisible');
                }
            }, 1000);
        }
    });
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
