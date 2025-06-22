<?php
require_once 'db.php';
session_start();

function respond($success, $data = null, $message = '', $redirect = null) {
    header('Content-Type: application/json');
    $resp = ['success' => $success, 'data' => $data, 'message' => $message];
    if ($redirect) $resp['redirect'] = $redirect;
    echo json_encode($resp);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(false, 'Unauthorized');
}

$pdo = db_connect();
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'complete_profile') {
    $role = $_POST['role'] ?? '';
    $userId = $_SESSION['user_id'];

    try {
        if ($role === 'influencer') {
            $stmt = $pdo->prepare('UPDATE influencers SET instagram_handle=?, category=?, bio=?, upi_id=?, profile_complete=1 WHERE id=?');
            $stmt->execute([
                $_POST['instagram_handle'] ?? '',
                $_POST['category'] ?? '',
                $_POST['bio'] ?? '',
                $_POST['upi_id'] ?? '',
                $userId
            ]);
        } else {
            $stmt = $pdo->prepare('UPDATE brands SET company_name=?, website=?, industry=?, profile_complete=1 WHERE id=?');
            $stmt->execute([
                $_POST['company_name'] ?? '',
                $_POST['website'] ?? '',
                $_POST['industry'] ?? '',
                $userId
            ]);
        }
        $redirect = ($role === 'influencer') ? '../pages/influencer-dashboard.php' : '../pages/brand-dashboard.php';
        respond(true, null, 'Profile updated.', $redirect);
    } catch (Exception $e) {
        error_log('Profile update error: '.$e->getMessage());
        respond(false, 'Failed to update profile.');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'me') {
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    if ($role === 'influencer') {
        $stmt = $pdo->prepare('SELECT * FROM influencers WHERE id=?');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare('SELECT * FROM brands WHERE id=?');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
    }
    respond(true, $profile, 'ok');
} else {
    respond(false, 'Invalid request');
}