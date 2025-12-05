<?php
// admin/generate_matches.php

require_once 'header.php';
require_once '../league/schedule.php'; // The Round Robin generator

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['league_id'], $_POST['match_date'])) {
    $leagueId = (int)$_POST['league_id'];
    $matchDate = $_POST['match_date'];

    // Basic validation
    if (empty($leagueId) || empty($matchDate)) {
        $error = "Please select a league and a match date.";
    } else {
        $pdo->beginTransaction();
        try {
            // 1. Check if matches already exist for this league to prevent duplicates
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE league_id = ?");
            $checkStmt->execute([$leagueId]);
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception("Matches have already been generated for this league.");
            }

            // 2. Get all approved teams for the selected league
            $teamStmt = $pdo->prepare("SELECT t.id FROM teams t JOIN team_registration_requests trr ON t.id = trr.team_id WHERE trr.league_id = ? AND trr.status = 'approved'");
            $teamStmt->execute([$leagueId]);
            $teamIds = $teamStmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($teamIds) < 2) {
                throw new Exception("This league needs at least two approved teams to generate a schedule.");
            }

            // 3. Generate the schedule using the existing function
            $schedule = generateRoundRobinSchedule($teamIds);

            if (empty($schedule)) {
                throw new Exception("Failed to generate the schedule.");
            }

            // 4. Insert the generated matches into the database
            $insertMatchStmt = $pdo->prepare(
                "INSERT INTO matches (league_id, home_team_id, away_team_id, round_number, match_date, status) VALUES (?, ?, ?, ?, ?, 'scheduled')"
            );

            $matchCount = 0;
            foreach ($schedule as $roundNumber => $matches) {
                foreach ($matches as $match) {
                    $insertMatchStmt->execute([
                        $leagueId,
                        $match['home'],
                        $match['away'],
                        $roundNumber,
                        $matchDate
                    ]);
                    $matchCount++;
                }
            }

            $pdo->commit();
            $message = "Successfully generated and inserted $matchCount matches for $matchDate! 🔥";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Generate Matches</h2>
    </div>
    <div class="card-body">
        <p>Select a league and a date to automatically generate a full schedule where every team plays each other once.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="generate_matches.php" method="post">
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
                <label for="match_date">Match Date</label>
                <input type="date" name="match_date" id="match_date" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Generate Schedule</button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>
