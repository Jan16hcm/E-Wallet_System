<!-- To be able to use the service of the e-wallet website, the user needs to register an account on the website. When registering, users need to enter
information including: phone number, email, full name, date of birth, address, upload photo of the front and back of the identity card. Each user
must have a different email address and a different phone number. After successful registration, the user will be generated a random password
(any string of 6 characters), both email and phone number can be used as username for login. These two information will be automatically sent to
the user's email immediately. If you can't do the email sending feature, you need to display these two information on the website interface right
after successful registration. -->
<?php
    include_once("../modules/db_connection.php");
    include_once("../modules/send_otp.php");
    include_once("../modules/isValidDate.php");

    $name = '';
    $email = '';
    $phonenum = '';
    $birth = '';
    $address = '';
    $front = '';
    $back = '';
    $error = '';

    if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['phonenum']) && isset($_POST['birth']) 
    && isset($_POST['address']) && isset($_POST['front']) && isset($_POST['back'])){
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phonenum = $_POST['phonenum'];
        $birth = $_POST['birth'];
        $address = $_POST['address'];
        $front = $_POST['front'];
        $back = $_POST['back'];

        if (empty($name)) {
            $error = 'Please enter your name';
        } else if (empty($email)) {
            $error = 'Please enter your email';
        } else if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
            $error = 'This is not a valid email address';
        } else if (empty($phonenum)) {
            $error = 'Please enter your phone number';
        } else if (mb_strlen($phonenum) < 5 || mb_strlen($phonenum) > 15) {
            $error = 'A phone number length must be greater than 5 and less than 15';
        } else if (filter_var($phonenum, FILTER_VALIDATE_INT) == false){
            $error = 'A phone number only contain number';
        } else if (empty($birth)) {
            $error = 'Please enter your birthdate';
        } else if (isValidDate($birth)) {
            $error = 'Invalid birthdate, format must be Year-month-day';
        } else if ($birth > time()) {
            $error = 'Your birthdate must be in the past';
        } else if (empty($address)) {
            $error = 'Please enter your address';
        } else if (empty($front)) {
            $error = 'Please upload the front of your id card';
        } else if (empty($back)) {
            $error = 'Please upload the back of your id card';
        } else {
            //picture check
            $target_file = "../uploads/" . basename($_FILES["fileToUpload"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
            
            // Check if image file is a actual image or fake image
            if(!getimagesize($_FILES["fileToUpload"]["tmp_name"])) {
                $error = 'This file is not a picture';
            }
            if ($_FILES["fileToUpload"]["size"] > 2048000) {
                $error = 'Your picture must be < 2MB';
            }
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                $error = 'Only JPG, JPEG, PNG files are allowed';
            }
            if($error == '') {
                $con = connect_db();
                $result = $con->query("SELECT email, phonenum, name FROM user");
                $duplicate = false;

                if ($result->num_rows > 0) {    //check if database is not empty
                    while($row = $result->fetch_assoc()) { //check duplicate
                        if($row["email"] == $email && $row["phonenum"] == $phonenum && $row["name"] == $name) {
                            $con->close();
                            $duplicate = true;
                            $error = 'Please use login site to login, ' . $name;
                        }
                    }
                }

                if(!$duplicate){
                    $otp=strval(rand(100000,999999));
                    $_SESSION['otp'] = $otp;//first pass
                    $_SESSION['email'] = $email;
                    if(send_otp_email($otp, $email, $name)){
                        $error = 'Failed to send mail, please try again later';
                    } else {
                        $res = $con->prepare("INSERT INTO user VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NULL, 0, NULL, NULL)");
                        $res->bind_param("sssssbbs", $phonenum, $email, $name, $birth, $address, $front, $back, $otp);
                        /*  i - integer
                            d - double 
                            s - string
                            b - binary (image, PDF,...)*/
                        $res->execute();
                        //echo 'good';
                        $res->close();
                        $con->close();
                        header('Location: Home.php');
                    }
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
    <title>Register</title>
</head>

<body>
    <?php include("../src/headerOutSide.php") ?>
    <form>
        <div class="register-container">
            <div class="d-flex flex-column align-items-center p-3 bg-white rounded shadow"  style="width: 600px;">
                <h2 class="mb-3">Create an Account</h2>
                <div class="mb-3 w-100">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" placeholder="Enter your full name">
                </div>
                <div class="mb-3 w-100">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" placeholder="Enter your email">
                </div>
                <div class="mb-3 w-100">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="phone" placeholder="Enter your phone number">
                </div>
                <div class="mb-3 w-100">
                    <label for="dob" class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" id="dob">
                </div>
                <div class="mb-3 w-100">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" placeholder="Enter your address">
                </div>
                <div class="mb-3 w-100">
                    <label for="front" class="form-label">ID Card Front</label>
                    <input type="file" class="form-control" id="front">
                </div>
                <div class="mb-3 w-100">
                    <label for="back" class="form-label">ID Card Back</label>
                    <input type="file" class="form-control" id="back">
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
                <button type="button" onclick="location.href='register.php'" class="btn btn-link mt-2 opacity-75" style="text-decoration: none;">Already have an account? Sign in</button>
                <?php
                    if (!empty($error)) {
                        echo "<div class='alert alert-danger'>$error</div>";
                    }
                ?>
            </div>
        </div>
    </form>
    <?php
    include("../src/footer.php");
    ?>
</body>
<script>
    /*
    
    */
</script>
</html>