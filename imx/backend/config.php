<?php
require_once __DIR__ . '/../includes/env.php';
header('Content-Type: application/json');

echo json_encode([
    'instagramClientId' => env('INSTAGRAM_CLIENT_ID'),
    'instagramRedirect' => env('INSTAGRAM_REDIRECT_URI')
]);

