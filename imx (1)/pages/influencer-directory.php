<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Influencer Directory</title>
    <link rel="stylesheet" href="/../css/brand-dashboard-style.css" />
    <link rel="stylesheet" href="/../css/instagram-theme.css" />
</head>
<body>
    <div class="dashboard-container">
        <h2>Influencer Directory</h2>
        <div>
            <label for="filter-cat">Category:</label>
            <input type="text" id="filter-cat" />
            <label for="filter-ind">Industry:</label>
            <input type="text" id="filter-ind" />
            <label for="filter-budget">Budget/Reach &gt;=</label>
            <input type="number" id="filter-budget" />
            <label for="filter-loc">Location:</label>
            <input type="text" id="filter-loc" />
            <button id="load-btn">Load</button>
        </div>
        <table id="inf-table">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Badge</th><th>Category</th><th>Followers</th><th>Reach</th><th>Engagement</th><th>Invite</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let campaigns = [];
async function loadCampaigns() {
    const res = await fetch('/backend/campaigns.php?action=list_campaigns');
    const data = await res.json();
    if(data.success){ campaigns = data.data; }
}
async function loadInfluencers(){
    const cat=document.getElementById('filter-cat').value;
    let url='/backend/influencer.php?action=list_all';
    if(cat) url+='&category='+encodeURIComponent(cat);
    const res=await fetch(url);
    const data=await res.json();
    const tbody=document.querySelector('#inf-table tbody');
    tbody.innerHTML='';
    if(data.success){
        let rows=data.data;
        const reachMin=parseInt(document.getElementById('filter-budget').value||0);
        if(reachMin) rows=rows.filter(r=>(parseInt(r.reach||0)>=reachMin));
        rows.forEach(i=>{
            const tr=document.createElement('tr');
            tr.innerHTML=`<td>${i.username||''}</td><td>${i.email}</td><td>${i.badge_level}</td><td>${i.category||''}</td><td>${i.followers_count||0}</td><td>${i.reach||0}</td><td>${i.engagement||0}</td>`;
            const td=document.createElement('td');
            const sel=document.createElement('select');
            campaigns.forEach(c=>{ const o=document.createElement('option'); o.value=c.id; o.textContent=c.title; sel.appendChild(o); });
            const btn=document.createElement('button'); btn.textContent='Invite';
            btn.onclick=async()=>{
                await fetch('/backend/requests.php?action=invite',{method:'POST',body:new URLSearchParams({campaign_id:sel.value,influencer_id:i.id})});
                alert('Invitation sent');
            };
            td.appendChild(sel); td.appendChild(btn); tr.appendChild(td);
            tbody.appendChild(tr);
        });
    }
}

document.getElementById('load-btn').onclick=loadInfluencers;

document.addEventListener('DOMContentLoaded',()=>{loadCampaigns();loadInfluencers();});
</script>
</body>
</html>
