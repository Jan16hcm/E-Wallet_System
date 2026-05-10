<?php
require_once("../modules/db_connection.php");
require_once("../modules/sendOTP.php");
require_once("../modules/usertype.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $error = '';
    $email = '';
    $con = connect_db();
    if ($_POST['action'] == 'send_otp') {
        $email = $_POST['email'];

        $stmt = $con->prepare("SELECT `name`, `verified` FROM `user` WHERE `email` = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $type = $row["verified"];
            if ($type === 4) { // Block account want to change pass
                echo json_encode([
                    'success' => false,
                    'message' => 'Account has been locked due to entering the
wrong password many times, please contact the administrator for support'
                ]);
            } else {
                $otp_str = str_shuffle('0123456789');
                $otp = substr($otp_str, 0, 6);
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['reset_email_pending'] = $email;
                $_SESSION['reset_expire_ts'] = time() + 60;
                $_SESSION['reset_display_email'] = $email;
                if (sendOTPEmail($email, $row['name'], $otp)) {
                    echo json_encode(['success' => true, 'message' => 'OTP sent successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
                }
            }
        } else {
            $otp_str = str_shuffle('0123456789');
            $otp = substr($otp_str, 0, 6);
            $fake_mail = "dummy@gmail.com";
            $_SESSION['reset_email_pending'] = $fake_mail; // Chap het cac attacker by Khai hehehe
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_expire_ts'] = time() + 60;
            $_SESSION['reset_display_email'] = $_POST['email'];
            sendOTPEmail($fake_mail, 'test', $otp); // Fake to avoid timing attack
            echo json_encode(['success' => true, 'message' => 'OTP sent successfully.']);
        }
        $stmt->close();
    }
    if ($_POST['action'] == 'verify_otp') {
        if (!isset($_SESSION['reset_email_pending'])) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please restart the process.']);
            exit;
        }

        $email = $_SESSION['reset_email_pending'];
        $otp_in = $_POST['otp_code'];
        $now = date('Y-m-d H:i:s');

        if (strlen($otp_in) !== 6 || !ctype_digit($otp_in)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a complete 6-digit OTP.']);
            exit;
        }

        $stmt = $con->prepare("SELECT `email` FROM `user` WHERE `email` = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row || $otp_in !== $_SESSION["reset_otp"]) {
            echo json_encode(['success' => false, 'message' => 'Invalid OTP code.']);
        } else if (time() > $_SESSION['reset_expire_ts']) {
            echo json_encode(['success' => false, 'message' => 'OTP has expired (1-minute limit).']);
        } else {
            $_SESSION['user_verified_for_reset'] = true;
            $_SESSION['reset_email_final'] = $email;
            $_SESSION['email'] = $email;
            $_SESSION['forgotPass'] = true;
            echo json_encode(['success' => true]);
        }
        $stmt->close();
    }
    $con->close();
    exit();
}

$pendingOtp = false;
$pendingDisplayEmail = '';
$pendingTimeLeft = 0;

if (!empty($_SESSION['reset_email_pending']) && !empty($_SESSION['reset_otp']) && !empty($_SESSION['reset_expire_ts'])) {
    $remaining = $_SESSION['reset_expire_ts'] - time();
    if ($remaining > 0) {
        $pendingOtp = true;
        $pendingTimeLeft = $remaining;
        $pendingDisplayEmail = $_SESSION['reset_display_email'] ?? '';
    } else {
        unset($_SESSION['reset_otp'], $_SESSION['reset_email_pending'], $_SESSION['reset_expire_ts'], $_SESSION['reset_display_email']);
    }
}


include("../src/headerOutSide.php");
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@700&family=Plus+Jakarta+Sans:wght@400;600&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="../assets/css/forgot.css">
<title>Forgot Password | MeoMeo Wallet</title>

<main class="auth-page-wrapper">
    <div class="auth-box">
        <a href="Login.php" class="btn-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7" />
            </svg>
            Back to Login
        </a>

        <h2 class="form-title">Forgot Password</h2>
        <p class="form-subtitle">Enter your email to receive a 6-digit verification code.</p>

        <form id="forgotForm" method="POST">
            <div id="email-section" style="display: <?= $pendingOtp ? 'none' : 'block' ?>;">
                <div class="input-wrap">
                    <label class="form-label-custom">Your Registered Email</label>
                    <input type="email" id="email" class="form-control" placeholder="name@example.com" required>
                </div>
                <button type="button" id="btn-send-otp" class="btn-primary-custom">Send Code</button>
            </div>

            <div id="otp-section" style="display: <?= $pendingOtp ? 'block' : 'none' ?>;">
                <label class="form-label-custom">Enter 6-Digit OTP</label>
                <p id="display-email-text"
                    style="text-align: center; font-size: 14px; margin-bottom: 15px; color: var(--text-3);">
                    Please check the email address <strong id="display-email" style="color: var(--primary);"></strong>
                    for instructions to reset your password.
                </p>
                <div class="otp-container">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                </div>
                <button type="button" id="btn-verify-otp" class="btn-primary-custom">Verify OTP</button>
                <p style="text-align: center; margin-top: 15px; font-size: 14px; color: var(--text-2);">
                    Time remaining: <span id="otp-timer" style="font-weight: bold; color: var(--primary);">01:00</span>
                </p>
                <p style="text-align: center; margin-top: 10px;">
                    <a href="javascript:void(0)" id="back-to-email"
                        style="color: var(--text-3); font-size: 13px; text-decoration: none;">Change Email</a>
                </p>
            </div>

            <div id="error-msg" class="error-alert">
                <span id="err-text"></span>
            </div>
        </form>
    </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const emailSection = document.getElementById('email-section');
        const otpSection = document.getElementById('otp-section');
        const btnSendOtp = document.getElementById('btn-send-otp');
        const btnVerifyOtp = document.getElementById('btn-verify-otp');
        const backToEmail = document.getElementById('back-to-email');
        const emailInput = document.getElementById('email');
        const errorMsg = document.getElementById('error-msg');
        const errText = document.getElementById('err-text');

        // PHP-injected state for page-refresh persistence
        const pendingOtp = <?= $pendingOtp ? 'true' : 'false' ?>;
        const pendingTimeLeft = <?= (int) $pendingTimeLeft ?>;
        const pendingDisplayEmail = "<?= addslashes($pendingDisplayEmail) ?>";

        const showError = (msg) => {
            errText.innerText = msg;
            errorMsg.style.visibility = 'visible';
            errorMsg.style.opacity = '1';
        };

        const hideError = () => {
            errText.innerText = '\u00A0';
            errorMsg.style.visibility = 'hidden';
            errorMsg.style.opacity = '0';
        };

        let countdownInterval;

        const startCountdown = (initialSeconds) => {
            clearInterval(countdownInterval);
            let timeLeft = initialSeconds || 60;
            const timerElement = document.getElementById('otp-timer');
            const m0 = Math.floor(timeLeft / 60);
            const s0 = timeLeft % 60;
            timerElement.innerText = `0${m0}:${s0 < 10 ? '0' : ''}${s0}`;
            timerElement.style.color = 'var(--primary)';

            countdownInterval = setInterval(() => {
                timeLeft--;
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    timerElement.innerText = "Expired";
                    timerElement.style.color = "red";
                } else {
                    const m = Math.floor(timeLeft / 60);
                    const s = timeLeft % 60;
                    timerElement.innerText = `0${m}:${s < 10 ? '0' : ''}${s}`;
                }
            }, 1000);
        };

        hideError();

        // Restore OTP view on page refresh if a pending session exists
        if (pendingOtp) {
            document.getElementById('display-email').innerText = pendingDisplayEmail;
            document.querySelector('.otp-input').focus();
            startCountdown(pendingTimeLeft);
        }

        emailInput.addEventListener('input', hideError);

        btnSendOtp.addEventListener('click', function () {
            if (!emailInput.checkValidity()) {
                showError("Please enter a valid email address.");
                return;
            }

            const originalText = btnSendOtp.innerText;
            btnSendOtp.innerText = 'Sending...';
            btnSendOtp.disabled = true;

            fetch('ForgotPassword.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=send_otp&email=${encodeURIComponent(emailInput.value)}`
            })
                .then(res => res.json())
                .then(data => {
                    btnSendOtp.innerText = originalText;
                    btnSendOtp.disabled = false;

                    if (data.success) {
                        emailSection.style.display = 'none';
                        document.getElementById('display-email').innerText = emailInput.value;
                        otpSection.style.display = 'block';
                        hideError();
                        document.querySelector('.otp-input').focus();
                        startCountdown(60);
                    } else {
                        showError(data.message);
                    }
                })
                .catch(err => {
                    btnSendOtp.innerText = originalText;
                    btnSendOtp.disabled = false;
                    showError("An error occurred. Please try again.");
                });
        });

        btnVerifyOtp.addEventListener('click', function () {
            const code = Array.from(document.querySelectorAll('.otp-input')).map(i => i.value).join('');

            const originalText = btnVerifyOtp.innerText;
            btnVerifyOtp.innerText = 'Verifying...';
            btnVerifyOtp.disabled = true;

            fetch('ForgotPassword.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=verify_otp&otp_code=${code}`
            })
                .then(res => res.json())
                .then(data => {
                    btnVerifyOtp.innerText = originalText;
                    btnVerifyOtp.disabled = false;

                    if (data.success) {
                        window.location.href = 'ChangePassword.php';
                    } else {
                        showError(data.message);
                    }
                })
                .catch(err => {
                    btnVerifyOtp.innerText = originalText;
                    btnVerifyOtp.disabled = false;
                    showError("An error occurred. Please try again.");
                });
        });

        backToEmail.addEventListener('click', () => {
            clearInterval(countdownInterval);
            otpSection.style.display = 'none';
            emailSection.style.display = 'block';
            hideError();
            document.querySelectorAll('.otp-input').forEach(input => input.value = '');
        });

        const otpInputs = document.querySelectorAll('.otp-input');

        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                input.value = input.value.replace(/[^0-9]/g, '');
                hideError();
                if (e.inputType === 'deleteContentBackward') return;
                if (input.value.length === 1 && otpInputs[index + 1]) {
                    otpInputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && otpInputs[index - 1]) {
                    otpInputs[index - 1].focus();
                }
            });
        });
    });
</script>

<?php include("../src/footer.php"); ?>
