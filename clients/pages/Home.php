<?php
require_once("../modules/db_connection.php");
require_once("../modules/usertype.php");
// require_once ('../../vendor/Mobile_Detect.php');

$usertype = usertype();//3 == admin, 2 = Request additional information, -1 = first login 
if ($usertype != "1") {
    redirectHome();
}
$error = $_SESSION["error"] ?? '';
unset($_SESSION["error"]);
// $detect = new WP_Rocket_Mobile_Detect;

// $is_desktop = false;
// if (!$detect->isMobile() && !$detect->isTablet()) {
//     $is_desktop = true;
// }
$useremail_session = $_SESSION['email'] ?? '';
$con = connect_db();
$stmt = $con->prepare("SELECT `name`, `email`, `phonenum`, `birth`, `address`, `verified`, `card_num`, `money`, `CVV`, `monthly_goal` FROM `user` WHERE `email` = ?");
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
$_SESSION['money'] = $user_data['money'] ?? 0;
$_SESSION['CVV'] = $user_data['CVV'] ?? '';

// --- FETCH DATA FOR CHARTS ---
// 1. Last 3 Transactions
$recent_txs = [];
$tx_stmt = $con->prepare("SELECT `transfer_type`, `date_transfer`, `money`, `status`, `id`, `card_num`, `note` FROM `history` WHERE `user_phone` = ? ORDER BY `date_transfer` DESC LIMIT 3");
$tx_stmt->bind_param("s", $user_data['phonenum']);
$tx_stmt->execute();
$tx_res = $tx_stmt->get_result();
while ($row = $tx_res->fetch_assoc()) {
    $recent_txs[] = $row;
}
$tx_stmt->close();

// 2. Daily balance change for last 6 days (Line Chart)
$daily_data = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $display_date = date('d/m', strtotime("-$i days"));

    // Net = (Incoming) - (Outgoing)
    // Incoming: Deposit + Transfer (as recipient)
    // Outgoing: Transfer (as sender) + Withdraw + Buycard
    $q = $con->prepare("SELECT 
        (SELECT SUM(money) FROM history WHERE receiver_phone = ? AND DATE(date_transfer) = ? AND status = 1) as incoming_transfer,
        (SELECT SUM(money) FROM history WHERE user_phone = ? AND DATE(date_transfer) = ? AND status = 1 AND transfer_type = 'Deposit') as incoming_deposit,
        (SELECT SUM(money + fee) FROM history WHERE user_phone = ? AND DATE(date_transfer) = ? AND status = 1 AND transfer_type IN ('Transfer', 'Withdraw', 'Buy Card')) as outgoing
    ");
    $q->bind_param("ssssss", $user_data['phonenum'], $date, $user_data['phonenum'], $date, $user_data['phonenum'], $date);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $net = ((float) ($r['incoming_transfer'] ?? 0) + (float) ($r['incoming_deposit'] ?? 0)) - (float) ($r['outgoing'] ?? 0);
    $daily_data[$display_date] = $net;
    $q->close();
}

// 3. Earnings vs Spending for current month (Doughnut/Goal)
$month_start = date('Y-m-01');
$q = $con->prepare("SELECT 
    (SELECT SUM(money) FROM history WHERE (user_phone = ? AND transfer_type = 'Deposit' OR (receiver_phone = ? AND status = 1)) AND date_transfer >= ?) as earnings,
    (SELECT SUM(money + fee) FROM history WHERE user_phone = ? AND transfer_type IN ('Transfer', 'Withdraw', 'Buy Card') AND date_transfer >= ? AND status = 1) as spending
");
$q->bind_param("sssss", $user_data['phonenum'], $user_data['phonenum'], $month_start, $user_data['phonenum'], $month_start);
$q->execute();
$stats = $q->get_result()->fetch_assoc();
$month_earnings = (float) ($stats['earnings'] ?? 0);
$month_spending = (float) ($stats['spending'] ?? 0);
$q->close();

$monthly_goal = (float) ($user_data['monthly_goal'] ?: 5000000);
$goal_pct = $monthly_goal > 0 ? round(($month_spending / $monthly_goal) * 100) : 0;

// Handle Goal Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_goal'])) {
    $raw_goal = $_POST['monthly_goal'] ?? '5000000';
    $new_goal = (float) str_replace('.', '', $raw_goal); // Remove dots for DB
    $stmt = $con->prepare("UPDATE `user` SET `monthly_goal` = ? WHERE `email` = ?");
    $stmt->bind_param("ds", $new_goal, $useremail_session);
    $stmt->execute();
    $stmt->close();
    header("Location: Home.php");
    exit();
}

// 4. Growth percentages
$q = $con->prepare("SELECT 
    (SELECT SUM(CASE WHEN receiver_phone = ? THEN money WHEN user_phone = ? AND transfer_type = 'Deposit' THEN money ELSE -(money+fee) END) FROM history WHERE (user_phone = ? OR receiver_phone = ?) AND date_transfer < DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = 1) as prev_net
");
$q->bind_param("ssss", $user_data['phonenum'], $user_data['phonenum'], $user_data['phonenum'], $user_data['phonenum']);
$q->execute();
$prev_res = $q->get_result()->fetch_assoc();
$prev_balance = (float) ($prev_res['prev_net'] ?? 0);
$q->close();

$current_balance = (float) $user_data['money'];
$balance_growth = $prev_balance > 0 ? round((($current_balance - $prev_balance) / $prev_balance) * 100) : 100;
$balance_growth_dir = $balance_growth >= 0 ? 'up' : 'down';
$balance_growth = abs($balance_growth);

// Earnings growth (This month vs last month)
$last_month_start = date('Y-m-01', strtotime('last month'));
$last_month_end = date('Y-m-t', strtotime('last month'));
$q = $con->prepare("SELECT SUM(money) as earnings FROM history WHERE (user_phone = ? AND transfer_type = 'Deposit' OR (receiver_phone = ? AND status = 1)) AND date_transfer BETWEEN ? AND ?");
$q->bind_param("ssss", $user_data['phonenum'], $user_data['phonenum'], $last_month_start, $last_month_end);
$q->execute();
$last_stats = $q->get_result()->fetch_assoc();
$last_month_earnings = (float) ($last_stats['earnings'] ?? 0);
$q->close();

$earnings_growth = $last_month_earnings > 0 ? round((($month_earnings - $last_month_earnings) / $last_month_earnings) * 100) : 100;
$earnings_growth_dir = $earnings_growth >= 0 ? 'up' : 'down';
$earnings_growth = abs($earnings_growth);

$con->close();

$useremail = htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8');
$username = htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8');
$userphone = htmlspecialchars($_SESSION['phonenum'], ENT_QUOTES, 'UTF-8');
$money = number_format($_SESSION['money'], 0, ',', '.');
$card_num = $_SESSION["card_num"];

$current_date = strtoupper(date('l, F j'));

include '../src/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="../assets/css/home.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            <a href="Home.php" class="nav-link active"><i class="fa-solid fa-border-all"></i> Dashboard</a>
            <a href="Profile.php" class="nav-link"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="Transfer.php" class="nav-link"><i class="fa-solid fa-money-bill-transfer"></i> Transfer money</a>
            <a href="Withdraw.php" class="nav-link"><i class="fa-solid fa-arrow-up-from-bracket"></i> Withdraw</a>
            <a href="Deposit.php" class="nav-link"><i class="fa-solid fa-wallet fa-arrow-down-to-bracket"></i> Deposit
                money</a>
            <a href="Transactions.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> Transaction
                history</a>
            <a href="Buycard.php" class="nav-link"><i class="fa-solid fa-mobile-screen-button"></i> Buy phone card</a>
            <a href="ChangePassword.php" class="nav-link"><i class="fa-solid fa-gear"></i> Change Password</a>
            <a href="../modules/logout.php" class="nav-link" style="color: var(--danger);"><i
                    class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="mobile-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="avatar" style="width: 40px; height: 40px; margin: 0; font-size: 16px;">
                    <?= strtoupper(substr($username, 0, 2)) ?>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-muted);">Welcome back,</div>
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
        <div class="header-actions">
            <div class="month-selector">
                <i class="fa-regular fa-calendar"></i> This Month
            </div>
            <div class="btn-group">
            </div>
        </div>
        <div class="mobile-services-grid">
            <a href="Profile.php" class="service-item">
                <div class="icon-box"><i class="fa-solid fa-user"></i></div>
                <span>Profile</span>
            </a>
            <a href="Deposit.php" class="service-item">
                <div class="icon-box"><i class="fa-solid fa-arrow-down"></i></div>
                <span>Deposit</span>
            </a>
            <a href="Withdraw.php" class="service-item">
                <div class="icon-box"><i class="fa-solid fa-arrow-up"></i></div>
                <span>Withdraw</span>
            </a>
            <a href="Transfer.php" class="service-item">
                <div class="icon-box"><i class="fa-solid fa-money-bill-transfer"></i></div>
                <span>Transfer</span>
            </a>
            <a href="Buycard.php" class="service-item">
                <div class="icon-box"><i class="fa-solid fa-mobile-screen"></i></div>
                <span>Phone Card</span>
            </a>
            <a href="ChangePassword.php" class="service-item">
                <div class="icon-box"><i class="fa-solid fa-key"></i></div>
                <span>Password</span>
            </a>
        </div>
        <div class="dashboard-grid">
            <!-- 1. Balance Overview (Spans 2) -->
            <div class="widget balance-widget">
                <div class="widget-header">
                    <span class="widget-title">Balance Overview</span>
                    <div class="widget-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
                </div>
                <div class="balance-value">
                    <?= $money ?> ₫ <span class="badge-<?= $balance_growth_dir ?>"><i
                            class="fa-solid fa-arrow-<?= $balance_growth_dir ?>"></i> <?= $balance_growth ?>%</span>
                </div>
                <div class="chart-wrapper">
                    <canvas id="balanceChart"></canvas>
                </div>
            </div>

            <!-- 2. Quick Action (Spans 1) -->
            <div class="widget transfer-widget">
                <div>
                    <div class="widget-header" style="margin-bottom: 10px;">
                        <span class="widget-title quick-action-badge">Quick Action</span>
                    </div>
                    <h3 class="transfer-title">Need to send money<br>urgently?</h3>
                    <p class="transfer-desc">Transfer securely to other users.</p>
                </div>
                <a href="Transfer.php" class="transfer-btn-large">
                    Transfer Now <i class="fa-solid fa-arrow-right" style="margin-left: 5px;"></i>
                </a>
            </div>

            <!-- 3. Transactions (Spans 2) -->
            <div class="widget transactions-widget">
                <div class="widget-header">
                    <span class="widget-title">Recent Activity</span>
                    <div style="display: flex; gap: 8px;">
                        <a href="Transactions.php" class="widget-icon" style="text-decoration:none;"><i
                                class="fa-solid fa-arrow-up-right-from-square"></i></a>
                    </div>
                </div>

                <div class="tx-list">
                    <?php if (empty($recent_txs)): ?>
                        <div style="text-align:center; padding: 20px; color: var(--text-muted);">No recent transactions
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_txs as $tx):
                            $is_pos = ($tx['transfer_type'] == 'Deposit');
                            $icon_class = $is_pos ? 'fa-arrow-down' : 'fa-arrow-up';
                            $icon_color = $is_pos ? '#10b981' : '#ef4444';
                            $amount_prefix = $is_pos ? '+' : '-';
                            ?>
                            <div class="tx-item">
                                <div class="tx-left" style="flex: 1.5;">
                                    <div class="tx-icon-img"
                                        style="background: <?= $icon_color ?>20; color: <?= $icon_color ?>;">
                                        <i class="fa-solid <?= $icon_class ?>"></i>
                                    </div>
                                    <div>
                                        <div class="tx-name"><?= htmlspecialchars($tx['transfer_type']) ?></div>
                                        <?php if (!empty($tx['note'])): ?>
                                            <div style="font-size: 13px; color: var(--text-muted); margin-top: 2px;">
                                                <?= htmlspecialchars($tx['note']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="tx-card" style="flex: 1;">
                                    <?php if (!empty($tx['card_num'])): ?>
                                        <i class="fa-solid fa-credit-card"></i><?= htmlspecialchars($tx['card_num']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="tx-date" style="flex: 1.2; text-align: center; padding: 0;">
                                    <?= date('d M, g:i A', strtotime($tx['date_transfer'])) ?></div>
                                <div class="tx-amount <?= $is_pos ? 'pos' : '' ?>"
                                    style="flex: 1.3; width: auto; white-space: nowrap;">
                                    <?= $amount_prefix ?>        <?= number_format($tx['money'], 0, ',', '.') ?> ₫
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 4. Monthly Goal (Spans 1) -->
            <div class="widget earnings-widget">
                <div class="widget-header">
                    <span class="widget-title">Spending Goal</span>
                    <div class="widget-icon" style="cursor: pointer;"
                        onclick="document.getElementById('goalModal').style.display='flex'"><i
                            class="fa-solid fa-pen-to-square"></i></div>
                </div>
                <div class="balance-value" style="font-size: 20px; margin-bottom: 15px;">
                    <?= $goal_pct ?>% <span style="font-size: 12px; color: var(--text-muted); font-weight: 400;">of
                        goal</span>
                </div>
                <div class="doughnut-wrapper">
                    <canvas id="earningsChart"></canvas>
                    <div
                        style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -10%); text-align: center;">
                        <div style="font-size: 24px; font-weight: 700;"><?= $goal_pct ?>%</div>
                    </div>
                </div>
                <div style="font-size: 11px; color: var(--text-muted); text-align: center; margin-top: 10px;">
                    Limit: <?= number_format($monthly_goal, 0, ',', '.') ?> ₫
                </div>
            </div>

            <!-- Goal Setting Modal (Simple) -->
            <div id="goalModal"
                style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
                <div
                    style="background:var(--bg-surface); padding:30px; border-radius:16px; width:90%; max-width:400px; border:1px solid var(--border-color);">
                    <h3 style="margin-top:0;">Set Monthly Spending Goal</h3>
                    <p style="font-size:14px; color:var(--text-muted); margin-bottom:20px;">Enter your target monthly
                        spending limit.</p>
                    <form method="POST">
                        <input type="text" name="monthly_goal" id="goalInput"
                            value="<?= number_format($monthly_goal, 0, ',', '.') ?>" onkeyup="formatCurrency(this)"
                            style="width:100%; padding:12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-dark); color:var(--text-main); margin-bottom:20px; font-size: 18px; font-weight: 600;">
                        <script>
                            function formatCurrency(input) {
                                let val = input.value.replace(/\D/g, "");
                                if (val) {
                                    input.value = parseInt(val).toLocaleString('de-DE'); // Use German locale for dots
                                } else {
                                    input.value = "";
                                }
                            }
                        </script>
                        <div style="display:flex; gap:10px;">
                            <button type="button" onclick="document.getElementById('goalModal').style.display='none'"
                                class="btn btn-outline" style="flex:1;">Cancel</button>
                            <button type="submit" name="update_goal" class="btn btn-primary" style="flex:1;">Save
                                Goal</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 5. Spending (Spans 3) -->
            <div class="widget spending-widget">
                <div class="widget-header">
                    <span class="widget-title">Spending Pattern</span>
                    <div class="widget-icon"><i class="fa-solid fa-chart-simple"></i></div>
                </div>
                <div class="balance-value" style="font-size: 24px;">
                    <?= number_format($month_spending, 0, ',', '.') ?> ₫ <span class="badge-down"
                        style="color: var(--danger); background: rgba(239, 68, 68, 0.1);"><i
                            class="fa-solid fa-arrow-down"></i> Spent this month</span>
                </div>
                <div class="chart-wrapper" style="height: 180px; margin-top: 20px;">
                    <canvas id="spendingChart"></canvas>
                </div>
            </div>
        </div>
    </main>
</div>
<div class="mobile-bottom-nav">
    <a href="Home.php" class="nav-item active">
        <i class="fa-solid fa-house"></i>
        <span>Home</span>
    </a>
    <a href="Transactions.php" class="nav-item">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>History</span>
    </a>
    <a href="Buycard.php" class="nav-item scan-btn">
        <div class="scan-circle">
            <i class="fa-solid fa-mobile-screen"></i>
        </div>
        <span>Phone Card</span>
    </a>
    <a href="Transfer.php" class="nav-item">
        <i class="fa-solid fa-arrow-right-arrow-left"></i>
        <span>Transfer</span>
    </a>
    <a href="ChangePassword.php" class="nav-item">
        <i class="fa-solid fa-key"></i>
        <span>Password</span>
    </a>
</div>
<?php if (!empty($error)) { ?>
    <script> alert(<?= json_encode($error) ?>)</script>
<?php } ?>
<script>

    // Pass PHP data to home.js
    window.chartData = {
        daily: <?= json_encode(array_values($daily_data)) ?>,
        labels: <?= json_encode(array_keys($daily_data)) ?>,
        earnings: <?= $monthly_goal ?>, // Use goal as the base for the doughnut
        spending: <?= $month_spending ?>,
        earningsPct: <?= $goal_pct ?>
    };
</script>
<script src="../assets/js/home.js"></script>
<?php
include '../src/footer.php';
?>