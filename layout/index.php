<?php
require '../config/database.php';
require '../includes/header.php';
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
        <a href="jeu-calcul.php" class="card-jeu" data-difficulty="facile">
            <div class="card-content">
                <span class="badge facile">Facile</span>
                <h3>Calcul Mental</h3>
                <p>Soyez le plus rapide pour résoudre ces opérations.</p>
            </div>
        </a>

        <a href="jeu-logique.php" class="card-jeu" data-difficulty="moyen">
            <div class="card-content">
                <span class="badge moyen">Moyen</span>
                <h3>Le Code Perdu</h3>
                <p>Retrouvez la combinaison secrète en 5 essais.</p>
            </div>
        </a>

        <a href="jeu-expert.php" class="card-jeu" data-difficulty="difficile">
            <div class="card-content">
                <span class="badge difficile">Difficile</span>
                <h3>Énigme d'Einstein</h3>
                <p>Seulement 2% de la population peut résoudre ceci.</p>
            </div>
        </a>
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