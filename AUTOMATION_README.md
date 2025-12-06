# Automation Logic Documentation

This document explains the automated features implemented for the Sports League Management System.

## 1. Admin-Triggered Automation

All automation is triggered by the **Admin** recording match scores. This ensures that the system state advances only when authoritative data is entered.

**Trigger Point:** `admin/record_scores.php` -> calls `recordMatchResult()` in `match/results.php`.

### What happens when a score is recorded?
1.  **Match Status Update:** The match is marked as 'completed' and scores are saved.
2.  **Standings Update (Regular Season):**
    -   Points are awarded (Win: 3, Draw: 1, Loss: 0).
    -   Wins/Losses/Draws counters are incremented.
    -   Score For/Against/Difference are updated.
3.  **Bracket Progression (Playoffs):**
    -   **Single Elimination:** Winner advances to the next round.
    -   **Double Elimination:**
        -   Winner advances in the **Winners Bracket**.
        -   Loser drops to the **Losers Bracket**.

## 2. Playoff Brackets

The system supports automatic generation of playoff brackets based on regular season standings.

**Supported Formats:**
-   **Single Elimination:** Standard knockout.
-   **Double Elimination:** Winners and Losers brackets.
-   **Knockout:** (Same as Single Elimination).

**Generation Logic:** `league/playoffs.php`
-   Teams are seeded based on their final standings (Rank 1 vs Rank N, etc.).
-   Double Elimination brackets distinguish between 'winners' and 'losers' sides in the `playoff_matches` table.

## 3. Standings System

**Ranking Criteria:**
The standings are automatically sorted based on the following hierarchy:
1.  **Points** (Highest first)
2.  **Head-to-Head** (Results between tied teams)
3.  **Score Difference** (Higher is better)
4.  **Score For** (Higher is better)
5.  **Team Name** (Alphabetical)

**Implementation:** `league/view_league.php` uses PHP-based sorting to handle complex Head-to-Head logic dynamically.

## 4. Round Robin Generator

The system includes a generator for Round Robin schedules using the Circle Method.
-   Handles odd numbers of teams by adding a "BYE".
-   Rotates teams to ensure every team plays every other team once.

## Database Changes
-   Added `bracket_side` column to `playoff_matches` to support Double Elimination topology.
