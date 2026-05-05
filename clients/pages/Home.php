<?php
require_once("../modules/db_connection.php");
require_once("../modules/usertype.php");

$usertype = usertype();//3 == admin, 2 = Reque st additional information, -1 = first login 



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
            <a href="home.php" class="nav-link active"><i class="fa-solid fa-border-all"></i> Dashboard</a>
            <a href="profile.php" class="nav-link"><i class="fa-regular fa-user"></i> Accounts</a>
            <a href="transfer.php" class="nav-link"><i class="fa-solid fa-money-bill-transfer"></i> Transfer money</a>
            <a href="withdraw.php" class="nav-link"><i class="fa-solid fa-arrow-up-from-bracket"></i> Withdraw</a>
            <a href="deposit.php" class="nav-link"><i class="fa-solid fa-wallet fa-arrow-down-to-bracket"></i> Deposit
                money</a>
            <a href="transaction_history.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> Transaction
                history</a>
            <a href="buy_card.php" class="nav-link"><i class="fa-solid fa-mobile-screen-button"></i> Buy phone card</a>
            <a href="settings.php" class="nav-link"><i class="fa-solid fa-gear"></i> Settings</a>
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
            <button class="theme-toggle"
                style="position: relative; top: 0; right: 0; border: 1px solid var(--border-color);">
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>
        <div class="header-actions">
            <div class="month-selector">
                <i class="fa-regular fa-calendar"></i> This Month
            </div>
            <div class="btn-group">
                <button class="btn btn-outline"><i class="fa-solid fa-grip"></i> Manage Widgets</button>
                <button class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add new Widget</button>
            </div>
        </div>
        <div class="mobile-services-grid">
            <a href="deposit.php" class="service-item">
                <div class="icon-box"><i class="fa-solid fa-arrow-down"></i></div>
                <span>Deposit</span>
            </a>
            <a href="withdraw.php" class="service-item">
                <div class="icon-box"><i class="fa-solid fa-arrow-up"></i></div>
                <span>Withdraw</span>
            </a>
            <a href="transfer.php" class="service-item">
                <div class="icon-box"><i class="fa-solid fa-money-bill-transfer"></i></div>
                <span>Transfer</span>
            </a>
            <a href="buy_card.php" class="service-item">
                <div class="icon-box"><i class="fa-solid fa-mobile-screen"></i></div>
                <span>Phone Card</span>
            </a>
        </div>
        <div class="dashboard-grid">
            <div class="widget transfer-widget">
                <div>
                    <div class="widget-header" style="margin-bottom: 10px;">
                        <span class="widget-title quick-action-badge">Quick Action</span>
                    </div>
                    <h3 class="transfer-title">Need to send money<br>urgently?</h3>
                    <p class="transfer-desc">Transfer securely to other users.</p>
                </div>
                <a href="transfer.php" class="transfer-btn-large">
                    Transfer Money Now <i class="fa-solid fa-arrow-right" style="margin-left: 5px;"></i>
                </a>
            </div>
            <div class="widget">
                <div class="widget-header">
                    <span class="widget-title">Balance Overview</span>
                    <div class="widget-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
                </div>
                <div class="balance-value">
                    <?= $money ?> ₫ <span class="badge-up"><i class="fa-solid fa-arrow-up"></i> 12%</span>
                </div>
                <div class="card-number-badge">
                    <i class="fa-regular fa-credit-card"></i> Card ending in •••• <?= substr($card_num, -4) ?>
                </div>
                <div class="chart-wrapper">
                    <canvas id="balanceChart"></canvas>
                </div>
            </div>

            <div class="widget">
                <div class="widget-header">
                    <span class="widget-title">Earnings</span>
                    <div class="widget-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
                </div>
                <div class="balance-value">
                    <?= $money ?> ₫ <span class="badge-up"><i class="fa-solid fa-arrow-up"></i> 7%</span>
                </div>
                <div class="doughnut-wrapper">
                    <canvas id="earningsChart"></canvas>
                    <div
                        style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -10%); text-align: center;">
                        <div style="font-size: 24px; font-weight: 700;">58%</div>
                    </div>
                </div>
            </div>

            <div class="widget transactions-widget">
                <div class="widget-header">
                    <span class="widget-title">Transactions</span>
                    <div style="display: flex; gap: 8px;">
                        <div class="widget-icon"><i class="fa-solid fa-filter"></i></div>
                        <a href="transaction_history.php" class="widget-icon" style="text-decoration:none;"><i
                                class="fa-solid fa-arrow-up-right-from-square"></i></a>
                    </div>
                </div>

                <div class="tx-list">
                    <div class="tx-item">
                        <div class="tx-left">
                            <div class="tx-icon-img"><i class="fa-brands fa-playstation"
                                    style="color:#003791; font-size: 20px;"></i></div>
                            <div class="tx-name">PlayStation</div>
                        </div>
                        <div class="tx-card"><i class="fa-brands fa-cc-mastercard" style="color:#ff5f00;"></i> ••••
                            <?= substr($card_num, -4) ?>
                        </div>
                        <div class="tx-date">31 Mar, 3:20 PM</div>
                        <div class="tx-amount">-19.99 ₫</div>
                    </div>

                    <div class="tx-item">
                        <div class="tx-left">
                            <div class="tx-icon-img"><i class="fa-solid fa-n"
                                    style="color:#E50914; font-size: 20px;"></i></div>
                            <div class="tx-name">Netflix</div>
                        </div>
                        <div class="tx-card"><i class="fa-brands fa-cc-mastercard" style="color:#ff5f00;"></i> ••••
                            <?= substr($card_num, -4) ?>
                        </div>
                        <div class="tx-date">29 Mar, 5:11 PM</div>
                        <div class="tx-amount">-30.00 ₫</div>
                    </div>

                    <div class="tx-item">
                        <div class="tx-left">
                            <div class="tx-icon-img" style="background:#3b82f6; color:#fff;">TC</div>
                            <div class="tx-name">Tommy C.</div>
                        </div>
                        <div class="tx-card"><i class="fa-brands fa-cc-mastercard" style="color:#ff5f00;"></i> ••••
                            <?= substr($card_num, -4) ?>
                        </div>
                        <div class="tx-date">27 Mar, 2:31 AM</div>
                        <div class="tx-amount pos">+27.00 ₫</div>
                    </div>
                </div>
            </div>

            <div class="widget">
                <div class="widget-header">
                    <span class="widget-title">Spending</span>
                    <div class="widget-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
                </div>
                <div class="balance-value" style="font-size: 24px;">
                    100.813 ₫ <span class="badge-up"
                        style="color: var(--danger); background: rgba(239, 68, 68, 0.1);"><i
                            class="fa-solid fa-arrow-down"></i> 2%</span>
                </div>
                <div class="chart-wrapper" style="height: 140px; margin-top: 20px;">
                    <canvas id="spendingChart"></canvas>
                </div>
            </div>

        </div>
    </main>
</div>
<div class="mobile-bottom-nav">
    <a href="home.php" class="nav-item active">
        <i class="fa-solid fa-house"></i>
        <span>Home</span>
    </a>
    <a href="transaction_history.php" class="nav-item">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>History</span>
    </a>
    <a href="scan.php" class="nav-item scan-btn">
        <div class="scan-circle">
            <i class="fa-solid fa-qrcode"></i>
        </div>
        <span>Scan QR</span>
    </a>
    <a href="transfer.php" class="nav-item">
        <i class="fa-solid fa-arrow-right-arrow-left"></i>
        <span>Transfer</span>
    </a>
    <a href="profile.php" class="nav-item">
        <i class="fa-regular fa-user"></i>
        <span>Profile</span>
    </a>
</div>

<script>
    // Theme Toggle
    const themeToggleBtns = document.querySelectorAll('.theme-toggle');
    const body = document.body;

    themeToggleBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Ngăn chặn nổi bọt sự kiện nếu cần
            e.preventDefault();

            body.classList.toggle('light-theme');

            // Đổi icon mặt trăng/mặt trời cho TẤT CẢ các nút toggle trên trang
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

    Chart.defaults.color = '#8b92a5';
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

    // Balance Chart
    const ctxBalance = document.getElementById('balanceChart').getContext('2d');
    let gradientBlue = ctxBalance.createLinearGradient(0, 0, 0, 150);
    gradientBlue.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
    gradientBlue.addColorStop(1, 'rgba(59, 130, 246, 0)');

    new Chart(ctxBalance, {
        type: 'line',
        data: {
            labels: ['15', '16', '17', '18', '19', '20'],
            datasets: [{
                data: [100, 150, 130, 200, 180, 250],
                borderColor: '#3b82f6',
                borderWidth: 3,
                backgroundColor: gradientBlue,
                fill: true,
                tension: 0.4,
                pointRadius: 0
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { display: false }, y: { display: false } }
        }
    });

    // Earnings Chart
    new Chart(document.getElementById('earningsChart'), {
        type: 'doughnut',
        data: { datasets: [{ data: [58, 42], backgroundColor: ['#3b82f6', '#2a2f3e'], borderWidth: 0, cutout: '80%', borderRadius: 20 }] },
        options: { responsive: true, maintainAspectRatio: false, rotation: -90, circumference: 180, plugins: { tooltip: { enabled: false } } }
    });

    // Spending Chart
    let gradBar = document.getElementById('spendingChart').getContext('2d').createLinearGradient(0, 0, 0, 150);
    gradBar.addColorStop(0, '#3b82f6'); gradBar.addColorStop(1, 'rgba(59, 130, 246, 0.2)');
    new Chart(document.getElementById('spendingChart'), {
        type: 'bar',
        data: { labels: ['C', 'G', 'P', 'B'], datasets: [{ data: [34, 16, 8, 6], backgroundColor: gradBar, borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } } }
    });
</script>

<?php include '../src/footer.php'; ?>