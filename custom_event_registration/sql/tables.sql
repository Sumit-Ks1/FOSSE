-- ============================================
-- Custom Event Registration Module
-- Database Schema for Drupal 10
-- ============================================

-- Note: These tables are automatically created by Drupal's
-- hook_schema() in custom_event_registration.install
-- This file is for reference and manual database setup if needed.

-- ============================================
-- Table: event_config
-- Stores event configuration data
-- ============================================

CREATE TABLE IF NOT EXISTS `event_config` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key: Unique event configuration ID.',
  `registration_start` VARCHAR(10) NOT NULL COMMENT 'Event registration start date (YYYY-MM-DD).',
  `registration_end` VARCHAR(10) NOT NULL COMMENT 'Event registration end date (YYYY-MM-DD).',
  `event_date` VARCHAR(10) NOT NULL COMMENT 'Event date (YYYY-MM-DD).',
  `event_name` VARCHAR(255) NOT NULL COMMENT 'Event name.',
  `category` VARCHAR(100) NOT NULL COMMENT 'Event category.',
  PRIMARY KEY (`id`),
  INDEX `idx_category` (`category`),
  INDEX `idx_event_date` (`event_date`),
  INDEX `idx_registration_dates` (`registration_start`, `registration_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: event_registration
-- Stores event registration data
-- ============================================

CREATE TABLE IF NOT EXISTS `event_registration` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key: Unique registration ID.',
  `full_name` VARCHAR(255) NOT NULL COMMENT 'Registrant full name.',
  `email` VARCHAR(255) NOT NULL COMMENT 'Registrant email address.',
  `college_name` VARCHAR(255) NOT NULL COMMENT 'Registrant college name.',
  `department` VARCHAR(255) NOT NULL COMMENT 'Registrant department.',
  `event_id` INT(11) UNSIGNED NOT NULL COMMENT 'Foreign key to event_config.id.',
  `created` INT(11) NOT NULL DEFAULT 0 COMMENT 'Unix timestamp when the registration was created.',
  PRIMARY KEY (`id`),
  INDEX `idx_event_id` (`event_id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_email_event` (`email`, `event_id`),
  CONSTRAINT `fk_event_registration_event_config`
    FOREIGN KEY (`event_id`)
    REFERENCES `event_config` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Data (Optional - for testing)
-- ============================================

-- Uncomment the following to insert sample data:

-- INSERT INTO `event_config` (`registration_start`, `registration_end`, `event_date`, `event_name`, `category`) VALUES
-- ('2026-01-01', '2026-02-28', '2026-03-15', 'Python Programming Workshop', 'Online Workshop'),
-- ('2026-01-01', '2026-02-28', '2026-03-20', 'AI/ML Hackathon 2026', 'Hackathon'),
-- ('2026-02-01', '2026-03-15', '2026-04-10', 'Tech Conference 2026', 'Conference'),
-- ('2026-01-15', '2026-02-15', '2026-03-01', 'Web Development Bootcamp', 'One-day Workshop');
