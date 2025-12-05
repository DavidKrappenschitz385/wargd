<?php
require_once 'wa ra gud/config/database.php';

$db = new Database();
$conn = $db->connect();

$migrationsDir = 'migrations/';
$files = scandir($migrationsDir);

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
        $sql = file_get_contents($migrationsDir . $file);
        if ($conn->multi_query($sql)) {
            echo "Successfully ran migration: $file\n";
            // Important to clear results after multi_query
            while ($conn->next_result()) {;}
        } else {
            echo "Error running migration $file: " . $conn->error . "\n";
        }
    }
}

$conn->close();
?>
