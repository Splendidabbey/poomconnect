-- Poom Connect Database Schema
-- Import this file before running seed.php

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS poomconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE poomconnect;

DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS social_share_logs;
DROP TABLE IF EXISTS email_campaign_recipients;
DROP TABLE IF EXISTS email_campaigns;
DROP TABLE IF EXISTS ai_usage_logs;
DROP TABLE IF EXISTS support_tickets;
DROP TABLE IF EXISTS platform_countries;
DROP TABLE IF EXISTS organizer_ratings;
DROP TABLE IF EXISTS host_applications;
DROP TABLE IF EXISTS event_templates;
DROP TABLE IF EXISTS recurring_event_series;
DROP TABLE IF EXISTS organizer_followers;
DROP TABLE IF EXISTS community_members;
DROP TABLE IF EXISTS communities;
DROP TABLE IF EXISTS user_blocks;
DROP TABLE IF EXISTS user_reports;
DROP TABLE IF EXISTS organization_subscriptions;
DROP TABLE IF EXISTS subscription_plans;
DROP TABLE IF EXISTS organization_members;
DROP TABLE IF EXISTS event_reminder_logs;
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS chat_rooms;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS referral_uses;
DROP TABLE IF EXISTS event_broadcasts;
DROP TABLE IF EXISTS event_messages;
DROP TABLE IF EXISTS event_invitations;
DROP TABLE IF EXISTS user_compatibility_profiles;
DROP TABLE IF EXISTS live_event_state;
DROP TABLE IF EXISTS matches;
DROP TABLE IF EXISTS match_votes;
DROP TABLE IF EXISTS rounds;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS event_participants;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS referral_codes;
DROP TABLE IF EXISTS event_images;
DROP TABLE IF EXISTS blog_posts;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS organizations;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    line_id VARCHAR(80) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    cover_image VARCHAR(255) NULL,
    bio TEXT NULL,
    gender ENUM('male','female','non_binary','prefer_not_to_say','other') NULL,
    date_of_birth DATE NULL,
    interests JSON NULL,
    personality VARCHAR(80) NULL,
    languages JSON NULL,
    city VARCHAR(100) NULL,
    occupation VARCHAR(120) NULL,
    instagram VARCHAR(120) NULL,
    facebook VARCHAR(120) NULL,
    privacy_settings JSON NULL,
    verified_at TIMESTAMP NULL,
    emergency_contact_name VARCHAR(150) NULL,
    emergency_contact_phone VARCHAR(30) NULL,
    country CHAR(2) NULL,
    preferred_currency CHAR(3) NULL,
    preferred_locale VARCHAR(5) NULL,
    loyalty_points INT UNSIGNED NOT NULL DEFAULT 0,
    loyalty_level VARCHAR(30) NOT NULL DEFAULT 'bronze',
    referral_credits INT UNSIGNED NOT NULL DEFAULT 0,
    is_vip TINYINT(1) NOT NULL DEFAULT 0,
    role ENUM('participant', 'organizer', 'moderator', 'admin', 'super_admin') NOT NULL DEFAULT 'participant',
    account_status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    deactivated_at TIMESTAMP NULL,
    deactivated_by INT UNSIGNED NULL,
    admin_notes TEXT NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE organizations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    custom_domain VARCHAR(255) NULL,
    subdomain VARCHAR(100) NULL,
    logo VARCHAR(255) DEFAULT NULL,
    primary_color VARCHAR(20) DEFAULT '#6C35FF',
    secondary_color VARCHAR(20) NULL DEFAULT '#FF2D8D',
    subscription_plan_id INT UNSIGNED NULL,
    status ENUM('active','suspended','pending') NOT NULL DEFAULT 'active',
    country CHAR(2) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    safe_event_badge TINYINT(1) NOT NULL DEFAULT 0,
    profile_bio TEXT NULL,
    profile_public TINYINT(1) NOT NULL DEFAULT 1,
    rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    rating_count INT UNSIGNED NOT NULL DEFAULT 0,
    promptpay_number VARCHAR(30) DEFAULT NULL,
    bank_name VARCHAR(100) DEFAULT NULL,
    bank_account_name VARCHAR(150) DEFAULT NULL,
    bank_account_number VARCHAR(50) DEFAULT NULL,
    landing_headline VARCHAR(255) NULL,
    landing_body TEXT NULL,
    landing_cta VARCHAR(120) NULL,
    tiktok_handle VARCHAR(80) NULL,
    seo_keywords VARCHAR(255) NULL,
    owner_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_org_subdomain (subdomain),
    UNIQUE KEY idx_org_custom_domain (custom_domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    type ENUM('event', 'blog', 'both') NOT NULL DEFAULT 'both',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NULL UNIQUE,
    category_id INT UNSIGNED NULL,
    description TEXT,
    cover_image VARCHAR(255) DEFAULT NULL,
    banner_image VARCHAR(255) NULL,
    location VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    event_type ENUM(
        'dating','networking','friendship','startup','business','recruitment',
        'university','corporate','lgbtq','speed_networking','professional_mixer',
        'private_event','mixer','speed_dating','workshop','social','other'
    ) NOT NULL DEFAULT 'social',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    map_url VARCHAR(500) NULL,
    dress_code VARCHAR(200) NULL,
    rules TEXT NULL,
    waitlist_enabled TINYINT(1) NOT NULL DEFAULT 1,
    invite_enabled TINYINT(1) NOT NULL DEFAULT 1,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_participants INT UNSIGNED NOT NULL DEFAULT 50,
    ticket_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT 'THB',
    round_duration INT UNSIGNED NOT NULL DEFAULT 300,
    round_count INT UNSIGNED NOT NULL DEFAULT 5,
    series_id INT UNSIGNED NULL,
    is_recurring_instance TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('draft', 'published', 'live', 'paused', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    meta_title VARCHAR(200) NULL,
    meta_description VARCHAR(320) NULL,
    og_image VARCHAR(255) NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_events_date (event_date),
    INDEX idx_events_status (status),
    INDEX idx_events_city (city),
    INDEX idx_events_category (category_id),
    INDEX idx_events_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_images_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NULL,
    author_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NULL,
    title VARCHAR(220) NOT NULL,
    slug VARCHAR(240) NOT NULL UNIQUE,
    excerpt TEXT NULL,
    content LONGTEXT NOT NULL,
    cover_image VARCHAR(255) NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    meta_title VARCHAR(200) NULL,
    meta_description VARCHAR(320) NULL,
    published_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_blog_status (status),
    INDEX idx_blog_published (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NULL,
    code VARCHAR(40) NOT NULL,
    discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(10,2) NOT NULL,
    max_uses INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at TIMESTAMP NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_coupon_code (organization_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE referral_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    reward_description VARCHAR(255) NULL,
    uses_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_participants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    registration_status ENUM('registered','waitlist','cancelled') NOT NULL DEFAULT 'registered',
    coupon_id INT UNSIGNED NULL,
    invited_by INT UNSIGNED NULL,
    invite_token VARCHAR(64) NULL,
    payment_status ENUM('pending', 'approved', 'rejected', 'none') NOT NULL DEFAULT 'pending',
    ticket_status ENUM('none', 'issued', 'used') NOT NULL DEFAULT 'none',
    checked_in TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_event_user (event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    coupon_id INT UNSIGNED NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    original_amount DECIMAL(10,2) NULL,
    payment_method VARCHAR(50) DEFAULT 'promptpay',
    payment_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    slip_image VARCHAR(255) DEFAULT NULL,
    approved_by INT UNSIGNED DEFAULT NULL,
    approved_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    qr_token VARCHAR(64) NOT NULL UNIQUE,
    checked_in TINYINT(1) NOT NULL DEFAULT 0,
    checked_in_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rounds (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    round_number INT UNSIGNED NOT NULL,
    table_number INT UNSIGNED NOT NULL,
    participant_a INT UNSIGNED NOT NULL,
    participant_b INT UNSIGNED NOT NULL,
    started_at TIMESTAMP NULL DEFAULT NULL,
    ended_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_a) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_b) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE match_votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    round_id INT UNSIGNED NOT NULL,
    voter_id INT UNSIGNED NOT NULL,
    target_id INT UNSIGNED NOT NULL,
    vote ENUM('like', 'friend', 'business', 'pass') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (round_id, voter_id, target_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (round_id) REFERENCES rounds(id) ON DELETE CASCADE,
    FOREIGN KEY (voter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE matches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_a INT UNSIGNED NOT NULL,
    user_b INT UNSIGNED NOT NULL,
    match_type ENUM('like','friend','business') NOT NULL DEFAULT 'like',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_event_match (event_id, user_a, user_b, match_type),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_a) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_b) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE live_event_state (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL UNIQUE,
    current_round INT UNSIGNED NOT NULL DEFAULT 0,
    event_status ENUM('waiting', 'live', 'paused', 'ended') NOT NULL DEFAULT 'waiting',
    timer_seconds INT UNSIGNED NOT NULL DEFAULT 300,
    broadcast_message TEXT NULL,
    timer_started_at TIMESTAMP NULL,
    emergency_stopped TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_compatibility_profiles (
    user_id INT UNSIGNED PRIMARY KEY,
    interests JSON NULL,
    personality_type VARCHAR(80) NULL,
    communication_style VARCHAR(80) NULL,
    relationship_goal VARCHAR(80) NULL,
    networking_goal VARCHAR(80) NULL,
    icebreaker_preferences JSON NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_invitations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    inviter_id INT UNSIGNED NOT NULL,
    invitee_email VARCHAR(180) NOT NULL,
    invite_token VARCHAR(64) NOT NULL UNIQUE,
    status ENUM('pending','accepted','expired') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_broadcasts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_broadcast_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_a INT UNSIGNED NOT NULL,
    user_b INT UNSIGNED NOT NULL,
    unlocked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chat (event_id, user_a, user_b),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_a) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_b) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    body TEXT NULL,
    image_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_chat_room (room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    body TEXT NULL,
    channel ENUM('in_app','email','line','push','sms') NOT NULL DEFAULT 'in_app',
    meta JSON NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_unread (user_id, read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_badges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    badge_key VARCHAR(50) NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_badge (user_id, badge_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE referral_uses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referral_code_id INT UNSIGNED NOT NULL,
    referred_user_id INT UNSIGNED NOT NULL,
    credits_awarded INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_reminder_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    reminded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_event_reminder (event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reminder_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE organization_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    member_role ENUM('owner','admin','staff','moderator') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_org_member (organization_id, user_id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subscription_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(80) NOT NULL,
    price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    features JSON NOT NULL,
    max_events INT UNSIGNED NULL,
    max_participants INT UNSIGNED NULL,
    white_label TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE organization_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    status ENUM('active','trialing','past_due','cancelled') NOT NULL DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT,
    INDEX idx_org_sub (organization_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    event_type VARCHAR(40) NOT NULL,
    description TEXT NULL,
    defaults JSON NOT NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE host_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    organization_name VARCHAR(150) NOT NULL,
    event_types JSON NULL,
    experience TEXT NULL,
    website VARCHAR(255) NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    review_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_host_app_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE organizer_ratings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_org_rating (organization_id, user_id, event_id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE communities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    description TEXT NULL,
    cover_image VARCHAR(255) NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    member_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_community_slug (organization_id, slug),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE community_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    community_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    member_role ENUM('member','moderator') NOT NULL DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_community_member (community_id, user_id),
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE organizer_followers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    follower_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (organization_id, follower_id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recurring_event_series (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    template_event_id INT UNSIGNED NULL,
    frequency ENUM('weekly','biweekly','monthly') NOT NULL DEFAULT 'weekly',
    next_date DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (template_event_id) REFERENCES events(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NOT NULL,
    reported_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NULL,
    reason ENUM('harassment','spam','inappropriate','fake_profile','other') NOT NULL DEFAULT 'other',
    details TEXT NULL,
    status ENUM('pending','reviewed','dismissed','actioned') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    INDEX idx_reports_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_blocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    blocker_id INT UNSIGNED NOT NULL,
    blocked_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_block (blocker_id, blocked_id),
    FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE support_tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    organization_id INT UNSIGNED NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
    status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    assigned_to INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    INDEX idx_ticket_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platform_countries (
    code CHAR(2) PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_usage_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    action VARCHAR(60) NOT NULL,
    event_id INT UNSIGNED NULL,
    meta JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ai_usage_org (organization_id),
    INDEX idx_ai_usage_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT NULL,
    audience ENUM('all_participants','event_attendees','followers','custom') NOT NULL DEFAULT 'all_participants',
    event_id INT UNSIGNED NULL,
    status ENUM('draft','scheduled','sent','cancelled') NOT NULL DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    sent_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_campaign_recipients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    email VARCHAR(180) NOT NULL,
    status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    FOREIGN KEY (campaign_id) REFERENCES email_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_campaign_recipient (campaign_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE social_share_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    entity_type ENUM('event','blog','org','referral') NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    channel VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_share_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
