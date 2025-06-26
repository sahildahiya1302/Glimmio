<?php
require_once 'db.php';
require_once __DIR__ . '/../includes/security.php';
secure_session_start();

function respond($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// Check if user is logged in and role is brand
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'brand') {
    respond(false, null, 'Unauthorized. Please log in as a brand.');
}

$pdo = null;
try {
    $pdo = db_connect();
} catch (Exception $ex) {
    error_log('DB connection error: ' . $ex->getMessage());
    respond(false, null, 'Database connection error.');
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'profile') {
    // Fetch brand profile
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare('SELECT id, name AS company_name, email, profile_pic AS logo_url, gstin, industry, website FROM brands WHERE id = ?');
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    if ($profile && empty($profile['company_name'])) {
        $profile['company_name'] = explode('@', $profile['email'])[0];
    }
    if ($profile) {
        respond(true, $profile);
    } else {
        respond(false, null, 'Profile not found.');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_profile') {
    require_csrf();
    // Update brand profile
    $user_id = $_SESSION['user_id'];
    $company_name = sanitize_text('company_name');
    $website = sanitize_text('website');
    $gstin = sanitize_text('gstin');
    $industry = sanitize_text('industry');
    // For logo upload, handle file upload if provided
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';

    $logo_url = null;

    if (isset($_FILES['logo']) && validate_upload($_FILES['logo'], ['image/jpeg','image/png'])) {
        $upload_dir = __DIR__ . '/../uploads/brands/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $filename = uniqid() . '_' . basename($_FILES['logo']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
            $logo_url = '/uploads/brands/' . $filename;
        } else {
            respond(false, null, 'Failed to upload logo.');
        }
    } elseif (isset($_FILES['logo'])) {
        respond(false, null, 'Invalid logo file.');
    }

    // Check if profile exists
    $stmt = $pdo->prepare('SELECT id FROM brands WHERE id = ?');
    $stmt->execute([$user_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Update existing profile
        $sql = 'UPDATE brands SET name = ?, website = ?, gstin = ?, industry = ?, email = ?';
        $params = [$company_name, $website, $gstin, $industry, $email];
        if ($logo_url) {
            $sql .= ', profile_pic = ?';
            $params[] = $logo_url;
        }
        $sql .= ' WHERE id = ?';
        $params[] = $user_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            respond(true, null, 'Profile updated successfully.');
        } else {
            respond(false, null, 'Failed to update profile.');
        }
    } else {
        // Insert new profile
        $stmt = $pdo->prepare('INSERT INTO brands (id, name, website, gstin, industry, email, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if ($stmt->execute([$user_id, $company_name, $website, $gstin, $industry, $email, $logo_url])) {
            respond(true, null, 'Profile created successfully.');
        } else {
            respond(false, null, 'Failed to create profile.');
        }
    }
} else {
    respond(false, null, 'Invalid request.');
}
?>
