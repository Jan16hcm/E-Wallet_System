<!-- To be able to use the service of the e-wallet website, the user needs to register an account on the website. When registering, users need to enter
information including: phone number, email, full name, date of birth, address, upload photo of the front and back of the identity card. Each user
must have a different email address and a different phone number. After successful registration, the user will be generated a random password
(any string of 6 characters), both email and phone number can be used as username for login. These two information will be automatically sent to
the user's email immediately. If you can't do the email sending feature, you need to display these two information on the website interface right
after successful registration. -->
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
    <?php include("../src/header.php") ?>
    <form>
        <div class="register-container">
            <div class="d-flex flex-column align-items-center p-3 bg-white rounded shadow"  style="width: 600px;">
                <h2 class="mb-3">Create an Account</h2>
                <div class="mb-3 w-100">
                    <label for="fullName" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="fullName" placeholder="Enter your full name">
                </div>
                <div class="mb-3 w-100">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" placeholder="Enter your email">
                </div>
                <div class="mb-3 w-100">
                    <label for="phoneNumber" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="phoneNumber" placeholder="Enter your phone number">
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
                    <label for="idCardFront" class="form-label">ID Card Front</label>
                    <input type="file" class="form-control" id="idCardFront">
                </div>
                <div class="mb-3 w-100">
                    <label for="idCardBack" class="form-label">ID Card Back</label>
                    <input type="file" class="form-control" id="idCardBack">
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
                <button type="button" onclick="location.href='Login.php'" class="btn btn-link mt-2 opacity-75" style="text-decoration: none;">Already have an account? Sign in</button>
            </div>
        </div>
    </form>
    <?php
    include("../src/footer.php");
    ?>
</body>

</html>