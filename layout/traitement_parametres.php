<?php
session_start();
require_once '../config/database.php'; // Assurez-vous que ce chemin est correct

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $id = $_SESSION['user_id'];
    $newName  = trim($_POST['username']);
    $newEmail = trim($_POST['email']);
    $newPass  = $_POST['new_password'];

    // Vérification : les données sont-elles identiques aux actuelles ?
    $sameName  = ($newName === $_SESSION['username']);
    $sameEmail = ($newEmail === $_SESSION['email']);
    $samePass  = empty($newPass);

    if ($sameName && $sameEmail && $samePass) {
        header('Location: parametres.php?status=no_change');
        exit();
    }

    // Mise à jour SQL
    if (!empty($newPass)) {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newName, $newEmail, $hashed, $id]);
    } else {
        $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newName, $newEmail, $id]);
    }

    // Mise à jour de la session pour que le Header s'actualise
    $_SESSION['username'] = $newName;
    $_SESSION['name'] = $newName;
    $_SESSION['email'] = $newEmail;

    header('Location: parametres.php?status=success');
    exit();
}