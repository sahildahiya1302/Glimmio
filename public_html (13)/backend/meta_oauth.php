<?php
require_once 'db.php';
session_start();

function respond($success, $message, $redirect = null) {
    header('Content-Type: application/json');
    $response = ['success' => $success, 'message' => $message];
    if ($redirect) {
        $response['redirect'] = $redirect;
    }
    echo json_encode($response);
    exit;
}

// This endpoint handles the Meta OAuth callback and token exchange
// Expected POST params: action=callback, code, state (contains role info)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

$action = $_POST['action'] ?? '';
if ($action !== 'callback') {
    respond(false, 'Invalid action.');
}

$code = $_POST['code'] ?? '';
$state = $_POST['state'] ?? ''; // state should contain role info: brand or influencer

if (!$code || !$state) {
    respond(false, 'Missing code or state.');
}

$role = $state;
if ($role !== 'brand' && $role !== 'influencer') {
    respond(false, 'Invalid role in state.');
}

// Load environment variables for Meta OAuth
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[$name] = $value;
    }
}

$client_id = $_ENV['META_APP_ID'] ?? '';
$client_secret = $_ENV['META_APP_SECRET'] ?? '';
$redirect_uri = $_ENV['META_REDIRECT_URI'] ?? '';

if (!$client_id || !$client_secret || !$redirect_uri) {
    respond(false, 'OAuth configuration missing.');
}

// Exchange code for access token
$token_url = "https://graph.facebook.com/v15.0/oauth/access_token?" . http_build_query([
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'client_secret' => $client_secret,
    'code' => $code,
]);

$token_response = file_get_contents($token_url);
if ($token_response === false) {
    respond(false, 'Failed to get access token.');
}

$token_data = json_decode($token_response, true);
if (!isset($token_data['access_token'])) {
    respond(false, 'Access token not found in response.');
}

$access_token = $token_data['access_token'];

// Use access token to get user info from Meta Graph API
$user_info_url = "https://graph.facebook.com/me?fields=id,name,email&access_token=" . urlencode($access_token);
$user_info_response = file_get_contents($user_info_url);
if ($user_info_response === false) {
    respond(false, 'Failed to get user info.');
}

$user_info = json_decode($user_info_response, true);
if (!isset($user_info['id'])) {
    respond(false, 'User ID not found in user info.');
}

$meta_user_id = $user_info['id'];
$email = $user_info['email'] ?? '';
$name = $user_info['name'] ?? '';

try {
    $pdo = db_connect();

    // Check if meta_user_id already linked to a user
    $stmt = $pdo->prepare('SELECT id, role FROM users WHERE meta_user_id = ?');
    $stmt->execute([$meta_user_id]);
    $existing_user = $stmt->fetch();

    if ($existing_user) {
        // User exists, check role matches
        if ($existing_user['role'] !== $role) {
            respond(false, 'Meta account linked to different role.');
        }
        // Log user in
        $_SESSION['user_id'] = $existing_user['id'];
        $_SESSION['role'] = $existing_user['role'];
        $redirect = ($role === 'influencer') ? 'influencer-dashboard.php' : 'brand-dashboard.php';
        respond(true, 'Login successful.', $redirect);
    } else {
        // Register new user with meta_user_id
        // Check if email already exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            respond(false, 'Email already registered.');
        }

        $stmt = $pdo->prepare('INSERT INTO users (email, role, meta_user_id) VALUES (?, ?, ?)');
        if ($stmt->execute([$email, $role, $meta_user_id])) {
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;
            $redirect = ($role === 'influencer') ? 'influencer-dashboard.php' : 'brand-dashboard.php';
            respond(true, 'Registration and login successful.', $redirect);
        } else {
            respond(false, 'Failed to register user.');
        }
    }
} catch (Exception $ex) {
    error_log('Meta OAuth error: ' . $ex->getMessage());
    respond(false, 'Server error during OAuth process.');
}
?>
