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
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'overview') {
    $campaignId = $_GET['campaign_id'] ?? null;
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    try {
        if ($role === 'influencer') {
            $metricsSql = 'SELECT SUM(m.reach) as reach, SUM(m.impressions) as impressions, SUM(m.likes) as likes, SUM(m.comments) as comments, SUM(m.shares) as shares, SUM(m.saves) as saves, SUM(m.engagement_total) as engagement FROM metrics m JOIN content_submissions cs ON m.submission_id = cs.id WHERE cs.influencer_id = ?';
            $params = [$userId];
            if ($campaignId) {
                $metricsSql .= ' AND cs.campaign_id = ?';
                $params[] = $campaignId;
            }
            $stmt = $pdo->prepare($metricsSql);
            $stmt->execute($params);
            $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

            $attrSql = 'SELECT event_type, SUM(value_count) as count, SUM(value_sum) as value FROM attribution_summary WHERE influencer_id = ?';
            $params = [$userId];
            if ($campaignId) {
                $attrSql .= ' AND campaign_id = ?';
                $params[] = $campaignId;
            }
            $attrSql .= ' GROUP BY event_type';
            $aStmt = $pdo->prepare($attrSql);
            $aStmt->execute($params);
            $events = $aStmt->fetchAll(PDO::FETCH_ASSOC);
            respond(true, ['metrics' => $metrics, 'events' => $events]);
        } else {
            $metricsSql = 'SELECT SUM(m.reach) as reach, SUM(m.impressions) as impressions, SUM(m.likes) as likes, SUM(m.comments) as comments, SUM(m.shares) as shares, SUM(m.saves) as saves, SUM(m.engagement_total) as engagement FROM metrics m JOIN content_submissions cs ON m.submission_id = cs.id JOIN campaigns c ON cs.campaign_id = c.id WHERE c.brand_id = ?';
            $params = [$userId];
            if ($campaignId) {
                $metricsSql .= ' AND cs.campaign_id = ?';
                $params[] = $campaignId;
            }
            $stmt = $pdo->prepare($metricsSql);
            $stmt->execute($params);
            $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

            $attrSql = 'SELECT event_type, SUM(value_count) as count, SUM(value_sum) as value FROM attribution_summary WHERE campaign_id IN (SELECT id FROM campaigns WHERE brand_id = ?)';
            $params = [$userId];
            if ($campaignId) {
                $attrSql = 'SELECT event_type, SUM(value_count) as count, SUM(value_sum) as value FROM attribution_summary WHERE campaign_id = ? AND campaign_id IN (SELECT id FROM campaigns WHERE brand_id = ?)';
                $params = [$campaignId, $userId];
            }
            $attrSql .= ' GROUP BY event_type';
            $aStmt = $pdo->prepare($attrSql);
            $aStmt->execute($params);
            $events = $aStmt->fetchAll(PDO::FETCH_ASSOC);
            respond(true, ['metrics' => $metrics, 'events' => $events]);
        }
    } catch (Exception $e) {
        error_log('Metrics overview error: ' . $e->getMessage());
        respond(false, null, 'Failed to fetch metrics');
    }
} else {
    respond(false, 'Invalid request');
}