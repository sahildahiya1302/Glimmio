<?php
require_once 'db.php';
session_start();

function respond($success, $message, $redirect = null, array $extra = []) {
    header('Content-Type: application/json');
    $response = ['success' => $success, 'message' => $message];
    if ($redirect) {
        $response['redirect'] = $redirect;
    }
    if (!empty($extra)) {
        foreach ($extra as $k => $v) {
            $response[$k] = $v;
        }
    }
    echo json_encode($response);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        // Load environment variables from .env
        if (file_exists(__DIR__ . '/../../.env')) {
            $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                $_ENV[$name] = $value;
            }
        }

        $pdo = db_connect();

        if ($action === 'register') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['type'] ?? 'brand';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                respond(false, 'Invalid email address.');
            }

            if (strlen($password) < 6) {
                respond(false, 'Password must be at least 6 characters.');
            }

            $table = $role === 'brand' ? 'brands' : ($role === 'admin' ? 'brands' : 'influencers');
            $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                respond(false, 'Email is already registered.');
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if (!in_array($role, ['brand', 'influencer', 'admin'])) {
                $role = 'brand';
            }
            $stmt = $pdo->prepare("INSERT INTO {$table} (email, password_hash) VALUES (?, ?)");
            if ($stmt->execute([$email, $password_hash])) {
                $userId = $pdo->lastInsertId();
                // create wallet for the user
                $walletStmt = $pdo->prepare('INSERT INTO wallets (user_id, wallet_type) VALUES (?, ?)');
                $walletStmt->execute([$userId, $role]);

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
            $stmt = $pdo->prepare('SELECT id, password_hash, profile_complete FROM brands WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'brand';
                $redirect = $user['profile_complete'] ? '/pages/brand-dashboard.php' : '/pages/onboarding.php';
                respond(true, 'Login successful.', $redirect, ['role' => 'brand']);
            }

            // Then influencer
            $stmt = $pdo->prepare('SELECT id, password_hash, profile_complete FROM influencers WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'influencer';
                $redirect = $user['profile_complete'] ? '/pages/influencer-dashboard.php' : '/pages/onboarding.php';
                respond(true, 'Login successful.', $redirect, ['role' => 'influencer']);
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