<?php
include_once("../modules/db_connection.php");
include_once("../modules/verifypass.php");
include_once("../modules/usertype.php");

$usertype = usertype();
$oldPass = '';
$normalreset = true;//reset_via_otp => false
$newPass1 = '';
$newPass2 = '';
$error = '';

if ($usertype == -1 || (isset($_SESSION['forgotPass']) && $_SESSION['forgotPass'] === true)) {
    $normalreset = false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['newPass1']) && isset($_POST['newPass2'])) {
    $oldPass = $_POST['oldPass'] ?? '';
    $newPass1 = $_POST['newPass1'] ?? '';
    $newPass2 = $_POST['newPass2'] ?? '';

    if (empty($_POST['oldPass']) && $normalreset) {
        $error = 'Please enter the old password';
    } else if (empty($_POST['newPass1']) || empty($_POST['newPass2'])) {
        $error = 'Please enter the new password';
    } else {
        if ($normalreset) {
            if (verifypass($_POST['oldPass'], $_SESSION['email'], true) == false) {
                $error = 'Old password is incorrect';
            }
        }
        if (empty($error)) {
            if (strlen($newPass1) < 6 || strlen($newPass2) < 6) {
                $error = 'New password must be at least 6 characters';
            } else if ($newPass1 != $newPass2) {
                $error = 'New passwords do not match';
            } else if ($normalreset && $oldPass == $newPass1) {
                $error = 'New password must be different from current password';
            } else {
                $hash = password_hash($newPass1, PASSWORD_DEFAULT);
                $con = connect_db();
                $email_for_update = isset($_SESSION['forgotPass']) && $_SESSION['forgotPass'] === true
                    ? $_SESSION['reset_email_final'] // Session in ForgotPassword.php
                    : $_SESSION['email'];
                $result = $con->prepare('UPDATE `user` SET `pass` = ? WHERE `email` = ?');
                //change password does not mean the user is verified, admin need to verify manually
                $result->bind_param('ss', $hash, $_SESSION['email']);
                if (!$result->execute()) {
                    $error = 'Failed to update to new password';
                    $result->close();
                    $con->close();
                } else {
                    $result->close();
                    $con->close();
                    if ($_SESSION['forgotPass'] === true) {
                        session_unset();
                        session_destroy();
                        header('Location: Login.php');
                    } else {
                        header('Location: Home.php');//change password
                    }
                    exit();
                }

            }
        }
    }
}
include("../src/headerOutSide.php");
?>
<link rel="stylesheet" href="../assets/css/changePassword.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="auth-page-wrapper">
    <div class="auth-box">
        <div class="brand-mark">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M19 7h-1V6a3 3 0 0 0-3-3H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V10a3 3 0 0 0-3-3ZM5 5h10a1 1 0 0 1 1 1v1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm14 14H5a1 1 0 0 1-1-1V9h15v1h-3a3 3 0 0 0-3 3v2a3 3 0 0 0 3 3h3v1a1 1 0 0 1-1 1Zm3-6v2h-3a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1h3v2Z" />
                </svg>
            </div>
            <span class="brand-name">MeoMeo Wallet</span>
        </div>

        <h2 class="form-title">Security Settings</h2>
        <p class="form-subtitle">Update your password to keep your wallet safe.</p>

        <div class="alert-container">
            <div class="alert-custom error-vibrant <?= empty($error) ? 'alert-hidden' : '' ?>" id="error-box">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?= !empty($error) ? htmlspecialchars($error) : '&nbsp;' ?></span>
            </div>

            <?php if (!$normalreset): ?>
                <div class="alert-custom info-vibrant">
                    <i class="bi bi-shield-check"></i>
                    <span><strong>First Login:</strong> Please set a permanent password to secure your account.</span>
                </div>
            <?php endif; ?>
        </div>

        <form method="POST" action="">
            <?php if ($normalreset): ?>
                <div class="form-group">
                    <label class="form-label-custom">CURRENT PASSWORD</label>
                    <div class="input-wrap">
                        <div class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="M12 15V17M6 10H18C19.1046 10 20 10.8954 20 12V20C20 21.1046 19.1046 22 18 22H6C4.89543 22 4 21.1046 4 20V12C4 10.8954 4.89543 10 6 10ZM12 2C9.23858 2 7 4.23858 7 7V10H17V7C17 4.23858 14.7614 2 12 2Z"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <input type="password" name="oldPass" class="form-input" placeholder="Enter current password"
                            value="<?= isset($_POST['oldPass']) ? htmlspecialchars($_POST['oldPass']) : '' ?>">
                        <button type="button" class="btn-toggle-pwd" onclick="togglePwd(this)"><i
                                class="bi bi-eye"></i></button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label-custom">NEW PASSWORD</label>
                <div class="input-wrap">
                    <div class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M7 11V7C7 4.23858 9.23858 2 12 2C14.7614 2 17 4.23858 17 7V11M5 11H19C20.1046 11 21 11.8954 21 13V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V13C3 11.8954 3.89543 11 5 11Z"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <input type="password" name="newPass1" class="form-input" placeholder="New password"
                        value="<?php isset($_POST['newPass1']) ? htmlspecialchars($_POST['newPass1']) : '' ?>">
                    <button type="button" class="btn-toggle-pwd" onclick="togglePwd(this)"><i
                            class="bi bi-eye"></i></button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label-custom">CONFIRM NEW PASSWORD</label>
                <div class="input-wrap">
                    <div class="input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M9 12L11 14L15 10M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2Z"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <input type="password" name="newPass2" class="form-input" placeholder="Repeat new password"
                        value="<?php isset($_POST['newPass2']) ? htmlspecialchars($_POST['newPass2']) : '' ?>">
                    <button type="button" class="btn-toggle-pwd" onclick="togglePwd(this)"><i
                            class="bi bi-eye"></i></button>
                </div>
            </div>

            <button type="submit" class="btn-primary-custom">
                Confirm Update
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <?php if ($normalreset == false) {
                echo "<a href='../modules/logout.php' class='btn-ghost-custom'>Cancel</a>";
            } else { ?>
                <a href="Home.php" class="btn-ghost-custom">Cancel</a>
            <?php } ?>
        </form>
    </div>
</div>

<script>
    function togglePwd(btn) {
        const input = btn.parentElement.querySelector('input');
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }

    // Ẩn error khi user bắt đầu nhập lại
    const errorBox = document.getElementById('error-box');
    if (errorBox) {
        ['oldPass', 'newPass1', 'newPass2'].forEach(fieldName => {
            const el = document.querySelector(`[name="${fieldName}"]`);
            if (el) el.addEventListener('input', () => errorBox.classList.add('alert-hidden'));
        });
    }

    // Auto-focus vào ô bị lỗi
    <?php if (!empty($error)): ?>
        <?php if (empty($_POST['oldPass']) && $normalreset): ?>
            document.querySelector('[name="oldPass"]')?.focus();
        <?php elseif (empty($_POST['newPass1'])): ?>
            document.querySelector('[name="newPass1"]')?.focus();
        <?php elseif (empty($_POST['newPass2'])): ?>
            document.querySelector('[name="newPass2"]')?.focus();
        <?php elseif (strpos($error, 'Old password') !== false): ?>
            document.querySelector('[name="oldPass"]')?.focus();
        <?php elseif (strpos($error, 'match') !== false): ?>
            document.querySelector('[name="newPass2"]')?.focus();
        <?php elseif (strpos($error, 'different') !== false || strpos($error, '6 characters') !== false): ?>
            document.querySelector('[name="newPass1"]')?.focus();
        <?php endif; ?>
    <?php endif; ?>
</script>

<?php include("../src/footer.php"); ?>
