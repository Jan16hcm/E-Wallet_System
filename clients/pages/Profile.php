<?php
require_once("../modules/db_connection.php");
require_once("../modules/usertype.php");
require_once '../../vendor/autoload.php';

$usertype = (string)usertype(); // 3 == admin, 2 = Request additional information, -1 = first login 
if ($usertype != "0" && $usertype != "1" && $usertype != "2") {
    header('Location: Login.php');
    exit();
}

$detect = new Detection\MobileDetect;
$is_desktop = false;
if (!$detect->isMobile() && !$detect->isTablet()) {
    $is_desktop = true;
}

// Ensure session variables are set
$useremail_session = $_SESSION['email'] ?? '';

// Fetch user data from database to ensure we have the latest and complete info
$con = connect_db();
$stmt = $con->prepare("SELECT `name`, `email`, `phonenum`, `birth`, `address`, `verified`, `card_num`, `money`, `CVV` FROM `user` WHERE `email` = ?");
$stmt->bind_param("s", $useremail_session);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    // Fallback if user not found (shouldn't happen if session is valid)
    header('Location: Login.php');
    exit();
}
$stmt->close();
$con->close();

$username = htmlspecialchars($user_data['name'], ENT_QUOTES, 'UTF-8');
$useremail = htmlspecialchars($user_data['email'], ENT_QUOTES, 'UTF-8');
$userphone = htmlspecialchars($user_data['phonenum'], ENT_QUOTES, 'UTF-8');
$userbirth = htmlspecialchars($user_data['birth'], ENT_QUOTES, 'UTF-8');
$useraddress = htmlspecialchars($user_data['address'], ENT_QUOTES, 'UTF-8');
$card_num = htmlspecialchars($user_data['card_num'], ENT_QUOTES, 'UTF-8');
$cvv = htmlspecialchars($user_data['CVV'], ENT_QUOTES, 'UTF-8');
$money = number_format($user_data['money'], 0, ',', '.');

$current_date = strtoupper(date('l, F j'));

include '../src/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/home.css">
<link rel="stylesheet" href="../assets/css/profile.css">

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="user-profile-card">
            <button class="theme-toggle" id="themeToggleBtn">
                <i class="fa-solid fa-moon"></i>
            </button>
            <div class="avatar"><?= strtoupper(substr($username, 0, 2)) ?></div>
            <div class="date-text"><?= $current_date ?></div>
            <div class="welcome-text">Welcome back,<br><?= $username ?>!</div>
        </div>

        <nav class="nav-menu">

            <?php if($usertype != "0") { ?>

            <a href="Home.php" class="nav-link"><i class="fa-solid fa-border-all"></i> Dashboard</a>
            <a href="Profile.php" class="nav-link active"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="transfer.php" class="nav-link"><i class="fa-solid fa-money-bill-transfer"></i> Transfer money</a>
            <a href="withdraw.php" class="nav-link"><i class="fa-solid fa-arrow-up-from-bracket"></i> Withdraw</a>
            <a href="deposit.php" class="nav-link"><i class="fa-solid fa-wallet fa-arrow-down-to-bracket"></i> Deposit money</a>
            <a href="transactions.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> Transaction history</a>
            <a href="Buycard.php" class="nav-link"><i class="fa-solid fa-mobile-screen-button"></i> Buy phone card</a>
            <a href="ChangePassword.php" class="nav-link"><i class="fa-solid fa-gear"></i> Change Password</a>

            <?php } else { ?>
                <a href="Profile.php" class="nav-link active"><i class="fa-solid fa-user"></i> Profile</a>
            <?php } ?>

        </nav>
    </aside>

    <main class="main-content">
        <div class="mobile-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="avatar" style="width: 40px; height: 40px; margin: 0; font-size: 16px;">
                    <?= strtoupper(substr($username, 0, 2)) ?>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-muted);">Profile</div>
                    <div style="font-size: 15px; font-weight: 700;"><?= $username ?></div>
                </div>
            </div>
            <button class="theme-toggle" style="position: relative; top: 0; right: 0; border: 1px solid var(--border-color);">
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>

        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($username, 0, 2)) ?>
                </div>
                <div class="profile-info">
                    <h1><?= $username ?></h1>
                    <p><i class="fa-solid fa-envelope"></i> <?= $useremail ?></p>
                    
                    <?php if ($usertype === "1"): ?>
                        <div class="verified-badge badge-success">
                            <i class="fa-solid fa-circle-check"></i> Verified Account
                        </div>
                    <?php elseif ($usertype === "2"): ?>
                        <div class="verified-badge badge-warning">
                            <i class="fa-solid fa-circle-exclamation"></i> Action Required: Update Info
                        </div>
                    <?php elseif ($usertype === "0"): ?>
                        <div class="verified-badge badge-warning">
                            <i class="fa-solid fa-clock"></i> Pending Verification
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-grid">
                
                <div class="profile-card">
                    <div class="profile-card-header">
                        <i class="fa-solid fa-address-card"></i> Personal Information
                    </div>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Full Name</span>
                            <span class="info-value"><?= $username ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date of Birth</span>
                            <span class="info-value"><?= date("F j, Y", strtotime($userbirth)) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone Number</span>
                            <span class="info-value"><?= $userphone ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Address</span>
                            <span class="info-value"><?= $useraddress ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="profile-card-header">
                        <i class="fa-solid fa-shield-halved"></i> Account Status
                    </div>
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Email Address</span>
                            <span class="info-value"><?= $useremail ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Verification Level</span>
                            <span class="info-value">
                                <?php
                                if ($usertype === "1") echo "Fully Verified (Level 2)";
                                if ($usertype === "0") echo "Pending Review (Level 1)";
                                ?>
                                <?php if ($usertype === "0") { ?>
                                <span class="info-label"><b>Waiting for Verification</b></span>
                                <?php }?>
                            </span>
                        </div>
                        <div style="margin-top: 10px;">
                            <a href="ChangePassword.php" class="btn btn-outline" style="width: 100%; text-align: center; display: block; text-decoration: none;">
                                Change Password
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php 
                    if($cvv != '')
                    {
                ?>
                <div class="card-mockup-wrapper">
                    <div class="card-mockup">
                        <div class="card-balance-label">Available Balance</div>
                        <div class="card-balance-value"><?= $money ?> ₫</div>
                        
                        <div class="card-details">
                            <div>
                                <div class="card-balance-label">Card Number</div>
                                <div class="card-num">•••• •••• •••• <?= substr($card_num, -4) ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div class="card-balance-label">CVV</div>
                                <div class="card-cvv"><?= $cvv ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </main>
</div>

<div class="mobile-bottom-nav">
    <a href="Home.php" class="nav-item">
        <i class="fa-solid fa-house"></i>
        <span>Home</span>
    </a>
    <a href="transactions.php" class="nav-item">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>History</span>
    </a>
    <a href="Buycard.php" class="nav-item scan-btn">
        <div class="scan-circle">
            <i class="fa-solid fa-mobile-screen"></i>
        </div>
        <span>Phone Card</span>
    </a>
    <a href="transfer.php" class="nav-item">
        <i class="fa-solid fa-arrow-right-arrow-left"></i>
        <span>Transfer</span>
    </a>
    <a href="Profile.php" class="nav-item active">
        <i class="fa-solid fa-user"></i>
        <span>Profile</span>
    </a>
</div>

<script>
    // Theme Toggle Logic
    const themeToggleBtns = document.querySelectorAll('.theme-toggle');
    const body = document.body;

    themeToggleBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            body.classList.toggle('light-theme');

            themeToggleBtns.forEach(b => {
                const icon = b.querySelector('i');
                if (icon) {
                    if (body.classList.contains('light-theme')) {
                        icon.classList.replace('fa-moon', 'fa-sun');
                    } else {
                        icon.classList.replace('fa-sun', 'fa-moon');
                    }
                }
            });
        });
    });
</script>

<?php
if ($is_desktop) {
    include '../src/footer.php';
} else {
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
?>
