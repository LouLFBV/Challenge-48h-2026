<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$error = null;
$success = null;
$editMode = isset($_GET['edit']) && $_GET['edit'] == 1;

$stmt = $pdo->prepare("
    SELECT id, name, surname, score, email, password, profile_image
    FROM users
    WHERE id = :id
");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $surname  = trim($_POST['surname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name) {
        $error = "Le prénom est requis.";
        $editMode = true;
    } elseif (!$surname) {
        $error = "Le nom est requis.";
        $editMode = true;
    } elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
        $editMode = true;
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt->execute([
            'email' => $email,
            'id'    => $_SESSION['user_id']
        ]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            $error = "Cet email est déjà utilisé par un autre compte.";
            $editMode = true;
        } else {
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $error = "Le nouveau mot de passe doit faire au moins 6 caractères.";
                    $editMode = true;
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET name = :name,
                            surname = :surname,
                            email = :email,
                            password = :password
                        WHERE id = :id
                    ");

                    $result = $stmt->execute([
                        'name'     => $name,
                        'surname'  => $surname,
                        'email'    => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'id'       => $_SESSION['user_id']
                    ]);
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET name = :name,
                        surname = :surname,
                        email = :email
                    WHERE id = :id
                ");

                $result = $stmt->execute([
                    'name'    => $name,
                    'surname' => $surname,
                    'email'   => $email,
                    'id'      => $_SESSION['user_id']
                ]);
            }

            if (!empty($result) && $result) {
                $_SESSION['name'] = $name;
                $success = "Profil mis à jour avec succès.";
                $editMode = false;
            } elseif (!$error) {
                $error = "Une erreur est survenue lors de la mise à jour.";
                $editMode = true;
            }

            $stmt = $pdo->prepare("
                SELECT id, name, surname, score, email, password, profile_image
                FROM users
                WHERE id = :id
            ");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
    }
}

require '../includes/header.php';

$initial = strtoupper(substr($user['name'], 0, 1));
?>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="profile-avatar"><?= htmlspecialchars($initial) ?></div>
        <h1 class="auth-title">Mon profil</h1>
        <p class="auth-subtitle">Votre espace personnel</p>

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

        <?php if ($success): ?>
            <div class="success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!$editMode): ?>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" value="<?= htmlspecialchars($user['name']) ?>" disabled>
            </div>

            <div class="form-group">
                <label>Nom</label>
                <input type="text" value="<?= htmlspecialchars($user['surname']) ?>" disabled>
            </div>

            <div class="form-group">
                <label>Score</label>
                <input type="number" value="<?= htmlspecialchars($user['score']) ?>" disabled>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            </div>

            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" value="********" disabled>
            </div>

            <a href="profile.php?edit=1" class="btn btn-primary">Modifier le profil</a>
        <?php else: ?>
            <form method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Prénom <span class="req">*</span></label>
                        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? $user['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Nom <span class="req">*</span></label>
                        <input type="text" name="surname" value="<?= htmlspecialchars($_POST['surname'] ?? $user['surname']) ?>" required>
                    </div>

                    <div class="form-group full">
                        <label>Score</label>
                        <input type="number" value="<?= htmlspecialchars($user['score']) ?>" disabled>
                    </div>

                    <div class="form-group full">
                        <label>Email <span class="req">*</span></label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>" required>
                    </div>

                    <div class="form-group full">
                        <label>Nouveau mot de passe</label>
                        <input type="password" name="password" placeholder="Laisser vide pour ne pas changer">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                <a href="profile.php" class="btn btn-secondary">Annuler</a>
            </form>
        <?php endif; ?>
    </div>
</div>