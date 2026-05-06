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
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $front_ext = strtolower(pathinfo($_FILES["front"]["name"], PATHINFO_EXTENSION));
            $back_ext = strtolower(pathinfo($_FILES["back"]["name"], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png'];

            if (!in_array($front_ext, $allowed_exts) || !in_array($back_ext, $allowed_exts)) {
                $error = 'Only JPG, JPEG & PNG files are allowed.';
            } else {
                $front_new_name = "FRONT_" . time() . "_" . uniqid() . "." . $front_ext;
                $back_new_name = "BACK_" . time() . "_" . uniqid() . "." . $back_ext;

                $front_path = $target_dir . $front_new_name;
                $back_path = $target_dir . $back_new_name;

                if (
                    move_uploaded_file($_FILES["front"]["tmp_name"], $front_path) &&
                    move_uploaded_file($_FILES["back"]["tmp_name"], $back_path)
                ) {

                    $random_pass = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 6);
                    if (sendRegistrationEmail($email, $name, $random_pass)) {
                        $password_to_store = password_hash($random_pass, PASSWORD_DEFAULT);
                        // 8. Lưu vào Database
                        // Lưu ý: Password nên hash nếu không có yêu cầu lưu text thô
                        $insert_stmt = $con->prepare("INSERT INTO user (`phonenum`, `email`, `name`, `birth`, `address`, `front`, `back`, `pass`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $insert_stmt->bind_param("sssssbbs", $phonenum, $email, $name, $birth, $address, $front_new_name, $back_new_name, $password_to_store);

                        if ($insert_stmt->execute()) {
                            $success = "Registration successful! Your password has been sent to $email.";
                            sleep(5);
                            header("Location: Login.php");
                        } else {
                            $error = "Error saving data: " . $con->error;
                        }
                        $insert_stmt->close();
                    } else {
                        $error = "Account created but failed to send email. Please contact support.";
                    }
                } else {
                    $error = "Failed to upload images. Please check folder permissions.";
                }
            }
        }
        $check_query->close();
    }
    $con->close();
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
            <form action="register.php" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow">
                <h2 class="text-center mb-4">Sign up</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div> <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Full name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Email address" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phonenum" class="form-control" placeholder="Phone number" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="birth" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2" required></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID Card Front</label>
                        <input type="file" name="front" class="form-control" accept="image/*" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID Card Back</label>
                        <input type="file" name="back" class="form-control" accept="image/*" required>
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
<?php include("../src/footer.php"); ?>