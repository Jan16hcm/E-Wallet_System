<?php
include_once("../modules/db_connection.php");
include_once("../modules/usertype.php");
include_once("../modules/formatMoney.php");
include_once("../modules/isValidCard.php");
include_once("../modules/generateCode.php");

$usertype = usertype();
$error = checkuser($usertype);
$fee = 0; //No fee for phone cards yet
$carrier = '';
$denomination = 0;
$quantity = 0;
$note = '';
$carriers = CARRIERS;//['Viettel'=>'11111','Mobifone'=>'22222','Vinaphone'=>'33333']
$denoms = CARD_DENOMINATIONS;//[10000, 20000, 50000, 100000]
$codes = array();
$user_money = 0;
$total = 0;
$id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $carrier = trim($_POST['carrier'] ?? '');
    $denomination = (int)($_POST['denomination'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if (!array_key_exists($carrier, $carriers)) {
        $error = 'Please select a valid carrier';
    } elseif (!in_array($denomination, $denoms)) {
        $error = 'Please select a valid denomination';
    } elseif ($quantity < 1 || $quantity > 5) {
        $error = 'You can buy between 1 and 5 cards at a time';
    } else {
        $total = ($denomination + $fee)*$quantity;
        $selfPhone = '';
        $con = connect_db();
        $stmt = $con->prepare("SELECT phonenum, money FROM user where email = ?");
        $stmt->bind_param("s", $_SESSION['email']); 
        $stmt->execute();
        $stmt->bind_result($selfPhone, $user_money);
        $stmt->fetch();//done get user phonenum, money
        
        if ($user_money < $total) {
            $error = 'Insufficient balance. You need ' . formatMoney($total) . ' but have ' . formatMoney($user_money);
        } else {
            // Insert transaction
            $status = 1; //no need approve in Buycard
            $transfer_type = "Buycard";
            $selfFeeBear = $fee != 0 ? 1 : 0;//bool, store as int
            //selfFeeBear is true because transaction fees may be updated in the future 
            $id = generateIdCode($selfPhone, 4);
            $date = date('Y-m-d H:i:s'); // current date/time
            $stmt = $con->prepare("INSERT INTO history (id, user_phone, transfer_type, date_transfer, money, note, status, selfFeeBear) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssdsii", $id, $selfPhone, $transfer_type, $date, $total, $note, $status, $selfFeeBear);
        
            if(!$stmt->execute()){
                $error = 'Failed to save in transfer history';
            } else {
                // Generate card codes
                $carrierCode = $carriers[$carrier];
                for ($i = 0; $i < $quantity; $i++) {
                    $code = generateCardCode($selfPhone, $carrierCode);//return int
                    $codes[$i] = $code;
                    //phonecard primary key: id, code; denomination = float

                    $stmt->prepare("INSERT INTO phonecard (id, code, carrier, denomination) VALUES (?,?,?,?)");
                    $stmt->bind_param("sisd", $id, $code, $carrier, $denomination);
                    if(!$stmt->execute()){
                        $error = "Failed to save in phone card history at card number " . ($i + 1) . ". ";
                        $total = $denomination*$i;
                        break;
                    }
                }
                // Deduct balance
                $stmt->prepare("UPDATE user SET money = money - ? WHERE phonenum = ?");
                $stmt->bind_param("ds", $total, $selfPhone);
                if(!$stmt->execute()){
                    //write cancel to history
                    $status = 0;
                    $canceldate = date('Y-m-d H:i:s');
                    $stmt = $con->prepare("UPDATE history SET status = ?, date_confirm = ? where id = ?");
                    $stmt->bind_param("iss", $status, $canceldate, $id);
                    
                    if(!$stmt->execute()){
                        if($total != ($denomination + $fee)*$quantity) {//only buy less card than expected
                            $error .= 'Failed to update user balance, failed to cancel the transaction to buy card.';
                            //i don't know how to handle this case
                        } else {
                            $error = 'Failed to update user balance, failed to cancel the transaction to buy card. It seem like god want you to have free money';
                            //i don't know how to handle this case
                        }
                    } else {
                        $error .= 'Failed to update user balance, cancelled the transferal';
                        //add more error to string
                    }
                } else {
                    //complete success

                }
            }
        }
        $stmt->close();
        $con->close();
    }
}
include("../src/header.php");
?>

<h2>Buy Phone Cards</h2>
<p>Purchase scratch cards for Viettel, Mobifone, Vinaphone</p>

<?php if (empty($error)): ?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <h4 style="font-family:'Playfair Display',serif;color:var(--navy);margin-top:12px">Purchase Successful!</h4>
        <div class="info-row"><span class="info-label">Carrier</span><span class="info-value fw-bold"><?= htmlspecialchars($carrier) ?></span></div>
        <div class="info-row"><span class="info-label">Denomination</span><span class="info-value"><?= formatMoney($denomination) ?> x <?= $quantity ?></span></div>
        <div class="info-row"><span class="info-label">Total Paid</span><span class="info-value fw-bold text-danger"><?= formatMoney($total) ?></span></div>
        <div class="info-row"><span class="info-label">Fee</span><span class="info-value"><?= formatMoney($fee) ?></span></div>
        <div class="info-row"><span class="info-label">New Balance</span><span class="info-value fw-bold text-success"><?= formatMoney($user_money) ?></span></div>
        <div class="divider"></div>
        <h6 class="mb-3" style="font-family:'Playfair Display',serif;color:var(--navy)">Your Card Code <?php $quantity > 1 ? "" : "s" ?></h6>
        <?php foreach ($codes as $i => $code): ?>
        <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded" style="background:var(--cream);border:1px solid var(--border)">
            <span style="font-size:12px;color:var(--text-muted)">Card <?= $i+1 ?></span>
            <code style="font-size:20px;letter-spacing:4px;font-weight:700;color:var(--navy)"><?= htmlspecialchars($code) ?></code>
            <button class="btn btn-sm btn-outline-secondary" onclick="copyCode('<?= $code ?>')">
                <i class="bi bi-copy"></i>
            </button>
        </div>
        <?php endforeach; ?>
        <div class="mt-4 d-flex gap-2 justify-content-center">
            <a href="transaction_detail.php?id=<?= $id ?>" class="btn btn-outline-secondary">View Receipt</a>
            <a href="Buycard.php" class="btn btn-primary">Buy More</a>
        </div>
    </div>
</div>

<?php else: ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class=""><i class="bi bi-sim-fill text-gold fs-5" style="color:var(--gold)"></i>
            <h5>Card Details</h5>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="cardForm">
            <div class="mb-3">
                <label class="">Mobile Carrier <span class="text-danger">*</span></label>
                <div class="row g-2">
                    <?php foreach ($carriers as $name => $code): ?>
                    <div class="col-4">
                        <input type="radio" name="carrier" value="<?= $name ?>" id="carrier_<?= $name ?>" class="btn-check" <?= ($_POST['carrier'] ?? '') === $name ? 'checked' : '' ?> required>
                        <label class="btn btn-outline-secondary w-100 fw-semibold" for="carrier_<?= $name ?>"><?= $name ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="">Denomination<span class="text-danger">*</span></label>
                <div class="row g-2">
                    <?php foreach ($denoms as $d): ?>
                    <div class="col-6">
                        <input type="radio" name="denomination" value="<?= $d ?>" id="denom_<?= $d ?>" class="btn-check" <?= (int)($_POST['denomination'] ?? 0) === $d ? 'checked' : '' ?> required>
                        <label class="btn btn-outline-secondary w-100 fw-semibold" for="denom_<?= $d ?>"><?= formatMoney($d) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-4">
                <label class="">Quantity (1-5) <span class="text-danger">*</span></label>
                <select name="quantity" class="form-select" id="quantity">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>" <?= (int)($_POST['quantity'] ?? 1) === $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Total preview -->
            <div class="p-3 rounded mb-4" style="background:var(--cream);border:1px solid var(--border)">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted" style="font-size:13px">Unit Price</span>
                    <span id="unitPrice" class="fw-semibold">—</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted" style="font-size:13px">Quantity</span>
                    <span id="qty" class="fw-semibold">—</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted" style="font-size:13px">Transaction Fee</span>
                    <span class="text-success fw-semibold"><?= formatMoney($fee) ?></span>
                </div>
                <div class="divider my-2"></div>
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Total</span>
                    <span id="total" class="fw-bold" style="color:var(--navy);font-size:16px">—</span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-bag-check-fill me-2"></i>Purchase Cards
            </button>
        </form>
    </div>
</div>

<div class="col-lg-6">
    <div class="">
        <i class="bi bi-wallet2 text-navy fs-5"></i><h5>Your Balance</h5>
    </div>

    <div class="text-center">
        <div style="font-size:28px;font-family:'Playfair Display',serif;color:var(--navy)"><?= formatMoney($user_money) ?></div>
    </div>

    <div class=""><i class="bi bi-table text-navy fs-5">
        </i><h5>Available Carriers</h5>
    </div>

    <div class="p-0">
        <table class="">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Carrier</th>
                    <th>Code</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Viettel</td>
                    <td><code>11111</code></td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Mobifone</td>
                    <td><code>22222</code></td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Vinaphone</td>
                    <td><code>33333</code></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php include("../src/footer.php"); ?>
<script>
function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        alert('Code copied: ' + code);
    });
}

function updateTotal() {
    const denom = document.querySelector('input[name="denomination"]:checked');
    const qty = document.getElementById('quantity');
    const unit = document.getElementById('unitPrice');
    const qtyEl = document.getElementById('qty');
    const total = document.getElementById('total');

    if (denom && qty) {
        const d = parseInt(denom.value);
        const q = parseInt(qty.value);
        unit.textContent  = new Intl.NumberFormat('vi-VN').format(d) + ' VND';
        qtyEl.textContent = q;
        total.textContent = new Intl.NumberFormat('vi-VN').format(d * q) + ' VND';
    }
}

document.querySelectorAll('input[name="denomination"]').forEach(el => el.addEventListener('change', updateTotal));
document.getElementById('quantity')?.addEventListener('change', updateTotal);
</script>