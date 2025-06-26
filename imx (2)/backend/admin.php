<?php
require_once 'db.php';
session_start();

function respond($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    respond(false, null, 'Unauthorized');
}

try {
    $pdo = db_connect();
} catch (Exception $e) {
    error_log('DB connection error: ' . $e->getMessage());
    respond(false, null, 'Database error');
}

$action = $_GET['action'] ?? '';

if ($action === 'list_accounts' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $brands = $pdo->query("SELECT id, email, 'brand' AS role, badge_level, profile_complete, created_at FROM brands ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $influencers = $pdo->query("SELECT id, email, 'influencer' AS role, badge_level, profile_complete, created_at FROM influencers ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    respond(true, array_merge($brands, $influencers));
}

if ($action === 'set_badge' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? '';
    $role = $_POST['role'] ?? '';
    $badge = $_POST['badge'] ?? '';
    if (!$userId || !in_array($role, ['brand','influencer']) || !in_array($badge, ['bronze','silver','gold'])) {
        respond(false, null, 'Invalid parameters');
    }
    $table = $role === 'brand' ? 'brands' : 'influencers';
    $stmt = $pdo->prepare("UPDATE {$table} SET badge_level=? WHERE id=?");
    if ($stmt->execute([$badge, $userId])) {
        respond(true, null, 'Badge updated');
    } else {
        respond(false, null, 'Update failed');
    }
}

if ($action === 'list_badge_rates' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM badge_rates');
    respond(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($action === 'set_badge_rate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $badge = $_POST['badge'] ?? '';
    $rate = floatval($_POST['rate'] ?? 0);
    if (!in_array($badge, ['bronze','silver','gold','elite']) || $rate <= 0) {
        respond(false, null, 'Invalid parameters');
    }
    $stmt = $pdo->prepare('INSERT INTO badge_rates (badge_level, cpm_rate) VALUES (?, ?) ON DUPLICATE KEY UPDATE cpm_rate=VALUES(cpm_rate)');
    if ($stmt->execute([$badge, $rate])) {
        respond(true, null, 'Rate updated');
    } else {
        respond(false, null, 'Update failed');
    }
}

if ($action === 'list_campaigns' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT c.id, c.title, c.status, c.budget_total, c.commission_percent, b.email AS brand_email FROM campaigns c JOIN brands b ON c.brand_id = b.id ORDER BY c.created_at DESC");
    respond(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($action === 'update_campaign_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid = $_POST['campaign_id'] ?? '';
    $status = $_POST['status'] ?? '';
    if (!$cid || !in_array($status, ['active','ended','completed','cancelled'])) {
        respond(false, null, 'Invalid parameters');
    }
    $stmt = $pdo->prepare('UPDATE campaigns SET status=? WHERE id=?');
    if ($stmt->execute([$status, $cid])) {
        respond(true, null, 'Campaign updated');
    } else {
        respond(false, null, 'Update failed');
    }
}

if ($action === 'list_requests' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT r.*, i.email AS influencer_email, c.title FROM requests r JOIN influencers i ON r.influencer_uid = i.id JOIN campaigns c ON r.campaign_id = c.id ORDER BY r.created_at DESC");
    respond(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($action === 'update_request_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid = $_POST['request_id'] ?? '';
    $status = $_POST['status'] ?? '';
    if (!$rid || !in_array($status, ['accepted','rejected','live','completed'])) {
        respond(false, null, 'Invalid parameters');
    }
    $stmt = $pdo->prepare('UPDATE requests SET status=? WHERE id=?');
    if ($stmt->execute([$status, $rid])) {
        respond(true, null, 'Request updated');
    } else {
        respond(false, null, 'Update failed');
    }
}

if ($action === 'list_submissions' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT cs.*, i.email AS influencer_email, c.title FROM content_submissions cs JOIN influencers i ON cs.influencer_id = i.id JOIN campaigns c ON cs.campaign_id = c.id ORDER BY cs.created_at DESC");
    respond(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

respond(false, null, 'Invalid request');