<?php
require_once("../modules/db_connection.php");
require_once("../modules/usertype.php");
require_once("../modules/formatMoney.php");

$usertype = usertype();
if ($usertype != "1") {
    header('Location: Login.php');
    exit();
}
$error = checkuser($usertype);
if (!empty($error)) {
    $_SESSION['error'] = $error;
    header('Location: Login.php');
    exit();
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$filter = $_GET['type'] ?? '';

$con = connect_db();
$user_phone = '';
$user_money = 0;
$stmt = $con->prepare("SELECT phonenum, money FROM user WHERE email = ?");
$stmt->bind_param("s", $_SESSION["email"]);
$stmt->execute();
$stmt->bind_result($user_phone, $user_money);
$stmt->fetch();
$stmt->close();

$where = "(user_phone = ? OR (receiver_phone = ? AND status = 1))";
$params = [$user_phone, $user_phone];
$types = "ss";

if (!empty($filter)) {
    $where .= " AND transfer_type = ?";
    $params[] = $filter;
    $types .= "s";
}

$count_stmt = $con->prepare("SELECT COUNT(*) FROM history WHERE $where");
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($totalCount);
$count_stmt->fetch();
$count_stmt->close();

$totalPages = ceil($totalCount / $perPage);

$query = "SELECT * FROM history WHERE $where ORDER BY date_transfer DESC LIMIT ? OFFSET ?";
$params_final = array_merge($params, [$perPage, $offset]);
$types_final = $types . "ii";

$stmt = $con->prepare($query);
$stmt->bind_param($types_final, ...$params_final);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$con->close();

$current_date = strtoupper(date('l, F j'));
$username = $_SESSION['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - Antigravity Wallet</title>
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
                        <div style="font-size: 11px; color: var(--text-muted);">History</div>
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
                    <div
                        style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                        <h1 style="font-size: 28px; font-weight: 800; color: var(--text-main); margin: 0;">Transaction
                            History</h1>
                        <div
                            style="background: var(--accent-blue-transparent); padding: 8px 16px; border-radius: 12px; border: 1px solid var(--accent-blue-dim);">
                            <span
                                style="font-size: 13px; color: var(--text-muted); display: block; margin-bottom: 2px;">Available
                                Balance</span>
                            <span
                                style="font-size: 20px; font-weight: 700; color: var(--success);"><?= number_format($user_money, 0, ',', '.') ?>
                                ₫</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="filter-container">
                <a href="Transactions.php" class="filter-btn <?= empty($filter) ? 'active' : '' ?>">All Activity</a>
                <a href="?type=Deposit" class="filter-btn <?= $filter == 'Deposit' ? 'active' : '' ?>">Deposits</a>
                <a href="?type=Transfer" class="filter-btn <?= $filter == 'Transfer' ? 'active' : '' ?>">Transfers</a>
                <a href="?type=Withdraw" class="filter-btn <?= $filter == 'Withdraw' ? 'active' : '' ?>">Withdrawals</a>
                <a href="?type=Buy Card" class="filter-btn <?= $filter == 'Buy Card' ? 'active' : '' ?>">Service
                    Payments</a>
            </div>

            <div class="widget" style="padding: 0; overflow: hidden;">
                <div class="tx-list">
                    <?php if (empty($transactions)): ?>
                        <div style="text-align:center; padding: 60px 20px; color: var(--text-muted);">
                            <i class="fa-solid fa-folder-open"
                                style="font-size: 40px; margin-bottom: 16px; opacity: 0.3;"></i>
                            <p>No transactions found in this period.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx):
                            $is_sender = ($tx['user_phone'] == $user_phone);
                            $is_pos = ($tx['transfer_type'] == 'Deposit' || (!$is_sender && $tx['transfer_type'] == 'Transfer'));
                            $icon_class = $is_pos ? 'fa-arrow-down' : 'fa-arrow-up';
                            $icon_color = $is_pos ? '#10b981' : '#ef4444';
                            $amount_prefix = $is_pos ? '+' : '-';

                            // Unified Status Mapping (0: Cancelled, 1: Approved, 2: Pending)
                            $display_status = $tx['status'];
                            $status_label = ['Declined', 'Approved', 'Pending'][$display_status] ?? 'Unknown';
                            ?>
                            <div class="tx-item" onclick="window.location.href='Transaction_detail.php?id=<?= $tx['id'] ?>'">
                                <div class="tx-top-row">
                                    <div class="tx-left">
                                        <div class="tx-icon-img"
                                            style="background: <?= $icon_color ?>20; color: <?= $icon_color ?>;">
                                            <i class="fa-solid <?= $icon_class ?>"></i>
                                        </div>
                                        <div class="tx-info">
                                            <div class="tx-name"><?= htmlspecialchars($tx['transfer_type']) ?></div>
                                        </div>
                                    </div>
                                    <div class="tx-amount <?= $is_pos ? 'pos' : '' ?>" style="color: <?= $icon_color ?>;">
                                        <?php
                                        $display_money = $tx['money'];
                                        if ($tx['transfer_type'] == 'Transfer') {
                                            if (!$is_sender) {
                                                if (isset($tx['selfFeeBear']) && $tx['selfFeeBear'] == 0) {
                                                    $display_money = $tx['money'] - $tx['fee'];
                                                }
                                            } else {
                                                if (isset($tx['selfFeeBear']) && $tx['selfFeeBear'] == 1) {
                                                    $display_money = $tx['money'] + $tx['fee'];
                                                }
                                            }
                                        }
                                        ?>
                                        <?= $amount_prefix ?>        <?= number_format($display_money, 0, ',', '.') ?> ₫
                                    </div>
                                </div>

                                <div class="tx-item-details">
                                    <div class="tx-card">
                                        <?php if (!empty($tx['card_num'])): ?>
                                            <i class="fa-solid fa-credit-card"></i> <?= htmlspecialchars($tx['card_num']) ?>
                                        <?php elseif (!empty($tx['receiver_phone']) && $is_sender): ?>
                                            <i class="fa-solid fa-user"></i> <?= $tx['receiver_phone'] ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="tx-status">
                                        <span class="status-badge status-<?= $display_status ?>"><?= $status_label ?></span>
                                    </div>

                                    <div class="tx-date">
                                        <?= date('d M, Y', strtotime($tx['date_transfer'])) ?><br>
                                        <span class="tx-time"><?= date('g:i A', strtotime($tx['date_transfer'])) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?type=<?= $filter ?>&page=<?= $page - 1 ?>" class="page-link"><i
                                class="fa-solid fa-chevron-left"></i></a>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="?type=<?= $filter ?>&page=<?= $p ?>"
                            class="page-link <?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?type=<?= $filter ?>&page=<?= $page + 1 ?>" class="page-link"><i
                                class="fa-solid fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="mobile-bottom-nav">
        <a href="Home.php" class="nav-item">
            <i class="fa-solid fa-house"></i>
            <span>Home</span>
        </a>
        <a href="Transactions.php" class="nav-item active">
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
        <a href="Profile.php" class="nav-item">
            <i class="fa-solid fa-user"></i>
            <span>Profile</span>
        </a>
    </div>

    <script src="../assets/js/home.js"></script>
</body>

</html>