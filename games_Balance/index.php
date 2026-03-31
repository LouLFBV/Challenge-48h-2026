<?php
session_start();
require_once '../config/database.php';

$order = $_GET['order'] ?? 'desc';
$order = in_array($order, ['asc', 'desc']) ? $order : 'desc';

// Récupère l'id fixe de Balance_Games
$balance_riddle_id = null;
try {
    $stmt_rid = $pdo->prepare("SELECT id FROM riddles WHERE title = 'Balance_Games' LIMIT 1");
    $stmt_rid->execute();
    $row = $stmt_rid->fetch();
    $balance_riddle_id = $row ? (int)$row['id'] : null;
} catch (PDOException $e) {}

// --- Scores Balance Master depuis riddles_balance ---
$players = [];
try {
    if (!$balance_riddle_id) {
        throw new Exception("Entrée Balance_Games introuvable dans riddles.");
    }

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.username,
            rb.points AS score
        FROM riddles_balance rb
        INNER JOIN users u ON u.id = rb.id_user
        WHERE rb.id_riddle = :riddle_id
        ORDER BY rb.points DESC, u.username ASC
    ");
    $stmt->bindParam(':riddle_id', $balance_riddle_id, PDO::PARAM_INT);
    $stmt->execute();
    $players_data = $stmt->fetchAll();

    foreach ($players_data as $index => $player) {
        $player['rank'] = $index + 1;
        $players[] = $player;
    }

    if ($order === 'asc') {
        $players = array_reverse($players);
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

function getMedal(int $rank): string {
    return [1 => '🥇', 2 => '🥈', 3 => '🥉'][$rank] ?? '●';
}

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
    <style>
        .bm-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #1e293b;
            border: 1px solid #a855f7;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 13px;
            color: #c4b5fd;
            margin-bottom: 12px;
        }
        .no-data-hint {
            color: #64748b;
            font-size: 13px;
            margin-top: 6px;
        }
    </style>
</head>

<body class="index-page">
    <div class="bg-particles">
        <?php for($i = 0; $i < 18; $i++): ?>
        <span class="particle" style="--i:<?= $i ?>"></span>
        <?php endfor; ?>
    </div>

    <div class="index-wrapper">
        <div class="index-actions">
            <a href="game.php" class="btn-play">
                <span class="btn-icon">▶</span>
                Rejouer maintenant
            </a>
        </div>

        <div class="container">
            <div class="header">
                <h1>🏆 Classement — Score Balance Master</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="controls">
                <div class="control-group">
                    <label>Ordre :</label>
                    <a href="?order=asc"
                       class="<?= $order === 'asc' ? 'active' : '' ?>"
                       style="padding: 8px 14px; border-radius: 6px; text-decoration: none; background: #1e293b; cursor: pointer; color: #cbd5f5;">
                        ↑ Croissant
                    </a>
                    <a href="?order=desc"
                       class="<?= $order === 'desc' ? 'active' : '' ?>"
                       style="padding: 8px 14px; border-radius: 6px; text-decoration: none; background: #1e293b; cursor: pointer; color: #cbd5f5;">
                        ↓ Décroissant
                    </a>
                
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Joueur</th>
                        <th>Meilleur score</th>
                        <th>Médaille</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($players)): ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                Aucun score enregistré.
                                <br><span class="no-data-hint">Joue une partie pour apparaître ici !</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($players as $player): ?>
                            <tr>
                                <td class="rank <?= $player['rank'] <= 3 ? 'top-' . $player['rank'] : '' ?>">
                                    #<?= $player['rank'] ?>
                                </td>
                                <td class="name"><?= htmlspecialchars($player['username']) ?></td>
                                <td class="score"><?= number_format($player['score'], 0, ',', ' ') ?> pts</td>
                                <td class="medal"><?= getMedal($player['rank']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php include '../includes/footer.php'; ?>