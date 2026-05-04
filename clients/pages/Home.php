<?php
    session_start();
    require_once("../modules/db_connection.php");
    require_once("../modules/usertype.php");

    $usertype = usertype();//3 == admin, 2 = Request additional information, -1 = first login 
    
    include '../src/header.php';

    $username = htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8');
    $userphone = htmlspecialchars($_SESSION['user_phone'], ENT_QUOTES, 'UTF-8');
    echo "<h1 class='text-center mt-5'>Welcome, $username!</h>";
    echo "<h2 class='text-center mb-4'>Your phone number: $userphone</h2>";

    include '../src/footer.php';
?>
<?php
    // require_once("../modules/db_connection.php");
    // $con = connect_db();
    // if (!isset($_SESSION["email"])) {
    //     header("Location: Login.php");
    //     exit();
    // }
    // include '../src/header.php';

    // $stmt = $con->prepare('SELECT `name`, `phone` FROM user WHERE email = ?');
    // $stmt->bind_param('s', $_SESSION['email']);
    // $stmt->execute();
    // $result = $stmt->get_result();

    // $row = $result->fetch_assoc();
    // $username = htmlspecialchars($row['name']);
    // $phonenum = htmlspecialchars($row['phonenum']);
    // echo "<h1 class='text-center mt-5'>Welcome, $$username!</h>";
    // echo "<h2 class='text-center mb-4'>Your phone number: $$phonenum</h2>";

    // include '../src/footer.php';
?>