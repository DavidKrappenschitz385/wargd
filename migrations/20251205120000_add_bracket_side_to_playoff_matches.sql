ALTER TABLE `playoff_matches` ADD `bracket_side` ENUM('winners', 'losers', 'final') NOT NULL DEFAULT 'winners' AFTER `bracket_type`;
