ALTER TABLE `playoff_matches` ADD COLUMN `bracket_side` ENUM('winners', 'losers', 'final') DEFAULT 'winners' AFTER `bracket_type`;
