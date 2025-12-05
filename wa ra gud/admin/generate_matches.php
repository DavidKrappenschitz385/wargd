<?php
// admin/generate_matches.php - Generate Round Robin Schedule
require_once '../config/database.php';
require_once '../league/schedule.php'; // Includes generateRoundRobinSchedule()
requireRole('admin');

$pdo = (new Database())->connect();
$message = '';
$error = '';

// Handle schedule generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_schedule'])) {
    $league_id = filter_input(INPUT_POST, 'league_id', FILTER_VALIDATE_INT);

    if (!$league_id) {
        $error = "Invalid league selected.";
    } else {
        try {
            // 1. Check if matches already exist for this league
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE league_id = ?");
            $stmt->execute([$league_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("A schedule has already been generated for this league. Clear existing matches first if you want to regenerate.");
            }

            // 2. Get all approved teams for the selected league
            $team_stmt = $pdo->prepare(
                "SELECT t.id FROM teams t
                 JOIN team_registration_requests trr ON t.name = trr.team_name AND t.league_id = trr.league_id
                 WHERE t.league_id = ? AND trr.status = 'approved'"
            );
            $team_stmt->execute([$league_id]);
            $team_ids = $team_stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($team_ids) < 2) {
                throw new Exception("Cannot generate a schedule. At least two approved teams are required.");
            }

            // 3. Generate the Round Robin schedule
            $schedule = generateRoundRobinSchedule($team_ids);

            // 4. Insert matches into the database
            $pdo->beginTransaction();
            $match_stmt = $pdo->prepare(
                "INSERT INTO matches (league_id, home_team_id, away_team_id, round, match_date, status)
                 VALUES (?, ?, ?, ?, NOW(), 'scheduled')"
            );

            foreach ($schedule as $roundNumber => $matches) {
                foreach ($matches as $match) {
                    // Ensure 'teamA' and 'teamB' keys exist and are not null
                    if (isset($match['teamA'], $match['teamB'])) {
                        $match_stmt->execute([
                            $league_id,
                            $match['teamA'],
                            $match['teamB'],
                            $roundNumber + 1 // Round numbers are 0-indexed in the function
                        ]);
                    }
                }
            }

            $pdo->commit();
            $message = "Successfully generated " . count($schedule) . " rounds of matches for the league! 🔥";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch active leagues to populate the dropdown
try {
    $leagues_stmt = $pdo->query("SELECT id, name FROM leagues WHERE status = 'active' ORDER BY name");
    $active_leagues = $leagues_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Could not fetch active leagues: " . $e->getMessage();
    $active_leagues = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Match Schedule - Admin</title>
    <style>
        body { font-family: 'Arial', sans-serif; background: #f8f9fa; color: #333; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        select, .btn { width: 100%; padding: 0.75rem; border-radius: 4px; font-size: 1rem; }
        select { border: 1px solid #ddd; }
        .btn { border: none; cursor: pointer; color: white; transition: background 0.3s; }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0056b3; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <div class="header"><h1>🗓️ Generate Round-Robin Schedule</h1></div>

    <div class="container">
        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <div class="alert alert-info">
            <strong>How it works:</strong> Select an 'active' league, and the system will automatically generate a full round-robin schedule where every approved team plays every other team exactly once.
        </div>

        <form method="POST" onsubmit="return confirm('Are you sure you want to generate a new schedule? This cannot be undone if matches already exist.');">
            <div class="form-group">
                <label for="league_id">Select an Active League</label>
                <select name="league_id" id="league_id" required>
                    <option value="">-- Choose a League --</option>
                    <?php foreach ($active_leagues as $league): ?>
                        <option value="<?php echo $league['id']; ?>"><?php echo htmlspecialchars($league['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="generate_schedule" class="btn btn-primary">
                ⚡ Generate Schedule
            </button>
        </form>
    </div>
</body>
</html>
