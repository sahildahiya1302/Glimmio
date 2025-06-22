<?php
session_start();
if(!isset($_SESSION['user_id'])){header('Location: login.html');exit;}
$role=$_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@400;600&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/../css/dashboard-style.css" />
    <link rel="stylesheet" href="/../css/brand-dashboard-style.css" />
    <link rel="stylesheet" href="/../css/influencer-dashboard-style.css" />
    <link rel="stylesheet" href="/../css/feed-style.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header class="top-bar">
    <div class="logo">Glimmio</div>
    <div id="page-title">Feed</div>
    <div class="top-icons">
        <a href="#" id="notif-btn">🔔</a>
        <a href="dm.php">✉️</a>
    </div>
</header>
<div class="dashboard-container">
    <div class="sidebar">
        <h2 style="display:none;">Dashboard</h2>
        <ul>
            <li><a href="#feed" class="tablink" data-title="Feed">🏠</a></li>
            <li><a href="#explore" class="tablink" data-title="Explore">🔍</a></li>
            <li><button id="add-post-btn" class="add-post" data-title="Post">➕</button></li>
            <li><a href="#ads" class="tablink" data-title="Campaigns">📣</a></li>
            <li><a href="profile.php" data-title="Profile">👤</a></li>
            <li><a href="dm.php" data-title="DM">✉️</a></li>
        </ul>
    </div>
    <div class="main-content">
        <section id="feed"></section>
        <section id="ads" style="display:none;"></section>
        <section id="explore" style="display:none;"></section>
    </div>
</div>
<nav class="nav-bottom">
    <a href="#feed" class="tablink" data-title="Feed">🏠</a>
    <a href="#explore" class="tablink" data-title="Explore">🔍</a>
    <a href="#" id="add-post-btn-mobile" data-title="Post" class="fab">➕</a>
    <a href="#ads" class="tablink" data-title="Campaigns">📣</a>
    <a href="profile.php" data-title="Profile">👤</a>
</nav>
<script>
const role='<?php echo $role;?>';
async function loadFeed(){
    const res=await fetch('feed.php');
    const html=await res.text();
    document.getElementById('feed').innerHTML=html;
}
async function loadAds(){
    const wrap=document.getElementById('ads');
    if(role==='brand'){
        wrap.innerHTML='<iframe src="pages/brand-dashboard.php" style="width:100%;border:none;height:1000px;"></iframe>';
    }else{
        wrap.innerHTML='<iframe src="pages/influencer-dashboard.php" style="width:100%;border:none;height:1000px;"></iframe>';
    }
}
async function loadExplore(){
    const res=await fetch('pages/influencer-directory.php');
    const html=await res.text();
    document.getElementById('explore').innerHTML=html;
}

document.querySelectorAll('.tablink').forEach(a=>{
    a.onclick=e=>{
        e.preventDefault();
        document.querySelectorAll('section').forEach(s=>s.style.display='none');
        document.querySelector(a.getAttribute('href')).style.display='block';
        document.getElementById('page-title').textContent=a.dataset.title||'';
        if(a.getAttribute('href')==='#feed') loadFeed();
        if(a.getAttribute('href')==='#ads') loadAds();
        if(a.getAttribute('href')==='#explore') loadExplore();
    };
});

function showPostForm(){
    const mainContent=document.querySelector('.main-content');
    document.getElementById('page-title').textContent='Post';
    mainContent.innerHTML=`
        <h2>Add New Post</h2>
        <form id="post-form" enctype="multipart/form-data">
            <textarea id="post-content" placeholder="Share something" required class="post-text"></textarea>
            <input type="file" id="post-image" name="images[]" multiple />
            <input type="text" id="poll-question" placeholder="Poll question" />
            <input type="text" id="poll-options" placeholder="Option1|Option2" />
            <button type="submit">Post</button>
            <button type="button" id="cancel-post">Cancel</button>
        </form>
        <div id="post-message"></div>
    `;
    document.getElementById('cancel-post').onclick=()=>{
        document.getElementById('page-title').textContent='Feed';
        loadFeed();
    };
    document.getElementById('post-form').onsubmit=async e=>{
        e.preventDefault();
        const fd=new FormData();
        fd.append('content',document.getElementById('post-content').value);
        const imgs=document.getElementById('post-image').files;
        for(let i=0;i<imgs.length;i++){fd.append('images[]',imgs[i]);}
        fd.append('poll_question',document.getElementById('poll-question').value);
        fd.append('poll_options',document.getElementById('poll-options').value);
        const res=await fetch('/backend/community.php?action=post',{method:'POST',body:fd});
        const data=await res.json();
        if(data.success){
            document.getElementById('post-message').textContent='Post added successfully!';
            loadFeed();
        }else{
            document.getElementById('post-message').textContent='Failed to add post: '+data.message;
        }
    };

}
document.getElementById('add-post-btn').onclick=showPostForm;
document.getElementById('add-post-btn-mobile').onclick=e=>{e.preventDefault();showPostForm();};

document.addEventListener('DOMContentLoaded',()=>{loadFeed();});
</script>
</body>
</html>
