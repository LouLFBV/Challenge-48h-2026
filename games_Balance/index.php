<?php
session_start();
require_once '../config/database.php'; 

// Récupérer le classement
$stmt = $pdo->query("
    SELECT u.username, u.profile_image, u.total_score,
           COUNT(usp.id) AS riddles_solved
    FROM users u
    LEFT JOIN user_scores_per_riddle usp ON u.id = usp.user_id
    GROUP BY u.id
    ORDER BY u.total_score DESC
    LIMIT 20
");
$leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats globales
$statsStmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $statsStmt->fetch()['total'];

$solvedStmt = $pdo->query("SELECT COUNT(*) AS total FROM user_scores_per_riddle");
$totalSolved = $solvedStmt->fetch()['total'];

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Master — Classement</title>
    <link rel="stylesheet" href="games.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Crimson+Pro:ital,wght@0,300;0,400;1,300&display=swap" rel="stylesheet">
</head>


<body class="index-page">

    <!-- Particules décoratives -->
    <div class="bg-particles">
        <?php for($i=0; $i<18; $i++): ?>
        <span class="particle" style="--i:<?=$i?>"></span>
        <?php endfor; ?>
    </div>

    <div class="index-wrapper">       

        <!-- Actions -->
        <div class="index-actions">
            <a href="game.php" class="btn-play">
                <span class="btn-icon">▶</span>
                Rejouer maintenant
            </a>
            
        </div>

        <!-- Leaderboard -->
        <section class="leaderboard">
            <div class="leaderboard-header">
                <h2>Classement général</h2>
                <span class="lb-badge">TOP 20</span>
            </div>

            <div class="lb-table">
                <!-- Header row -->
                <div class="lb-row lb-head">
                    <div class="lb-col lb-rank">#</div>
                    <div class="lb-col lb-player">Joueur</div>
                    <div class="lb-col lb-solved">Résolus</div>
                    <div class="lb-col lb-score">Score</div>
                </div>

                <?php if (empty($leaderboard)): ?>
                <div class="lb-empty">
                    <span>⚖</span>
                    <p>Aucun joueur encore. Soyez le premier !</p>
                </div>
                <?php else: ?>
                <?php foreach ($leaderboard as $i => $row): ?>
                <div class="lb-row <?= $i < 3 ? 'lb-top lb-top-'.($i+1) : '' ?>" 
                     style="--delay: <?= $i * 0.05 ?>s">
                    <div class="lb-col lb-rank">
                        <?php if ($i === 0): ?>🥇
                        <?php elseif ($i === 1): ?>🥈
                        <?php elseif ($i === 2): ?>🥉
                        <?php else: ?><span><?= $i+1 ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="lb-col lb-player">
                        <div class="player-avatar">
                            <?= strtoupper(substr($row['username'], 0, 1)) ?>
                        </div>
                        <span class="player-name"><?= htmlspecialchars($row['username']) ?></span>
                        <?php if (isset($_SESSION['username']) && $_SESSION['username'] === $row['username']): ?>
                        <span class="you-badge">Vous</span>
                        <?php endif; ?>
                    </div>
                    <div class="lb-col lb-solved">
                        <span class="solved-count"><?= $row['riddles_solved'] ?></span>
                    </div>
                    <div class="lb-col lb-score">
                        <span class="score-value"><?= number_format($row['total_score']) ?></span>
                        <span class="score-unit">pts</span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>