<?php
function instagram_get_profile($token) {
    $url = 'https://graph.instagram.com/me?fields=id,username,followers_count,media_count,profile_picture_url&access_token=' . urlencode($token);
    $resp = @file_get_contents($url);
    if ($resp === false) return null;
    $data = json_decode($resp, true);
    return $data ?? null;
}
?>
