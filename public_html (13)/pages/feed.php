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
        <h2>Community Feed</h2>
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
            li.innerHTML=`<strong>${p.author}</strong>: ${p.content} <button class="like-btn" data-id="${p.id}">❤ ${p.like_count||0}</button>`;
            ul.appendChild(li);
        });
        document.querySelectorAll('.like-btn').forEach(btn=>{
            btn.onclick=async()=>{
                const r=await fetch('/backend/community.php?action=like',{method:'POST',body:new URLSearchParams({post_id:btn.dataset.id})});
                const d=await r.json();
                if(d.success) loadFeed();
            };
        });
    }
}

document.addEventListener('DOMContentLoaded',loadFeed);
</script>
</body>
</html>
