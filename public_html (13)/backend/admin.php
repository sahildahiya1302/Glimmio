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

if ($action === 'list_users' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT id, email, role, badge_level, profile_complete, created_at FROM users ORDER BY created_at DESC');
    respond(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($action === 'set_role' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? '';
    $role = $_POST['role'] ?? '';
    if (!$userId || !in_array($role, ['brand','influencer','admin'])) {
        respond(false, null, 'Invalid parameters');
    }
    $stmt = $pdo->prepare('UPDATE users SET role=? WHERE id=?');
    if ($stmt->execute([$role, $userId])) {
        respond(true, null, 'Role updated');
    } else {
        respond(false, null, 'Update failed');
    }
}

if ($action === 'list_campaigns' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT c.id, c.title, c.status, c.budget_total, c.commission_percent, u.email AS brand_email FROM campaigns c JOIN users u ON c.brand_id = u.id ORDER BY c.created_at DESC');
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
    $stmt = $pdo->query('SELECT r.*, u.email AS influencer_email, c.title FROM requests r JOIN users u ON r.influencer_uid = u.id JOIN campaigns c ON r.campaign_id = c.id ORDER BY r.created_at DESC');
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
    $stmt = $pdo->query('SELECT cs.*, u.email AS influencer_email, c.title FROM content_submissions cs JOIN users u ON cs.influencer_id = u.id JOIN campaigns c ON cs.campaign_id = c.id ORDER BY cs.created_at DESC');
    respond(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

respond(false, null, 'Invalid request');