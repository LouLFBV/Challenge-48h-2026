<?php
session_start();
require '../config/database.php';

// --- Récupération de l'ordre ---
$order = $_GET['order'] ?? 'desc'; // 'asc' ou 'desc'

// Sécurisation du paramètre
$order = in_array($order, ['asc', 'desc']) ? $order : 'desc';

// --- Récupération des joueurs depuis la base de données ---
try {
    $orderClause = ($order === 'asc') ? 'ASC' : 'DESC';
    
    // Requête avec JOIN : récupère les users et somme leurs scores des énigmes
    $stmt = $pdo->prepare("
        SELECT 
            u.id, 
            u.username, 
            COALESCE(SUM(uspr.obtained_score), 0) as score
        FROM users u
        LEFT JOIN user_scores_per_riddle uspr ON u.id = uspr.user_id
        GROUP BY u.id, u.username
        ORDER BY score " . $orderClause
    );
    $stmt->execute();
    $players_data = $stmt->fetchAll();
    
    // Ajouter le rang à chaque joueur
    $players = [];
    foreach ($players_data as $index => $player) {
        $player['rank'] = $index + 1;
        $players[] = $player;
    }
} catch (PDOException $e) {
    $players = [];
    $error = "Erreur lors de la récupération des joueurs : " . $e->getMessage();
}

// Fonction pour obtenir la médaille
function getMedal($rank) {
    $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
    return $medals[$rank] ?? '●';
}

// Fonction pour obtenir la couleur par position
function getRankColor($rank) {
    if ($rank === 1) return '#FFD700';
    if ($rank === 2) return '#C0C0C0';
    if ($rank === 3) return '#CD7F32';
    return '#f0f0f0';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏆 Classement des joueurs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            padding: 30px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 2.2em;
            color: #333;
            margin-bottom: 15px;
        }

        .controls {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .control-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .control-group label {
            font-size: 1em;
            color: #666;
            font-weight: bold;
        }

        a {
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1em;
            border: 2px solid #ddd;
            background: white;
            color: #333;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        a.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        thead {
            background: #333;
            color: white;
        }

        th {
            padding: 18px;
            text-align: left;
            font-weight: bold;
            font-size: 1.1em;
        }

        th:nth-child(1) { width: 12%; text-align: center; }
        th:nth-child(2) { width: 50%; }
        th:nth-child(3) { width: 20%; text-align: right; }
        th:nth-child(4) { width: 18%; text-align: center; }

        tbody tr {
            border-bottom: 1px solid #ddd;
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        td {
            padding: 18px;
            font-size: 1.05em;
            color: #333;
        }

        .rank {
            text-align: center;
            font-weight: bold;
            font-size: 1.3em;
        }

        .rank.top-1 {
            color: #FFD700;
            font-size: 1.5em;
        }

        .rank.top-2 {
            color: #C0C0C0;
            font-size: 1.5em;
        }

        .rank.top-3 {
            color: #CD7F32;
            font-size: 1.5em;
        }

        .name {
            font-weight: 600;
            color: #333;
        }

        .score {
            text-align: right;
            padding-right: 30px;
            font-weight: bold;
            color: #667eea;
            font-size: 1.2em;
        }

        .medal {
            text-align: center;
            font-size: 1.8em;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            font-size: 1.1em;
            color: #999;
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }

        @media (max-width: 600px) {
            .header h1 {
                font-size: 1.6em;
            }

            .controls {
                flex-direction: column;
                gap: 15px;
            }

            .control-group {
                flex-direction: column;
                gap: 10px;
            }

            table {
                font-size: 0.95em;
            }

            th, td {
                padding: 12px;
            }

            th {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🏆 Classement des Joueurs</h1>
    </div>

    <?php if (isset($error)): ?>
        <div class="error">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="controls">
        <div class="control-group">
            <label>Ordre :</label>
            <a href="?order=desc" class="<?= $order === 'desc' ? 'active' : '' ?>">↓ Décroissant</a>
            <a href="?order=asc" class="<?= $order === 'asc' ? 'active' : '' ?>">↑ Croissant</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Rang</th>
                <th>Nom</th>
                <th>Score</th>
                <th>Médaille</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($players)): ?>
                <tr>
                    <td colspan="4" class="empty-state">Aucun joueur trouvé</td>
                </tr>
            <?php else: ?>
                <?php foreach ($players as $player): ?>
                    <tr>
                        <td class="rank <?= $player['rank'] <= 3 ? 'top-' . $player['rank'] : '' ?>">
                            #<?= $player['rank'] ?>
                        </td>
                        <td class="name"><?= htmlspecialchars($player['username']) ?></td>
                        <td class="score"><?= number_format($player['score'], 0, ',', ' ') ?></td>
                        <td class="medal"><?= getMedal($player['rank']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>