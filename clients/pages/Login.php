<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/css/login.css">
    <title>Login</title>
</head>
<body>
    <form action="Home.php">
        <!-- <div class="container-fluid mt-5"> -->
            <div class="login-container">
                <div class="row login-box">
                    <div id="loginMenu" class="col-lg-6 p-5">
                        <h2 class="mb-5 text-center">Meo Meo Wallet</h2>
            
                        <div class="mb-4">
                            <label for="username" class="form-label fw-semibold fs-4">Username</label>
                            <input type="text" class="form-control p-3 fs-5" placeholder="Type your username" id="username">
                        </div>
        
                        <div class="mb-3">
                            <label for="pwd" class="form-label fw-semibold fs-4">Password</label>
                            <input type="password" class="form-control p-3 fs-5" placeholder="Type your password" id="pwd">
                        </div>
        
                        <button type="submit" class="btn btn-success w-100 py-2 mt-3 fs-4" id="btn-signin">Sign in</button>
                    </div>
            
                    <div id="loginBackground" class="col-md-6 d-none d-lg-block p-0">
                        <img src="../assets/img/meomeoBackground.jpg" alt="Meo Meo Login background" class="login-image">
                    </div>
                </div>
            </div>
        <!-- </div> -->
    </form>
</body>
</html>