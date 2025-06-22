<?php
session_start();
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
    <link rel="stylesheet" href="/../css/instagram-theme.css" />
    <link rel="stylesheet" href="/../css/dark-theme.css" />
    <style>
        .nav-tabs {
            position: fixed;
            left: 0;
            top: 60px;
            width: 60px;
            height: calc(100vh - 60px);
            background-color: #fff;
            border-right: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 20px;
            z-index: 100;
        }
        .nav-tabs a,
        .nav-tabs button {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 60px;
            height: 60px;
            text-decoration: none;
            color: #333;
            font-size: 24px;
            background: none;
            border: none;
            cursor: pointer;
        }
        .nav-tabs .fab {
            font-size: 32px;
        }
        @media screen and (max-width: 768px) {
            .nav-tabs {
                top: auto;
                bottom: 0;
                left: 0;
                right: 0;
                height: 60px;
                width: 100%;
                flex-direction: row;
                justify-content: space-around;
                border-top: 1px solid #ddd;
                border-right: none;
            }
            .nav-tabs a,
            .nav-tabs button {
                width: 100%;
                height: 100%;
                font-size: 22px;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header class="top-bar">
    <div class="logo">Glimmio</div>
    <div id="page-title">Feed</div>
    <div class="top-icons">
        <button id="theme-toggle" style="background:none;border:none;cursor:pointer">🌙</button>
        <a href="#" id="notif-btn">🔔</a>
        <a href="pages/dm.php">✉️</a>
    </div>
</header>
<nav class="nav-tabs">
    <a href="#feed" class="tablink" data-title="Feed">🏠</a>
    <a href="#explore" class="tablink" data-title="Explore">🔍</a>
    <button id="add-post-btn-mobile" class="fab" data-title="Post">➕</button>
    <a href="#ads" class="tablink" data-title="Campaigns">📣</a>
    <a href="profile.php" data-title="Profile">👤</a>
</nav>
<div class="dashboard-container">
    <div class="main-content" id="main-content">
        <section id="feed"></section>
        <section id="ads" style="display:none;"></section>
        <section id="explore" style="display:none;">
            <h2>Influencer Directory</h2>
            <div>
                <label for="filter-cat">Category:</label>
                <input type="text" id="filter-cat" />
                <label for="filter-ind">Industry:</label>
                <input type="text" id="filter-ind" />
                <label for="filter-budget">Budget/Reach >=</label>
                <input type="number" id="filter-budget" />
                <label for="filter-loc">Location:</label>
                <input type="text" id="filter-loc" />
                <button id="load-btn">Load</button>
            </div>
            <table id="inf-table">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Badge</th><th>Category</th><th>Followers</th><th>Reach</th><th>Engagement</th><th>Invite</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </section>
    </div>
</div>
<script>
const role='<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : '';?>';
let campaigns = [];

async function loadFeed(){
    const feed = document.getElementById('feed');
    if (!feed) return;
    const res = await fetch('pages/feed.php');
    const html = await res.text();
    feed.innerHTML = html;
}

async function loadAds(){
    const wrap=document.getElementById('ads');
    if (!wrap) return;
    if(role==='brand'){
        wrap.innerHTML='<iframe src="pages/brand-dashboard.php" style="width:100%;border:none;height:1000px;"></iframe>';
    }else{
        wrap.innerHTML='<iframe src="pages/influencer-dashboard.php" style="width:100%;border:none;height:1000px;"></iframe>';
    }
}

async function loadCampaigns() {
    const res = await fetch('/backend/campaigns.php?action=list_campaigns');
    const data = await res.json();
    if(data.success){ campaigns = data.data; }
}

async function loadInfluencers(){
    const catEl = document.getElementById('filter-cat');
    const budgetEl = document.getElementById('filter-budget');
    if (!catEl || !budgetEl) return;

    const cat = catEl.value;
    let url='/backend/influencer.php?action=list_all';
    if(cat) url+='&category='+encodeURIComponent(cat);
    const res=await fetch(url);
    const data=await res.json();
    const tbody=document.querySelector('#inf-table tbody');
    if (!tbody) return;
    tbody.innerHTML='';
    if(data.success){
        let rows=data.data;
        const reachMin=parseInt(budgetEl.value||0);
        if(reachMin) rows=rows.filter(r=>(parseInt(r.reach||0)>=reachMin));
        rows.forEach(i=>{
            const tr=document.createElement('tr');
            tr.innerHTML=`<td>${i.username||''}</td><td>${i.email}</td><td>${i.badge_level}</td><td>${i.category||''}</td><td>${i.followers_count||0}</td><td>${i.reach||0}</td><td>${i.engagement||0}</td>`;
            const td=document.createElement('td');
            const sel=document.createElement('select');
            campaigns.forEach(c=>{ const o=document.createElement('option'); o.value=c.id; o.textContent=c.title; sel.appendChild(o); });
            const btn=document.createElement('button'); btn.textContent='Invite';
            btn.onclick=async()=>{
                await fetch('/backend/requests.php?action=invite',{method:'POST',body:new URLSearchParams({campaign_id:sel.value,influencer_id:i.id})});
                alert('Invitation sent');
            };
            td.appendChild(sel); td.appendChild(btn); tr.appendChild(td);
            tbody.appendChild(tr);
        });
    }
}

function showPostForm(){
    const content = document.getElementById('main-content');
    document.getElementById('page-title').textContent='Post';
    content.innerHTML = `
        <section>
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
        </section>
    `;
    document.getElementById('cancel-post').onclick=()=>{
        document.getElementById('page-title').textContent='Feed';
        location.reload();
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
        }else{
            document.getElementById('post-message').textContent='Failed to add post: '+data.message;
        }
    };
}

function restoreSections(){
    const content = document.getElementById('main-content');
    content.innerHTML = `
        <section id="feed"></section>
        <section id="ads" style="display:none;"></section>
        <section id="explore" style="display:none;">
            <h2>Influencer Directory</h2>
            <div>
                <label for="filter-cat">Category:</label>
                <input type="text" id="filter-cat" />
                <label for="filter-ind">Industry:</label>
                <input type="text" id="filter-ind" />
                <label for="filter-budget">Budget/Reach >=</label>
                <input type="number" id="filter-budget" />
                <label for="filter-loc">Location:</label>
                <input type="text" id="filter-loc" />
                <button id="load-btn">Load</button>
            </div>
            <table id="inf-table">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Badge</th><th>Category</th><th>Followers</th><th>Reach</th><th>Engagement</th><th>Invite</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </section>
    `;
}

document.addEventListener('DOMContentLoaded',()=>{
    loadFeed();
    const loadBtn = document.getElementById('load-btn');
    if (loadBtn) loadBtn.onclick = loadInfluencers;
    document.querySelectorAll('.tablink').forEach(a=>{
        a.onclick=e=>{
            e.preventDefault();
            restoreSections();
            const target = document.querySelector(a.getAttribute('href'));
            document.querySelectorAll('section').forEach(s=>s.style.display='none');
            if (target) target.style.display = 'block';
            document.getElementById('page-title').textContent=a.dataset.title||'';
            if(a.getAttribute('href')==='#feed') loadFeed();
            if(a.getAttribute('href')==='#ads') loadAds();
            if(a.getAttribute('href')==='#explore'){loadCampaigns();loadInfluencers();}
        };
    });
    document.getElementById('add-post-btn-mobile').onclick=e=>{
        e.preventDefault();
        showPostForm();
    };
    if(localStorage.getItem('theme')==='dark'){
        document.body.classList.add('dark-mode');
    }
    document.getElementById('theme-toggle').addEventListener('click',()=>{
        const dark=document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme',dark?'dark':'light');
    });
});
</script>
</body>
</html>