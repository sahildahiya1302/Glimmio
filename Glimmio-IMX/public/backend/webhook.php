<?php
require_once __DIR__ . '/../includes/env.php';
// Your verify token
$VERIFY_TOKEN = env('INSTAGRAM_VERIFY_TOKEN', 'glimmio_secure_token');

// Handle GET requests for verification
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === $VERIFY_TOKEN) {
        // Respond with the challenge token to verify
        echo $challenge;
        http_response_code(200);
    } else {
        // Unauthorized
        http_response_code(403);
    }
    exit();
}

// Handle POST requests for event notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the incoming JSON payload
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Log the received data (for debugging)
    $logFile = __DIR__ . '/../logs/webhook_log.txt';
    file_put_contents($logFile, print_r($data, true), FILE_APPEND);

    // Process the webhook data
    // Example: Save or act on the received events
    if (isset($data['entry'])) {
        foreach ($data['entry'] as $entry) {
            // Process entry data here
            // For example: Save to database or send an email alert
        }
    }

    http_response_code(200); // Respond to Instagram
    echo "Event Received";
    exit();
}

// Default response for unsupported methods
http_response_code(405);
echo "Method Not Allowed";