<?php
require_once 'db.php';
require_once __DIR__ . '/../includes/instagram_api.php';
session_start();

function respond($success, $data = null, $message = '', $redirect = null) {
    header('Content-Type: application/json');
    $resp = ['success' => $success, 'data' => $data, 'message' => $message];
    if ($redirect) $resp['redirect'] = $redirect;
    echo json_encode($resp);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(false, 'Unauthorized');
}

$pdo = db_connect();
$action = $_GET['action'] ?? '';

function badge_rate(PDO $pdo, string $badge): float {
    $stmt = $pdo->prepare('SELECT cpm_rate FROM badge_rates WHERE badge_level=?');
    $stmt->execute([$badge]);
    $r = $stmt->fetchColumn();
    if ($r === false) {
        $def = ['bronze'=>0.50,'silver'=>0.60,'gold'=>0.65,'elite'=>0.75];
        return $def[$badge] ?? 0.50;
    }
    return floatval($r);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'complete_profile') {
    $role = $_POST['role'] ?? '';
    $userId = $_SESSION['user_id'];

    try {
        if ($role === 'influencer') {
            $stmt = $pdo->prepare('UPDATE influencers SET instagram_handle=?, category=?, bio=?, upi_id=?, profile_complete=1 WHERE id=?');
            $stmt->execute([
                $_POST['instagram_handle'] ?? '',
                $_POST['category'] ?? '',
                $_POST['bio'] ?? '',
                $_POST['upi_id'] ?? '',
                $userId
            ]);
        } else {
            $stmt = $pdo->prepare('UPDATE brands SET company_name=?, website=?, industry=?, profile_complete=1 WHERE id=?');
            $stmt->execute([
                $_POST['company_name'] ?? '',
                $_POST['website'] ?? '',
                $_POST['industry'] ?? '',
                $userId
            ]);
        }
        $redirect = ($role === 'influencer') ? '../pages/influencer-dashboard.php' : '../pages/brand-dashboard.php';
        respond(true, null, 'Profile updated.', $redirect);
    } catch (Exception $e) {
        error_log('Profile update error: '.$e->getMessage());
        respond(false, 'Failed to update profile.');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'me') {
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    if ($role === 'influencer') {
        $stmt = $pdo->prepare('SELECT * FROM influencers WHERE id=?');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare('SELECT * FROM brands WHERE id=?');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
    }
    respond(true, $profile, 'ok');
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'overview') {
    $campaignId = $_GET['campaign_id'] ?? null;
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    try {
        if ($role === 'influencer') {
            $metricsSql = 'SELECT SUM(m.reach) as reach, SUM(m.impressions) as impressions, SUM(m.likes) as likes, SUM(m.comments) as comments, SUM(m.shares) as shares, SUM(m.saves) as saves, SUM(m.engagement_total) as engagement FROM metrics m JOIN content_submissions cs ON m.submission_id = cs.id WHERE cs.influencer_id = ?';
            $params = [$userId];
            if ($campaignId) {
                $metricsSql .= ' AND cs.campaign_id = ?';
                $params[] = $campaignId;
            }
            $stmt = $pdo->prepare($metricsSql);
            $stmt->execute($params);
            $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

            $attrSql = 'SELECT event_type, SUM(value_count) as count, SUM(value_sum) as value FROM attribution_summary WHERE influencer_id = ?';
            $params = [$userId];
            if ($campaignId) {
                $attrSql .= ' AND campaign_id = ?';
                $params[] = $campaignId;
            }
            $attrSql .= ' GROUP BY event_type';
            $aStmt = $pdo->prepare($attrSql);
            $aStmt->execute($params);
            $events = $aStmt->fetchAll(PDO::FETCH_ASSOC);
            respond(true, ['metrics' => $metrics, 'events' => $events]);
        } else {
            $metricsSql = 'SELECT SUM(m.reach) as reach, SUM(m.impressions) as impressions, SUM(m.likes) as likes, SUM(m.comments) as comments, SUM(m.shares) as shares, SUM(m.saves) as saves, SUM(m.engagement_total) as engagement FROM metrics m JOIN content_submissions cs ON m.submission_id = cs.id JOIN campaigns c ON cs.campaign_id = c.id WHERE c.brand_id = ?';
            $params = [$userId];
            if ($campaignId) {
                $metricsSql .= ' AND cs.campaign_id = ?';
                $params[] = $campaignId;
            }
            $stmt = $pdo->prepare($metricsSql);
            $stmt->execute($params);
            $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

            $attrSql = 'SELECT event_type, SUM(value_count) as count, SUM(value_sum) as value FROM attribution_summary WHERE campaign_id IN (SELECT id FROM campaigns WHERE brand_id = ?)';
            $params = [$userId];
            if ($campaignId) {
                $attrSql = 'SELECT event_type, SUM(value_count) as count, SUM(value_sum) as value FROM attribution_summary WHERE campaign_id = ? AND campaign_id IN (SELECT id FROM campaigns WHERE brand_id = ?)';
                $params = [$campaignId, $userId];
            }
            $attrSql .= ' GROUP BY event_type';
            $aStmt = $pdo->prepare($attrSql);
            $aStmt->execute($params);
            $events = $aStmt->fetchAll(PDO::FETCH_ASSOC);
            respond(true, ['metrics' => $metrics, 'events' => $events]);
        }
    } catch (Exception $e) {
        error_log('Metrics overview error: ' . $e->getMessage());
        respond(false, null, 'Failed to fetch metrics');
    }
} elseif ($_SERVER['REQUEST_METHOD']==='GET' && $action === 'post_metrics') {
    $subId = $_GET['submission_id'] ?? '';
    if(!$subId) respond(false,null,'Submission required');
    $role = $_SESSION['role'];
    $user = $_SESSION['user_id'];
    $checkSql = $role==='brand'
        ? 'SELECT cs.id,c.rate,c.goal_type,i.badge_level FROM content_submissions cs JOIN campaigns c ON cs.campaign_id=c.id JOIN influencers i ON cs.influencer_id=i.id WHERE cs.id=? AND c.brand_id=?'
        : 'SELECT cs.id,c.rate,c.goal_type,i.badge_level FROM content_submissions cs JOIN campaigns c ON cs.campaign_id=c.id JOIN influencers i ON cs.influencer_id=i.id WHERE cs.id=? AND cs.influencer_id=?';
    $stmt=$pdo->prepare($checkSql);
    $stmt->execute([$subId,$user]);
    $row=$stmt->fetch(PDO::FETCH_ASSOC);
    if(!$row) respond(false,null,'Not found');
    $m=$pdo->prepare('SELECT * FROM metrics WHERE submission_id=?');
    $m->execute([$subId]);
    $metrics=$m->fetch(PDO::FETCH_ASSOC);
    if(!$metrics) $metrics=[];
    $earn=0;
    if($metrics){
        if($row['goal_type']=='CPM'){
            $rate = badge_rate($pdo, $row['badge_level'] ?? 'bronze');
            $earn = ($metrics['impressions']/1000) * $rate;
        }else{
            $earn = $metrics['engagement_total'] * $row['rate'];
        }
    }
    respond(true,['metrics'=>$metrics,'estimated_earnings'=>$earn]);
} elseif ($_SERVER['REQUEST_METHOD']==='GET' && $action === 'projections') {
    if (($_SESSION['role'] ?? '') !== 'brand') {
        respond(false, null, 'Only brands can view projections');
    }
    $brandId = $_SESSION['user_id'];
    $campaignId = $_GET['campaign_id'] ?? null;

    $sql = 'SELECT c.id,c.title,c.goal_type,c.target_metrics,c.start_date,c.end_date, '.
           'SUM(m.impressions) as impressions, SUM(m.engagement_total) as engagement '.
           'FROM campaigns c LEFT JOIN content_submissions cs ON cs.campaign_id=c.id '.
           'LEFT JOIN metrics m ON m.submission_id=cs.id WHERE c.brand_id=?';
    $params = [$brandId];
    if ($campaignId) {
        $sql .= ' AND c.id=?';
        $params[] = $campaignId;
    }
    $sql .= ' GROUP BY c.id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $projections = [];
    foreach ($rows as $r) {
        $start = $r['start_date'] ? new DateTime($r['start_date']) : new DateTime('-7 days');
        $end = $r['end_date'] ? new DateTime($r['end_date']) : new DateTime('+7 days');
        $now = new DateTime();
        $daysElapsed = max(1, $start->diff($now)->days);
        $totalDays = max($daysElapsed, $start->diff($end)->days);
        $metric = ($r['goal_type'] === 'CPM') ? $r['impressions'] : $r['engagement'];
        $projected = ($metric / $daysElapsed) * $totalDays;
        $suggest = '';
        if ($r['target_metrics'] && $projected < $r['target_metrics']) {
            $suggest = 'Projected below target, consider boosting posts or adding influencers';
        } else {
            $suggest = 'On track';
        }
        $projections[] = [
            'campaign_id' => $r['id'],
            'title' => $r['title'],
            'current' => (int)$metric,
            'projected' => (int)round($projected),
            'target' => (int)$r['target_metrics'],
            'suggestion' => $suggest
        ];
    }
    respond(true, $campaignId ? ($projections[0] ?? null) : $projections);
} elseif ($_SERVER['REQUEST_METHOD']==='GET' && $action === 'send_daily_report') {
    if (($_SESSION['role'] ?? '') !== 'brand') {
        respond(false, null, 'Only brands can request report');
    }
    $brandId = $_SESSION['user_id'];
    $stmt = $pdo->prepare('SELECT id,title FROM campaigns WHERE brand_id=? AND status="active"');
    $stmt->execute([$brandId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) respond(false,null,'No active campaigns');
    $report='';
    foreach($rows as $c){
        $m = $pdo->prepare('SELECT SUM(impressions) as imp,SUM(engagement_total) as eng FROM metrics m JOIN content_submissions cs ON m.submission_id=cs.id WHERE cs.campaign_id=?');
        $m->execute([$c['id']]);
        $data=$m->fetch(PDO::FETCH_ASSOC);
        $report .= "<h3>{$c['title']}</h3><p>Impressions: " . ($data['imp']??0) . " | Engagement: " . ($data['eng']??0) . "</p>";
    }
    require_once __DIR__ . '/../includes/mail.php';
    $bemail = $pdo->prepare('SELECT email FROM brands WHERE id=?');
    $bemail->execute([$brandId]);
    $to = $bemail->fetchColumn();
    if ($to && send_mail($to, 'Daily campaign report', $report)) {
        respond(true,null,'Report sent');
    }
    respond(false,null,'Failed to send report');
} else {
    respond(false, 'Invalid request');
}

function fetch_metrics_graph($postId, $token) {
    $url = 'https://graph.facebook.com/v18.0/' . urlencode($postId) . '?fields=impressions,reach,like_count,comments_count,media_type&access_token=' . urlencode($token);
    $resp = @file_get_contents($url);
    if ($resp === false) return null;
    return json_decode($resp, true);
}