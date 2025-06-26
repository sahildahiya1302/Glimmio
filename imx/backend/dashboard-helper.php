<?php
require_once __DIR__ . '/db.php';

function load_influencer_data(string $userId): ?array {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT username, followers_count, media_count FROM influencers WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }
}
?>