<?php
// admin/generate_round_robin.php
require_once '../config/database.php';
require_once '../league/schedule.php'; // 🔥 Including our new scheduling logic!
requireRole('admin');

$pdo = (new Database())->connect();
$message = '';
$error = '';

// --- HANDLE SCHEDULE GENERATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_schedule'])) {
    $league_id = filter_input(INPUT_POST, 'league_id', FILTER_VALIDATE_INT);
    $start_date_str = $_POST['start_date'];
    $match_time_str = $_POST['match_time'];
    $rest_days = filter_input(INPUT_POST, 'rest_days', FILTER_VALIDATE_INT);

    // Basic validation
    if (!$league_id || !$start_date_str || !$match_time_str || $rest_days === false) {
        $error = "All fields are required and must be valid.";
    } else {
        try {
            // 1. Check if matches already exist for this league
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE league_id = ?");
            $check_stmt->execute([$league_id]);
            if ($check_stmt->fetchColumn() > 0) {
                throw new Exception("This league already has a schedule. Please clear it before generating a new one.");
            }

            // 2. Fetch all approved team IDs for the selected league
            $team_stmt = $pdo->prepare("SELECT id FROM teams WHERE league_id = ?");
            $team_stmt->execute([$league_id]);
            $team_ids = $team_stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($team_ids) < 2) {
                throw new Exception("Not enough teams in the league to generate a schedule. At least 2 teams are required.");
            }

            // 3. Generate the schedule using our smart function
            $schedule = generateRoundRobinSchedule($team_ids);

            // 4. Insert the generated matches into the database within a transaction
            $pdo->beginTransaction();

            $insert_sql = "
                INSERT INTO matches (league_id, home_team_id, away_team_id, round, match_date, status)
                VALUES (:league_id, :home_team_id, :away_team_id, :round, :match_date, 'scheduled')
            ";
            $insert_stmt = $pdo->prepare($insert_sql);

            $current_date = new DateTime($start_date_str . ' ' . $match_time_str);
            $total_matches_created = 0;

            foreach ($schedule as $roundNum => $roundMatches) {
                foreach ($roundMatches as $match) {
                    $insert_stmt->execute([
                        ':league_id' => $league_id,
                        ':home_team_id' => $match['home'],
                        ':away_team_id' => $match['away'],
                        ':round' => $roundNum,
                        ':match_date' => $current_date->format('Y-m-d H:i:s')
                    ]);
                    $total_matches_created++;
                }
                // Advance the date for the next round
                $days_to_add = $rest_days + 1;
                $current_date->add(new DateInterval("P{$days_to_add}D"));
            }

            $pdo->commit();
            $message = "Successfully generated a round-robin schedule with {$total_matches_created} matches! 🔥";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to generate schedule: " . $e->getMessage();
        }
    }
}


// --- DATA FOR THE VIEW ---
// Fetch all leagues that are in 'active' status to populate the dropdown
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
    <title>Generate Round-Robin Schedule - Admin</title>
    <style>
        body { font-family: 'Arial', sans-serif; background: #f8f9fa; color: #333; line-height: 1.6; }
        .header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-section { margin-bottom: 2rem; }
        label { font-weight: bold; margin-bottom: 0.5rem; display: block; }
        select, input[type="date"], input[type="time"], input[type="number"] {
            width: 100%;
            padding: 0.75rem;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            color: white;
            text-decoration: none;
            font-size: 1rem;
            font-weight: bold;
            background: #28a745;
            transition: background 0.3s;
        }
        .btn:hover { background: #218838; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info-box { background: #e2e3e5; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #007bff; }
        .info-box h3 { margin-top: 0; }
        .info-box ul { padding-left: 20px; }
    </style>
</head>
<body>
    <div class="header"><h1>🗓️ Generate Round-Robin Schedule</h1></div>

    <div class="container">
        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <div class="info-box">
            <h3>How It Works</h3>
            <p>This tool automatically generates a full round-robin schedule for any active league. Here's what to do:</p>
            <ul>
                <li><strong>Select a League:</strong> Choose from the list of active leagues.</li>
                <li><strong>Set Schedule Details:</strong> Define the start date, match times, and rest days.</li>
                <li><strong>Generate:</strong> The system will create all possible pairings, ensuring every team plays every other team exactly once.</li>
            </ul>
            <p><strong>Note:</strong> This will only work for leagues that have no matches scheduled yet. If a schedule already exists, you must clear it before generating a new one.</p>
        </div>

        <form method="POST" class="form-section">
            <div class="form-group">
                <label for="league_id">1. Select a League</label>
                <select name="league_id" id="league_id" required>
                    <option value="">-- Choose an Active League --</option>
                    <?php foreach ($active_leagues as $league): ?>
                        <option value="<?php echo $league['id']; ?>">
                            <?php echo htmlspecialchars($league['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">2. Schedule Start Date</label>
                <input type="date" name="start_date" id="start_date" required value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label for="match_time">3. Match Time</label>
                <input type="time" name="match_time" id="match_time" value="18:00" required>
            </div>

            <div class="form-group">
                <label for="rest_days">4. Days Between Match Rounds</label>
                <input type="number" name="rest_days" id="rest_days" min="0" value="1" required>
                <small>Enter 0 for back-to-back match days, 1 for one rest day, etc.</small>
            </div>

            <button type="submit" name="generate_schedule" class="btn">🚀 Generate Schedule</button>
        </form>
    </div>
</body>
</html>
