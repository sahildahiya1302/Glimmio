<?php
require_once 'db.php';
require_once __DIR__ . '/../includes/instagram_api.php';
session_start();
require_once __DIR__ . "/../includes/security.php";

function respond($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(false, null, 'Unauthorized.');
}
$role = $_SESSION['role'] ?? '';

$pdo = null;
try {
    $pdo = db_connect();
} catch (Exception $ex) {
    error_log('DB connection error: ' . $ex->getMessage());
    respond(false, null, 'Database connection error.');
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'profile' && $role === 'influencer') {
    try {
        // Fetch influencer profile
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare('SELECT id, email, username, profile_pic FROM influencers WHERE id = ?');
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        if ($profile) {
            respond(true, $profile);
        } else {
            respond(false, null, 'Profile not found.');
        }
    } catch (Exception $ex) {
        error_log('Error fetching profile for user_id ' . $user_id . ': ' . $ex->getMessage());
        respond(false, null, 'Error fetching profile.');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list_campaigns' && $role === 'influencer') {
    try {
        // List active campaigns filtered by influencer metrics
        $user_id = $_SESSION['user_id'];

        $stmt = $pdo->prepare('SELECT badge_level, category FROM influencers WHERE id = ?');
        $stmt->execute([$user_id]);
        $inf = $stmt->fetch();
        $badge = $inf['badge_level'] ?? 'bronze';
        $followers = 0;
        $category = $inf['category'] ?? '';

        $levels = ['bronze'=>1,'silver'=>2,'gold'=>3,'elite'=>4];
        $badgeVal = $levels[$badge] ?? 1;

        $stmt = $pdo->prepare("SELECT *, FIELD(badge_min,'bronze','silver','gold','elite') as lvl FROM campaigns WHERE status='active' AND (min_followers <= ?) AND (lvl <= ? OR badge_min IS NULL) AND (category = ? OR category = '' OR category IS NULL) ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$followers, $badgeVal, $category]);
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add estimated payout based on goal type
        foreach ($campaigns as &$c) {
            if ($c['goal_type'] === 'CPM') {
                $c['estimated_payout'] = ($followers / 1000) * $c['rate'];
            } else {
                $engRate = floatval($inf['engagement_rate'] ?? 0) / 100;
                $expectedEngagements = $followers * $engRate;
                $c['estimated_payout'] = $expectedEngagements * $c['rate'];
            }
        }

        respond(true, $campaigns);
    } catch (Exception $ex) {
        error_log('Error fetching campaigns: ' . $ex->getMessage());
        respond(false, null, 'Error fetching campaigns.');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list_requests' && $role === 'influencer') {
    // List requests made by influencer
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare('SELECT * FROM requests WHERE influencer_uid = ? ORDER BY created_at DESC LIMIT 100');
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll();
    respond(true, $requests);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit_request' && $role === 'influencer') {
    // Submit a new request with reel upload and message
    $user_id = $_SESSION['user_id'];
    require_csrf();
    $campaign_id = $_POST['campaign_id'] ?? '';
    if (!$campaign_id) {
        respond(false, null, 'Campaign ID is required.');
    }

    $message = trim($_POST['message'] ?? '');

    // Handle reel upload (optional)
    $reel_url = null;
    if (isset($_FILES['reel']) && validate_upload($_FILES['reel'], ['video/mp4','video/quicktime'])) {
        $upload_dir = __DIR__ . '/../uploads/reels/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $filename = uniqid() . '_' . basename($_FILES['reel']['name']);
        $target_file = $upload_dir . $filename;
        if (!move_uploaded_file($_FILES['reel']['tmp_name'], $target_file)) {
            respond(false, null, 'Failed to upload reel.');
        }
        $reel_url = '/uploads/reels/' . $filename;
    } elseif (isset($_FILES['reel'])) {
        respond(false, null, 'Invalid reel file.');
    }

    // Insert request
    $stmt = $pdo->prepare('INSERT INTO requests (influencer_uid, campaign_id, message, status, reel_url, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
    try {
        $stmt->execute([$user_id, $campaign_id, $message, 'pending', $reel_url]);
        respond(true, null, 'Request submitted successfully.');
    } catch (Exception $ex) {
        error_log('Error submitting request: ' . $ex->getMessage());
        respond(false, null, 'Failed to submit request.');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'refresh_profile' && $role === 'influencer') {
    $tokenStmt = $pdo->prepare('SELECT access_token FROM instagram_tokens WHERE user_id = ?');
    $tokenStmt->execute([$_SESSION['user_id']]);
    $token = $tokenStmt->fetchColumn();
    if ($token) {
        $profile = instagram_get_profile($token);
        if ($profile) {
            $up = $pdo->prepare('UPDATE influencers SET username=?, profile_pic=?, followers_count=?, media_count=? WHERE id=?');
            $up->execute([
                $profile['username'] ?? '',
                $profile['profile_picture_url'] ?? '',
                $profile['followers_count'] ?? 0,
                $profile['media_count'] ?? 0,
                $_SESSION['user_id']
            ]);
            respond(true, $profile, 'Profile updated');
        }
    }
    respond(false, null, 'Unable to refresh profile');
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list_all' && $role === 'brand') {
    $category = $_GET['category'] ?? null;
    $sql = 'SELECT i.id, i.username, i.email, i.badge_level, i.category, i.followers_count, SUM(m.reach) as reach, SUM(m.engagement_total) as engagement FROM influencers i LEFT JOIN content_submissions cs ON cs.influencer_id = i.id LEFT JOIN metrics m ON m.submission_id = cs.id';
    $params = [];
    if ($category) {
        $sql .= ' WHERE i.category = ?';
        $params[] = $category;
    }
    $sql .= ' GROUP BY i.id ORDER BY i.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond(true, $rows);
} else {
    respond(false, null, 'Invalid request.');
}
?>