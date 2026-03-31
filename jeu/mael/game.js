// --- 1. DÉFINITION DES FORMES (Matrices codées) ---
// {r: ligne, c: colonne}. Le point (0,0) est le coin en haut à gauche de la pièce.
const SHAPES = {
    'DOT': [{r:0,c:0}],
    'SQUARE3x3': [{r:0,c:0},{r:0,c:1},{r:0,c:2}, {r:1,c:0},{r:1,c:1},{r:1,c:2}, {r:2,c:0},{r:2,c:1},{r:2,c:2}],
    'RECT5x2': [{r:0,c:0},{r:0,c:1},{r:0,c:2},{r:0,c:3},{r:0,c:4}, {r:1,c:0},{r:1,c:1},{r:1,c:2},{r:1,c:3},{r:1,c:4}],
    'LINE1x2': [{r:0,c:0},{r:1,c:0}]
};

// --- 2. DÉFINITION DES NIVEAUX ---
// On définit la Cible en disant au code quelles pièces superposer virtuellement.
const LEVELS = [
    {
        name: "Facile",
        // Solution cible : Un carré 3x3 percé au centre par un point
        targetSolution: [ {type: 'SQUARE3x3', r: 3, c: 3}, {type: 'DOT', r: 4, c: 4} ],
        // Pièces données au joueur
        inventory: ['SQUARE3x3', 'DOT']
    },
    {
        name: "Moyen",
        // Solution cible : Deux petits blocs séparés (Un rectangle 5x2 coupé par une ligne 1x2)
        targetSolution: [ {type: 'RECT5x2', r: 4, c: 2}, {type: 'LINE1x2', r: 4, c: 4} ],
        inventory: ['RECT5x2', 'LINE1x2']
    },
    {
        name: "Difficile",
        // Solution cible : Une croix (Signe +). Un carré 3x3 dont on efface les 4 coins.
        targetSolution: [
            {type: 'SQUARE3x3', r: 3, c: 3},
            {type: 'DOT', r: 3, c: 3}, // Haut-Gauche
            {type: 'DOT', r: 3, c: 5}, // Haut-Droite
            {type: 'DOT', r: 5, c: 3}, // Bas-Gauche
            {type: 'DOT', r: 5, c: 5}  // Bas-Droite
        ],
        inventory: ['SQUARE3x3', 'DOT', 'DOT', 'DOT', 'DOT']
    }
];

// --- VARIABLES GLOBALES ---
const gridSize = 10;
let currentLevel = 0;
let piecesOnGrid = []; // Garde en mémoire ce qui est posé
let targetGridLogic = []; // Grille de la cible pour la vérification

// --- 3. FONCTIONS UTILITAIRES ---

// Rotation mathématique (90 degrés)
function rotateShape(shapeCells, rotations) {
    let rotated = [...shapeCells];
    for (let i = 0; i < rotations; i++) {
        // Formule de rotation de matrice : (r, c) -> (c, -r)
        // On recale ensuite pour ne pas avoir de coordonnées négatives
        let temp = rotated.map(p => ({ r: p.c, c: -p.r }));
        let minC = Math.min(...temp.map(p => p.c));
        rotated = temp.map(p => ({ r: p.r, c: p.c - minC }));
    }
    return rotated;
}

// Calcule l'état logique (0 ou 1) d'une grille selon les pièces posées (Superposition Inverse)
function computeGridLogic(pieces) {
    let grid = Array.from({ length: gridSize }, () => Array(gridSize).fill(0));
    pieces.forEach(p => {
        const rotated = rotateShape(SHAPES[p.type], p.rotation || 0);
        rotated.forEach(cell => {
            const finalR = p.r + cell.r;
            const finalC = p.c + cell.c;
            if (finalR >= 0 && finalR < gridSize && finalC >= 0 && finalC < gridSize) {
                grid[finalR][finalC] += 1; // On incrémente
            }
        });
    });
    
    // Application de la règle : pair = 0 (éteint), impair = 1 (allumé)
    return grid.map(row => row.map(val => val % 2));
}

// --- 4. AFFICHAGE DES GRILLES ET INVENTAIRE ---

// Construit une grille HTML vide (Atelier ou Modèle)
function createHtmlGrid(containerId, isAtelier) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    for (let r = 0; r < gridSize; r++) {
        for (let c = 0; c < gridSize; c++) {
            const cell = document.createElement('div');
            cell.className = 'cell';
            cell.dataset.r = r;
            cell.dataset.c = c;
            
            if (isAtelier) {
                // CORRECTION : Indicateur visuel pour savoir où on lâche la pièce
                cell.ondragover = e => {
                    e.preventDefault();
                    cell.classList.add('drag-hover');
                };
                
                cell.ondragleave = e => {
                    cell.classList.remove('drag-hover');
                };
                
                cell.ondrop = e => {
                    e.preventDefault();
                    cell.classList.remove('drag-hover'); // Enlève la surbrillance
                    
                    try {
                        const pieceData = JSON.parse(e.dataTransfer.getData('application/json'));
                        piecesOnGrid.push({
                            type: pieceData.type,
                            rotation: pieceData.rotation,
                            r: r,
                            c: c
                        });
                        updateWorkshop();
                    } catch (error) {
                        console.error("Erreur de placement:", error);
                    }
                };
            }
            container.appendChild(cell);
        }
    }
}

// Crée les petites formes dans l'inventaire
function renderInventory(level) {
    const container = document.getElementById('pieces-list');
    container.innerHTML = '';
    
    LEVELS[level].inventory.forEach((type, index) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'piece-wrapper';
        wrapper.draggable = true;
        wrapper.dataset.type = type;
        wrapper.dataset.rotation = 0;

        // Fonction pour dessiner la mini-grille de l'inventaire
        const drawMiniGrid = () => {
            wrapper.innerHTML = `<span style="font-size:0.7rem; color:#a855f7;">${type}</span>`;
            const miniGrid = document.createElement('div');
            miniGrid.className = 'mini-grid';
            
            const shape = rotateShape(SHAPES[type], parseInt(wrapper.dataset.rotation));
            const maxR = Math.max(...shape.map(p => p.r));
            const maxC = Math.max(...shape.map(p => p.c));
            
            miniGrid.style.gridTemplateColumns = `repeat(${maxC + 1}, 15px)`;
            miniGrid.style.gridTemplateRows = `repeat(${maxR + 1}, 15px)`;
            
            // Remplissage de la mini-grille
            for(let r=0; r<=maxR; r++) {
                for(let c=0; c<=maxC; c++) {
                    const mCell = document.createElement('div');
                    mCell.className = 'mini-cell';
                    if (shape.some(p => p.r === r && p.c === c)) mCell.classList.add('filled');
                    miniGrid.appendChild(mCell);
                }
            }
            wrapper.appendChild(miniGrid);
        };

        drawMiniGrid();

        // ROTATION AU CLIC
        wrapper.onclick = () => {
            wrapper.dataset.rotation = (parseInt(wrapper.dataset.rotation) + 1) % 4;
            drawMiniGrid(); // Redessine la pièce tournée
        };

        // DRAG
        wrapper.ondragstart = e => {
            e.dataTransfer.setData('application/json', JSON.stringify({
                type: type,
                rotation: parseInt(wrapper.dataset.rotation)
            }));
        };

        container.appendChild(wrapper);
    });
}

// Met à jour l'affichage de l'Atelier
function updateWorkshop() {
    const logicGrid = computeGridLogic(piecesOnGrid);
    const container = document.getElementById('workshop-grid');
    
    // Efface tout
    container.querySelectorAll('.cell').forEach(c => c.classList.remove('is-active'));
    
    // Allume les bonnes cases
    for(let r = 0; r < gridSize; r++) {
        for(let c = 0; c < gridSize; c++) {
            if(logicGrid[r][c] === 1) {
                container.querySelector(`[data-r="${r}"][data-c="${c}"]`).classList.add('is-active');
            }
        }
    }
    
    checkWin(logicGrid);
}

// --- 5. LOGIQUE DE JEU ---

// Vider l'atelier
window.clearWorkshop = function() {
    piecesOnGrid = [];
    updateWorkshop();
    document.getElementById('win-message').style.display = 'none';
}

// Charger un niveau
window.loadLevel = function(index) {
    currentLevel = index;
    piecesOnGrid = [];
    document.getElementById('win-message').style.display = 'none';
    
    // Met à jour les boutons (CSS)
    document.querySelectorAll('.level-selector .btn-cyber:not(:last-child)').forEach((btn, i) => {
        btn.classList.toggle('active', i === index);
    });

    createHtmlGrid('model-grid', false);
    createHtmlGrid('workshop-grid', true);
    renderInventory(index);

    // Générer et afficher la cible
    targetGridLogic = computeGridLogic(LEVELS[index].targetSolution);
    const modelContainer = document.getElementById('model-grid');
    for(let r = 0; r < gridSize; r++) {
        for(let c = 0; c < gridSize; c++) {
            if(targetGridLogic[r][c] === 1) {
                modelContainer.querySelector(`[data-r="${r}"][data-c="${c}"]`).classList.add('is-active');
            }
        }
    }
}

// Vérifier la victoire (Comparaison pixel parfait)
function checkWin(workshopLogic) {
    let hasWon = true;
    for(let r = 0; r < gridSize; r++) {
        for(let c = 0; c < gridSize; c++) {
            if(workshopLogic[r][c] !== targetGridLogic[r][c]) {
                hasWon = false;
                break;
            }
        }
    }
    
    if(hasWon && piecesOnGrid.length > 0) {
        document.getElementById('win-message').style.display = 'block';
    }
}

// Lancement au chargement de la page
document.addEventListener('DOMContentLoaded', () => loadLevel(0));