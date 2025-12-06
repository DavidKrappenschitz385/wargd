<?php
require_once 'wa ra gud/config/database.php';

$db = new Database();
$pdo = $db->connect();

$file = 'migrations/20251205120000_add_bracket_side_to_playoff_matches.sql';
$sql = file_get_contents($file);

try {
    $pdo->exec($sql);
    echo "Successfully ran migration: $file\n";
} catch (PDOException $e) {
    echo "Error running migration $file: " . $e->getMessage() . "\n";
}
?>
