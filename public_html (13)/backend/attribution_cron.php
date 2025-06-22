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
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                respond(false, 'Email is already registered.');
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if (!in_array($role, ['brand', 'influencer', 'admin'])) {
                $role = 'brand';
            }
            $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, profile_complete) VALUES (?, ?, ?, 0)');
            if ($stmt->execute([$email, $password_hash, $role])) {
                $userId = $pdo->lastInsertId();
                if ($role === 'brand') {
                    $stmtProfile = $pdo->prepare('INSERT INTO brands (user_id, email) VALUES (?, ?)');
                    $stmtProfile->execute([$userId, $email]);
                } else {
                    $stmtProfile = $pdo->prepare('INSERT INTO influencers (user_id, email) VALUES (?, ?)');
                    $stmtProfile->execute([$userId, $email]);
                }
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

            $stmt = $pdo->prepare('SELECT id, password_hash, role, profile_complete FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];

                if (!$user['profile_complete']) {
                    $redirect = '/pages/onboarding.php';
                } else {
                    if ($user['role'] === 'influencer') {
                        $redirect = '/pages/influencer-dashboard.php';
                    } elseif ($user['role'] === 'admin') {
                        $redirect = '/pages/admin-dashboard.php';
                    } else {
                        $redirect = '/pages/brand-dashboard.php';
                    }
                }
                respond(true, 'Login successful.', $redirect, ['role' => $user['role']]);
            } else {
                respond(false, 'Invalid email or password.');
            }

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