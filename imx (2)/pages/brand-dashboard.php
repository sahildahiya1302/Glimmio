<?php
require_once __DIR__ . '/../includes/security.php';
secure_session_start();
secure_page_headers();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'brand') {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Brand Dashboard</title>
    <link rel="stylesheet" href="/../css/brand-dashboard-style.css" />
    <link rel="stylesheet" href="/../css/instagram-theme.css" />
    <link rel="stylesheet" href="/../css/dark-theme.css" />
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
            <h2>Brand Dashboard</h2>
            <section id="profile">
                <img id="profile-pic" src="" alt="Profile Picture" />
                <p id="profile-name">Brand Name</p>
                <p id="profile-email">brand@example.com</p>
            </section>
            <ul>
                <li><a href="#post-campaign" class="active">Post Campaign</a></li>
                <li><a href="#campaign-list">View Campaigns</a></li>
                <li><a href="influencer-directory.php">Browse Influencers</a></li>
                <li><a href="#request-list">View Requests</a></li>
                <li><a href="#wallet">Wallet</a></li>
                <li><a href="#submission-list">Content Submissions</a></li>
                <li><a href="#analytics">Analytics</a></li>
            </ul>
        </div>

        <div class="main-content">
            <section id="post-campaign">
                <h3>Post a New Campaign</h3>
                <form id="post-campaign-form" enctype="multipart/form-data">
                    <label for="campaign-image">Campaign Image</label>
                    <input type="file" id="campaign-image" name="image" />
                    <label for="campaign-title">Campaign Title</label>
                    <input type="text" id="campaign-title" name="title" placeholder="Campaign Title" required />
                    <label for="campaign-objective">Objective</label>
                    <input type="text" id="campaign-objective" name="objective" placeholder="Objective" />
                    <label for="campaign-description">Description</label>
                    <textarea id="campaign-description" name="description" placeholder="Campaign Description" required></textarea>
                    <label for="campaign-category">Category</label>
                    <input type="text" id="campaign-category" name="category" placeholder="Category" />

                    <label for="campaign-guidelines">Guidelines</label>
                    <textarea id="campaign-guidelines" name="guidelines" placeholder="Guidelines"></textarea>
                    <label for="campaign-hashtags">Hashtags/Mentions</label>
                    <input type="text" id="campaign-hashtags" name="hashtags_required" placeholder="#brand @mention" />
                    <label for="campaign-captions">Caption Suggestions</label>
                    <textarea id="campaign-captions" name="caption_examples" placeholder="Example captions"></textarea>

                    <label for="campaign-file">Brief Upload</label>
                    <input type="file" id="campaign-file" name="brief_file" />

                    <label for="campaign-min-followers">Min Followers</label>
                    <input type="number" id="campaign-min-followers" name="min_followers" placeholder="0" />
                    <label for="campaign-badge">Badge Requirement</label>
                    <select id="campaign-badge" name="badge_min">
                        <option value="bronze">Bronze</option>
                        <option value="silver">Silver</option>
                        <option value="gold">Gold</option>
                        <option value="elite">Elite</option>
                    </select>
                    <label for="campaign-max-inf">Max Influencers</label>
                    <input type="number" id="campaign-max-inf" name="max_influencers" />

                    <label for="campaign-start">Start Date</label>
                    <input type="date" id="campaign-start" name="start_date" />
                    <label for="campaign-end">End Date</label>
                    <input type="date" id="campaign-end" name="end_date" />
                    <label for="campaign-goal">Goal Type</label>
                    <select id="campaign-goal" name="goal_type">
                        <option value="CPM">CPM</option>
                        <option value="CPE">CPE</option>
                    </select>
                    <label for="campaign-target">Target Metrics</label>
                    <input type="number" id="campaign-target" name="target_metrics" placeholder="10000" />
                    <label for="campaign-rate">Rate</label>
                    <input type="number" id="campaign-rate" name="rate" placeholder="Rate" required />
                    <label for="campaign-budget">Total Budget</label>
                    <input type="number" id="campaign-budget" name="budget_total" placeholder="Budget" required />
                    <button type="submit" id="post-campaign-btn">Post Campaign</button>
                </form>
            </section>

            <section id="campaign-list">
                <h3>Your Campaigns</h3>
                <ul></ul>
            </section>

            <section id="request-list">
                <h3>Requests</h3>
                <ul></ul>
            </section>

            <section id="wallet">
                <h3>Wallet</h3>
                <p id="brand-balance"></p>
                <input type="number" id="add-funds-amt" placeholder="Amount" />
                <button id="add-funds-btn">Add Funds</button>
                <ul id="brand-tx"></ul>
            </section>

            <section id="submission-list" style="display:none;">
                <h3>Content Submissions</h3>
                <ul></ul>
            </section>

            <section id="analytics" style="display:none;">
                <h3>Performance Analytics</h3>
                <select id="analytics-campaign"></select>
                <canvas id="brandChart"></canvas>
                <h4>Projections</h4>
                <canvas id="projectionChart" height="100"></canvas>
                <p id="projection-suggestion"></p>
            </section>

        </div>
    </div>

    <script>
        let csrfToken = '';
        fetch('/backend/auth.php?action=csrf').then(r=>r.json()).then(d=>{csrfToken=d.token;});
        // Load profile data from backend API
        async function loadProfile() {
            try {
                const response = await fetch('/backend/metrics.php?action=me');
                const result = await response.json();
                if (result.success) {
                    const profile = result.data;
                    document.getElementById('profile-name').textContent = profile.username || 'Brand Name';
                    document.getElementById('profile-email').textContent = profile.email || 'brand@example.com';
                    document.getElementById('profile-pic').src = profile.profilePic || '';
                } else {
                    console.error('Failed to load profile:', result.message);
                }
            } catch (error) {
                console.error('Error loading profile:', error);
            }
        }

        // Post a new campaign
        document.getElementById('post-campaign-form').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('/backend/campaigns.php?action=post_campaign', {
                    method: 'POST',
                    headers: {'X-CSRF-Token': csrfToken},
                    body: formData,
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    this.reset();
                    loadCampaigns();
                }
            } catch (error) {
                alert('Error posting campaign: ' + error.message);
            }
        });

        // Load campaigns
        async function loadCampaigns() {
            try {
                const response = await fetch('/backend/campaigns.php?action=list_campaigns');
                const result = await response.json();
                const campaignList = document.querySelector('#campaign-list ul');
                campaignList.innerHTML = '';
                if (result.success && result.data.length > 0) {
                    const select = document.getElementById('analytics-campaign');
                    if (select) {
                        select.innerHTML = '<option value="">All Campaigns</option>';
                    }
                    result.data.forEach((campaign) => {
                        const li = document.createElement('li');
                        li.innerHTML = `
                            <div class="campaign-card">
                                ${campaign.image_url ? `<img src="${campaign.image_url}" alt="Campaign Image" />` : ''}
                                <h3>${campaign.title}</h3>
                                <p>${campaign.description}</p>
                                <p>Category: ${campaign.category || ''}</p>
                                <p>Goal: ${campaign.goal_type} @ ${campaign.rate}</p>
                                <p>Target: ${campaign.target_metrics || ''}</p>
                                <p>Budget: ${campaign.budget_total}</p>
                                <p>Min Followers: ${campaign.min_followers}</p>
                                <p>Badge Requirement: ${campaign.badge_min}</p>
                                <p>Status: <span class="status-dot ${campaign.status}"></span> ${campaign.status}</p>
                                <button class="view-requests-button" data-id="${campaign.id}">View Accepted Requests</button>
                                <div class="requests-container" id="requests-${campaign.id}" style="display: none;">
                                    <h4>Accepted Requests:</h4>
                                    <div class="requests-list"></div>
                                </div>
                                <button class="end-campaign-button" data-id="${campaign.id}">End Campaign</button>
                            </div>
                        `;
                        campaignList.appendChild(li);
                        if (select) {
                            const o=document.createElement('option');
                            o.value=campaign.id;
                            o.textContent=campaign.title;
                            select.appendChild(o);
                        }
                    });
                    if (select) {
                        select.addEventListener('change', loadAnalytics);
                    }

                    // Attach event listeners to buttons
                    document.querySelectorAll('.view-requests-button').forEach(button => {
                        button.addEventListener('click', () => {
                            const campaignId = button.getAttribute('data-id');
                            toggleRequests(campaignId);
                        });
                    });

                    document.querySelectorAll('.end-campaign-button').forEach(button => {
                        button.addEventListener('click', () => {
                            const campaignId = button.getAttribute('data-id');
                            endCampaign(campaignId);
                        });
                    });
                } else {
                    campaignList.innerHTML = '<p>No campaigns found.</p>';
                }
            } catch (error) {
                console.error('Error loading campaigns:', error);
            }
        }

        // Toggle requests display for a campaign
        async function toggleRequests(campaignId) {
            const container = document.getElementById(`requests-${campaignId}`);
            if (container.style.display === 'block') {
                container.style.display = 'none';
                container.querySelector('.requests-list').innerHTML = '';
            } else {
                container.style.display = 'block';
                await loadRequests(campaignId);
            }
        }

        // Load requests for a campaign
        async function loadRequests(campaignId) {
            try {
                const response = await fetch('/backend/requests.php?action=list_requests');
                const result = await response.json();
                const requestsList = document.querySelector(`#requests-${campaignId} .requests-list`);
                requestsList.innerHTML = '';
                if (result.success && result.data.length > 0) {
                    const filteredRequests = result.data.filter(r => r.campaign_id == campaignId);
                    if (filteredRequests.length === 0) {
                        requestsList.innerHTML = '<p>No requests found.</p>';
                        return;
                    }
                    filteredRequests.forEach(request => {
                        const div = document.createElement('div');
                        div.classList.add('request-item');
                        div.innerHTML = `
                            <p><strong>Influencer:</strong> ${request.influencer_uid}</p>
                            <p>Status: ${request.status}</p>
                            <p>Created At: ${request.created_at}</p>
                            <div class="action-buttons">
                                ${request.status === 'pending' ? `
                                <button class="accept-request-button" data-id="${request.id}" data-influencer="${request.influencer_uid}">Accept</button>
                                <button class="reject-request-button" data-id="${request.id}">Reject</button>
                                ` : ''}
                            </div>
                        `;
                        requestsList.appendChild(div);
                    });

                    // Attach event listeners for accept/reject buttons
                    requestsList.querySelectorAll('.accept-request-button').forEach(button => {
                        button.addEventListener('click', () => {
                            const requestId = button.getAttribute('data-id');
                            const influencerUID = button.getAttribute('data-influencer');
                            updateRequestStatus(requestId, 'accepted');
                        });
                    });
                    requestsList.querySelectorAll('.reject-request-button').forEach(button => {
                        button.addEventListener('click', () => {
                            const requestId = button.getAttribute('data-id');
                            updateRequestStatus(requestId, 'rejected');
                        });
                    });
                } else {
                    requestsList.innerHTML = '<p>No requests found.</p>';
                }
            } catch (error) {
                console.error('Error loading requests:', error);
            }
        }

        // Update request status (accept/reject)
        async function updateRequestStatus(requestId, status) {
            try {
                const formData = new FormData();
                formData.append('request_id', requestId);
                formData.append('status', status);

                const response = await fetch('/backend/requests.php?action=update_request_status', {
                    method: 'POST',
                    headers: {'X-CSRF-Token': csrfToken},
                    body: formData,
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    loadCampaigns();
                }
            } catch (error) {
                alert('Error updating request status: ' + error.message);
            }
        }

        // End a campaign (update status)
        async function endCampaign(campaignId) {
            try {
                const formData = new FormData();
                formData.append('campaign_id', campaignId);
                formData.append('status', 'ended');

                const response = await fetch('/backend/campaigns.php?action=end_campaign', {
                    method: 'POST',
                    headers: {'X-CSRF-Token': csrfToken},
                    body: formData,
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    loadCampaigns();
                }
            } catch (error) {
                alert('Error ending campaign: ' + error.message);
            }
        }

        // Load content submissions
        async function loadSubmissions() {
            try {
                const response = await fetch('/backend/submissions.php?action=list');
                const result = await response.json();
                const list = document.querySelector('#submission-list ul');
                list.innerHTML = '';
                if (result.success && result.data.length > 0) {
                    result.data.forEach(sub => {
                        const li = document.createElement('li');
                        li.innerHTML = `
                            <div class="submission-item">
                                <p>Campaign ID: ${sub.campaign_id}</p>
                                <p>Status: ${sub.status}</p>
                                ${sub.media_url ? `<a href="${sub.media_url}" target="_blank">View Media</a>` : ''}
                                <span class="metrics" id="bm${sub.id}"></span>
                                ${sub.status === 'pending' || sub.status === 'needs_revision' ? `
                                    <button class="approve-sub" data-id="${sub.id}">Approve</button>
                                    <button class="reject-sub" data-id="${sub.id}">Reject</button>
                                ` : ''}
                            </div>
                        `;
                        list.appendChild(li);
                        if(sub.status==='live' || sub.status==='completed'){
                            fetch('/backend/metrics.php?action=post_metrics&submission_id='+sub.id)
                                .then(r=>r.json()).then(m=>{if(m.success){document.getElementById('bm'+sub.id).textContent=` Reach ${m.data.metrics.reach||0} Earn $${m.data.estimated_earnings.toFixed(2)}`;}});
                        }
                    });

                    document.querySelectorAll('.approve-sub').forEach(btn => {
                        btn.addEventListener('click', () => updateSubmission(btn.getAttribute('data-id'), 'approved'));
                    });
                    document.querySelectorAll('.reject-sub').forEach(btn => {
                        btn.addEventListener('click', () => updateSubmission(btn.getAttribute('data-id'), 'rejected'));
                    });
                } else {
                    list.innerHTML = '<p>No submissions.</p>';
                }
            } catch (err) {
                console.error('Error loading submissions:', err);
            }
        }

        async function updateSubmission(id, status) {
            const formData = new FormData();
            formData.append('submission_id', id);
            formData.append('status', status);
            const response = await fetch('/backend/submissions.php?action=update', {
                method: 'POST',
                    headers: {'X-CSRF-Token': csrfToken},
                body: formData,
            });
            const result = await response.json();
            alert(result.message);
            if (result.success) {
                loadSubmissions();
            }
        }

        async function loadWallet(){
            const r=await fetch('/backend/wallet.php?action=balance');
            const d=await r.json();
            if(d.success){document.getElementById('brand-balance').textContent=`Balance: $${d.data.balance}`;}
            const t=await fetch('/backend/wallet.php?action=transactions');
            const td=await t.json();
            const list=document.getElementById('brand-tx');
            list.innerHTML='';
            if(td.success){td.data.forEach(tx=>{const li=document.createElement('li');li.textContent=`${tx.type}: $${tx.amount}`;list.appendChild(li);});}
        }

        document.getElementById('add-funds-btn').addEventListener('click',async()=>{
            const amt=parseFloat(document.getElementById('add-funds-amt').value||0);
            if(!amt){alert('Amount required');return;}
            const fd=new FormData();fd.append('amount',amt);
            const r=await fetch('/backend/wallet.php?action=add_funds',{method:'POST',headers:{'X-CSRF-Token': csrfToken},body:fd});
            const d=await r.json();alert(d.message);if(d.success) loadWallet();
        });


        let chart;
        let projChart;
        async function loadAnalytics() {
            const sel = document.getElementById('analytics-campaign');
            const campaignId = sel.value || '';
            let url = '/backend/metrics.php?action=overview';
            if (campaignId) url += '&campaign_id=' + encodeURIComponent(campaignId);
            const res = await fetch(url);
            const data = await res.json();
            if (!data.success) return;
            const m = data.data.metrics || {};
            const ctx = document.getElementById('brandChart').getContext('2d');
            const vals = [m.reach||0, m.impressions||0, m.likes||0, m.comments||0, m.shares||0, m.saves||0];
            if (!chart) {
                chart = new Chart(ctx, {type:'bar',data:{labels:['Reach','Impressions','Likes','Comments','Shares','Saves'],datasets:[{label:'Metrics',backgroundColor:'#D0007D',data:vals}]}});
            } else {
                chart.data.datasets[0].data = vals;
                chart.update();
            }

            // fetch projections
            let purl = '/backend/metrics.php?action=projections';
            if (campaignId) purl += '&campaign_id=' + encodeURIComponent(campaignId);
            const pres = await fetch(purl);
            const pdata = await pres.json();
            const div = document.getElementById('projection-suggestion');
            if (pdata.success && pdata.data) {
                const pr = pdata.data;
                div.textContent = pr.suggestion;
                const pctx = document.getElementById('projectionChart').getContext('2d');
                const pvals = [pr.current, pr.projected, pr.target||0];
                const labels = ['Current','Projected','Target'];
                if (!projChart) {
                    projChart = new Chart(pctx, {type:'bar',data:{labels:labels,datasets:[{label:'Metrics',backgroundColor:'#0094FF',data:pvals}]}});
                } else {
                    projChart.data.datasets[0].data = pvals;
                    projChart.update();
                }
            } else {
                div.textContent = 'No projection data';
            }
        }

        // Initial load
        document.addEventListener('DOMContentLoaded', () => {
            loadProfile();
            loadCampaigns().then(loadAnalytics);
            loadSubmissions();
            loadWallet();
            if(localStorage.getItem('theme')==='dark'){
                document.body.classList.add('dark-mode');
            }

            const links = document.querySelectorAll('.sidebar ul li a');
            const sections = document.querySelectorAll('.main-content section');
            links.forEach(link => {
                link.addEventListener('click', e => {
                    e.preventDefault();
                    const targetID = link.getAttribute('href').substring(1);
                    sections.forEach(sec => sec.style.display = 'none');
                    const target = document.getElementById(targetID);
                    if (target) target.style.display = 'block';
                });
            });
            sections.forEach(sec => sec.style.display = 'none');
            document.getElementById('post-campaign').style.display = 'block';
        });

        document.getElementById('theme-toggle').addEventListener('click', () => {
            const dark = document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        });
    </script>
</body>
</html>