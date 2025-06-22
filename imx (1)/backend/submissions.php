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
    respond(false, null, 'Unauthorized. Please log in.');
}

$pdo = null;
try {
    $pdo = db_connect();
} catch (Exception $ex) {
    error_log('DB connect error: ' . $ex->getMessage());
    respond(false, null, 'Database connection error.');
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit') {
    require_csrf();
    // influencer submits content for a campaign
    $campaign_id = $_POST['campaign_id'] ?? '';
    if (!$campaign_id) {
        respond(false, null, 'Campaign ID required');
    }
    if (!isset($_FILES['media']) || !validate_upload($_FILES['media'], ['image/jpeg','image/png','video/mp4','video/quicktime'])) {
        respond(false, null, 'Media file required');
    }
    $upload_dir = __DIR__ . '/../uploads/submissions/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $filename = uniqid() . '_' . basename($_FILES['media']['name']);
    $target = $upload_dir . $filename;
    if (!move_uploaded_file($_FILES['media']['tmp_name'], $target)) {
        respond(false, null, 'Upload failed');
    }
    $media_url = '/uploads/submissions/' . $filename;
    $caption = $_POST['caption'] ?? null;

    $stmt = $pdo->prepare('INSERT INTO content_submissions (campaign_id, influencer_id, media_url, caption) VALUES (?, ?, ?, ?)');
    try {
        $stmt->execute([$campaign_id, $_SESSION['user_id'], $media_url, $caption]);
        respond(true, null, 'Content submitted');
    } catch (Exception $e) {
        error_log('Error submit content: ' . $e->getMessage());
        respond(false, null, 'Failed to submit');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    // list submissions depending on role
    if ($_SESSION['role'] === 'brand') {
        $stmt = $pdo->prepare('SELECT cs.* FROM content_submissions cs JOIN campaigns c ON cs.campaign_id = c.id WHERE c.brand_id = ? ORDER BY cs.created_at DESC');
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM content_submissions WHERE influencer_id = ? ORDER BY created_at DESC');
        $stmt->execute([$_SESSION['user_id']]);
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond(true, $rows);
    require_csrf();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    // brand updates status
    if ($_SESSION['role'] !== 'brand') {
        respond(false, null, 'Unauthorized');
    }
    $submission_id = $_POST['submission_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $feedback = $_POST['feedback'] ?? null;
    if (!$submission_id || !in_array($status, ['approved','rejected','needs_revision'])) {
        respond(false, null, 'Invalid parameters');
    }
    $stmt = $pdo->prepare('SELECT cs.id FROM content_submissions cs JOIN campaigns c ON cs.campaign_id = c.id WHERE cs.id = ? AND c.brand_id = ?');
    $stmt->execute([$submission_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        respond(false, null, 'Submission not found');
    }
    $stmt = $pdo->prepare('UPDATE content_submissions SET status = ?, brand_feedback = ? WHERE id = ?');
    if ($stmt->execute([$status, $feedback, $submission_id])) {
        respond(true, null, 'Status updated');
    } else {
        respond(false, null, 'Update failed');
    require_csrf();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mark_live') {
    // influencer marks submission as posted on Instagram
    $submission_id = $_POST['submission_id'] ?? '';
    $post_id = $_POST['post_id'] ?? '';
    $post_url = $_POST['post_url'] ?? null;
    if (!$submission_id || !$post_id) {
        respond(false, null, 'Missing parameters');
    }
    $stmt = $pdo->prepare('UPDATE content_submissions SET post_id = ?, posted_url = ?, posted_at = NOW(), status = ? WHERE id = ? AND influencer_id = ?');
    if ($stmt->execute([$post_id, $post_url, 'live', $submission_id, $_SESSION['user_id']])) {
        // Fetch metrics immediately
        require_once __DIR__ . '/metrics.php';
        $tokenStmt = $pdo->prepare('SELECT access_token FROM instagram_tokens WHERE user_id = ?');
        $tokenStmt->execute([$_SESSION['user_id']]);
        $token = $tokenStmt->fetchColumn();
        if ($token) {
            $metrics = fetch_metrics_graph($post_id, $token);
            if ($metrics) {
                $eng = $metrics['likes'] + $metrics['comments'] + $metrics['shares'] + $metrics['saves'];
                $mStmt = $pdo->prepare('INSERT INTO metrics (submission_id, post_id, reach, impressions, likes, comments, shares, saves, engagement_total, fetched_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE reach=VALUES(reach), impressions=VALUES(impressions), likes=VALUES(likes), comments=VALUES(comments), shares=VALUES(shares), saves=VALUES(saves), engagement_total=VALUES(engagement_total), fetched_at=NOW()');
                $mStmt->execute([$submission_id, $post_id, $metrics['reach'], $metrics['impressions'], $metrics['likes'], $metrics['comments'], $metrics['shares'], $metrics['saves'], $eng]);
            }
        }
        respond(true, null, 'Marked live');
    } else {
        respond(false, null, 'Failed to update submission');
    }
} else {
    respond(false, null, 'Invalid request');
}
?>