<?php
/**
 * chat_poll.php — EnYgmes
 * Endpoint AJAX : retourne les nouveaux messages en JSON
 *
 * GET  ?last_id=N   → messages avec id > N
 * POST {message}    → insère un nouveau message
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Auth requise
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

/* ── POST : envoyer un message ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body    = json_decode(file_get_contents('php://input'), true);
    $message = trim($body['message'] ?? '');

    if ($message === '' || mb_strlen($message) > 1000) {
        http_response_code(400);
        echo json_encode(['error' => 'Message invalide']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO general_chat (user_id, message)
        VALUES (:user_id, :message)
    ");
    $stmt->execute(['user_id' => $userId, 'message' => $message]);
    $newId = $pdo->lastInsertId();

    // Retourner le message inséré
    $stmt = $pdo->prepare("
        SELECT gc.id, gc.message, gc.created_at,
               u.name, u.profile_image
        FROM general_chat gc
        JOIN users u ON u.id = gc.user_id
        WHERE gc.id = :id
    ");
    $stmt->execute(['id' => $newId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['message' => formatMessage($row, $userId)]);
    exit;
}

/* ── GET : polling des nouveaux messages ── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $lastId = (int) ($_GET['last_id'] ?? 0);

    // Première charge : 50 derniers messages
    if ($lastId === 0) {
        $stmt = $pdo->prepare("
            SELECT gc.id, gc.message, gc.created_at,
                   u.name, u.profile_image
            FROM general_chat gc
            JOIN users u ON u.id = gc.user_id
            ORDER BY gc.id DESC
            LIMIT 50
        ");
        $stmt->execute();
        $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        // Polling : seulement les nouveaux
        $stmt = $pdo->prepare("
            SELECT gc.id, gc.message, gc.created_at,
                   u.name, u.profile_image
            FROM general_chat gc
            JOIN users u ON u.id = gc.user_id
            WHERE gc.id > :last_id
            ORDER BY gc.id ASC
            LIMIT 50
        ");
        $stmt->execute(['last_id' => $lastId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $messages = array_map(fn($r) => formatMessage($r, $userId), $rows);
    echo json_encode(['messages' => $messages]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);

/* ── Helper ── */
function formatMessage(array $row, int $currentUserId): array {
    return [
        'id'       => (int) $row['id'],
        'message'  => $row['message'],
        'name' => $row['name'],
        'avatar'   => $row['profile_image'] ?? 'default.png',
        'time'     => date('H:i', strtotime($row['created_at'])),
        'date'     => date('d/m/Y', strtotime($row['created_at'])),
        'is_me'    => false, // rendu côté JS avec sessionStorage
    ];
}