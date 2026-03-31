<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum kapalı.']);
    exit;
}

if (isset($_FILES['avatar'])) {
    $uid = $_SESSION['user_id'];
    $file = $_FILES['avatar'];
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Sadece resim (jpg, png, gif) yüklenebilir.']);
        exit;
    }

    $fileName = "avatar_" . $uid . "_" . time() . "." . $ext;
    $uploadPath = "../public/uploads/avatars/" . $fileName;

    if (!is_dir('../public/uploads/avatars/')) {
        mkdir('../public/uploads/avatars/', 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$fileName, $uid]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Klasöre yazılamadı.']);
    }
}