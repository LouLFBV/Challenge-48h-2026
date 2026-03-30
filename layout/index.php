<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EnYgmes - Jeux d'énigmes</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

    <header class="top-bar">
        <div class="logo">EnYgmes</div>
        
        <div class="header-right">
            <a href="leaderboard.php" class="btn-score">
                <span>🏆 Classement</span>
            </a>
            
            <div class="profile-container">
                <div class="user-profile" id="profileToggle">
                    <img src="avatar-placeholder.png" alt="Avatar">
                    <div class="user-info">
                        <span class="username">Joueur_123</span>
                        <span class="rank">Niveau 5 ▼</span>
                    </div>
                </div>

                <div class="dropdown-menu" id="dropdownMenu">
                    <a href="#" class="dropdown-item">👤 Mon Profil</a>
                    <a href="#" class="dropdown-item">👤 Scores</a>
                    <a href="#" class="dropdown-item">⚙️ Paramètres</a>
                    <hr>
                    <a href="#" class="dropdown-item logout">🚪 Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <main class="games-container">
        <section class="game-card">
            <div class="game-thumb">?</div>
            <h3>L'Énigme du Sphinx</h3>
            <p>Logique et réflexion</p>
            <button class="btn-play">Jouer</button>
        </section>

        <section class="game-card">
            <div class="game-thumb">🔑</div>
            <h3>Escape Room Digital</h3>
            <p>Observation</p>
            <button class="btn-play">Jouer</button>
        </section>

        <section class="game-card">
            <div class="game-thumb">🧩</div>
            <h3>Code Brisé</h3>
            <p>Cryptographie</p>
            <button class="btn-play">Jouer</button>
        </section>
    </main>

</body>
</html>

<script>
    const profileToggle = document.getElementById('profileToggle');
    const dropdownMenu = document.getElementById('dropdownMenu');

    // Toggle (ouvrir/fermer) au clic sur le profil
    profileToggle.addEventListener('click', function(e) {
        e.stopPropagation(); // Empêche la propagation du clic au document
        dropdownMenu.classList.toggle('active');
    });

    // Fermer le menu si on clique n'importe où ailleurs sur la page
    document.addEventListener('click', function() {
        dropdownMenu.classList.remove('active');
    });
</script>