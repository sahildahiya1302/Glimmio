<?php
function instagram_get_profile($token) {
    $url = 'https://graph.instagram.com/me?fields=id,username,followers_count,media_count,profile_picture_url&access_token=' . urlencode($token);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    if (curl_errno($ch)) {
        curl_close($ch);
        return null;
    }
    curl_close($ch);
    $data = json_decode($resp, true);
    return $data ?? null;
}

function instagram_get_insights($igUserId, $token) {
    $metrics = 'impressions,reach,profile_views,website_clicks,follower_count';
    $url = 'https://graph.facebook.com/v18.0/' . urlencode($igUserId) . '/insights?metric=' . $metrics . '&period=lifetime&access_token=' . urlencode($token);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    if (curl_errno($ch)) {
        curl_close($ch);
        return null;
    }
    curl_close($ch);
    return json_decode($resp, true);
}
?>
