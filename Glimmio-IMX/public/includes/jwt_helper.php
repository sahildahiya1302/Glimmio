<?php
function create_jwt(array $payload, string $secret): string {
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payloadEncoded = base64_encode(json_encode($payload));
    $signature = base64_encode(hash_hmac('sha256', "$header.$payloadEncoded", $secret, true));
    return "$header.$payloadEncoded.$signature";
}

function verify_jwt(string $token, string $secret) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    list($header, $payload, $signature) = $parts;
    $expected = base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true));
    if (!hash_equals($expected, $signature)) {
        return false;
    }
    return json_decode(base64_decode($payload), true);
}

