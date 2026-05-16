-- ============================================================================
-- schema.sql
-- AcePath Hub — complete database schema
-- DB: quizmani_acepathhub
-- Run on: phpMyAdmin import or cPanel Terminal:
--   mysql -u quizmani_acepathhub_user -p quizmani_acepathhub < schema.sql
--
-- Built incrementally — each day adds its tables below.
-- Do not run this twice on a live DB (tables use CREATE TABLE, not IF NOT EXISTS).
-- ============================================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ============================================================================
-- DAY 2 — Auth
-- ============================================================================

-- ----------------------------------------------------------------------------
-- users
-- type:               TINYINT UNSIGNED  — 1=admin, 2=student, 3=data_entry
-- is_session:         TINYINT(1)        — 0=false, 1=true
-- is_blocked:         TINYINT(1)        — 0=false, 1=true
-- registered_on/at:   DATE/TIME         — native types, not strings
-- first/last_login:   DATETIME NULL     — NULL until first/last login occurs
-- session_token_time: TIMESTAMP NULL    — native, enables expiry via MySQL
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `user_id`            INT(11)          NOT NULL AUTO_INCREMENT,
  `username`           VARCHAR(30)      NOT NULL,
  `first_name`         VARCHAR(50)      NOT NULL,
  `middle_name`        VARCHAR(50)      DEFAULT NULL,
  `last_name`          VARCHAR(50)      NOT NULL,
  `email`              VARCHAR(100)     NOT NULL,
  `phone`              VARCHAR(15)      NOT NULL,
  `country`            VARCHAR(20)      DEFAULT NULL,
  `city`               VARCHAR(20)      DEFAULT NULL,
  `postal_code`        VARCHAR(10)      DEFAULT NULL,
  `gender`             VARCHAR(7)       DEFAULT NULL,
  `dob`                DATE             DEFAULT NULL,
  `password`           VARCHAR(255)     NOT NULL,
  `pin`                VARCHAR(255)     DEFAULT NULL,
  `type`               TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `registered_on`      DATE             NOT NULL,
  `registered_at`      TIME             NOT NULL,
  `first_login`        DATETIME         DEFAULT NULL,
  `last_login`         DATETIME         DEFAULT NULL,
  `status`             VARCHAR(10)      NOT NULL DEFAULT 'active',
  `referral_code`      VARCHAR(10)      NOT NULL,
  `referral_by`        VARCHAR(10)      DEFAULT NULL,
  `is_session`         TINYINT(1)       NOT NULL DEFAULT 0,
  `is_blocked`         TINYINT(1)       NOT NULL DEFAULT 0,
  `session_token`      VARCHAR(64)      DEFAULT NULL,
  `session_token_time` TIMESTAMP        DEFAULT NULL,

  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_username`      (`username`),
  UNIQUE KEY `uq_email`         (`email`),
  UNIQUE KEY `uq_phone`         (`phone`),
  UNIQUE KEY `uq_referral_code` (`referral_code`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- login_attempts
-- Throttle by username (5/15min) AND ip_address (20/15min).
-- ip_address VARCHAR(45) covers IPv4 (15) and IPv6 (39, max notation 45).
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`           INT(11)     NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(30) NOT NULL,
  `ip_address`   VARCHAR(45) NOT NULL,
  `attempt_time` INT(11)     NOT NULL,

  PRIMARY KEY (`id`),
  INDEX `idx_username_time`   (`username`,   `attempt_time`),
  INDEX `idx_ip_time`         (`ip_address`, `attempt_time`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DAY 3 — add tables here
-- ============================================================================

-- ============================================================================
-- DAY 4 — add tables here
-- ============================================================================

-- ============================================================================
-- DAY 5 — add tables here
-- ============================================================================

-- ============================================================================
-- DAY 6 — add tables here
-- ============================================================================

-- ============================================================================
-- DAY 7 — add tables here
-- ============================================================================

-- ============================================================================
-- SEEDS
-- ============================================================================

-- Admin user
-- IMPORTANT: password is a placeholder.
-- After import, run in cPanel Terminal:
--   php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT);"
-- Then:
--   UPDATE users SET password = '<output>' WHERE username = 'admin';

INSERT INTO `users` (
  `username`, `first_name`, `last_name`,
  `email`, `phone`,
  `password`,
  `type`, `status`,
  `registered_on`, `registered_at`,
  `referral_code`
) VALUES (
  'admin', 'Admin', 'User',
  'admin@acepathhub.com', '00000000000',
  'PLACEHOLDER_RUN_PASSWORD_HASH_BEFORE_USE',
  1, 'active',
  CURDATE(), CURTIME(),
  'ADMINREF1'
);

SET foreign_key_checks = 1;
