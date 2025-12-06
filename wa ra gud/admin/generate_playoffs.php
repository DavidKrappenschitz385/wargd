<?php
// admin/generate_playoffs.php

require_once 'header.php';
require_once '../league/playoffs.php';

$pdo = (new Database())->connect();
$message = ''; // For feedback
$error = '';   // For errors

// Get all leagues to populate the dropdown
try {
    $leaguesStmt = $pdo->query("SELECT id, name FROM leagues ORDER BY name");
    $leagues = $leaguesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching leagues: " . $e->getMessage();
    $leagues = [];
}

// --- FORM SUBMISSION LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['league_id'], $_POST['bracket_type'])) {
    $leagueId = (int)$_POST['league_id'];
    $bracketType = $_POST['bracket_type'];

    // Basic validation
    if (empty($leagueId) || empty($bracketType)) {
        $error = "Please select a league and a bracket type.";
    } else {
        $pdo->beginTransaction();
        try {
            // 1. Generate the playoff matches
            $matches = generatePlayoffBrackets($pdo, $leagueId, $bracketType);

            if (empty($matches)) {
                throw new Exception("Failed to generate the playoff matches.");
            }

            // 2. Insert the generated matches into the database
            // Note: Added bracket_side
            $insertMatchStmt = $pdo->prepare(
                "INSERT INTO playoff_matches (league_id, round, match_num, team1_id, team2_id, bracket_type, bracket_side) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $matchCount = 0;
            foreach ($matches as $match) {
                $bracketSide = $match['bracket_side'] ?? 'winners';
                $insertMatchStmt->execute([
                    $leagueId,
                    1, // Initial round
                    $matchCount + 1,
                    $match['team1'],
                    $match['team2'],
                    $bracketType,
                    $bracketSide
                ]);
                $matchCount++;
            }

            $pdo->commit();
            $message = "Successfully generated and inserted $matchCount playoff matches! 🔥";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Generate Playoff Brackets</h2>
    </div>
    <div class="card-body">
        <p>Select a league and a bracket type to automatically generate a playoff schedule.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="generate_playoffs.php" method="post">
            <div class="form-group">
                <label for="league_id">Select League</label>
                <select name="league_id" id="league_id" class="form-control" required>
                    <option value="">-- Choose a League --</option>
                    <?php foreach ($leagues as $league): ?>
                        <option value="<?= $league['id'] ?>">
                            <?= htmlspecialchars($league['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="bracket_type">Bracket Type</label>
                <select name="bracket_type" id="bracket_type" class="form-control" required>
                    <option value="single_elimination">Single Elimination</option>
                    <option value="double_elimination">Double Elimination</option>
                    <option value="knockout">Knockout</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Generate Bracket</button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>
