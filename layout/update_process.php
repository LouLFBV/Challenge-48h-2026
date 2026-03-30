<?php
session_start();
require_once '../config/database.php'; // Veritabanı bağlantı dosyanızın yolu

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $newName = $_POST['name'];
    $newEmail = $_POST['email'];
    $newPassword = $_POST['password'];

    try {
        // 1. Mevcut bilgileri çek (Şifre kontrolü için)
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        // 2. Şifre değiştirilmek isteniyorsa kontrol et
        $passwordUpdateSql = "";
        $params = [$newName, $newEmail];

        if (!empty($newPassword)) {
            // Mevcut şifre ile aynı mı kontrol et (password_verify kullanıyorsanız)
            if (password_verify($newPassword, $user['password'])) {
                $_SESSION['error_msg'] = "Vous ne pouvez pas changer votre mot de passe par le même mot de passe.";
                header('Location: parametres.php');
                exit();
            }
            
            // Yeni şifreyi hashle
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $passwordUpdateSql = ", password = ?";
            $params[] = $hashedPassword;
        }

        $params[] = $userId;

        // 3. Güncelleme işlemini yap
        $updateStmt = $pdo->prepare("UPDATE users SET name = ?, email = ? $passwordUpdateSql WHERE id = ?");
        $updateStmt->execute($params);

        // Session bilgilerini güncelle
        $_SESSION['name'] = $newName;
        $_SESSION['email'] = $newEmail;

        $_SESSION['success_msg'] = "Vos modifications ont été enregistrées avec succès !";
        header('Location: parametres.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Une erreur est survenue lors de la mise à jour.";
        header('Location: parametres.php');
        exit();
    }
} else {
    header('Location: parametres.php');
    exit();
}