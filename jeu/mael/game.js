document.addEventListener('DOMContentLoaded', () => {
    const gridElement = document.getElementById('workshop-grid');
    const gridSize = 10;
    let piecesOnGrid = []; // Stocke { type, r, c, rotation }

    // Définition des formes (coordonnées relatives)
    const shapes = {
        'L': [{r:0,c:0}, {r:1,c:0}, {r:2,c:0}, {r:2,c:1}],
        'T': [{r:0,c:0}, {r:0,c:1}, {r:0,c:2}, {r:1,c:1}],
        'I': [{r:0,c:0}, {r:1,c:0}, {r:2,c:0}, {r:3,c:0}],
        'SQUARE': [{r:0,c:0}, {r:0,c:1}, {r:1,c:0}, {r:1,c:1}]
    };

    // 1. Création de la grille
    function initGrid() {
        gridElement.innerHTML = '';
        for (let r = 0; r < gridSize; r++) {
            for (let c = 0; c < gridSize; c++) {
                const cell = document.createElement('div');
                cell.className = 'workshop-cell';
                cell.dataset.r = r;
                cell.dataset.c = c;

                cell.ondragover = e => e.preventDefault();
                cell.ondrop = e => {
                    const type = e.dataTransfer.getData('text/plain');
                    addPiece(type, r, c);
                };
                gridElement.appendChild(cell);
            }
        }
    }

    // 2. Rotation d'une forme (Mathématique)
    function rotateShape(shapeCells, angle) {
        // angle: 0, 90, 180, 270
        let rotated = [...shapeCells];
        const count = (angle / 90) % 4;
        for (let i = 0; i < count; i++) {
            rotated = rotated.map(p => ({ r: p.c, c: -p.r }));
        }
        return rotated;
    }

    // 3. Ajouter une pièce
    function addPiece(type, r, c) {
        piecesOnGrid.push({ type, r, c, rotation: 0 });
        render();
    }

    // 4. LE MOTEUR DE RENDU (Superposition Inverse)
    function render() {
        // Reset toutes les cellules (utilise tes classes du style.css)
        const allCells = document.querySelectorAll('.workshop-cell');
        allCells.forEach(cell => cell.classList.remove('is-active'));

        // Tableau de calcul (gridSize x gridSize) rempli de 0
        const logicGrid = Array.from({ length: gridSize }, () => Array(gridSize).fill(0));

        piecesOnGrid.forEach(p => {
            const rotatedCoords = rotateShape(shapes[p.type], p.rotation);
            rotatedCoords.forEach(offset => {
                const finalR = p.r + offset.r;
                const finalC = p.c + offset.c;
                if (finalR >= 0 && finalR < gridSize && finalC >= 0 && finalC < gridSize) {
                    logicGrid[finalR][finalC] += 1; // On incrémente chaque passage
                }
            });
        });

        // Application du résultat : SI IMPAIR (1, 3, 5) -> ALLUMÉ, SI PAIR (0, 2, 4) -> ÉTEINT
        for (let r = 0; r < gridSize; r++) {
            for (let c = 0; c < gridSize; c++) {
                if (logicGrid[r][c] % 2 !== 0) {
                    const target = document.querySelector(`[data-r="${r}"][data-c="${c}"]`);
                    target.classList.add('is-active'); // Ta classe CSS pour le noir/néon
                }
            }
        }
    }

    // 5. Gestion de la rotation (Touche R)
    window.addEventListener('keydown', (e) => {
        if (e.key.toLowerCase() === 'r' && piecesOnGrid.length > 0) {
            // Fait tourner la dernière pièce posée pour le test
            piecesOnGrid[piecesOnGrid.length - 1].rotation += 90;
            render();
        }
    });

    // Initialisation Drag & Drop inventaire
    document.querySelectorAll('.piece-item').forEach(item => {
        item.ondragstart = e => {
            e.dataTransfer.setData('text/plain', item.dataset.shape);
        };
    });

    initGrid();
});