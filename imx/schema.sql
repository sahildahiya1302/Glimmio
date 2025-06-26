-- Users table removed as per new requirement

-- Brands table updated to include authentication and user info
CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    badge_level ENUM('bronze','silver','gold') DEFAULT 'bronze',
    meta_user_id VARCHAR(255) DEFAULT NULL,
    profile_complete BOOLEAN DEFAULT FALSE,
    name VARCHAR(255),
    profile_pic VARCHAR(255),
    company_name VARCHAR(255),
    website VARCHAR(255),
    logo_url VARCHAR(255),
    gstin VARCHAR(50),
    industry VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Influencers table updated to include authentication and user info
CREATE TABLE IF NOT EXISTS influencers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    badge_level ENUM('bronze','silver','gold') DEFAULT 'bronze',
    meta_user_id VARCHAR(255) DEFAULT NULL,
    profile_complete BOOLEAN DEFAULT FALSE,
    username VARCHAR(255),
    profile_pic VARCHAR(255),
    followers_count INT DEFAULT 0,
    media_count INT DEFAULT 0,
    instagram_handle VARCHAR(255),
    category VARCHAR(100),
    bio TEXT,
    upi_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Campaigns table
CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    objective VARCHAR(100),
    description TEXT,
    category VARCHAR(100),
    min_followers INT DEFAULT 0,
    badge_min ENUM('bronze','silver','gold','elite') DEFAULT 'bronze',
    max_influencers INT DEFAULT NULL,
    start_date DATE,
    end_date DATE,
    goal_type ENUM('CPM', 'CPE') NOT NULL,
    rate DECIMAL(10,2) NOT NULL,
    target_metrics INT DEFAULT NULL,
    budget_total DECIMAL(10,2) NOT NULL,
    commission_percent DECIMAL(5,2) DEFAULT 0,
    influencer_payout_total DECIMAL(10,2) DEFAULT 0,
    image_url VARCHAR(255),
    status ENUM('draft', 'active', 'completed', 'ended') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
);

-- Campaign briefs table holds creative references for campaigns
CREATE TABLE IF NOT EXISTS campaign_briefs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    file_url VARCHAR(255),
    guidelines TEXT,
    hashtags_required VARCHAR(255),
    caption_examples TEXT,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);

-- Requests table
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    influencer_uid INT NOT NULL,
    campaign_id INT NOT NULL,
    message TEXT,
    status ENUM('pending', 'accepted', 'rejected', 'live', 'completed') DEFAULT 'pending',
    reel_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    decision_at TIMESTAMP NULL,
    FOREIGN KEY (influencer_uid) REFERENCES influencers(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);

-- Notifications table (optional)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES influencers(id) ON DELETE CASCADE
);

-- Content submissions table for workflow
CREATE TABLE IF NOT EXISTS content_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    influencer_id INT NOT NULL,
    media_url VARCHAR(255) NOT NULL,
    caption TEXT,
    status ENUM('pending','approved','rejected','needs_revision','live','completed') DEFAULT 'pending',
    brand_feedback TEXT,
    posted_url VARCHAR(255),
    post_id VARCHAR(255),
    posted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (influencer_id) REFERENCES influencers(id) ON DELETE CASCADE
);

-- Instagram tokens table stores OAuth tokens for API access
CREATE TABLE IF NOT EXISTS instagram_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ig_user_id VARCHAR(255) NOT NULL,
    access_token TEXT NOT NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user (user_id),
    FOREIGN KEY (user_id) REFERENCES influencers(id) ON DELETE CASCADE
);

-- Metrics table for real-time Instagram stats
CREATE TABLE IF NOT EXISTS metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    post_id VARCHAR(255) NOT NULL,
    reach INT DEFAULT 0,
    impressions INT DEFAULT 0,
    likes INT DEFAULT 0,
    comments INT DEFAULT 0,
    shares INT DEFAULT 0,
    saves INT DEFAULT 0,
    engagement_total INT DEFAULT 0,
    fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_submission (submission_id),
    FOREIGN KEY (submission_id) REFERENCES content_submissions(id) ON DELETE CASCADE
);

-- Wallets table to store balances for brands and influencers
CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    balance DECIMAL(10,2) DEFAULT 0,
    on_hold DECIMAL(10,2) DEFAULT 0,
    wallet_type ENUM('brand','influencer') NOT NULL,
    UNIQUE KEY uniq_wallet_user (user_id, wallet_type),
    FOREIGN KEY (user_id) REFERENCES brands(id) ON DELETE CASCADE
);

-- Transactions ledger for wallets
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_id INT NOT NULL,
    campaign_id INT DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('campaign_charge','payout','commission','refund','credit') NOT NULL,
    description TEXT,
    platform_share DECIMAL(10,2) DEFAULT 0,
    influencer_payout DECIMAL(10,2) DEFAULT 0,
    brand_payment DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL
);

-- Commission settings table
CREATE TABLE IF NOT EXISTS commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('global','brand','campaign','badge') NOT NULL,
    reference_id INT DEFAULT NULL,
    commission_percent DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Raw user events for attribution
CREATE TABLE IF NOT EXISTS user_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    campaign_id INT NOT NULL,
    influencer_id INT NOT NULL,
    submission_id INT NOT NULL,
    event_type ENUM('view','click','add_to_cart','checkout','purchase') NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    source_type VARCHAR(50),
    ip_address VARCHAR(45),
    device_info TEXT,
    revenue_value DECIMAL(10,2) DEFAULT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_campaign (campaign_id),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (submission_id) REFERENCES content_submissions(id) ON DELETE CASCADE
);

-- Attribution summary derived from user_events
CREATE TABLE IF NOT EXISTS attribution_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    influencer_id INT NOT NULL,
    submission_id INT NOT NULL,
    event_type ENUM('purchase','add_to_cart','checkout') NOT NULL,
    attributed_via VARCHAR(20) NOT NULL,
    value_count INT DEFAULT 0,
    value_sum DECIMAL(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_attr (campaign_id, influencer_id, submission_id, event_type, attributed_via),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (submission_id) REFERENCES content_submissions(id) ON DELETE CASCADE
);

-- Default CPM payout rates per badge level
CREATE TABLE IF NOT EXISTS badge_rates (
    badge_level ENUM('bronze','silver','gold','elite') PRIMARY KEY,
    cpm_rate DECIMAL(6,2) NOT NULL
);

INSERT INTO badge_rates (badge_level, cpm_rate) VALUES
    ('bronze', 0.50),
    ('silver', 0.60),
    ('gold',   0.65),
    ('elite',  0.75)
ON DUPLICATE KEY UPDATE cpm_rate=VALUES(cpm_rate);

-- Pixel tracking events
CREATE TABLE IF NOT EXISTS pixel_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    utm_source VARCHAR(100) DEFAULT NULL,
    utm_medium VARCHAR(100) DEFAULT NULL,
    utm_campaign VARCHAR(100) DEFAULT NULL,
    utm_content VARCHAR(100) DEFAULT NULL,
    utm_term VARCHAR(100) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT,
    page_url VARCHAR(255) DEFAULT NULL,
    referrer VARCHAR(255) DEFAULT NULL,
    session_id VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Community posts for forums and influencer discussions
CREATE TABLE IF NOT EXISTS community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT NOT NULL,
    role ENUM('brand','influencer') NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    poll_question TEXT DEFAULT NULL,
    poll_options TEXT DEFAULT NULL,
    share_count INT DEFAULT 0,
    save_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_author (author_id, role)
);
-- Likes for community posts
ALTER TABLE community_posts ADD COLUMN like_count INT DEFAULT 0;
CREATE TABLE IF NOT EXISTS community_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('brand','influencer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_like (post_id, user_id, role),
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE
);

-- Comments on community posts
CREATE TABLE IF NOT EXISTS community_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('brand','influencer') NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE
);

-- Saved posts
CREATE TABLE IF NOT EXISTS community_saves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('brand','influencer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_save (post_id, user_id, role),
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE
);

-- Shares count table
CREATE TABLE IF NOT EXISTS community_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('brand','influencer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE
);

-- Poll votes
CREATE TABLE IF NOT EXISTS community_poll_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    option_index INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('brand','influencer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_vote (post_id, user_id, role),
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE
);

-- Direct messages between users
CREATE TABLE IF NOT EXISTS direct_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_role ENUM('brand','influencer') NOT NULL,
    receiver_id INT NOT NULL,
    receiver_role ENUM('brand','influencer') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
