<?php
session_start();
require '../config/database.php';

// Si déjà connecté, rediriger directement
if (!empty($_SESSION['user_id'])) {
    header('Location: ../layout/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, is_admin, profile_image FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name']     = $user['username'];
            $_SESSION['avatar']   = $user['profile_image'] ?? null;
            
            // On transforme le 1 ou 0 de la BDD en vrai true ou false
            $_SESSION['is_admin'] = (bool)$user['is_admin']; 
            
            // Optionnel : on définit aussi le rôle pour ton header
            $_SESSION['role']     = $_SESSION['is_admin'] ? 'admin' : 'user';

            header('Location: ../layout/index.php');
            exit;
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}

$page = 'login';
require '../includes/header.php';
?>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">EnYgmes</div>
        <p class="auth-subtitle">Connectez-vous à votre espace</p>

        <?php if ($error): ?>
            <div class="error">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       placeholder="votre@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn"
                    style="width:100%;justify-content:center;padding:13px;">
                Se connecter
            </button>
        </form>

        <p class="auth-footer">
            Pas encore de compte ? <a href="register.php">S'inscrire</a>
        </p>
    </div>
</div>

<?php require '../includes/footer.php'; ?>