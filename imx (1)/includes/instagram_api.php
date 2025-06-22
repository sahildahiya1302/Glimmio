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

function instagram_get_recent_media($token, $count = 10) {
    $url = 'https://graph.instagram.com/me/media?fields=id,media_url,caption,media_type&limit=' . intval($count) . '&access_token=' . urlencode($token);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $resp = curl_exec($ch);
    if (curl_errno($ch)) { curl_close($ch); return null; }
    curl_close($ch);
    return json_decode($resp, true);
}

function instagram_publish_photo($imageUrl, $caption, $token) {
    // Placeholder implementation for posting to Instagram
    $endpoint = 'https://graph.facebook.com/v18.0/me/media';
    $ch = curl_init($endpoint);
    $data = http_build_query(['image_url'=>$imageUrl,'caption'=>$caption,'access_token'=>$token]);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$data]);
    $resp=curl_exec($ch);
    if(curl_errno($ch)){curl_close($ch);return null;}
    curl_close($ch);
    $res=json_decode($resp,true);
    if(!isset($res['id'])) return null;
    $publish=curl_init('https://graph.facebook.com/v18.0/me/media_publish');
    $data=http_build_query(['creation_id'=>$res['id'],'access_token'=>$token]);
    curl_setopt_array($publish,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$data]);
    $pub=curl_exec($publish);
    curl_close($publish);
    return json_decode($pub,true);
}

function instagram_search_top_creators($token, $limit = 10) {
    // Placeholder search for creators the account follows
    $url = 'https://graph.facebook.com/v18.0/me/following?fields=username,followers_count,media_count&limit=' . intval($limit) . '&access_token=' . urlencode($token);
    $ch = curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10]);
    $resp=curl_exec($ch);
    if(curl_errno($ch)){curl_close($ch);return null;}
    curl_close($ch);
    return json_decode($resp,true);
}
?>
