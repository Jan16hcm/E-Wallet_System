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
                $expire_time = date("Y-m-d H:i:s", time() + 60);

                $update_stmt = $con->prepare("UPDATE `user` SET `expire` = ? WHERE `email` = ?");
                $update_stmt->bind_param("ss", $expire_time, $email);

                if ($update_stmt->execute()) {
                    $otp_str = str_shuffle('0123456789');
                    $otp = substr($otp_str, 0, 6);
                    $_SESSION['reset_otp'] = $otp;
                    $_SESSION['reset_email_pending'] = $email;
                    if (sendOTPEmail($email, $row['name'], $otp)) {
                        echo json_encode(['success' => true, 'message' => 'OTP sent successfully.']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
                    }
                }
                $update_stmt->close();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Email address not found.']);
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

        $stmt = $con->prepare("SELECT `expire` FROM `user` WHERE `email` = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row || $otp_in !== $_SESSION["reset_otp"]) {
            echo json_encode(['success' => false, 'message' => 'Invalid OTP code.']);
        } else if ($now > $row['expire']) {
            echo json_encode(['success' => false, 'message' => 'OTP has expired (1-minute limit).']);
        } else {
            $_SESSION['user_verified_for_reset'] = true;
            $_SESSION['reset_email_final'] = $email;
            $_SESSION['forgotPass'] = true;
            $clear_stmt = $con->prepare("UPDATE `user` SET `expire` = NULL WHERE `email` = ?");
            $clear_stmt->bind_param("s", $email);
            $clear_stmt->execute();
            $clear_stmt->close();

            echo json_encode(['success' => true]);
        }
        $stmt->close();
    }
    $con->close();
    exit();
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
            <div id="email-section">
                <div class="input-wrap">
                    <label class="form-label-custom">Your Registered Email</label>
                    <input type="email" id="email" class="form-control" placeholder="name@example.com" required>
                </div>
                <button type="button" id="btn-send-otp" class="btn-primary-custom">Send Code</button>
            </div>

            <div id="otp-section" style="display: none;">
                <label class="form-label-custom">Enter 6-Digit OTP</label>
                <div class="otp-container">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                </div>
                <button type="button" id="btn-verify-otp" class="btn-primary-custom">Verify OTP</button>
                <p style="text-align: center; margin-top: 15px;">
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

        hideError();

        emailInput.addEventListener('input', hideError);

        btnSendOtp.addEventListener('click', function () {
            if (!emailInput.checkValidity()) {
                showError("Please enter a valid email address.");
                return;
            }

            fetch('ForgotPassword.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=send_otp&email=${encodeURIComponent(emailInput.value)}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        emailSection.style.display = 'none';
                        otpSection.style.display = 'block';
                        hideError();
                        document.querySelector('.otp-input').focus();
                    } else {
                        showError(data.message);
                    }
                });
        });

        btnVerifyOtp.addEventListener('click', function () {
            const code = Array.from(document.querySelectorAll('.otp-input')).map(i => i.value).join('');

            fetch('ForgotPassword.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=verify_otp&otp_code=${code}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'ChangePassword.php';
                    } else {
                        showError(data.message);
                    }
                });
        });

        backToEmail.addEventListener('click', () => {
            otpSection.style.display = 'none';
            emailSection.style.display = 'block';
            hideError();
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