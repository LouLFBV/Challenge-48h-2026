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
            u.name, 
            COALESCE(SUM(uspr.obtained_score), 0) as score
        FROM users u
        LEFT JOIN user_scores_per_riddle uspr ON u.id = uspr.user_id
        GROUP BY u.id, u.name
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

include '../includes/header.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏆 Classement des joueurs</title>
    
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
                        <td class="name"><?= htmlspecialchars($player['name']) ?></td>
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

<?php
include '../includes/footer.php';
?>