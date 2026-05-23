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
-- role:               TINYINT UNSIGNED  — 1=admin, 2=student, 3=data_entry
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
  `role`               TINYINT UNSIGNED NOT NULL DEFAULT 2,
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

CREATE TABLE IF NOT EXISTS `notification` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11)      NOT NULL,
  `notification` VARCHAR(250) NOT NULL,
  `date`         DATE         NOT NULL,
  `time`         TIME         NOT NULL,
  `is_read`      TINYINT(1)   NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
-- ============================================================================
-- DAY 3 — add tables here
-- ============================================================================

-- ============================================================================
-- DAY 4 — add tables here
-- ============================================================================
-- DAY 4 — Exam architecture
-- ============================================================================
 
-- ----------------------------------------------------------------------------
-- exam_bodies
-- e.g. WAEC, JAMB, NECO, GCE
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_bodies` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
 
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_eb_slug` (`slug`)
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- subjects
-- e.g. WAEC Biology, JAMB Mathematics
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subjects` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_body_id` INT UNSIGNED NOT NULL,
  `name`         VARCHAR(100) NOT NULL,
  `slug`         VARCHAR(100) NOT NULL,
 
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sub_slug` (`slug`),
  INDEX `idx_sub_exam_body` (`exam_body_id`),
 
  CONSTRAINT `fk_subjects_exam_body`
    FOREIGN KEY (`exam_body_id`) REFERENCES `exam_bodies` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- topics
-- e.g. Photosynthesis (under WAEC Biology)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `topics` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` INT UNSIGNED NOT NULL,
  `name`       VARCHAR(120) NOT NULL,
 
  PRIMARY KEY (`id`),
  INDEX `idx_topic_subject` (`subject_id`),
 
  CONSTRAINT `fk_topics_subject`
    FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- past_papers
-- e.g. WAEC Biology 2019
-- exam_body reachable via past_papers → subjects → exam_bodies
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `past_papers` (
  `id`               INT UNSIGNED              NOT NULL AUTO_INCREMENT,
  `subject_id`       INT UNSIGNED              NOT NULL,
  `year`             YEAR                      NOT NULL,
  `duration_minutes` SMALLINT UNSIGNED         NOT NULL DEFAULT 60,
  `total_questions`  SMALLINT UNSIGNED         NOT NULL DEFAULT 50,
  `status`           ENUM('active','inactive') NOT NULL DEFAULT 'active',
 
  PRIMARY KEY (`id`),
  INDEX `idx_pp_subject_year` (`subject_id`, `year`),
 
  CONSTRAINT `fk_past_papers_subject`
    FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- question_sets
-- Container: one image + one passage + topic context.
-- Holds 1–N rows in questions table beneath it.
-- source='past_paper' requires past_paper_id + question_no.
-- source='practice'  requires both NULL.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `question_sets` (
  `id`            INT UNSIGNED                  NOT NULL AUTO_INCREMENT,
  `topic_id`      INT UNSIGNED                  DEFAULT NULL,
  `image_path`    VARCHAR(255)                  DEFAULT NULL,
  `passage_text`  TEXT                          DEFAULT NULL,
  `source`        ENUM('practice','past_paper') NOT NULL DEFAULT 'practice',
  `past_paper_id` INT UNSIGNED                  DEFAULT NULL,
  `question_no`   SMALLINT UNSIGNED             DEFAULT NULL,
  `verified`      TINYINT(1)                    NOT NULL DEFAULT 0,
  `status`        ENUM('draft','published')     NOT NULL DEFAULT 'draft',
  `created_by`    INT UNSIGNED                  NOT NULL,
  `created_on`    DATE                          NOT NULL,
  `created_at`    TIME                          NOT NULL,
 
  PRIMARY KEY (`id`),
  INDEX `idx_qs_topic`      (`topic_id`),
  INDEX `idx_qs_past_paper` (`past_paper_id`),
  INDEX `idx_qs_created_by` (`created_by`),
 
  CONSTRAINT `fk_qs_topic`
    FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
 
  CONSTRAINT `fk_qs_past_paper`
    FOREIGN KEY (`past_paper_id`) REFERENCES `past_papers` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- questions
-- Actual question text + options + answer under a question_set.
-- answer CHECK enforces only A/B/C/D stored.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questions` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_set_id` INT UNSIGNED NOT NULL,
  `question`        TEXT         NOT NULL,
  `option_a`        VARCHAR(500) NOT NULL,
  `option_b`        VARCHAR(500) NOT NULL,
  `option_c`        VARCHAR(500) NOT NULL,
  `option_d`        VARCHAR(500) NOT NULL,
  `answer`          CHAR(1)      NOT NULL,
  `explanation`     TEXT         DEFAULT NULL,
 
  PRIMARY KEY (`id`),
  INDEX `idx_q_set` (`question_set_id`),
 
  CONSTRAINT `fk_questions_set`
    FOREIGN KEY (`question_set_id`) REFERENCES `question_sets` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
 
  CONSTRAINT `chk_q_answer`
    CHECK (`answer` IN ('A','B','C','D'))
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- products
-- Practice product → links to one subject via product_subjects.
-- Mock exam product → links to multiple subjects via product_subjects.
-- Convention enforced in application, not DB.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id`              INT UNSIGNED                         NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(120)                         NOT NULL,
  `product_type`    ENUM('mock','practice','past_paper') NOT NULL DEFAULT 'mock',
  `exam_body_id`    INT UNSIGNED                         DEFAULT NULL,
  `description`     VARCHAR(255)                         DEFAULT NULL,
  `duration_minutes` SMALLINT UNSIGNED                   NOT NULL DEFAULT 60,
  `total_questions` SMALLINT UNSIGNED                    NOT NULL DEFAULT 50,
  `total_marks`     SMALLINT UNSIGNED                    NOT NULL DEFAULT 100,
  `sets`            TINYINT UNSIGNED                     NOT NULL DEFAULT 1,
  `price`           DECIMAL(10,2)                        NOT NULL DEFAULT 0.00,
  `status`          ENUM('active','inactive')            NOT NULL DEFAULT 'active',

  PRIMARY KEY (`id`),
  INDEX `idx_products_exam_body` (`exam_body_id`),

  CONSTRAINT `fk_products_exam_body`
    FOREIGN KEY (`exam_body_id`) REFERENCES `exam_bodies` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- product_subjects
-- Junction: one product → one or many subjects.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_subjects` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
 
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_subject` (`product_id`, `subject_id`),
 
  CONSTRAINT `fk_ps_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
 
  CONSTRAINT `fk_ps_subject`
    FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- exam_groups
-- Divides an exam product into named groups (e.g. "English", "Maths").
-- question_count = how many questions to pull from this group per attempt.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_groups` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`     INT UNSIGNED NOT NULL,
  `name`           VARCHAR(120) NOT NULL,
  `question_count` SMALLINT UNSIGNED NOT NULL DEFAULT 10,
 
  PRIMARY KEY (`id`),
  INDEX `idx_eg_product` (`product_id`),
 
  CONSTRAINT `fk_eg_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- exam_group_topics
-- Topics assigned to an exam group — only these topics feed questions into it.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_group_topics` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_group_id` INT UNSIGNED NOT NULL,
  `topic_id`      INT UNSIGNED NOT NULL,
 
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_egt_group_topic` (`exam_group_id`, `topic_id`),
 
  CONSTRAINT `fk_egt_group`
    FOREIGN KEY (`exam_group_id`) REFERENCES `exam_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
 
  CONSTRAINT `fk_egt_topic`
    FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- practice_groups
-- Named navigation sections for a practice product (e.g. "Unit 1", "Unit 2").
-- sort_order controls display sequence.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `practice_groups` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED     NOT NULL,
  `name`       VARCHAR(120)     NOT NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
 
  PRIMARY KEY (`id`),
  INDEX `idx_pg_product` (`product_id`),
 
  CONSTRAINT `fk_pg_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- practice_group_topics
-- Topics assigned to a practice group.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `practice_group_topics` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `practice_group_id` INT UNSIGNED NOT NULL,
  `topic_id`          INT UNSIGNED NOT NULL,
 
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pgt_group_topic` (`practice_group_id`, `topic_id`),
 
  CONSTRAINT `fk_pgt_group`
    FOREIGN KEY (`practice_group_id`) REFERENCES `practice_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
 
  CONSTRAINT `fk_pgt_topic`
    FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- exam_attempts
-- Exactly one of product_id or past_paper_id must be set — enforced by CHECK.
-- Bulk question generation happens in exam.php on first load (not guidelines).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_attempts` (
  `id`            INT UNSIGNED                               NOT NULL AUTO_INCREMENT,
  `user_id`       INT(11)                                    NOT NULL,
  `product_id`    INT UNSIGNED                               DEFAULT NULL,
  `past_paper_id` INT UNSIGNED                               DEFAULT NULL,
  `started_at`    DATETIME                                   NOT NULL,
  `submitted_at`  DATETIME                                   DEFAULT NULL,
  `score`         DECIMAL(8,2)                               DEFAULT NULL,
  `status`        ENUM('in_progress','submitted','abandoned') NOT NULL DEFAULT 'in_progress',
 
  PRIMARY KEY (`id`),
  INDEX `idx_ea_user`       (`user_id`),
  INDEX `idx_ea_product`    (`product_id`),
  INDEX `idx_ea_past_paper` (`past_paper_id`),
 
  CONSTRAINT `fk_ea_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
 
  CONSTRAINT `fk_ea_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
 
  CONSTRAINT `fk_ea_past_paper`
    FOREIGN KEY (`past_paper_id`) REFERENCES `past_papers` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- exam_attempt_sets
-- One row per question_set assigned to an attempt.
-- selected_option NULL = unanswered. is_correct NULL = not yet evaluated.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_attempt_sets` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id`      INT UNSIGNED NOT NULL,
  `question_set_id` INT UNSIGNED NOT NULL,
  `selected_option` CHAR(1)      DEFAULT NULL,
  `is_correct`      TINYINT(1)   DEFAULT NULL,
 
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_eas_attempt_set` (`attempt_id`, `question_set_id`),
  INDEX `idx_eas_attempt` (`attempt_id`),
 
  CONSTRAINT `fk_eas_attempt`
    FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
 
  CONSTRAINT `fk_eas_set`
    FOREIGN KEY (`question_set_id`) REFERENCES `question_sets` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
 
  CONSTRAINT `chk_eas_option`
    CHECK (`selected_option` IN ('A','B','C','D') OR `selected_option` IS NULL)
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- ----------------------------------------------------------------------------
-- practice_answers
-- Tracks student answers during free practice (no product, no attempt).
-- One row per question_set per user — upsert on re-answer.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `practice_answers` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`         INT(11)      NOT NULL,
  `question_set_id` INT UNSIGNED NOT NULL,
  `selected_option` CHAR(1)      NOT NULL,
  `answered_at`     DATETIME     NOT NULL,
 
  PRIMARY KEY (`id`),
  INDEX `idx_pa_user_set` (`user_id`, `question_set_id`),
 
  CONSTRAINT `fk_pa_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
 
  CONSTRAINT `fk_pa_set`
    FOREIGN KEY (`question_set_id`) REFERENCES `question_sets` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
 
  CONSTRAINT `chk_pa_option`
    CHECK (`selected_option` IN ('A','B','C','D'))
 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ============================================================================
-- DAY 5 — add tables here
-- ============================================================================
CREATE TABLE IF NOT EXISTS `purchased_products` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `user_id`        INT(11)          NOT NULL,
  `product_id`     INT UNSIGNED     NOT NULL,
  `amount`         DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
  `sets_remaining` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at`     DATE             DEFAULT NULL,
  `txn_no`         VARCHAR(64)      DEFAULT NULL,
  `txn_mode`       ENUM('esewa','khalti','bank','free') NOT NULL DEFAULT 'free',
  `mobile`         VARCHAR(15)      DEFAULT NULL,
  `status`         ENUM('active','cancelled') NOT NULL DEFAULT 'active',
  `purchased_on`   DATE             NOT NULL,
  `created_by`     INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_pp_user`    (`user_id`),
  INDEX `idx_pp_product` (`product_id`),
  CONSTRAINT `fk_pp_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pp_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

INSERT IGNORE INTO `users` (
  `username`, `first_name`, `last_name`,
  `email`, `phone`,
  `password`,
  `role`, `status`,
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
