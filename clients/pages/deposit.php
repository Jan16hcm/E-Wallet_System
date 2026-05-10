<?php
include_once("../modules/db_connection.php");
include_once("../modules/usertype.php");
include_once("../modules/isValidDate.php");
include_once("../modules/formatMoney.php");
include_once("../modules/getTodayWithdrawCount.php");
include_once("../modules/generateCode.php");
include_once("../modules/isValidCard.php");//this is getting long

$usertype = usertype();
$card_num = '';//String
$expire = '';
$cvv = '';
$amount = 0;
$note = '';
$error = checkuser((int)$usertype);
if(!empty($error)){
    $_SESSION['error'] = $error;
    redirectHome();
}
$success_msg = '';

$username = htmlspecialchars($_SESSION['name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$current_date = strtoupper(date('l, F j'));

if (isset($_POST['card_number']) && isset($_POST['expire']) && isset($_POST['cvv']) && 
    isset($_POST['amount']) && $_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    
    $card_num = trim($_POST['card_number'] ?? '');
    $expire = trim($_POST['expire'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $amount_str = str_replace(',', '', trim($_POST['amount'] ?? ''));
    $note = trim($_POST['note'] ?? '');

    if (empty($card_num)) {
        $error = 'Please enter the card number';
    } else if (empty($expire)) {
        $error = 'Please enter the expiration date';
    } else if (empty($cvv)) {
        $error = 'Please enter the cvv number';
    } else if (empty($amount_str)) {
        $error = 'Please enter the amount to deposit';
    } else if (!is_numeric($amount_str)) {
        $error = 'This is not a valid number to deposit';
    } else {
        $amount = floatval($amount_str);
        $date_error = isValidDate($expire);
        $valid_error = isValidDepositCard($card_num, $expire, $cvv, $amount);
        
        if ($amount <= 0) {
            //put link to deposit page here -----------------
            $error = 'Please visit the <a href="withdraw.php">withdraw page</a> if you want to withdraw';
        } else if (!empty($date_error)) {
            $error = $date_error;
        } else if (!empty($valid_error)) {
            $error = $valid_error;
        } else {
            $selfPhone = '';
            $con = connect_db();
            $dep = $con->prepare("SELECT phonenum FROM user where email = ?");
            $dep->bind_param("s", $_SESSION["email"]);
            
            if(!$dep->execute()){
                $error = 'Error in database, please try again later';
            } else {
                $dep->bind_result($selfPhone);
                if (!$dep->fetch()) {
                    $error = 'User account not found';
                }
            }
            $dep->close();

            if (!empty($selfPhone) && empty($error)) {
                $status = 1; 
                $transfer_type = "Deposit";
                $id = generateIdCode($selfPhone, 2);
                $date = date('Y-m-d H:i:s');
                
                $dt = DateTime::createFromFormat('d/m/Y', $expire);
                $expire_db = $dt->format('Y-m-d');

                $dep = $con->prepare("INSERT INTO `history` (`id`, `user_phone`, `transfer_type`, `card_num`, `expire`, `CVV`, `date_transfer`, `money`, `note`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $dep->bind_param("sssssssdsi", $id, $selfPhone, $transfer_type, $card_num, $expire_db, $cvv, $date, $amount, $note, $status);

                if(!$dep->execute()){
                    $error = 'Failed to save in deposit history';
                } else {
                    $dep->close();
                    
                    $dep = $con->prepare("UPDATE user SET money = money + ? WHERE phonenum = ?");
                    $dep->bind_param("ds", $amount, $selfPhone);

                    if(!$dep->execute()){
                        $error = 'Failed to update user balance, cancelled the deposit';
                        $status = 0;
                        $canceldate = date('Y-m-d H:i:s');
                        $dep->close();
                        $dep = $con->prepare("UPDATE history SET status = ?, date_confirm = ? WHERE id = ?");
                        $dep->bind_param("iss", $status, $canceldate, $id);
                        $dep->execute();
                    } else {
                        $masked_card = "•••• " . substr($card_num, -4);
                        $success_msg = "Successfully deposited " . number_format($amount, 0, ',', '.') . " ₫ from card $masked_card.";
                        if (!empty($note)) {
                            $success_msg .= " Note: " . htmlspecialchars($note);
                        }
                        
                        // Update session money
                        $stmt = $con->prepare("SELECT `money` FROM `user` WHERE `phonenum` = ?");
                        $stmt->bind_param("s", $selfPhone);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            $_SESSION["money"] = $row["money"];
                        }
                        $stmt->close();

                        // clear fields on success
                        $card_num = ''; $expire = ''; $cvv = ''; $amount = 0; $note = '';
                    }
                }
            }
            $dep->close();
            $con->close();
        }
    }
}
include("../src/header.php");
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/home.css">
<link rel="stylesheet" href="../assets/css/profile.css">
<link rel="stylesheet" href="../assets/css/deposit.css">

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-wrapper">
    <aside class="sidebar" id="sidebar">
        <div class="user-profile-card">
            <button class="theme-toggle" id="themeToggleBtn">
                <i class="fa-solid fa-moon"></i>
            </button>
            <div class="avatar"><?= strtoupper(substr($username, 0, 2)) ?></div>
            <div class="date-text"><?= $current_date ?></div>
            <div class="welcome-text">Welcome back,<br><?= $username ?>!</div>
        </div>

        <nav class="nav-menu">
            <?php if ($usertype === "0" || $usertype === "2") { ?>
                <a href="#" class="nav-link restricted-feature"><i class="fa-solid fa-border-all"></i> Dashboard</a>
                <a href="Profile.php" class="nav-link"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="#" class="nav-link restricted-feature"><i class="fa-solid fa-money-bill-transfer"></i> Transfer money</a>
                <a href="#" class="nav-link restricted-feature"><i class="fa-solid fa-arrow-up-from-bracket"></i> Withdraw</a>
                <a href="#" class="nav-link restricted-feature active"><i class="fa-solid fa-wallet fa-arrow-down-to-bracket"></i> Deposit money</a>
                <a href="#" class="nav-link restricted-feature"><i class="fa-solid fa-clock-rotate-left"></i> Transaction history</a>
                <a href="#" class="nav-link restricted-feature"><i class="fa-solid fa-mobile-screen-button"></i> Buy phone card</a>
                <a href="ChangePassword.php" class="nav-link"><i class="fa-solid fa-gear"></i> Change Password</a>
            <?php } else { ?>
                <a href="Home.php" class="nav-link"><i class="fa-solid fa-border-all"></i> Dashboard</a>
                <a href="Profile.php" class="nav-link"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="transfer.php" class="nav-link"><i class="fa-solid fa-money-bill-transfer"></i> Transfer money</a>
                <a href="withdraw.php" class="nav-link"><i class="fa-solid fa-arrow-up-from-bracket"></i> Withdraw</a>
                <a href="deposit.php" class="nav-link active"><i class="fa-solid fa-wallet fa-arrow-down-to-bracket"></i> Deposit money</a>
                <a href="transactions.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> Transaction history</a>
                <a href="Buycard.php" class="nav-link"><i class="fa-solid fa-mobile-screen-button"></i> Buy phone card</a>
                <a href="ChangePassword.php" class="nav-link"><i class="fa-solid fa-gear"></i> Change Password</a>
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
                    <div style="font-size: 11px; color: var(--text-muted);">Deposit</div>
                    <div style="font-size: 15px; font-weight: 700;"><?= $username ?></div>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button class="theme-toggle" style="border: 1px solid var(--border-color); background: transparent; color: var(--text-main);">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <button class="sidebar-toggle-btn" id="sidebarToggleBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>

        <div class="profile-container" style="display: flex; flex-direction: column; align-items: center; min-height: calc(100vh - 100px); padding-top: 20px;">
            <div style="width: 100%; max-width: 800px;">
                <h2 style="margin-bottom: 20px;">Deposit Money</h2>
                
                <div id="alert-error" class="alert alert-danger" style="<?= empty($error) ? 'visibility: hidden; opacity: 0;' : 'visibility: visible; opacity: 1;' ?> transition: opacity 0.3s ease;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="err-text"><?= htmlspecialchars($error) ?></span>
                </div>

                <div id="alert-success" class="alert alert-success" style="<?= empty($success_msg) ? 'visibility: hidden; opacity: 0;' : 'visibility: visible; opacity: 1;' ?> transition: opacity 0.3s ease;">
                    <i class="fa-solid fa-circle-check"></i>
                    <span id="success-text"><?= htmlspecialchars($success_msg) ?></span>
                </div>

                <div class="profile-card" style="width: 100%;">
                    <div class="profile-card-header" style="font-size: 18px; padding: 20px;">
                        <i class="fa-solid fa-credit-card"></i> Credit Card Details
                    </div>
                    <div style="padding: 30px;">
                    <form id="depositForm" method="POST" action="deposit.php" class="deposit-form">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-size: 15px; margin-bottom: 8px;">Card Number (6 digits)</label>
                            <input type="text" name="card_number" value="<?= htmlspecialchars($card_num) ?>" placeholder="******"  maxlength="6" pattern="\d{6}" style="padding: 16px; font-size: 16px;">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 15px; margin-bottom: 8px;">Expiration Date (dd/mm/yyyy)</label>
                                <input type="text" name="expire" value="<?= htmlspecialchars($expire) ?>" placeholder="dd/mm/yyyy"  style="padding: 16px; font-size: 16px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 15px; margin-bottom: 8px;">CVV (3 digits)</label>
                                <input type="text" name="cvv" value="<?= htmlspecialchars($cvv) ?>" placeholder="***"  maxlength="3" pattern="\d{3}" style="padding: 16px; font-size: 16px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-size: 15px; margin-bottom: 8px;">Amount (VND)</label>
                            <input type="text" name="amount" value="<?= htmlspecialchars($amount > 0 ? $amount : '') ?>" placeholder="00,000"  style="padding: 16px; font-size: 16px;">
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="font-size: 15px; margin-bottom: 8px;">Note </label>
                            <input type="text" name="note" value="<?= htmlspecialchars($note) ?>"  style="padding: 16px; font-size: 16px;">
                        </div>

                        <button type="submit" id="submitBtn" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 18px; font-weight: 600; border-radius: 10px;">Deposit Now</button>
                    </form>
                </div>
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
    <a href="Profile.php" class="nav-item">
        <i class="fa-solid fa-user"></i>
        <span>Profile</span>
    </a>
</div>
<script src="../assets/js/deposit.js"></script>
<?php include("../src/footer.php"); ?>