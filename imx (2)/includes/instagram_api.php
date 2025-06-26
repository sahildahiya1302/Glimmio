<?php
function instagram_cached_request(string $url, int $ttl = 3600) {
    $cacheDir = __DIR__ . '/../cache/';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    $hash = md5($url);
    $file = $cacheDir . $hash . '.json';
    if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
        return json_decode(file_get_contents($file), true);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    if (!curl_errno($ch)) {
        file_put_contents($file, $resp);
    }
    curl_close($ch);
    return json_decode($resp, true);
}

function instagram_get_profile($token) {
    $url = 'https://graph.instagram.com/me?fields=id,username,followers_count,media_count,profile_picture_url&access_token=' . urlencode($token);
    $data = instagram_cached_request($url, 3600);
    return $data ?? null;
}

function instagram_get_insights($igUserId, $token) {
    $metrics = 'impressions,reach,profile_views,website_clicks,follower_count';
    $url = 'https://graph.facebook.com/v18.0/' . urlencode($igUserId) . '/insights?metric=' . $metrics . '&period=lifetime&access_token=' . urlencode($token);
    return instagram_cached_request($url, 3600);
}

function instagram_get_top_media($igUserId, $token) {
    $url = 'https://graph.facebook.com/v18.0/' . urlencode($igUserId) . '/media?fields=id,caption,media_url,like_count,comments_count,timestamp&limit=5&access_token=' . urlencode($token);
    return instagram_cached_request($url, 600);
}
?>
