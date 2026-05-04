<?php
    session_start();
    if (!isset($_SESSION['user_phone']) || !isset($_SESSION['user_name'])) {
        header("Location: Login.php");
        exit();
    }
    include '../src/header.php';

    $username = htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8');
    $userphone = htmlspecialchars($_SESSION['user_phone'], ENT_QUOTES, 'UTF-8');
    echo "<h1 class='text-center mt-5'>Welcome, $username!</h>";
    echo "<h2 class='text-center mb-4'>Your phone number: $userphone</h2>";

    include '../src/footer.php';
?>