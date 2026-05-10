<?php
require_once("../modules/db_connection.php");
require_once("../modules/usertype.php");
require_once("../modules/formatMoney.php");
require_once("../modules/sendOTP.php");
require_once("../modules/generateCode.php");

$usertype = usertype();
$error = checkuser((int) $usertype);
if (!empty($error)) {
    $_SESSION['error'] = $error;
    redirectHome();
}

$step = (int) ($_GET['step'] ?? 1);

// Persist Step 2 if OTP is still active and not expired
if ($step == 1 && !empty($_SESSION['otp']) && !empty($_SESSION['otp_expire'])) {
    if (time() < $_SESSION['otp_expire']) {
        $step = 2;
    } else {
        // Clear expired OTP
        unset($_SESSION['otp'], $_SESSION['otp_expire'], $_SESSION['transfer']);
    }
}
$recipientPhone = '';
$amount = 0;
$note = '';
$selfFeeBear = 0;
$error = '';
$username = $_SESSION['name'] ?? 'User';
$current_date = strtoupper(date('l, F j'));

$con = connect_db();
$stmt = $con->prepare("SELECT phonenum, money, name FROM user WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($selfPhone, $selfamount, $selfName);
$stmt->fetch();
$stmt->close();

// Cancel from step 1 or step 2
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['cancel'])) {
    unset($_SESSION['otp'], $_SESSION['otp_expire'], $_SESSION['transfer']);
    header('Location: Transfer.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $recipientPhone = trim($_POST['recipientPhone'] ?? '');
    $amount_str = str_replace(['.', ','], '', $_POST['amount'] ?? '0');
    $amount = floatval($amount_str);
    $note = trim($_POST['note'] ?? '');
    $selfFeeBear = (int) ($_POST['selfFeeBear'] ?? 0);

    if (empty($recipientPhone)) {
        $error = 'Please enter the recipient phone number';
    } else if ($amount < 2000) {
        $error = 'Minimum transfer amount is 2.000 VND';
    } else if ($recipientPhone == $selfPhone) {
        $error = 'You cannot transfer money to yourself';
    } else {
        // Check recipient
        $stmt = $con->prepare("SELECT name, verified, abnormal_login, locked_time FROM user WHERE phonenum = ?");
        $stmt->bind_param("s", $recipientPhone);
        $stmt->execute();
        $stmt->bind_result($recipientName, $recipientVerified, $abnormal_login, $locked_time);
        if (!$stmt->fetch() || $recipientVerified == 3 || $recipientVerified == 4 || $abnormal_login >= 6 || !empty($locked_time)) {
            $error = 'Recipient not found';
        } else {
            $stmt->close();
            $selfDeduct = ($selfFeeBear == 1) ? $amount * 1.05 : $amount;
            $recipientGet = ($selfFeeBear == 1) ? $amount : $amount * 0.95;

            if ($selfamount < $selfDeduct) {
                $error = 'Insufficient balance. You need ' . number_format($selfDeduct, 0, ',', '.') . ' ₫ but have ' . number_format($selfamount, 0, ',', '.') . ' ₫.';
            } else {
                $otp_str = str_shuffle('0123456789');
                $otp = substr($otp_str, 0, 6);
                $expire = time() + 60;
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_expire'] = $expire;
                $_SESSION['transfer'] = [
                    'amount' => $amount,
                    'selfFeeBear' => $selfFeeBear,
                    'note' => $note,
                    'recipientName' => $recipientName,
                    'selfName' => $selfName,
                    'recipientPhone' => $recipientPhone,
                    'selfPhone' => $selfPhone,
                    'recipientGet' => $recipientGet,
                    'selfDeduct' => $selfDeduct
                ];

                if (!sendOTPEmail($_SESSION['email'], $selfName, $otp)) {
                    $error = 'Failed to send OTP email, please try again later';
                } else {
                    header('Location: Transfer.php');
                    exit();
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2 && isset($_POST['otp_in1'])) {
    $otp_in = ($_POST['otp_in1'] ?? '') . ($_POST['otp_in2'] ?? '') . ($_POST['otp_in3'] ?? '') .
        ($_POST['otp_in4'] ?? '') . ($_POST['otp_in5'] ?? '') . ($_POST['otp_in6'] ?? '');
    $saved_otp = $_SESSION['otp'] ?? '';
    $expire = $_SESSION['otp_expire'] ?? 0;

    if (empty($saved_otp) || time() > $expire) {
        $error = 'OTP expired or invalid. Please start over.';
        unset($_SESSION['otp'], $_SESSION['otp_expire']);
        $step = 1;
    } else if ($otp_in !== $saved_otp) {
        $error = 'Wrong OTP code';
    } else {
        unset($_SESSION['otp'], $_SESSION['otp_expire']);
        $t = $_SESSION['transfer'];
        $status = ($t['amount'] > 5000000) ? 2 : 1; // 1: Approved/Completed, 2: Pending
        $type = "Transfer";
        $now = date('Y-m-d H:i:s');
        $id = generateIdCode($t['selfPhone'], 1);

        $con->begin_transaction();
        try {
            $fee = $t['amount'] * 0.05;
            $stmt = $con->prepare("INSERT INTO `history` (`id`, `user_phone`, `receiver_phone`, `transfer_type`, `date_transfer`, `money`, `fee`, `note`, `status`, `selfFeeBear`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssddsii", $id, $t['selfPhone'], $t['recipientPhone'], $type, $now, $t['amount'], $fee, $t['note'], $status, $t['selfFeeBear']);
            $stmt->execute();

            // Always deduct from sender immediately to prevent double-spending
            $stmt = $con->prepare("UPDATE `user` SET `money` = `money` - ? WHERE `phonenum` = ?");
            $stmt->bind_param("ds", $t['selfDeduct'], $t['selfPhone']);
            $stmt->execute();

            if ($status == 1) {
                // Instant transfer: credit recipient immediately
                $stmt = $con->prepare("UPDATE `user` SET `money` = `money` + ? WHERE `phonenum` = ?");
                $stmt->bind_param("ds", $t['recipientGet'], $t['recipientPhone']);
                $stmt->execute();

                // Get recipient email for receipt
                $stmt = $con->prepare("SELECT `email`, `money` FROM `user` WHERE `phonenum` = ?");
                $stmt->bind_param("s", $t['recipientPhone']);
                $stmt->execute();
                $stmt->bind_result($rEmail, $rNewMoney);
                $stmt->fetch();
                $stmt->close();

                send_receipt(number_format($t['recipientGet'], 0, ',', '.') . ' ₫', number_format($rNewMoney, 0, ',', '.') . ' ₫', $rEmail, $t['selfName'], $t['recipientName'], $t['note']);
            }
            $con->commit();
            $_SESSION['money'] -= $t['selfDeduct']; // Always deduct from current session balance
            header('Location: Transfer.php?step=3');
            exit();
        } catch (Exception $e) {
            $con->rollback();
            $error = 'Transaction failed: ' . $e->getMessage();
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
    <title>Transfer Money - Antigravity Wallet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="../assets/css/transaction.css">
    <link rel="stylesheet" href="../assets/css/transfer.css">
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
                <a href="Transfer.php" class="nav-link active"><i class="fa-solid fa-money-bill-transfer"></i> Transfer
                    money</a>
                <a href="Withdraw.php" class="nav-link"><i class="fa-solid fa-arrow-up-from-bracket"></i> Withdraw</a>
                <a href="Deposit.php" class="nav-link"><i class="fa-solid fa-wallet fa-arrow-down-to-bracket"></i>
                    Deposit money</a>
                <a href="Transactions.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> Transaction
                    history</a>
                <a href="Buycard.php" class="nav-link"><i class="fa-solid fa-mobile-screen-button"></i> Buy phone
                    card</a>
                <a href="ChangePassword.php" class="nav-link"><i class="fa-solid fa-gear"></i> Change Password</a>
                <a href="../modules/logout.php" class="nav-link" style="color: var(--danger);"><i
                        class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="mobile-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="avatar" style="width: 40px; height: 40px; margin: 0; font-size: 16px;">
                        <?= strtoupper(substr($username, 0, 2)) ?></div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted);">Transfer</div>
                        <div style="font-size: 15px; font-weight: 700;"><?= $username ?></div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="theme-toggle"
                        style="border: 1px solid var(--border-color); background: transparent; color: var(--text-main);"><i
                            class="fa-solid fa-moon"></i></button>
                    <button class="sidebar-toggle-btn" id="sidebarToggleBtn"><i class="fa-solid fa-bars"></i></button>
                </div>
            </div>

            <div class="transfer-container">
                <?php if ($step == 1): ?>
                    <div class="header-actions" style="margin-bottom: 32px;">
                        <div class="header-welcome">
                            <div class="date-text"><?= $current_date ?></div>
                            <h1 style="font-size: 28px; font-weight: 800; color: var(--text-main); margin: 0;">Transfer
                                Money</h1>
                        </div>
                    </div>

                    <div class="alert alert-danger" id="transferError"
                        style="border-radius: 12px; margin-bottom: 24px; <?= $error ? 'visibility:visible;' : 'visibility:hidden;' ?>">
                        <i class="fa-solid fa-circle-exclamation" style="margin-right: 8px;"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>

                    <form method="POST" id="transferForm">
                        <div class="widget" style="padding: 32px; border-radius: 24px;">
                            <div class="form-group" style="margin-bottom: 24px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 700;">Recipient Phone
                                    Number</label>
                                <input type="text" name="recipientPhone" id="recipientPhone"
                                    placeholder="Enter recipient phone number" class="form-control"
                                    style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);"
                                    value="<?= htmlspecialchars($recipientPhone) ?>" required>
                                <div id="recipientBadge" style="margin-top: 8px; font-size: 13px;"></div>
                            </div>

                            <div class="form-group" style="margin-bottom: 24px;">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <label style="font-weight: 700;">Amount (VND)</label>
                                    <span style="font-size: 13px; color: var(--accent-blue); font-weight: 600;">
                                        Balance: <?= number_format($selfamount, 0, ',', '.') ?> ₫
                                    </span>
                                </div>
                                <input type="text" name="amount" id="amountInput" placeholder="Minimum 2.000"
                                    class="form-control"
                                    style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);"
                                    value="<?= $amount > 0 ? number_format($amount, 0, ',', '.') : '' ?>" required>
                            </div>

                            <label style="display: block; margin-bottom: 12px; font-weight: 700;">Who pays the 5%
                                fee?</label>
                            <div class="fee-toggle">
                                <label class="fee-option">
                                    <input type="radio" name="selfFeeBear" value="1" <?= $selfFeeBear == 1 ? 'checked' : '' ?>
                                        required>
                                    <div class="fee-card">
                                        <div style="font-weight: 700; color: var(--text-main);">I pay fee</div>
                                        <div style="font-size: 12px; color: var(--text-muted);">Recipient gets full</div>
                                    </div>
                                </label>
                                <label class="fee-option">
                                    <input type="radio" name="selfFeeBear" value="0" <?= $selfFeeBear == 0 ? 'checked' : '' ?>>
                                    <div class="fee-card">
                                        <div style="font-weight: 700; color: var(--text-main);">Recipient pays</div>
                                        <div style="font-size: 12px; color: var(--text-muted);">They get 95%</div>
                                    </div>
                                </label>
                            </div>

                            <div id="feePreview"
                                style="background: rgba(255,255,255,0.03); border-radius: 16px; padding: 20px; margin-bottom: 24px;">
                                <!-- JS populated -->
                            </div>

                            <div class="form-group" style="margin-bottom: 24px;">
                                <label
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-weight: 700;">
                                    <span>Note (Optional)</span>
                                    <span id="noteCount"
                                        style="font-size: 12px; font-weight: normal; color: var(--text-muted);">0/50</span>
                                </label>
                                <textarea name="note" id="noteInput" rows="2" maxlength="50" class="form-control"
                                    style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);"><?= htmlspecialchars($note) ?></textarea>
                            </div>

                            <button type="submit" class="btn"
                                style="width: 100%; padding: 16px; border-radius: 16px; background: var(--accent-blue); color: white; border: none; font-weight: 700; font-size: 18px; cursor: pointer;">
                                Continue
                            </button>
                        </div>
                    </form>

                <?php elseif ($step == 2): ?>
                    <div class="widget" style="padding: 40px; border-radius: 32px; text-align: center;">
                        <div class="success-icon"
                            style="background: var(--accent-blue)10; color: var(--accent-blue); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 24px;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <h2 style="font-weight: 800; color: var(--text-main);">Security Verification</h2>
                        <p style="color: var(--text-muted); margin-bottom: 24px;">We've sent a 6-digit OTP to your email.
                        </p>

                        <?php $t = $_SESSION['transfer'] ?? []; ?>
                        <?php if (!empty($t)): ?>
                            <div class="summary-card"
                                style="margin-bottom: 32px; text-align: left; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); padding: 16px; border-radius: 16px;">
                                <div class="summary-row" style="margin-bottom: 8px;"><span style="color: var(--text-muted);">To:
                                    </span><span
                                        style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($t['recipientName'] ?? '') ?></span>
                                </div>
                                <div class="summary-row" style="margin-bottom: 8px;"><span
                                        style="color: var(--text-muted);">Phone:
                                    </span><span><?= htmlspecialchars($t['recipientPhone'] ?? '') ?></span></div>
                                <div class="summary-row" style="margin-bottom: 8px;"><span
                                        style="color: var(--text-muted);">Amount: </span><span
                                        style="color: var(--accent-blue); font-weight: 600;"><?= number_format($t['amount'] ?? 0, 0, ',', '.') ?>
                                        ₫</span></div>
                                <div
                                    style="border-top: 1px dashed var(--border-color); padding-top: 8px; margin-top: 8px; display: flex; justify-content: space-between; font-weight: 700;">
                                    <span style="color: var(--text-muted);">Total Deducted: </span><span
                                        style="color: var(--danger);"><?= number_format($t['selfDeduct'] ?? 0, 0, ',', '.') ?>
                                        ₫</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" style="margin-bottom: 24px; border-radius: 12px; text-align: left;">
                                <i class="fa-solid fa-circle-exclamation" style="margin-right: 8px;"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="otpForm">
                            <div class="otp-inputs">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <input type="text" name="otp_in<?= $i ?>" maxlength="1" required
                                        autofocus="<?= $i == 1 ? 'true' : 'false' ?>">
                                <?php endfor; ?>
                            </div>
                            <button type="submit" class="btn"
                                style="width: 100%; padding: 16px; border-radius: 16px; background: var(--accent-blue); color: white; border: none; font-weight: 700; font-size: 18px; cursor: pointer; margin-bottom: 16px;">
                                Verify & Transfer
                            </button>

                            <div style="margin-bottom: 24px; font-size: 14px; color: var(--text-muted);">
                                Code expires in: <span id="otp-timer"
                                    style="font-weight: 700; color: var(--accent-blue);">01:00</span>
                            </div>

                            <a href="Transfer.php?cancel=2"
                                style="color: var(--text-muted); text-decoration: none; font-size: 14px;">Cancel
                                transaction</a>
                        </form>
                    </div>

                <?php elseif ($step == 3): ?>
                    <?php $t = $_SESSION['transfer'] ?? [];
                    $pending = ($t['amount'] > 5000000); ?>
                    <div class="widget" style="padding: 40px; border-radius: 32px; text-align: center;">
                        <div class="success-icon"
                            style="background: <?= $pending ? '#f59e0b' : '#10b981' ?>20; color: <?= $pending ? '#f59e0b' : '#10b981' ?>; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 24px;">
                            <i class="fa-solid <?= $pending ? 'fa-clock' : 'fa-check' ?>"></i>
                        </div>
                        <h2 style="font-weight: 800; color: var(--text-main);">
                            <?= $pending ? 'Pending Approval' : 'Transfer Successful!' ?></h2>
                        <p style="color: var(--text-muted); margin-bottom: 32px;">
                            <?= $pending ? 'Transfers over 5,000,000 VND require admin approval.' : 'Your money has been sent successfully.' ?>
                        </p>

                        <div class="summary-card" style="margin-bottom: 32px; text-align: left;">
                            <div class="summary-row"><span style="color: var(--text-muted);">To:
                                </span><span><?= htmlspecialchars($t['recipientName'] ?? '') ?></span></div>
                            <div class="summary-row"><span style="color: var(--text-muted);">Amount:
                                </span><span><?= number_format($t['amount'] ?? 0, 0, ',', '.') ?> ₫</span></div>
                            <div class="summary-total"><span>Total Deducted:
                                </span><span><?= number_format($t['selfDeduct'] ?? 0, 0, ',', '.') ?> ₫</span></div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <a href="Transfer.php" class="btn"
                                style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main); text-decoration: none; text-align: center; padding: 12px; border-radius: 12px; font-weight: 600;">New
                                Transfer</a>
                            <a href="Transactions.php" class="btn"
                                style="background: var(--accent-blue); color: white; text-decoration: none; text-align: center; padding: 12px; border-radius: 12px; font-weight: 600;">View
                                History</a>
                        </div>
                    </div>
                    <?php unset($_SESSION['transfer']); ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div class="mobile-bottom-nav">
        <a href="Home.php" class="nav-item"><i class="fa-solid fa-house"></i><span>Home</span></a>
        <a href="Transactions.php" class="nav-item"><i
                class="fa-solid fa-clock-rotate-left"></i><span>History</span></a>
        <a href="Buycard.php" class="nav-item scan-btn">
            <div class="scan-circle"><i class="fa-solid fa-mobile-screen"></i></div><span>Phone Card</span>
        </a>
        <a href="Transfer.php" class="nav-item active"><i
                class="fa-solid fa-arrow-right-arrow-left"></i><span>Transfer</span></a>
        <a href="Profile.php" class="nav-item"><i class="fa-solid fa-user"></i><span>Profile</span></a>
    </div>

    <script>
        const remainingOtpTime = <?= isset($_SESSION['otp_expire']) ? max(0, $_SESSION['otp_expire'] - time()) : 0 ?>;
    </script>
    <script src="../assets/js/home.js"></script>
    <script src="../assets/js/transfer.js"></script>
</body>

</html>