-- ============================================================
-- AI Meeting Minutes Summarizer - Database Schema
-- Compatible with MySQL / MariaDB (XAMPP / WAMP / Standalone)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ai_meeting_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ai_meeting_db`;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) DEFAULT 'Student/Project Member',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: meetings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `meetings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `department` VARCHAR(100) DEFAULT 'General',
  `meeting_date` DATE NOT NULL,
  `duration_minutes` INT DEFAULT 30,
  `raw_transcript` LONGTEXT NOT NULL,
  `executive_summary` TEXT,
  `key_points` JSON,
  `sentiment` VARCHAR(50) DEFAULT 'Neutral',
  `word_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: action_items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `action_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `meeting_id` INT NOT NULL,
  `task` VARCHAR(500) NOT NULL,
  `assignee` VARCHAR(100) DEFAULT 'Unassigned',
  `due_date` DATE DEFAULT NULL,
  `status` ENUM('pending', 'completed') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`meeting_id`) REFERENCES `meetings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: key_decisions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `key_decisions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `meeting_id` INT NOT NULL,
  `decision` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`meeting_id`) REFERENCES `meetings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- SEED DATA (For initial demonstration & college testing)
-- ------------------------------------------------------------

-- Default Demo User (Password: password123)
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`) VALUES
(1, 'Alex Morgan', 'alex@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Team Lead')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Sample Meeting 1
INSERT INTO `meetings` (`id`, `user_id`, `title`, `department`, `meeting_date`, `duration_minutes`, `raw_transcript`, `executive_summary`, `key_points`, `sentiment`, `word_count`, `created_at`) VALUES
(1, 1, 'Project Alpha Architecture & Database Design', 'Engineering', '2026-07-20', 45, 
'Alex: Welcome team. Today we need to finalize the database schema for our AI Summarizer project and decide on the API structure.\nSarah: I reviewed the proposed MySQL schema. We have users, meetings, action_items, and key_decisions tables. It looks solid.\nJohn: What about the Gemini API integration? Are we handling fallbacks?\nAlex: Yes, Sarah will implement the Gemini API integration in PHP along with an offline NLP fallback rules engine so the app works seamlessly even without an API key.\nJohn: Perfect. I will build the frontend dashboard using CSS custom properties, glassmorphism UI, and dark mode support by Friday.\nAlex: Great. We also agreed to use Web Speech API for real-time speech-to-text recording during live meetings.\nSarah: Sounds like a plan. I will complete the backend API endpoints by Thursday.\nAlex: Meeting adjourned.',
'The engineering team aligned on the database schema and architecture for the AI Meeting Summarizer project. Key decisions were made regarding Gemini API fallback mechanisms, frontend glassmorphism design, and Web Speech API integration for live transcription.',
'["Finalized 4-table MySQL schema for users, meetings, action items, and decisions.", "Decided on dual-mode AI engine (Gemini API with offline NLP fallback).", "Frontend to be built with CSS glassmorphism and modern dark mode.", "Web Speech API selected for live voice-to-text recording."]',
'Productive', 184, '2026-07-20 10:30:00')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Sample Action Items
INSERT INTO `action_items` (`id`, `meeting_id`, `task`, `assignee`, `due_date`, `status`) VALUES
(1, 1, 'Implement Gemini API integration with PHP offline NLP fallback', 'Sarah', '2026-07-24', 'pending'),
(2, 1, 'Build responsive Frontend Dashboard with glassmorphism UI', 'John', '2026-07-25', 'pending'),
(3, 1, 'Integrate Web Speech API for live voice recording component', 'John', '2026-07-26', 'completed')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Sample Key Decisions
INSERT INTO `key_decisions` (`id`, `meeting_id`, `decision`) VALUES
(1, 1, 'Use PDO MySQL with prepared statements for database operations to ensure SQL injection protection.'),
(2, 1, 'Implement an offline NLP fallback summarizer so the app can be presented offline without API keys.')
ON DUPLICATE KEY UPDATE `id`=`id`;
