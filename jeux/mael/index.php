<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap');

*, *::before, *::after { box-sizing: border-box; }

body {
    background: #050810;
    color: #e0e8ff;
    font-family: 'Rajdhani', sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
}
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
        radial-gradient(ellipse 70% 50% at 15% 15%, rgba(0,240,255,0.05) 0%, transparent 60%),
        radial-gradient(ellipse 60% 70% at 85% 85%, rgba(168,85,247,0.05) 0%, transparent 60%);
    pointer-events: none;
    z-index: 0;
}

#game-module {
    font-family: 'Orbitron', sans-serif;
    max-width: 1340px;
    margin: 120px auto 40px;
    padding: 24px 20px 40px;
    position: relative;
    z-index: 1;
}

/* ── En-tête ── */
.game-header { text-align: center; margin-bottom: 18px; }
.game-title {
    font-size: clamp(1.5rem, 4vw, 2.2rem);
    font-weight: 900;
    letter-spacing: 0.35em;
    color: #fff;
    text-shadow: 0 0 40px rgba(0,240,255,0.3);
}
.game-title span { color: #00f0ff; }
.game-subtitle {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.8rem;
    color: #3a5570;
    letter-spacing: 0.2em;
    margin-top: 4px;
}

/* ── Sélecteur de niveaux ── */
.level-selector {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}
.btn-level {
    --btn-color: #00f0ff;
    background: transparent;
    border: 1px solid color-mix(in srgb, var(--btn-color) 40%, transparent);
    color: color-mix(in srgb, var(--btn-color) 70%, #fff);
    padding: 7px 14px;
    font-family: 'Orbitron', sans-serif;
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    cursor: pointer;
    transition: background 0.2s, box-shadow 0.2s, border-color 0.2s;
    clip-path: polygon(6px 0%, 100% 0%, calc(100% - 6px) 100%, 0% 100%);
    white-space: nowrap;
}
.btn-level:hover {
    border-color: var(--btn-color);
    background: color-mix(in srgb, var(--btn-color) 12%, transparent);
}
.btn-level.active {
    border-color: var(--btn-color);
    color: var(--btn-color);
    background: color-mix(in srgb, var(--btn-color) 18%, transparent);
    box-shadow: 0 0 12px color-mix(in srgb, var(--btn-color) 35%, transparent);
}
.btn-reset {
    background: transparent;
    border: 1px solid rgba(255,100,80,0.4);
    color: #ff6450;
    padding: 7px 14px;
    font-family: 'Orbitron', sans-serif;
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    cursor: pointer;
    transition: background 0.2s;
    clip-path: polygon(6px 0%, 100% 0%, calc(100% - 6px) 100%, 0% 100%);
    margin-left: 8px;
}
.btn-reset:hover { background: rgba(255,100,80,0.1); }

/* Description */
#level-desc {
    text-align: center;
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.8rem;
    color: rgba(255,255,255,0.3);
    letter-spacing: 0.12em;
    margin-bottom: 14px;
    min-height: 1.2em;
    font-style: italic;
}

/* ── Barre de statut ── */
.status-bar {
    display: flex;
    justify-content: center;
    max-width: 780px;
    margin: 0 auto 22px;
    border: 1px solid rgba(0,240,255,0.1);
    background: rgba(0,8,18,0.7);
    clip-path: polygon(14px 0%, 100% 0%, calc(100% - 14px) 100%, 0% 100%);
}
.status-item {
    flex: 1;
    padding: 9px 16px;
    text-align: center;
    border-right: 1px solid rgba(0,240,255,0.07);
}
.status-item:last-child { border-right: none; }
.status-label {
    font-size: 0.5rem;
    letter-spacing: 0.2em;
    color: #2a4060;
    text-transform: uppercase;
    font-family: 'Rajdhani', sans-serif;
    margin-bottom: 2px;
}
.status-value { font-size: 1rem; font-weight: 700; letter-spacing: 0.1em; }
#chrono      { color: #00f0ff; text-shadow: 0 0 10px rgba(0,240,255,0.5); }
#best-time   { color: #a855f7; text-shadow: 0 0 10px rgba(168,85,247,0.4); font-size: 0.82rem; }
#best-score  { color: #ffd700; font-size: 0.78rem; }
#progress-label { color: #00ff88; font-size: 0.88rem; }
.progress-track {
    width: 100%; height: 3px;
    background: rgba(255,255,255,0.05);
    border-radius: 2px; margin-top: 4px; overflow: hidden;
}
#progress-bar-fill {
    height: 100%; width: 0%;
    background: linear-gradient(90deg, #00f0ff, #a855f7);
    transition: width 0.35s cubic-bezier(0.4,0,0.2,1);
}

/* ── Zone de jeu ── */
#game-container {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    gap: 18px;
    flex-wrap: wrap;
}

.grid-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}
.panel-title {
    font-size: 0.62rem;
    letter-spacing: 0.4em;
    text-transform: uppercase;
    padding: 5px 16px;
    border: 1px solid currentColor;
    clip-path: polygon(5px 0%, 100% 0%, calc(100% - 5px) 100%, 0% 100%);
}
.panel-title.target   { color: #a855f7; }
.panel-title.workshop { color: #00f0ff; }
.panel-title.pieces   { color: #ffd700; }

/* ── Grilles ── */
#model-grid, #workshop-grid {
    display: grid;
    grid-template-columns: repeat(10, 34px);
    grid-template-rows:    repeat(10, 34px);
    gap: 1px;
    background: rgba(255,255,255,0.02);
    padding: 3px;
}
#model-grid    { border: 1px solid rgba(168,85,247,0.22); box-shadow: 0 0 18px rgba(168,85,247,0.06); }
#workshop-grid { border: 1px solid rgba(0,240,255,0.22);  box-shadow: 0 0 18px rgba(0,240,255,0.06); }

.cell {
    width: 34px; height: 34px;
    background: rgba(8,12,22,0.9);
    border: 1px solid rgba(255,255,255,0.04);
    transition: background 0.1s;
}

#model-grid .cell.is-active {
    background: #a855f7;
    border-color: #c084fc;
    box-shadow: 0 0 5px rgba(168,85,247,0.5);
}
#workshop-grid .cell.is-active {
    background: #00f0ff;
    border-color: #67e8f9;
    box-shadow: 0 0 5px rgba(0,240,255,0.5);
}

/* ── Preview ── */
#workshop-grid .cell.preview-valid {
    background: rgba(0,240,255,0.22) !important;
    border-color: rgba(0,240,255,0.7) !important;
    box-shadow: inset 0 0 0 1px rgba(0,240,255,0.4);
}
#workshop-grid .cell.preview-invalid {
    background: rgba(255,60,60,0.22) !important;
    border-color: rgba(255,80,80,0.7) !important;
    box-shadow: inset 0 0 0 1px rgba(255,60,60,0.4);
}
#workshop-grid .cell.is-active.preview-valid {
    background: rgba(255,200,0,0.35) !important;
    border-color: rgba(255,220,0,0.8) !important;
}

@keyframes cellAppear {
    from { transform: scale(0.55); opacity: 0; }
    to   { transform: scale(1);    opacity: 1; }
}
.cell-appear { animation: cellAppear 0.11s ease-out; }

.workshop-hint {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.7rem;
    color: rgba(255,255,255,0.16);
    text-align: center;
    letter-spacing: 0.08em;
    max-width: 360px;
}

/* Légende preview */
.preview-legend {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-top: 2px;
}
.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.65rem;
    color: rgba(255,255,255,0.3);
    letter-spacing: 0.08em;
}
.legend-dot {
    width: 10px; height: 10px;
    border: 1px solid;
    border-radius: 1px;
}
.legend-dot.valid   { background: rgba(0,240,255,0.22); border-color: rgba(0,240,255,0.7); }
.legend-dot.invalid { background: rgba(255,60,60,0.22);  border-color: rgba(255,80,80,0.7); }
.legend-dot.xor     { background: rgba(255,200,0,0.35);  border-color: rgba(255,220,0,0.8); }

/* ── VS divider ── */
.vs-divider {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 44px;
    gap: 8px;
}
.vs-line { width: 1px; height: 45px; background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.07), transparent); }
.vs-label { font-size: 0.5rem; letter-spacing: 0.3em; color: rgba(255,255,255,0.1); writing-mode: vertical-rl; }

/* ── Pièces ── */
.pieces-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 145px;
}
#pieces-list {
    display: flex;
    flex-direction: column;
    gap: 7px;
    width: 100%;
    max-height: 500px;
    overflow-y: auto;
    padding-right: 2px;
}
#pieces-list::-webkit-scrollbar { width: 3px; }
#pieces-list::-webkit-scrollbar-thumb { background: rgba(168,85,247,0.3); }

.piece-wrapper {
    border: 1px solid rgba(168,85,247,0.28);
    background: rgba(168,85,247,0.04);
    padding: 9px 11px;
    cursor: grab;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    transition: border-color 0.18s, background 0.18s, transform 0.12s;
    clip-path: polygon(5px 0%, 100% 0%, calc(100% - 5px) 100%, 0% 100%);
}
.piece-wrapper:hover {
    border-color: rgba(168,85,247,0.6);
    background: rgba(168,85,247,0.1);
    transform: translateX(3px);
}
.piece-wrapper:active { cursor: grabbing; }
.piece-wrapper.dragging { opacity: 0.4; }
@keyframes pieceRotate {
    0%   { transform: rotateY(0); }
    50%  { transform: rotateY(90deg); }
    100% { transform: rotateY(0); }
}
.piece-wrapper.rotating { animation: pieceRotate 0.26s ease; }

.piece-label { font-size: 0.5rem; letter-spacing: 0.14em; color: #a855f7; text-transform: uppercase; }
.piece-hint  { font-size: 0.5rem; color: rgba(168,85,247,0.35); font-family: 'Rajdhani', sans-serif; }
.mini-grid { display: grid; gap: 2px; }
.mini-cell {
    width: 14px; height: 14px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.05);
}
.mini-cell.filled {
    background: #a855f7;
    border-color: #c084fc;
    box-shadow: 0 0 4px rgba(168,85,247,0.45);
}

/* ── Panel header de l'atelier ── */
.panel-header-row {
    display: flex;
    align-items: center;
    gap: 12px;
}
#move-counter {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.65rem;
    color: rgba(0,240,255,0.4);
    letter-spacing: 0.12em;
}

/* ── Victoire ── */
#win-message {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(5,8,16,0.9);
    z-index: 200;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}
.win-card {
    border: 1px solid rgba(0,255,136,0.3);
    background: rgba(0,18,12,0.95);
    padding: 42px 62px;
    text-align: center;
    clip-path: polygon(18px 0%, 100% 0%, calc(100% - 18px) 100%, 0% 100%);
    box-shadow: 0 0 60px rgba(0,255,136,0.07);
    opacity: 0;
    transform: scale(0.84) translateY(16px);
}
@keyframes winAppear {
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.win-card.win-appear { animation: winAppear 0.42s cubic-bezier(0.175,0.885,0.32,1.275) forwards; }
.win-title {
    font-size: clamp(1.2rem, 3.5vw, 1.8rem);
    font-weight: 900; color: #00ff88;
    letter-spacing: 0.3em;
    text-shadow: 0 0 28px rgba(0,255,136,0.5);
    margin-bottom: 5px;
}
.win-subtitle { font-family: 'Rajdhani', sans-serif; font-size: 0.82rem; color: rgba(255,255,255,0.32); letter-spacing: 0.18em; margin-bottom: 20px; }

/* Score / temps dans la win card */
.win-stats {
    display: flex;
    gap: 32px;
    justify-content: center;
    margin-bottom: 8px;
}
.win-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.win-stat-label {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.55rem;
    color: rgba(255,255,255,0.22);
    letter-spacing: 0.22em;
    text-transform: uppercase;
    margin-bottom: 2px;
}
.win-stat-value {
    font-size: 2rem;
    font-weight: 900;
    letter-spacing: 0.08em;
}
#final-time   { color: #00f0ff; text-shadow: 0 0 16px rgba(0,240,255,0.4); }
#final-score  { color: #ffd700; text-shadow: 0 0 16px rgba(255,215,0,0.4); }
#score-record {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.72rem;
    color: #ffd700;
    letter-spacing: 0.12em;
    margin-bottom: 24px;
    min-height: 1.2em;
}

.win-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
.btn-win {
    padding: 9px 24px;
    font-family: 'Orbitron', sans-serif;
    font-size: 0.6rem; font-weight: 700;
    letter-spacing: 0.12em; cursor: pointer;
    clip-path: polygon(6px 0%, 100% 0%, calc(100% - 6px) 100%, 0% 100%);
    border: 1px solid; transition: background 0.2s;
}
.btn-win-retry { background: transparent; border-color: rgba(255,255,255,0.16); color: rgba(255,255,255,0.4); }
.btn-win-retry:hover { background: rgba(255,255,255,0.05); color: #fff; }
.btn-win-next { background: rgba(0,255,136,0.1); border-color: #00ff88; color: #00ff88; }
.btn-win-next:hover { background: rgba(0,255,136,0.22); }
.btn-win-next:disabled { opacity: 0.28; cursor: default; }
.btn-win-exit {
    background: transparent;
    border-color: rgba(0,240,255,0.3);
    color: rgba(0,240,255,0.5);
    text-decoration: none;
    display: inline-block;
    padding: 9px 24px;
    font-family: 'Orbitron', sans-serif;
    font-size: 0.6rem; font-weight: 700;
    letter-spacing: 0.12em;
    clip-path: polygon(6px 0%, 100% 0%, calc(100% - 6px) 100%, 0% 100%);
    transition: background 0.2s, color 0.2s;
}
.btn-win-exit:hover { background: rgba(0,240,255,0.08); color: #00f0ff; }

/* ── Particules ── */
#particles-container { position: fixed; inset: 0; pointer-events: none; z-index: 201; }
@keyframes particleFly {
    0%   { transform: translate(0,0) scale(1); opacity: 1; }
    100% { transform: translate(var(--tx), var(--ty)) scale(0); opacity: 0; }
}
.particle { position: absolute; border-radius: 2px; animation: particleFly 1s ease-out forwards; }

/* ── Responsive ── */
@media (max-width: 900px) {
    #model-grid, #workshop-grid {
        grid-template-columns: repeat(10, 28px);
        grid-template-rows:    repeat(10, 28px);
    }
    .cell { width: 28px; height: 28px; }
    .vs-divider { display: none; }
}
@media (max-width: 600px) {
    #model-grid, #workshop-grid {
        grid-template-columns: repeat(10, 22px);
        grid-template-rows:    repeat(10, 22px);
    }
    .cell { width: 22px; height: 22px; }
    .status-bar { clip-path: none; flex-direction: column; }
    .status-item { border-right: none; border-bottom: 1px solid rgba(0,240,255,0.07); }
    .win-card { padding: 28px 20px; }
    .win-stats { gap: 18px; }
}
</style>

<div id="game-module">

    <!-- Titre -->
    <div class="game-header">
        <div class="game-title">ENIGMA<span>_</span>GRID</div>
        <div class="game-subtitle">&gt; XOR PUZZLE SYSTEM v2.0 — PLACE. SUPERPOSE. RÉSOUS.</div>
    </div>

    <!-- Sélecteur de niveaux -->
    <div class="level-selector">
        <button class="btn-level active" style="--btn-color:#00f0ff" onclick="loadLevel(0)">Nv.1 INITIÉ</button>
        <button class="btn-level" style="--btn-color:#4ade80" onclick="loadLevel(1)">Nv.2 ADEPTE</button>
        <button class="btn-level" style="--btn-color:#facc15" onclick="loadLevel(2)">Nv.3 STRATÈGE</button>
        <button class="btn-level" style="--btn-color:#fb923c" onclick="loadLevel(3)">Nv.4 TACTICIEN</button>
        <button class="btn-level" style="--btn-color:#f472b6" onclick="loadLevel(4)">Nv.5 MAÎTRE</button>
        <button class="btn-level" style="--btn-color:#a855f7" onclick="loadLevel(5)">Nv.6 ARCHIVISTE</button>
        <button class="btn-level" style="--btn-color:#ffd700" onclick="loadLevel(6)">Nv.7 ORACLE</button>
        <button class="btn-level" style="--btn-color:#ff4466" onclick="loadLevel(7)">Nv.8 LÉGENDE</button>
        <button class="btn-reset" onclick="clearWorkshop()">↺ Reset</button>
    </div>

    <!-- Description du niveau -->
    <div id="level-desc"></div>

    <!-- Barre de statut -->
    <div class="status-bar">
        <div class="status-item">
            <div class="status-label">Niveau</div>
            <div class="status-value" style="font-size:0.7rem; color:#fff" id="current-level-name">ENIGMA_GRID_01</div>
        </div>
        <div class="status-item">
            <div class="status-label">Chrono</div>
            <div class="status-value" id="chrono">00:00</div>
        </div>
        <div class="status-item">
            <div class="status-label">Meilleur Temps</div>
            <div class="status-value" id="best-time">--:--</div>
        </div>
        <div class="status-item">
            <div class="status-label">Meilleur Score</div>
            <div class="status-value" id="best-score">---</div>
        </div>
        <div class="status-item">
            <div class="status-label">Progression</div>
            <div class="status-value" id="progress-label">0%</div>
            <div class="progress-track"><div id="progress-bar-fill"></div></div>
        </div>
    </div>

    <!-- Zone de jeu -->
    <div id="game-container">

        <!-- Grille Cible -->
        <div class="grid-panel">
            <span class="panel-title target">◈ Cible</span>
            <div id="model-grid"></div>
        </div>

        <!-- Séparateur VS -->
        <div class="vs-divider">
            <div class="vs-line"></div>
            <div class="vs-label">VS</div>
            <div class="vs-line"></div>
        </div>

        <!-- Grille Atelier -->
        <div class="grid-panel">
            <div class="panel-header-row">
                <span class="panel-title workshop">◈ Atelier</span>
                <span id="move-counter">0 PLACEMENTS</span>
            </div>
            <div id="workshop-grid"></div>
            <p class="workshop-hint">[ Clic sur une pièce pour pivoter · Glisser-déposer ]</p>
            <div class="preview-legend">
                <div class="legend-item"><div class="legend-dot valid"></div> Placement valide</div>
                <div class="legend-item"><div class="legend-dot xor"></div> Annulation XOR</div>
                <div class="legend-item"><div class="legend-dot invalid"></div> Hors grille</div>
            </div>
        </div>

        <!-- Pièces -->
        <div class="pieces-panel">
            <span class="panel-title pieces">◈ Pièces</span>
            <div id="pieces-list"></div>
        </div>

    </div><!-- /#game-container -->

    <!-- Message de victoire -->
    <div id="win-message">
        <div id="particles-container"></div>
        <div class="win-card">
            <div class="win-title">▶ MODULE DÉVERROUILLÉ</div>
            <div class="win-subtitle">ENIGMA GRID — NIVEAU COMPLÉTÉ</div>

            <div class="win-stats">
                <div class="win-stat">
                    <div class="win-stat-label">Temps</div>
                    <div class="win-stat-value" id="final-time">00:00</div>
                </div>
                <div class="win-stat">
                    <div class="win-stat-label">Score</div>
                    <div class="win-stat-value" id="final-score">0</div>
                </div>
            </div>
            <div id="score-record"></div>

            <div class="win-actions">
                <button class="btn-win btn-win-retry" onclick="loadLevel(currentLevel)">↺ Rejouer</button>
                <button class="btn-win btn-win-next" id="btn-next-level" onclick="goNextLevel()">Niveau suivant ▶</button>
                <a href="../../layout/index.php" class="btn-win-exit">⎋ Quitter</a>
            </div>
        </div>
    </div>

</div><!-- /#game-module -->

<script src="game.js"></script>

<?php require_once '../../includes/footer.php'; ?>