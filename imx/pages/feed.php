<?php
require_once __DIR__ . '/../includes/security.php';
secure_session_start();
secure_page_headers();
$role = $_SESSION['role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Feed</title>
  <link rel="stylesheet" href="/../css/feed-style.css" />
  <link rel="stylesheet" href="/../css/instagram-theme.css" />
  <link rel="stylesheet" href="/../css/dark-theme.css" />
</head>
<body>
<header class="top-nav">
  <div class="logo">Glimmio</div>
  <nav>
    <button id="theme-toggle">🌙</button>
  </nav>
</header>

<!-- FEED VIEW -->
<section id="feed" style="display: block;" class="feed-view" role="main" aria-label="Content Feed">
  <!-- FILTER BAR -->
  <div class="filter-bar" role="tablist" aria-label="Feed Filters">
    <button class="chip active" data-filter="" role="tab" aria-selected="true">All</button>
    <button class="chip" data-filter="influencers" role="tab">Influencers</button>
    <button class="chip" data-filter="brands" role="tab">Brands</button>
    <button class="chip" data-filter="polls" role="tab">Polls</button>
    <button class="chip" data-filter="videos" role="tab">Videos</button>
    <button class="chip" data-filter="carousel" role="tab">Carousel</button>
    <button class="chip" data-filter="trending" role="tab">Trending</button>
  </div>

  <!-- FEED GRID -->
  <div id="feed-grid" class="feed-grid" aria-live="polite" aria-busy="false"></div>

  <!-- END MESSAGE -->
  <div id="end-message" class="end-message" style="display:none;">🎉 You're All Caught Up</div>
</section>

<div id="comment-popup" class="comment-popup" style="display:none;">
  <div class="popup-content">
    <button id="close-comment-popup">X</button>
    <div id="comments-list"></div>
    <textarea id="comment-text" placeholder="Add a comment"></textarea>
    <button id="send-comment">Send</button>
  </div>
</div>

<!-- SCRIPT -->
<script>
const role = '<?php echo $role; ?>';
let posts = [];
let offset = 0;
const limit = 10;
let loading = false;
let allLoaded = false;
const commentPopup = document.getElementById('comment-popup');
const commentsList = document.getElementById('comments-list');
const commentText = document.getElementById('comment-text');

async function loadFeed(reset=false) {
  if (loading || allLoaded && !reset) return;
  loading = true;
  const activeChip = document.querySelector('.filter-bar .chip.active');
  const filter = activeChip ? activeChip.dataset.filter : '';
  if(reset){
    offset = 0;
    posts = [];
    allLoaded = false;
    document.getElementById('feed-grid').innerHTML = '';
  }
  let url = `/backend/community.php?action=list&limit=${limit}&offset=${offset}`;
  if (filter === 'trending') url += '&filter=trending';

  const grid = document.getElementById('feed-grid');
  grid.setAttribute('aria-busy', 'true');
  if(offset === 0) grid.innerHTML = '<div class="skeleton"></div>'.repeat(6);

  const res = await fetch(url);
  const data = await res.json();
  if(data.success){
    posts = posts.concat(data.data);
    offset += data.data.length;
    if(data.data.length < limit) allLoaded = true;
  }
  renderPosts(filter);
  loading = false;
}

function renderPosts(filter = '') {
  const grid = document.getElementById('feed-grid');
  grid.innerHTML = '';
  document.getElementById('end-message').style.display = 'block';

  let filtered = posts.filter(p => {
    if (filter === 'influencers') return p.role === 'influencer';
    if (filter === 'brands') return p.role === 'brand';
    if (filter === 'polls') return p.poll_question;
    if (filter === 'videos') return p.image_url && p.image_url.match(/\.mp4$/);
    if (filter === 'carousel') return p.image_url && p.image_url.includes('|');
    return true;
  });

  filtered.forEach(p => {
    const card = document.createElement('div');
    card.className = 'feed-card';
    const avatar = p.role === 'brand' ? '/uploads/default-brand.png' : '/uploads/default-user.png';
    let media = '';

    if (p.image_url) {
      const imgs = p.image_url.split('|');
      if (imgs.length > 1) {
        media = '<div class="media carousel">' + imgs.map(i => `<img src="${i}" alt="carousel item">`).join('') + '</div>';
      } else if (p.image_url.match(/\.mp4$/)) {
        media = `<div class="media"><video src="${p.image_url}" muted loop controls></video></div>`;
      } else {
        media = `<div class="media"><img src="${p.image_url}" alt="post image" /></div>`;
      }
    }

    const poll = p.poll_question
      ? `<div class="poll"><p>${p.poll_question}</p>${(p.poll_results || []).map((o, i) => `<button class="poll-option" data-id="${p.id}" data-opt="${i}">${o.option} (${o.votes})</button>`).join('')}</div>`
      : '';

    const caption = `<div class="caption">${p.content.replace(/@(\w+)/g, '<a href="/pages/profile.php?user=$1">@$1</a>')}</div>`;
    const reactions = `
      <div class="reactions">
        <button class="like-btn" data-id="${p.id}">❤ ${p.like_count || 0}</button>
        <button class="comment-toggle" data-id="${p.id}">💬 ${p.comment_count || 0}</button>
        <button class="share-btn" data-id="${p.id}">🔁</button>
        <button class="save-btn" data-id="${p.id}">📌</button>
        <span class="analytics-icon">📊
          <span class="analytics-tooltip">Impressions: ${p.like_count || 0}</span>
        </span>
      </div>
    `;
    const cta = `
      <div class="cta">
        <button class="cta-btn" data-id="${p.id}">
          ${role === 'brand' ? 'Invite to Campaign' : 'Apply to Similar Campaign'}
        </button>
      </div>
    `;

    card.innerHTML = `
      <div class="header">
        <img class="avatar" src="${avatar}" alt="${p.role} avatar">
        <span class="name">${p.author}</span>
        <span class="role-tag">${p.role.toUpperCase()}</span>
        <span class="dropdown">⋮</span>
      </div>
      ${media}
      ${caption}
      ${poll}
      ${reactions}
      ${cta}
    `;
    grid.appendChild(card);
  });

  document.getElementById('feed-grid').setAttribute('aria-busy', 'false');
  document.getElementById('end-message').style.display = 'block';
  attachHandlers();
}

function attachHandlers() {
  document.querySelectorAll('.like-btn').forEach(btn => {
    btn.onclick = async () => {
        await fetch('/backend/community.php?action=like', {
          method: 'POST',
          body: new URLSearchParams({ post_id: btn.dataset.id })
        });
        loadFeed(true);
    };
  });
  document.querySelectorAll('.share-btn').forEach(btn => {
    btn.onclick = async () => {
      await fetch('/backend/community.php?action=share', {
        method: 'POST',
        body: new URLSearchParams({ post_id: btn.dataset.id })
      });
    };
  });
  document.querySelectorAll('.save-btn').forEach(btn => {
    btn.onclick = async () => {
      await fetch('/backend/community.php?action=save', {
        method: 'POST',
        body: new URLSearchParams({ post_id: btn.dataset.id })
      });
    };
  });
  document.querySelectorAll('.poll-option').forEach(btn => {
    btn.onclick = async () => {
      await fetch('/backend/community.php?action=vote', {
        method: 'POST',
        body: new URLSearchParams({
          post_id: btn.dataset.id,
          option: btn.dataset.opt
        })
        });
        loadFeed(true);
      };
  });
  document.querySelectorAll('.comment-toggle').forEach(btn => {
    btn.onclick = () => openComments(btn.dataset.id);
  });
  document.querySelectorAll('.cta-btn').forEach(btn => {
    btn.onclick = () => {
      alert('This action requires integration');
    };
  });
}

function openComments(id) {
  commentPopup.dataset.postId = id;
  commentPopup.style.display = 'flex';
  loadComments(id);
}

async function loadComments(id) {
  const res = await fetch('/backend/community.php?action=list_comments&post_id=' + id);
  const data = await res.json();
  commentsList.innerHTML = '';
  if (data.success) {
    data.data.forEach(c => {
      const div = document.createElement('div');
      div.textContent = `${c.author}: ${c.comment}`;
      commentsList.appendChild(div);
    });
  }
}

document.getElementById('close-comment-popup').onclick = () => {
  commentPopup.style.display = 'none';
};

document.getElementById('send-comment').onclick = async () => {
  const id = commentPopup.dataset.postId;
  const txt = commentText.value.trim();
  if (!txt) return;
  await fetch('/backend/community.php?action=comment', {
    method: 'POST',
    body: new URLSearchParams({ post_id: id, comment: txt })
  });
  commentText.value = '';
  loadComments(id);
};

// Filter interaction
document.querySelectorAll('.filter-bar .chip').forEach(chip => {
  chip.onclick = () => {
    document.querySelectorAll('.chip').forEach(c => {
      c.classList.remove('active');
      c.setAttribute('aria-selected', 'false');
    });
    chip.classList.add('active');
    chip.setAttribute('aria-selected', 'true');
    loadFeed(true);
  };
});

// Force initial load and show #feed section
document.addEventListener('DOMContentLoaded', () => {
  // Ensure #feed section is visible
  const feedSection = document.getElementById('feed');
  if (feedSection) feedSection.style.display = 'block';

  if(localStorage.getItem('theme')==='dark'){
    document.body.classList.add('dark-mode');
  }
  document.getElementById('theme-toggle').addEventListener('click',()=>{
    const dark=document.body.classList.toggle('dark-mode');
    localStorage.setItem('theme',dark?'dark':'light');
  });

  // Reset and mark "All" filter as active
  document.querySelectorAll('.chip').forEach(c => {
    c.classList.remove('active');
    c.setAttribute('aria-selected', 'false');
  });
  const defaultChip = document.querySelector('.chip[data-filter=""]');
  if (defaultChip) {
    defaultChip.classList.add('active');
    defaultChip.setAttribute('aria-selected', 'true');
  }

  // Load feed
  loadFeed(true);

  window.addEventListener('scroll', () => {
    if(window.innerHeight + window.pageYOffset >= document.body.offsetHeight - 200) {
      loadFeed();
    }
  });
});
</script>
</body>
</html>
