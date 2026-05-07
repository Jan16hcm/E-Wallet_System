<!-- To be able to use the service of the e-wallet website, the user needs to register an account on the website. When registering, users need to enter
information including: phone number, email, full name, date of birth, address, upload photo of the front and back of the identity card. Each user
must have a different email address and a different phone number. After successful registration, the user will be generated a random password
(any string of 6 characters), both email and phone number can be used as username for login. These two information will be automatically sent to
the user's email immediately. If you can't do the email sending feature, you need to display these two information on the website interface right
after successful registration. -->
<?php
include_once("../modules/db_connection.php");
include_once("../modules/sendOTP.php");
include_once("../modules/isValidDate.php");

function getCompressedImageData($source, $quality)
{
    $info = getimagesize($source);
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        return false;
    }

    ob_start();
    imagejpeg($image, null, $quality);
    $imageData = ob_get_contents();
    ob_end_clean();

    imagedestroy($image);
    return $imageData;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $con = connect_db();

    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phonenum = mysqli_real_escape_string($con, $_POST['phonenum']);
    $birth = $_POST['birth'];
    $address = mysqli_real_escape_string($con, $_POST['address']);

    if (empty($name) || empty($email) || empty($phonenum) || empty($birth) || empty($address)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!isset($_FILES['front']) || !isset($_FILES['back']) || $_FILES['front']['error'] !== 0 || $_FILES['back']['error'] !== 0) {
        $error = 'Please upload both front and back ID card images.';
    } else {
        $check_query = $con->prepare("SELECT email, phonenum FROM user WHERE email = ? OR phonenum = ?");
        $check_query->bind_param("ss", $email, $phonenum);
        $check_query->execute();
        $result = $check_query->get_result();

        if ($result->num_rows > 0) {
            $error = 'Email or Phone number already registered.';
        } else {
            $front_ext = strtolower(pathinfo($_FILES["front"]["name"], PATHINFO_EXTENSION));
            $back_ext = strtolower(pathinfo($_FILES["back"]["name"], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png'];

            if (!in_array($front_ext, $allowed_exts) || !in_array($back_ext, $allowed_exts)) {
                $error = 'Only JPG, JPEG & PNG files are allowed.';
            } else {
                $front_data = getCompressedImageData($_FILES["front"]["tmp_name"], 60);
                $back_data = getCompressedImageData($_FILES["back"]["tmp_name"], 60);

                if ($front_data === false || $back_data === false) {
                    $error = 'Failed to process images. Please upload valid image files.';
                } else {
                    $random_pass = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 6);

                    if (sendRegistrationEmail($email, $phonenum, $name, $random_pass)) {
                        $password_to_store = password_hash($random_pass, PASSWORD_DEFAULT);

                        $insert_stmt = $con->prepare(
                            "INSERT INTO user (`phonenum`, `email`, `name`, `birth`, `address`, `front`, `back`, `pass`, `verified`)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, -1)"
                        );

                        // s=string, b=blob
                        //$verified = -1; // default to not verified -1 =? first register
                        $null = null;
                        $insert_stmt->bind_param(
                            "sssssbbs",
                            $phonenum,
                            $email,
                            $name,
                            $birth,
                            $address,
                            $null,
                            $null,
                            $password_to_store
                        );
                        $insert_stmt->send_long_data(5, $front_data);
                        $insert_stmt->send_long_data(6, $back_data);
                        if ($insert_stmt->execute()) {
                            $success = "Registration successful! Your password has been sent to $email.";
                        } else {
                            $error = "Error saving data: " . $con->error;
                        }
                        $insert_stmt->close();
                        $con->close();
                    } else {
                        $error = "Account created but failed to send email. Please contact support.";
                    }
                }
            }
        }
        $check_query->close();
    }
}
include("../src/headerOutSide.php");
?>

<meta charset="UTF-8">
<title>Register - MeoMeo E-Wallet</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/register.css">


<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="Register.php" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow">
                <h2 class="text-center mb-4">Sign up</h2>

                <div class="alert alert-danger <?= empty($error) ? 'invisible' : '' ?>" id="error-box">
                    <?= !empty($error) ? $error : '&nbsp;' ?>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= $success ?>
                        <br><small>Redirecting to login page in <span id="countdown">10</span> seconds...</small>
                    </div>
                    <script>
                        let seconds = 10;
                        setInterval(function () {
                            seconds--;
                            document.getElementById('countdown').textContent = seconds;
                            if (seconds <= 0) {
                                window.location.href = 'Login.php';
                            }
                        }, 1000);
                    </script>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Full name"
                        value="<?= htmlspecialchars($name ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Email address"
                        value="<?= htmlspecialchars($email ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phonenum" class="form-control" placeholder="Phone number"
                        value="<?= htmlspecialchars($phonenum ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="birth" class="form-control" value="<?= htmlspecialchars($birth ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"> <?= htmlspecialchars($address ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID Card Front</label>
                        <input type="file" name="front" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID Card Back</label>
                        <input type="file" name="back" class="form-control" accept="image/*">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Sign up</button>

                <div class="text-center mt-3 fs-5">
                    <small>Already have an account? <a href="login.php" class="text-decoration-none">Sign in</a></small>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    const errorBox = document.getElementById('error-box');

    const nameInput     = document.querySelector('[name="name"]');
    const emailInput    = document.querySelector('[name="email"]');
    const phonenumInput = document.querySelector('[name="phonenum"]');
    const birthInput    = document.querySelector('[name="birth"]');
    const addressInput  = document.querySelector('[name="address"]');
    const frontInput    = document.querySelector('[name="front"]');
    const backInput     = document.querySelector('[name="back"]');

    ['name', 'email', 'phonenum', 'birth', 'address'].forEach(fieldName => {
        const el = document.querySelector(`[name="${fieldName}"]`);
        if (el) el.addEventListener('input', () => errorBox.classList.add('invisible'));
    });

    <?php if ($error): ?>
        <?php if (empty($_POST['name'])): ?>
            nameInput.focus();
        <?php elseif (empty($_POST['email'])): ?>
            emailInput.focus();
        <?php elseif (empty($_POST['phonenum'])): ?>
            phonenumInput.focus();
        <?php elseif (empty($_POST['birth'])): ?>
            birthInput.focus();
        <?php elseif (empty($_POST['address'])): ?>
            addressInput.focus();
        <?php elseif (strpos($error, 'email') !== false || strpos($error, 'Email') !== false): ?>
            emailInput.focus();
        <?php elseif (strpos($error, 'Phone') !== false || strpos($error, 'phone') !== false): ?>
            phonenumInput.focus();
        <?php elseif (strpos($error, 'ID') !== false || strpos($error, 'front') !== false || strpos($error, 'back') !== false): ?>
            frontInput.focus();
        <?php endif; ?>
    <?php endif; ?>
</script>

<?php include("../src/footer.php"); ?>
