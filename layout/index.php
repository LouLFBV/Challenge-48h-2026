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
            <a href="#" class="btn-score">🏆 Classement</a>
            
            <a href="#" class="user-profile" id="profileClick">
                <img src="avatar-placeholder.png" alt="Avatar">
                <div class="user-info">
                    <span class="username">Joueur_123</span>
                    <span class="rank">Niveau 5</span>
                </div>
            </a>
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