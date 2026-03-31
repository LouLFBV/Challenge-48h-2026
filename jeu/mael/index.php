<?php 
// On définit le dossier racine pour aider les inclusions si besoin
$root = "../../";
include $root . 'includes/header.php'; 
?>

<link rel="stylesheet" href="../../public/css/style.css">

<style>
    /* CSS MINIMAL POUR LE JEU (Non présent dans style.css) */
    #game-container {
        display: flex;
        justify-content: space-around;
        align-items: flex-start;
        gap: 20px;
        padding: 40px 20px;
        flex-wrap: wrap;
    }

    /* Grille de l'Atelier */
    #workshop-grid {
        display: grid !important;
        grid-template-columns: repeat(10, 35px);
        grid-template-rows: repeat(10, 35px);
        gap: 2px;
        background: #1a1f26; /* Fond sombre pour la grille */
        padding: 10px;
        border: 2px solid #00f0ff; /* Bordure Néon du thème */
        box-shadow: 0 0 15px rgba(0, 240, 255, 0.2);
    }

    .workshop-cell {
        width: 35px;
        height: 35px;
        background: #0d1117;
        border: 1px solid #21262d;
    }

    /* La classe que ton game.js va utiliser pour "allumer" les cases */
    .workshop-cell.is-active {
        background: #00f0ff !important;
        box-shadow: 0 0 12px #00f0ff;
    }

    /* Liste des pièces */
    #pieces-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .piece-item {
        padding: 15px;
        border: 2px dashed #a855f7;
        background: rgba(168, 85, 247, 0.1);
        color: #a855f7;
        cursor: grab;
        text-align: center;
        font-family: 'Orbitron', sans-serif;
        text-transform: uppercase;
        font-size: 0.8rem;
    }

    .panel-modele img {
        max-width: 250px;
        border: 2px solid #30363d;
    }
</style>

<div id="game-container">
    <div class="panel-modele">
        <h3 style="color: #a855f7; font-family: 'Orbitron'; text-align: center;">CIBLE</h3>
        <img id="model-image" src="../../public/images/modele1.png">
    </div>

    <div>
        <h3 style="color: #00f0ff; font-family: 'Orbitron'; text-align: center;">ATELIER</h3>
        <div id="workshop-grid"></div>
        <p style="color: #666; text-align: center; margin-top: 10px;">Appuie sur <strong>R</strong> pour pivoter la pièce</p>
    </div>

    <div id="pieces-list">
        <h3 style="color: #a855f7; font-family: 'Orbitron'; text-align: center;">PIÈCES</h3>
        <div class="piece-item" draggable="true" data-shape="L">Shape L</div>
        <div