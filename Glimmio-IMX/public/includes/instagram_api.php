<?php
function instagram_get_profile($token) {
    $url = 'https://graph.instagram.com/me?fields=id,username,followers_count,media_count,profile_picture_url&access_token=' . urlencode($token);
    $resp = @file_get_contents($url);
    if ($resp === false) return null;
    $data = json_decode($resp, true);
    return $data ?? null;
}

function instagram_get_insights($igUserId, $token) {
    $metrics = 'impressions,reach,profile_views,website_clicks,follower_count';
    $url = 'https://graph.facebook.com/v18.0/' . urlencode($igUserId) . '/insights?metric=' . $metrics . '&period=lifetime&access_token=' . urlencode($token);
    $resp = @file_get_contents($url);
    if ($resp === false) return null;
    return json_decode($resp, true);
}
?>
