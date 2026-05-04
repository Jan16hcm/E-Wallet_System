<?php
    session_start();
    if (isset($_SESSION['user_phone']) || isset($_SESSION['user_name'])) {
        header("Location: Login.php");
        exit();
    }
    include '../src/header.php';

    $username = $_SESSION['user_name'];
    $userphone = $_SESSION['user_phone'];
    echo "<h1 class='text-center mt-5'>Welcome, $username!</h>";
    echo "<h2 class='text-center mb-4'>Your phone number: $userphone</h2>";

    include '../src/footer.php';
?>