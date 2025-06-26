<?php
require_once __DIR__ . '/../includes/security.php';
secure_session_start();
secure_page_headers();
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin'){
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/../css/brand-dashboard-style.css" />
    <link rel="stylesheet" href="/../css/admin-dashboard-style.css" />
    <link rel="stylesheet" href="/../css/instagram-theme.css" />
</head>
<body>
    <div class="dashboard-container">
        <h1>Admin Dashboard</h1>
        <section id="users">
            <h2>Users</h2>
            <table id="user-table">
                <thead><tr><th>ID</th><th>Email</th><th>Role</th><th>Badge</th><th>Joined</th><th>Change Role</th></tr></thead>
                <tbody></tbody>
            </table>
        </section>
        <section id="campaigns">
            <h2>Campaigns</h2>
            <table id="campaign-table">
                <thead><tr><th>ID</th><th>Title</th><th>Status</th><th>Budget</th><th>Commission%</th><th>Brand</th></tr></thead>
                <tbody></tbody>
            </table>
        </section>
        <section id="requests">
            <h2>Requests</h2>
            <table id="request-table">
                <thead><tr><th>ID</th><th>Campaign</th><th>Influencer</th><th>Status</th><th>Created</th></tr></thead>
                <tbody></tbody>
            </table>
        </section>
        <section id="submissions">
            <h2>Content Submissions</h2>
            <table id="submission-table">
                <thead><tr><th>ID</th><th>Campaign</th><th>Influencer</th><th>Status</th><th>Created</th></tr></thead>
                <tbody></tbody>
            </table>
        </section>
    </div>

<script>
async function loadAccounts() {
    const res = await fetch('/backend/admin.php?action=list_accounts');
    const data = await res.json();
    const tbody = document.querySelector('#user-table tbody');
    tbody.innerHTML = '';
    if (data.success) {
        data.data.forEach(u => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${u.id}</td><td>${u.email}</td><td>${u.role}</td><td>${u.badge_level||''}</td><td>${u.created_at}</td>` +
            `<td><select data-id="${u.id}"><option value="bronze">Bronze</option><option value="silver">Silver</option><option value="gold">Gold</option></select></td>`;
            tr.querySelector('select').value = u.badge_level;
            tr.querySelector('select').addEventListener('change', async e => {
                await fetch('/backend/admin.php?action=set_badge', {method:'POST', body:new URLSearchParams({user_id:u.id, role:u.role, badge:e.target.value})});
            });
            tbody.appendChild(tr);
        });
    }
}

async function loadCampaigns() {
    const res = await fetch('/backend/admin.php?action=list_campaigns');
    const data = await res.json();
    const tbody = document.querySelector('#campaign-table tbody');
    tbody.innerHTML = '';
    if (data.success) {
        data.data.forEach(c => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${c.id}</td><td>${c.title}</td><td>${c.status}</td><td>${c.budget_total}</td><td>${c.commission_percent}</td><td>${c.brand_email}</td>`;
            tbody.appendChild(tr);
        });
    }
}

async function loadRequests() {
    const res = await fetch('/backend/admin.php?action=list_requests');
    const data = await res.json();
    const tbody = document.querySelector('#request-table tbody');
    tbody.innerHTML = '';
    if (data.success) {
        data.data.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${r.id}</td><td>${r.title}</td><td>${r.influencer_email}</td><td>${r.status}</td><td>${r.created_at}</td>`;
            tbody.appendChild(tr);
        });
    }
}

async function loadSubmissions() {
    const res = await fetch('/backend/admin.php?action=list_submissions');
    const data = await res.json();
    const tbody = document.querySelector('#submission-table tbody');
    tbody.innerHTML = '';
    if (data.success) {
        data.data.forEach(s => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${s.id}</td><td>${s.title}</td><td>${s.influencer_email}</td><td>${s.status}</td><td>${s.created_at}</td>`;
            tbody.appendChild(tr);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadAccounts();
    loadCampaigns();
    loadRequests();
    loadSubmissions();
});
</script>
</body>
</html>