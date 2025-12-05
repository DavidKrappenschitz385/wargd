<?php
// admin/record_scores.php

require_once 'header.php';
require_once '../match/results.php'; // The logic to update standings

$pdo = (new Database())->connect();
$message = ''; // For feedback
$error = '';   // For errors

// --- FORM SUBMISSION LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['match_id'], $_POST['home_score'], $_POST['away_score'])) {
    $matchId = (int)$_POST['match_id'];
    $homeScore = (int)$_POST['home_score'];
    $awayScore = (int)$_POST['away_score'];

    if (empty($matchId) || !isset($_POST['home_score']) || !isset($_POST['away_score'])) {
        $error = "Please fill in all fields.";
    } else {
        // Define the points system (e.g., 3 for a win, 1 for a draw, 0 for a loss)
        $pointsConfig = ['win' => 3, 'draw' => 1, 'loss' => 0];

        // Call the function to record the result and update standings
        $success = recordMatchResult($pdo, $matchId, $homeScore, $awayScore, $pointsConfig);

        if ($success) {
            $message = "Match result recorded successfully! Standings have been updated. 🔥";
        } else {
            $error = "Failed to record match result. It might have already been recorded or the match ID is invalid.";
        }
    }
}

// Get all leagues for the filter dropdown
try {
    $leaguesStmt = $pdo->query("SELECT id, name FROM leagues ORDER BY name");
    $leagues = $leaguesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle this gracefully
    $leagues = [];
    $error = "Could not fetch leagues.";
}


// --- FILTERING LOGIC ---
$selectedLeagueId = $_GET['league_id'] ?? null;
$whereClause = "WHERE m.status = 'scheduled'";
$params = [];

if ($selectedLeagueId) {
    $whereClause .= " AND m.league_id = ?";
    $params[] = $selectedLeagueId;
}


// Get all scheduled matches to populate the table, with optional filtering
try {
    $sql = "
        SELECT
            m.id,
            ht.name AS home_team,
            at.name AS away_team,
            l.name AS league_name,
            m.round_number,
            m.match_date
        FROM matches m
        JOIN teams ht ON m.home_team_id = ht.id
        JOIN teams at ON m.away_team_id = at.id
        JOIN leagues l ON m.league_id = l.id
        $whereClause
        ORDER BY l.name, m.round_number
    ";
    $matchesStmt = $pdo->prepare($sql);
    $matchesStmt->execute($params);
    $matches = $matchesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching matches: " . $e->getMessage();
    $matches = [];
}
?>

<div class="card">
    <div class="card-header">
        <h2>Record Match Scores</h2>
    </div>
    <div class="card-body">
        <p>Select a match, enter the final scores, and the system will automatically update the league standings.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- League Filter Form -->
        <form action="record_scores.php" method="get" class="mb-3">
            <div class="form-row align-items-end">
                <div class="col">
                    <label for="league_id">Filter by League</label>
                    <select name="league_id" id="league_id" class="form-control">
                        <option value="">All Leagues</option>
                        <?php foreach ($leagues as $league): ?>
                            <option value="<?= $league['id'] ?>" <?= ($selectedLeagueId == $league['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($league['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-info">Filter</button>
                </div>
            </div>
        </form>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>League</th>
                    <th>Round</th>
                    <th>Home Team</th>
                    <th>Away Team</th>
                    <th>Match Date</th>
                    <th>Scores</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matches as $match): ?>
                    <tr>
                        <td><?= htmlspecialchars($match['league_name']) ?></td>
                        <td><?= htmlspecialchars($match['round_number']) ?></td>
                        <td><?= htmlspecialchars($match['home_team']) ?></td>
                        <td><?= htmlspecialchars($match['away_team']) ?></td>
                        <td><?= htmlspecialchars($match['match_date']) ?></td>
                        <form action="record_scores.php" method="post">
                            <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                            <td>
                                <div class="form-row">
                                    <div class="col">
                                        <input type="number" name="home_score" class="form-control" placeholder="Home" required>
                                    </div>
                                    <div class="col">
                                        <input type="number" name="away_score" class="form-control" placeholder="Away" required>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
