<?php
// results.php - Logic for recording match results and updating standings

/**
 * Records the result of a match and updates the standings for both teams.
 *
 * @param PDO $pdo The database connection object.
 * @param int $matchId The ID of the match to update.
 * @param int $homeScore The final score of the home team.
 * @param int $awayScore The final score of the away team.
 * @param array $pointsConfig Optional. An array to configure points for [win, draw, loss].
 * @param string $matchType The type of match ('regular' or 'playoff').
 * @return bool True on success, false on failure.
 */
function recordMatchResult(PDO $pdo, int $matchId, int $homeScore, int $awayScore, array $pointsConfig = ['win' => 1, 'draw' => 0, 'loss' => 0], string $matchType = 'regular'): bool
{
    $pdo->beginTransaction();

    try {
        if ($matchType === 'regular') {
            // Fetch regular match details
            $stmt = $pdo->prepare("SELECT league_id, home_team_id, away_team_id FROM matches WHERE id = ? AND status != 'completed'");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$match) return false;

            // Update match score and status
            $updateMatchStmt = $pdo->prepare("UPDATE matches SET home_score = ?, away_score = ?, status = 'completed' WHERE id = ?");
            $updateMatchStmt->execute([$homeScore, $awayScore, $matchId]);

            // Update standings for regular matches
            updateStandings($pdo, $match['league_id'], $match['home_team_id'], $match['away_team_id'], $homeScore, $awayScore, $pointsConfig);
        } else { // Playoff match
            // Fetch playoff match details
            $stmt = $pdo->prepare("SELECT * FROM playoff_matches WHERE id = ? AND status != 'completed'");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$match) return false;

            $winnerId = ($homeScore > $awayScore) ? $match['team1_id'] : $match['team2_id'];
            $loserId = ($winnerId == $match['team1_id']) ? $match['team2_id'] : $match['team1_id'];

            // Update playoff match score and status
            $updateMatchStmt = $pdo->prepare("UPDATE playoff_matches SET winner_id = ?, status = 'completed' WHERE id = ?");
            $updateMatchStmt->execute([$winnerId, $matchId]);

            // Advance the winner to the next round
            advanceWinner($pdo, $match, $winnerId);

            // If Double Elimination, advance the loser to the losers bracket
            if ($match['bracket_type'] === 'double_elimination') {
                advanceLoser($pdo, $match, $loserId);
            }
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Updates the standings for both teams in a regular season match.
 */
function updateStandings(PDO $pdo, int $leagueId, int $homeTeamId, int $awayTeamId, int $homeScore, int $awayScore, array $pointsConfig): void
{
    // Determine the result and prepare stat changes
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
        $leagueId, $homeTeamId, $homeResult['wins'], $homeResult['losses'], $homeResult['draws'], $homeResult['points'], $homeScore, $awayScore, ($homeScore - $awayScore)
    ]);

    // Update Away Team's standings
    $standingsStmt->execute([
        $leagueId, $awayTeamId, $awayResult['wins'], $awayResult['losses'], $awayResult['draws'], $awayResult['points'], $awayScore, $homeScore, ($awayScore - $homeScore)
    ]);
}

/**
 * Advances the winner of a playoff match to the next round.
 */
function advanceWinner(PDO $pdo, array $match, int $winnerId): void
{
    // For Double Elimination, winners stay in 'winners' bracket (unless it's the final)
    // For others, side is irrelevant or stays same.
    $bracketSide = $match['bracket_side'] ?? 'winners';
    $nextRound = $match['round'] + 1;
    $nextMatchNum = ceil($match['match_num'] / 2);

    // Special case for Double Elimination Final
    if ($match['bracket_type'] === 'double_elimination' && $bracketSide === 'losers') {
        // In losers bracket, logic is different.
        // Assuming losers bracket converges to a final winner who plays the winners bracket winner.
        // For simplicity, let's assume standard progression within the bracket side.
        // Complex DE logic would require pre-generated bracket structure or complex calculation.
        // We will stick to "Winner advances to next round in same bracket side" logic for now.
    }

    // Check if the next match already exists
    // We must check bracket_side for Double Elimination
    $query = "SELECT id, team1_id FROM playoff_matches WHERE league_id = ? AND round = ? AND match_num = ? AND bracket_type = ?";
    $params = [$match['league_id'], $nextRound, $nextMatchNum, $match['bracket_type']];

    if ($match['bracket_type'] === 'double_elimination') {
        $query .= " AND bracket_side = ?";
        $params[] = $bracketSide;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $nextMatch = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($nextMatch) {
        // If the match exists and team1 is not set, set it
        if (is_null($nextMatch['team1_id'])) {
            $updateStmt = $pdo->prepare("UPDATE playoff_matches SET team1_id = ? WHERE id = ?");
            $updateStmt->execute([$winnerId, $nextMatch['id']]);
        } else {
            $updateStmt = $pdo->prepare("UPDATE playoff_matches SET team2_id = ? WHERE id = ?");
            $updateStmt->execute([$winnerId, $nextMatch['id']]);
        }
    } else {
        // Create a new match for the next round
        $insertSql = "INSERT INTO playoff_matches (league_id, round, match_num, team1_id, bracket_type";
        $insertSql .= ($match['bracket_type'] === 'double_elimination') ? ", bracket_side) VALUES (?, ?, ?, ?, ?, ?)" : ") VALUES (?, ?, ?, ?, ?)";

        $insertParams = [$match['league_id'], $nextRound, $nextMatchNum, $winnerId, $match['bracket_type']];
        if ($match['bracket_type'] === 'double_elimination') {
            $insertParams[] = $bracketSide;
        }

        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute($insertParams);
    }
}

/**
 * Advances the loser of a playoff match to the losers bracket (for Double Elimination).
 */
function advanceLoser(PDO $pdo, array $match, int $loserId): void
{
    // Only applies if currently in Winners bracket
    if (($match['bracket_side'] ?? 'winners') !== 'winners') {
        // Loser in Losers bracket is eliminated.
        return;
    }

    $currentRound = $match['round'];
    // In standard DE, losers from R1 go to Losers R1. Losers from R2 go to Losers R2 (or R3 depending on structure).
    // Simplifying: Losers drop to the same round number in Losers bracket.
    // NOTE: This is a simplification. Real DE often has more rounds in Losers bracket.
    // e.g. 4 team DE:
    // WB R1 (2 matches) -> WB R2 (1 match) -> Final
    // LB R1 (1 match - losers of WB R1) -> LB R2 (Winner LB R1 vs Loser WB R2) -> Final vs WB Winner.

    // To implement fully correct DE without pre-generation is very hard.
    // We will attempt a simplified "Knockout drop" where they just enter the Losers bracket.

    // General Logic for Dropping to Losers Bracket
    // In a simplified "Double Chance" model for this system:
    // Losers from Winners Round R drop to a "Parallel" Losers Bracket Round R.
    // This effectively creates two separate brackets.
    // NOTE: This avoids collision issues present in standard interleaved Double Elim topology when not pre-generated.

    $targetRound = $match['round'];
    $targetMatchNum = ceil($match['match_num'] / 2);

    // Check for existing LB match
    $stmt = $pdo->prepare("SELECT id, team1_id, team2_id FROM playoff_matches WHERE league_id = ? AND round = ? AND match_num = ? AND bracket_type = 'double_elimination' AND bracket_side = 'losers'");
    $stmt->execute([$match['league_id'], $targetRound, $targetMatchNum]);
    $lbMatch = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lbMatch) {
         // Collision Check: If both slots are full, we shouldn't overwrite.
         // In a Parallel Bracket model, slots fill up exactly as they do in Winners bracket (2 losers per match).
         // So overwrite shouldn't happen unless logic is wrong or data is corrupt.
         // If for some reason it is full, we log/ignore to prevent data loss.

         if (is_null($lbMatch['team1_id'])) {
            $pdo->prepare("UPDATE playoff_matches SET team1_id = ? WHERE id = ?")->execute([$loserId, $lbMatch['id']]);
        } elseif (is_null($lbMatch['team2_id'])) {
            $pdo->prepare("UPDATE playoff_matches SET team2_id = ? WHERE id = ?")->execute([$loserId, $lbMatch['id']]);
        } else {
            // Match is full. This implies a collision or unexpected state.
            // In a strict parallel structure, this shouldn't happen if inputs are clean.
            // We will do nothing to preserve existing data.
        }
    } else {
         $stmt = $pdo->prepare("INSERT INTO playoff_matches (league_id, round, match_num, team1_id, bracket_type, bracket_side) VALUES (?, ?, ?, ?, 'double_elimination', 'losers')");
         $stmt->execute([$match['league_id'], $targetRound, $targetMatchNum, $loserId]);
    }
}
