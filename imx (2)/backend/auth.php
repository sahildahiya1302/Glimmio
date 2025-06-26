<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once 'db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/jwt_helper.php';
require_once __DIR__ . '/../includes/env.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/mail.php';
secure_session_start();

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

        // Load environment variables
        env('JWT_SECRET'); // ensures .env is parsed

        $pdo = db_connect();

        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            respond(false, 'Invalid CSRF token.');
        }

        if ($action === 'send_otp') {
            $email = $_POST['email'] ?? '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                respond(false, 'Invalid email');
            }
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            if (!isset($_SESSION['otp'])) { $_SESSION['otp'] = []; }
            $_SESSION['otp'][$email] = [
                'hash' => password_hash($otp, PASSWORD_DEFAULT),
                'exp' => time() + 300
            ];
            require_once __DIR__ . '/../includes/mail.php';
            if (send_otp_email($email, $otp)) {
                respond(true, 'OTP sent');
            }
            respond(false, 'Failed to send OTP');
        } elseif ($action === 'register') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $companyName = $_POST['company_name'] ?? '';
            $website = $_POST['website'] ?? '';
            $industry = $_POST['industry'] ?? '';
            $instaHandle = $_POST['instagram_handle'] ?? '';
            $category = $_POST['category'] ?? '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                respond(false, 'Invalid email address.');
            }

            if (strlen($password) < 8) {
                respond(false, 'Password must be at least 8 characters.');
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

            $otp = $_POST['otp'] ?? '';
            if (!isset($_SESSION['otp'][$email]) ||
                $_SESSION['otp'][$email]['exp'] < time() ||
                !password_verify($otp, $_SESSION['otp'][$email]['hash'])) {
                respond(false, 'Invalid or expired OTP');
            }
            unset($_SESSION['otp'][$email]);

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if ($role === 'brand') {
                $stmt = $pdo->prepare("INSERT INTO brands (email, password_hash, company_name, website, industry) VALUES (?, ?, ?, ?, ?)");
                $params = [$email, $password_hash, $companyName, $website, $industry];
            } else {
                $stmt = $pdo->prepare("INSERT INTO influencers (email, password_hash, instagram_handle, category) VALUES (?, ?, ?, ?)");
                $params = [$email, $password_hash, $instaHandle, $category];
            }
            if ($stmt->execute($params)) {
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
            $stmt = $pdo->prepare('SELECT id, password_hash, company_name, email FROM brands WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'brand';
                $_SESSION['username'] = $user['company_name'] ?: explode('@', $user['email'])[0];
                session_regenerate_id(true);
                $token = create_jwt([
                    'uid' => $user['id'],
                    'role' => 'brand',
                    'exp' => time() + 3600
                ], env('JWT_SECRET', 'secret'));
                respond(true, 'Login successful.', '../dashboard.php', ['token' => $token]);
            }

            // Then influencer
            $stmt = $pdo->prepare('SELECT id, password_hash, username, instagram_handle, email FROM influencers WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'influencer';
                $uname = $user['username'] ?: ($user['instagram_handle'] ?: explode('@', $user['email'])[0]);
                $_SESSION['username'] = $uname;
                session_regenerate_id(true);
                $token = create_jwt([
                    'uid' => $user['id'],
                    'role' => 'influencer',
                    'exp' => time() + 3600
                ], env('JWT_SECRET', 'secret'));
                respond(true, 'Login successful.', '../dashboard.php', ['token' => $token]);
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
