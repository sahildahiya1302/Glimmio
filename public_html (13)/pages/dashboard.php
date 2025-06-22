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
    <div class="main-content">
        <section id="feed"><ul id="feed-list"></ul></section>
        <section id="ads" style="display:none;"></section>
    </div>
    <nav class="bottom-nav">
        <a href="#feed" class="tablink" id="nav-feed"><i class="fas fa-home"></i></a>
        <button id="new-post-btn"><i class="fas fa-plus-square"></i></button>
        <a href="#ads" class="tablink" id="nav-ads"><i class="fas fa-bullhorn"></i></a>
        <a href="dm.php"><i class="fas fa-envelope"></i></a>
    </nav>
</div>
<div id="post-modal" class="post-modal" style="display:none;">
    <div class="modal-content">
        <form id="post-form" enctype="multipart/form-data">
            <textarea id="post-content" placeholder="Share something" required></textarea>
            <input type="file" id="post-image" name="image" />
            <select id="filter">
                <option value="">No Filter</option>
                <option value="grayscale(1)">Grayscale</option>
                <option value="sepia(1)">Sepia</option>
            </select>
            <input type="text" id="tags" placeholder="tag1,tag2" />
            <input type="datetime-local" id="scheduled_at" />
            <input type="datetime-local" id="expires_at" />
            <input type="text" id="poll-question" placeholder="Poll question" />
            <input type="text" id="poll-options" placeholder="Option1|Option2" />
            <button type="submit">Post</button>
            <button type="button" id="close-modal">Cancel</button>
        </form>
    </div>
</div>
<script>
const role='<?php echo $role;?>';
async function loadFeed(){
    const r=await fetch('/backend/community.php?action=list');
    const d=await r.json();
    const ul=document.getElementById('feed-list');
    ul.innerHTML='';
    if(d.success){
        d.data.forEach(p=>{
            const li=document.createElement('li');
            let html=`<strong>@${p.author}</strong>: ${p.content}`;
            if(p.image_url){const fil=p.filter?`style=\"max-width:100%;filter:${p.filter}\"`:'style="max-width:100%;"';html+=`<div><img src="${p.image_url}" ${fil}/></div>`;}
            if(p.poll_question){html+=`<div>${p.poll_question}<ul>`;p.poll_results.forEach((o,i)=>{html+=`<li>${o.option} - ${o.votes} <button class="vote-btn" data-id="${p.id}" data-opt="${i}">Vote</button></li>`;});html+='</ul></div>';}
            if(p.tags&&p.tags.length){html+=`<div class="tags">`+p.tags.map(t=>`#${t.trim()}`).join(' ')+'</div>';}
            html+=` <button class="like-btn" data-id="${p.id}">❤ ${p.like_count||0}</button>`;
            html+=` <button class="share-btn" data-id="${p.id}">Share ${p.share_count||0}</button>`;
            html+=` <button class="save-btn" data-id="${p.id}">Save ${p.save_count||0}</button>`;
            html+=` <button class="comment-toggle" data-id="${p.id}">Comments (${p.comment_count||0})</button>`;
            html+=`<ul class="comment-list" id="c${p.id}" style="display:none"></ul>`;
            li.innerHTML=html;
            ul.appendChild(li);
        });
        document.querySelectorAll('.like-btn').forEach(btn=>{btn.onclick=async()=>{await fetch('/backend/community.php?action=like',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id})});loadFeed();};});
        document.querySelectorAll('.share-btn').forEach(btn=>{btn.onclick=async()=>{await fetch('/backend/community.php?action=share',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id})});loadFeed();};});
        document.querySelectorAll('.save-btn').forEach(btn=>{btn.onclick=async()=>{await fetch('/backend/community.php?action=save',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id})});loadFeed();};});
        document.querySelectorAll('.vote-btn').forEach(btn=>{btn.onclick=async()=>{await fetch('/backend/community.php?action=vote',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id,option:btn.dataset.opt})});loadFeed();};});
        document.querySelectorAll('.comment-toggle').forEach(btn=>{btn.onclick=async()=>{const cid='c'+btn.dataset.id;const ulc=document.getElementById(cid);if(ulc.style.display==='none'){const r=await fetch('/backend/community.php?action=list_comments&post_id='+btn.dataset.id);const d=await r.json();ulc.innerHTML='';if(d.success){d.data.forEach(c=>{const li=document.createElement('li');li.textContent=`${c.author}: ${c.comment}`;ulc.appendChild(li);});const inp=document.createElement('input');inp.placeholder='Comment';const send=document.createElement('button');send.textContent='Send';send.onclick=async()=>{await fetch('/backend/community.php?action=comment',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id,comment:inp.value})});loadFeed();};ulc.appendChild(inp);ulc.appendChild(send);}ulc.style.display='block';}else{ulc.style.display='none';}}});
    }
}
async function loadAds(){
    const wrap=document.getElementById('ads');
    const page=role==='brand'?'/pages/ads-brand.php':'/pages/ads-influencer.php';
    const res=await fetch(page);
    const html=await res.text();
    // extract body if full page returned
    const tmp=document.createElement('div');
    tmp.innerHTML=html;
    const body=tmp.querySelector('body');
    wrap.innerHTML=body?body.innerHTML:html;
}

document.querySelectorAll('.tablink').forEach(a=>{a.onclick=e=>{e.preventDefault();document.querySelectorAll('section').forEach(s=>s.style.display='none');document.querySelector(a.getAttribute('href')).style.display='block';if(a.getAttribute('href')==='#feed') loadFeed();if(a.getAttribute('href')==='#ads') loadAds();};});

document.addEventListener('DOMContentLoaded',()=>{loadFeed();});
document.getElementById('new-post-btn').addEventListener('click',()=>{
    document.getElementById('post-modal').style.display='flex';
});
document.getElementById('close-modal').addEventListener('click',()=>{
    document.getElementById('post-modal').style.display='none';
});
document.getElementById('post-form').addEventListener('submit',async e=>{
    e.preventDefault();
    const fd=new FormData();
    fd.append('content',document.getElementById('post-content').value);
    const img=document.getElementById('post-image').files[0];if(img)fd.append('image',img);
    fd.append('filter',document.getElementById('filter').value);
    fd.append('tags',document.getElementById('tags').value);
    fd.append('scheduled_at',document.getElementById('scheduled_at').value);
    fd.append('expires_at',document.getElementById('expires_at').value);
    fd.append('poll_question',document.getElementById('poll-question').value);
    fd.append('poll_options',document.getElementById('poll-options').value);
    await fetch('/backend/community.php?action=post',{method:'POST',body:fd});
    document.getElementById('post-form').reset();
    document.getElementById('post-modal').style.display='none';
    loadFeed();
});
</script>
</body>
</html>
