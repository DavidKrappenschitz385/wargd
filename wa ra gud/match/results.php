<?php
// results.php - Logic for recording match results and updating standings

// The database connection is expected to be included by the file that calls this function.
// For standalone testing, you might include it like this:
// require_once __DIR__ . '/../config/database.php';

/**
 * Records the result of a match and updates the standings for both teams.
 *
 * This function handles the entire process of finalizing a match, from updating
 * its status and score to recalculating all relevant statistics in the standings table.
 * It uses a transaction to ensure data integrity.
 *
 * --- EXAMPLE USAGE ---
 *
 * $pdo = (new Database())->getConnection(); // Get PDO connection
 * $matchId = 101;
 * $homeScore = 3;
 * $awayScore = 1;
 *
 * // Use default points system (Win: 1, Draw: 0, Loss: 0)
 * $success = recordMatchResult($pdo, $matchId, $homeScore, $awayScore);
 *
 * // Use a custom points system (e.g., Win: 3, Draw: 1, Loss: 0)
 * $customPoints = ['win' => 3, 'draw' => 1, 'loss' => 0];
 * $success_custom = recordMatchResult($pdo, $matchId, $homeScore, $awayScore, $customPoints);
 *
 * if ($success) {
 *     echo "Match result recorded and standings updated successfully!";
 * } else {
 *     echo "Failed to record match result.";
 * }
 *
 * ---------------------
 *
 * @param PDO $pdo The database connection object.
 * @param int $matchId The ID of the match to update.
 * @param int $homeScore The final score of the home team.
 * @param int $awayScore The final score of the away team.
 * @param array $pointsConfig Optional. An array to configure points for [win, draw, loss].
 *                            Default: ['win' => 1, 'draw' => 0, 'loss' => 0] as per requirements.
 * @return bool True on success, false on failure.
 */
function recordMatchResult(PDO $pdo, int $matchId, int $homeScore, int $awayScore, array $pointsConfig = ['win' => 1, 'draw' => 0, 'loss' => 0]): bool
{
    // 1. Fetch match details (league_id, home_team_id, away_team_id)
    $stmt = $pdo->prepare("SELECT league_id, home_team_id, away_team_id FROM matches WHERE id = ? AND status != 'completed'");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        // Match not found or already completed
        return false;
    }

    $leagueId = (int)$match['league_id'];
    $homeTeamId = (int)$match['home_team_id'];
    $awayTeamId = (int)$match['away_team_id'];

    // Start a transaction to ensure all updates are atomic (all or nothing)
    $pdo->beginTransaction();

    try {
        // 2. Update the match score and status to 'completed'
        $updateMatchStmt = $pdo->prepare(
            "UPDATE matches SET home_score = ?, away_score = ?, status = 'completed' WHERE id = ?"
        );
        $updateMatchStmt->execute([$homeScore, $awayScore, $matchId]);

        // 3. Determine the result and prepare stat changes
        $homeResult = ['wins' => 0, 'losses' => 0, 'draws' => 0, 'points' => 0];
        $awayResult = ['wins' => 0, 'losses' => 0, 'draws' => 0, 'points' => 0];

        if ($homeScore > $awayScore) { // Home team wins
            $homeResult['wins'] = 1;
            $homeResult['points'] = $pointsConfig['win'];
            $awayResult['losses'] = 1;
            $awayResult['points'] = $pointsConfig['loss'];
        } elseif ($awayScore > $homeScore) { // Away team wins
            $awayResult['wins'] = 1;
            $awayResult['points'] = $pointsConfig['win'];
            $homeResult['losses'] = 1;
            $homeResult['points'] = $pointsConfig['loss'];
        } else { // It's a draw
            $homeResult['draws'] = 1;
            $homeResult['points'] = $pointsConfig['draw'];
            $awayResult['draws'] = 1;
            $awayResult['points'] = $pointsConfig['draw'];
        }

        // 4. SQL to update standings.
        // This query will create a new row if the team isn't in the standings for this league yet,
        // or update the existing row if they are. This is incredibly efficient.
        $updateStandingsSql = "
            INSERT INTO standings (league_id, team_id, matches_played, wins, losses, draws, points, score_for, score_against, score_difference)
            VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                matches_played = matches_played + 1,
                wins = wins + VALUES(wins),
                losses = losses + VALUES(losses),
                draws = draws + VALUES(draws),
                points = points + VALUES(points),
                score_for = score_for + VALUES(score_for),
                score_against = score_against + VALUES(score_against),
                score_difference = score_difference + VALUES(score_difference);
        ";

        $standingsStmt = $pdo->prepare($updateStandingsSql);

        // Update Home Team's standings
        $standingsStmt->execute([
            $leagueId,
            $homeTeamId,
            $homeResult['wins'],
            $homeResult['losses'],
            $homeResult['draws'],
            $homeResult['points'],
            $homeScore,
            $awayScore,
            ($homeScore - $awayScore) // Score difference for this match
        ]);

        // Update Away Team's standings
        $standingsStmt->execute([
            $leagueId,
            $awayTeamId,
            $awayResult['wins'],
            $awayResult['losses'],
            $awayResult['draws'],
            $awayResult['points'],
            $awayScore,
            $homeScore,
            ($awayScore - $homeScore) // Score difference for this match
        ]);

        // If all queries were successful, commit the changes to the database
        $pdo->commit();
        return true;

    } catch (Exception $e) {
        // If anything went wrong, roll back the transaction to prevent partial updates
        $pdo->rollBack();
        // For debugging, you can log the error: error_log($e->getMessage());
        return false;
    }
}
?>