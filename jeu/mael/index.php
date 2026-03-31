<?php
require_once '../../includes/header.php'; 
?>

<style>
/* ============================================
   ENIGMA GRID — Styles complets
   ============================================ */

@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    background: #050810;
    color: #e0e8ff;
    font-family: 'Rajdhani', sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
}

/* --- Fond animé --- */
#game-module::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
        radial-gradient(ellipse 80% 50% at 20% 20%, rgba(0,240,255,0.04) 0%, transparent 60%),
        radial-gradient(ellipse 60% 80% at 80% 80%, rgba(168,85,247,0.04) 0%, transparent 60%);
    pointer-events: none;
    z-index: 0;
}

/* --- Module principal --- */
#game-module {
    font-family: 'Orbitron', sans-serif;
    max-width: 1300px;
    margin: 0 auto;
    padding: 24px 20px 60px;
    position: relative;
    z-index: 1;
}

/* --- En-tête --- */
.game-header {
    text-align: center;
    margin-bottom: 28px;
}

.game-title {
    font-size: clamp(1.4rem, 4vw, 2.2rem);
    font-weight: 900;
    letter-spacing: 0.3em;
    color: #fff;
    text-shadow: 0 0 30px rgba(0,240,255,0.4);
    margin-bottom: 4px;
}

.game-title span { color: #00f0ff; }

.game-subtitle {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.85rem;
    color: #4a6080;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}

/* --- Sélecteur de niveau --- */
.level-selector {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.btn-level {
    --btn-color: #00f0ff;
    background: transparent;
    border: 1px solid var(--btn-color);
    color: var(--btn-color);
    padding: 8px 18px;
    font-family: 'Orbitron', sans-serif;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    cursor: pointer;
    transition: all 0.25s;
    position: relative;
    overflow: hidden;
    clip-path: polygon(8px 0%, 100% 0%, calc(100% - 8px) 100%, 0% 100%);
}

.btn-level::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--btn-color);
    opacity: 0;
    transition: opacity 0.25s;
}

.btn-level:hover::before,
.btn-level.active::before { opacity: 0.15; }

.btn-level.active {
    background: color-mix(in srgb, var(--btn-color) 20%, transparent);
    box-shadow: 0 0 16px color-mix(in srgb, var(--btn-color) 40%, transparent),
                inset 0 0 8px color-mix(in srgb, var(--btn-color) 10%, transparent);
}

.btn-level span { position: relative; }

.btn-reset {
    background: transparent;
    border: 1px solid rgba(255,100,80,0.5);
    color: #ff6450;
    padding: 8px 18px;
    font-family: 'Orbitron', sans-serif;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    cursor: pointer;
    transition: all 0.25s;
    clip-path: polygon(8px 0%, 100% 0%, calc(100% - 8px) 100%, 0% 100%);
    margin-left: 12px;
}
.btn-reset:hover { background: rgba(255,100,80,0.1); border-color: #ff6450; }

/* --- Barre de statut (chrono + meilleur temps + progression) --- */
.status-bar {
    display: flex;
    justify-content: center;
    align-items: stretch;
    gap: 0;
    margin-bottom: 28px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
    border: 1px solid rgba(0,240,255,0.12);
    background: rgba(0,10,20,0.6);
    clip-path: polygon(16px 0%, 100% 0%, calc(100% - 16px) 100%, 0% 100%);
}

.status-item {
    flex: 1;
    padding: 10px 20px;
    text-align: center;
    border-right: 1px solid rgba(0,240,255,0.1);
    position: relative;
}
.status-item:last-child { border-right: none; }

.status-label {
    font-size: 0.55rem;
    letter-spacing: 0.2em;
    color: #3a5070;
    text-transform: uppercase;
    margin-bottom: 2px;
    font-family: 'Rajdhani', sans-serif;
}

.status-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #00f0ff;
    text-shadow: 0 0 10px rgba(0,240,255,0.5);
    letter-spacing: 0.1em;
}

#best-time { color: #a855f7; text-shadow: 0 0 10px rgba(168,85,247,0.5); font-size: 0.85rem; }

/* Barre de progression */
.progress-track {
    width: 100%;
    height: 4px;
    background: rgba(255,255,255,0.05);
    border-radius: 2px;
    margin-top: 6px;
    overflow: hidden;
}
#progress-bar-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #00f0ff, #a855f7);
    transition: width 0.4s cubic-bezier(0.4,0,0.2,1);
    border-radius: 2px;
}
#progress-label {
    font-size: 0.75rem;
    color: #a855f7;
    display: block;
    margin-top: 2px;
}

/* --- Zone de jeu principale --- */
#game-container {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 24px;
    align-items: start;
    justify-items: center;
}

@media (max-width: 900px) {
    #game-container {
        grid-template-columns: 1fr;
    }
}

/* --- Panneaux de grille --- */
.grid-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    width: 100%;
}

.panel-title {
    font-size: 0.7rem;
    letter-spacing: 0.4em;
    text-transform: uppercase;
    padding: 6px 20px;
    border: 1px solid currentColor;
    clip-path: polygon(8px 0%, 100% 0%, calc(100% - 8px) 100%, 0% 100%);
    display: inline-block;
}

.panel-title.target  { color: #a855f7; }
.panel-title.workshop { color: #00f0ff; }
.panel-title.pieces  { color: #ffd700; }

/* --- Grilles --- */
.grid-board {
    display: grid;
    grid-template-columns: repeat(10, 34px);
    grid-template-rows: repeat(10, 34px);
    gap: 1px;
    background: rgba(255,255,255,0.03);
    padding: 4px;
    position: relative;
}

.grid-board::before {
    content: '';
    position: absolute;
    inset: -2px;
    border: 1px solid rgba(255,255,255,0.06);
}

#model-grid .grid-board::before    { border-color: rgba(168,85,247,0.25); box-shadow: 0 0 20px rgba(168,85,247,0.08); }
#workshop-grid-wrap .grid-board::before { border-color: rgba(0,240,255,0.25); box-shadow: 0 0 20px rgba(0,240,255,0.08); }

.cell {
    width: 34px;
    height: 34px;
    background: rgba(10,15,25,0.8);
    border: 1px solid rgba(255,255,255,0.04);
    transition: background 0.15s, box-shadow 0.15s;
}

.cell.drag-hover {
    background: rgba(0,240,255,0.2) !important;
    border-color: rgba(0,240,255,0.6);
}

/* Cellules actives — Cible */
#model-grid .cell.is-active {
    background: #a855f7;
    border-color: #c084fc;
    box-shadow: 0 0 6px rgba(168,85,247,0.6);
}

/* Cellules actives — Atelier */
#workshop-grid-wrap .cell.is-active {
    background: #00f0ff;
    border-color: #67e8f9;
    box-shadow: 0 0 6px rgba(0,240,255,0.6);
}

@keyframes cellAppear {
    from { transform: scale(0.6); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}
.cell-appear { animation: cellAppear 0.15s ease-out; }

/* --- Panneau Pièces --- */
.pieces-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    min-width: 160px;
}

#pieces-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
}

.piece-wrapper {
    border: 1px solid rgba(168,85,247,0.3);
    background: rgba(168,85,247,0.04);
    padding: 10px 12px;
    cursor: grab;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    transition: border-color 0.2s, background 0.2s, transform 0.15s;
    clip-path: polygon(6px 0%, 100% 0%, calc(100% - 6px) 100%, 0% 100%);
}

.piece-wrapper:hover {
    border-color: rgba(168,85,247,0.7);
    background: rgba(168,85,247,0.1);
    transform: translateX(3px);
}

.piece-wrapper:active { cursor: grabbing; transform: scale(0.97); }
.piece-wrapper.dragging { opacity: 0.5; border-style: dashed; }

@keyframes pieceRotate {
    0%   { transform: rotateY(0deg); }
    50%  { transform: rotateY(90deg); }
    100% { transform: rotateY(0deg); }
}
.piece-wrapper.rotating { animation: pieceRotate 0.3s ease; }

.piece-label {
    font-size: 0.55rem;
    letter-spacing: 0.15em;
    color: #a855f7;
    text-transform: uppercase;
    font-family: 'Orbitron', sans-serif;
}

.piece-hint {
    font-size: 0.55rem;
    color: rgba(168,85,247,0.4);
    font-family: 'Rajdhani', sans-serif;
    letter-spacing: 0.1em;
}

.mini-grid { display: grid; gap: 2px; }
.mini-cell { width: 14px; height: 14px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); }
.mini-cell.filled { background: #a855f7; border-color: #c084fc; box-shadow: 0 0 4px rgba(168,85,247,0.5); }

/* --- Instruction --- */
.workshop-hint {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.75rem;
    color: rgba(255,255,255,0.2);
    text-align: center;
    letter-spacing: 0.1em;
    margin-top: 4px;
}

/* --- Message de victoire --- */
#win-message {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(5,8,16,0.92);
    z-index: 100;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    backdrop-filter: blur(8px);
}

@keyframes winAppear {
    from { opacity: 0; transform: scale(0.8) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.win-appear { animation: winAppear 0.5s cubic-bezier(0.175,0.885,0.32,1.275) forwards; }

.win-card {
    border: 1px solid rgba(0,255,136,0.3);
    background: rgba(0,20,15,0.9);
    padding: 40px 60px;
    text-align: center;
    clip-path: polygon(20px 0%, 100% 0%, calc(100% - 20px) 100%, 0% 100%);
    box-shadow: 0 0 60px rgba(0,255,136,0.1);
}

.win-title {
    font-size: clamp(1.4rem, 4vw, 2rem);
    font-weight: 900;
    color: #00ff88;
    letter-spacing: 0.3em;
    text-shadow: 0 0 30px rgba(0,255,136,0.6);
    margin-bottom: 8px;
}

.win-subtitle {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.9rem;
    color: rgba(255,255,255,0.4);
    letter-spacing: 0.2em;
    margin-bottom: 20px;
}

.win-time-display {
    font-size: 2.5rem;
    font-weight: 900;
    color: #fff;
    letter-spacing: 0.1em;
    text-shadow: 0 0 20px rgba(255,255,255,0.3);
    margin-bottom: 6px;
}

.win-time-label {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.75rem;
    color: rgba(255,255,255,0.3);
    letter-spacing: 0.2em;
    margin-bottom: 28px;
}

.win-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.btn-win {
    padding: 10px 24px;
    font-family: 'Orbitron', sans-serif;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    cursor: pointer;
    transition: all 0.25s;
    clip-path: polygon(8px 0%, 100% 0%, calc(100% - 8px) 100%, 0% 100%);
    border: 1px solid;
}

.btn-win-next { background: rgba(0,255,136,0.15); border-color: #00ff88; color: #00ff88; }
.btn-win-next:hover { background: rgba(0,255,136,0.3); }
.btn-win-retry { background: transparent; border-color: rgba(255,255,255,0.2); color: rgba(255,255,255,0.5); }
.btn-win-retry:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.4); color: #fff; }

/* --- Particules --- */
#particles-container {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 101;
}

@keyframes particleFly {
    0%   { transform: translate(0,0) scale(1); opacity: 1; }
    100% { transform: translate(var(--tx), var(--ty)) scale(0); opacity: 0; }
}

.particle {
    position: absolute;
    border-radius: 2px;
    --tx: calc((var(--rand-x, 0.5) - 0.5) * 400px);
    --ty: calc(-100px - var(--rand-y, 0.5) * 300px);
    animation: particleFly 1s ease-out forwards;
}

/* --- Séparateur central --- */
.vs-divider {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding-top: 50px;
}

.vs-line {
    width: 1px;
    height: 60px;
    background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.1), transparent);
}

.vs-label {
    font-size: 0.6rem;
    letter-spacing: 0.3em;
    color: rgba(255,255,255,0.15);
    writing-mode: vertical-rl;
    text-orientation: mixed;
}

/* --- Responsive --- */
@media (max-width: 700px) {
    .grid-board {
        grid-template-columns: repeat(10, 28px);
        grid-template-rows: repeat(10, 28px);
    }
    .cell { width: 28px; height: 28px; }
    .status-bar { clip-path: none; }
    .vs-divider { display: none; }
}
</style>

<div id="game-module">
    <!-- EN-TÊTE -->
    <div class="game-header">
        <h1 class="game-title">ENIGMA <span>GRID</span></h1>
        <p class="game-subtitle">Reconstitue le schéma cible en superposant les formes</p>
    </div>

    <!-- SÉLECTEUR DE NIVEAU + RESET -->
    <div class="level-selector">
        <?php foreach([['INITIÉ','Nv.1'],['ADEPTE','Nv.2'],['MAÎTRE','Nv.3'],['ORACLE','Nv.4']] as $i => $lvl): ?>
        <button class="btn-level <?= $i===0?'active':'' ?>" onclick="loadLevel(<?= $i ?>)">
            <span><?= $lvl[0] ?> <small style="opacity:0.5;font-size:0.7em;"><?= $lvl[1] ?></small></span>
        </button>
        <?php endforeach; ?>
        <button class="btn-reset" onclick="clearWorkshop()">⟲ RÉINITIALISER</button>
    </div>

    <!-- BARRE DE STATUT -->
    <div class="status-bar">
        <div class="status-item">
            <div class="status-label">⏱ Temps</div>
            <div class="status-value" id="chrono">00:00</div>
        </div>
        <div class="status-item">
            <div class="status-label">★ Record</div>
            <div class="status-value" id="best-time">--:--</div>
        </div>
        <div class="status-item">
            <div class="status-label">Correspondance</div>
            <div class="status-value" id="progress-label">0%</div>
            <div class="progress-track"><div id="progress-bar-fill"></div></div>
        </div>
    </div>

    <!-- ZONE DE JEU -->
    <div id="game-container">
        <!-- CIBLE -->
        <div class="grid-panel" id="model-grid">
            <span class="panel-title target">◈ Cible</span>
            <div class="grid-board"></div>
        </div>

        <!-- SÉPARATEUR VS -->
        <div class="vs-divider">
            <div class="vs-line"></div>
            <div class="vs-label">VERSUS</div>
            <div class="vs-line"></div>
        </div>

        <!-- ATELIER + PIÈCES -->
        <div style="display:flex;gap:20px;align-items:flex-start;">
            <div class="grid-panel" id="workshop-grid-wrap">
                <span class="panel-title workshop">◈ Atelier</span>
                <div class="grid-board" id="workshop-grid"></div>
                <p class="workshop-hint">[ Clic sur une pièce pour pivoter · Glisser-déposer sur la grille ]</p>
            </div>

            <div class="pieces-panel">
                <span class="panel-title pieces">◈ Pièces</span>
                <div id="pieces-list"></div>
            </div>
        </div>
    </div>

    <!-- MESSAGE DE VICTOIRE -->
    <div id="win-message">
        <div class="win-card" id="win-card">
            <div class="win-title">▶ MODULE DÉVERROUILLÉ</div>
            <div class="win-subtitle">Schéma reconstitué avec succès</div>
            <div class="win-time-display" id="final-time">00:00</div>
            <div class="win-time-label">TEMPS ENREGISTRÉ</div>
            <div class="win-actions">
                <button class="btn-win btn-win-retry" onclick="loadLevel(currentLevel)">↺ Rejouer</button>
                <button class="btn-win btn-win-next" id="btn-next-level" onclick="goNextLevel()">Niveau suivant ▶</button>
            </div>
        </div>
    </div>

    <!-- CONTENEUR PARTICULES -->
    <div id="particles-container"></div>
</div>

<script>
// Correction : les grilles ont été refactorisées dans l'HTML (id model-grid pointe vers .grid-board enfant)
// On réoriente les fonctions createHtmlGrid pour cibler les .grid-board enfants

const _origCreateHtmlGrid = window.createHtmlGrid || null;

// Patch pour pointer sur les .grid-board fils
document.addEventListener('DOMContentLoaded', () => {
    // Rien de spécial, le JS pointe déjà bien sur les IDs
});

function goNextLevel() {
    const next = (currentLevel + 1) % LEVELS.length;
    document.getElementById('win-message').style.display = 'none';
    loadLevel(next);
}

// Patch createHtmlGrid pour cibler les bons conteneurs
// model-grid est maintenant le div.grid-panel, son enfant .grid-board est la vraie grille
// On redéfinit createHtmlGrid pour gérer les deux cas
</script>

<script src="game.js"></script>

<script>
// Surcharge de createHtmlGrid après chargement de game.js
// pour gérer la nouvelle structure HTML (wrapper + .grid-board enfant)
(function() {
    const originalCreate = createHtmlGrid || function(){};

    // Pour model-grid : on cible le div.grid-board à l'intérieur
    // Pour workshop-grid : ID direct sur la div.grid-board (inchangé)
    // model-grid est maintenant un div.grid-panel ; son .grid-board est l'enfant
    // → On remplace l'ID utilisé dans loadLevel

    // Redéfinir createHtmlGrid pour qu'il fonctionne avec l'ancien ID "model-grid"
    // qui existe toujours comme div.grid-panel ; on y cherche .grid-board
    window.createHtmlGrid = function(containerId, isAtelier) {
        let container = document.getElementById(containerId);
        if (!container) return;
        // Si le container est un grid-panel, on cible son .grid-board enfant
        if (container.classList.contains('grid-panel')) {
            const board = container.querySelector('.grid-board');
            if (board) container = board;
        }
        container.innerHTML = '';
        const GRID_SIZE = 10;
        for (let r = 0; r < GRID_SIZE; r++) {
            for (let c = 0; c < GRID_SIZE; c++) {
                const cell = document.createElement('div');
                cell.className = 'cell';
                cell.dataset.r = r;
                cell.dataset.c = c;
                if (isAtelier) {
                    cell.addEventListener('dragover', e => {
                        e.preventDefault();
                        cell.classList.add('drag-hover');
                    });
                    cell.addEventListener('dragleave', () => cell.classList.remove('drag-hover'));
                    cell.addEventListener('drop', e => {
                        e.preventDefault();
                        cell.classList.remove('drag-hover');
                        if (isGameWon) return;
                        try {
                            const pieceData = JSON.parse(e.dataTransfer.getData('application/json'));
                            piecesOnGrid.push({type: pieceData.type, rotation: pieceData.rotation, r, c});
                            updateWorkshop();
                            playDropSound();
                        } catch(err) {}
                    });
                }
                container.appendChild(cell);
            }
        }
    };

    // Surcharge updateWorkshop pour cibler le bon conteneur atelier
    const _origUpdateWorkshop = window.updateWorkshop;
    window.updateWorkshop = function() {
        const logicGrid = computeGridLogic(piecesOnGrid);
        const container = document.getElementById('workshop-grid');
        container.querySelectorAll('.cell').forEach(c => c.classList.remove('is-active','cell-appear'));
        for (let r = 0; r < 10; r++) {
            for (let c = 0; c < 10; c++) {
                if (logicGrid[r][c] === 1) {
                    const cell = container.querySelector(`[data-r="${r}"][data-c="${c}"]`);
                    if (cell) { cell.classList.add('is-active','cell-appear'); }
                }
            }
        }
        updateComparisonHUD(logicGrid);
        checkWin(logicGrid);
    };

    // Surcharge loadLevel pour le rendu modèle
    const _origLoad = window.loadLevel;
    window.loadLevel = function(index) {
        currentLevel = index;
        piecesOnGrid = [];
        isGameWon = false;

        const level = LEVELS[index];
        document.getElementById('win-message').style.display = 'none';

        document.querySelectorAll('.btn-level').forEach((btn, i) => {
            btn.classList.toggle('active', i === index);
            btn.style.setProperty('--btn-color', LEVELS[i].color);
        });

        const titleEl = document.getElementById('level-title');
        if (titleEl) { titleEl.textContent = level.name; titleEl.style.color = level.color; }

        updateBestTime(index);
        createHtmlGrid('model-grid', false);
        createHtmlGrid('workshop-grid', true);
        renderInventory(index);

        targetGridLogic = computeGridLogic(level.targetSolution);

        // Remplir la grille modèle
        let modelContainer = document.getElementById('model-grid');
        if (modelContainer.classList.contains('grid-panel')) {
            modelContainer = modelContainer.querySelector('.grid-board') || modelContainer;
        }
        for (let r = 0; r < 10; r++) {
            for (let c = 0; c < 10; c++) {
                if (targetGridLogic[r][c] === 1) {
                    const cell = modelContainer.querySelector(`[data-r="${r}"][data-c="${c}"]`);
                    if (cell) cell.classList.add('is-active');
                }
            }
        }

        // Mettre le bouton "niveau suivant" en grisé si dernier niveau
        const btnNext = document.getElementById('btn-next-level');
        if (btnNext) {
            const isLast = index === LEVELS.length - 1;
            btnNext.disabled = isLast;
            btnNext.style.opacity = isLast ? '0.3' : '1';
            btnNext.textContent = isLast ? '◈ Dernier niveau' : 'Niveau suivant ▶';
        }

        updateComparisonHUD(computeGridLogic([]));
        startTimer();
    };
})();
</script>

<?php 
require_once '../../includes/footer.php'; 
?>