ALTER TABLE `playoff_matches` ADD `bracket_type` VARCHAR(255) NOT NULL DEFAULT 'winners' AFTER `match_num`;
