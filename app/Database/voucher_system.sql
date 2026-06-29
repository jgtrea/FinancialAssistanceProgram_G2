-- =============================================================================
-- Financial Assistance Program — Voucher Management System
-- Database Template
-- =============================================================================
--
-- This file creates the base tables that php spark migrate does NOT create.
-- Run this FIRST, then run: php spark migrate
--
-- Usage:
--   1. Create the database:
--        C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS voucher_system CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
--   2. Import this template:
--        C:\xampp\mysql\bin\mysql.exe -u root voucher_system < app\Database\voucher_system.sql
--   3. Apply migrations:
--        php spark migrate
--
-- Default admin credentials:
--   Email:    admin@example.com
--   Password: Admin@1234
--
-- IMPORTANT: Change the admin password after first login.
-- To generate a new ARGON2ID hash for a custom password, run:
--   C:\xampp\php\php.exe -r "echo password_hash('YourPassword', PASSWORD_ARGON2ID);"
-- Then UPDATE users SET password = '<hash>' WHERE email = 'admin@example.com';
--
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Table: users
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id`             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `username`            VARCHAR(100)        NOT NULL,
  `first_name`          VARCHAR(100)        NOT NULL,
  `middle_name`         VARCHAR(100)        NULL DEFAULT NULL,
  `last_name`           VARCHAR(100)        NOT NULL,
  `email`               VARCHAR(255)        NOT NULL,
  `password`            VARCHAR(255)        NOT NULL,
  `role`                ENUM('admin','user') NOT NULL DEFAULT 'user',
  `is_active`           TINYINT(1)          NOT NULL DEFAULT 1,
  `last_login`          DATETIME            NULL DEFAULT NULL,
  `session_token`       VARCHAR(128)        NULL DEFAULT NULL,
  `session_last_active` DATETIME            NULL DEFAULT NULL,
  `created_at`          DATETIME            NULL DEFAULT NULL,
  `updated_at`          DATETIME            NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Table: school
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `school` (
  `school_id`    INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_name`  VARCHAR(255)     NOT NULL,
  `school_level` ENUM('JHS','SHS') NOT NULL,
  `acronym`      VARCHAR(50)      NULL DEFAULT NULL,
  `is_active`    TINYINT(1)       NOT NULL DEFAULT 1,
  PRIMARY KEY (`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Table: signatories
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `signatories` (
  `signatory_id`    INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `prefix`          VARCHAR(20)      NULL DEFAULT NULL,
  `first_name`      VARCHAR(100)     NOT NULL,
  `middle_name`     VARCHAR(100)     NULL DEFAULT NULL,
  `last_name`       VARCHAR(100)     NOT NULL,
  `suffix`          VARCHAR(20)      NULL DEFAULT NULL,
  `degree`          VARCHAR(100)     NULL DEFAULT NULL,
  `position_title`  VARCHAR(150)     NOT NULL,
  `signature_image` VARCHAR(255)     NULL DEFAULT NULL,
  `is_active`       TINYINT(1)       NOT NULL DEFAULT 1,
  `is_selected`     TINYINT(1)       NOT NULL DEFAULT 0,
  PRIMARY KEY (`signatory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Table: students
-- All migration-added columns are already included here.
-- Migrations will safely skip adding them (each migration checks fieldExists).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `student_id`                   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `voucher_no`                   VARCHAR(50)      NULL DEFAULT NULL,
  `control_no`                   VARCHAR(50)      NULL DEFAULT NULL,
  `voucher_date`                 DATE             NULL DEFAULT NULL,
  `first_name`                   VARCHAR(100)     NOT NULL,
  `middle_name`                  VARCHAR(100)     NULL DEFAULT NULL,
  `last_name`                    VARCHAR(100)     NOT NULL,
  `suffix`                       VARCHAR(20)      NULL DEFAULT NULL,
  `rank_no`                      DECIMAL(7,1)     NULL DEFAULT NULL,
  `gwa`                          DECIMAL(5,2)     NULL DEFAULT NULL,
  `gender`                       VARCHAR(10)      NULL DEFAULT NULL,
  `junior_high_school`           INT(11) UNSIGNED NULL DEFAULT NULL,
  `preferred_senior_high_school` INT(11) UNSIGNED NULL DEFAULT NULL,
  `contact_number`               VARCHAR(30)      NULL DEFAULT NULL,
  `remarks_status`               VARCHAR(100)     NULL DEFAULT NULL,
  `other_remarks`                VARCHAR(255)     NULL DEFAULT NULL,
  `school_year`                  VARCHAR(20)      NULL DEFAULT NULL,
  `eligibility_status`           VARCHAR(50)      NULL DEFAULT NULL,
  `voucher_status`               VARCHAR(50)      NULL DEFAULT 'not_generated',
  `is_active`                    TINYINT(1)       NOT NULL DEFAULT 1,
  `evaluated_by`                 VARCHAR(150)     NULL DEFAULT NULL,
  `generate_count`               INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `generated_at`                 DATETIME         NULL DEFAULT NULL,
  `created_at`                   DATETIME         NULL DEFAULT NULL,
  `updated_at`                   DATETIME         NULL DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `uniq_students_control_no` (`control_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Table: student_archive
-- All migration-added columns (control_no, evaluated_by, other_remarks,
-- indexes) are already included. Migrations check and skip safely.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_archive` (
  `archive_id`                   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id`                   INT(11) UNSIGNED NULL DEFAULT NULL,
  `voucher_no`                   VARCHAR(50)      NULL DEFAULT NULL,
  `control_no`                   VARCHAR(50)      NULL DEFAULT NULL,
  `voucher_date`                 DATE             NULL DEFAULT NULL,
  `first_name`                   VARCHAR(100)     NOT NULL,
  `middle_name`                  VARCHAR(100)     NULL DEFAULT NULL,
  `last_name`                    VARCHAR(100)     NOT NULL,
  `suffix`                       VARCHAR(20)      NULL DEFAULT NULL,
  `rank_no`                      DECIMAL(7,1)     NULL DEFAULT NULL,
  `gwa`                          DECIMAL(5,2)     NULL DEFAULT NULL,
  `gender`                       VARCHAR(10)      NULL DEFAULT NULL,
  `junior_high_school`           INT(11) UNSIGNED NULL DEFAULT NULL,
  `preferred_senior_high_school` INT(11) UNSIGNED NULL DEFAULT NULL,
  `contact_number`               VARCHAR(30)      NULL DEFAULT NULL,
  `remarks_status`               VARCHAR(100)     NULL DEFAULT NULL,
  `other_remarks`                VARCHAR(255)     NULL DEFAULT NULL,
  `school_year`                  VARCHAR(20)      NULL DEFAULT NULL,
  `voucher_status`               VARCHAR(50)      NULL DEFAULT NULL,
  `evaluated_by`                 VARCHAR(150)     NULL DEFAULT NULL,
  `archive_reason`               VARCHAR(255)     NULL DEFAULT NULL,
  `archived_by`                  INT(11) UNSIGNED NULL DEFAULT NULL,
  `archived_at`                  DATETIME         NULL DEFAULT NULL,
  PRIMARY KEY (`archive_id`),
  KEY `idx_sa_sy_archived` (`school_year`, `archived_at`),
  KEY `idx_sa_jhs`         (`junior_high_school`),
  KEY `idx_sa_shs`         (`preferred_senior_high_school`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- Seed: default admin user
-- Password: Admin@1234  (ARGON2ID hash — change after first login)
-- To regenerate: C:\xampp\php\php.exe -r "echo password_hash('NewPassword', PASSWORD_ARGON2ID);"
-- =============================================================================
INSERT IGNORE INTO `users`
  (`username`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`)
VALUES
  ('admin', 'Admin', NULL, 'User', 'admin@example.com',
   '$argon2id$v=19$m=65536,t=4,p=1$PLACEHOLDER$PLACEHOLDER',
   'admin', 1, NOW(), NOW());

-- =============================================================================
-- NOTE: After importing, generate a real password hash and update:
--
--   1. Run: C:\xampp\php\php.exe -r "echo password_hash('Admin@1234', PASSWORD_ARGON2ID);"
--   2. Copy the output hash, then run in MySQL:
--        UPDATE users SET password = '<paste-hash-here>' WHERE email = 'admin@example.com';
--
-- Or: visit http://localhost/generate-hash after starting the app
--     (temporarily echoes a hash for the string 'test')
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 1;
