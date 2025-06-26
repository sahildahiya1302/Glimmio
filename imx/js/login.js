document.addEventListener('DOMContentLoaded', () => {
    let csrfToken = '';
    let oauthConfig = null;

    fetch('/backend/config.php')
        .then(r => r.json())
        .then(d => { oauthConfig = d; });
    fetch('/backend/auth.php?action=csrf')
        .then(r => r.json())
        .then(d => {
            csrfToken = d.token;
            document.getElementById('csrf_login').value = csrfToken;
            document.getElementById('csrf_brand').value = csrfToken;
            document.getElementById('csrf_influencer').value = csrfToken;
            const gen = document.getElementById('csrf_generic');
            if (gen) gen.value = csrfToken;
        });

    const brandModal = document.getElementById('brand-register-modal');
    const influencerModal = document.getElementById('influencer-register-modal');
    const userTypeModal = document.getElementById('user-type-modal');
    const openRegisterBtn = document.getElementById('show-register');
    const closeUserTypeBtn = document.getElementById('close-user-type');
    const closeBrandBtn = document.getElementById('close-brand-register');
    const closeInfluencerBtn = document.getElementById('close-influencer-register');
    const selectBrandBtn = document.getElementById('select-brand');
    const selectInfluencerBtn = document.getElementById('select-influencer');
    const loginForm = document.getElementById('login-form-container');
    const registerForm = document.getElementById('register-form-container');
    const showLoginLink = document.getElementById('show-login');

    openRegisterBtn.onclick = () => {
        loginForm.style.display = 'none';
        userTypeModal.style.display = 'block';
    };
    closeUserTypeBtn.onclick = () => {
        userTypeModal.style.display = 'none';
        loginForm.style.display = 'block';
    };
    closeBrandBtn.onclick = () => {
        brandModal.style.display = 'none';
        loginForm.style.display = 'block';
    };
    closeInfluencerBtn.onclick = () => {
        influencerModal.style.display = 'none';
        loginForm.style.display = 'block';
    };

    selectBrandBtn.onclick = () => {
        userTypeModal.style.display = 'none';
        brandModal.style.display = 'block';
    };
    selectInfluencerBtn.onclick = () => {
        userTypeModal.style.display = 'none';
        influencerModal.style.display = 'block';
    };

    showLoginLink.onclick = (e) => {
        e.preventDefault();
        brandModal.style.display = 'none';
        influencerModal.style.display = 'none';
        userTypeModal.style.display = 'none';
        loginForm.style.display = 'block';
    };

    window.onclick = function(event) {
        if (event.target === brandModal) {
            brandModal.style.display = 'none';
            loginForm.style.display = 'block';
        }
        if (event.target === influencerModal) {
            influencerModal.style.display = 'none';
            loginForm.style.display = 'block';
        }
        if (event.target === userTypeModal) {
            userTypeModal.style.display = 'none';
            loginForm.style.display = 'block';
        }
    };

    document.getElementById('email-login-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;

        const response = await fetch('/backend/auth.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'login',
                email: email,
                password: password,
                csrf_token: csrfToken
            })
        });
        const result = await response.json();
        alert(result.message);
        if (result.success && result.token) {
            localStorage.setItem('jwt', result.token);
        }
        if (result.success && result.redirect) {
            window.location.href = result.redirect;
        }
    });

    document.getElementById('brand-register-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = document.getElementById('brand-register-email').value;
        const company = document.getElementById('brand-register-company').value;
        const website = document.getElementById('brand-register-website').value;
        const industry = document.getElementById('brand-register-industry').value;
        const password = document.getElementById('brand-register-password').value;
        const passwordConfirm = document.getElementById('brand-register-password-confirm').value;

        if (password !== passwordConfirm) {
            alert('Passwords do not match.');
            return;
        }

        let resp = await fetch('/backend/auth.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action:'send_otp', email:email, csrf_token:csrfToken})
        });
        let data = await resp.json();
        if (!data.success) { alert(data.message); return; }
        const code = prompt('Enter OTP sent to your email');
        if (!code) return;
        resp = await fetch('/backend/auth.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action:'register', email, password, role:'brand', company_name:company,
                website, industry, otp:code, csrf_token:csrfToken
            })
        });
        data = await resp.json();
        alert(data.message);
        if (data.success) brandModal.style.display = 'none';
    });

    document.getElementById('influencer-register-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = document.getElementById('influencer-register-email').value;
        const handle = document.getElementById('influencer-register-handle').value;
        const category = document.getElementById('influencer-register-category').value;
        const password = document.getElementById('influencer-register-password').value;
        const passwordConfirm = document.getElementById('influencer-register-password-confirm').value;

        if (password !== passwordConfirm) {
            alert('Passwords do not match.');
            return;
        }

        let resp = await fetch('/backend/auth.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action:'send_otp', email:email, csrf_token:csrfToken})
        });
        let data = await resp.json();
        if (!data.success) { alert(data.message); return; }
        const code = prompt('Enter OTP sent to your email');
        if (!code) return;
        resp = await fetch('/backend/auth.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action:'register', email, password, role:'influencer', instagram_handle:handle,
                category, otp:code, csrf_token:csrfToken
            })
        });
        data = await resp.json();
        alert(data.message);
        if (data.success && oauthConfig) {
            influencerModal.style.display = 'none';
            const scopes = 'pages_user_timezone,instagram_branded_content_creator,instagram_branded_content_brand,instagram_manage_events,instagram_business_basic,instagram_business_manage_messages,instagram_business_content_publish,instagram_business_manage_insights,instagram_business_manage_comments,pages_read_engagement,ads_management,instagram_content_publish,instagram_manage_comments';
            window.location.href = 'https://api.instagram.com/oauth/authorize?client_id=' +
                encodeURIComponent(oauthConfig.instagramClientId) +
                '&redirect_uri=' + encodeURIComponent(oauthConfig.instagramRedirect) +
                '&scope=' + encodeURIComponent(scopes) +
                '&response_type=code';
        }
    });

    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }
    document.getElementById('theme-toggle').addEventListener('click', () => {
        const dark = document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    });
});
