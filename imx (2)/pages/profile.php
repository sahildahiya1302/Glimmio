<?php
$include = __DIR__ . '/includes/security.php';
require_once $include;
secure_session_start();
secure_page_headers();
if(!isset($_SESSION['user_id'])){header('Location: login.html');exit;}
$role=$_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Profile</title>
    <link rel="stylesheet" href="/../css/dashboard-style.css" />
    <link rel="stylesheet" href="/../css/feed-style.css" />
    <link rel="stylesheet" href="/../css/instagram-theme.css" />
</head>
<body>
<header class="top-bar">
    <div class="logo">Glimmio</div>
    <div id="page-title">Profile</div>
    <div class="top-icons"><a href="dashboard.php">🏠</a></div>
</header>
<div class="profile-page" style="padding-top:60px;">
    <div class="profile-header" style="text-align:center; padding:20px;">
        <img id="profile-pic" src="" alt="" style="width:100px;height:100px;border-radius:50%;object-fit:cover;background:#ddd;" />
        <h2 id="username" style="margin:10px 0 5px;font-size:22px;"></h2>
        <p id="stats" style="color:#666;"></p>
        <p><a href="pages/profile-edit.php">Edit Profile</a></p>
    </div>
    <div id="profile-grid" class="feed-grid"></div>
    <h3>Top Instagram Posts</h3>
    <div id="top-media-grid" class="feed-grid"></div>
</div>
<script>
const role='<?php echo $role;?>';
let posts=[];
let topMedia=[];
async function loadProfile(){
    const resp=await fetch('/backend/'+(role==='brand'?'brand':'influencer')+'.php?action=profile');
    const data=await resp.json();
    if(data.success){
        const p=data.data;
        document.getElementById('username').textContent=p.username||p.company_name||'';
        document.getElementById('profile-pic').src=p.profile_pic||p.logo_url||'';
        document.getElementById('stats').textContent=`Followers ${p.followers_count||0} • Posts ${p.media_count||0}`;
        loadPosts(p.id);
        if(role==='influencer') loadTopMedia();
    }
}
async function loadPosts(uid){
    document.getElementById('profile-grid').innerHTML='<div class="skeleton"></div>'.repeat(6);
    const res=await fetch('/backend/community.php?action=list&author='+uid+'&role='+role);
    const data=await res.json();
    posts=data.success?data.data:[];
    renderPosts();
}
function renderPosts(){
    const grid=document.getElementById('profile-grid');
    grid.innerHTML='';
    posts.forEach(p=>{
        const card=document.createElement('div');
        card.className='feed-card';
        let media='';
        if(p.image_url){
            const imgs=p.image_url.split('|');
            if(imgs.length>1){
                media='<div class="media carousel">'+imgs.map(i=>`<img src="${i}">`).join('')+'</div>';
            }else if(/\.mp4$/.test(p.image_url)){
                media=`<div class="media"><video src="${p.image_url}" muted loop></video></div>`;
            }else{
                media=`<div class="media"><img src="${p.image_url}"></div>`;
            }
        }
        const caption=`<div class="caption">${p.content}</div>`;
        card.innerHTML=media+caption;
        grid.appendChild(card);
    });
}
document.addEventListener('DOMContentLoaded',loadProfile);

async function loadTopMedia(){
    const res = await fetch('/backend/influencer.php?action=top_media');
    const data = await res.json();
    if(data.success){
        topMedia = data.data.data || [];
    }
    renderTopMedia();
}

function renderTopMedia(){
    const grid = document.getElementById('top-media-grid');
    if(!grid) return;
    grid.innerHTML='';
    topMedia.forEach(m=>{
        const card=document.createElement('div');
        card.className='feed-card';
        card.innerHTML=`<div class="media"><img src="${m.media_url}"/></div>`;
        grid.appendChild(card);
    });
}
</script>
</body>
</html>
