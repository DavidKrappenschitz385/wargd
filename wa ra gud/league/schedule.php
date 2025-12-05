<?php
// wa ra gud/league/schedule.php

/**
 * Generates a round-robin tournament schedule using the Circle Method algorithm.
 *
 * This function is the core of the automatic scheduling system. It takes a list
 * of team IDs and creates a fair, balanced schedule where every team plays every
 * other team exactly once. It's designed to handle both even and odd numbers of
 * teams gracefully by automatically adding a "bye" round if needed.
 *
 * --- HOW IT WORKS (THE CIRCLE METHOD) ---
 *
 * 1.  **Handle Odd Teams:** If there's an odd number of teams, a dummy team (a "bye")
 *     is added. This ensures the algorithm works with an even set.
 *
 * 2.  **Pin One Team:** One team is "pinned" to the top position and never moves.
 *     This anchors the entire schedule.
 *
 * 3.  **Rotate Everyone Else:** In each round, all other teams rotate one position
 *     clockwise around the pinned team.
 *
 * 4.  **Pair Up:** In every round, teams are paired vertically. The team at the top
 *     of a column plays the team at the bottom.
 *
 * This process guarantees that after N-1 rounds (where N is the number of teams),
 * every possible unique pairing has been created.
 *
 * --- EXAMPLE USAGE ---
 *
 * $teamIds = [1, 2, 3, 4, 5, 6]; // Team IDs from your database
 * $schedule = generateRoundRobinSchedule($teamIds);
 *
 * // The output ($schedule) will be an array of rounds, each containing match pairings.
 * // e.g., [['round' => 1, 'home' => 1, 'away' => 6], ['round' => 1, 'home' => 2, 'away' => 5], ...]
 *
 * ---------------------
 *
 * @param array $teams An array of unique team IDs.
 * @return array A multi-dimensional array representing the schedule.
 *               Returns an empty array if there are fewer than 2 teams.
 */
function generateRoundRobinSchedule(array $teams): array
{
    // At least two teams are required to make a schedule.
    if (count($teams) < 2) {
        return [];
    }

    // If the number of teams is odd, add a "bye" (represented by null).
    // This makes the scheduling algorithm much simpler.
    if (count($teams) % 2 !== 0) {
        $teams[] = null;
    }

    $teamCount = count($teams);
    $rounds = [];
    $matchesPerRound = $teamCount / 2;
    $totalRounds = $teamCount - 1;

    // Split teams into two halves for the rotation algorithm.
    $topRow = array_slice($teams, 0, $matchesPerRound);
    $bottomRow = array_reverse(array_slice($teams, $matchesPerRound));

    for ($roundNum = 1; $roundNum <= $totalRounds; $roundNum++) {
        $currentRoundMatches = [];
        for ($i = 0; $i < $matchesPerRound; $i++) {
            $homeTeam = $topRow[$i];
            $awayTeam = $bottomRow[$i];

            // If a pairing includes the "bye", it's not a real match, so we skip it.
            if ($homeTeam !== null && $awayTeam !== null) {
                // To balance home/away games, we can swap them on alternate rounds.
                if ($roundNum % 2 !== 0) {
                    $currentRoundMatches[] = ['home' => $homeTeam, 'away' => $awayTeam];
                } else {
                    $currentRoundMatches[] = ['home' => $awayTeam, 'away' => $homeTeam];
                }
            }
        }
        $rounds[$roundNum] = $currentRoundMatches;

        // Now, rotate the teams for the next round (the "Circle Method").
        // The first team in the top row is "pinned" and never moves.
        $pinnedTeam = array_shift($topRow);

        // The first team from the bottom row moves to the end of the top row.
        $topRow[] = array_shift($bottomRow);

        // The last team from the top row (before the new one was added) moves to the start of the bottom row.
        array_unshift($bottomRow, array_pop($topRow));

        // Put the pinned team back at the start of the top row.
        array_unshift($topRow, $pinnedTeam);
    }

    return $rounds;
}
