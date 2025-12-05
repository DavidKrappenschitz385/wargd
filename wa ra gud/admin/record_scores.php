<?php
// admin/record_scores.php - Interface for Recording Match Scores
require_once '../config/database.php';
require_once '../match/results.php'; // Includes recordMatchResult()
requireRole('admin');

$pdo = (new Database())->connect();
$message = '';
$error = '';

// Handle score submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_score'])) {
    $match_id = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
    $home_score = filter_input(INPUT_POST, 'home_score', FILTER_VALIDATE_INT);
    $away_score = filter_input(INPUT_POST, 'away_score', FILTER_VALIDATE_INT);

    // Basic validation
    if ($match_id === false || $home_score === false || $away_score === false) {
        $error = "Invalid input. Please enter valid scores for the match.";
    } else {
        // Define the points system (can be made dynamic later if needed)
        $pointsConfig = ['win' => 3, 'draw' => 1, 'loss' => 0];

        // Call the function to record the result
        $success = recordMatchResult($pdo, $match_id, $home_score, $away_score, $pointsConfig);

        if ($success) {
            $message = "Match result recorded successfully! Standings have been updated. 🔥";
        } else {
            $error = "Failed to record match result. It might be already completed or the match ID is invalid.";
        }
    }
}

// Fetch leagues with scheduled matches to populate the filter dropdown
try {
    $leagues_stmt = $pdo->query("SELECT DISTINCT l.id, l.name FROM leagues l JOIN matches m ON l.id = m.league_id WHERE m.status = 'scheduled' ORDER BY l.name");
    $leagues_with_matches = $leagues_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Could not fetch leagues: " . $e->getMessage();
    $leagues_with_matches = [];
}

// Fetch scheduled matches, with optional league filtering
$selected_league = filter_input(INPUT_GET, 'league_id', FILTER_VALIDATE_INT);
$sql = "
    SELECT
        m.id,
        m.match_date,
        l.name as league_name,
        ht.name as home_team_name,
        at.name as away_team_name
    FROM matches m
    JOIN leagues l ON m.league_id = l.id
    JOIN teams ht ON m.home_team_id = ht.id
    JOIN teams at ON m.away_team_id = at.id
    WHERE m.status = 'scheduled'
";
if ($selected_league) {
    $sql .= " AND m.league_id = :league_id";
}
$sql .= " ORDER BY m.match_date ASC, l.name, m.id";

$matches_stmt = $pdo->prepare($sql);
if ($selected_league) {
    $matches_stmt->bindParam(':league_id', $selected_league, PDO::PARAM_INT);
}
$matches_stmt->execute();
$scheduled_matches = $matches_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Match Scores - Admin</title>
    <style>
        body { font-family: 'Arial', sans-serif; background: #f8f9fa; color: #333; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; }
        .container { max-width: 900px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .filter-section { margin-bottom: 2rem; display: flex; gap: 1rem; align-items: center; }
        select { padding: 0.5rem; border-radius: 4px; border: 1px solid #ddd; font-size: 1rem; }
        .btn { padding: 0.5rem 1rem; border-radius: 4px; border: none; cursor: pointer; color: white; text-decoration: none; }
        .btn-secondary { background: #6c757d; }
        .match-list { border-collapse: collapse; width: 100%; }
        .match-list th, .match-list td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        .match-list th { background: #f8f9fa; }
        .team-names { font-weight: bold; }
        .vs { color: #999; }
        .score-input { width: 60px; padding: 0.5rem; text-align: center; border: 1px solid #ccc; border-radius: 4px; }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #28a745; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .no-matches { text-align: center; padding: 2rem; color: #666; }
    </style>
</head>
<body>
    <div class="header"><h1>📝 Record Match Scores</h1></div>

    <div class="container">
        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <div class="filter-section">
            <form method="GET" style="display: flex; align-items: center; gap: 1rem;">
                <label for="league_id">Filter by League:</label>
                <select name="league_id" id="league_id" onchange="this.form.submit()">
                    <option value="">-- All Leagues --</option>
                    <?php foreach ($leagues_with_matches as $league): ?>
                        <option value="<?php echo $league['id']; ?>" <?php echo ($selected_league == $league['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($league['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <a href="record_scores.php" class="btn btn-secondary">Clear Filter</a>
            </form>
        </div>

        <?php if (empty($scheduled_matches)): ?>
            <div class="no-matches">
                <h3>No scheduled matches found.</h3>
                <p>Once you generate a schedule, the matches will appear here ready for score entry.</p>
            </div>
        <?php else: ?>
            <table class="match-list">
                <thead>
                    <tr>
                        <th>Match</th>
                        <th>League</th>
                        <th>Score</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scheduled_matches as $match): ?>
                    <tr>
                        <td class="team-names">
                            <?php echo htmlspecialchars($match['home_team_name']); ?>
                            <span class="vs">vs</span>
                            <?php echo htmlspecialchars($match['away_team_name']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($match['league_name']); ?></td>
                        <form method="POST">
                            <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                            <td>
                                <input type="number" name="home_score" class="score-input" min="0" required placeholder="Home">
                                -
                                <input type="number" name="away_score" class="score-input" min="0" required placeholder="Away">
                            </td>
                            <td>
                                <button type="submit" name="record_score" class="btn btn-sm">Save Result</button>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
