<?php
require_once 'db.php';
session_start();
require_once __DIR__ . "/../includes/security.php";

function respond($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(false, null, 'Unauthorized');
}

$pdo = db_connect();
$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

// ensure wallet exists
$stmt = $pdo->prepare('SELECT id FROM wallets WHERE user_id = ? AND wallet_type = ?');
$stmt->execute([$userId, $role]);
$walletId = $stmt->fetchColumn();
if (!$walletId) {
    $pdo->prepare('INSERT INTO wallets (user_id, wallet_type) VALUES (?, ?)')->execute([$userId, $role]);
    $walletId = $pdo->lastInsertId();
}

function record_txn(PDO $pdo, $walletId, $campaignId, $amount, $type, $desc, $platformShare = 0, $influencerPayout = 0, $brandPayment = 0) {
    $stmt = $pdo->prepare('INSERT INTO transactions (wallet_id, campaign_id, amount, type, description, platform_share, influencer_payout, brand_payment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$walletId, $campaignId, $amount, $type, $desc, $platformShare, $influencerPayout, $brandPayment]);
}

if ($action === 'balance') {
    $stmt = $pdo->prepare('SELECT balance, on_hold FROM wallets WHERE id = ?');
    $stmt->execute([$walletId]);
    $data = $stmt->fetch();
    respond(true, $data);
} elseif ($action === 'transactions') {
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE wallet_id = ? ORDER BY created_at DESC LIMIT 50');
    $stmt->execute([$walletId]);
    respond(true, $stmt->fetchAll());
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_funds' && $role === 'brand') {
    require_csrf();
    $amount = floatval($_POST['amount'] ?? 0);
    if ($amount <= 0) respond(false, null, 'Invalid amount');
    $pdo->prepare('UPDATE wallets SET balance = balance + ? WHERE id = ?')->execute([$amount, $walletId]);
    record_txn($pdo, $walletId, null, $amount, 'credit', 'Funds added');
    respond(true, null, 'Balance updated');
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'payout' && $role === 'influencer') {
    require_csrf();
    $amount = floatval($_POST['amount'] ?? 0);
    $upi = trim($_POST['upi'] ?? '');
    if ($amount <= 0 || !$upi) respond(false, null, 'Invalid payout');
    $stmt = $pdo->prepare('SELECT balance FROM wallets WHERE id=?');
    $stmt->execute([$walletId]);
    $balance = $stmt->fetchColumn();
    if ($balance < $amount) respond(false, null, 'Insufficient balance');
    $pdo->prepare('UPDATE wallets SET balance = balance - ? WHERE id=?')->execute([$amount, $walletId]);
    record_txn($pdo, $walletId, null, -$amount, 'payout', 'UPI payout to '.$upi);
    respond(true, null, 'Payout requested');
} else {
    respond(false, null, 'Invalid request');
}