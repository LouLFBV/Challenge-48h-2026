<?php
/**
 * save_score.php — Enigma Grid
 * Enregistre le temps et le score d'un joueur pour un niveau donné.
 * Appelé en POST depuis game.js lors d'une victoire.
 */

session_start();
header('Content-Type: application/json');

// Seuls les utilisateurs connectés peuvent sauvegarder
if (empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non authentifié']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!isset($data['level'], $data['time_seconds'], $data['score'])) {
    echo json_encode(['status' => 'error', 'message' => 'Données manquantes']);
    exit;
}

$userId      = (int) $_SESSION['user_id'];
$level       = (int) $data['level'];
$timeSeconds = (int) $data['time_seconds'];
$score       = (int) $data['score'];

// Validation basique
if ($level < 0 || $level > 7 || $timeSeconds < 0 || $score < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Données invalides']);
    exit;
}

require_once '../../config/database.php';

try {
    // Vérifier si une entrée existe déjà pour ce joueur/niveau
    $stmt = $pdo->prepare("
        SELECT id, best_time_seconds, best_score 
        FROM enigma_grid_scores 
        WHERE user_id = :uid AND level = :level
    ");
    $stmt->execute(['uid' => $userId, 'level' => $level]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $isNewRecord = false;

    if ($existing) {
        // Mettre à jour seulement si meilleur score ou meilleur temps
        $updates = [];
        $params  = ['uid' => $userId, 'level' => $level];

        if ($timeSeconds < $existing['best_time_seconds']) {
            $updates[] = 'best_time_seconds = :time';
            $params['time'] = $timeSeconds;
        }
        if ($score > $existing['best_score']) {
            $updates[] = 'best_score = :score';
            $params['score'] = $score;
            $isNewRecord = true;
        }

        if (!empty($updates)) {
            $updates[] = 'updated_at = NOW()';
            $sql = "UPDATE enigma_grid_scores SET " . implode(', ', $updates) . " WHERE user_id = :uid AND level = :level";
            $pdo->prepare($sql)->execute($params);
        }
    } else {
        // Première entrée pour ce niveau
        $stmt = $pdo->prepare("
            INSERT INTO enigma_grid_scores (user_id, level, best_time_seconds, best_score, created_at, updated_at)
            VALUES (:uid, :level, :time, :score, NOW(), NOW())
        ");
        $stmt->execute([
            'uid'   => $userId,
            'level' => $level,
            'time'  => $timeSeconds,
            'score' => $score,
        ]);
        $isNewRecord = true;
    }

    // Mettre à jour le total_score dans la table users
    // On recalcule la somme des meilleurs scores de tous les niveaux
    $sumStmt = $pdo->prepare("
        SELECT COALESCE(SUM(best_score), 0) AS total
        FROM enigma_grid_scores
        WHERE user_id = :uid
    ");
    $sumStmt->execute(['uid' => $userId]);
    $totalScore = (int) $sumStmt->fetchColumn();

    // On ajoute ce score au total_score existant du site (d'autres jeux peuvent contribuer)
    // Option : stocker dans une colonne dédiée enigma_score, ou mettre à jour total_score
    // Ici on met à jour enigma_score si elle existe, sinon total_score
    try {
        $pdo->prepare("UPDATE users SET enigma_score = :score WHERE id = :uid")
            ->execute(['score' => $totalScore, 'uid' => $userId]);
    } catch (Exception $e) {
        // La colonne enigma_score n'existe peut-être pas encore — on essaie total_score
        try {
            $pdo->prepare("UPDATE users SET total_score = total_score + :score WHERE id = :uid")
                ->execute(['score' => $score, 'uid' => $userId]);
        } catch (Exception $e2) {
            // Silencieux — on ne bloque pas le jeu pour une erreur de BDD
        }
    }

    echo json_encode([
        'status'       => 'ok',
        'new_record'   => $isNewRecord,
        'total_enigma' => $totalScore,
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}