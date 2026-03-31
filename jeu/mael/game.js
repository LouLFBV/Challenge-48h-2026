// =============================================
// ENIGMA GRID — game.js (version améliorée)
// =============================================

// --- 1. DÉFINITION DES FORMES ---
const SHAPES = {
    'DOT':       [{r:0,c:0}],
    'SQUARE3x3': [{r:0,c:0},{r:0,c:1},{r:0,c:2},{r:1,c:0},{r:1,c:1},{r:1,c:2},{r:2,c:0},{r:2,c:1},{r:2,c:2}],
    'RECT5x2':   [{r:0,c:0},{r:0,c:1},{r:0,c:2},{r:0,c:3},{r:0,c:4},{r:1,c:0},{r:1,c:1},{r:1,c:2},{r:1,c:3},{r:1,c:4}],
    'LINE1x2':   [{r:0,c:0},{r:1,c:0}],
    'LINE1x3':   [{r:0,c:0},{r:1,c:0},{r:2,c:0}],
    'L_SHAPE':   [{r:0,c:0},{r:1,c:0},{r:2,c:0},{r:2,c:1}],
    'T_SHAPE':   [{r:0,c:0},{r:0,c:1},{r:0,c:2},{r:1,c:1}],
    'DIAMOND':   [{r:0,c:1},{r:1,c:0},{r:1,c:2},{r:2,c:1}]
};

// --- 2. DÉFINITION DES NIVEAUX ---
const LEVELS = [
    {
        name: "INITIÉ",
        subtitle: "Niveau 1",
        color: "#00f0ff",
        targetSolution: [
            {type:'SQUARE3x3', r:3, c:3},
            {type:'DOT', r:4, c:4}
        ],
        inventory: ['SQUARE3x3','DOT']
    },
    {
        name: "ADEPTE",
        subtitle: "Niveau 2",
        color: "#a855f7",
        targetSolution: [
            {type:'RECT5x2', r:4, c:2},
            {type:'LINE1x2', r:4, c:4}
        ],
        inventory: ['RECT5x2','LINE1x2']
    },
    {
        name: "MAÎTRE",
        subtitle: "Niveau 3",
        color: "#ff6b35",
        targetSolution: [
            {type:'SQUARE3x3', r:3, c:3},
            {type:'DOT', r:3, c:3},{type:'DOT', r:3, c:5},
            {type:'DOT', r:5, c:3},{type:'DOT', r:5, c:5}
        ],
        inventory: ['SQUARE3x3','DOT','DOT','DOT','DOT']
    },
    {
        name: "ORACLE",
        subtitle: "Niveau 4",
        color: "#ffd700",
        targetSolution: [
            {type:'DIAMOND', r:2, c:3},
            {type:'T_SHAPE', r:6, c:3},
            {type:'DOT', r:4, c:4}
        ],
        inventory: ['DIAMOND','T_SHAPE','DOT']
    }
];

// --- VARIABLES GLOBALES ---
const GRID_SIZE = 10;
let currentLevel = 0;
let piecesOnGrid = [];
let targetGridLogic = [];
let timerInterval;
let secondsElapsed = 0;
let isGameWon = false;
let bestTimes = {}; // stockage local des meilleurs temps

// --- 3. CHRONOMÈTRE ---
function startTimer() {
    clearInterval(timerInterval);
    secondsElapsed = 0;
    updateTimerDisplay();
    timerInterval = setInterval(() => {
        if (!isGameWon) {
            secondsElapsed++;
            updateTimerDisplay();
        }
    }, 1000);
}

function stopTimer() { clearInterval(timerInterval); }

function updateTimerDisplay() {
    const m = Math.floor(secondsElapsed / 60).toString().padStart(2,'0');
    const s = (secondsElapsed % 60).toString().padStart(2,'0');
    const el = document.getElementById('chrono');
    if (el) el.textContent = `${m}:${s}`;
}

// --- 4. UTILITAIRES ---
function rotateShape(shapeCells, rotations) {
    let rotated = [...shapeCells];
    for (let i = 0; i < rotations; i++) {
        let temp = rotated.map(p => ({r: p.c, c: -p.r}));
        let minC = Math.min(...temp.map(p => p.c));
        rotated = temp.map(p => ({r: p.r, c: p.c - minC}));
    }
    return rotated;
}

function computeGridLogic(pieces) {
    const grid = Array.from({length: GRID_SIZE}, () => Array(GRID_SIZE).fill(0));
    pieces.forEach(p => {
        const rotated = rotateShape(SHAPES[p.type], p.rotation || 0);
        rotated.forEach(cell => {
            const fr = p.r + cell.r;
            const fc = p.c + cell.c;
            if (fr >= 0 && fr < GRID_SIZE && fc >= 0 && fc < GRID_SIZE) {
                grid[fr][fc] += 1;
            }
        });
    });
    return grid.map(row => row.map(v => v % 2));
}

function formatTime(s) {
    return `${Math.floor(s/60).toString().padStart(2,'0')}:${(s%60).toString().padStart(2,'0')}`;
}

// --- 5. CRÉATION DES GRILLES HTML ---
function createHtmlGrid(containerId, isAtelier) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
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
}

// --- 6. INVENTAIRE DE PIÈCES ---
function renderInventory(levelIndex) {
    const container = document.getElementById('pieces-list');
    container.innerHTML = '';
    const level = LEVELS[levelIndex];

    LEVELS[levelIndex].inventory.forEach((type) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'piece-wrapper';
        wrapper.draggable = true;
        wrapper.dataset.type = type;
        wrapper.dataset.rotation = 0;

        const drawMiniGrid = () => {
            wrapper.innerHTML = `<span class="piece-label">${type.replace('_',' ')}</span>`;
            const shape = rotateShape(SHAPES[type], parseInt(wrapper.dataset.rotation));
            const maxR = Math.max(...shape.map(p => p.r));
            const maxC = Math.max(...shape.map(p => p.c));
            const miniGrid = document.createElement('div');
            miniGrid.className = 'mini-grid';
            miniGrid.style.gridTemplateColumns = `repeat(${maxC+1}, 14px)`;
            miniGrid.style.gridTemplateRows = `repeat(${maxR+1}, 14px)`;
            for (let r = 0; r <= maxR; r++) {
                for (let c = 0; c <= maxC; c++) {
                    const mc = document.createElement('div');
                    mc.className = 'mini-cell';
                    if (shape.some(p => p.r === r && p.c === c)) mc.classList.add('filled');
                    miniGrid.appendChild(mc);
                }
            }
            wrapper.appendChild(miniGrid);
            const hint = document.createElement('span');
            hint.className = 'piece-hint';
            hint.textContent = '↻ pivoter';
            wrapper.appendChild(hint);
        };

        drawMiniGrid();

        wrapper.addEventListener('click', () => {
            if (isGameWon) return;
            wrapper.dataset.rotation = (parseInt(wrapper.dataset.rotation)+1) % 4;
            wrapper.classList.add('rotating');
            setTimeout(() => wrapper.classList.remove('rotating'), 300);
            drawMiniGrid();
        });

        wrapper.addEventListener('dragstart', e => {
            if (isGameWon) { e.preventDefault(); return; }
            wrapper.classList.add('dragging');
            e.dataTransfer.setData('application/json', JSON.stringify({
                type,
                rotation: parseInt(wrapper.dataset.rotation)
            }));
        });

        wrapper.addEventListener('dragend', () => wrapper.classList.remove('dragging'));

        container.appendChild(wrapper);
    });
}

// --- 7. MISE À JOUR DE L'ATELIER ---
function updateWorkshop() {
    const logicGrid = computeGridLogic(piecesOnGrid);
    const container = document.getElementById('workshop-grid');
    container.querySelectorAll('.cell').forEach(c => {
        c.classList.remove('is-active','cell-appear');
    });
    for (let r = 0; r < GRID_SIZE; r++) {
        for (let c = 0; c < GRID_SIZE; c++) {
            if (logicGrid[r][c] === 1) {
                const cell = container.querySelector(`[data-r="${r}"][data-c="${c}"]`);
                cell.classList.add('is-active','cell-appear');
            }
        }
    }
    updateComparisonHUD(logicGrid);
    checkWin(logicGrid);
}

// --- 8. HUD DE COMPARAISON ---
function updateComparisonHUD(workshopGrid) {
    let correct = 0, total = 0;
    for (let r = 0; r < GRID_SIZE; r++) {
        for (let c = 0; c < GRID_SIZE; c++) {
            if (targetGridLogic[r][c] === 1) total++;
            if (workshopGrid[r][c] === 1 && targetGridLogic[r][c] === 1) correct++;
        }
    }
    const pct = total === 0 ? 0 : Math.round((correct / total) * 100);
    const bar = document.getElementById('progress-bar-fill');
    const label = document.getElementById('progress-label');
    if (bar) bar.style.width = pct + '%';
    if (label) label.textContent = pct + '%';
}

// --- 9. CONTRÔLES GLOBAUX ---
window.clearWorkshop = function() {
    if (isGameWon) return;
    piecesOnGrid = [];
    updateWorkshop();
};

window.loadLevel = function(index) {
    currentLevel = index;
    piecesOnGrid = [];
    isGameWon = false;

    const level = LEVELS[index];
    document.getElementById('win-message').style.display = 'none';
    document.body.dataset.levelColor = level.color;

    // Mise à jour des boutons
    document.querySelectorAll('.btn-level').forEach((btn, i) => {
        btn.classList.toggle('active', i === index);
        btn.style.setProperty('--btn-color', LEVELS[i].color);
    });

    // Mise à jour du titre de niveau
    const titleEl = document.getElementById('level-title');
    if (titleEl) {
        titleEl.textContent = level.name;
        titleEl.style.color = level.color;
    }

    // Meilleur temps affiché
    updateBestTime(index);

    createHtmlGrid('model-grid', false);
    createHtmlGrid('workshop-grid', true);
    renderInventory(index);

    targetGridLogic = computeGridLogic(level.targetSolution);

    const modelContainer = document.getElementById('model-grid');
    for (let r = 0; r < GRID_SIZE; r++) {
        for (let c = 0; c < GRID_SIZE; c++) {
            if (targetGridLogic[r][c] === 1) {
                modelContainer.querySelector(`[data-r="${r}"][data-c="${c}"]`).classList.add('is-active');
            }
        }
    }

    updateComparisonHUD(computeGridLogic([]));
    startTimer();
};

// --- 10. VÉRIFICATION DE LA VICTOIRE ---
function checkWin(workshopLogic) {
    for (let r = 0; r < GRID_SIZE; r++) {
        for (let c = 0; c < GRID_SIZE; c++) {
            if (workshopLogic[r][c] !== targetGridLogic[r][c]) return;
        }
    }
    if (piecesOnGrid.length === 0) return;

    isGameWon = true;
    stopTimer();

    // Sauvegarder meilleur temps
    const key = `best_${currentLevel}`;
    const prev = bestTimes[key];
    if (!prev || secondsElapsed < prev) {
        bestTimes[key] = secondsElapsed;
        localStorage.setItem(key, secondsElapsed);
    }

    const finalTimeStr = formatTime(secondsElapsed);
    const winEl = document.getElementById('win-message');
    document.getElementById('final-time').textContent = finalTimeStr;
    winEl.style.display = 'flex';
    winEl.classList.add('win-appear');

    // Explosion de particules
    launchParticles();
    playWinSound();

    // Envoyer le temps au serveur
    sendScoreToServer(currentLevel, secondsElapsed);
}

// --- 11. MEILLEUR TEMPS LOCAL ---
function updateBestTime(index) {
    const key = `best_${index}`;
    const stored = localStorage.getItem(key);
    const el = document.getElementById('best-time');
    if (el) {
        el.textContent = stored ? `Meilleur : ${formatTime(parseInt(stored))}` : 'Meilleur : --:--';
    }
    if (stored) bestTimes[key] = parseInt(stored);
}

// --- 12. ENVOI SCORE (AJAX) ---
function sendScoreToServer(levelIndex, time) {
    fetch('save_score.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({level: levelIndex, time_seconds: time})
    }).catch(() => {}); // Silencieux si pas encore implémenté
}

// --- 13. EFFETS SONORES (WebAudio API) ---
function playDropSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.setValueAtTime(440, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
        osc.start(); osc.stop(ctx.currentTime + 0.2);
    } catch(e) {}
}

function playWinSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const notes = [523, 659, 784, 1047];
        notes.forEach((freq, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.2, ctx.currentTime + i * 0.15);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.15 + 0.4);
            osc.start(ctx.currentTime + i * 0.15);
            osc.stop(ctx.currentTime + i * 0.15 + 0.4);
        });
    } catch(e) {}
}

// --- 14. PARTICULES DE VICTOIRE ---
function launchParticles() {
    const container = document.getElementById('particles-container');
    if (!container) return;
    container.innerHTML = '';
    const colors = ['#00f0ff','#a855f7','#ffd700','#ff6b35','#00ff88'];
    for (let i = 0; i < 60; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.top = Math.random() * 100 + '%';
        p.style.background = colors[Math.floor(Math.random()*colors.length)];
        p.style.width = p.style.height = (4 + Math.random()*8) + 'px';
        p.style.animationDelay = Math.random() * 0.5 + 's';
        p.style.animationDuration = (0.8 + Math.random() * 0.8) + 's';
        container.appendChild(p);
        setTimeout(() => p.remove(), 2000);
    }
}

// --- 15. INITIALISATION ---
document.addEventListener('DOMContentLoaded', () => {
    // Charger meilleurs temps depuis localStorage
    LEVELS.forEach((_, i) => {
        const stored = localStorage.getItem(`best_${i}`);
        if (stored) bestTimes[`best_${i}`] = parseInt(stored);
    });
    loadLevel(0);
});