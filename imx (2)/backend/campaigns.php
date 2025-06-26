<?php
require_once 'db.php';
session_start();

function respond($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    respond(false, null, 'Unauthorized. Please log in.');
}

$pdo = null;
try {
    $pdo = db_connect();
} catch (Exception $ex) {
    error_log('DB connection error: ' . $ex->getMessage());
    respond(false, null, 'Database connection error.');
}

function get_commission(PDO $pdo, $brandId) {
    $stmt = $pdo->prepare("SELECT commission_percent FROM commissions WHERE level='brand' AND reference_id=? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$brandId]);
    $c = $stmt->fetchColumn();
    if ($c !== false) return floatval($c);
    $g = $pdo->query("SELECT commission_percent FROM commissions WHERE level='global' ORDER BY id DESC LIMIT 1")->fetchColumn();
    return $g !== false ? floatval($g) : 20.0;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'post_campaign') {
    require_csrf();
    // Handle campaign posting
    $brand_id = $_SESSION['user_id'];

    // Ensure brand has sufficient wallet balance
    $w = $pdo->prepare('SELECT id, balance FROM wallets WHERE user_id = ? AND wallet_type = ?');
    $w->execute([$brand_id, 'brand']);
    $wallet = $w->fetch();
    if (!$wallet) {
        respond(false, null, 'Wallet not found.');
    }
    // Validate inputs
    $title = sanitize_text("title");
    $objective = sanitize_text("objective");
    $description = sanitize_text("description");
    $category = sanitize_text("category");
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $goal_type = $_POST['goal_type'] ?? '';
    $rate = floatval($_POST['rate'] ?? 0);
    $budget_total = floatval($_POST['budget_total'] ?? 0);

    $target_metrics = intval($_POST['target_metrics'] ?? 0);
    $min_followers = intval($_POST['min_followers'] ?? 0);
    $badge_min = $_POST['badge_min'] ?? 'bronze';
    $max_influencers = $_POST['max_influencers'] !== '' ? intval($_POST['max_influencers']) : null;
    $commission_percent = isset($_POST['commission_percent']) ? floatval($_POST['commission_percent']) : get_commission($pdo, $brand_id);

    // Calculate influencer payout excluding commission
    $influencer_payout_total = $budget_total * (1 - $commission_percent / 100);

    if (!$title || !$goal_type || !$rate || !$budget_total) {
        respond(false, null, 'Missing required fields.');
    }

    if ($wallet['balance'] < $budget_total) {
        respond(false, null, 'Insufficient wallet balance.');
    }

    // Handle image upload if exists
    $image_url = null;
    if (isset($_FILES['image']) && validate_upload($_FILES['image'], ['image/jpeg','image/png','image/gif'])) {
        $upload_dir = __DIR__ . '/../uploads/campaigns/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $filename = uniqid() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_url = '/uploads/campaigns/' . $filename;
        } else {
            respond(false, null, 'Failed to upload image.');
        }
    } elseif (isset($_FILES['image'])) {
        respond(false, null, 'Invalid image uploaded.');
    }

    // Insert campaign into database
    $stmt = $pdo->prepare('INSERT INTO campaigns (brand_id, title, objective, description, category, min_followers, badge_min, max_influencers, start_date, end_date, goal_type, rate, target_metrics, budget_total, commission_percent, influencer_payout_total, image_url, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $status = 'active';
    try {
        $stmt->execute([
            $brand_id,
            $title,
            $objective,
            $description,
            $category,
            $min_followers,
            $badge_min,
            $max_influencers,
            $start_date,
            $end_date,
            $goal_type,
            $rate,
            $target_metrics,
            $budget_total,
            $commission_percent,
            $influencer_payout_total,
            $image_url,
            $status
        ]);

        $campaignId = $pdo->lastInsertId();

        // Handle creative brief data
        $brief_file_url = null;
        if (isset($_FILES['brief_file']) && validate_upload($_FILES['brief_file'], ['application/pdf','image/jpeg','image/png'])) {
            $brief_dir = __DIR__ . '/../uploads/campaigns/';
            if (!is_dir($brief_dir)) {
                mkdir($brief_dir, 0755, true);
            }
            $bfname = uniqid() . '_' . basename($_FILES['brief_file']['name']);
            $btarget = $brief_dir . $bfname;
            if (move_uploaded_file($_FILES['brief_file']['tmp_name'], $btarget)) {
                $brief_file_url = '/uploads/campaigns/' . $bfname;
            }
        } elseif (isset($_FILES['brief_file'])) {
            respond(false, null, 'Invalid brief file.');
        }

        $guidelines = $_POST['guidelines'] ?? null;
        $hashtags_required = $_POST['hashtags_required'] ?? null;
        $caption_examples = $_POST['caption_examples'] ?? null;

        $stmtBrief = $pdo->prepare('INSERT INTO campaign_briefs (campaign_id, file_url, guidelines, hashtags_required, caption_examples) VALUES (?, ?, ?, ?, ?)');
        $stmtBrief->execute([$campaignId, $brief_file_url, $guidelines, $hashtags_required, $caption_examples]);

        // Deduct budget from wallet and hold influencer payout
        $pdo->prepare('UPDATE wallets SET balance = balance - ?, on_hold = on_hold + ? WHERE id = ?')
            ->execute([$budget_total, $influencer_payout_total, $wallet["id"]]);

        $platform_share = $budget_total - $influencer_payout_total;
        $txn = $pdo->prepare('INSERT INTO transactions (wallet_id, campaign_id, amount, type, description, platform_share, influencer_payout, brand_payment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $txn->execute([
            $wallet['id'],
            $campaignId,
            -$budget_total,
            'campaign_charge',
            'Budget reserved for campaign',
            $platform_share,
            $influencer_payout_total,
            $budget_total
        ]);

        respond(true, null, 'Campaign posted successfully.');
    } catch (Exception $ex) {
        error_log('Error inserting campaign: ' . $ex->getMessage());
        respond(false, null, 'Failed to post campaign.');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'end_campaign') {
    require_csrf();
    $campaign_id = $_POST['campaign_id'] ?? '';
    if (!$campaign_id) {
        respond(false, null, 'Campaign ID missing');
    }
    $stmt = $pdo->prepare('UPDATE campaigns SET status = ? WHERE id = ? AND brand_id = ?');
    if ($stmt->execute(['completed', $campaign_id, $_SESSION['user_id']])) {
        $w = $pdo->prepare('SELECT id, on_hold FROM wallets WHERE user_id = ? AND wallet_type = ?');
        $w->execute([$_SESSION['user_id'], 'brand']);
        $wallet = $w->fetch();
        if ($wallet && $wallet['on_hold'] > 0) {
            $pdo->prepare('UPDATE wallets SET balance = balance + on_hold, on_hold = 0 WHERE id = ?')->execute([$wallet['id']]);
            $pdo->prepare('INSERT INTO transactions (wallet_id, campaign_id, amount, type, description, platform_share, influencer_payout, brand_payment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$wallet['id'], $campaign_id, $wallet['on_hold'], 'refund', 'Unused budget refunded', 0, 0, $wallet['on_hold']]);
        }
        respond(true, null, 'Campaign ended');
    } else {
        respond(false, null, 'Failed to update campaign');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list_campaigns') {
    // List campaigns for logged in brand
    $brand_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare('SELECT * FROM campaigns WHERE brand_id = ? ORDER BY created_at DESC LIMIT 100');
    $stmt->execute([$brand_id]);
    $campaigns = $stmt->fetchAll();
    respond(true, $campaigns);
} else {
    respond(false, null, 'Invalid request.');
}
?>