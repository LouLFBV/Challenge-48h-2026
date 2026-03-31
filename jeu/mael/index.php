<?php 
// L'utilisation de __DIR__ force PHP à partir du dossier actuel. Plus d'erreurs !
include __DIR__ . '/../../includes/header.php'; 
?>

<style>
    body {
        background-color: #0d1117; /* Fond sombre absolu */
        color: white;
    }

    #game-module {
        font-family: 'Orbitron', sans-serif;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .level-selector {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-bottom: 30px;
    }

    .btn-cyber {
        background: rgba(0, 240, 255, 0.1);
        border: 1px solid #00f0ff;
        color: #00f0ff;
        padding: 10px 20px;
        font-family: 'Orbitron', sans-serif;
        cursor: pointer;
        transition: 0.3s;
    }
    .btn-cyber:hover, .btn-cyber.active {
        background: #00f0ff;
        color: black;
    }

    #game-container {
        display: flex;
        justify-content: space-around;
        align-items: flex-start;
        gap: 20px;
        flex-wrap: wrap;
    }

    /* CORRECTION DU BUG DE LA GRILLE (L'espace gris en trop) */
    .grid-board {
        display: grid;
        grid-template-columns: repeat(10, 35px);
        grid-template-rows: repeat(10, 35px);
        gap: 1px;
        background: #1a1f26;
        padding: 5px;
        border: 2px solid #00f0ff;
        box-shadow: 0 0 15px rgba(0, 240, 255, 0.2);
        width: fit-content; /* C'est cette ligne qui répare ton carré gris ! */
        margin: 0 auto;
    }
    
    #model-grid { border-color: #a855f7; box-shadow: 0 0 15px rgba(168, 85, 247, 0.2); }

    .cell {
        width: 35px;
        height: 35px;
        background: #0a0c10;
        border: 1px solid #1f2937;
        box-sizing: border-box;
        transition: background 0.2s;
    }

    /* CORRECTION DRAG & DROP : Case ciblée en surbrillance */
    .cell.drag-hover {
        background: rgba(0, 240, 255, 0.5) !important;
        border: 1px solid #ffffff;
    }

    .cell.is-active {
        background: #00f0ff;
        box-shadow: 0 0 8px #00f0ff;
        border-color: #00f0ff;
    }
    
    #model-grid .cell.is-active {
        background: #a855f7;
        box-shadow: 0 0 8px #a855f7;
        border-color: #a855f7;
    }

    #pieces-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
        min-width: 150px;
    }

    .piece-wrapper {
        border: 1px dashed #a855f7;
        background: rgba(168, 85, 247, 0.05);
        padding: 10px;
        cursor: grab;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
    }
    .piece-wrapper:active { cursor: grabbing; }

    .mini-grid { display: grid; gap: 2px; }
    .mini-cell { width: 15px; height: 15px; background: transparent; }
    .mini-cell.filled { background: #a855f7; }

    #win-message {
        display: none;
        text-align: center;
        color: #00ff00;
        font-size: 2rem;
        margin-top: 20px;
        text-shadow: 0 0 10px #00ff00;
    }
</style>

<div id="game-module">
    <div class="level-selector">
        <button class="btn-cyber active" onclick="loadLevel(0)">Facile</button>
        <button class="btn-cyber" onclick="loadLevel(1)">Moyen</button>
        <button class="btn-cyber" onclick="loadLevel(2)">Difficile</button>
        <button class="btn-cyber" style="border-color: #ff0055; color: #ff0055;" onclick="clearWorkshop()">Vider l'Atelier</button>
    </div>

    <div id="game-container">
        <div>
            <h3 style="color: #a855f7; text-align: center;">CIBLE</h3>
            <div id="model-grid" class="grid-board"></div>
        </div>

        <div>
            <h3 style="color: #00f0ff; text-align: center;">ATELIER</h3>
            <div id="workshop-grid" class="grid-board"></div>
            <p style="text-align: center; margin-top: 10px; font-size: 0.8rem; color: #888;">
                [ Clic GAUCHE sur une pièce pour la pivoter. Glisse-la avec le coin HAUT-GAUCHE. ]
            </p>
        </div>

        <div>
            <h3 style="color: #a855f7; text-align: center;">PIÈCES</h3>
            <div id="pieces-list"></div>
        </div>
    </div>

    <div id="win-message">&gt; MODULE DÉVERROUILLÉ_</div>
</div>

<script src="game.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>