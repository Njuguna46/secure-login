-- ─────────────────────────────────────────────────────────────────────────────
-- Secure Login System — Database Schema
-- Run once: mysql -u root -p < config/database.sql
-- ─────────────────────────────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS `secure_login`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `secure_login`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `username`        VARCHAR(50)      NOT NULL,
    `email`           VARCHAR(255)     NOT NULL,
    `password_hash`   VARCHAR(255)     NOT NULL,
    `is_verified`     TINYINT(1)       NOT NULL DEFAULT 0,
    `is_active`       TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login_at`   DATETIME                  DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_username` (`username`),
    UNIQUE KEY `uq_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login attempts (brute-force protection)
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `identifier`   VARCHAR(255)  NOT NULL COMMENT 'username or email attempted',
    `ip_address`   VARCHAR(45)   NOT NULL COMMENT 'supports IPv6',
    `attempted_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_identifier_time` (`identifier`, `attempted_at`),
    KEY `idx_ip_time`         (`ip_address`,  `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Remember-me tokens
CREATE TABLE IF NOT EXISTS `remember_tokens` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED  NOT NULL,
    `token_hash` VARCHAR(255)  NOT NULL,
    `expires_at` DATETIME      NOT NULL,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_token` (`token_hash`),
    KEY `fk_remember_user` (`user_id`),
    CONSTRAINT `fk_remember_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
