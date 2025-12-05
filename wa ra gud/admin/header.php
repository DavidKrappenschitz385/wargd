<?php
// admin/header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Sports League</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header {  background: linear-gradient(black, maroon); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .nav { display: flex; gap: 2rem; }
        .nav a { color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; }
         .nav a:hover { background: red;  transition: background-color 0.7s ease;  }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .dropdown-menu { background-color: #343a40; }
        .dropdown-item { color: white; }
        .dropdown-item:hover { background-color: #495057; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sports League System - Labangon </h1>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_leagues.php">Manage Leagues</a>
            <a href="manage_users.php">Manage Users</a>
            <div class="dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Matches
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="generate_matches.php">Generate Round Robin</a>
                    <a class="dropdown-item" href="generate_playoffs.php">Generate Playoffs</a>
                </div>
            </div>
            <a href="system_reports.php">Reports</a>
            <a href="../profile.php">Profile</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>
    <div class="container">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
