<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challenge 48h</title>
    <link rel="stylesheet" href="/Challenge-48h-2026/public/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <a href="/Challenge-48h-2026/layout/index.php" class="brand">🎮 Challenge 48h</a>
    </div>

    <div class="navbar-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="nav-user">👋 <?= htmlspecialchars($_SESSION['name']) ?></span>
            <a href="/Challenge-48h-2026/layout/profile.php" class="nav-btn nav-profile">Profil</a>
            <a href="/Challenge-48h-2026/auth/logout.php" class="nav-btn nav-logout">Logout</a>
        <?php else: ?>
            <a href="/Challenge-48h-2026/auth/login.php" class="nav-btn nav-login">Login</a>
            <a href="/Challenge-48h-2026/auth/register.php" class="nav-btn nav-register">Register</a>
        <?php endif; ?>
    </div>
</nav>