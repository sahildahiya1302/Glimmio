<?php
require_once 'db.php';
session_start();

function respond($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    respond(false, null, 'Unauthorized. Please log in.');
}

$pdo = null;
try {
    $pdo = db_connect();
} catch (Exception $ex) {
    error_log('DB connection error: ' . $ex->getMessage());
    respond(false, null, 'Database connection error.');
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list_requests') {
    // List requests for campaigns of logged in brand
    $brand_id = $_SESSION['user_id'];

    // Get campaigns of brand
    $stmt = $pdo->prepare('SELECT id FROM campaigns WHERE brand_id = ?');
    $stmt->execute([$brand_id]);
    $campaigns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$campaigns) {
        respond(true, [], 'No campaigns found.');
    }

    // Get requests for these campaigns
    $inQuery = implode(',', array_fill(0, count($campaigns), '?'));
    $stmt = $pdo->prepare("SELECT * FROM requests WHERE campaign_id IN ($inQuery) ORDER BY created_at DESC");
    $stmt->execute($campaigns);
    $requests = $stmt->fetchAll();

    respond(true, $requests);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_request_status') {
    // Accept or reject a request
    $request_id = $_POST['request_id'] ?? '';
    $status = $_POST['status'] ?? '';

    if (!$request_id || !in_array($status, ['accepted', 'rejected', 'live', 'completed'])) {
        respond(false, null, 'Invalid parameters.');
    }

    // Verify request belongs to brand's campaign
    $stmt = $pdo->prepare('SELECT r.id FROM requests r JOIN campaigns c ON r.campaign_id = c.id WHERE r.id = ? AND c.brand_id = ?');
    $stmt->execute([$request_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        respond(false, null, 'Unauthorized or request not found.');
    }

    // Update request status
    $stmt = $pdo->prepare('UPDATE requests SET status = ?, decision_at = NOW() WHERE id = ?');
    if ($stmt->execute([$status, $request_id])) {
        respond(true, null, 'Request status updated.');
    } else {
        respond(false, null, 'Failed to update request status.');
    }
} else {
    respond(false, null, 'Invalid request.');
}
?>