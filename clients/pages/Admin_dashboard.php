<?php
require_once('../modules/adminLogic.php');
// require_once ('../../vendor/Mobile_Detect.php');


include '../src/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="user-profile-card">
            <button class="theme-toggle" id="themeToggleBtn">
                <i class="fa-solid fa-moon"></i>
            </button>
            <div class="avatar"><?= strtoupper(substr($username, 0, 2)) ?></div>
            <div class="date-text"><?= $current_date ?></div>
            <div class="welcome-text">Administrator<br><?= $username ?></div>
        </div>

        <nav class="nav-menu">
            <a href="Admin_dashboard.php" class="nav-link active"><i class="fa-solid fa-shield-halved"></i> Admin Control</a>
            <a href="ChangePassword.php" class="nav-link"><i class="fa-solid fa-gear"></i> Change Password</a>
        </nav>
    </aside>

    <main class="main-content" style="padding-top: 20px;">
        <div class="mobile-header" style="margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="avatar" style="width: 40px; height: 40px; margin: 0; font-size: 16px;">
                    <?= strtoupper(substr($username, 0, 2)) ?>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-muted);">Administrator</div>
                    <div style="font-size: 15px; font-weight: 700;"><?= $username ?></div>
                </div>
            </div>
            <button class="theme-toggle" style="position: relative; top: 0; right: 0; border: 1px solid var(--border-color);">
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>

        <div class="mobile-services-grid">
            <a href="ChangePassword.php" class="service-item" style="grid-column: span 4;">
                <div class="icon-box"><i class="fa-solid fa-key"></i></div>
                <span>Change Password</span>
            </a>
        </div>

        <h2 style="margin-bottom: 20px;">MeoMeo Management</h2>

        <form method="GET" action="../modules/adminLogic.php" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" name="search" placeholder="Search by phone number or email..." value="<?= htmlspecialchars($search_query) ?>" style="flex: 1; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <?php if ($search_query): ?>
                <a href="Admin_dashboard.php" class="btn btn-outline" style="text-decoration: none; padding: 12px; display: flex; align-items: center;">Clear</a>
            <?php endif; ?>
        </form>

        <div class="admin-tabs">
            <?php if ($search_query): ?>
                <div class="admin-tab <?= $active_tab === 'search' ? 'active' : '' ?>" onclick="switchTab('search')">Search Results (<?= count($search_results) ?>)</div>
            <?php endif; ?>
            <div class="admin-tab <?= $active_tab === 'pending' ? 'active' : '' ?>" onclick="switchTab('pending')">Pending (<?= count($pending_accounts) ?>)</div>
            <div class="admin-tab <?= $active_tab === 'active' ? 'active' : '' ?>" onclick="switchTab('active')">Active (<?= count($active_accounts) ?>)</div>
            <div class="admin-tab <?= $active_tab === 'disabled' ? 'active' : '' ?>" onclick="switchTab('disabled')">Disabled (<?= count($disabled_accounts) ?>)</div>
            <div class="admin-tab <?= $active_tab === 'locked' ? 'active' : '' ?>" onclick="switchTab('locked')">Locked (<?= count($locked_accounts) ?>)</div>
            <div class="admin-tab <?= $active_tab === 'tx' ? 'active' : '' ?>" onclick="switchTab('tx')">Pending Tx (<?= count($pending_tx) ?>)</div>
        </div>

        <?php if ($search_query): ?>
        <div id="list-search" class="admin-list <?= $active_tab === 'search' ? 'active' : '' ?>">
            <?php foreach($search_results as $u): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <strong><?= htmlspecialchars($u['name'] ?: 'Unknown') ?></strong>
                            <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($u['phonenum']) ?> | <?= htmlspecialchars($u['email']) ?></div>
                        </div>
                    </div>
                    <div>
                        <a href="?tab=search&search=<?= urlencode($search_query) ?>&details=<?= urlencode($u['phonenum']) ?>" class="btn btn-outline btn-sm">View Details</a>
                    </div>
                </div>
            <?php endforeach; if(empty($search_results)) echo "<p>No users found matching your search.</p>"; ?>
        </div>
        <?php endif; ?>

        <div id="list-pending" class="admin-list <?= $active_tab === 'pending' ? 'active' : '' ?>">
            <?php foreach($pending_accounts as $u): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <strong><?= htmlspecialchars($u['name'] ?: 'Unknown') ?></strong>
                            <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($u['phonenum']) ?></div>
                            <?php if ($u['verified'] == 2): ?>
                                <div style="margin-top: 4px;">
                                    <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px; background: rgba(251,146,60,0.15); color: var(--warning); border: 1px solid var(--warning);">
                                        <i class="fa-solid fa-circle-exclamation"></i> Needs Info
                                    </span>
                                </div>
                            <?php elseif ($u['verified'] == -1): ?>
                                <div style="margin-top: 4px;">
                                    <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px; background: rgba(99,102,241,0.15); color: #818cf8; border: 1px solid #818cf8;">
                                        <i class="fa-solid fa-user-plus"></i> New Account
                                    </span>
                                </div>
                            <?php else: ?>
                                <div style="margin-top: 4px;">
                                    <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px; background: rgba(234,179,8,0.15); color: #facc15; border: 1px solid #facc15;">
                                        <i class="fa-solid fa-id-card"></i> ID Submitted
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <a href="?tab=pending&details=<?= urlencode($u['phonenum']) ?>" class="btn btn-outline btn-sm">View Details</a>
                    </div>
                </div>
            <?php endforeach; if(empty($pending_accounts)) echo "<p>No pending accounts.</p>"; ?>
        </div>

        <div id="list-active" class="admin-list <?= $active_tab === 'active' ? 'active' : '' ?>">
            <?php foreach($active_accounts as $u): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <strong><?= htmlspecialchars($u['name']) ?></strong>
                            <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($u['phonenum']) ?></div>
                        </div>
                    </div>
                    <div>
                        <a href="?tab=active&details=<?= urlencode($u['phonenum']) ?>" class="btn btn-outline btn-sm">View Details</a>
                    </div>
                </div>
            <?php endforeach; if(empty($active_accounts)) echo "<p>No active accounts.</p>"; ?>
        </div>

        <div id="list-disabled" class="admin-list <?= $active_tab === 'disabled' ? 'active' : '' ?>">
            <?php foreach($disabled_accounts as $u): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <strong><?= htmlspecialchars($u['name']) ?></strong>
                            <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($u['phonenum']) ?></div>
                        </div>
                    </div>
                    <div>
                        <a href="?tab=disabled&details=<?= urlencode($u['phonenum']) ?>" class="btn btn-outline btn-sm">View Details</a>
                    </div>
                </div>
            <?php endforeach; if(empty($disabled_accounts)) echo "<p>No disabled accounts.</p>"; ?>
        </div>

        <div id="list-locked" class="admin-list <?= $active_tab === 'locked' ? 'active' : '' ?>">
            <?php foreach($locked_accounts as $u): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <strong><?= htmlspecialchars($u['name']) ?></strong>
                            <div style="font-size: 12px; color: var(--danger);"><i class="fa-solid fa-lock"></i> Locked</div>
                        </div>
                    </div>
                    <div>
                        <a href="?tab=locked&details=<?= urlencode($u['phonenum']) ?>" class="btn btn-outline btn-sm">View Details</a>
                    </div>
                </div>
            <?php endforeach; if(empty($locked_accounts)) echo "<p>No locked accounts.</p>"; ?>
        </div>

        <div id="list-tx" class="admin-list <?= $active_tab === 'tx' ? 'active' : '' ?>">
            <?php foreach($pending_tx as $tx): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <strong><?= htmlspecialchars($tx['transfer_type']) ?> - <?= number_format($tx['money'],0,',','.') ?> ₫</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">From: <?= htmlspecialchars($tx['user_phone']) ?> | Date: <?= htmlspecialchars($tx['date_transfer']) ?></div>
                        </div>
                    </div>
                    <div>
                        <a href="?tab=tx&tx_details=<?= urlencode($tx['id']) ?>" class="btn btn-outline btn-sm">Review</a>
                    </div>
                </div>
            <?php endforeach; if(empty($pending_tx)) echo "<p>No pending high-value transactions.</p>"; ?>
        </div>
    </main>
</div>

<div class="overlay <?= $selected_phone ? 'open' : '' ?>" onclick="window.location='Admin_dashboard.php?tab=<?= htmlspecialchars($active_tab) ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>'"></div>
<div class="details-panel <?= $selected_phone ? 'open' : '' ?>">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0;">Account Details</h3>
        <a href="Admin_dashboard.php?tab=<?= htmlspecialchars($active_tab) ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>" style="color: var(--text-color);"><i class="fa-solid fa-xmark fa-xl"></i></a>
    </div>
    
    <?php if($user_details): ?>
        <?php
            $status_label = '';
            $status_color = '';
            if (in_array($user_details['verified'], [-1, 0])) {
                $status_label = 'Pending Activation';
                $status_color = 'var(--warning)';
            } elseif ($user_details['verified'] == 2) {
                $status_label = 'Update Requested';
                $status_color = 'var(--warning)';
            } elseif ($user_details['verified'] == 4) {
                $status_label = 'Disabled / Blocked';
                $status_color = 'var(--danger)';
            } elseif ($user_details['verified'] == 1) {
                $status_label = 'Active';
                $status_color = 'var(--success)';
            }
            if ($user_details['abnormal_login'] >= 6 || $user_details['locked_time']) {
                $status_label .= ' (Locked)';
                $status_color = 'var(--danger)';
            }
        ?>
        <div style="background: var(--bg-surface); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                <div class="avatar" style="margin: 0; width: 50px; height: 50px; font-size: 20px;"><?= strtoupper(substr($user_details['name'] ?: 'UN', 0, 2)) ?></div>
                <div style="flex: 1;">
                    <h4 style="margin: 0; font-size: 18px;"><?= htmlspecialchars($user_details['name'] ?: 'N/A') ?></h4>
                    <div style="font-size: 13px; color: var(--text-muted);"><?= htmlspecialchars($user_details['email']) ?></div>
                </div>
                <div style="font-size: 12px; font-weight: 600; padding: 4px 8px; border-radius: 6px; background: rgba(255,255,255,0.05); color: <?= $status_color ?>; border: 1px solid <?= $status_color ?>;">
                    <?= $status_label ?>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Phone Number</div>
                    <div style="font-size: 14px; font-weight: 600;"><?= htmlspecialchars($user_details['phonenum']) ?></div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Date of Birth</div>
                    <div style="font-size: 14px; font-weight: 600;"><?= htmlspecialchars($user_details['birth'] ?: 'N/A') ?></div>
                </div>
                <div style="grid-column: span 2;">
                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Address</div>
                    <div style="font-size: 14px; font-weight: 600;"><?= htmlspecialchars($user_details['address'] ?: 'N/A') ?></div>
                </div>
                <div style="grid-column: span 2;">
                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Available Balance</div>
                    <div style="font-size: 20px; font-weight: 700; color: var(--success);"><?= number_format($user_details['money'],0,',','.') ?> ₫</div>
                </div>
            </div>
        </div>
            
        <?php if(in_array($user_details['verified'], [-1, 0, 2])): ?>
            <h4 style="margin-top: 20px; margin-bottom: 15px;">ID Verification Documents</h4>
            <?php if($user_details['front']): ?>
                <div style="margin-bottom: 15px;">
                    <span style="font-size: 12px; color: var(--text-muted);">Front Side:</span>
                    <img src="data:image/jpeg;base64,<?= base64_encode($user_details['front']) ?>" class="img-id">
                </div>
            <?php endif; ?>
            <?php if($user_details['back']): ?>
                <div style="margin-bottom: 15px;">
                    <span style="font-size: 12px; color: var(--text-muted);">Back Side:</span>
                    <img src="data:image/jpeg;base64,<?= base64_encode($user_details['back']) ?>" class="img-id">
                </div>
            <?php endif; ?>
            
            <form method="POST" action="../modules/adminLogic.php" style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;" onsubmit="return confirm('Are you sure you want to perform this action?');">
                <input type="hidden" name="phone" value="<?= htmlspecialchars($user_details['phonenum']) ?>">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" name="action" value="verify" class="btn btn-primary"><i class="fa-solid fa-check"></i> Verify Account</button>
                <button type="submit" name="action" value="request_info" class="btn btn-outline" style="border-color: var(--warning); color: var(--warning);"><i class="fa-solid fa-circle-exclamation"></i> Request More Info</button>
                <button type="submit" name="action" value="cancel" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger);"><i class="fa-solid fa-xmark"></i> Cancel & Disable</button>
            </form>
        <?php endif; ?>

        <?php if($user_details['verified'] == 4): ?>
            <form method="POST" action="../modules/adminLogic.php" style="margin-top: 20px;" onsubmit="return confirm('Are you sure you want to unblock/re-activate this user?');">
                <input type="hidden" name="phone" value="<?= htmlspecialchars($user_details['phonenum']) ?>">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" name="action" value="unlock" class="btn btn-outline" style="width: 100%; border-color: var(--success); color: var(--success);">
                    <i class="fa-solid fa-unlock"></i> Unblock Account
                </button>
            </form>
        <?php endif; ?>

        <?php if($user_details['verified'] == 1 && $user_details['abnormal_login'] < 6 && !$user_details['locked_time']): ?>
            <form method="POST" action="../modules/adminLogic.php" style="margin-top: 20px;" onsubmit="return confirm('Are you sure you want to block this user? They will not be able to login.');">
                <input type="hidden" name="phone" value="<?= htmlspecialchars($user_details['phonenum']) ?>">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" name="action" value="block" class="btn btn-outline" style="width: 100%; border-color: var(--danger); color: var(--danger);">
                    <i class="fa-solid fa-ban"></i> Block Account
                </button>
            </form>
        <?php endif; ?>

        <?php if($user_details['abnormal_login'] >= 6 || $user_details['locked_time']): ?>
            <div style="background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 8px; margin-top: 20px; border: 1px solid var(--danger);">
                <h4 style="color: var(--danger); margin-bottom: 10px;"><i class="fa-solid fa-lock"></i> Account Locked</h4>
                <p>Failed logins: <?= $user_details['abnormal_login'] ?></p>
                <p>Lock time: <?= $user_details['locked_time'] ?: 'N/A' ?></p>
                <form method="POST" action="../modules/adminLogic.php" onsubmit="return confirm('Unlock this account?');" style="margin-top: 15px;">
                    <input type="hidden" name="phone" value="<?= htmlspecialchars($user_details['phonenum']) ?>">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                    <button type="submit" name="action" value="unlock" class="btn btn-primary" style="width: 100%;">Unlock Account</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if(!empty($user_history)): ?>
            <h4 style="margin-top: 25px; margin-bottom: 15px;">Recent Transactions (This Month)</h4>
            <?php foreach($user_history as $h): ?>
                <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 8px; margin-bottom: 10px; font-size: 13px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="font-size: 14px;"><?= $h['transfer_type'] ?></strong>
                        <div style="color: var(--text-muted); margin-top: 4px;"><?= $h['date_transfer'] ?></div>
                    </div>
                    <div style="font-weight: 600; font-size: 14px; <?= $h['user_phone'] === $user_details['phonenum'] && $h['transfer_type'] !== 'Deposit' ? 'color: var(--danger);' : 'color: var(--success);' ?>">
                        <?= $h['user_phone'] === $user_details['phonenum'] && $h['transfer_type'] !== 'Deposit' ? '-' : '+' ?><?= number_format($h['money'],0,',','.') ?> ₫
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="overlay <?= $selected_tx_id ? 'open' : '' ?>" onclick="window.location='Admin_dashboard.php?tab=tx'"></div>
<div class="details-panel <?= $selected_tx_id ? 'open' : '' ?>">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0;">Transaction Details</h3>
        <a href="Admin_dashboard.php?tab=tx" style="color: var(--text-color);"><i class="fa-solid fa-xmark fa-xl"></i></a>
    </div>
    
    <?php if($tx_details): ?>
        <div style="margin-bottom: 20px;">
            <p><strong>ID:</strong> <?= htmlspecialchars($tx_details['id']) ?></p>
            <p><strong>Type:</strong> <?= htmlspecialchars($tx_details['transfer_type']) ?></p>
            <p><strong>Amount:</strong> <?= number_format($tx_details['money'],0,',','.') ?> ₫</p>
            <p><strong>Sender:</strong> <?= htmlspecialchars($tx_details['user_phone']) ?></p>
            <p><strong>Receiver:</strong> <?= htmlspecialchars($tx_details['receiver_phone'] ?: 'N/A') ?></p>
            <p><strong>Date:</strong> <?= htmlspecialchars($tx_details['date_transfer']) ?></p>
            <p><strong>Note:</strong> <?= htmlspecialchars($tx_details['note'] ?: 'None') ?></p>
            
            <form method="POST" action="../modules/adminLogic.php" style="margin-top: 30px; display: flex; flex-direction: column; gap: 10px;" onsubmit="return confirm('Confirm transaction decision?');">
                <input type="hidden" name="tx_id" value="<?= htmlspecialchars($tx_details['id']) ?>">
                <input type="hidden" name="tab" value="tx">
                <button type="submit" name="action" value="approve_tx" class="btn btn-primary" style="background: var(--success);">Approve Transaction</button>
                <button type="submit" name="action" value="reject_tx" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger);">Decline Transaction</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="mobile-bottom-nav">
    <a href="Admin_dashboard.php" class="nav-item active">
        <i class="fa-solid fa-shield-halved"></i>
        <span>Admin</span>
    </a>
    <a href="ChangePassword.php" class="nav-item">
        <i class="fa-solid fa-key"></i>
        <span>Password</span>
    </a>
</div>

<script src="../assets/js/admin.js"></script>

<?php
    include '../src/footer.php';
// if ($is_desktop) {
// } else {
//     ?>
