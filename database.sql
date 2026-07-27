-- ============================================================
--  User Management System — Database Schema
--  Production-ready with indexes, constraints & soft deletes
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
--  Table: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `username`        VARCHAR(50)     NOT NULL,
    `email`           VARCHAR(255)    NOT NULL,
    `password`        VARCHAR(255)    NOT NULL,
    `role`            ENUM('user','admin','super_admin')
                        NOT NULL DEFAULT 'user',
    `status`          ENUM('active','inactive')
                        NOT NULL DEFAULT 'active',
    `avatar`          VARCHAR(255)    NULL DEFAULT NULL,
    `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`      TIMESTAMP       NULL DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_email`    (`email`),
    KEY `idx_role`          (`role`),
    KEY `idx_status`        (`status`),
    KEY `idx_created_at`    (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Table: sessions  (for session tracking / revocation)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
    `id`            VARCHAR(128)   NOT NULL,
    `user_id`       INT UNSIGNED   NOT NULL,
    `ip_address`    VARCHAR(45)    NOT NULL,
    `user_agent`    TEXT           NOT NULL,
    `expires_at`    TIMESTAMP      NOT NULL,
    `created_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_user_id`    (`user_id`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Table: activity_log
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NULL,
    `action`        VARCHAR(100)    NOT NULL,
    `description`   TEXT            NOT NULL,
    `ip_address`    VARCHAR(45)     NOT NULL,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_user_id`    (`user_id`),
    KEY `idx_action`     (`action`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Table: rate_limits  (for rate limiting)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `key`           VARCHAR(255)    NOT NULL,
    `count`         INT UNSIGNED    NOT NULL DEFAULT 1,
    `expires_at`    TIMESTAMP       NOT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_key` (`key`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
