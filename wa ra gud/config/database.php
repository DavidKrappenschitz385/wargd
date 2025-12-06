<?php
// config/database.php - Database Configuration

class Database {
    private $host = 'localhost';
    private $db_name = 'leagues';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");

            // 🔥 Check for auto-updates (User requested zero-config)
            $this->autoMigrate();

        } catch(PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }

        return $this->conn;
    }

    private function autoMigrate() {
        // Check if the update is needed by checking for the 'playoff_matches' table
        try {
            $this->conn->query("SELECT 1 FROM playoff_matches LIMIT 1");
            // If query succeeds, table exists.

            // Check for standings table existence
            try {
                $this->conn->query("SELECT 1 FROM standings LIMIT 1");
            } catch (PDOException $e) {
                 // Standings table missing, run updates to create it
                 $this->runUpdates();
                 return;
            }

            // Check for new columns in existing tables (e.g. score_difference in standings)
            try {
                $this->conn->query("SELECT score_difference FROM standings LIMIT 1");
            } catch (PDOException $e) {
                // Column missing, run the updates
                $this->runUpdates();
            }
        } catch (PDOException $e) {
            // Table missing, run the updates
            $this->runUpdates();
        }
    }

    private function runUpdates() {
        $updates = [
            // 0. Create standings table (Split creation to fix Error 150)
            "CREATE TABLE IF NOT EXISTS `standings` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // 0.1 Apply FK constraints for standings (if they don't exist)
            // Note: We use try/catch in execution loop, so just attempting ALTER is fine
            "ALTER TABLE `standings` ADD CONSTRAINT `fk_standings_league` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`) ON DELETE CASCADE",
            "ALTER TABLE `standings` ADD CONSTRAINT `fk_standings_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE",

            // 1. Create playoff_matches
            "CREATE TABLE IF NOT EXISTS `playoff_matches` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `league_id` int(11) NOT NULL,
              `round` int(11) NOT NULL,
              `match_num` int(11) NOT NULL,
              `team1_id` int(11) DEFAULT NULL,
              `team2_id` int(11) DEFAULT NULL,
              `winner_id` int(11) DEFAULT NULL,
              `status` varchar(255) NOT NULL DEFAULT 'scheduled',
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `league_id` (`league_id`),
              KEY `team1_id` (`team1_id`),
              KEY `team2_id` (`team2_id`),
              KEY `winner_id` (`winner_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // 2. Add bracket_type
            "ALTER TABLE `playoff_matches` ADD `bracket_type` VARCHAR(255) NOT NULL DEFAULT 'winners' AFTER `match_num`",

            // 3. Add bracket_side
            "ALTER TABLE `playoff_matches` ADD `bracket_side` ENUM('winners', 'losers', 'final') NOT NULL DEFAULT 'winners' AFTER `bracket_type`",

            // 4. Add round_number to matches
            "ALTER TABLE `matches` ADD `round_number` INT NULL AFTER `away_score`",

            // 5. Add score_difference to standings
            "ALTER TABLE `standings` ADD COLUMN `score_difference` INT NOT NULL DEFAULT 0"
        ];

        foreach ($updates as $sql) {
            try {
                $this->conn->exec($sql);
            } catch (PDOException $e) {
                // Ignore "Duplicate column" or "Table exists" errors
                // This allows the script to be idempotent
            }
        }
    }
}

// Session configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper functions

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;

    $database = new Database();
    $db = $database->connect();

    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    $user = getCurrentUser();
    if ($user['role'] !== $role && $user['role'] !== 'admin') {
        header('Location: /dashboard.php');
        exit();
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function showMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        echo "<div class='alert alert-$type'>$message</div>";
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}
?>