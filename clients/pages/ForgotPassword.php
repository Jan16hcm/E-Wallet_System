<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/register.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/css/forgot.css">

    <title>Forgot Password</title>
</head>

<body>
    <?php include("../src/header.php"); ?>
    <link rel="stylesheet" href="../assets/css/forgot.css">

    <body>
        <div class="container" style="max-width: 500px; margin: 80px auto;">
            <button class="btn btn-secondary mt-3 opacity-75 d-flex align-items-center justify-content-center"
                onclick="location.href='Login.php'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-box-arrow-left" viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                        d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0z" />
                    <path fill-rule="evenodd"
                        d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708z" />
                </svg>
                <span class="ms-2">Back to Login</span>
            </button>

            <div class="card p-4 mt-4 shadow-sm">
                <h2 class="mt-2">Forgot Password</h2>
                <p class="text-muted mb-4">Please enter your email to reset your password.</p>

                <form action="ResetPassword.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Your Email</label>
                        <input type="email" class="form-control" id="username" name="username"
                            placeholder="Enter your email" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                </form>
            </div>
        </div>
    </body>

    <?php include("../src/footer.php"); ?>

</html>