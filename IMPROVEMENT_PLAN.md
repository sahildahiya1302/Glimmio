# Glimmio Improvement Plan

This document outlines recommended steps to clean up the codebase, enhance security, and ensure reliable data handling. The repository currently contains duplicate application folders (`imx`, `imx (1)`, and `public_html (13)`). The directory `imx (1)` is used as the primary source.

## 1. Consolidate Folders
- Remove duplicate directories (`imx` and `public_html (13)`) or migrate their unique files into `imx (1)`.
- Keep a single entry point to simplify maintenance. *(Completed: extra folders consolidated.)*
## 2. Environment Management
- Store all sensitive configuration in an `.env` file. An example configuration is provided in `.env.example`.
- Ensure `.env` is excluded from version control via `.gitignore`.

## 3. Dependency Management
- Run `composer install` in `imx (1)` to install PHP dependencies such as PHPMailer.
- Commit the `vendor/` directory only if deployments cannot run Composer themselves.

## 4. Security Enhancements
- Use prepared statements everywhere (already implemented for most queries).
- Regenerate CSRF tokens per session and verify them for every state‑changing request.
- Replace `file_get_contents` network requests with cURL (see `includes/instagram_api.php` for an example) to handle errors and timeouts safely.
- Validate uploaded files (MIME type and size) to avoid malicious uploads.
- Sanitize all incoming parameters using appropriate PHP filters.

## 5. Data Handling
- Implement proper error logging in `logs/` and monitor `php-error.log`.
- Consider rate limiting API endpoints to prevent abuse.
- Ensure access tokens are stored encrypted in the database. Refresh tokens when Instagram indicates expiration.

## 6. Future Refactoring
- Split large scripts in `backend/` into smaller classes or controllers.
- Add unit tests for authentication, campaign management, and influencer flows.
- Migrate inline HTML/PHP pages in `pages/` to templates for easier maintenance.

## 7. Influencer OAuth Flow
- During influencer login, check if an Instagram token exists. If not, redirect them to Instagram OAuth.
- Tokens are stored in `instagram_tokens` and retrieved for API calls so each influencer only sees their own metrics.
- Campaigns displayed on the influencer dashboard are filtered by badge level and category via `backend/influencer.php`.

Following this plan will make the project easier to maintain and more secure.

## Visual Refresh
- Introduced `css/instagram-theme.css` which unifies fonts and colors across all pages for a sleek Instagram-like appearance.
- Added `css/dark-theme.css` and a toggle button so users can switch between light and dark modes.
- The feed page now includes a popup for viewing and adding comments without navigating away.
- The dashboards now delegate feed and forum features to `dashboard.php` so there is a single posting experience.
- Posting supports camera capture with optional Instagram cross‑posting.
