<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.html'); exit; }
$role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Feed</title>
    <link rel="stylesheet" href="/../css/feed-style.css" />
</head>
<body>
<div class="feed-view">
    <div class="filter-bar">
        <span class="chip active" data-filter="">All</span>
        <span class="chip" data-filter="influencers">Influencers</span>
        <span class="chip" data-filter="brands">Brands</span>
        <span class="chip" data-filter="polls">Polls</span>
        <span class="chip" data-filter="videos">Videos</span>
        <span class="chip" data-filter="carousel">Carousel</span>
        <span class="chip" data-filter="trending">Trending</span>
    </div>
    <div id="feed-grid" class="feed-grid"></div>
    <div id="end-message" class="end-message" style="display:none;">You're All Caught Up</div>
</div>
<script>
const role = '<?php echo $role;?>';
let posts = [];
async function loadFeed(){
    const active = document.querySelector('.filter-bar .chip.active').dataset.filter;
    let url = '/backend/community.php?action=list';
    if(active === 'trending'){ url += '&filter=trending'; }
    document.getElementById('feed-grid').innerHTML = '<div class="skeleton"></div>'.repeat(6);
    const res = await fetch(url);
    const data = await res.json();
    posts = data.success ? data.data : [];
    renderPosts();
}
function renderPosts(){
    const grid = document.getElementById('feed-grid');
    grid.innerHTML = '';
    const active = document.querySelector('.filter-bar .chip.active').dataset.filter;
    let filtered = posts.filter(p => {
        if(active === 'influencers') return p.role === 'influencer';
        if(active === 'brands') return p.role === 'brand';
        if(active === 'polls') return p.poll_question;
        if(active === 'videos') return p.image_url && p.image_url.match(/\.mp4$/);
        if(active === 'carousel') return p.image_url && p.image_url.includes('|');
        return true;
    });
    filtered.forEach(p => {
        const card = document.createElement('div');
        card.className = 'feed-card';
        const avatar = p.role === 'brand' ? '/uploads/default-brand.png' : '/uploads/default-user.png';
        let media = '';
        if(p.image_url){
            const imgs = p.image_url.split('|');
            if(imgs.length > 1){
                media = '<div class="media carousel">' + imgs.map(i => `<img src="${i}">`).join('') + '</div>';
            }else if(p.image_url.match(/\.mp4$/)){
                media = `<div class="media"><video src="${p.image_url}" muted loop></video></div>`;
            }else{
                media = `<div class="media"><img src="${p.image_url}" /></div>`;
            }
        }
        const poll = p.poll_question ? `<div class="poll"><p>${p.poll_question}</p>${(p.poll_results||[]).map((o,i)=>`<button class=\"poll-option\" data-id=\"${p.id}\" data-opt=\"${i}\">${o.option} (${o.votes})</button>`).join('')}</div>` : '';
        const caption = `<div class="caption">${p.content.replace(/@(\\w+)/g,'<a href="/profile.php?user=$1">@$1</a>')}</div>`;
        const reactions = `<div class="reactions"><button class="like-btn" data-id="${p.id}">❤ ${p.like_count||0}</button><button class="comment-toggle" data-id="${p.id}">💬 ${p.comment_count||0}</button><button class="share-btn" data-id="${p.id}">🔁</button><button class="save-btn" data-id="${p.id}">📌</button><span class="analytics-icon">📊<span class="analytics-tooltip">Impressions: ${p.like_count||0}</span></span></div>`;
        const cta = `<div class="cta"><button class="cta-btn" data-id="${p.id}">${role==='brand'?'Invite to Campaign':'Apply to Similar Campaign'}</button></div>`;
        card.innerHTML = `<div class="header"><img class="avatar" src="${avatar}" alt=""><span class="name">${p.author}</span><span class="role-tag">${p.role.toUpperCase()}</span><span class="dropdown">⋮</span></div>` + media + caption + poll + reactions + cta;
        grid.appendChild(card);
    });
    document.getElementById('end-message').style.display = 'block';
    attachHandlers();
}
function attachHandlers(){
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.onclick = async () => { await fetch('/backend/community.php?action=like',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id})}); loadFeed(); };
    });
    document.querySelectorAll('.share-btn').forEach(btn => { btn.onclick = async ()=>{ await fetch('/backend/community.php?action=share',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id})}); }; });
    document.querySelectorAll('.save-btn').forEach(btn => { btn.onclick = async ()=>{ await fetch('/backend/community.php?action=save',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id})}); }; });
    document.querySelectorAll('.poll-option').forEach(btn => { btn.onclick = async ()=>{ await fetch('/backend/community.php?action=vote',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id,option:btn.dataset.opt})}); loadFeed(); }; });
    document.querySelectorAll('.cta-btn').forEach(btn => { btn.onclick = () => { alert('This action requires integration'); }; });
}
document.querySelectorAll('.filter-bar .chip').forEach(chip => {
    chip.onclick = () => { document.querySelectorAll('.filter-bar .chip').forEach(c=>c.classList.remove('active')); chip.classList.add('active'); loadFeed(); };
});
document.addEventListener('DOMContentLoaded', loadFeed);
</script>
</body>
</html>
