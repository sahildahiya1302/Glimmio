<?php
$pdo = null;
require_once __DIR__ . '/db.php';
header('Content-Type: image/gif');

try {
    $pdo = db_connect();
} catch (Throwable $e) {
    error_log('Pixel DB error: ' . $e->getMessage());
}
$input = json_decode(file_get_contents('php://input'), true);
$event = $input['event'] ?? 'page_view';
$utm = [
    $_GET['utm_source'] ?? null,
    $_GET['utm_medium'] ?? null,
    $_GET['utm_campaign'] ?? null,
    $_GET['utm_content'] ?? null,
    $_GET['utm_term'] ?? null
];
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$url = $input['url'] ?? null;
$ref = $input['referrer'] ?? null;
$sid = $input['session'] ?? null;
$campaignId = $input['campaign_id'] ?? null;
$submissionId = $input['submission_id'] ?? null;
$influencerId = $input['influencer_id'] ?? null;
$value = $input['value'] ?? null;


if ($pdo) {
    try {
        $stmt = $pdo->prepare('INSERT INTO pixel_events (event_type, utm_source, utm_medium, utm_campaign, utm_content, utm_term, ip_address, user_agent, page_url, referrer, session_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array_merge([$event], $utm, [$ip, $agent, $url, $ref, $sid]));

        if ($campaignId && $influencerId && $submissionId) {
            $stmt2 = $pdo->prepare('INSERT INTO user_events (campaign_id,influencer_id,submission_id,event_type,session_id,ip_address,device_info,revenue_value) VALUES (?,?,?,?,?,?,?,?)');
            $stmt2->execute([$campaignId,$influencerId,$submissionId,$event,$sid,$ip,$agent,$value]);
        }
    } catch (Throwable $e) {
        error_log('Pixel insert error: ' . $e->getMessage());
    }
}

// Transparent 1x1 GIF
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
