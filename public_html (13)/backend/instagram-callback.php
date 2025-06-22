<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/env.php';

$clientId = env('INSTAGRAM_CLIENT_ID');
$clientSecret = env('INSTAGRAM_CLIENT_SECRET');
$redirectUri = env('INSTAGRAM_REDIRECT_URI');

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
                'code' => $code
            ])
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents('https://api.instagram.com/oauth/access_token', false, $context);
    $data = json_decode($response, true);

    if (isset($data['access_token'], $data['user_id'])) {
        try {
            $pdo = db_connect();
            $stmt = $pdo->prepare('INSERT INTO instagram_tokens (user_id, access_token) VALUES (?, ?) ON DUPLICATE KEY UPDATE access_token = VALUES(access_token)');
            $stmt->execute([$data['user_id'], $data['access_token']]);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        header('Location: /pages/influencer-dashboard.php?user_id=' . urlencode($data['user_id']));
        exit;
    } else {
        echo 'Error fetching Instagram access token.';
    }
}
?>
