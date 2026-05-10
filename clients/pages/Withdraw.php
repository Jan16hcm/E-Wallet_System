<?php
require_once("../modules/db_connection.php");
require_once("../modules/usertype.php");
require_once("../modules/isValidDate.php");
require_once("../modules/formatMoney.php");
require_once("../modules/generateCode.php");
require_once("../modules/getTodayWithdrawCount.php");
require_once("../modules/isValidCard.php");

$usertype = usertype();
$error = checkuser((int)$usertype);
if(!empty($error)){
    $_SESSION['error'] = $error;
    redirectHome();
}

$card_num = '';
$expire = '';
$cvv = '';
$amount = 0;
$note = '';
$error = '';
$success = false;
$pendingApproval = false;
$username = $_SESSION['name'] ?? 'User';
$current_date = strtoupper(date('l, F j'));

$con = connect_db();
$stmt = $con->prepare("SELECT `phonenum`, `money` FROM `user` WHERE `email` = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($selfPhone, $selfamount);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_num = trim($_POST['card_num'] ?? '');
    $expire = trim($_POST['expire'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $amount_str = str_replace(['.', ','], '', $_POST['amount'] ?? '');
    $amount = floatval($amount_str);
    $amount_int = (int)round($amount); // use integer for modulo checks
    $note = trim($_POST['note'] ?? '');

    if (empty($card_num) || empty($expire) || empty($cvv) || $amount <= 0) {
        $error = 'Please fill in all required fields correctly.';
    } else if (getTodayWithdrawCount($_SESSION['email']) >= 2) {
        $error = 'Daily withdrawal limit reached (max 2). Please try again tomorrow.';
    } else if ($amount_int % 50000 !== 0) {
        $error = 'Withdrawal amount must be a multiple of 50,000 VND.';
    } else {
        $date_error = isValidDate($expire);
        $valid_error = isValidWithdrawCard($card_num, $expire, $cvv);
        
        if (!empty($date_error)) {
            $error = $date_error;
        } else if (!empty($valid_error)) {
            $error = $valid_error;
        } else {
            $totalDeduct = $amount * 1.05; // 5% fee
            if ($selfamount < $totalDeduct) {
                $error = 'Insufficient balance. You need ' . number_format($totalDeduct, 0, ',', '.') . ' ₫ (incl. 5% fee).';
            } else {
                $status = ($amount > 5000000) ? 2 : 1; // 1: Approved/Completed, 2: Pending
                $type = "Withdraw";
                $now = date('Y-m-d H:i:s');
                $id = generateIdCode($selfPhone, 3);

                $con->begin_transaction();
                try {
                    $fee = $amount * 0.05;
                    $stmt = $con->prepare("INSERT INTO `history` (`id`, `user_phone`, `transfer_type`, `card_num`, `date_transfer`, `money`, `fee`, `note`, `status`, `selfFeeBear`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $selfFeeBear = 1;
                    $stmt->bind_param("sssssddsii", $id, $selfPhone, $type, $card_num, $now, $amount, $fee, $note, $status, $selfFeeBear);
                    $stmt->execute();

                    if ($status == 1) {
                        // Only deduct immediately if the transaction does not require admin approval
                        $stmt = $con->prepare("UPDATE `user` SET `money` = `money` - ? WHERE `phonenum` = ?");
                        $stmt->bind_param("ds", $totalDeduct, $selfPhone);
                        $stmt->execute();
                        $_SESSION['money'] -= $totalDeduct;
                    }

                    if ($status == 1) {
                        $success = true;
                    } else {
                        $pendingApproval = true;
                    }
                    $con->commit();
                } catch (Exception $e) {
                    $con->rollback();
                    $error = 'Transaction failed: ' . $e->getMessage();
                }
            }
        }
    }
}
$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Money - Antigravity Wallet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="../assets/css/transaction.css">
    <link rel="stylesheet" href="../assets/css/withdraw.css">
</head>
<body>
<script>
    if (localStorage.getItem("theme") !== "dark") { document.body.classList.add("light-theme"); }
</script>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-wrapper">
    <aside class="sidebar" id="sidebar">
        <div class="user-profile-card">
            <button class="theme-toggle" id="themeToggleBtn"><i class="fa-solid fa-moon"></i></button>
            <div class="avatar"><?= strtoupper(substr($username, 0, 2)) ?></div>
            <div class="date-text"><?= $current_date ?></div>
            <div class="welcome-text">Welcome back,<br><?= $username ?>!</div>
        </div>
        <nav class="nav-menu">
            <a href="Home.php" class="nav-link"><i class="fa-solid fa-border-all"></i> Dashboard</a>
            <a href="Profile.php" class="nav-link"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="Transfer.php" class="nav-link"><i class="fa-solid fa-money-bill-transfer"></i> Transfer money</a>
            <a href="Withdraw.php" class="nav-link active"><i class="fa-solid fa-arrow-up-from-bracket"></i> Withdraw</a>
            <a href="Deposit.php" class="nav-link"><i class="fa-solid fa-wallet fa-arrow-down-to-bracket"></i> Deposit money</a>
            <a href="Transactions.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> Transaction history</a>
            <a href="Buycard.php" class="nav-link"><i class="fa-solid fa-mobile-screen-button"></i> Buy phone card</a>
            <a href="ChangePassword.php" class="nav-link"><i class="fa-solid fa-gear"></i> Change Password</a>
            <a href="../modules/logout.php" class="nav-link" style="color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="mobile-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="avatar" style="width: 40px; height: 40px; margin: 0; font-size: 16px;"><?= strtoupper(substr($username, 0, 2)) ?></div>
                <div>
                    <div style="font-size: 11px; color: var(--text-muted);">Withdraw</div>
                    <div style="font-size: 15px; font-weight: 700;"><?= $username ?></div>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button class="theme-toggle" style="border: 1px solid var(--border-color); background: transparent; color: var(--text-main);"><i class="fa-solid fa-moon"></i></button>
                <button class="sidebar-toggle-btn" id="sidebarToggleBtn"><i class="fa-solid fa-bars"></i></button>
            </div>
        </div>

        <div class="withdraw-container">
            <?php if (!$success && !$pendingApproval): ?>
                <div class="header-actions" style="margin-bottom: 32px;">
                    <div class="header-welcome">
                        <div class="date-text"><?= $current_date ?></div>
                        <h1 style="font-size: 28px; font-weight: 800; color: var(--text-main); margin: 0;">Withdraw Money</h1>
                    </div>
                </div>

                <div class="alert alert-danger" id="withdrawError" style="border-radius: 12px; margin-bottom: 24px; <?= $error ? '' : 'display:none;' ?>">
                    <i class="fa-solid fa-circle-exclamation" style="margin-right: 8px;"></i> <?= htmlspecialchars($error) ?>
                </div>

                <form method="POST" id="withdrawForm">
                    <div class="widget" style="padding: 32px; border-radius: 24px;">
                        <div class="form-group" style="margin-bottom: 24px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 700;">Card Number</label>
                            <input type="text" name="card_num" placeholder="6-digit card number" class="form-control" style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);" value="<?= htmlspecialchars($card_num) ?>" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-weight: 700;">Expiry Date</label>
                                <input type="text" name="expire" placeholder="dd/mm/yyyy" class="form-control" style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);" value="<?= htmlspecialchars($expire) ?>" required>
                            </div>
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-weight: 700;">CVV</label>
                                <input type="text" name="cvv" placeholder="3 digits" class="form-control" style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);" value="<?= htmlspecialchars($cvv) ?>" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label style="font-weight: 700;">Amount (VND)</label>
                                <span style="font-size: 13px; color: var(--accent-blue); font-weight: 600;">
                                    Balance: <?= number_format($selfamount, 0, ',', '.') ?> ₫
                                </span>
                            </div>
                            <input type="text" name="amount" id="amountInput" placeholder="Multiples of 50,000" class="form-control" style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);" value="<?= $amount > 0 ? number_format($amount, 0, ',', '.') : '' ?>" required>
                            <div id="amountError" style="color: #ef4444; font-size: 12px; margin-top: 8px; display: none; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span>Amount must be a multiple of 50,000 VND</span>
                            </div>
                        </div>

                        <div id="feePreview" class="fee-preview" style="background: rgba(255,255,255,0.03); padding: 16px; border-radius: 16px; margin-bottom: 24px;">
                            <!-- JS populated -->
                        </div>

                        <div class="form-group" style="margin-bottom: 24px;">
                            <label style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-weight: 700;">
                                <span>Note (Optional)</span>
                                <span id="noteCount" style="font-size: 12px; font-weight: normal; color: var(--text-muted);">0/50</span>
                            </label>
                            <textarea name="note" id="noteInput" rows="2" maxlength="50" class="form-control" style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);"><?= htmlspecialchars($note) ?></textarea>
                        </div>

                        <div class="alert alert-info" style="font-size: 13px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); color: #3b82f6; margin-bottom: 24px; border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-circle-info" style="color: #3b82f6; font-size: 16px;"></i>
                            <span style="font-weight: 600;">Max 2 withdrawals per day. 5% fee applies.</span>
                        </div>

                        <button type="submit" class="btn" style="width: 100%; padding: 16px; border-radius: 16px; background: var(--accent-blue); color: white; border: none; font-weight: 700; font-size: 18px; cursor: pointer;">
                            Withdraw Now
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <!-- Result State -->
                <div class="widget" style="padding: 40px; border-radius: 32px; text-align: center;">
                    <div class="success-icon" style="background: <?= $pendingApproval ? '#f59e0b' : '#10b981' ?>20; color: <?= $pendingApproval ? '#f59e0b' : '#10b981' ?>; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 24px;">
                        <i class="fa-solid <?= $pendingApproval ? 'fa-clock' : 'fa-check' ?>"></i>
                    </div>
                    <h2 style="font-weight: 800; color: var(--text-main);"><?= $pendingApproval ? 'Pending Approval' : 'Withdrawal Successful!' ?></h2>
                    <p style="color: var(--text-muted); margin-bottom: 32px;"><?= $pendingApproval ? 'Withdrawals over 5,000,000 VND require admin approval.' : 'Your request has been processed successfully.' ?></p>

                    <div class="summary-card" style="margin-bottom: 32px; text-align: left;">
                        <div class="summary-row"><span style="color: var(--text-muted);">Amount</span><span><?= number_format($amount, 0, ',', '.') ?> ₫</span></div>
                        <div class="summary-row"><span style="color: var(--text-muted);">Fee (5%)</span><span><?= number_format($amount * 0.05, 0, ',', '.') ?> ₫</span></div>
                        <div class="summary-total"><span>Total Deducted</span><span><?= number_format($amount * 1.05, 0, ',', '.') ?> ₫</span></div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <a href="Withdraw.php" class="btn" style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); text-decoration: none; text-align: center; padding: 12px; border-radius: 12px; font-weight: 600;">Withdraw More</a>
                        <a href="Transactions.php" class="btn" style="background: var(--accent-blue); color: white; text-decoration: none; text-align: center; padding: 12px; border-radius: 12px; font-weight: 600;">View History</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<div class="mobile-bottom-nav">
    <a href="Home.php" class="nav-item"><i class="fa-solid fa-house"></i><span>Home</span></a>
    <a href="Transactions.php" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i><span>History</span></a>
    <a href="Buycard.php" class="nav-item scan-btn"><div class="scan-circle"><i class="fa-solid fa-mobile-screen"></i></div><span>Phone Card</span></a>
    <a href="Transfer.php" class="nav-item"><i class="fa-solid fa-arrow-right-arrow-left"></i><span>Transfer</span></a>
    <a href="Withdraw.php" class="nav-item active"><i class="fa-solid fa-user"></i><span>Profile</span></a>
</div>

<script src="../assets/js/home.js"></script>
<script src="../assets/js/withdraw.js"></script>
</body>
</html>
