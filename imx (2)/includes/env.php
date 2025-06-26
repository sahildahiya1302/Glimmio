<?php
function env(string $key, $default = null) {
    if (!isset($_ENV[$key])) {
        $envPath = __DIR__ . '/../../.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                if (!isset($_ENV[$name])) {
                    $_ENV[$name] = trim($value);
                }
            }
        }
    }
    return $_ENV[$key] ?? $default;
}
?>
