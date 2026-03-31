<?php
require '../config/database.php';
require '../includes/header.php';

try {
    $query = $pdo->query("SELECT * FROM riddles ORDER BY id DESC");
    $riddles = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des énigmes : " . $e->getMessage());
}
?>


<link rel="stylesheet" href="../public/css/jeux.css">

<main class="container-jeux">
    <section class="controls">
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Rechercher une énigme...">
            <button type="submit" id="searchBtn" class="search-icon-btn">🔍</button>
        </div>

        <div class="filter-container">
            <select id="difficultyFilter" class="filter-select">
                <option value="all">Toutes les difficultés</option>
                <option value="facile">Facile</option>
                <option value="moyen">Moyen</option>
                <option value="difficile">Difficile</option>
            </select>
        </div>
    </section>

    <section class="grid-jeux" id="gridJeux">
        <?php if (count($riddles) > 0): ?>
            <?php foreach ($riddles as $riddle): ?>
                <a href="../games_Balance/game.php?id=<?= $riddle['id'] ?>" class="card-jeu" data-difficulty="<?= htmlspecialchars($riddle['difficulty']) ?>">
                <!-- <a href="<?= htmlspecialchars($riddle['url_jeu']) ?>?id=<?= $riddle['id'] ?>" class="card-jeu" data-difficulty="<?= htmlspecialchars($riddle['difficulty']) ?>"> -->
                    <div class="card-content">
                        <span class="badge <?= htmlspecialchars($riddle['difficulty']) ?>">
                            <?= ucfirst(htmlspecialchars($riddle['difficulty'])) ?>
                        </span>
                        
                        <h3><?= htmlspecialchars($riddle['title']) ?></h3>
                        
                        <p><?= nl2br(htmlspecialchars($riddle['description'])) ?></p>
                        
                        <div class="card-footer">
                            <small>Points max : <?= $riddle['max_points'] ?></small>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-data">Aucune énigme n'a été trouvée dans le terminal...</p>
        <?php endif; ?>
    </section>
</main>

<script>
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');
const filterSelect = document.getElementById('difficultyFilter');
const cards = document.querySelectorAll('.card-jeu');

function filtrerJeux() {
    const searchText = searchInput.value.toLowerCase();
    const filterValue = filterSelect.value;

    cards.forEach(card => {
        const title = card.querySelector('h3').innerText.toLowerCase();
        const difficulty = card.getAttribute('data-difficulty');
        
        const matchesSearch = title.includes(searchText);
        const matchesFilter = (filterValue === 'all' || difficulty === filterValue);

        if (matchesSearch && matchesFilter) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Écouteur pour le menu déroulant
filterSelect.addEventListener('change', filtrerJeux);

// Écouteur pour le clic sur la loupe
searchBtn.addEventListener('click', (e) => {
    e.preventDefault();
    filtrerJeux();
});

// Bonus : Filtrer en direct pendant la frappe
searchInput.addEventListener('input', filtrerJeux);
</script>

<?php require '../includes/footer.php'; ?>