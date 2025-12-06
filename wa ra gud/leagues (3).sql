-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2025 at 07:32 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `leagues`
--

-- --------------------------------------------------------

--
-- Table structure for table `leagues`
--

CREATE TABLE `leagues` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sport_id` int(11) NOT NULL,
  `season` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `registration_deadline` date NOT NULL,
  `max_teams` int(11) DEFAULT 16,
  `rules` text DEFAULT NULL,
  `status` enum('draft','open','closed','active','completed') DEFAULT 'draft',
  `approval_required` tinyint(1) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leagues`
--

INSERT INTO `leagues` (`id`, `name`, `sport_id`, `season`, `start_date`, `end_date`, `registration_deadline`, `max_teams`, `rules`, `status`, `approval_required`, `created_by`, `created_at`) VALUES
(1, 'Marc', 2, '2', '2025-08-20', '2025-08-30', '2025-08-14', 14, 'foul sunggo', 'draft', 0, 1, '2025-08-09 05:35:31'),
(2, 'Marc', 2, '2', '2025-08-20', '2025-08-30', '2025-08-14', 14, 'foul sunggo', 'draft', 0, 1, '2025-08-09 05:35:35'),
(4, 'wqewqe', 1, 'wqewqe', '2025-08-28', '2025-09-04', '2025-08-14', 16, 'wewe', 'draft', 0, 1, '2025-08-09 20:13:50'),
(11, 'Sugbo Cup League', 2, '12', '2025-08-29', '2025-09-03', '2025-08-23', 15, 'No hard feelings', 'draft', 0, 1, '2025-08-21 13:40:20'),
(13, 'Sugbo Cup League', 2, '12', '2025-08-29', '2025-09-03', '2025-08-23', 15, 'No hard feelings', 'active', 0, 1, '2025-08-21 13:41:43'),
(15, 'SK LEAGUE FOR NEWBIES', 2, '1', '2025-09-11', '2025-09-27', '2025-09-27', 10, 'JUST PLAY FOR FUN', 'active', 0, 1, '2025-09-09 01:11:50'),
(16, 'Edu rama', 2, '2', '2025-10-30', '2025-12-29', '2025-10-07', 16, 'wew', 'open', 0, 1, '2025-09-30 14:06:25'),
(17, 'Ball Club League Buhisanon', 2, '1', '2025-11-01', '2025-12-31', '2025-10-09', 12, 'for fun', 'open', 0, 1, '2025-10-02 12:59:21'),
(18, 'Labangon SK League 2025', 2, '1', '2025-11-02', '2026-01-01', '2025-10-10', 20, 'Open for labangon residents only', 'open', 0, 1, '2025-10-03 17:30:12'),
(19, 'Buhisanon League', 3, '1', '2025-11-03', '2026-01-02', '2025-10-11', 16, '1qwq', 'open', 0, 1, '2025-10-04 06:00:22'),
(20, 'ZZZZ', 1, '2', '2025-11-03', '2026-01-02', '2025-10-11', 16, '', 'open', 1, 1, '2025-10-04 16:41:55'),
(21, 'CEC Cesafi', 2, '1', '2025-11-03', '2026-01-02', '2025-10-11', 16, '12', 'open', 1, 1, '2025-10-04 17:22:30'),
(22, 'NBA', 2, '12', '2025-11-04', '2026-01-03', '2025-10-12', 16, 'STRONG', 'open', 1, 1, '2025-10-05 15:40:09'),
(23, 'EDU RAMA LEAGUE', 2, '12', '2025-11-10', '2026-01-09', '2025-10-18', 16, 'WEWE', 'open', 1, 1, '2025-10-11 04:39:29'),
(24, 'CEC BASKETBALL LEAGUE', 2, '1', '2025-11-15', '2026-01-14', '2025-10-23', 13, 'play for fun', 'open', 1, 1, '2025-10-16 02:16:25');

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id` int(11) NOT NULL,
  `league_id` int(11) NOT NULL,
  `home_team_id` int(11) NOT NULL,
  `away_team_id` int(11) NOT NULL,
  `round` int(11) DEFAULT NULL,
  `match_num` int(11) DEFAULT NULL,
  `venue_id` int(11) DEFAULT NULL,
  `match_date` datetime NOT NULL,
  `home_score` int(11) DEFAULT NULL,
  `away_score` int(11) DEFAULT NULL,
  `round_number` int(11) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled','postponed') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 1, 'Logged Out', 'You have successfully logged out.', 'info', 0, '2025-08-09 05:06:56');

-- --------------------------------------------------------

--
-- Table structure for table `player_stats`
--

CREATE TABLE `player_stats` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `league_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `matches_played` int(11) DEFAULT 0,
  `goals` int(11) DEFAULT 0,
  `assists` int(11) DEFAULT 0,
  `yellow_cards` int(11) DEFAULT 0,
  `red_cards` int(11) DEFAULT 0,
  `minutes_played` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registration_requests`
--

CREATE TABLE `registration_requests` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `current_address` text DEFAULT NULL,
  `sitio` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `team_id` int(11) NOT NULL,
  `league_id` int(11) NOT NULL,
  `preferred_position` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `document_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sports`
--

CREATE TABLE `sports` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `max_players_per_team` int(11) DEFAULT 11,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sports`
--

INSERT INTO `sports` (`id`, `name`, `description`, `max_players_per_team`, `created_at`) VALUES
(1, 'Badminton', 'Badminton Association (Single & Doubles)', 2, '2025-08-09 03:22:05'),
(2, 'Basketball', 'Labangon Basketball', 13, '2025-08-09 03:22:05'),
(3, 'Volleyball', 'Indoor/Outdoor Volleyball', 13, '2025-08-09 03:22:05'),
(4, 'Tennis', 'Singles/Doubles Tennis', 2, '2025-08-09 03:22:05');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `league_id` int(11) DEFAULT NULL,
  `owner_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `wins` int(11) DEFAULT 0,
  `losses` int(11) DEFAULT 0,
  `draws` int(11) DEFAULT 0,
  `points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `goals_for` int(11) DEFAULT 0,
  `goals_against` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_league_registrations`
--

CREATE TABLE `team_league_registrations` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `league_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `sitio` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `birthday` date NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `photo_id_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `position` varchar(50) DEFAULT NULL,
  `jersey_number` int(11) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_registration_requests`
--

CREATE TABLE `team_registration_requests` (
  `id` int(11) NOT NULL,
  `league_id` int(11) NOT NULL,
  `team_name` varchar(255) NOT NULL,
  `team_owner_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `request_message` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `role` enum('player','team_owner','admin') DEFAULT 'player',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `address`, `role`, `created_at`, `updated_at`, `status`) VALUES
(1, 'admin', 'admin@sportsleague.com', '$2y$10$QRMEydO4LLStL5/amQ1sTexmcZMvj1.sGxBa6CuKdCiJil7buEMyG', 'System', 'Administrator', '', 'Buhisan', 'admin', '2025-08-09 03:22:05', '2025-10-16 02:26:51', 'active'),
(2, 'vanz', 'vanzy@gmail.com', '$2y$10$pCBh51c8u.ZTeXMKYMfpnexqFn2jBcGpsUe/j/Mldeos1WVMDgW/O', 'John', 'Mark', '112212', NULL, 'team_owner', '2025-08-09 15:54:36', '2025-09-28 02:27:43', 'active'),
(3, 'james', 'james@gmail.com', '$2y$10$gRok6CiY8I/O8hEzJX1aHueAcV6zRgVLFBIahBJkI14IS/spPiidy', 'James', 'Lover', '09452560280', NULL, 'team_owner', '2025-08-22 08:12:15', '2025-10-16 02:26:32', 'active'),
(4, 'leemar', 'leemar@gmail.com', '$2y$10$xWyt/kdcivlh1QtuCH5.L.X6rOq.Mept2M/OHEG27KdH72BpJrzia', 'Leemar', 'Baril', '09452560280', NULL, 'player', '2025-08-27 05:57:13', '2025-08-27 06:00:30', 'active'),
(5, 'kevs', 'kevs@gmail.com', '$2y$10$Gbht9fq1jYVkjJd3l9N14eN6AbwxNeYbULcKxzowT.MHXzL9kNRaO', 'Kevin', 'John', '093248699580', NULL, 'player', '2025-09-08 16:37:31', '2025-09-09 03:19:17', 'active'),
(6, 'lebron', 'lebronjames@gmail.com', '$2y$10$NErWJTkrxpulB5gV2jBaC.S3GO/A.E8EAtaBd/Yqr0epyvP2pTXBC', 'Lebron', 'James', '09452560280', NULL, 'player', '2025-10-02 11:22:36', '2025-10-02 11:43:17', 'active'),
(7, 'peter', 'peter@gmail.com', '$2y$10$R5cWZwc5TA2d3Kqnl5uIru.d6kcVBYjA.L.OY0U.EzeRFW7ZHkR06', 'peter', 'hahays', '565565656', NULL, 'player', '2025-10-02 11:44:42', '2025-10-16 02:24:24', 'active'),
(8, 'mjay', 'mjay@gmail.com', '$2y$10$L50HHB0kEOPT24P4IQ8DSeuv4H0WnQ0Mlac3PEigJ4t.bGQtI5NcO', 'Mjay', 'Calayag', '123123', NULL, 'team_owner', '2025-10-02 13:00:38', '2025-10-05 06:52:34', 'active'),
(10, 'roly', 'roly@gmail.com', '$2y$10$I15eGhwampoK01D4WcNRkOURaP2Jde10NtzZTqRUDuxufGfnvwVNC', 'roly', 'gastanes', '000000', NULL, 'player', '2025-10-02 17:25:49', '2025-10-02 17:25:49', 'active'),
(11, 'Jeinilyn', 'jeinilyn@gmail.com', '$2y$10$2sU5qiQIn91.dlmBOjdNYOR0gxEjxtPbbn22HWfF1CYtECcZHdtrO', 'Jeinilyn', 'Unknown', '0912312323', NULL, 'player', '2025-10-03 06:55:36', '2025-10-03 17:35:56', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `facilities` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`id`, `name`, `address`, `capacity`, `facilities`, `created_at`) VALUES
(1, 'Central Sports Complex', '123 Sports Ave, City Center', 5000, NULL, '2025-08-09 03:22:05'),
(2, 'Community Field A', '456 Park St, Downtown', 1000, NULL, '2025-08-09 03:22:05'),
(3, 'Indoor Arena', '789 Arena Blvd, Sports District', 3000, NULL, '2025-08-09 03:22:05'),
(4, 'zzz', 'asdasdasd', 500, 'asd', '2025-08-09 05:45:24'),
(5, 'Cebu EASTERN', 'LEON KILAT STREET', 1000, 'PAY PARKING', '2025-10-02 11:18:18');

-- --------------------------------------------------------

--
-- Table structure for table `standings`
--

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
  UNIQUE KEY `unique_league_team` (`league_id`,`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `playoff_matches`
--

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `leagues`
--
ALTER TABLE `leagues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sport_id` (`sport_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `league_id` (`league_id`),
  ADD KEY `home_team_id` (`home_team_id`),
  ADD KEY `away_team_id` (`away_team_id`),
  ADD KEY `venue_id` (`venue_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `player_stats`
--
ALTER TABLE `player_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_player_league` (`player_id`,`league_id`),
  ADD KEY `league_id` (`league_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `registration_requests`
--
ALTER TABLE `registration_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `league_id` (`league_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `sports`
--
ALTER TABLE `sports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `league_id` (`league_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `team_league_registrations`
--
ALTER TABLE `team_league_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_league` (`team_id`,`league_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_team_id` (`team_id`),
  ADD KEY `idx_league_id` (`league_id`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_player` (`team_id`,`player_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indexes for table `team_registration_requests`
--
ALTER TABLE `team_registration_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `league_id` (`league_id`),
  ADD KEY `team_owner_id` (`team_owner_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `leagues`
--
ALTER TABLE `leagues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=430;

--
-- AUTO_INCREMENT for table `player_stats`
--
ALTER TABLE `player_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registration_requests`
--
ALTER TABLE `registration_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sports`
--
ALTER TABLE `sports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `team_league_registrations`
--
ALTER TABLE `team_league_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `team_registration_requests`
--
ALTER TABLE `team_registration_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `leagues`
--
ALTER TABLE `leagues`
  ADD CONSTRAINT `leagues_ibfk_1` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`),
  ADD CONSTRAINT `leagues_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`),
  ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`home_team_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `matches_ibfk_3` FOREIGN KEY (`away_team_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `matches_ibfk_4` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `player_stats`
--
ALTER TABLE `player_stats`
  ADD CONSTRAINT `player_stats_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `player_stats_ibfk_2` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`),
  ADD CONSTRAINT `player_stats_ibfk_3` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`);

--
-- Constraints for table `registration_requests`
--
ALTER TABLE `registration_requests`
  ADD CONSTRAINT `registration_requests_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `registration_requests_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `registration_requests_ibfk_3` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`),
  ADD CONSTRAINT `registration_requests_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `standings`
--
ALTER TABLE `standings`
  ADD CONSTRAINT `standings_ibfk_1` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `standings_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`),
  ADD CONSTRAINT `teams_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `team_league_registrations`
--
ALTER TABLE `team_league_registrations`
  ADD CONSTRAINT `team_league_registrations_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_league_registrations_ibfk_2` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_league_registrations_ibfk_3` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_league_registrations_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `team_registration_requests`
--
ALTER TABLE `team_registration_requests`
  ADD CONSTRAINT `team_registration_requests_ibfk_1` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_registration_requests_ibfk_2` FOREIGN KEY (`team_owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_registration_requests_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
