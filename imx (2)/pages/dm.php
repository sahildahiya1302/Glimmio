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
<title>Direct Messages</title>
<link rel="stylesheet" href="/../css/feed-style.css" />
<link rel="stylesheet" href="/../css/instagram-theme.css" />
</head>
<body>
<div class="feed-container">
<h2>Direct Messages</h2>
<label>Chat with ID: <input type="number" id="other_id" /></label>
<select id="other_role"><option value="brand">Brand</option><option value="influencer">Influencer</option></select>
<button id="load">Load</button>
<ul id="msg-list"></ul>
<input type="text" id="msg-box" placeholder="Type message" />
<button id="send">Send</button>
</div>
<script>
let otherId=null, otherRole='brand';
document.getElementById('load').onclick=()=>{otherId=document.getElementById('other_id').value;otherRole=document.getElementById('other_role').value;load();};
async function load(){
 if(!otherId) return;
 const r=await fetch(`/backend/dm.php?action=list&other_id=${otherId}&other_role=${otherRole}`);
 const d=await r.json();
 const ul=document.getElementById('msg-list');
 ul.innerHTML='';
 if(d.success){d.data.forEach(m=>{const li=document.createElement('li');li.textContent=`${m.sender_role}:${m.message}`;ul.appendChild(li);});}
}
async function send(){
 if(!otherId) return;
 const txt=document.getElementById('msg-box').value.trim();
 if(!txt) return;
 await fetch('/backend/dm.php?action=send',{method:'POST',body:new URLSearchParams({receiver_id:otherId,receiver_role:otherRole,message:txt})});
 document.getElementById('msg-box').value='';
 load();
}
document.getElementById('send').onclick=send;
</script>
</body>
</html>
