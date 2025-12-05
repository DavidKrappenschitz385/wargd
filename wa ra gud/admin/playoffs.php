<?php
// admin/playoffs.php

require_once 'header.php';

$pdo = (new Database())->connect();
$message = ''; // For feedback
$error = '';   // For errors

// Get all leagues to populate the dropdown
try {
    $leaguesStmt = $pdo->query("SELECT id, name FROM leagues WHERE status = 'completed' OR status = 'active' ORDER BY name");
    $leagues = $leaguesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching leagues: " . $e->getMessage();
    $leagues = [];
}

// --- FORM SUBMISSION LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_bracket'])) {
        $leagueId = (int)$_POST['league_id'];
        $bracketType = $_POST['bracket_type'];
        $numTeams = isset($_POST['num_teams']) ? (int)$_POST['num_teams'] : 8;

        if (empty($leagueId) || empty($bracketType)) {
            $error = "Please select a league and a bracket type.";
        } else {
            // Check if a bracket already exists for this league
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM playoff_matches WHERE league_id = ?");
            $checkStmt->execute([$leagueId]);
            if ($checkStmt->fetchColumn() > 0) {
                $error = "A playoff bracket has already been generated for this league.";
            } else {
                if ($bracketType === 'single_elimination' || $bracketType === 'double_elimination') {
                    $pdo->beginTransaction();
                    try {
                        // 1. Get the top N teams from the standings for the selected league
                        $standingsStmt = $pdo->prepare("
                            SELECT team_id
                            FROM standings
                            WHERE league_id = :league_id
                            ORDER BY points DESC, score_difference DESC
                            LIMIT :limit
                        ");
                        $standingsStmt->bindValue(':league_id', $leagueId, PDO::PARAM_INT);
                        $standingsStmt->bindValue(':limit', $numTeams, PDO::PARAM_INT);
                        $standingsStmt->execute();
                        $teamIds = $standingsStmt->fetchAll(PDO::FETCH_COLUMN);

                        if (count($teamIds) < $numTeams) {
                            throw new Exception("Not enough teams in the league to generate a bracket of this size.");
                        }

                        // 2. Generate the matchups (e.g., 1 vs 8, 2 vs 7, etc.)
                        $matchups = [];
                        $highSeed = 0;
                        $lowSeed = count($teamIds) - 1;

                        while ($highSeed < $lowSeed) {
                            $matchups[] = [$teamIds[$highSeed], $teamIds[$lowSeed]];
                            $highSeed++;
                            $lowSeed--;
                        }

                        // 3. Insert these matchups into the 'playoff_matches' table
                        $insertStmt = $pdo->prepare("
                            INSERT INTO playoff_matches (league_id, round, match_num, team1_id, team2_id, bracket_type)
                            VALUES (?, 1, ?, ?, ?, 'winners')
                        ");

                        foreach ($matchups as $i => $matchup) {
                            $insertStmt->execute([$leagueId, $i + 1, $matchup[0], $matchup[1]]);
                        }

                        $pdo->commit();
                        $message = "Successfully generated playoff bracket!";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = "An error occurred: " . $e->getMessage();
                    }
                }
            }
        }
    } elseif (isset($_POST['set_winner'])) {
        $matchId = (int)$_POST['match_id'];
        $winnerId = (int)$_POST['winner_id'];
        $leagueId = (int)$_GET['league_id'];

        $pdo->beginTransaction();
        try {
            // 1. Update the winner of the current match
            $updateStmt = $pdo->prepare("UPDATE playoff_matches SET winner_id = ?, status = 'completed' WHERE id = ?");
            $updateStmt->execute([$winnerId, $matchId]);

            // 2. Advance the winner and handle loser for double elimination
            $matchStmt = $pdo->prepare("SELECT * FROM playoff_matches WHERE id = ?");
            $matchStmt->execute([$matchId]);
            $match = $matchStmt->fetch(PDO::FETCH_ASSOC);

            if ($match['bracket_type'] === 'winners') {
                // Advance winner in winners' bracket
                $nextRound = $match['round'] + 1;
                $nextMatchNum = ceil($match['match_num'] / 2);

                $nextMatchStmt = $pdo->prepare("SELECT * FROM playoff_matches WHERE league_id = ? AND round = ? AND match_num = ? AND bracket_type = 'winners'");
                $nextMatchStmt->execute([$leagueId, $nextRound, $nextMatchNum]);
                $nextMatch = $nextMatchStmt->fetch(PDO::FETCH_ASSOC);

                if ($nextMatch) {
                    $updateNextStmt = $pdo->prepare("UPDATE playoff_matches SET " . ($match['match_num'] % 2 != 0 ? "team1_id" : "team2_id") . " = ? WHERE id = ?");
                    $updateNextStmt->execute([$winnerId, $nextMatch['id']]);
                } else {
                    $insertNextStmt = $pdo->prepare("INSERT INTO playoff_matches (league_id, round, match_num, " . ($match['match_num'] % 2 != 0 ? "team1_id" : "team2_id") . ", bracket_type) VALUES (?, ?, ?, ?, 'winners')");
                    $insertNextStmt->execute([$leagueId, $nextRound, $nextMatchNum, $winnerId]);
                }

                // Move loser to losers' bracket
                $loserId = ($winnerId == $match['team1_id']) ? $match['team2_id'] : $match['team1_id'];
                $loserRound = ($match['round'] * 2) - 1;
                $loserMatchNum = floor(($match['match_num']-1)/2) + 1;

                $nextLoserMatchStmt = $pdo->prepare("SELECT * FROM playoff_matches WHERE league_id = ? AND round = ? AND match_num = ? AND bracket_type = 'losers'");
                $nextLoserMatchStmt->execute([$leagueId, $loserRound, $loserMatchNum]);
                $nextLoserMatch = $nextLoserMatchStmt->fetch(PDO::FETCH_ASSOC);

                if ($nextLoserMatch) {
                    $updateLoserStmt = $pdo->prepare("UPDATE playoff_matches SET team2_id = ? WHERE id = ?");
                    $updateLoserStmt->execute([$loserId, $nextLoserMatch['id']]);
                } else {
                    $insertLoserStmt = $pdo->prepare("INSERT INTO playoff_matches (league_id, round, match_num, team1_id, bracket_type) VALUES (?, ?, ?, ?, 'losers')");
                    $insertLoserStmt->execute([$leagueId, $loserRound, $loserMatchNum, $loserId]);
                }
            } else { // Losers' bracket logic
                // Advance winner in losers' bracket
                $nextRound = $match['round'] + 1;
                // In losers' bracket, the match num logic is simpler
                $nextMatchNum = ceil($match['match_num'] / 2);

                $nextMatchStmt = $pdo->prepare("SELECT * FROM playoff_matches WHERE league_id = ? AND round = ? AND match_num = ? AND bracket_type = 'losers'");
                $nextMatchStmt->execute([$leagueId, $nextRound, $nextMatchNum]);
                $nextMatch = $nextMatchStmt->fetch(PDO::FETCH_ASSOC);

                if ($nextMatch) {
                    // Determine if the winner should be team1 or team2 in the next match
                    $updateSlot = ($match['match_num'] % 2 != 0) ? "team1_id" : "team2_id";
                    $updateNextStmt = $pdo->prepare("UPDATE playoff_matches SET {$updateSlot} = ? WHERE id = ?");
                    $updateNextStmt->execute([$winnerId, $nextMatch['id']]);
                } else {
                    // If the next match doesn't exist, create it
                    $insertSlot = ($match['match_num'] % 2 != 0) ? "team1_id" : "team2_id";
                    $insertNextStmt = $pdo->prepare("INSERT INTO playoff_matches (league_id, round, match_num, {$insertSlot}, bracket_type) VALUES (?, ?, ?, ?, 'losers')");
                    $insertNextStmt->execute([$leagueId, $nextRound, $nextMatchNum, $winnerId]);
                }
            }

            $pdo->commit();
            $message = "Winner updated and advanced to the next round!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}

// Fetch playoff matches for display
$playoff_matches = [];
if (isset($_GET['league_id'])) {
    $leagueId = (int)$_GET['league_id'];
    $playoffStmt = $pdo->prepare("
        SELECT
            pm.*,
            t1.name AS team1_name,
            t2.name AS team2_name,
            w.name AS winner_name
        FROM playoff_matches pm
        LEFT JOIN teams t1 ON pm.team1_id = t1.id
        LEFT JOIN teams t2 ON pm.team2_id = t2.id
        LEFT JOIN teams w ON pm.winner_id = w.id
        WHERE pm.league_id = ?
        ORDER BY pm.round, pm.match_num
    ");
    $playoffStmt->execute([$leagueId]);
    $playoff_matches = $playoffStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="card">
    <div class="card-header">
        <h2>Generate Playoff Bracket</h2>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="playoffs.php" method="post">
            <div class="form-group">
                <label for="league_id">Select League</label>
                <select name="league_id" id="league_id" class="form-control" required onchange="window.location.href='playoffs.php?league_id='+this.value">
                    <option value="">-- Choose a League --</option>
                    <?php foreach ($leagues as $league): ?>
                        <option value="<?= $league['id'] ?>" <?= (isset($_GET['league_id']) && $_GET['league_id'] == $league['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($league['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="bracket_type">Bracket Type</label>
                <select name="bracket_type" id="bracket_type" class="form-control" required>
                    <option value="single_elimination">Single Elimination</option>
                    <option value="double_elimination">Double Elimination</option>
                </select>
            </div>

            <div class="form-group">
                <label for="num_teams">Number of Teams</label>
                <select name="num_teams" id="num_teams" class="form-control" required>
                    <option value="4">4</option>
                    <option value="8" selected>8</option>
                    <option value="16">16</option>
                </select>
            </div>

            <button type="submit" name="generate_bracket" class="btn btn-primary">Generate Bracket</button>
        </form>

        <?php if (!empty($playoff_matches)): ?>
            <h3 class="mt-5">Winners' Bracket</h3>
            <div class="bracket">
                <?php
                $winners_rounds = [];
                foreach ($playoff_matches as $match) {
                    if ($match['bracket_type'] === 'winners') {
                        $winners_rounds[$match['round']][] = $match;
                    }
                }
                ?>
                <?php foreach ($winners_rounds as $round_num => $matches): ?>
                    <div class="round">
                        <h4>Round <?= $round_num ?></h4>
                        <?php foreach ($matches as $match): ?>
                            <div class="match">
                                <p>
                                    <strong><?= htmlspecialchars($match['team1_name'] ?? 'TBD') ?></strong>
                                    vs
                                    <strong><?= htmlspecialchars($match['team2_name'] ?? 'TBD') ?></strong>
                                </p>
                                <?php if ($match['status'] === 'scheduled' && $match['team1_id'] && $match['team2_id']): ?>
                                    <form action="playoffs.php?league_id=<?= $leagueId ?>" method="post">
                                        <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                        <select name="winner_id" class="form-control">
                                            <option value="<?= $match['team1_id'] ?>"><?= htmlspecialchars($match['team1_name']) ?></option>
                                            <option value="<?= $match['team2_id'] ?>"><?= htmlspecialchars($match['team2_name']) ?></option>
                                        </select>
                                        <button type="submit" name="set_winner" class="btn btn-sm btn-success mt-2">Set Winner</button>
                                    </form>
                                <?php else: ?>
                                    <p>Winner: <?= htmlspecialchars($match['winner_name']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <h3 class="mt-5">Losers' Bracket</h3>
            <div class="bracket">
                <?php
                $losers_rounds = [];
                foreach ($playoff_matches as $match) {
                    if ($match['bracket_type'] === 'losers') {
                        $losers_rounds[$match['round']][] = $match;
                    }
                }
                ?>
                <?php foreach ($losers_rounds as $round_num => $matches): ?>
                    <div class="round">
                        <h4>Round <?= $round_num ?></h4>
                        <?php foreach ($matches as $match): ?>
                            <div class="match">
                                <p>
                                    <strong><?= htmlspecialchars($match['team1_name'] ?? 'TBD') ?></strong>
                                    vs
                                    <strong><?= htmlspecialchars($match['team2_name'] ?? 'TBD') ?></strong>
                                </p>
                                <?php if ($match['status'] === 'scheduled' && $match['team1_id'] && $match['team2_id']): ?>
                                    <form action="playoffs.php?league_id=<?= $leagueId ?>" method="post">
                                        <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                        <select name="winner_id" class="form-control">
                                            <option value="<?= $match['team1_id'] ?>"><?= htmlspecialchars($match['team1_name']) ?></option>
                                            <option value="<?= $match['team2_id'] ?>"><?= htmlspecialchars($match['team2_name']) ?></option>
                                        </select>
                                        <button type="submit" name="set_winner" class="btn btn-sm btn-success mt-2">Set Winner</button>
                                    </form>
                                <?php else: ?>
                                    <p>Winner: <?= htmlspecialchars($match['winner_name']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
