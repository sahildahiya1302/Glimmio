document.addEventListener('DOMContentLoaded', () => {
    window.csrfToken = ''; // make it global
    let oauthConfig = null;

    // Fetch Config and CSRF Token
    fetch('/backend/config.php')
        .then(r => r.json())
        .then(d => { oauthConfig = d; });

    fetch('/backend/auth.php?action=csrf')
  .then(async r => {
    const text = await r.text();
    try {
      const d = JSON.parse(text);
      window.csrfToken = d.token;
      ['csrf_login', 'csrf_brand', 'csrf_influencer', 'csrf_generic'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = window.csrfToken;
      });
    } catch (e) {
      console.error('CSRF response was not JSON:', text);
      alert("CSRF token fetch failed. Server error.");
    }
  })
  .catch(err => {
    console.error('CSRF fetch error:', err);
    alert("CSRF fetch failed. Please try again later.");
  });

    // Utility to show/hide modal/form
    const show = el => el && (el.style.display = 'block');
    const hide = el => el && (el.style.display = 'none');

    const els = {
        brandModal: document.getElementById('brand-register-modal'),
        influencerModal: document.getElementById('influencer-register-modal'),
        userTypeModal: document.getElementById('user-type-modal'),
        loginForm: document.getElementById('login-form-container'),
        registerForm: document.getElementById('register-form-container')
    };

    // Modal navigation buttons
    const actions = [
        ['show-register', () => { hide(els.loginForm); show(els.userTypeModal); }],
        ['close-user-type', () => { hide(els.userTypeModal); show(els.loginForm); }],
        ['close-brand-register', () => { hide(els.brandModal); show(els.loginForm); }],
        ['close-influencer-register', () => { hide(els.influencerModal); show(els.loginForm); }],
        ['select-brand', () => { hide(els.userTypeModal); show(els.brandModal); }],
        ['select-influencer', () => { hide(els.userTypeModal); show(els.influencerModal); }]
    ];

    actions.forEach(([id, handler]) => {
        const btn = document.getElementById(id);
        if (btn) btn.addEventListener('click', handler);
    });

    // "Back to login" buttons
    document.querySelectorAll('.show-login').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            hide(els.brandModal);
            hide(els.influencerModal);
            hide(els.userTypeModal);
            show(els.loginForm);
        });
    });

    // Click outside modals
    window.onclick = function (event) {
        if (event.target === els.brandModal || event.target === els.influencerModal || event.target === els.userTypeModal) {
            hide(els.brandModal);
            hide(els.influencerModal);
            hide(els.userTypeModal);
            show(els.loginForm);
        }
    };

    // Login form
    const loginFormEl = document.getElementById('email-login-form');
    if (loginFormEl) {
        loginFormEl.addEventListener('submit', async function (e) {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            const response = await fetch('/backend/auth.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'login',
                    email,
                    password,
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
    }

    // Brand registration
    const brandForm = document.getElementById('brand-register-form');
    if (brandForm) {
        brandForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const email = document.getElementById('brand-register-email').value;
            const company = document.getElementById('brand-register-company').value;
            const website = document.getElementById('brand-register-website').value;
            const industry = document.getElementById('brand-register-industry').value;
            const password = document.getElementById('brand-register-password').value;
            const confirm = document.getElementById('brand-register-password-confirm').value;

            if (password !== confirm) return alert('Passwords do not match.');

            const otpRes = await fetch('/backend/auth.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'send_otp', email, csrf_token: csrfToken })
            });
            const otpData = await otpRes.json();
            if (!otpData.success) return alert(otpData.message);

            const code = prompt('Enter OTP sent to your email');
            if (!code) return;

            const regRes = await fetch('/backend/auth.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'register',
                    role: 'brand',
                    email, password, company_name: company, website, industry,
                    otp: code, csrf_token: csrfToken
                })
            });
            const regData = await regRes.json();
            alert(regData.message);
            if (regData.success) {
                hide(els.brandModal);
                show(els.loginForm);
            }
        });
    }

    // Influencer registration
    const inflForm = document.getElementById('influencer-register-form');
    if (inflForm) {
        inflForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const email = document.getElementById('influencer-register-email').value;
            const handle = document.getElementById('influencer-register-handle').value;
            const category = document.getElementById('influencer-register-category').value;
            const password = document.getElementById('influencer-register-password').value;
            const confirm = document.getElementById('influencer-register-password-confirm').value;

            if (password !== confirm) return alert('Passwords do not match.');

            const otpRes = await fetch('/backend/auth.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'send_otp', email, csrf_token: csrfToken })
            });
            const otpData = await otpRes.json();
            if (!otpData.success) return alert(otpData.message);

            const code = prompt('Enter OTP sent to your email');
            if (!code) return;

            const regRes = await fetch('/backend/auth.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'register',
                    role: 'influencer',
                    email, password, instagram_handle: handle, category,
                    otp: code, csrf_token: csrfToken
                })
            });
            const regData = await regRes.json();
            alert(regData.message);
            if (regData.success) {
                hide(els.influencerModal);
                show(els.loginForm);
            }

            if (regData.success && oauthConfig) {
                const scopes = [
                    'pages_user_timezone', 'instagram_branded_content_creator',
                    'instagram_branded_content_brand', 'instagram_manage_events',
                    'instagram_business_basic', 'instagram_business_manage_messages',
                    'instagram_business_content_publish', 'instagram_business_manage_insights',
                    'instagram_business_manage_comments', 'pages_read_engagement',
                    'ads_management', 'instagram_content_publish', 'instagram_manage_comments'
                ].join(',');
                window.location.href = `https://api.instagram.com/oauth/authorize?client_id=${encodeURIComponent(oauthConfig.instagramClientId)}&redirect_uri=${encodeURIComponent(oauthConfig.instagramRedirect)}&scope=${encodeURIComponent(scopes)}&response_type=code`;
            }
        });
    }

    // Theme toggle
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }

    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const dark = document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        });
    }
});
