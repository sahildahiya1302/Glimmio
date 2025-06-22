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
<link rel="stylesheet" href="/../css/brand-dashboard-style.css" />
<link rel="stylesheet" href="/../css/influencer-dashboard-style.css" />
<link rel="stylesheet" href="/../css/feed-style.css" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <h2>Dashboard</h2>
        <ul>
            <li><a href="#feed" class="tablink">Feed</a></li>
            <li><a href="#community" class="tablink">Community</a></li>
            <li><a href="#ads" class="tablink">Ads</a></li>
            <li><a href="dm.php">DM</a></li>
        </ul>
    </div>
    <div class="main-content">
        <section id="feed"></section>
        <section id="community" style="display:none;"></section>
        <section id="ads" style="display:none;"></section>
    </div>
</div>
<script>
const role='<?php echo $role;?>';
async function loadFeed(){
    const res=await fetch('feed.php');
    const html=await res.text();
    document.getElementById('feed').innerHTML=html;
}
async function loadCommunity(){
    const wrap=document.getElementById('community');
    wrap.innerHTML=`<form id="community-form"><textarea id="community-content" required></textarea><button type="submit">Post</button></form><ul id="community-list"></ul>`;
    async function refresh(){
        const r=await fetch('/backend/community.php?action=list');
        const d=await r.json();
        const ul=document.getElementById('community-list');
        ul.innerHTML='';
        if(d.success){
            d.data.forEach(p=>{const li=document.createElement('li');li.innerHTML=`<strong>${p.author}</strong>: ${p.content} <button class='like-btn' data-id='${p.id}'>❤ ${p.like_count||0}</button>`;ul.appendChild(li);});
            document.querySelectorAll('.like-btn').forEach(btn=>{btn.onclick=async()=>{await fetch('/backend/community.php?action=like',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id})});refresh();};});
        }
    }
    document.getElementById('community-form').onsubmit=async e=>{e.preventDefault();await fetch('/backend/community.php?action=post',{method:'POST',body:new URLSearchParams({content:document.getElementById('community-content').value})});document.getElementById('community-content').value='';refresh();};
    refresh();
}
async function loadAds(){
    const wrap=document.getElementById('ads');
    if(role==='brand'){
        wrap.innerHTML='<iframe src="brand-dashboard.php" style="width:100%;border:none;height:1000px;"></iframe>';
    }else{
        wrap.innerHTML='<iframe src="influencer-dashboard.php" style="width:100%;border:none;height:1000px;"></iframe>';
    }
}

document.querySelectorAll('.tablink').forEach(a=>{a.onclick=e=>{e.preventDefault();document.querySelectorAll('section').forEach(s=>s.style.display='none');document.querySelector(a.getAttribute('href')).style.display='block';if(a.getAttribute('href')==='#feed') loadFeed();if(a.getAttribute('href')==='#community') loadCommunity();if(a.getAttribute('href')==='#ads') loadAds();};});

document.addEventListener('DOMContentLoaded',()=>{loadFeed();});
</script>
</body>
</html>
