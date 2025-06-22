<?php
require_once __DIR__ . '/db.php';
header('Content-Type: image/gif');

$pdo = db_connect();
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

$stmt = $pdo->prepare('INSERT INTO pixel_events (event_type, utm_source, utm_medium, utm_campaign, utm_content, utm_term, ip_address, user_agent) VALUES (?,?,?,?,?,?,?,?)');
$stmt->execute(array_merge([$event], $utm, [$ip, $agent]));

// Transparent 1x1 GIF
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');

