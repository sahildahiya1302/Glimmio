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

            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                respond(false, 'Email is already registered.');
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)');
            if ($stmt->execute([$email, $password_hash, $role])) {
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

            $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];

                $redirect = ($user['role'] === 'influencer') ? 'influencer-dashboard.php' : 'brand-dashboard.php';
                respond(true, 'Login successful.', $redirect);
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
