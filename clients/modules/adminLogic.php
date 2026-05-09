<?php
require_once("../modules/db_connection.php");
require_once("../modules/usertype.php");
$usertype = usertype(); 
if ($usertype != "3") {
    header('Location: Login.php');
    exit();
}
// $detect = new WP_Rocket_Mobile_Detect;
// $is_desktop = false;
// if (!$detect->isMobile() && !$detect->isTablet()) {
//     $is_desktop = true;
// }

$useremail = htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8');
$username = htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8');
$current_date = strtoupper(date('l, F j'));

$con = connect_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $tx_id = $_POST['tx_id'] ?? '';
    
    if ($action === 'verify' && $phone) {
        $stmt = $con->prepare("UPDATE `user` SET `verified` = 1 WHERE `phonenum` = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'cancel' && $phone) {
        $stmt = $con->prepare('SELECT `verified` FROM `user` WHERE `phonenum` = ?');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $subverified = -1;
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $subverified = $row['verified'] ?? -1;
        }
        $stmt->close();

        $stmt = $con->prepare("UPDATE `user` SET `subverified` = ?, `verified` = 4 WHERE `phonenum` = ?");
        $stmt->bind_param("is", $subverified, $phone);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'request_info' && $phone) {
        $stmt = $con->prepare("UPDATE `user` SET `verified` = 2 WHERE `phonenum` = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'unlock' && $phone) {
        $stmt = $con->prepare('SELECT `subverified` FROM `user` WHERE `phonenum` = ?');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $verified = -1;
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $verified = $row['subverified'] ?? -1;
        }
        $stmt->close();
        
        $stmt = $con->prepare("UPDATE `user` SET `verified` = ?, `subverified` = -1, `abnormal_login` = 0, `locked_time` = NULL WHERE `phonenum` = ?");
        $stmt->bind_param("is", $verified, $phone);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'block' && $phone) {
        $stmt = $con->prepare('SELECT `verified` FROM `user` WHERE `phonenum` = ?');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $subverified = -1;
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $subverified = $row['verified'] ?? -1;
        }
        $stmt->close();
        
        $stmt = $con->prepare("UPDATE `user` SET `subverified` = ?, `verified` = 4 WHERE `phonenum` = ?");
        $stmt->bind_param("is", $subverified, $phone);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'approve_tx' && $tx_id) {
        $stmt = $con->prepare("SELECT * FROM `history` WHERE `id` = ? AND `status` = 2");
        $stmt->bind_param("s", $tx_id);
        $stmt->execute();
        $tx = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($tx) {
            $sender = $tx['user_phone'];
            $receiver = $tx['receiver_phone'];
            $amount = floatval($tx['money']);
            $type = $tx['transfer_type'];
            
            $stmt = $con->prepare("SELECT `money` FROM `user` WHERE `phonenum` = ?");
            $stmt->bind_param("s", $sender);
            $stmt->execute();
            $sender_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($sender_data && floatval($sender_data['money']) >= $amount) {
                // Deduct from sender
                $stmt = $con->prepare("UPDATE `user` SET `money` = `money` - ? WHERE `phonenum` = ?");
                $stmt->bind_param("ds", $amount, $sender);
                $stmt->execute();
                $stmt->close();
                
                // Add to receiver if Transfer
                if ($type === 'Transfer' && $receiver) {
                    $stmt = $con->prepare("UPDATE `user` SET `money` = `money` + ? WHERE `phonenum` = ?");
                    $stmt->bind_param("ds", $amount, $receiver);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Update history status
                $now = date('Y-m-d H:i:s');
                $stmt = $con->prepare("UPDATE `history` SET `status` = 1, `date_confirm` = ? WHERE `id` = ?");
                $stmt->bind_param("ss", $now, $tx_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    } elseif ($action === 'reject_tx' && $tx_id) {
        $now = date('Y-m-d H:i:s');
        $stmt = $con->prepare("UPDATE `history` SET `status` = 0, `date_confirm` = ? WHERE `id` = ?");
        $stmt->bind_param("ss", $now, $tx_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Redirect to prevent form resubmission
    $redirect_tab = $_POST['tab'] ?? 'pending';
    $redirect_search = $_POST['search'] ?? '';
    $url = 'Admin_dashboard.php?tab=' . urlencode($redirect_tab);
    if ($redirect_search) $url .= '&search=' . urlencode($redirect_search);
    header('Location: ' . $url);
    exit();
}


// Pending: verified -1 (just registered), 0 (submitted ID), 2 (needs more info)
$pending_accounts = $con->query("SELECT * FROM `user` WHERE `verified` IN (-1, 0, 2) AND `phonenum` != '0000000000' ORDER BY GREATEST(`created_at`, COALESCE(`card_updated_at`, `created_at`)) DESC")->fetch_all(MYSQLI_ASSOC);
// Active: sort descending by account creation date
$active_accounts = $con->query("SELECT * FROM `user` WHERE `verified` = 1 AND `phonenum` != '0000000000' ORDER BY `created_at` DESC")->fetch_all(MYSQLI_ASSOC);
// Disabled (admin blocked / rejected): sort descending by creation date
$disabled_accounts = $con->query("SELECT * FROM `user` WHERE `verified` = 4 ORDER BY `created_at` DESC")->fetch_all(MYSQLI_ASSOC);
// Locked (too many failed logins): sort descending by lock time
$locked_accounts = $con->query("SELECT * FROM `user` WHERE (`abnormal_login` >= 6 OR `locked_time` IS NOT NULL) AND `verified` != 3 ORDER BY `locked_time` DESC")->fetch_all(MYSQLI_ASSOC);
$pending_tx = $con->query("SELECT * FROM `history` WHERE `money` > 5000000 AND `status` = 2 AND `transfer_type` IN ('Withdraw', 'Transfer') ORDER BY `date_transfer` DESC")->fetch_all(MYSQLI_ASSOC);

$search_query = trim($_GET['search'] ?? '');
$active_tab = $_GET['tab'] ?? ($search_query ? 'search' : 'pending');

$search_results = [];
if ($search_query) {
    $search_param = "%" . $search_query . "%";
    $stmt = $con->prepare("SELECT * FROM `user` WHERE (`phonenum` LIKE ? OR `email` LIKE ?) AND `phonenum` != '0000000000'");
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$selected_phone = $_GET['details'] ?? '';
$user_details = null;
$user_history = [];
if ($selected_phone) {
    $stmt = $con->prepare("SELECT * FROM `user` WHERE `phonenum` = ?");
    $stmt->bind_param("s", $selected_phone);
    $stmt->execute();
    $user_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($user_details && in_array($user_details['verified'], [1, 4]) && $user_details['abnormal_login'] < 6) {
        // Fetch transaction history in current month
        $current_month = date('Y-m');
        $stmt = $con->prepare("SELECT * FROM `history` WHERE (`user_phone` = ? OR `receiver_phone` = ?) AND DATE_FORMAT(`date_transfer`, '%Y-%m') = ? ORDER BY `date_transfer` DESC");
        $stmt->bind_param("sss", $selected_phone, $selected_phone, $current_month);
        $stmt->execute();
        $user_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$selected_tx_id = $_GET['tx_details'] ?? '';
$tx_details = null;
if ($selected_tx_id) {
    $stmt = $con->prepare("SELECT * FROM `history` WHERE `id` = ?");
    $stmt->bind_param("s", $selected_tx_id);
    $stmt->execute();
    $tx_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

?>