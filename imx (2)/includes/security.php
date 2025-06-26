<?php
require_once __DIR__ . '/csrf.php';

function secure_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

function require_csrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
    if (!verify_csrf_token($token)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'CSRF validation failed']);
        exit;
    }
}

function sanitize_text(string $key): string {
    return filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING) ?? '';
}

function validate_upload(array $file, array $allowedTypes, int $maxSize = 5242880): bool {
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxSize) {
        return false;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    return in_array($mime, $allowedTypes, true);
}

function secure_page_headers(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:");
}
