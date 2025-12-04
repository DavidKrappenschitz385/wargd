<?php
require_once '../config/database.php';
requireLogin();

// Function to generate round-robin matches using the circle method
function generateRoundRobin($teams) {
    $matches = [];
    if (count($teams) % 2 != 0) {
        $teams[] = ['id' => null, 'name' => 'BYE'];
    }

    $num_teams = count($teams);
    $num_rounds = $num_teams - 1;
    $matches_per_round = $num_teams / 2;

    for ($round = 0; $round < $num_rounds; $round++) {
        for ($match_num = 0; $match_num < $matches_per_round; $match_num++) {
            $home_team = $teams[$match_num];
            $away_team = $teams[$num_teams - 1 - $match_num];

            if ($home_team['id'] !== null && $away_team['id'] !== null) {
                $matches[] = [
                    'round_number' => $round + 1,
                    'teamA' => $home_team,
                    'teamB' => $away_team,
                ];
            }
        }

        // Rotate teams
        $last_team = array_pop($teams);
        array_splice($teams, 1, 0, [$last_team]);
    }

    return $matches;
}

$league_id = $_GET['id'] ?? null;
if (!$league_id) {
    showMessage("League ID is required!", "error");
    redirect('browse_leagues.php');
}

$database = new Database();
$db = $database->connect();
$current_user = getCurrentUser();

// Get league details to verify manager
$league_query = "SELECT * FROM leagues WHERE id = :league_id";
$league_stmt = $db->prepare($league_query);
$league_stmt->bindParam(':league_id', $league_id);
$league_stmt->execute();
$league = $league_stmt->fetch(PDO::FETCH_ASSOC);

if (!$league || ($league['created_by'] != $current_user['id'] && $current_user['role'] != 'admin')) {
    showMessage("You are not authorized to manage this league.", "error");
    redirect('view_league.php?id=' . $league_id);
}

// Get teams for the league
$teams_query = "SELECT id, name FROM teams WHERE league_id = :league_id";
$teams_stmt = $db->prepare($teams_query);
$teams_stmt->bindParam(':league_id', $league_id);
$teams_stmt->execute();
$teams = $teams_stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($teams) < 2) {
    showMessage("Not enough teams to generate a schedule.", "warning");
    redirect('view_league.php?id=' . $league_id);
}

// Check if matches already exist for this league
$check_matches_query = "SELECT COUNT(*) FROM matches WHERE league_id = :league_id";
$check_stmt = $db->prepare($check_matches_query);
$check_stmt->bindParam(':league_id', $league_id);
$check_stmt->execute();
if ($check_stmt->fetchColumn() > 0) {
    showMessage("A schedule has already been generated for this league.", "error");
    redirect('view_league.php?id=' . $league_id);
}

// Generate the matches
$generated_matches = generateRoundRobin($teams);

// Save the matches to the database
try {
    $db->beginTransaction();

    $insert_query = "INSERT INTO matches (league_id, home_team_id, away_team_id, round_number, match_date, status)
                     VALUES (:league_id, :home_team_id, :away_team_id, :round_number, :match_date, 'scheduled')";
    $insert_stmt = $db->prepare($insert_query);

    // Stagger match times, e.g., starting today, 2 matches per day
    $match_date = new DateTime();
    $match_date->setTime(10, 0); // First match at 10 AM
    $matches_today = 0;

    foreach ($generated_matches as $match) {
        $home_team_id = $match['teamA']['id'];
        $away_team_id = $match['teamB']['id'];

        $insert_stmt->bindParam(':league_id', $league_id);
        $insert_stmt->bindParam(':home_team_id', $home_team_id);
        $insert_stmt->bindParam(':away_team_id', $away_team_id);
        $insert_stmt->bindParam(':round_number', $match['round_number']);
        $insert_stmt->bindParam(':match_date', $match_date->format('Y-m-d H:i:s'));
        $insert_stmt->execute();

        // Schedule next match
        $matches_today++;
        if ($matches_today >= 2) {
            $match_date->add(new DateInterval('P1D')); // Next day
            $match_date->setTime(10, 0); // Reset time
            $matches_today = 0;
        } else {
            $match_date->add(new DateInterval('PT4H')); // 4 hours later
        }
    }

    $db->commit();
    showMessage("Round-robin schedule generated and saved successfully!", "success");
} catch (Exception $e) {
    $db->rollBack();
    showMessage("Failed to save the schedule: " . $e->getMessage(), "error");
}

redirect('view_league.php?id=' . $league_id);
?>
