<?php
// match/record_result.php - Record Match Results
require_once '../config/database.php';
requireLogin();

$match_id = $_GET['id'] ?? null;
if (!$match_id) {
    showMessage("Match ID is required!", "error");
    header('Location: ../league/browse_leagues.php');
    exit;
}

$database = new Database();
$db = $database->connect();
$current_user = getCurrentUser();

// Get match details
$match_query = "SELECT m.*, l.name as league_name, l.created_by,
                       ht.name as home_team_name, at.name as away_team_name
                FROM matches m
                JOIN leagues l ON m.league_id = l.id
                JOIN teams ht ON m.home_team_id = ht.id
                JOIN teams at ON m.away_team_id = at.id
                WHERE m.id = :match_id";
$match_stmt = $db->prepare($match_query);
$match_stmt->bindParam(':match_id', $match_id);
$match_stmt->execute();
$match = $match_stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    showMessage("Match not found!", "error");
    header('Location: ../league/browse_leagues.php');
    exit;
}

// Check permissions (only league creator or admin)
if ($current_user['role'] != 'admin' && $match['created_by'] != $current_user['id']) {
    showMessage("You do not have permission to record results for this match.", "error");
    header('Location: ../league/view_league.php?id=' . $match['league_id']);
    exit;
}

// Handle form submission
if (isset($_POST['record_result'])) {
    $home_score = (int)$_POST['home_score'];
    $away_score = (int)$_POST['away_score'];
    $notes = trim($_POST['notes']);

    if ($home_score < 0 || $away_score < 0) {
        showMessage("Scores cannot be negative!", "error");
    } else {
        require_once 'results.php';

        // Define points config (could be fetched from league settings if available)
        $pointsConfig = ['win' => 3, 'draw' => 1, 'loss' => 0];

        if (recordMatchResult($db, $match_id, $home_score, $away_score, $pointsConfig, 'regular')) {
            // Update notes if provided
            if (!empty($notes)) {
                $notes_stmt = $db->prepare("UPDATE matches SET notes = :notes WHERE id = :id");
                $notes_stmt->execute([':notes' => $notes, ':id' => $match_id]);
            }

            showMessage("Match result recorded successfully! Standings have been updated.", "success");
            header('Location: ../league/view_league.php?id=' . $match['league_id']);
            exit;
        } else {
            showMessage("Failed to record match result!", "error");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Result - <?php echo htmlspecialchars($match['home_team_name']); ?> vs <?php echo htmlspecialchars($match['away_team_name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Arial', sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
        }

        .match-info {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .league-name {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .teams-display {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            font-size: 1.2rem;
            font-weight: bold;
            margin: 1rem 0;
        }

        .vs {
            color: #999;
            font-size: 0.9rem;
            font-weight: normal;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .score-inputs {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
        }

        .score-field {
            flex: 1;
            text-align: center;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1.1rem;
        }

        .score-field input {
            font-size: 2rem;
            text-align: center;
            font-weight: bold;
            padding: 1rem;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-primary {
            background: #28a745;
            color: white;
        }

        .btn-primary:hover {
            background: #218838;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-top: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Record Result</h1>
    </div>

    <div class="container">
        <?php displayMessage(); ?>

        <div class="card">
            <div class="match-info">
                <div class="league-name"><?php echo htmlspecialchars($match['league_name']); ?></div>
                <div class="teams-display">
                    <span><?php echo htmlspecialchars($match['home_team_name']); ?></span>
                    <span class="vs">VS</span>
                    <span><?php echo htmlspecialchars($match['away_team_name']); ?></span>
                </div>
                <div style="color: #666; font-size: 0.9rem;">
                    <?php echo date('F j, Y - g:i A', strtotime($match['match_date'])); ?>
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label style="text-align: center; margin-bottom: 1rem;">Enter Final Score</label>
                    <div class="score-inputs">
                        <div class="score-field">
                            <label><?php echo htmlspecialchars($match['home_team_name']); ?></label>
                            <input type="number" name="home_score" class="form-control" min="0" required
                                   value="<?php echo $match['status'] == 'completed' ? $match['home_score'] : '0'; ?>">
                        </div>
                        <div class="score-field">
                            <label><?php echo htmlspecialchars($match['away_team_name']); ?></label>
                            <input type="number" name="away_score" class="form-control" min="0" required
                                   value="<?php echo $match['status'] == 'completed' ? $match['away_score'] : '0'; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Match Notes (Optional)</label>
                    <textarea name="notes" class="form-control" placeholder="Enter any notes about the match (e.g. halftime score, penalties, etc.)"><?php echo htmlspecialchars($match['notes'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="record_result" class="btn btn-primary">
                    💾 Save Result
                </button>

                <a href="../league/view_league.php?id=<?php echo $match['league_id']; ?>" class="btn btn-secondary">
                    Cancel
                </a>
            </form>
        </div>
    </div>
</body>
</html>