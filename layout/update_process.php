<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $newUsername = $_POST['username'] ?? '';
    $newEmail = $_POST['email'] ?? '';
    $newPassword = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        // Déterminer si on utilise username ou name
        $hasUsernameColumn = isset($user['username']);
        $usernameColumn = $hasUsernameColumn ? 'username' : 'name';

        $passwordUpdateSql = "";
        $params = [$newUsername, $newEmail];

        if (!empty($newPassword)) {
            if (password_verify($newPassword, $user['password'])) {
                $_SESSION['error_msg'] = "Vous ne pouvez pas changer votre mot de passe par le même mot de passe.";
                header('Location: parametres.php');
                exit();
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $passwordUpdateSql = ", password = ?";
            $params[] = $hashedPassword;
        }

        $params[] = $userId;

        $updateStmt = $pdo->prepare("UPDATE users SET $usernameColumn = ?, email = ? $passwordUpdateSql WHERE id = ?");
        $updateStmt->execute($params);

        $_SESSION['name'] = $newUsername;
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