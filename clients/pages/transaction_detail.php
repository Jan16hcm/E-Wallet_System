<?php
include_once("../modules/db_connection.php");
include_once("../modules/usertype.php");
include_once("../modules/formatMoney.php");
include_once("../modules/generateIdCode.php");

$usertype = usertype();
$id = (int)($_GET['id'] ?? 0);
if ($id == 0) { 
    header('Location: transactions.php'); 
    exit;
}
$otherUser = array();
$phoneCodes = array();
$data = array();
$error = checkuser($usertype);

if (empty($error)){
    $con = connect_db();
    $stmt = $con->prepare("SELECT * FROM history WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);//size 1 array

    // Get receiver info
    if ($data['transfer_type'] == 'Transfer') {
        $stmt = $con->prepare("SELECT name, email FROM user WHERE phonenum = ?");
        $stmt->bind_param("s", $data['receiver_phone']);
        if(!$stmt->execute()){
            $error = "Database error: " . $stmt->error;
        } else {
            $stmt->bind_result($otherUser);
            $stmt->fetch();
        }
    }

    // Get phone card codes
    /*
    if ($data['transfer_type'] == 'Buycard') {
        $stmt = $con->prepare("SELECT * FROM phone_cards WHERE transaction_id = ?");
        $stmt->bind_param("s", $data['phone_card']);
        if(!$stmt->execute()){
            $error = "Database error: " . $stmt->error;
        } else {
            $stmt->bind_result($phoneCodes);
            $stmt->fetch();
        }
    }
    */
    $stmt->close();
    $con->close();
}

$iconColors = ['Deposit'=>'var(--success)','Withdraw'=>'var(--danger)','Transfer'=>'var(--info)','Buycard'=>'var(--gold)'];
$typeIcons = ['Deposit'=>'bi-arrow-down-circle-fill','Withdraw'=>'bi-arrow-up-circle-fill','Transfer'=>'bi-arrow-left-circle-fill','Buycard'=>'bi-sim-fill'];
include("../src/headerOutSide.php");
?>

<div>
    <div>
        <h2>Transaction Detail</h2>
        <p><?= $data['transfer_type'] ?></p>
    </div>
    <a href="transactions.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to History
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div>
            <div class="text-center py-4">
                <div style="width:64px;height:64px;border-radius:20px;background:<?= $iconColors[$data['transfer_type']] ?? '#ccc' ?>22;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:28px;color:<?= $iconColors[$data['transfer_type']] ?? '#ccc' ?>">
                    <i class="bi <?= $typeIcons[$data['transfer_type']] ?? 'bi-receipt' ?>"></i>
                </div>
                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px"><?= $data['transfer_type'] ?></div>
                <?php
                $amtSign  = in_array($data['transfer_type'], ['Deposit','Transfer']) ? '+' : '-';
                $amtColor = in_array($data['transfer_type'], ['Deposit','Transfer']) ? 'var(--success)' : 'var(--danger)';
                if ($data['status'] == 2) $amtColor = 'var(--warning)';
                ?>
                <div style="font-size:36px;font-family:'Playfair Display',serif;font-weight:700;color:<?= $amtColor ?>;margin:8px 0">
                    <?= $amtSign ?><?= formatMoney($data['money']) ?>
                </div>
                <?php
                $badge = '';
                switch ($data['status']) {
                    case 0: 
                        $badge = '<span class="badge bg-success px-3 py-2">Completed</span>';
                        break;
                    case 1:
                        $badge = '<span class="badge bg-warning text-dark px-3 py-2">Pending</span>';
                        break;
                    case 2:
                        $badge = '<span class="badge bg-danger px-3 py-2">Cancelled</span>';
                        break;
                    default:
                        $badge = '<span class="badge bg-secondary px-3 py-2">' . $data['status'] . '</span>';
                    }
                echo $badge;
                ?>
            </div>

            <div class="divider my-0"></div>

            <div>
                <div class="info-row">
                    <span class="info-label">Transaction Code</span>
                    <span class="info-value"><code><?= generateTransactionCode($data['id']) ?></code></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date init</span>
                    <span class="info-value"><?= date('d/m/Y H:i:s', strtotime($data['date_transfer'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date Cancelled/Approved</span>
                    <?php
                    if(!empty($data['date_confirm'])){
                        $dateconfirm = date('d/m/Y H:i:s', strtotime($data['date_confirm']));
                        echo '<span class="info-value">'. $dateconfirm . '</span>';
                    } else {
                        echo '<span class="info-value">The admin has yet to approve</span>';
                    }
                    ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Amount</span>
                    <span class="info-value fw-bold"><?= formatMoney($data['money']) ?></span>
                </div>

                <?php if ($data['selfFeeBear']): ?>
                <div class="info-row">
                    <span class="info-label">Fee 5% paid by yourself: </span>
                    <span class="info-value"><?= formatMoney($data['money']*0.05) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($data['card_num']): ?>
                <div class="info-row">
                    <span class="info-label">Card Number</span>
                    <span class="info-value"><code><?= $data['card_num'] ?></code></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($otherUser)): ?>
                    <div class="info-row">
                    <?php if ($data['selfFeeBear']): ?>
                        <span class="info-label">Fee 5% paid by yourself: </span>
                    <?php else: ?>
                        <span class="info-label">Fee 5% paid by <?= htmlspecialchars($otherUser['name']) ?>: </span>
                    <?php endif; ?>
                        <span class="info-value"><?= formatMoney($data['money']*0.05) ?></span>
                    </div>
                <div class="info-row">
                    <span class="info-label">Recipient</span>
                    <span class="info-value">
                        <strong><?= htmlspecialchars($otherUser['name']) ?></strong>
                        <div style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($otherUser['phonenum']) ?></div>
                    </span>
                </div>
                <?php else: ?>
                    <div class="info-row">
                    <?php if ($data['selfFeeBear']): ?>
                        <span class="info-label">Fee 5%: </span>
                        <span class="info-value"><?= formatMoney($data['money']*0.05) ?></span>
                    <?php else: ?>
                        <span class="info-label">No fee:>: </span>
                        <span class="info-value">0</span>
                    <?php endif; ?>
                    </div>
                <?php endif; ?>

                
                <?php if (!empty($data['card_num'])): ?>
                <div class="info-row">
                    <span class="info-label">Card number</span>
                    <span class="info-value"><?= $data['card_num'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Expiration date</span>
                    <span class="info-value"><?= $data['expire'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">CVV</span>
                    <span class="info-value"><?= $data['CVV'] ?></span>
                </div>
                <?php endif; ?>

                <?php if ($data['note']): ?>
                <div class="info-row">
                    <span class="info-label">Note</span>
                    <span class="info-value"><?= htmlspecialchars($data['note']) ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($phone_card)): ?>
                <div class="info-row flex-column">
                    
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
