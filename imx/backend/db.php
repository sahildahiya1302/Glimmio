<?php
function load_env() {
    $envPath = __DIR__ . '/../../.env';
    if (!file_exists($envPath)) {
        return;
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function db_connect(): PDO {
    load_env();
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $db   = $_ENV['DB_NAME'] ?? 'glimmio';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
?>