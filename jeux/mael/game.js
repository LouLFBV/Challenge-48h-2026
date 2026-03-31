// =============================================
// ENIGMA GRID — game.js (version corrigée)
// =============================================

// --- 1. FORMES ---
const SHAPES = {
    'DOT':        [{r:0,c:0}],
    'LINE2':      [{r:0,c:0},{r:1,c:0}],
    'LINE3':      [{r:0,c:0},{r:1,c:0},{r:2,c:0}],
    'LINE4':      [{r:0,c:0},{r:1,c:0},{r:2,c:0},{r:3,c:0}],
    'LINE5':      [{r:0,c:0},{r:1,c:0},{r:2,c:0},{r:3,c:0},{r:4,c:0}],
    'SQUARE2x2':  [{r:0,c:0},{r:0,c:1},{r:1,c:0},{r:1,c:1}],
    'SQUARE3x3':  [{r:0,c:0},{r:0,c:1},{r:0,c:2},{r:1,c:0},{r:1,c:1},{r:1,c:2},{r:2,c:0},{r:2,c:1},{r:2,c:2}],
    'RECT3x2':    [{r:0,c:0},{r:0,c:1},{r:0,c:2},{r:1,c:0},{r:1,c:1},{r:1,c:2}],
    'RECT4x2':    [{r:0,c:0},{r:0,c:1},{r:0,c:2},{r:0,c:3},{r:1,c:0},{r:1,c:1},{r:1,c:2},{r:1,c:3}],
    'RECT5x2':    [{r:0,c:0},{r:0,c:1},{r:0,c:2},{r:0,c:3},{r:0,c:4},{r:1,c:0},{r:1,c:1},{r:1,c:2},{r:1,c:3},{r:1,c:4}],
    'L_SHAPE':    [{r:0,c:0},{r:1,c:0},{r:2,c:0},{r:2,c:1}],
    'J_SHAPE':    [{r:0,c:1},{r:1,c:1},{r:2,c:0},{r:2,c:1}],
    'T_SHAPE':    [{r:0,c:0},{r:0,c:1},{r:0,c:2},{r:1,c:1}],
    'S_SHAPE':    [{r:0,c:1},{r:0,c:2},{r:1,c:0},{r:1,c:1}],
    'Z_SHAPE':    [{r:0,c:0},{r:0,c:1},{r:1,c:1},{r:1,c:2}],
    'DIAMOND':    [{r:0,c:1},{r:1,c:0},{r:1,c:2},{r:2,c:1}],
    'CROSS':      [{r:0,c:1},{r:1,c:0},{r:1,c:1},{r:1,c:2},{r:2,c:1}],
    'CORNER':     [{r:0,c:0},{r:1,c:0},{r:1,c:1}],
    'BIG_L':      [{r:0,c:0},{r:1,c:0},{r:2,c:0},{r:3,c:0},{r:3,c:1},{r:3,c:2}],
    'U_SHAPE':    [{r:0,c:0},{r:0,c:2},{r:1,c:0},{r:1,c:1},{r:1,c:2}],
};

// --- 2. NIVEAUX ---
const LEVELS = [
    {
        name: "INITIÉ", subtitle: "Nv.1", color: "#00f0ff",
        description: "Pose la croix sur la grille.",
        targetSolution: [{type:'CROSS', r:4, c:4}],
        inventory: ['CROSS']
    },
    {
        name: "ADEPTE", subtitle: "Nv.2", color: "#4ade80",
        description: "Deux pièces, un seul alignement possible.",
        targetSolution: [
            {type:'RECT4x2', r:4, c:1},
            {type:'RECT4x2', r:4, c:5}
        ],
        inventory: ['RECT4x2', 'RECT4x2']
    },
    {
        name: "STRATÈGE", subtitle: "Nv.3", color: "#facc15",
        description: "La superposition s'annule : trouve le bon ordre.",
        targetSolution: [
            {type:'SQUARE3x3', r:3, c:3},
            {type:'SQUARE2x2', r:4, c:4}
        ],
        inventory: ['SQUARE3x3', 'SQUARE2x2', 'DOT']
    },
    {
        name: "TACTICIEN", subtitle: "Nv.4", color: "#fb923c",
        description: "Rotation obligatoire pour au moins une pièce.",
        targetSolution: [
            {type:'BIG_L',   r:2, c:2},
            {type:'BIG_L',   r:2, c:2, rotation:2},
            {type:'LINE4',   r:2, c:5}
        ],
        inventory: ['BIG_L', 'BIG_L', 'LINE4', 'LINE3']
    },
    {
        name: "MAÎTRE", subtitle: "Nv.5", color: "#f472b6",
        description: "Construis la croix par superpositions successives.",
        targetSolution: [
            {type:'RECT5x2', r:4, c:2},
            {type:'RECT5x2', r:4, c:2, rotation:1},
        ],
        inventory: ['RECT5x2', 'RECT5x2', 'CROSS', 'DOT']
    },
    {
        name: "ARCHIVISTE", subtitle: "Nv.6", color: "#a855f7",
        description: "Quatre pièces, patience et logique.",
        targetSolution: [
            {type:'U_SHAPE', r:2, c:3},
            {type:'U_SHAPE', r:2, c:3, rotation:2},
            {type:'LINE3',   r:5, c:4},
            {type:'LINE3',   r:5, c:4, rotation:1}
        ],
        inventory: ['U_SHAPE', 'U_SHAPE', 'LINE3', 'LINE3', 'DIAMOND']
    },
    {
        name: "ORACLE", subtitle: "Nv.7", color: "#ffd700",
        description: "Seul l'oracle voit la solution.",
        targetSolution: [
            {type:'SQUARE3x3', r:1, c:1},
            {type:'SQUARE3x3', r:1, c:5},
            {type:'SQUARE3x3', r:5, c:1},
            {type:'SQUARE3x3', r:5, c:5},
            {type:'SQUARE3x3', r:3, c:3}
        ],
        inventory: ['SQUARE3x3','SQUARE3x3','SQUARE3x3','SQUARE3x3','SQUARE3x3','DOT','LINE3']
    },
    {
        name: "LÉGENDE", subtitle: "Nv.8", color: "#ff4466",
        description: "Le niveau ultime. Chaque cellule compte.",
        targetSolution: [
            {type:'DIAMOND',  r:1, c:4},
            {type:'DIAMOND',  r:4, c:1},
            {type:'DIAMOND',  r:4, c:7},
            {type:'DIAMOND',  r:7, c:4},
            {type:'CROSS',    r:4, c:4},
            {type:'SQUARE2x2',r:3, c:3}
        ],
        inventory: ['DIAMOND','DIAMOND','DIAMOND','DIAMOND','CROSS','SQUARE2x2','T_SHAPE','LINE2']
    }
];

// --- ÉTAT GLOBAL ---
const GRID_SIZE = 10;
let currentLevel = 0;
let piecesOnGrid = [];
let targetGridLogic = [];
let timerInterval, secondsElapsed = 0, isGameWon = false;
let dragPreview = { type: null, rotation: 0 };

// --- SCORE ---
// Score basé sur le temps : plus vite = plus de points
// Base 10000 points, décroît avec le temps
function computeScore(levelIndex, seconds) {
    const baseScore = 10000;
    const levelBonus = (levelIndex + 1) * 500; // bonus par niveau
    const timePenalty = Math.floor(seconds * 8); // -8 pts/sec
    return Math.max(100, baseScore + levelBonus - timePenalty);
}

// --- 3. CHRONO ---
function startTimer() {
    clearInterval(timerInterval);
    secondsElapsed = 0;
    updateTimerDisplay();
    timerInterval = setInterval(() => {
        if (!isGameWon) { secondsElapsed++; updateTimerDisplay(); }
    }, 1000);
}
function stopTimer() { clearInterval(timerInterval); }
function updateTimerDisplay() {
    const el = document.getElementById('chrono');
    if (el) el.textContent = formatTime(secondsElapsed);
}
function formatTime(s) {
    return `${Math.floor(s/60).toString().padStart(2,'0')}:${(s%60).toString().padStart(2,'0')}`;
}

// --- 4. LOGIQUE GRILLE ---
function rotateShape(cells, rotations) {
    let rot = [...cells];
    for (let i = 0; i < (rotations || 0); i++) {
        let tmp = rot.map(p => ({r: p.c, c: -p.r}));
        const minC = Math.min(...tmp.map(p => p.c));
        rot = tmp.map(p => ({r: p.r, c: p.c - minC}));
    }
    return rot;
}

function computeGridLogic(pieces) {
    const grid = Array.from({length: GRID_SIZE}, () => Array(GRID_SIZE).fill(0));
    pieces.forEach(p => {
        rotateShape(SHAPES[p.type], p.rotation || 0).forEach(cell => {
            const fr = p.r + cell.r, fc = p.c + cell.c;
            if (fr >= 0 && fr < GRID_SIZE && fc >= 0 && fc < GRID_SIZE)
                grid[fr][fc]++;
        });
    });
    return grid.map(row => row.map(v => v % 2));
}

// --- 5. CONSTRUCTION GRILLE HTML ---
function buildGrid(containerEl, isAtelier) {
    containerEl.innerHTML = '';
    for (let r = 0; r < GRID_SIZE; r++) {
        for (let c = 0; c < GRID_SIZE; c++) {
            const cell = document.createElement('div');
            cell.className = 'cell';
            cell.dataset.r = r;
            cell.dataset.c = c;

            if (isAtelier) {
                cell.addEventListener('dragover', e => {
                    e.preventDefault();
                    if (dragPreview.type) showPreview(parseInt(cell.dataset.r), parseInt(cell.dataset.c));
                });
                cell.addEventListener('dragleave', e => {
                    if (!e.relatedTarget || !e.relatedTarget.closest || !e.relatedTarget.closest('#workshop-grid')) {
                        clearPreview();
                    }
                });
                cell.addEventListener('drop', e => {
                    e.preventDefault();
                    clearPreview();
                    if (isGameWon) return;
                    try {
                        const data = JSON.parse(e.dataTransfer.getData('application/json'));
                        piecesOnGrid.push({
                            type: data.type,
                            rotation: data.rotation,
                            r: parseInt(cell.dataset.r),
                            c: parseInt(cell.dataset.c)
                        });
                        updateWorkshop();
                        playDropSound();
                    } catch(err) { console.error(err); }
                });
            }
            containerEl.appendChild(cell);
        }
    }
}

// --- 6. PREVIEW ---
function showPreview(dropR, dropC) {
    const workshopEl = document.getElementById('workshop-grid');
    workshopEl.querySelectorAll('.cell').forEach(c => c.classList.remove('preview-valid','preview-invalid'));
    if (!dragPreview.type) return;
    const shape = rotateShape(SHAPES[dragPreview.type], dragPreview.rotation);
    let allValid = shape.every(cell => {
        const fr = dropR + cell.r, fc = dropC + cell.c;
        return fr >= 0 && fr < GRID_SIZE && fc >= 0 && fc < GRID_SIZE;
    });
    shape.forEach(cell => {
        const fr = dropR + cell.r, fc = dropC + cell.c;
        if (fr >= 0 && fr < GRID_SIZE && fc >= 0 && fc < GRID_SIZE) {
            const el = workshopEl.querySelector(`[data-r="${fr}"][data-c="${fc}"]`);
            if (el) el.classList.add(allValid ? 'preview-valid' : 'preview-invalid');
        }
    });
}

function clearPreview() {
    const wg = document.getElementById('workshop-grid');
    if (wg) wg.querySelectorAll('.cell').forEach(c => c.classList.remove('preview-valid','preview-invalid'));
}

// --- 7. INVENTAIRE ---
function renderInventory(levelIndex) {
    const container = document.getElementById('pieces-list');
    container.innerHTML = '';
    const desc = document.getElementById('level-desc');
    if (desc) desc.textContent = LEVELS[levelIndex].description || '';

    LEVELS[levelIndex].inventory.forEach(type => {
        const wrapper = document.createElement('div');
        wrapper.className = 'piece-wrapper';
        wrapper.draggable = true;
        wrapper.dataset.rotation = 0;

        const redraw = () => {
            wrapper.innerHTML = `<span class="piece-label">${type.replace(/_/g,' ')}</span>`;
            const shape = rotateShape(SHAPES[type], parseInt(wrapper.dataset.rotation));
            const maxR = Math.max(...shape.map(p => p.r));
            const maxC = Math.max(...shape.map(p => p.c));
            const mg = document.createElement('div');
            mg.className = 'mini-grid';
            mg.style.gridTemplateColumns = `repeat(${maxC+1}, 14px)`;
            mg.style.gridTemplateRows    = `repeat(${maxR+1}, 14px)`;
            for (let rr = 0; rr <= maxR; rr++) {
                for (let cc = 0; cc <= maxC; cc++) {
                    const mc = document.createElement('div');
                    mc.className = 'mini-cell' + (shape.some(p => p.r===rr && p.c===cc) ? ' filled' : '');
                    mg.appendChild(mc);
                }
            }
            wrapper.appendChild(mg);
            const hint = document.createElement('span');
            hint.className = 'piece-hint';
            hint.textContent = `↻  rot. ${parseInt(wrapper.dataset.rotation) * 90}°`;
            wrapper.appendChild(hint);
        };

        redraw();

        wrapper.addEventListener('click', () => {
            if (isGameWon) return;
            wrapper.dataset.rotation = (parseInt(wrapper.dataset.rotation) + 1) % 4;
            wrapper.classList.add('rotating');
            setTimeout(() => wrapper.classList.remove('rotating'), 280);
            redraw();
        });

        wrapper.addEventListener('dragstart', e => {
            if (isGameWon) { e.preventDefault(); return; }
            dragPreview.type     = type;
            dragPreview.rotation = parseInt(wrapper.dataset.rotation);
            wrapper.classList.add('dragging');
            e.dataTransfer.setData('application/json', JSON.stringify({
                type, rotation: parseInt(wrapper.dataset.rotation)
            }));
        });

        wrapper.addEventListener('dragend', () => {
            wrapper.classList.remove('dragging');
            dragPreview.type = null;
            clearPreview();
        });

        container.appendChild(wrapper);
    });
}

// --- 8. MISE À JOUR ATELIER ---
function updateWorkshop() {
    const logic = computeGridLogic(piecesOnGrid);
    const workshopEl = document.getElementById('workshop-grid');
    workshopEl.querySelectorAll('.cell').forEach(c =>
        c.classList.remove('is-active','cell-appear','preview-valid','preview-invalid')
    );
    for (let r = 0; r < GRID_SIZE; r++) {
        for (let c = 0; c < GRID_SIZE; c++) {
            if (logic[r][c] === 1) {
                const cell = workshopEl.querySelector(`[data-r="${r}"][data-c="${c}"]`);
                if (cell) cell.classList.add('is-active','cell-appear');
            }
        }
    }
    // Mise à jour du compteur de placements
    const counter = document.getElementById('move-counter');
    if (counter) counter.textContent = `${piecesOnGrid.length} PLACEMENT${piecesOnGrid.length !== 1 ? 'S' : ''}`;

    updateProgress(logic);
    checkWin(logic);
}

// --- 9. PROGRESSION ---
function updateProgress(workshopLogic) {
    let correct = 0, total = 0, falsePos = 0;
    for (let r = 0; r < GRID_SIZE; r++)
        for (let c = 0; c < GRID_SIZE; c++) {
            if (targetGridLogic[r][c] === 1) total++;
            if (workshopLogic[r][c] === 1 && targetGridLogic[r][c] === 1) correct++;
            if (workshopLogic[r][c] === 1 && targetGridLogic[r][c] === 0) falsePos++;
        }
    const score = total === 0 ? 0 : Math.max(0, Math.round((correct - falsePos * 0.5) / total * 100));
    const bar   = document.getElementById('progress-bar-fill');
    const label = document.getElementById('progress-label');
    if (bar)   bar.style.width = Math.min(score, 100) + '%';
    if (label) label.textContent = score + '%';
}

// --- 10. CHARGEMENT NIVEAU ---
window.loadLevel = function(index) {
    currentLevel = index;
    piecesOnGrid = [];
    isGameWon = false;
    dragPreview.type = null;

    const level = LEVELS[index];

    // Masquer le message de victoire
    const winMsg = document.getElementById('win-message');
    if (winMsg) {
        winMsg.style.display = 'none';
        const card = winMsg.querySelector('.win-card');
        if (card) card.classList.remove('win-appear');
    }

    // Nom du niveau
    const nameEl = document.getElementById('current-level-name');
    if (nameEl) nameEl.textContent = `ENIGMA_GRID_0${index+1}`;

    // Boutons actifs
    document.querySelectorAll('.btn-level').forEach((btn, i) => btn.classList.toggle('active', i === index));
    document.querySelectorAll('.btn-level').forEach((btn, i) => {
        btn.style.setProperty('--btn-color', LEVELS[i].color);
    });

    // Meilleur temps
    const stored = localStorage.getItem(`best_${index}`);
    const bestEl = document.getElementById('best-time');
    if (bestEl) bestEl.textContent = stored ? formatTime(parseInt(stored)) : '--:--';

    // Meilleur score
    const bestScore = localStorage.getItem(`best_score_${index}`);
    const bestScoreEl = document.getElementById('best-score');
    if (bestScoreEl) bestScoreEl.textContent = bestScore ? parseInt(bestScore).toLocaleString() + ' pts' : '---';

    // Bouton suivant
    const btnNext = document.getElementById('btn-next-level');
    if (btnNext) {
        const isLast = index === LEVELS.length - 1;
        btnNext.disabled = isLast;
        btnNext.style.opacity = isLast ? '0.3' : '1';
        btnNext.textContent = isLast ? '◈ Dernier niveau' : 'Niveau suivant ▶';
    }

    // Construire les grilles
    buildGrid(document.getElementById('model-grid'), false);
    buildGrid(document.getElementById('workshop-grid'), true);

    // Afficher la cible
    targetGridLogic = computeGridLogic(level.targetSolution);
    const modelEl = document.getElementById('model-grid');
    for (let r = 0; r < GRID_SIZE; r++)
        for (let c = 0; c < GRID_SIZE; c++)
            if (targetGridLogic[r][c] === 1)
                modelEl.querySelector(`[data-r="${r}"][data-c="${c}"]`).classList.add('is-active');

    // Compteur reset
    const counter = document.getElementById('move-counter');
    if (counter) counter.textContent = '0 PLACEMENTS';

    renderInventory(index);
    updateProgress(computeGridLogic([]));
    startTimer();
};

// --- 11. CONTRÔLES GLOBAUX ---
window.clearWorkshop = function() {
    if (isGameWon) return;
    piecesOnGrid = [];
    clearPreview();
    updateWorkshop();
};

window.goNextLevel = function() {
    if (currentLevel < LEVELS.length - 1) loadLevel(currentLevel + 1);
};

// --- 12. VICTOIRE ---
function checkWin(logic) {
    if (piecesOnGrid.length === 0) return;
    for (let r = 0; r < GRID_SIZE; r++)
        for (let c = 0; c < GRID_SIZE; c++)
            if (logic[r][c] !== targetGridLogic[r][c]) return;

    isGameWon = true;
    stopTimer();

    // Meilleur temps
    const key = `best_${currentLevel}`;
    const prev = parseInt(localStorage.getItem(key));
    if (!prev || secondsElapsed < prev) {
        localStorage.setItem(key, secondsElapsed);
        const bestEl = document.getElementById('best-time');
        if (bestEl) bestEl.textContent = formatTime(secondsElapsed);
    }

    // Calcul du score
    const finalScore = computeScore(currentLevel, secondsElapsed);
    const scoreKey = `best_score_${currentLevel}`;
    const prevScore = parseInt(localStorage.getItem(scoreKey)) || 0;
    const isNewRecord = finalScore > prevScore;
    if (isNewRecord) {
        localStorage.setItem(scoreKey, finalScore);
        const bestScoreEl = document.getElementById('best-score');
        if (bestScoreEl) bestScoreEl.textContent = finalScore.toLocaleString() + ' pts';
    }

    // Afficher le résultat
    const finalTimeEl = document.getElementById('final-time');
    if (finalTimeEl) finalTimeEl.textContent = formatTime(secondsElapsed);

    const finalScoreEl = document.getElementById('final-score');
    if (finalScoreEl) finalScoreEl.textContent = finalScore.toLocaleString();

    const scoreRecordEl = document.getElementById('score-record');
    if (scoreRecordEl) scoreRecordEl.textContent = isNewRecord ? '★ NOUVEAU RECORD !' : `Record : ${prevScore.toLocaleString()} pts`;

    const winMsg = document.getElementById('win-message');
    if (winMsg) {
        winMsg.style.display = 'flex';
        requestAnimationFrame(() => {
            const card = winMsg.querySelector('.win-card');
            if (card) card.classList.add('win-appear');
        });
    }

    launchParticles();
    playWinSound();
    sendScore(currentLevel, secondsElapsed, finalScore);
}

// --- 13. ENVOI SCORE ---
function sendScore(level, time, score) {
    fetch('save_score.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({level, time_seconds: time, score})
    }).catch(() => {});
}

// --- 14. SONS ---
function playDropSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator(), gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.setValueAtTime(330, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(550, ctx.currentTime + 0.07);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
        osc.start(); osc.stop(ctx.currentTime + 0.12);
    } catch(e) {}
}

function playWinSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [523, 659, 784, 1047].forEach((freq, i) => {
            const osc = ctx.createOscillator(), gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.value = freq;
            const t = ctx.currentTime + i * 0.14;
            gain.gain.setValueAtTime(0.16, t);
            gain.gain.exponentialRampToValueAtTime(0.001, t + 0.35);
            osc.start(t); osc.stop(t + 0.35);
        });
    } catch(e) {}
}

// --- 15. PARTICULES ---
function launchParticles() {
    const c = document.getElementById('particles-container');
    if (!c) return;
    c.innerHTML = '';
    const colors = ['#00f0ff','#a855f7','#ffd700','#ff6b35','#00ff88','#f472b6'];
    for (let i = 0; i < 80; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const angle = Math.random() * Math.PI * 2;
        const dist  = 120 + Math.random() * 300;
        p.style.cssText = `
            left:50%; top:45%;
            width:${3 + Math.random()*8}px;
            height:${3 + Math.random()*8}px;
            background:${colors[i % colors.length]};
            --tx:${Math.cos(angle)*dist}px;
            --ty:${Math.sin(angle)*dist}px;
            animation-delay:${Math.random()*0.25}s;
            animation-duration:${0.7 + Math.random()*0.7}s;
        `;
        c.appendChild(p);
        setTimeout(() => p.remove(), 2000);
    }
}

// --- 16. INIT ---
document.addEventListener('DOMContentLoaded', () => {
    const wg = document.getElementById('workshop-grid');
    if (wg) {
        wg.addEventListener('dragleave', e => {
            if (!e.relatedTarget || !wg.contains(e.relatedTarget)) {
                clearPreview();
            }
        });
    }
    loadLevel(0);
});