<?php
// schedule.php - Round Robin Match Generation

/**
 * Generates a round robin schedule for a given list of teams.
 *
 * This function uses the "circle method" algorithm to create a schedule
 * where every team plays every other team exactly once. It handles both
 * even and odd numbers of teams by adding a "bye" if necessary.
 *
 * @param array $teams An array of team IDs.
 * @return array A multi-dimensional array representing the schedule.
 *               Each top-level element represents a round, and each
 *               sub-element represents a match in that round.
 *               Example:
 *               [
 *                   // Round 1
 *                   [
 *                       ['teamA' => 1, 'teamB' => 6],
 *                       ['teamA' => 2, 'teamB' => 5],
 *                       ['teamA' => 3, 'teamB' => 4],
 *                   ],
 *                   // Round 2
 *                   [
 *                       ['teamA' => 6, 'teamB' => 5],
 *                       // ... and so on
 *                   ]
 *               ]
 */
function generateRoundRobinSchedule(array $teams): array
{
    // If there's an odd number of teams, add a "bye" to make it even.
    if (count($teams) % 2 !== 0) {
        $teams[] = null; // Represents a bye
    }

    $numTeams = count($teams);
    $rounds = [];
    $numRounds = $numTeams - 1;
    $matchesPerRound = $numTeams / 2;

    // Create a copy of the teams array to manipulate
    $scheduleTeams = $teams;

    for ($round = 0; $round < $numRounds; $round++) {
        $currentRoundMatches = [];
        for ($i = 0; $i < $matchesPerRound; $i++) {
            $teamA = $scheduleTeams[$i];
            $teamB = $scheduleTeams[$numTeams - 1 - $i];

            // Only add the match if neither team is a "bye"
            if ($teamA !== null && $teamB !== null) {
                // To ensure fair home/away distribution, alternate the pairing order
                if ($i % 2 === 0) {
                    $currentRoundMatches[] = ['teamA' => $teamA, 'teamB' => $teamB];
                } else {
                    $currentRoundMatches[] = ['teamA' => $teamB, 'teamB' => $teamA];
                }
            }
        }
        $rounds[] = $currentRoundMatches;

        // Rotate the teams for the next round
        // The first team stays in place, the rest rotate
        $lastTeam = array_pop($scheduleTeams);
        array_splice($scheduleTeams, 1, 0, [$lastTeam]);
    }

    return $rounds;
}

?>