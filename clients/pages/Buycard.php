<?php
require_once("../modules/db_connection.php");
require_once("../modules/usertype.php");
require_once("../modules/formatMoney.php");
require_once("../modules/generateCode.php");

$usertype = usertype();
$error = checkuser((int)$usertype);
if(!empty($error)){
    $_SESSION['error'] = $error;
    redirectHome();
}

$error = '';
$success_data = null;
$username = $_SESSION['name'] ?? 'User';
$current_date = strtoupper(date('l, F j'));

// Use defined constants or defaults
$carriers_list = defined('CARRIERS') ? CARRIERS : ['Viettel'=>'11111','Mobifone'=>'22222','Vinaphone'=>'33333'];
$denoms_list = defined('CARD_DENOMINATIONS') ? CARD_DENOMINATIONS : [10000, 20000, 50000, 100000];

$con = connect_db();

// Get current balance
$user_phone = '';
$current_balance = 0;
$stmt = $con->prepare("SELECT phonenum, money FROM user WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($user_phone, $current_balance);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $carrier = $_POST['carrier'] ?? '';
    $denomination = (int)($_POST['denomination'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $note = trim($_POST['note'] ?? '');
    $fee = 0; // Current fee is 0

    if (!array_key_exists($carrier, $carriers_list)) {
        $error = 'Please select a valid carrier.';
    } elseif (!in_array($denomination, $denoms_list)) {
        $error = 'Please select a valid denomination.';
    } elseif ($quantity < 1 || $quantity > 5) {
        $error = 'You can buy between 1 and 5 cards at a time.';
    } else {
        $total_cost = $denomination * $quantity;
        
        if ($current_balance < $total_cost) {
            $error = 'Insufficient balance. You need ' . number_format($total_cost, 0, ',', '.') . ' ₫.';
        } else {
            // Start transaction
            $con->begin_transaction();
            try {
                $history_id = generateIdCode($user_phone, 4);
                $now = date('Y-m-d H:i:s');
                $status = 1; // Approved immediately
                $type = "Buy Card";

                // 1. Insert into history
                $fee = 0;
                $stmt = $con->prepare("INSERT INTO `history` (`id`, `user_phone`, `transfer_type`, `date_transfer`, `money`, `fee`, `note`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssddsi", $history_id, $user_phone, $type, $now, $total_cost, $fee, $note, $status);
                $stmt->execute();

                // 2. Generate and Insert card codes
                $codes = [];
                $carrier_code = $carriers_list[$carrier];
                $stmt_card = $con->prepare("INSERT INTO phonecard (id, code, carrier, denomination) VALUES (?, ?, ?, ?)");
                for ($i = 0; $i < $quantity; $i++) {
                    $card_code = generateCardCode($carrier_code);
                    $codes[] = $card_code;
                    $stmt_card->bind_param("sssd", $history_id, $card_code, $carrier, $denomination);
                    $stmt_card->execute();
                }

                // 3. Deduct balance
                $stmt_upd = $con->prepare("UPDATE user SET money = money - ? WHERE phonenum = ?");
                $stmt_upd->bind_param("ds", $total_cost, $user_phone);
                $stmt_upd->execute();

                $con->commit();

                // Success data for UI
                $success_data = [
                    'id' => $history_id,
                    'carrier' => $carrier,
                    'denomination' => $denomination,
                    'quantity' => $quantity,
                    'total' => $total_cost,
                    'fee' => $fee,
                    'codes' => $codes,
                    'new_balance' => $current_balance - $total_cost
                ];
                
                // Update session money
                $_SESSION['money'] = $current_balance - $total_cost;
                $current_balance = $_SESSION['money'];

            } catch (Exception $e) {
                $con->rollback();
                $error = 'Transaction failed: ' . $e->getMessage();
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
    <title>Buy Phone Card - Antigravity Wallet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="../assets/css/transaction.css">
    <link rel="stylesheet" href="../assets/css/Buycard.css">
</head>
<body>
<script>
    if (localStorage.getItem("theme") !== "dark") {
        document.body.classList.add("light-theme");
    }
</script>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
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
            <a href="Transfer.php" class="nav-link"><i class="fa-solid fa-money-bill-transfer"></i> Transfer money</a>
            <a href="Withdraw.php" class="nav-link"><i class="fa-solid fa-arrow-up-from-bracket"></i> Withdraw</a>
            <a href="Deposit.php" class="nav-link"><i class="fa-solid fa-wallet fa-arrow-down-to-bracket"></i> Deposit money</a>
            <a href="Transactions.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> Transaction history</a>
            <a href="Buycard.php" class="nav-link active"><i class="fa-solid fa-mobile-screen-button"></i> Buy phone card</a>
            <a href="ChangePassword.php" class="nav-link"><i class="fa-solid fa-gear"></i> Change Password</a>
            <a href="../modules/logout.php" class="nav-link" style="color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="mobile-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="avatar" style="width: 40px; height: 40px; margin: 0; font-size: 16px;">
                    <?= strtoupper(substr($username, 0, 2)) ?>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-muted);">Buy Card</div>
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

        <div class="buycard-container">
            <?php if (!$success_data): ?>
                <div class="header-actions" style="margin-bottom: 32px;">
                    <div class="header-welcome">
                        <div class="date-text"><?= $current_date ?></div>
                        <h1 style="font-size: 28px; font-weight: 800; color: var(--text-main); margin: 0;">Buy Phone Card</h1>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger" style="margin-bottom: 24px; border-radius: 12px;">
                        <i class="fa-solid fa-circle-exclamation" style="margin-right: 8px;"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="buyCardForm">
                    <div class="widget" style="padding: 32px; border-radius: 24px;">
                        <label style="display: block; margin-bottom: 16px; font-weight: 700; font-size: 16px;">1. Select Carrier</label>
                        <div class="carrier-grid">
                            <?php foreach ($carriers_list as $name => $code): ?>
                                <label class="carrier-option">
                                    <input type="radio" name="carrier" value="<?= $name ?>" required>
                                    <div class="carrier-card">
                                        <div class="carrier-logo">
                                            <i class="fa-solid fa-tower-broadcast"></i>
                                        </div>
                                        <div style="font-weight: 700; color: var(--text-main);"><?= $name ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted);">Code: <?= $code ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <label style="display: block; margin-bottom: 16px; font-weight: 700; font-size: 16px;">2. Select Denomination</label>
                        <div class="denom-grid">
                            <?php foreach ($denoms_list as $d): ?>
                                <label class="denom-option">
                                    <input type="radio" name="denomination" value="<?= $d ?>" required>
                                    <div class="denom-card">
                                        <?= number_format($d, 0, ',', '.') ?> ₫
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 700;">Quantity</label>
                                <select name="quantity" class="form-control" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?> card<?= $i>1 ? 's' : '' ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 700;">Transaction Fee</label>
                                <div style="padding: 12px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-body); color: #10b981; font-weight: 700;">
                                    0 ₫
                                </div>
                            </div>
                        </div>

                        <label style="display: block; margin-bottom: 8px; font-weight: 700;">Note (Optional)</label>
                        <input type="text" name="note" placeholder="Message or reminder..." style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main); margin-bottom: 24px;">

                        <div class="summary-card">
                            <div class="summary-row">
                                <span style="color: var(--text-muted);">Current Balance</span>
                                <span><?= number_format($current_balance, 0, ',', '.') ?> ₫</span>
                            </div>
                            <div class="summary-total">
                                <span>Total Payable</span>
                                <span id="totalDisplay">0 ₫</span>
                            </div>
                        </div>

                        <button type="submit" class="btn" style="width: 100%; margin-top: 32px; background: var(--accent-blue); color: white; padding: 16px; border-radius: 16px; font-size: 18px; font-weight: 700; border: none; cursor: pointer;">
                            Confirm Purchase
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <!-- Success State -->
                <div class="widget" style="padding: 40px; border-radius: 32px; max-width: 600px; margin: 0 auto;">
                    <div class="success-animation">
                        <div class="success-icon">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <h2 style="font-weight: 800; color: var(--text-main);">Purchase Successful!</h2>
                        <p style="color: var(--text-muted);">Your card codes have been generated.</p>
                    </div>

                    <div style="margin-bottom: 32px;">
                        <label style="display: block; margin-bottom: 12px; font-weight: 700; font-size: 14px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">Card Codes</label>
                        <?php foreach ($success_data['codes'] as $idx => $code): ?>
                            <div class="card-code-display">
                                <div>
                                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Card <?= $idx + 1 ?> (<?= $success_data['carrier'] ?>)</div>
                                    <div class="code-text" id="code-<?= $idx ?>"><?= $code ?></div>
                                </div>
                                <button class="copy-btn" onclick="copyCode('<?= $code ?>', this)">Copy</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-card" style="margin-bottom: 32px;">
                        <div class="summary-row">
                            <span style="color: var(--text-muted);">Denomination</span>
                            <span><?= number_format($success_data['denomination'], 0, ',', '.') ?> ₫ x <?= $success_data['quantity'] ?></span>
                        </div>
                        <div class="summary-row">
                            <span style="color: var(--text-muted);">Transaction ID</span>
                            <span class="code-badge"><?= $success_data['id'] ?></span>
                        </div>
                        <div class="summary-total">
                            <span>Total Paid</span>
                            <span><?= number_format($success_data['total'], 0, ',', '.') ?> ₫</span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <a href="Buycard.php" class="btn" style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); text-decoration: none; text-align: center; padding: 12px; border-radius: 12px; font-weight: 600;">Buy More</a>
                        <a href="Transactions.php" class="btn" style="background: var(--accent-blue); color: white; text-decoration: none; text-align: center; padding: 12px; border-radius: 12px; font-weight: 600;">View History</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<div class="mobile-bottom-nav">
    <a href="Home.php" class="nav-item">
        <i class="fa-solid fa-house"></i>
        <span>Home</span>
    </a>
    <a href="Transactions.php" class="nav-item">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>History</span>
    </a>
    <a href="Buycard.php" class="nav-item scan-btn active">
        <div class="scan-circle">
            <i class="fa-solid fa-mobile-screen"></i>
        </div>
        <span>Phone Card</span>
    </a>
    <a href="Transfer.php" class="nav-item">
        <i class="fa-solid fa-arrow-right-arrow-left"></i>
        <span>Transfer</span>
    </a>
    <a href="Profile.php" class="nav-item">
        <i class="fa-solid fa-user"></i>
        <span>Profile</span>
    </a>
</div>

<script src="../assets/js/home.js"></script>
<script src="../assets/js/Buycard.js"></script>
</body>
</html>
