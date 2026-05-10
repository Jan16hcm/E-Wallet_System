<?php
include_once("../modules/db_connection.php");
include_once("../modules/formatMoney.php");
include_once("../modules/receipt.php");

$usertype = usertype();
if($usertype != 1 || $usertype != 3) {//1: verified; 3: admin
    header("Location: Home.php");
    exit();
}

$step = $_GET['step'] ?? 1;
//?? is use the rightside value if leftside is null
$recipientPhone = '';
$amount = 0;
$note = '';
$selfFeeBear = false;//false -> recipient bear 5% fee; true -> sender bear 5% fee
$error = '';

if (isset($_POST['recipientPhone']) && isset($_POST['amount']) && $step == 1){
    $recipientPhone = trim($_POST['recipientPhone'] ?? '');
    $amount = str_replace(',','',($_POST['amount'] ?? '0'));//double and float are the same type
    $note = trim($_POST['note'] ?? 'no note');//note can be empty;  
    $selfFeeBear = $_POST['selfFeeBear'];

    if (empty($_POST['recipientPhone'])) {
        $error = 'Please enter the recipient phone number';
    } else if (empty($_POST['amount'])) {
        $error = 'Please enter the amount to transfer';
    } else if (empty($selfFeeBear)) {
        $error = 'Please choose wheather to bear the 5% fee yourself or the ricipieint';
    } else if (!is_numeric($amount)) {
        $error = 'This is not a valid number';
    } else {
        $amount = floatval($amount);
        if ($amount > 50000) {
            $con = connect_db();
            $result = $con->query("SELECT email, phonenum, name, money FROM user");
            $transfer_yourself = false;
            $found = false;
            $recipientName = '';
            $selfName = '';
            $selfamount = 0;
            $selfPhone = '';

            if ($result->num_rows > 0) {    //check if database is not empty
                while($row = $result->fetch_assoc()) { //check duplicate
                    if($row["email"] == $_SESSION['email']) {
                        $selfamount = $row['money'];//get self money
                        $selfPhone = $row['phonenum'];
                        $selfName = $row['name'];
                        if($selfPhone == $recipientPhone) {
                            $transfer_yourself = true;
                            $error = 'Warning: you may give ' . formatMoney($amount*0.05) . '\$ to owner of MeoMeo by transfering to yourself';
                            break;
                        }
                    }
                    if($row["phonenum"] == $recipientPhone && !$transfer_yourself) {
                        $recipientName = $row["name"];
                        $found = true;
                    }
                }
            }

            $con->close();
            if ($found) {
                $selfDeduct = $selfFeeBear ? $amount*1.05 : $amount;
                $recipientGet = $selfFeeBear ? $amount : $amount*0.95;
                if($selfamount < $selfDeduct){
                    $error = 'Insufficient balance. You need ' . formatMoney($selfDeduct) . ' (including 5% fee) but have ' . formatMoney($selfamount);
                } else {
                    $otp = sprintf('%06d', random_int(0, 999999));
                    $expire = date('Y-m-d H:i:s', time() + 60);//a time in future
                    $_SESSION['otp'] = $otp;
                    $_SESSION['otp_expire'] = $expire;

                    if(send_otp_email($otp, $_SESSION['email'], $name)){
                        $error = 'Failed to send mail, please try again later';
                    } else {
                        $_SESSION['transfer'] = ['amount' => $amount,
                                                'selfFeeBear' => $selfFeeBear,
                                                'note' => $note,
                                                'recipientName' => $recipientName,
                                                'selfName'=> $selfName,
                                                'recipientPhone' => $recipientPhone,
                                                'selfPhone' => $selfPhone,
                                                'recipientGet' => $recipientGet,
                                                'selfDeduct' => $selfDeduct];
                        /*$step = 2;
                        header('Location: transfer.php?step=2');
                        exit;*/
                    }
                }
            } else {
                $error = 'Recipient not found';
            }
        } else {
            $error = 'Minimum transfer amount is 50,000 VND';
        }
    }
}

if($step == 2 && isset($_SESSION['otp']) && isset($_SESSION['transfer'])) {
    if (empty($_SESSION['transfer'])) {//useless ?
        header('Location: transfer.php');
        exit;
    }

    $otp = strval($_SESSION['otp']);
    $expire = $_SESSION['otp_expire'];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if(isset($_POST['otp_in6'])){
            $otp_in = $_POST['otp_in1'] . $_POST['otp_in2'] . $_POST['otp_in3'] . $_POST['otp_in4'] . $_POST['otp_in5'] . $_POST['otp_in6'];
            if(strcmp($otp_in, $otp) != 0) {
                $error = 'Wrong OTP code';
            } else if($expire < time()){
                $error = 'OTP code expired';
                unset($_SESSION['otp']);
                unset($_SESSION['otp_expire']);
            } else {
                unset($_SESSION['otp']);
                unset($_SESSION['otp_expire']);
                $t = $_SESSION['transfer']; //to shorten lonnnng name
                $status = $t['amount'] > 5000000 ? 2 : 1; //5 milion need approve
                //1 == completed == Approved; 2 == Pending
                $date = date('Y-m-d H:i:s'); // current date/time
                $con = connect_db();

                $tran = $con->prepare("INSERT INTO history (user_phone, receiver_phone, transfer_type, date_transfer, money, note, status, selfFeeBear) VALUES (?, ?, Transferto, " . $date . ", ?, ?, " . $status . ", ?)");
                $tran->bind_param("ssdsi", $t['selfPhone'], $t['recipientPhone'], $t['amount'], $t['note'], $t['selfFeeBear']);
                //bool => integer (i) if sql TINYINT(1) or string (s)
                if(!$tran->execute()){
                    $error = "Database error: " . $tran->error;
                } else if($status == 1){
                    $tran = $con->prepare("update user set money = money - ? where phonenum = ?");
                    $tran->bind_param("ds", $t['selfDeduct'], $t['selfPhone']);
                    $tran->execute();//no error check

                    $tran = $con->prepare("update user set money = money + ? where phonenum = ?");
                    $tran->bind_param("ds", $t['recipientGet'], $t['recipientPhone']);
                    $tran->execute();

                    //get recipient email and balance
                    $email = '';
                    $recipientMoney = 0;
                    $tran = $con->prepare('select email, money from user where phonenum = ?');
                    $tran->bind_param("s", $t['recipientPhone']);
                    $tran->execute();
                    $tran->get_result();
                    $tran->bind_result($email, $recipientMoney);
                    $tran->fetch();

                    $recipientMoney = floatval($recipientMoney); //incase
                    $tran->close();
                    $con->close();

                    if(!send_receipt(formatMoney($t['recipientGet']), formatMoney($recipientMoney), $email, $t['selfName'], $t['recipientName'], $t['note'])){
                        $error = 'Failed to sent receipt to receiver';
                    } else {
                        $step = 3;
                        //complete success
                        //should show something on screen
                    }
                }
                $tran->close();
                $con->close();
            }
        }
    }
}
?>

<label for="otp_in">Enter OTP here:</label>
<table>
    <tr>
        <th><input name="otp_in1" id="otp_in" type="text" maxlength="1"></th>
        <th><input name="otp_in2" id="otp_in" type="text" maxlength="1"></th>
        <th><input name="otp_in3" id="otp_in" type="text" maxlength="1"></th>
        <th><input name="otp_in4" id="otp_in" type="text" maxlength="1"></th>
        <th><input name="otp_in5" id="otp_in" type="text" maxlength="1"></th>
        <th><input name="otp_in6" id="otp_in" type="text" maxlength="1"></th>
    </tr>
</table>

<script>
//claude here (not checked)
// Look up recipient as they type
const phoneInput = document.getElementById('recipientPhone');
const recipientInfo = document.getElementById('recipientInfo');
if (phoneInput) {
    let timeout;
    phoneInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const phone = this.value.trim();
        if (phone.length >= 8) {
            timeout = setTimeout(() => {
                fetch('../modules/lookup_user.php?phone=' + encodeURIComponent(phone))
                    .then(r => r.json())
                    .then(data => {
                        if (data.found) {
                            recipientInfo.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> ' + data.name;
                        } else {
                            recipientInfo.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i> User not found';
                        }
                    }).catch(() => {});
            }, 500);
        }
    });
}

const inputs = document.querySelectorAll("table input");

    inputs.forEach((input, index) => {
        input.addEventListener("input", () => {
            if (input.value.length === input.maxLength) {
                const nextInput = inputs[index + 1];
                if (nextInput) {
                    nextInput.focus();
                }
            }
        });

        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && input.value.length === 0) {
                const prevInput = inputs[index - 1];
                if (prevInput) {
                    prevInput.focus();
                }
            }
        });
    });
</script>
