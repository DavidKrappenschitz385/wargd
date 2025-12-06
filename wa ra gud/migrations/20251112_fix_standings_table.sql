-- Fix for Error 150: Foreign key constraint is incorrectly formed
-- This script separates table creation from constraint application to ensure reliability

-- 1. Create the standings table without foreign keys first
CREATE TABLE IF NOT EXISTS `standings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `league_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `wins` int(11) DEFAULT 0,
  `losses` int(11) DEFAULT 0,
  `draws` int(11) DEFAULT 0,
  `matches_played` int(11) DEFAULT 0,
  `points` int(11) DEFAULT 0,
  `score_for` int(11) DEFAULT 0,
  `score_against` int(11) DEFAULT 0,
  `score_difference` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_league_team` (`league_id`,`team_id`),
  KEY `idx_team_id` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Add the foreign keys separately
-- We use IGNORE or try/catch in applications, but in pure SQL script we can just run ALTER.
-- If the constraints already exist, this might fail, so we can wrap it or just assume this is for a broken state.

SET @exist := (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_name = 'standings' AND constraint_name = 'fk_standings_league' AND table_schema = DATABASE());
SET @sql := IF (@exist > 0, 'SELECT "Constraint fk_standings_league already exists"', 'ALTER TABLE `standings` ADD CONSTRAINT `fk_standings_league` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`) ON DELETE CASCADE');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_name = 'standings' AND constraint_name = 'fk_standings_team' AND table_schema = DATABASE());
SET @sql := IF (@exist > 0, 'SELECT "Constraint fk_standings_team already exists"', 'ALTER TABLE `standings` ADD CONSTRAINT `fk_standings_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
