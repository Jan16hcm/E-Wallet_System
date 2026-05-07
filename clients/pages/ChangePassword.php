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

if ($usertype == 4) {
    $error = 'This function is only for activated account';
}

if ($usertype == -1) {
    $normalreset = false;//first login
}

if (isset($_POST['otp'])){
    if ($_POST['otp'] == 'SUC'){
        $normalreset = false;//change pass using otp
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['newPass1']) && isset($_POST['newPass2'])) {
    $oldPass = $_POST['oldPass'] ?? '';
    $newPass1 = $_POST['newPass1'] ?? '';
    $newPass2 = $_POST['newPass2'] ?? '';

    if (empty($_POST['oldPass']) && $normalreset) {
        $error = 'Please enter the old password';
    } else if (empty($_POST['newPass1'])) {
        $error = 'Please enter the new password';
    } else if (empty($_POST['newPass2'])) {
        $error = 'Please confirm the new password';
    } else {
        if ($normalreset) {
            if (verifypass($oldPass, $_SESSION['email'], true)) {
                //kind of waste time open/closing db
                $error = 'Old password is incorrect';
            }
        }

        if (empty($error)) {
            if (strlen($newPass1) < 6) {
                $error = 'New password must be at least 6 characters';
            } else if ($newPass1 != $newPass2) {
                $error = 'New passwords do not match';
            } else if ($oldPass == $newPass1) {
                $error = 'New password must be different from current password';
            } else {

                $hash = password_hash($newPass1, PASSWORD_BCRYPT);
                $con = connect_db();
                $result = $con->prepare("UPDATE user SET pass = ? WHERE email = ?");
                $result->bind_param("s", $hash, $_SESSION["email"]);
                if (!$result->execute()) {
                    $error = 'Failed to update to new password';
                } else {
                    header('Location: Home.php');
                    exit();
                }
                $result->close();
                $con->close();
            }
        }
    }
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
    <title>Change Password</title>
</head>

<body>
<?php include("../src/headerOutSide.php") ?>
<h2>Update your password</h2>
<div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
        <div class="fb-card">
            <div class="fb-card-header">
                <i class="bi bi-shield-lock-fill text-navy fs-5"></i>
                <h5>Password Settings</h5>
            </div>
            <div class="fb-card-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php if ($normalreset): ?>
                    <div class="mb-3">
                        <label class="fb-form-label">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="oldPass" class="form-control" placeholder="Enter current password" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd(this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="fb-form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="newPass1" class="form-control" placeholder="At least 6 characters" required minlength="6">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd(this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="fb-form-label">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="newPass2" class="form-control" placeholder="Repeat new password" required minlength="6">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd(this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-shield-check me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include("../src/footer.php");?>
</body>
<script>
function togglePwd(btn) {
    const input = btn.parentElement.querySelector('input');
    const icon = btn.querySelector('i');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
