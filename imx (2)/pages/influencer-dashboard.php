<?php
require_once __DIR__ . '/../includes/security.php';
secure_session_start();
secure_page_headers();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'influencer') {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Influencer Dashboard</title>
    <link rel="stylesheet" href="/../css/influencer-dashboard-style.css" />
    <link rel="stylesheet" href="/../css/instagram-theme.css" />
    <link rel="stylesheet" href="/../css/dark-theme.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="top-nav">
        <div class="logo">Glimmio</div>
        <nav>
            <button id="theme-toggle">🌙</button>
            <a href="feed.php" class="home-link"><i class="fas fa-home"></i></a>
            <a href="../pages/login.html" class="logout-link">Logout</a>
        </nav>
    </header>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>Influencer Dashboard</h2>
            <ul>
                <li><a href="#profile" data-section="profile"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="#campaigns" data-section="campaigns"><i class="fas fa-bullhorn"></i> View Campaigns</a></li>
                <li><a href="#requests" data-section="requests"><i class="fas fa-list-alt"></i> My Requests</a></li>
                <li><a href="#active-campaigns" data-section="active-campaigns"><i class="fas fa-chart-line"></i> Active Campaigns</a></li>
                <li><a href="#earnings" data-section="earnings"><i class="fas fa-wallet"></i> Earnings</a></li>
                <li><a href="#notifications" data-section="notifications"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="#analytics" data-section="analytics"><i class="fas fa-chart-bar"></i> Analytics</a></li>
            </ul>
        </div>

        <div class="main-content">
            <!-- Profile Section -->
            <section id="profile">
                <h3>Your Profile</h3>
                <img id="profile-pic" src="" alt="Profile Picture" />
                <p id="profile-name">Username</p>
                <p id="profile-email"></p>
                <p id="followers-count">Followers: 0</p>
                <p id="media-count">Media Count: 0</p>
            </section>

            <!-- Instagram Metrics Section -->
            <section id="instagram-metrics">
                <h3>Instagram Metrics</h3>
                <p id="followers-count"></p>
                <p id="media-count"></p>
            </section>

            <!-- Campaigns Section -->
            <section id="campaigns">
                <h3>Available Campaigns</h3>
                <select id="filter-category">
                    <option value="">All Categories</option>
                    <option value="Fashion">Fashion</option>
                    <option value="Technology">Technology</option>
                    <option value="Lifestyle">Lifestyle</option>
                </select>
                <ul id="campaign-list"></ul>
            </section>

            <!-- Requests Section -->
            <section id="requests">
                <h3>My Requests</h3>
                <ul id="request-list"></ul>
            </section>

            <!-- Active Campaigns Section -->
            <section id="active-campaigns">
                <h3>Active Campaigns</h3>
                <ul id="active-campaign-list"></ul>
            </section>

            <section id="earnings">
                <h3>Earnings Dashboard</h3>
                <p id="balance"></p>
                <input type="text" id="upi" placeholder="UPI ID" />
                <input type="number" id="payout-amount" placeholder="Amount" />
                <button id="payout-btn">Withdraw</button>
                <ul id="txn-list"></ul>
            </section>

            <!-- Notifications Section -->
            <section id="notifications">
                <h3>Notifications</h3>
                <ul id="notification-list"></ul>
            </section>

            <section id="analytics" style="display:none;">
                <h3>Performance Analytics</h3>
                <canvas id="infChart"></canvas>
            </section>

        </div>
    </div>

    <div id="reelUploadPopup" class="popup" style="display:none;">
        <button id="closePopupButton" class="close-popup-btn">X</button>
        <h3>Apply to Campaign</h3>
        <p id="metricInfo"></p>
        <textarea id="applyMessage" placeholder="Message to brand" style="width:100%;margin-bottom:10px;"></textarea>
        <input type="file" id="reelInput" />
        <button id="submitRequestButton">Submit Request</button>
    </div>

    <script>
        let influencerUID = null;
        let csrfToken = '';
        fetch('/backend/auth.php?action=csrf').then(r=>r.json()).then(d=>{csrfToken=d.token;});

        // Load profile data
        async function loadProfile() {
            try {
                const response = await fetch('../backend/influencer.php?action=profile');
                const result = await response.json();
                if (result.success) {
                    const profile = result.data;
                    influencerUID = profile.id;
                    document.getElementById('profile-name').textContent = profile.username || 'Username';
                    document.getElementById('profile-email').textContent = profile.email || '';
                    document.getElementById('profile-pic').src = profile.profile_pic || '';
                    document.getElementById('followers-count').textContent = `Followers: ${profile.followers_count || 0}`;
                    document.getElementById('media-count').textContent = `Media Count: ${profile.media_count || 0}`;
                } else {
                    console.error('Failed to load profile:', result.message);
                }
            } catch (error) {
                console.error('Error loading profile:', error);
            }
        }

        async function refreshProfile() {
            try {
                await fetch('../backend/influencer.php?action=refresh_profile');
            } catch (e) {
                console.error('Refresh failed', e);
            }
        }


        // Load campaigns
        async function loadCampaigns() {
            try {
                const response = await fetch('../backend/influencer.php?action=list_campaigns');
                const result = await response.json();
                const campaignList = document.getElementById('campaign-list');
                campaignList.innerHTML = '';
                if (result.success && result.data.length > 0) {
                    result.data.forEach(campaign => {
                        const li = document.createElement('li');
                        li.innerHTML = `
                            <div class="campaign-card">
                                ${campaign.image_url ? `<img src="${campaign.image_url}" alt="Campaign Image" />` : ''}
                                <h3>${campaign.name}</h3>
                                <p>${campaign.description}</p>
                                <p>Category: ${campaign.category}</p>
                                <button class="raise-request-btn" data-campaign-id="${campaign.id}">Raise Request</button>
                            </div>
                        `;
                        campaignList.appendChild(li);
                    });

                    // Add event listeners for raise request buttons
                    document.querySelectorAll('.raise-request-btn').forEach(button => {
                        button.addEventListener('click', () => {
                            const campaignId = button.getAttribute('data-campaign-id');
                            showReelUploadPopup(campaignId);
                        });
                    });
                } else {
                    campaignList.innerHTML = '<p>No campaigns found.</p>';
                }
            } catch (error) {
                console.error('Error loading campaigns:', error);
            }
        }

        // Show reel upload popup
        function showReelUploadPopup(campaignId) {
            const popup = document.getElementById('reelUploadPopup');
            popup.style.display = 'block';
            popup.dataset.campaignId = campaignId;
            document.getElementById('applyMessage').value = '';
            document.getElementById('reelInput').value = '';
            // show metrics in popup
            const profile = {
                followers: document.getElementById('followers-count').textContent.split(':')[1] || 0
            };
            document.getElementById('metricInfo').textContent = `Followers: ${profile.followers.trim()}`;
        }

        // Hide reel upload popup
        document.getElementById('closePopupButton').addEventListener('click', () => {
            const popup = document.getElementById('reelUploadPopup');
            popup.style.display = 'none';
            document.getElementById('reelInput').value = '';
        });

        // Submit request with reel upload
        document.getElementById('submitRequestButton').addEventListener('click', async () => {
            const popup = document.getElementById('reelUploadPopup');
            const campaignId = popup.dataset.campaignId;
            const reelInput = document.getElementById('reelInput');
            const reelFile = reelInput.files[0];
            const msg = document.getElementById('applyMessage').value;

            const formData = new FormData();
            formData.append('campaign_id', campaignId);
            if(reelFile) formData.append('reel', reelFile);
            formData.append('message', msg);

            try {
                const response = await fetch('../backend/influencer.php?action=submit_request', {
                    method: 'POST',
                    headers: {'X-CSRF-Token': csrfToken},
                    body: formData,
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    popup.style.display = 'none';
                    reelInput.value = '';
                    loadRequests();
                }
            } catch (error) {
                alert('Error submitting request: ' + error.message);
            }
        });

        // Load requests
       async function loadRequests() {
            try {
                const response = await fetch('../backend/influencer.php?action=list_requests');
                const result = await response.json();
                const requestList = document.getElementById('request-list');
                requestList.innerHTML = '';
                if (result.success && result.data.length > 0) {
                    result.data.forEach(request => {
                        const li = document.createElement('li');
                        li.innerHTML = `
                            <div class="request-card">
                                <p>Campaign ID: ${request.campaign_id}</p>
                                <p>Status: ${request.status}</p>
                                <p>Requested At: ${request.created_at}</p>
                            </div>
                        `;
                        requestList.appendChild(li);
                    });
                } else {
                    requestList.innerHTML = '<p>No requests found.</p>';
                }
           } catch (error) {
               console.error('Error loading requests:', error);
           }
       }

        async function loadActiveCampaigns(){
            try{
                const r = await fetch('../backend/submissions.php?action=list');
                const d = await r.json();
                const list=document.getElementById('active-campaign-list');
                list.innerHTML='';
                if(d.success){
                    const live=d.data.filter(s=>s.status==='live' || s.status==='completed');
                    for(const s of live){
                        const li=document.createElement('li');
                        li.innerHTML=`<span class="money">💰</span> Post ID: ${s.post_id || ''} - <a href="${s.posted_url||'#'}" target="_blank">View</a> <span class="metrics" id="m${s.id}"></span>`;
                        list.appendChild(li);
                        fetch('/backend/metrics.php?action=post_metrics&submission_id='+s.id)
                            .then(r=>r.json()).then(m=>{ if(m.success){ document.getElementById('m'+s.id).textContent = ` Reach ${m.data.metrics.reach||0} Earn $${m.data.estimated_earnings.toFixed(2)}`; }});
                    }
                }
            }catch(e){console.error(e);}
        }

        let chart;
        async function loadAnalytics() {
            const res = await fetch('../backend/metrics.php?action=overview');
            const data = await res.json();
            if (!data.success) return;
            const m = data.data.metrics || {};
            const ctx = document.getElementById('infChart').getContext('2d');
            const vals = [m.reach||0, m.impressions||0, m.likes||0, m.comments||0, m.shares||0, m.saves||0];
            if (!chart) {
                chart = new Chart(ctx, {type:'bar',data:{labels:['Reach','Impressions','Likes','Comments','Shares','Saves'],datasets:[{label:'Metrics',backgroundColor:'#D0007D',data:vals}]}});
            } else {
                chart.data.datasets[0].data = vals;
                chart.update();
            }
        }

        async function loadEarnings(){
            const r=await fetch('../backend/wallet.php?action=balance');
            const d=await r.json();
            if(d.success){
                document.getElementById('balance').textContent=`Balance: $${d.data.balance}`;
            }
            const tr=await fetch('/backend/wallet.php?action=transactions');
            const td=await tr.json();
            const list=document.getElementById('txn-list');
            list.innerHTML='';
            if(td.success){
                td.data.forEach(t=>{const li=document.createElement('li');li.textContent=`${t.type}: $${t.amount} (${t.description})`;list.appendChild(li);});
            }
        }

        document.getElementById('payout-btn').addEventListener('click',async()=>{
            const amt=parseFloat(document.getElementById('payout-amount').value||0);
            const upi=document.getElementById('upi').value;
            if(!amt||!upi){alert('UPI and amount required');return;}
            const fd=new FormData();fd.append('amount',amt);fd.append('upi',upi);
            const r=await fetch('/backend/wallet.php?action=payout',{method:'POST',headers:{'X-CSRF-Token': csrfToken},body:fd});
            const d=await r.json();alert(d.message);if(d.success) loadEarnings();
        });

        // Navigation behavior
        const links = document.querySelectorAll('.sidebar ul li a');
        const sections = document.querySelectorAll('section');

        links.forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const targetID = link.getAttribute('href').slice(1);

                // Hide all sections
                sections.forEach(section => (section.style.display = 'none'));

                // Show the selected section
                document.getElementById(targetID).style.display = 'block';
            });
        });

        // Default section to show
        sections.forEach(section => (section.style.display = 'none'));
        document.getElementById('profile').style.display = 'block';

        // Initial load
        document.addEventListener('DOMContentLoaded', () => {
            refreshProfile().then(loadProfile);
            loadCampaigns();
            loadRequests();
            loadActiveCampaigns();
            loadAnalytics();
            loadEarnings();
            // apply saved theme
            if(localStorage.getItem('theme')==='dark'){
                document.body.classList.add('dark-mode');
            }
        });

        document.getElementById('theme-toggle').addEventListener('click', () => {
            const dark = document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        });
    </script>
</body>
</html>
