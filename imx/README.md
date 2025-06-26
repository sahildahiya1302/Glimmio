# IMX Application Structure

The `imx (1)` folder contains the complete PHP web application for Glimmio. Below is a summary of the important directories and how they interact.

## Directory Overview

- **backend/** – PHP API endpoints. Implements authentication, campaign management, influencer actions and metrics. Files use prepared statements and rely on `includes/` helpers for CSRF protection, JWT creation and environment variables.
- **includes/** – Reusable PHP helpers loaded by backend scripts.
- **pages/** – HTML/PHP pages that fetch data from the backend via `fetch()` calls. Dashboards for brands, influencers and admin are implemented here.
- **css/** and **js/** – Static assets.
- **uploads/** – Uploaded images and media.
- **logs/** – Application logs.

The root of this folder also holds `dashboard.php` (main entry after login), `profile.php` for viewing a user profile, `schema.sql` defining the database, and Composer files (`composer.json`, `composer.lock`).

## File Listing

```
imx (1)/ads.txt
imx (1)/backend/admin.php
imx (1)/backend/attribution_cron.php
imx (1)/backend/auth.php
imx (1)/backend/brand.php
imx (1)/backend/campaigns.php
imx (1)/backend/community.php
imx (1)/backend/config.php
imx (1)/backend/dashboard-helper.php
imx (1)/backend/db.php
imx (1)/backend/dm.php
imx (1)/backend/influencer.php
imx (1)/backend/instagram-callback.php
imx (1)/backend/instagram-token-exchange.php
imx (1)/backend/meta_oauth.php
imx (1)/backend/metrics.php
imx (1)/backend/pixel.php
imx (1)/backend/requests.php
imx (1)/backend/submissions.php
imx (1)/backend/unsubscribe.php
imx (1)/backend/wallet.php
imx (1)/backend/webhook.php
imx (1)/composer.json
imx (1)/composer.lock
imx (1)/css/brand-dashboard-style.css
imx (1)/css/dashboard-style.css
imx (1)/css/feed-style.css
imx (1)/css/influencer-dashboard-style.css
imx (1)/css/login-style.css
imx (1)/dashboard.php
imx (1)/includes/bottom.php
imx (1)/includes/csrf.php
imx (1)/includes/env.php
imx (1)/includes/instagram_api.php
imx (1)/includes/jwt_helper.php
imx (1)/index.html
imx (1)/js/pixel.js
imx (1)/logs/auth_log.log
imx (1)/logs/mail.log
imx (1)/logs/php-error.log
imx (1)/logs/webhook_log.txt
imx (1)/pages/admin-dashboard.php
imx (1)/pages/brand-create-campaign.html
imx (1)/pages/brand-dashboard.php
imx (1)/pages/dm.php
imx (1)/pages/feed.php
imx (1)/pages/influencer-dashboard.php
imx (1)/pages/influencer-directory.php
imx (1)/pages/login.html
imx (1)/pages/onboarding.php
imx (1)/profile.php
imx (1)/schema.sql
imx (1)/sitemap.xml
imx (1)/uploads/Lakhi.png
imx (1)/uploads/WhatsApp Image 2025-06-22 at 17.33.23_7caef946.jpg
```

## Authentication and Instagram OAuth

Influencers authenticate through `/backend/auth.php` and then complete Instagram authorization. The callback (`backend/instagram-token-exchange.php`) stores a long-lived access token per user in the `instagram_tokens` table. Dashboard pages use `/backend/influencer.php` to fetch or refresh profile data with that token so each influencer sees their own metrics.

## Campaign Visibility

Brands post campaigns via `/backend/campaigns.php`. Influencers view eligible campaigns from `/backend/influencer.php?action=list_campaigns`, ensuring only campaigns matching their badge level and category appear in their dashboard.

## Security Notes

- All database operations use prepared statements.
- CSRF tokens are generated in `includes/csrf.php` and verified for sensitive operations.
- JWTs are issued after login via `includes/jwt_helper.php`.
- Environment variables loaded through `includes/env.php` keep secrets like API keys out of the codebase.

The shared `css/instagram-theme.css` stylesheet gives the interface an Instagram-inspired design. Users can enable dark mode via `css/dark-theme.css` using the new toggle button in each header.

## Profile Editing

Users can now update their username, bio, category and payout details from the new `pages/profile-edit.php` screen. Profile pictures may be uploaded and are stored under `uploads/influencers/` or `uploads/brands/` depending on role.

## Instagram API Caching

To minimise calls to Instagram and improve performance, API requests now use a simple file based cache in the new `cache/` directory. Helper functions in `includes/instagram_api.php` provide profile, insights and top media data with a short TTL. Endpoints can request cached data via `/backend/influencer.php?action=top_media`.

## Development

To install PHP dependencies, run `composer install` inside this directory. Ensure a MySQL server is available and load `schema.sql` to create tables.

Copy `.env.example` to `.env` and adjust credentials before running the app.

See [../IMPROVEMENT_PLAN.md](../IMPROVEMENT_PLAN.md) for future cleanup tasks.
