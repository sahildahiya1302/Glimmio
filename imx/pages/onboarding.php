<?php
require_once __DIR__ . '/../includes/security.php';
secure_session_start();
secure_page_headers();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
$role = $_SESSION['role'] ?? 'brand';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding</title>
    <link rel="stylesheet" href="../css/login-style.css">
    <link rel="stylesheet" href="../css/instagram-theme.css">
</head>
<body>
    <h1>Complete Your Profile</h1>
    <form id="onboarding-form">
<?php if ($role === 'influencer'): ?>
        <input type="text" name="instagram_handle" placeholder="Instagram Handle" required><br>
        <input type="text" name="category" placeholder="Category" required><br>
        <textarea name="bio" placeholder="Bio"></textarea><br>
        <input type="text" name="upi_id" placeholder="UPI ID"><br>
<?php else: ?>
        <input type="text" name="company_name" placeholder="Company Name" required><br>
        <input type="text" name="website" placeholder="Website"><br>
        <input type="text" name="industry" placeholder="Industry"><br>
<?php endif; ?>
        <button type="submit">Save Profile</button>
    </form>
<script>
    document.getElementById('onboarding-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('role', '<?php echo $role; ?>');
        const response = await fetch('../backend/metrics.php?action=complete_profile', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        if (result.success && result.redirect) {
            window.location.href = result.redirect;
        }
    });
</script>
</body>
</html>