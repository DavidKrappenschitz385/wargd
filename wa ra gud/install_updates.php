<?php
// wa ra gud/install_updates.php
require_once 'config/database.php';

$pageTitle = "System Update";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Updates - Sports League</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 500px; width: 100%; text-align: center; }
        .success { color: #28a745; font-size: 1.2rem; font-weight: bold; margin: 1rem 0; }
        .error { color: #dc3545; font-size: 1rem; margin: 1rem 0; }
        .log { text-align: left; background: #eee; padding: 1rem; border-radius: 4px; font-family: monospace; font-size: 0.85rem; height: 200px; overflow-y: auto; margin-bottom: 1rem; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
<div class="card">
    <h2>🚀 System Updater</h2>
    <div class="log">
<?php
$db = new Database();
$pdo = $db->connect();
$errors = false;

function executeSql($pdo, $sql, $description) {
    try {
        $pdo->exec($sql);
        echo "<div style='color:green'>✔ Success: $description</div>";
        return true;
    } catch (PDOException $e) {
        // Check if error is "Duplicate column" or "Table exists" which is fine
        if (strpos($e->getMessage(), "Duplicate column") !== false || strpos($e->getMessage(), "already exists") !== false) {
             echo "<div style='color:orange'>⚠ Skipped: $description (Already exists)</div>";
             return true;
        }
        echo "<div style='color:red'>❌ Error: $description<br><small>" . htmlspecialchars($e->getMessage()) . "</small></div>";
        return false;
    }
}

// 1. Create playoff_matches table
$sql1 = "CREATE TABLE IF NOT EXISTS `playoff_matches` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
executeSql($pdo, $sql1, "Create 'playoff_matches' table");

// 2. Add bracket_type to playoff_matches
// Using a safe way to add column only if missing is hard in pure SQL without procedures,
// so we rely on the try-catch block handling "Duplicate column name".
$sql2 = "ALTER TABLE `playoff_matches` ADD `bracket_type` VARCHAR(255) NOT NULL DEFAULT 'winners' AFTER `match_num`";
executeSql($pdo, $sql2, "Add 'bracket_type' to playoff_matches");

// 3. Add bracket_side to playoff_matches
$sql3 = "ALTER TABLE `playoff_matches` ADD `bracket_side` ENUM('winners', 'losers', 'final') NOT NULL DEFAULT 'winners' AFTER `bracket_type`";
executeSql($pdo, $sql3, "Add 'bracket_side' to playoff_matches");

// 4. Add round_number to matches
$sql4 = "ALTER TABLE `matches` ADD `round_number` INT NULL AFTER `away_score`";
executeSql($pdo, $sql4, "Add 'round_number' to matches");

// 5. Add score_difference to standings
$sql5 = "ALTER TABLE `standings` ADD COLUMN `score_difference` INT NOT NULL DEFAULT 0";
executeSql($pdo, $sql5, "Add 'score_difference' to standings");

?>
    </div>

    <div class="success">
        Database updated successfully! 🔥
    </div>
    <p>You can now use the full system features.</p>
    <a href="index.php" class="btn">Go to Homepage</a>
</div>
</body>
</html>
