<?php
require_once("../modules/db_connection.php");
require_once("../modules/usertype.php");
require_once("../modules/formatMoney.php");

$usertype = usertype();
if ($usertype != "1") {
    header('Location: Login.php');
    exit();
}

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: Transactions.php');
    exit;
}

$con = connect_db();

// 1. Get transaction main data
$stmt = $con->prepare("SELECT * FROM history WHERE id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    header('Location: Transactions.php');
    exit;
}

// Check if user is authorized to see this transaction
// (Must be sender or recipient)
$user_phone = '';
$stmt = $con->prepare("SELECT phonenum FROM user WHERE email = ?");
$stmt->bind_param("s", $_SESSION["email"]);
$stmt->execute();
$stmt->bind_result($user_phone);
$stmt->fetch();
$stmt->close();

if ($data['user_phone'] != $user_phone && $data['receiver_phone'] != $user_phone) {
    header('Location: Transactions.php');
    exit;
}

// 2. Get other user info if it's a transfer
$otherUser = null;
if (!empty($data['receiver_phone'])) {
    $target_phone = ($data['user_phone'] == $user_phone) ? $data['receiver_phone'] : $data['user_phone'];
    $stmt = $con->prepare("SELECT name, email, phonenum FROM user WHERE phonenum = ?");
    $stmt->bind_param("s", $target_phone);
    $stmt->execute();
    $otherUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// 3. Get phone card codes if it's a buy card transaction
$phonecards = [];
if ($data['transfer_type'] == 'Buy Card') {
    $stmt = $con->prepare("SELECT * FROM phonecard WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $phonecards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$con->close();

$current_date = strtoupper(date('l, F j'));
$username = $_SESSION['name'] ?? 'User';

$is_sender = ($data['user_phone'] == $user_phone);
$is_pos = ($data['transfer_type'] == 'Deposit' || (!$is_sender && $data['transfer_type'] == 'Transfer'));
$icon_color = $is_pos ? '#10b981' : '#ef4444';
$amount_prefix = $is_pos ? '+' : '-';
$status_label = ['Completed', 'Pending', 'Cancelled'][$data['status']] ?? 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Detail - Antigravity Wallet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="../assets/css/transaction.css">
</head>

<body>
    <script>
        if (localStorage.getItem("theme") !== "dark") {
            document.body.classList.add("light-theme");
        }
    </script>
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
                <a href="Home.php" class="nav-link"><i class="fa-solid fa-border-all"></i> Dashboard</a>
                <a href="Profile.php" class="nav-link"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="Transfer.php" class="nav-link"><i class="fa-solid fa-money-bill-transfer"></i> Transfer
                    money</a>
                <a href="Withdraw.php" class="nav-link"><i class="fa-solid fa-arrow-up-from-bracket"></i> Withdraw</a>
                <a href="Deposit.php" class="nav-link"><i class="fa-solid fa-wallet fa-arrow-down-to-bracket"></i>
                    Deposit money</a>
                <a href="Transactions.php" class="nav-link active"><i class="fa-solid fa-clock-rotate-left"></i>
                    Transaction history</a>
                <a href="Buycard.php" class="nav-link"><i class="fa-solid fa-mobile-screen-button"></i> Buy phone
                    card</a>
                <a href="ChangePassword.php" class="nav-link"><i class="fa-solid fa-gear"></i> Change Password</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="mobile-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="avatar" style="width: 40px; height: 40px; margin: 0; font-size: 16px;">
                        <?= strtoupper(substr($username, 0, 2)) ?>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted);">Detail</div>
                        <div style="font-size: 15px; font-weight: 700;"><?= $username ?></div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="theme-toggle"
                        style="border: 1px solid var(--border-color); background: transparent; color: var(--text-main);">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                    <button class="sidebar-toggle-btn" id="sidebarToggleBtn">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>
            </div>

            <div class="header-actions" style="margin-bottom: 20px;">
                <div class="header-welcome">
                    <div class="date-text"><?= $current_date ?></div>
                    <h1 style="font-size: 28px; font-weight: 800; color: var(--text-main); margin: 0;">Transaction
                        Details</h1>
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-header">
                    <div class="detail-icon" style="background: <?= $icon_color ?>20; color: <?= $icon_color ?>;">
                        <i class="fa-solid <?= $is_pos ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i>
                    </div>
                    <?php
                    $display_money = (float) $data['money'];
                    $fee = (float) $data['fee'];

                    if ($data['transfer_type'] == 'Transfer') {
                        if (!$is_sender) {
                            if (isset($data['selfFeeBear']) && $data['selfFeeBear'] == 0) {
                                $display_money = $display_money - $fee;
                            }
                        } else {
                            if (isset($data['selfFeeBear']) && $data['selfFeeBear'] == 1) {
                                $display_money = $display_money + $fee;
                            }
                        }
                    } elseif ($data['transfer_type'] == 'Withdraw') {
                        $display_money = $display_money + $fee;
                    }
                    ?>
                    <div class="detail-amount"><?= $amount_prefix ?><?= number_format($display_money, 0, ',', '.') ?> ₫
                    </div>
                    <?php $status_label = ['Declined', 'Approved', 'Pending'][$data['status']] ?? 'Unknown'; ?>
                    <div class="detail-status status-<?= $data['status'] ?>"><?= $status_label ?></div>
                </div>

                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">Transaction ID</span>
                        <span class="info-value code-badge"><?= htmlspecialchars($data['id']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Type</span>
                        <span class="info-value"><?= htmlspecialchars($data['transfer_type']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date & Time</span>
                        <span class="info-value"><?= date('d M Y, g:i A', strtotime($data['date_transfer'])) ?></span>
                    </div>

                    <?php
                    $fee = (float) ($data['fee'] ?? 0);
                    $fee_payer = '';
                    $show_fee_row = false;
                    $display_fee = $data['status'] == 0 ? 0 : $fee;

                    if ($fee > 0) {
                        $selfFeeBear = (int) ($data['selfFeeBear'] ?? 0);

                        if ($data['transfer_type'] == 'Withdraw') {
                            $fee_payer = 'You (Deducted from amount)';
                            $show_fee_row = true;
                        } else {
                            // For Transfers
                            $show_fee_row = true;
                            if ($is_sender) {
                                $fee_payer = ($selfFeeBear === 1) ? 'You (Paid)' : 'Recipient (Paid from amount)';
                            } else {
                                if ($selfFeeBear === 1) {
                                    $fee_payer = 'Sender paid the fee';
                                    // We keep display_fee as the actual fee so the recipient sees how much was paid
                                } else {
                                    $fee_payer = 'You (Paid from amount)';
                                }
                            }
                        }
                    }
                    ?>

                    <?php if ($show_fee_row && in_array($data['transfer_type'], ['Transfer', 'Withdraw'])): ?>
                        <?php if (!$is_sender && $data['transfer_type'] == 'Transfer'): ?>
                            <div class="info-row">
                                <span class="info-label">Total Money Transferred</span>
                                <span class="info-value"><?= number_format($data['money'], 0, ',', '.') ?> ₫</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($is_sender && $data['transfer_type'] == 'Transfer'): ?>
                            <div class="info-row">
                                <span class="info-label">Transfer Amount</span>
                                <span class="info-value"><?= number_format($data['money'], 0, ',', '.') ?> ₫</span>
                            </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">Transaction Fee (5%)</span>
                            <span class="info-value"
                                style="color: <?= $display_fee > 0 ? '#ef4444' : 'var(--text-main)' ?>;">
                                <?= number_format($display_fee, 0, ',', '.') ?> ₫<br>
                                <span
                                    style="font-size: 11px; font-weight: normal; color: var(--text-muted);"><?= $display_fee == 0 ? $fee_payer : 'Paid by: ' . $fee_payer ?></span>
                            </span>
                        </div>

                        <?php if (!$is_sender && $data['transfer_type'] == 'Transfer'): ?>
                            <div class="info-row">
                                <span class="info-label">Total Received</span>
                                <span class="info-value" style="color: #10b981; font-weight: 700;">
                                    <?= number_format($data['status'] == 0 ? 0 : (isset($data['selfFeeBear']) && $data['selfFeeBear'] == 1 ? $data['money'] : $data['money'] - $data['fee']), 0, ',', '.') ?>
                                    ₫
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if ($is_sender && $data['transfer_type'] == 'Transfer'): ?>
                            <div class="info-row">
                                <span class="info-label">Recipient Received</span>
                                <span class="info-value" style="color: #10b981; font-weight: 700;">
                                    <?= number_format($data['status'] == 0 ? 0 : (isset($data['selfFeeBear']) && $data['selfFeeBear'] == 1 ? $data['money'] : $data['money'] - $data['fee']), 0, ',', '.') ?>
                                    ₫
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Total Deducted</span>
                                <span class="info-value" style="color: #ef4444; font-weight: 700;">
                                    <?= $data['status'] == 0 ? '' : '-' ?>        <?= number_format($data['status'] == 0 ? 0 : (isset($data['selfFeeBear']) && $data['selfFeeBear'] == 1 ? $data['money'] + $data['fee'] : $data['money']), 0, ',', '.') ?>
                                    ₫
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($data['transfer_type'] == 'Transfer'): ?>
                        <div class="info-row">
                            <span class="info-label"><?= $is_sender ? 'Recipient' : 'Sender' ?></span>
                            <span class="info-value">
                                <?= htmlspecialchars($otherUser['name'] ?? 'Unknown User') ?><br>
                                <span
                                    style="font-size: 12px; font-weight: normal; color: var(--text-muted);"><?= htmlspecialchars($otherUser['phonenum'] ?? '') ?></span>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($data['card_num'])): ?>
                        <div class="info-row">
                            <span class="info-label">Account / Card</span>
                            <span class="info-value"><?= htmlspecialchars($data['card_num']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($data['note'])): ?>
                        <div class="info-row" style="flex-direction: column; align-items: flex-start; gap: 8px;">
                            <span class="info-label">Message / Note</span>
                            <div class="detail-note">
                                <?= nl2br(htmlspecialchars($data['note'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($phonecards)): ?>
                        <div class="info-row"
                            style="flex-direction: column; align-items: flex-start; gap: 8px; margin-top: 10px;">
                            <span class="info-label">Purchased Card Codes</span>
                            <?php foreach ($phonecards as $card): ?>
                                <div class="scratch-card" style="width: 100%;">
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-main);">
                                            <?= htmlspecialchars($card['carrier']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted);">
                                            <?= number_format($card['denomination'], 0, ',', '.') ?> ₫</div>
                                    </div>
                                    <div class="code-badge"
                                        style="font-size: 16px; letter-spacing: 1px; color: var(--accent-blue); background: white;">
                                        <?= htmlspecialchars($card['code']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="Transactions.php" class="btn"
                    style="width: 100%; margin-top: 32px; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); font-weight: 600; padding: 12px; border-radius: 12px; text-decoration: none; display: block; text-align: center;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i> Back to History
                </a>
            </div>
        </main>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script src="../assets/js/home.js"></script>
</body>

</html>