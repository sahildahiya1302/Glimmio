<?php
require_once 'db.php';
session_start();

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
    $stmt = $pdo->prepare('SELECT b.id, b.name AS company_name, b.email, b.profile_pic AS logo_url, b.gstin, b.industry, b.website FROM brands b WHERE b.user_id = ?');
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    if ($profile) {
        respond(true, $profile);
    } else {
        respond(false, null, 'Profile not found.');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_profile') {
    // Update brand profile
    $user_id = $_SESSION['user_id'];
    $company_name = $_POST['company_name'] ?? '';
    $website = $_POST['website'] ?? '';
    $gstin = $_POST['gstin'] ?? '';
    $industry = $_POST['industry'] ?? '';
    $email = $_POST['email'] ?? '';
    // For logo upload, handle file upload if provided
    $logo_url = null;

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
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
    }

    // Check if profile exists
    $stmt = $pdo->prepare('SELECT id FROM brands WHERE user_id = ?');
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
        $sql .= ' WHERE user_id = ?';
        $params[] = $user_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            respond(true, null, 'Profile updated successfully.');
        } else {
            respond(false, null, 'Failed to update profile.');
        }
    } else {
        // Insert new profile
        $stmt = $pdo->prepare('INSERT INTO brands (user_id, name, website, gstin, industry, email, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?)');
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
