<?php
session_start();
require '../config/database.php';

$page = 'classement';

include '../includes/header.php';

// --- Récupération de l'ordre ---
$order = $_GET['order'] ?? 'desc'; // 'asc' ou 'desc'

// Sécurisation du paramètre
$order = in_array($order, ['asc', 'desc']) ? $order : 'desc';

// --- Récupération du filtre énigme ---
$riddle_filter = $_GET['riddle'] ?? null;
$riddle_filter = $riddle_filter ? (int)$riddle_filter : null;

// --- Récupération des énigmes disponibles ---
$riddles_available = [];
try {
    $stmt_riddles = $pdo->prepare("SELECT DISTINCT r.id, r.title FROM riddles r ORDER BY r.id ASC");
    $stmt_riddles->execute();
    $riddles_available = $stmt_riddles->fetchAll();
} catch (PDOException $e) {
    $riddles_available = [];
}

// --- Récupération des joueurs depuis la base de données ---
try {
    if ($riddle_filter) {
        // Requête pour récupérer les scores par énigme spécifique
        $stmt = $pdo->prepare("
            SELECT 
                u.id, 
                u.username, 
                COALESCE(uspr.obtained_score, 0) as score
            FROM users u
            LEFT JOIN user_scores_per_riddle uspr ON u.id = uspr.user_id AND uspr.riddle_id = :riddle_id
            ORDER BY score DESC, u.username ASC
            LIMIT 10
        ");
        $stmt->bindParam(':riddle_id', $riddle_filter, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        // Requête pour récupérer les users et leurs scores totaux
        $stmt = $pdo->prepare("
            SELECT 
                u.id, 
                u.username, 
                u.total_score as score
            FROM users u
            ORDER BY u.total_score DESC, u.username ASC
            LIMIT 10
        ");
        $stmt->execute();
    }
    
    $players_data = $stmt->fetchAll();
    
    // Ajouter le rang à chaque joueur (toujours par score desc)
    $players = [];
    foreach ($players_data as $index => $player) {
        $player['rank'] = $index + 1;
        $players[] = $player;
    }
    
    // Inverser l'ordre d'affichage si croissant
    if ($order === 'asc') {
        $players = array_reverse($players);
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
    
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🏆 Classement des Joueurs<?= $riddle_filter ? ' - Niveau ' . htmlspecialchars($riddle_filter) : ' - Score Total' ?></h1>
    </div>

    <?php if (isset($error)): ?>
        <div class="error">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="controls">
        <div class="control-group">
            <label> Filtrer par niveau :</label>
            <select onchange="window.location = (this.value ? '?riddle=' + this.value + '&order=<?= $order ?>' : '?order=<?= $order ?>')" style="padding: 8px 14px; border-radius: 6px; background: #1e293b; color: #cbd5f5; border: 1px solid #a855f7; cursor: pointer; font-size: 14px;">
                <option value="">Score Total</option>
                <?php foreach ($riddles_available as $riddle): ?>
                    <option value="<?= $riddle['id'] ?>" <?= $riddle_filter == $riddle['id'] ? 'selected' : '' ?>>Niveau <?= $riddle['id'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="control-group">
            <label>Ordre :</label>
            <a href="?order=desc<?= $riddle_filter ? '&riddle=' . $riddle_filter : '' ?>" class="<?= $order === 'desc' ? 'active' : '' ?>" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; background: #1e293b; cursor: pointer; color: #cbd5f5;">↑ Croissant</a>
            <a href="?order=asc<?= $riddle_filter ? '&riddle=' . $riddle_filter : '' ?>" class="<?= $order === 'asc' ? 'active' : '' ?>" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; background: #1e293b; cursor: pointer; color: #cbd5f5;">↓ Décroissant</a>
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

<?php
include '../includes/footer.php';
?>