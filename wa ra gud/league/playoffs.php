<?php
// wa ra gud/league/playoffs.php

/**
 * Generates a playoff bracket for a league based on the final standings.
 *
 * @param PDO $pdo The database connection.
 * @param int $leagueId The ID of the league.
 * @param string $bracketType The type of bracket to generate ('single_elimination', 'double_elimination', 'knockout').
 * @return array The generated playoff matches.
 */
function generatePlayoffBrackets(PDO $pdo, int $leagueId, string $bracketType): array
{
    // 1. Get the teams from the standings table, ordered by their rank.
    $stmt = $pdo->prepare(
        "SELECT team_id FROM standings WHERE league_id = :league_id ORDER BY points DESC, score_difference DESC"
    );
    $stmt->execute([':league_id' => $leagueId]);
    $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Generate the matches based on the bracket type.
    $matches = [];
    switch ($bracketType) {
        case 'single_elimination':
            $matches = generateSingleElimination($teams);
            break;
        case 'double_elimination':
            $matches = generateDoubleElimination($teams);
            break;
        case 'knockout':
            $matches = generateSingleElimination($teams);
            break;
    }

    return $matches;
}

/**
 * Generates a single-elimination bracket.
 *
 * @param array $teams The teams in the bracket, ordered by rank.
 * @return array The generated matches.
 */
function generateSingleElimination(array $teams): array
{
    $matches = [];
    $numTeams = count($teams);

    // 3. Seed the teams and create the first round of matches.
    for ($i = 0; $i < $numTeams / 2; $i++) {
        $matches[] = [
            'team1' => $teams[$i],
            'team2' => $teams[$numTeams - 1 - $i],
        ];
    }

    return $matches;
}

/**
 * Generates a double-elimination bracket.
 *
 * @param array $teams The teams in the bracket, ordered by rank.
 * @return array The generated matches.
 */
function generateDoubleElimination(array $teams): array
{
    // A double-elimination bracket is just a single-elimination bracket
    // to start, so we can reuse the same logic.
    // The winners bracket starts exactly like Single Elimination.
    // We assume the caller will handle inserting with bracket_side='winners'.
    // The Losers bracket is empty initially.

    // However, if we want to be explicit, we could return a structure that indicates side.
    // But existing caller in admin/generate_playoffs.php expects simple team1/team2 array.
    // We will stick to generating the first round of Winners bracket here.

    return generateSingleElimination($teams);
}
