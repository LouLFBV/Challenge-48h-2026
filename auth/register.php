<?php
session_start();
require '../config/database.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = trim($_POST['name'] ?? '');
    $surname          = trim($_POST['surname'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$name) {
        $error = "Le prénom est requis.";
    } elseif (!$surname) {
        $error = "Le nom est requis.";
    } elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif (!$password) {
        $error = "Le mot de passe est requis.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit faire au moins 6 caractères.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    }

    if (!$error) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            $error = "Cet email est déjà utilisé.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, surname, score, email, password, profile_image)
                    VALUES (:name, :surname, :score, :email, :password, :profile_image)
                ");

                $stmt->execute([
                    'name'          => $name,
                    'surname'       => $surname,
                    'score'         => 0,
                    'email'         => $email,
                    'password'      => password_hash($password, PASSWORD_DEFAULT),
                    'profile_image' => 'default.png'
                ]);

                $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = :email");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];

                header('Location: ../layout/index.php');
                exit;
            } catch (PDOException $e) {
                $error = "Erreur lors de l'inscription : " . $e->getMessage();
            }
        }
    }
}

require '../includes/header.php';
?>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">Auth</div>
        <h1 class="auth-title">Inscription</h1>
        <p class="auth-subtitle">Créez votre compte</p>

        <?php if ($error): ?>
            <div class="error">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-grid">
                <div class="form-group">
                    <label>Prénom <span class="req">*</span></label>
                    <input type="text" name="name" placeholder="Jean" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Nom <span class="req">*</span></label>
                    <input type="text" name="surname" placeholder="Dupont" value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>" required>
                </div>

                <div class="form-group full">
                    <label>Email <span class="req">*</span></label>
                    <input type="email" name="email" placeholder="votre@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Mot de passe <span class="req">*</span></label>
                    <input type="password" name="password" placeholder="Min. 6 caractères" required>
                </div>

                <div class="form-group">
                    <label>Confirmer <span class="req">*</span></label>
                    <input type="password" name="confirm_password" placeholder="Répétez le mot de passe" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Créer mon compte</button>
        </form>

        <p class="auth-footer">
            Déjà un compte ? <a href="login.php">Se connecter</a>
        </p>
    </div>
</div>