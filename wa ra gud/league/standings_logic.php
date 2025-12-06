<?php
// wa ra gud/league/standings_logic.php

function getSortedStandings($pdo, $leagueId) {
    // Fetch all standings for the league
    $stmt = $pdo->prepare("
        SELECT
            s.*,
            t.name as team_name,
            t.description as team_description,
            u.first_name,
            u.last_name,
            (SELECT COUNT(*) FROM team_members WHERE team_id = t.id AND status = 'active') as member_count
        FROM standings s
        JOIN teams t ON s.team_id = t.id
        JOIN users u ON t.owner_id = u.id
        WHERE s.league_id = :league_id
    ");
    $stmt->execute([':league_id' => $leagueId]);
    $standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sort in PHP
    usort($standings, function($a, $b) use ($pdo, $leagueId) {
        // 1. Points
        if ($a['points'] !== $b['points']) {
            return $b['points'] - $a['points'];
        }

        // 2. Head-to-Head
        $h2h = getHeadToHead($pdo, $leagueId, $a['team_id'], $b['team_id']);
        if ($h2h !== 0) {
            return $h2h;
        }

        // 3. Score Difference
        if ($a['score_difference'] !== $b['score_difference']) {
            return $b['score_difference'] - $a['score_difference'];
        }

        // 4. Score For
        if ($a['score_for'] !== $b['score_for']) {
            return $b['score_for'] - $a['score_for'];
        }

        // 5. Name (Ascending)
        return strcmp($a['team_name'], $b['team_name']);
    });

    return $standings;
}

function getHeadToHead($pdo, $leagueId, $teamA, $teamB) {
    // Find matches between Team A and Team B
    $stmt = $pdo->prepare("
        SELECT home_team_id, away_team_id, home_score, away_score
        FROM matches
        WHERE league_id = ?
          AND status = 'completed'
          AND (
              (home_team_id = ? AND away_team_id = ?) OR
              (home_team_id = ? AND away_team_id = ?)
          )
    ");
    $stmt->execute([$leagueId, $teamA, $teamB, $teamB, $teamA]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($matches)) {
        return 0;
    }

    $pointsA = 0;
    $pointsB = 0;
    $diffA = 0;
    $diffB = 0;

    foreach ($matches as $m) {
        $scoreA = ($m['home_team_id'] == $teamA) ? $m['home_score'] : $m['away_score'];
        $scoreB = ($m['home_team_id'] == $teamA) ? $m['away_score'] : $m['home_score'];

        $diffA += ($scoreA - $scoreB);
        $diffB += ($scoreB - $scoreA);

        if ($scoreA > $scoreB) {
            $pointsA += 3;
        } elseif ($scoreB > $scoreA) {
            $pointsB += 3;
        } else {
            $pointsA += 1;
            $pointsB += 1;
        }
    }

    if ($pointsA !== $pointsB) {
        return $pointsB - $pointsA; // Higher points first
    }

    // H2H Goal Diff
    if ($diffA !== $diffB) {
        return $diffB - $diffA;
    }

    return 0;
}
?>
