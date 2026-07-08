-- Poom Connect Database Schema
-- Import this file before running seed.php

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS poomconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE poomconnect;

DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS live_event_state;
DROP TABLE IF EXISTS matches;
DROP TABLE IF EXISTS match_votes;
DROP TABLE IF EXISTS rounds;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS event_participants;
DROP TABLE IF EXISTS events;
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
    role ENUM('participant', 'organizer', 'admin', 'super_admin') NOT NULL DEFAULT 'participant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE organizations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    logo VARCHAR(255) DEFAULT NULL,
    primary_color VARCHAR(20) DEFAULT '#6C35FF',
    promptpay_number VARCHAR(30) DEFAULT NULL,
    bank_name VARCHAR(100) DEFAULT NULL,
    bank_account_name VARCHAR(150) DEFAULT NULL,
    bank_account_number VARCHAR(50) DEFAULT NULL,
    owner_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255) DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_participants INT UNSIGNED NOT NULL DEFAULT 50,
    ticket_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    round_duration INT UNSIGNED NOT NULL DEFAULT 300,
    status ENUM('draft', 'published', 'live', 'paused', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_events_date (event_date),
    INDEX idx_events_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_participants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_match (event_id, user_a, user_b),
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
