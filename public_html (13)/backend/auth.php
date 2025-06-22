<?php
require_once 'db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/jwt_helper.php';
require_once __DIR__ . '/../includes/env.php';
session_start();

function respond($success, $message, $redirect = null, array $extra = []) {
    header('Content-Type: application/json');
    $response = ['success' => $success, 'message' => $message] + $extra;
    if ($redirect) {
        $response['redirect'] = $redirect;
    }
    echo json_encode($response);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'csrf') {
        $token = generate_csrf_token();
        header('Content-Type: application/json');
        echo json_encode(['token' => $token]);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $action = $_POST['action'] ?? '';

        // Load environment variables
        env('JWT_SECRET'); // ensures .env is parsed

        $pdo = db_connect();

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            respond(false, 'Invalid CSRF token.');
        }

        if ($action === 'register') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                respond(false, 'Invalid email address.');
            }

            if (strlen($password) < 6) {
                respond(false, 'Password must be at least 6 characters.');
            }

            // Validate role from param, default to brand if invalid
            if ($role !== 'brand' && $role !== 'influencer') {
                respond(false, 'Invalid role specified.');
            }

            $table = $role === 'brand' ? 'brands' : 'influencers';
            $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                respond(false, 'Email is already registered.');
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO {$table} (email, password_hash) VALUES (?, ?)");
            if ($stmt->execute([$email, $password_hash])) {
                respond(true, 'Registration successful. You can now log in.');
            } else {
                respond(false, 'Registration failed. Please try again.');
            }
        } elseif ($action === 'login') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                respond(false, 'Invalid email address.');
            }

            // Check brand first
            $stmt = $pdo->prepare('SELECT id, password_hash FROM brands WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'brand';
                $token = create_jwt([
                    'uid' => $user['id'],
                    'role' => 'brand',
                    'exp' => time() + 3600
                ], env('JWT_SECRET', 'secret'));
                respond(true, 'Login successful.', 'brand-dashboard.php', ['token' => $token]);
            }

            // Then influencer
            $stmt = $pdo->prepare('SELECT id, password_hash FROM influencers WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'influencer';
                $token = create_jwt([
                    'uid' => $user['id'],
                    'role' => 'influencer',
                    'exp' => time() + 3600
                ], env('JWT_SECRET', 'secret'));
                respond(true, 'Login successful.', 'influencer-dashboard.php', ['token' => $token]);
            }

            respond(false, 'Invalid email or password.');
        } else {
            respond(false, 'Invalid action.');
        }
    } else {
        respond(false, 'Invalid request method.');
    }
} catch (Exception $e) {
    error_log('Auth error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    respond(false, 'Server error. Please try again later.');
}
