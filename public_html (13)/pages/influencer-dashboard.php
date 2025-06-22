<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Influencer Dashboard</title>
    <link rel="stylesheet" href="/../css/influencer-dashboard-style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>Influencer Dashboard</h2>
            <ul>
                <li><a href="#profile" data-section="profile"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="#campaigns" data-section="campaigns"><i class="fas fa-bullhorn"></i> View Campaigns</a></li>
                <li><a href="#requests" data-section="requests"><i class="fas fa-list-alt"></i> My Requests</a></li>
                <li><a href="#active-campaigns" data-section="active-campaigns"><i class="fas fa-chart-line"></i> Active Campaigns</a></li>
                <li><a href="#notifications" data-section="notifications"><i class="fas fa-bell"></i> Notifications</a></li>
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

            <!-- Notifications Section -->
            <section id="notifications">
                <h3>Notifications</h3>
                <ul id="notification-list"></ul>
            </section>
        </div>
    </div>

    <div id="reelUploadPopup" class="popup" style="display:none;">
        <button id="closePopupButton" class="close-popup-btn">X</button>
        <h3>Upload Your Reel</h3>
        <input type="file" id="reelInput" />
        <button id="submitRequestButton">Submit Request</button>
    </div>

    <script>
        let influencerUID = null;

        // Load profile data
        async function loadProfile() {
            try {
                const response = await fetch('/backend/influencer.php?action=profile');
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

        // Load campaigns
        async function loadCampaigns() {
            try {
                const response = await fetch('/backend/influencer.php?action=list_campaigns');
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

            if (!reelFile) {
                alert('Please select a reel file to upload.');
                return;
            }

            const formData = new FormData();
            formData.append('campaign_id', campaignId);
            formData.append('reel', reelFile);

            try {
                const response = await fetch('/backend/influencer.php?action=submit_request', {
                    method: 'POST',
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
                const response = await fetch('/backend/influencer.php?action=list_requests');
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
            loadProfile();
            loadCampaigns();
            loadRequests();
        });
    </script>
</body>
</html>
