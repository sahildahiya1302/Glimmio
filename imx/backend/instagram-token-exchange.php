<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/instagram_api.php';
require_once __DIR__ . '/../includes/env.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $client_id = env('INSTAGRAM_CLIENT_ID');
    $client_secret = env('INSTAGRAM_CLIENT_SECRET');
    $redirect_uri = env('INSTAGRAM_REDIRECT_URI');
    $code = $input['code'] ?? null;

    if (!$code) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing code']);
        exit;
    }

    $ch = curl_init('https://api.instagram.com/oauth/access_token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $redirect_uri,
        'code' => $code
    ]));
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_status !== 200) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to exchange token']);
        exit;
    }

    $data = json_decode($response, true);
    if (!isset($data['access_token'], $data['user_id'])) {
        http_response_code(500);
        echo json_encode(['error' => 'Invalid Instagram response']);
        exit;
    }

    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare('INSERT INTO instagram_tokens (user_id, ig_user_id, access_token) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE ig_user_id=VALUES(ig_user_id), access_token = VALUES(access_token)');
        $stmt->execute([$data['user_id'], $data['user_id'], $data['access_token']]);

        $profile = instagram_get_profile($data['access_token']);
        if ($profile) {
            $up = $pdo->prepare('UPDATE influencers SET username=?, profile_pic=?, followers_count=?, media_count=? WHERE id=?');
            $up->execute([
                $profile['username'] ?? '',
                $profile['profile_picture_url'] ?? '',
                $profile['followers_count'] ?? 0,
                $profile['media_count'] ?? 0,
                $data['user_id']
            ]);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        exit;
    }

    echo json_encode(['success' => true]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
}
