<?php
    require_once("../modules/db_connection.php");
    require_once("../modules/usertype.php");

    $usertype = usertype();//3 == admin, 2 = Reque st additional information, -1 = first login 
    
    include '../src/header.php';
    
    $useremail = htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8');
    $username = htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8');
    $userphone = htmlspecialchars($_SESSION['phonenum'], ENT_QUOTES, 'UTF-8');
    echo "<h1 class='text-center mt-5'>Welcome, $username!</h>";
    echo "<h2 class='text-center mb-4'>Your phone number: $userphone</h2>";
    echo "<h2 class='text-center mb-4'>Your email: $useremail</h2>";
    echo "<h2 class='text-center mb-4'>Your user type: $usertype</h2>";

    include '../src/footer.php';
?>