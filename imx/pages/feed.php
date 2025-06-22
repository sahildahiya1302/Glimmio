<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Community Feed</title>
    <link rel="stylesheet" href="/../css/feed-style.css" />
</head>
<body>
    <div class="feed-container">
        <h2>Feed</h2>
        <ul id="feed-list"></ul>
    </div>

<script>
async function loadFeed(){
    const res = await fetch('/backend/community.php?action=list');
    const data = await res.json();
    const ul = document.getElementById('feed-list');
    ul.innerHTML='';
    if(data.success){
        data.data.forEach(p=>{
            const li=document.createElement('li');
            let contentWithTags = p.content.replace(/@(\w+)/g, '<a href="/profile.php?user=$1">@$1</a>');
            let html=`
                <div class="post-header">
                    <div class="post-author">${p.author}</div>
                </div>
                <div class="post-content">${contentWithTags}</div>
            `;
            if(p.image_url) html+=`<img class="post-image" src="${p.image_url}" />`;
            html+=`
                <div class="post-buttons">
                    <button class="like-btn" data-id="${p.id}">❤ ${p.like_count||0}</button>
                    <button class="share-btn" data-id="${p.id}">🔗 ${p.share_count||0}</button>
                    <button class="save-btn" data-id="${p.id}">💾 ${p.save_count||0}</button>
                    <button class="comment-toggle" data-id="${p.id}">💬 ${p.comment_count||0}</button>
                </div>
                <ul class="comment-list" id="c${p.id}" style="display:none"></ul>
            `;
            li.innerHTML=html;
            ul.appendChild(li);
        });
        document.querySelectorAll('.like-btn').forEach(btn=>{
            btn.onclick=async()=>{
                const r=await fetch('/backend/community.php?action=like',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id})});
                const d=await r.json();
                if(d.success) loadFeed();
            };
        });
        document.querySelectorAll('.share-btn').forEach(btn=>{btn.onclick=async()=>{await fetch('/backend/community.php?action=share',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id})});loadFeed();};});
        document.querySelectorAll('.save-btn').forEach(btn=>{btn.onclick=async()=>{await fetch('/backend/community.php?action=save',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id})});loadFeed();};});
        document.querySelectorAll('.comment-toggle').forEach(btn=>{
            btn.onclick=async()=>{
                const cid='c'+btn.dataset.id;
                const ulc=document.getElementById(cid);
                if(ulc.style.display==='none'){
                    const r=await fetch('/backend/community.php?action=list_comments&post_id='+btn.dataset.id);
                    const d=await r.json();
                    ulc.innerHTML='';
                    if(d.success){
                        d.data.forEach(c=>{
                            const li=document.createElement('li');
                            li.textContent=`${c.author}: ${c.comment}`;
                            ulc.appendChild(li);
                        });
                        const inp=document.createElement('input');
                        inp.placeholder='Comment';
                        const send=document.createElement('button');
                        send.textContent='Send';
                        send.onclick=async()=>{
                            await fetch('/backend/community.php?action=comment',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id,comment:inp.value})});
                            loadFeed();
                        };
                        ulc.appendChild(inp);
                        ulc.appendChild(send);
                    }
                    ulc.style.display='block';
                }else{
                    ulc.style.display='none';
                }
            };
        });
    }
}

document.addEventListener('DOMContentLoaded',loadFeed);

// Add simple fade-in animation for posts
const style = document.createElement('style');
style.innerHTML = `
    #feed-list li {
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.5s forwards;
    }
    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);
</script>
</body>
</html>
