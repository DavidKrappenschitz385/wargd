# Round Robin Standings & Scheduling System

This document explains the new Round Robin scheduling and automated standings features for the Sports League Management System.

## 🔥 1. Automated Standings System

We've built a powerful system that automatically updates the league standings every time an admin records a match result. Here's a breakdown of how it works.

### Standings Data Structure

The standings for each team are stored in the `standings` table, which has the following structure:

```json
{
  "id": 1,
  "league_id": 1,
  "team_id": 1,
  "wins": 0,
  "losses": 0,
  "draws": 0,
  "matches_played": 0,
  "points": 0,
  "score_for": 0,
  "score_against": 0,
  "score_difference": 0,
  "created_at": "2023-10-27 10:00:00",
  "updated_at": "2023-10-27 10:00:00"
}
```

### How Standings are Automatically Updated

When an admin records a match result, the `recordMatchResult()` function in `wa ra gud/match/results.php` handles everything.

**What the Admin Does:**

1.  Goes to the "Record Result" page for a specific match.
2.  Enters the final score for Team A and Team B.
3.  Clicks "Save Result."

**What the System Does Automatically:**

1.  **Determines the Winner:** It compares the scores to see who won, lost, or if it was a draw.
2.  **Updates Both Teams:** It updates the `standings` table for both the home and away teams.
3.  **Increments Stats:** It automatically increments all the necessary fields:
    *   `matches_played`
    *   `wins`, `losses`, or `draws`
    *   `points` (based on your custom point system)
    *   `score_for`, `score_against`, and `score_difference`
4.  **Marks Match as Completed:** The match's status is set to `completed`.
5.  **Recalculates Standings Order:** The `view_league.php` page will automatically display the teams in the correct order:
    *   Highest `points` first.
    *   If points are tied, highest `score_difference` wins.

### How to Use `recordMatchResult()`

Here's how you can use the function in your PHP code.

```php
<?php
require_once 'config/database.php';
require_once 'match/results.php';

// 1. Get your database connection
$pdo = (new Database())->getConnection();

// 2. Define the match details
$matchId = 101;
$homeScore = 3;
$awayScore = 1;

// 3. (Optional) Define a custom points system
// If you don't provide this, it defaults to Win: 1, Draw: 0, Loss: 0
$pointsConfig = [
    'win'  => 3,
    'draw' => 1,
    'loss' => 0,
];

// 4. Call the function
$success = recordMatchResult($pdo, $matchId, $homeScore, $awayScore, $pointsConfig);

if ($success) {
    echo "Standings updated successfully! 🔥";
} else {
    echo "Something went wrong.";
}
?>
```

## 🔥 2. Round Robin Match Generation

You can now automatically generate a complete Round Robin schedule for any league.

### How it Works

The `generateRoundRobinSchedule()` function in `wa ra gud/league/schedule.php` uses the **Circle Method algorithm** to create a fair schedule where every team plays every other team exactly once.

If you have an odd number of teams, it automatically adds a "bye" (a rest week) for one team in each round.

### How to Use `generateRoundRobinSchedule()`

Here's an example of how to generate a schedule and get a clean list of matches.

```php
<?php
require_once 'league/schedule.php';

// 1. Get a list of team IDs in your league
$teamIds = [1, 2, 3, 4, 5, 6]; // Example with 6 teams

// 2. Generate the schedule
$schedule = generateRoundRobinSchedule($teamIds);

// 3. The output is a clean array of rounds and matches
// You can now loop through it and save the matches to your database.

$match_id = 1;
foreach ($schedule as $roundNumber => $matches) {
    echo "--- Round " . ($roundNumber + 1) . " ---<br>";
    foreach ($matches as $match) {
        echo "Match " . $match_id++ . ": ";
        echo "Team " . $match['teamA'] . " vs. Team " . $match['teamB'] . "<br>";

        // Here, you would write the code to INSERT this match
        // into your `matches` table in the database.
    }
    echo "<br>";
}

/*
EXPECTED OUTPUT:
--- Round 1 ---
Match 1: Team 1 vs. Team 6
Match 2: Team 5 vs. Team 2
Match 3: Team 3 vs. Team 4

--- Round 2 ---
Match 4: Team 6 vs. Team 5
Match 5: Team 4 vs. Team 1
Match 6: Team 2 vs. Team 3

... and so on for all 5 rounds.
*/
?>
```

### Match Output Format

The function returns a list of matches, each with a consistent format:

```json
{
  "match_id": 1,
  "teamA": 101,
  "teamB": 102,
  "round_number": 1
}
```

This makes it incredibly easy to take the output and save it directly to your `matches` table.
