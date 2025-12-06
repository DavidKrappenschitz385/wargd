-- Database Update Script for Sports League System
-- Import this file into your 'leagues' database to enable Playoff and Standings features.

-- 1. Create table for Playoff Matches
CREATE TABLE IF NOT EXISTS `playoff_matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `league_id` int(11) NOT NULL,
  `round` int(11) NOT NULL,
  `match_num` int(11) NOT NULL,
  `team1_id` int(11) DEFAULT NULL,
  `team2_id` int(11) DEFAULT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'scheduled',
  `bracket_type` varchar(255) NOT NULL DEFAULT 'winners',
  `bracket_side` enum('winners', 'losers', 'final') NOT NULL DEFAULT 'winners',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `league_id` (`league_id`),
  KEY `team1_id` (`team1_id`),
  KEY `team2_id` (`team2_id`),
  KEY `winner_id` (`winner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Add 'round_number' to the main matches table (for Round Robin tracking)
-- We use a stored procedure to safely add columns only if they don't exist
-- (Simple SQL import often fails if column exists, so we try a direct ALTER.
--  If you get an error that column exists, you can ignore it).

ALTER TABLE `matches` ADD `round_number` INT NULL AFTER `away_score`;

-- 3. Add 'score_difference' to standings for better sorting
ALTER TABLE `standings` ADD COLUMN `score_difference` INT NOT NULL DEFAULT 0;
