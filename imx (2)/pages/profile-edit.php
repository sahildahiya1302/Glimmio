<?php
require_once __DIR__ . '/../includes/security.php';
secure_session_start();
secure_page_headers();
if(!isset($_SESSION['user_id'])){header('Location: login.html');exit;}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Profile</title>
    <link rel="stylesheet" href="/../css/dashboard-style.css" />
    <link rel="stylesheet" href="/../css/instagram-theme.css" />
    <link rel="stylesheet" href="/../css/dark-theme.css" />
</head>
<body>
<header class="top-bar">
    <div class="logo">Glimmio</div>
    <div id="page-title">Edit Profile</div>
    <div class="top-icons"><a href="../dashboard.php">🏠</a></div>
</header>
<form id="edit-form" style="padding:20px;max-width:400px;margin:0 auto;" enctype="multipart/form-data">
    <input type="text" name="username" id="username" placeholder="Username" />
    <input type="text" name="category" id="category" placeholder="Category" />
    <textarea name="bio" id="bio" placeholder="Bio" rows="4"></textarea>
    <input type="text" name="upi_id" id="upi" placeholder="UPI ID" />
    <input type="file" name="profile_pic" id="profile_pic" />
    <input type="hidden" name="csrf_token" id="csrf_token" />
    <button type="submit">Save</button>
</form>
<script>
const role = '<?php echo $_SESSION["role"] ?? ""; ?>';
fetch('/backend/auth.php?action=csrf').then(r=>r.json()).then(d=>{
    document.getElementById('csrf_token').value=d.token;
});

document.getElementById('edit-form').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const res = await fetch('/backend/'+(role==='brand'?'brand':'influencer')+'.php?action=update_profile', {
        method: 'POST',
        body: fd
    });
    const data = await res.json();
    alert(data.message);
    if(data.success){ window.location.href = 'profile.php'; }
});
</script>
</body>
</html>
