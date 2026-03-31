<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session fermée.']);
    exit;
}

if (isset($_FILES['avatar'])) {
    $uid = $_SESSION['user_id'];
    $file = $_FILES['avatar'];
    
    // Vérifier la taille (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max 2MB).']);
        exit;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Seuls les fichiers JPG, PNG ou GIF sont acceptés.']);
        exit;
    }

    $fileName = "avatar_" . $uid . "_" . time() . "." . $ext;
    $uploadDirPath = dirname(__DIR__) . "/public/uploads/";
    $uploadPath = $uploadDirPath . $fileName;

    if (!is_dir($uploadDirPath)) {
        mkdir($uploadDirPath, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Déterminer si la colonne s'appelle profile_image ou avatar
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        
        $columnName = isset($user['profile_image']) ? 'profile_image' : 'avatar';
        
        $stmt = $pdo->prepare("UPDATE users SET $columnName = ? WHERE id = ?");
        $stmt->execute([$fileName, $uid]);
        
        echo json_encode(['success' => true, 'message' => 'Avatar mis à jour avec succès.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Impossible de télécharger le fichier.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu.']);
}