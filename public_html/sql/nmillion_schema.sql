-- =============================================================================
-- nMillion — MySQL schema (matches PHP migrations in db.php)
-- =============================================================================
--
-- HOW TO USE IN cPanel phpMyAdmin
-- -------------------------------
-- 1) cPanel → MySQL® Databases → create a database (name will look like
--    youraccount_nmillion) → create a user → Add User To Database → ALL PRIVILEGES.
-- 2) cPanel → phpMyAdmin → click your database in the left sidebar (so it is
--    selected; do NOT run this against `mysql` or `information_schema`).
-- 3) Open the SQL tab → paste this entire file → Go.
--
--    Alternatively: Import tab → choose this file → Go.
--
-- NOTE: The PHP app also runs these CREATE TABLE IF NOT EXISTS statements on
-- first page load. Running this SQL manually is optional; use it when you want
-- tables ready before traffic, or to verify permissions.
--
-- Requirements: MySQL 5.7.8+ or MariaDB 10.2+ (JSON columns).
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portfolio_positions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    ticker VARCHAR(32) NOT NULL,
    shares DECIMAL(18,6) NOT NULL,
    cost_per_share DECIMAL(18,6) NOT NULL,
    purchase_date DATE NOT NULL,
    comments TEXT NULL,
    extra_json JSON NULL,
    current_price DECIMAL(18,6) NULL,
    price_updated_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_portfolio_user (user_id),
    INDEX idx_portfolio_ticker (ticker),
    CONSTRAINT fk_portfolio_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portfolio_prefs (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    prefs_json JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_portfolio_prefs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
