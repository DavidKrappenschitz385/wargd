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
            $loserId = ($homeScore > $awayScore) ? $match['team2_id'] : $match['team1_id'];

            // Update playoff match score and status
            $updateMatchStmt = $pdo->prepare("UPDATE playoff_matches SET winner_id = ?, status = 'completed' WHERE id = ?");
            $updateMatchStmt->execute([$winnerId, $matchId]);

            // Advance the winner and loser
            if ($match['bracket_type'] === 'double_elimination') {
                advanceDoubleElimination($pdo, $match, $winnerId, $loserId);
            } else {
                advanceWinner($pdo, $match, $winnerId);
            }
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in recordMatchResult: " . $e->getMessage());
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
 * Advances the winner of a single elimination or knockout match.
 */
function advanceWinner(PDO $pdo, array $match, int $winnerId): void
{
    $nextRound = $match['round'] + 1;
    $nextMatchNum = ceil($match['match_num'] / 2);

    // Check if the next match already exists
    $stmt = $pdo->prepare("SELECT id, team1_id FROM playoff_matches WHERE league_id = ? AND round = ? AND match_num = ? AND bracket_type = ?");
    $stmt->execute([$match['league_id'], $nextRound, $nextMatchNum, $match['bracket_type']]);
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
        $insertStmt = $pdo->prepare("INSERT INTO playoff_matches (league_id, round, match_num, team1_id, bracket_type) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute([$match['league_id'], $nextRound, $nextMatchNum, $winnerId, $match['bracket_type']]);
    }
}

/**
 * Handles Double Elimination progression.
 */
function advanceDoubleElimination(PDO $pdo, array $match, int $winnerId, int $loserId): void
{
    $round = $match['round'];
    $matchNum = $match['match_num'];
    $bracketSide = $match['bracket_side'] ?? 'winners';

    // 1. Advance Winner
    if ($bracketSide === 'winners') {
        // Winner stays in Winners Bracket
        $nextRound = $round + 1;
        $nextMatchNum = ceil($matchNum / 2);
        placeTeamInMatch($pdo, $match['league_id'], $nextRound, $nextMatchNum, $match['bracket_type'], 'winners', $winnerId);

        // 2. Drop Loser to Losers Bracket
        // Logic: WB Round R Loser -> LB Round (R-1)*2 + 1 if R=1 else (R-1)*2?
        // LB R1 takes WB R1 losers.
        // LB R2 takes LB R1 winners + WB R2 losers.
        // LB R3 takes LB R2 winners.
        // LB R4 takes LB R3 winners + WB R3 losers.

        $lbRound = ($round == 1) ? 1 : ($round - 1) * 2;

        // Match mapping for dropping to LB is tricky.
        // WB R1 M1 Loser -> LB R1 M1?
        // If WB has 8 matches (16 teams), LB R1 has 4 matches.
        // WB M1, M2 -> LB M1.

        $lbMatchNum = ceil($matchNum / 2);

        // However, for rounds where LB takes WB losers directly (LB R2, LB R4...),
        // the LB match count equals the WB match count of that round.
        // WB R2 (4 matches) -> LB R2 (4 matches).
        // WB R2 M1 Loser -> LB R2 M1.

        if ($round > 1) {
            $lbMatchNum = $matchNum;
        }

        placeTeamInMatch($pdo, $match['league_id'], $lbRound, $lbMatchNum, $match['bracket_type'], 'losers', $loserId);

    } elseif ($bracketSide === 'losers') {
        // Winner advances in Losers Bracket
        $nextRound = $round + 1;

        // LB R1 -> LB R2 (Merge pairs? No, LB R2 takes WB losers too)
        // LB R1 (4 matches) -> 4 winners. LB R2 needs 8 teams. 4 from LB R1, 4 from WB R2.
        // So LB R1 M1 Winner -> LB R2 M1.

        // LB R2 -> LB R3 (4 matches -> 2 matches).
        // LB R2 M1, M2 -> LB R3 M1.

        // Pattern:
        // Odd LB Rounds (1, 3, 5...): Winners go to Next Round, Match Num = Match Num.
        // Even LB Rounds (2, 4, 6...): Winners go to Next Round, Match Num = Ceil(Match Num / 2).

        if ($round % 2 != 0) {
            $nextMatchNum = $matchNum;
        } else {
            $nextMatchNum = ceil($matchNum / 2);
        }

        placeTeamInMatch($pdo, $match['league_id'], $nextRound, $nextMatchNum, $match['bracket_type'], 'losers', $winnerId);

        // Loser is eliminated.
    } elseif ($bracketSide === 'final') {
        // Handle Final Match logic if needed (e.g. if winner is from LB, force another match?)
        // For now, winner is champion.
    }
}

/**
 * Helper to place a team in a specific match slot.
 */
function placeTeamInMatch(PDO $pdo, int $leagueId, int $round, int $matchNum, string $bracketType, string $bracketSide, int $teamId): void
{
    // Check if match exists
    $stmt = $pdo->prepare("SELECT id, team1_id, team2_id FROM playoff_matches WHERE league_id = ? AND round = ? AND match_num = ? AND bracket_type = ? AND bracket_side = ?");
    $stmt->execute([$leagueId, $round, $matchNum, $bracketType, $bracketSide]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($match) {
        if (is_null($match['team1_id'])) {
            $updateSql = "UPDATE playoff_matches SET team1_id = ? WHERE id = ?";
        } elseif (is_null($match['team2_id'])) {
            $updateSql = "UPDATE playoff_matches SET team2_id = ? WHERE id = ?";
        } else {
            // Match is full? Should not happen in standard bracket logic unless bug.
            return;
        }
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$teamId, $match['id']]);
    } else {
        // Create match
        $insertStmt = $pdo->prepare("INSERT INTO playoff_matches (league_id, round, match_num, team1_id, bracket_type, bracket_side) VALUES (?, ?, ?, ?, ?, ?)");
        $insertStmt->execute([$leagueId, $round, $matchNum, $teamId, $bracketType, $bracketSide]);
    }
}
