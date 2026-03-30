<?php
require '../config/database.php';


require '../includes/header.php';

?>




<h1>Bienvenue sur notre site</h1>
    vous êtes connecté en tant que <?= htmlspecialchars($_SESSION['name'] ?? 'Invité') ?>.
        <p class="auth-footer">Déjà un compte ? <a href="../auth/login.php">Se connecter</a></p>

<?php
require '../includes/footer.php';
?>