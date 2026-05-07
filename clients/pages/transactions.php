<?php
include_once("../modules/db_connection.php");
include_once("../modules/usertype.php");
include_once("../modules/formatMoney.php");

$usertype = usertype();
if ($usertype != 1 && $usertype != 3) {
    $error = 'This function is only for verified accounts';
}
$page = max(1, (int)($_GET['page'] ?? 1));//no -number
$perPage = max(1, (int)($_GET['perPage'] ?? 20));
$totalPages = 1;
$count = 0;
$offset = ($page - 1) * $perPage;
$filter = $_GET['transfer_type'] ?? '';
//Deposit/Transferto/Transferby/Withdraw/Buycard
$error = '';


if(empty($error)){
    $con = connect_db();
    $stmt = $con->prepare("select phonenum from user where email = ?");
    $stmt->bind_param("s", $_SESSION["email"]);
    $stmt->execute();
    $user_phone = '';
    $stmt->bind_result($user_phone);
    $stmt->fetch();//done get user phonenum

    $stmt = $con->prepare("SELECT COUNT(*) FROM history WHERE user_phone = ?");
    $stmt->bind_param("s", $user_phone);
    $stmt->execute();
    $count = 0;
    $stmt->bind_result($count);
    $stmt->fetch();//get count for page showing done

    $stmt = $con->prepare("SELECT * FROM history WHERE user_phone = ? 
                            UNION
                            SELECT * FROM history WHERE receiver_phone = ? AND status = 1
                            ORDER BY date_transfer DESC LIMIT $perPage OFFSET $offset");
    $stmt->bind_param("ss", $user_phone, $user_phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);//get all data related to user done
    $stmt->close();
    $con->close(); 
    $totalPages = ceil($count / $perPage);
}
$typeIcons = ['Deposit'=>'bi-arrow-down-circle-fill','Withdraw'=>'bi-arrow-up-circle-fill','Transfer'=>'bi-arrow-left-circle-fill','Buycard'=>'bi-sim-fill'];
include("../src/headerOutSide.php");
?>

<h2>Transaction History</h2>

<?php if (empty($error)): ?> 
<!-- Filter Tabs -->
<div class="d-flex gap-2 flex-wrap">
    <?php
    $tabs = ['' => 'All', 'Deposit' => 'Deposits', 'Withdraw' => 'Withdrawals', 'Transfer' => 'Transfers', 'Buycard' => 'Phone Cards'];
    foreach ($tabs as $key => $label):
    ?>
    <a href="?type=<?= $key ?>" class="btn btn-sm <?= $filter == $key ? 'btn-primary' : 'btn-outline-secondary' ?>">
    <?= $label ?></a>
    <?php endforeach; ?>
</div>

<div>
    <?php if (empty($data)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-inbox d-block mb-2"></i>
        No transactions found.
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $tx):
                    $amtSign = in_array($tx['transfer_type'], ['Deposit','Transfer']) ? '+' : '-';
                    $amtClass = in_array($tx['transfer_type'], ['Deposit','Transfer']) ? 'text-success' : 'text-danger';
                    if ($tx['status'] === 'pending') $amtClass = 'text-warning';
                    $statusBadge = '';
                    switch ($tx['status']) {
                        case 0: 
                            $statusBadge = '<span class="badge bg-success">Completed</span>';
                            break;
                        case 1:
                            $statusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
                            break;
                        case 2:
                            $statusBadge = '<span class="badge bg-danger">Cancelled</span>';
                            break;
                        default:
                            $statusBadge = '<span class="badge bg-secondary">' . $tx['status'] . '</span>';
                    }
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="tx-icon <?= $tx['type'] ?>" style="width:36px;height:36px;font-size:15px">
                                <i class="bi <?= $typeIcons[$tx['transfer_type']] ?? 'bi-receipt' ?>"></i>
                            </div>
                            <span class="fw-semibold"><?= $tx['type'] ?></span>
                        </div>
                    </td>
                    <td class="fw-bold <?= $amtClass ?>"><?= $amtSign ?><?= formatMoney($tx['money']) ?></td>
                    <td><?= $statusBadge ?></td>
                    <td class="text-muted" style="font-size:13px"><?= date('d/m/Y H:i', strtotime($tx['date_transfer'])) ?></td>
                    <td><a href="transaction_detail.php?id=<?= $tx['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-between align-items-center p-3 border-top">
        <span class="text-muted" style="font-size:13px">Showing <?= count($data) ?> of <?= $count ?> transactions</span>
        <div class="d-flex gap-1">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?type=<?= $filter ?>&page=<?= $p ?>&perPage=<?= $perPage ?>" class="btn btn-sm <?= $p == $page ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

</div>
<div id="error-msg" class="error-alert<?= empty($error) ? ' is-invisible' : '' ?>" role="alert">
    <svg viewBox="0 0 24 24" width="16" height="16">
        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" />
        <path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </svg>
    <span><?= !empty($error) ? htmlspecialchars($error) : '&nbsp;' ?></span>
</div>