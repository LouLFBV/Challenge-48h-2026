<?php
session_start();
require_once '../config/database.php'; 

// ─── Génération des puzzles ────────────────────────────────────────────────
function generatePuzzle(int $difficulty): array {
    /*
     * On choisit N objets avec des poids entiers.
     * On donne des équations de balance et on demande le poids d'un inconnu X.
     *
     * Difficulté :
     *   1 (facile)   – 2 objets connus, 1 inconnu simple
     *   2 (moyen)    – 3 objets, 2 équations, inférence à 2 étapes
     *   3 (difficile)– 4 objets, 3 équations, fractions possibles
     */

    $sets = [
        1 => [
            // [objets avec poids, équations, question, réponse]
            [
                'objects'   => ['Pomme'=>3, 'Orange'=>5, 'Banane'=>4],
                'clues'     => [
                    '2 Pomme(s) = 1 Orange(s) + 1 unité',
                    '1 Orange(s) + 1 Pomme(s) = 2 Banane(s)',
                ],
                'question'  => 'Quel est le poids d\'une <strong>Banane</strong> ?',
                'answer'    => 4,
                'unit'      => 'kg',
                'hint'      => 'Additionne les poids connus de chaque côté.',
                'balance_left'  => ['Pomme','Pomme'],
                'balance_right' => ['Orange','①'],   // ① = 1 unité
                'max_points'=> 100,
                'difficulty'=> 'facile',
            ],
            [
                'objects'   => ['Chat'=>6, 'Chien'=>10, 'Lapin'=>4],
                'clues'     => [
                    '1 Chat(s) + 1 Lapin(s) = 10 kg',
                    '1 Chien(s) = 1 Chat(s) + 4 kg',
                ],
                'question'  => 'Quel est le poids d\'un <strong>Chat</strong> ?',
                'answer'    => 6,
                'unit'      => 'kg',
                'hint'      => 'Utilise la première équation pour trouver Chat + Lapin.',
                'balance_left'  => ['Chat','Lapin'],
                'balance_right' => ['10kg'],
                'max_points'=> 100,
                'difficulty'=> 'facile',
            ],
        ],
        2 => [
            [
                'objects'   => ['Boîte rouge'=>7, 'Boîte bleue'=>5, 'Boîte verte'=>3],
                'clues'     => [
                    '2 Boîte(s) rouge(s) = 3 Boîte(s) bleue(s) - 1 kg',
                    '1 Boîte(s) bleue(s) + 2 Boîte(s) verte(s) = 11 kg',
                ],
                'question'  => 'Quel est le poids d\'une <strong>Boîte rouge</strong> ?',
                'answer'    => 7,
                'unit'      => 'kg',
                'hint'      => 'Résous d\'abord la seconde équation pour trouver la Boîte bleue.',
                'balance_left'  => ['Boîte rouge','Boîte rouge'],
                'balance_right' => ['Boîte bleue','Boîte bleue','Boîte bleue','-1kg'],
                'max_points'=> 200,
                'difficulty'=> 'moyen',
            ],
            [
                'objects'   => ['Poire'=>8, 'Mangue'=>12, 'Kiwi'=>4],
                'clues'     => [
                    '1 Mangue(s) = 3 Kiwi(s)',
                    '1 Poire(s) + 1 Kiwi(s) = 1 Mangue(s)',
                ],
                'question'  => 'Quel est le poids d\'une <strong>Poire</strong> ?',
                'answer'    => 8,
                'unit'      => 'kg',
                'hint'      => 'Commence par trouver Kiwi via la première équation.',
                'balance_left'  => ['Poire','Kiwi'],
                'balance_right' => ['Mangue'],
                'max_points'=> 200,
                'difficulty'=> 'moyen',
            ],
        ],
        3 => [
            [
                'objects'   => ['Cube A'=>9, 'Cube B'=>6, 'Cube C'=>4, 'Cube D'=>3],
                'clues'     => [
                    '2 Cube A = 3 Cube B',
                    '1 Cube B + 1 Cube C = 10 kg',
                    '2 Cube C = 1 Cube A - 1 kg',
                ],
                'question'  => 'Quel est le poids du <strong>Cube D</strong> ?<br><small>(Cube A − Cube B − Cube C = Cube D)</small>',
                'answer'    => 3,  // 9-6-? ... hint guides
                'unit'      => 'kg',
                'hint'      => 'Résous le système : A=9, B=6, C=4, D = A-B-C.',
                'balance_left'  => ['Cube A','Cube A'],
                'balance_right' => ['Cube B','Cube B','Cube B'],
                'max_points'=> 350,
                'difficulty'=> 'difficile',
            ],
        ],
    ];

    $pool = $sets[min($difficulty, 3)];
    $puzzle = $pool[array_rand($pool)];
    return $puzzle;
}

// ─── Logique AJAX ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // Démarrer une nouvelle partie
    if ($action === 'start') {
    $riddleId = isset($_POST['riddle_id']) ? (int)$_POST['riddle_id'] : 0;
    
    if ($riddleId > 0) {
        // C'est ici que le jeu va chercher les infos de la carte cliquée
        $stmt = $pdo->prepare("SELECT * FROM riddles WHERE id = ?");
        $stmt->execute([$riddleId]);
        $dbRiddle = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dbRiddle) {
            $puzzle = [
                'id_db'      => $dbRiddle['id'],
                'question'   => $dbRiddle['title'],
                'clues'      => explode(" | ", $dbRiddle['description']),
                'answer'     => (float)$dbRiddle['answer'],
                'max_points' => (int)$dbRiddle['max_points'],
                'difficulty' => $dbRiddle['difficulty'],
                    'unit'       => 'kg', 
                    'hint'       => 'Analyse bien les poids sur la balance.',
                    'balance_left'  => ['?'], 
                    'balance_right' => ['⚖']
                ];
            }
        }

        // Si pas d'ID ou énigme non trouvée, on génère de l'aléatoire (ton code actuel)
        if (!isset($puzzle)) {
            $diff = (int)($_POST['difficulty'] ?? 1);
            $puzzle = generatePuzzle($diff);
        }

        $_SESSION['current_puzzle']  = $puzzle;
        $_SESSION['puzzle_start']    = time();
        $_SESSION['puzzle_attempts'] = 0;

        echo json_encode(['ok' => true, 'puzzle' => $puzzle]);
        exit;
    }

    // Vérifier la réponse
    if ($action === 'answer') {
        $puzzle = $_SESSION['current_puzzle'] ?? null;
        if (!$puzzle) { echo json_encode(['ok'=>false,'msg'=>'Aucune partie en cours']); exit; }

        $_SESSION['puzzle_attempts']++;
        $attempts = $_SESSION['puzzle_attempts'];
        $elapsed  = time() - ($_SESSION['puzzle_start'] ?? time());
        $answer   = trim($_POST['answer'] ?? '');

        if ((float)$answer == $puzzle['answer']) {
            // Calcul du score : base − pénalité temps − pénalité tentatives
            $timePenalty     = min(floor($elapsed / 10) * 10, $puzzle['max_points'] * 0.7);
            $attemptPenalty  = ($attempts - 1) * 15;
            $score           = max(10, $puzzle['max_points'] - $timePenalty - $attemptPenalty);
            $score           = (int)round($score);

            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                
                // 1. On vérifie si l'énigme existe, sinon on la crée
                $stmt = $pdo->prepare("SELECT id FROM riddles WHERE title = ? LIMIT 1");
                $stmt->execute([$puzzle['question']]);
                $riddle = $stmt->fetch();

                if (!$riddle) {
                    $ins = $pdo->prepare("INSERT INTO riddles (title, description, answer, max_points, difficulty) VALUES (?, ?, ?, ?, ?)");
                    $ins->execute([
                        $puzzle['question'], 
                        implode(" | ", $puzzle['clues']), 
                        $puzzle['answer'], 
                        $puzzle['max_points'], 
                        $puzzle['difficulty']
                    ]);
                    $riddleId = $pdo->lastInsertId();
                } else {
                    $riddleId = $riddle['id'];
                }

                // 2. On insère le score (INSERT IGNORE évite les doublons grâce à ton UNIQUE KEY)
                $insScore = $pdo->prepare("INSERT IGNORE INTO user_scores_per_riddle (user_id, riddle_id, obtained_score) VALUES (?, ?, ?)");
                $insScore->execute([$userId, $riddleId, $score]);

                // 3. Mise à jour du score total de l'utilisateur
                // On ne rajoute les points que si l'insertion précédente a réussi (rowCount > 0)
                if ($insScore->rowCount() > 0) {
                    $updTotal = $pdo->prepare("UPDATE users SET total_score = total_score + ? WHERE id = ?");
                    $updTotal->execute([$score, $userId]);
                }
            }

            unset($_SESSION['current_puzzle'], $_SESSION['puzzle_start'], $_SESSION['puzzle_attempts']);
            echo json_encode(['ok'=>true,'correct'=>true,'score'=>$score,'elapsed'=>$elapsed,'attempts'=>$attempts]);
        } else {
            $remaining = max(0, $puzzle['answer'] - (float)$answer);
            $tooHigh   = (float)$answer > $puzzle['answer'];
            echo json_encode([
                'ok'      => true,
                'correct' => false,
                'hint'    => $tooHigh ? 'Trop lourd ! Essaie un nombre plus petit.' : 'Trop léger ! Essaie un nombre plus grand.',
                'attempts'=> $attempts,
            ]);
        }
        exit;
    }

    // Indice
    if ($action === 'hint') {
        $puzzle = $_SESSION['current_puzzle'] ?? null;
        echo json_encode(['ok'=>true,'hint'=> $puzzle['hint'] ?? 'Pas d\'indice disponible.']);
        exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Action inconnue']);
    exit;
}

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Master — Jeu</title>
    <link rel="stylesheet" href="games.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Crimson+Pro:ital,wght@0,300;0,400;1,300&display=swap" rel="stylesheet">
</head>
<body class="game-page">

<div class="bg-particles">
    <?php for($i=0; $i<12; $i++): ?>
    <span class="particle" style="--i:<?=$i?>"></span>
    <?php endfor; ?>
</div>


<!-- Écran d'accueil du jeu -->
<div id="screen-home" class="screen active">
    <div class="home-content">
        <div class="balance-logo">
            <div class="balance-beam-logo">
                <div class="pan pan-left"><span>?</span></div>
                <div class="pivot-logo">⚖</div>
                <div class="pan pan-right"><span>kg</span></div>
            </div>
        </div>
        <h2 class="home-title">Le Jeu de la Balance</h2>
        <p class="home-desc">
            Des objets mystérieux sont posés sur une balance. À toi de déduire leur poids
            grâce aux indices fournis. Plus tu es rapide, plus ton score est élevé !
        </p>
        <div class="difficulty-selector">
            <p>Choisissez votre niveau :</p>
            <div class="diff-buttons">
                <button class="diff-btn active" data-diff="1">
                    <span class="diff-icon">🌱</span>
                    <span class="diff-name">Facile</span>
                    <span class="diff-pts">100 pts max</span>
                </button>
                <button class="diff-btn" data-diff="2">
                    <span class="diff-icon">⚡</span>
                    <span class="diff-name">Moyen</span>
                    <span class="diff-pts">200 pts max</span>
                </button>
                <button class="diff-btn" data-diff="3">
                    <span class="diff-icon">🔥</span>
                    <span class="diff-name">Difficile</span>
                    <span class="diff-pts">350 pts max</span>
                </button>
            </div>
        </div>
        <button id="btn-start" class="btn-play">
            <span>Commencer l'énigme</span>
            <span class="btn-arrow">→</span>
        </button>
    </div>
</div>

<!-- Écran de jeu -->
<div id="screen-game" class="screen">
    <div class="game-layout">

        <!-- Panneau gauche : indices + question -->
        <aside class="game-sidebar">
            <div class="sidebar-header">
                <span class="diff-tag" id="diff-tag">Facile</span>
                <div class="timer-wrap">
                    <span class="timer-icon">⏱</span>
                    <span id="timer">0:00</span>
                </div>
            </div>

            <div class="clues-box">
                <h3 class="clues-title">Indices de la balance</h3>
                <ul id="clues-list" class="clues-list"></ul>
            </div>

            <div class="question-box">
                <h3 class="q-label">Question</h3>
                <p id="question-text" class="q-text"></p>
            </div>

            <div class="score-preview">
                <div class="score-meter">
                    <div id="score-bar" class="score-bar-fill"></div>
                </div>
                <div class="score-info">
                    <span>Score potentiel</span>
                    <span id="potential-score" class="score-num">—</span>
                </div>
            </div>
        </aside>

        <!-- Zone centrale : balance animée -->
        <main class="game-center">

            <div class="balance-container" id="balance-container">
                <!-- Support -->
                <div class="balance-support">
                    <div class="balance-post"></div>
                    <div class="balance-base"></div>
                </div>
                <!-- Fléau -->
                <div class="balance-beam" id="balance-beam">
                    <div class="balance-arm left-arm">
                        <div class="chain chain-l1"></div>
                        <div class="chain chain-l2"></div>
                        <div class="balance-pan left-pan" id="pan-left">
                            <div class="pan-items" id="pan-left-items"></div>
                        </div>
                    </div>
                    <div class="pivot-point"></div>
                    <div class="balance-arm right-arm">
                        <div class="chain chain-r1"></div>
                        <div class="chain chain-r2"></div>
                        <div class="balance-pan right-pan" id="pan-right">
                            <div class="pan-items" id="pan-right-items"></div>
                        </div>
                    </div>
                </div>
                <div class="balance-needle" id="balance-needle"></div>
            </div>

            <!-- Formulaire réponse -->
            <div class="answer-area">
                <div class="answer-feedback" id="answer-feedback"></div>
                <div class="answer-form">
                    <div class="input-group">
                        <input type="number" id="answer-input" 
                               class="answer-input" 
                               placeholder="Votre réponse…"
                               step="0.5" min="0">
                        <span class="input-unit" id="input-unit">kg</span>
                    </div>
                    <button id="btn-submit" class="btn-submit">Valider ⚖</button>
                </div>
                <div class="answer-actions">
                    <button id="btn-hint" class="btn-hint">💡 Indice</button>
                    <span class="attempts-count">Tentatives : <span id="attempts-num">0</span></span>
                </div>
            </div>
        </main>

    </div>
</div>

<!-- Écran résultat -->
<div id="screen-result" class="screen">
    <div class="result-card">
        <div class="result-icon" id="result-icon">✓</div>
        <h2 class="result-title">Énigme résolue !</h2>
        <div class="result-stats">
            <div class="result-stat">
                <span class="rs-label">Score obtenu</span>
                <span class="rs-value" id="res-score">—</span>
            </div>
            <div class="result-stat">
                <span class="rs-label">Temps</span>
                <span class="rs-value" id="res-time">—</span>
            </div>
            <div class="result-stat">
                <span class="rs-label">Tentatives</span>
                <span class="rs-value" id="res-attempts">—</span>
            </div>
        </div>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <p class="result-login-hint">
            <a href="login.php">Connectez-vous</a> pour sauvegarder votre score !
        </p>
        <?php endif; ?>
        <div class="result-actions">
            <button id="btn-replay" class="btn-play">Rejouer</button>
            <a href="index.php" class="btn-secondary">Classement</a>
        </div>
    </div>
</div>

<script>
// ══════════════════════════════════════════════════════════
//  Balance Master — Game Logic
// ══════════════════════════════════════════════════════════
// Récupère l'id dans l'URL (ex: game.php?id=12)
const urlParams = new URLSearchParams(window.location.search);
const riddleIdFromUrl = urlParams.get('id') || 0;

const $ = id => document.getElementById(id);

let selectedDiff = 1;
let timerInterval = null;
let startTime = null;
let maxPoints = 100;
let attempts = 0;

// ── Difficulté ─────────────────────────────────────────────
document.querySelectorAll('.diff-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.diff-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedDiff = parseInt(btn.dataset.diff);
    });
});

// ── Démarrer ────────────────────────────────────────────────
$('btn-start').addEventListener('click', startGame);

async function startGame() {
    const res = await fetch('game.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        // On ajoute riddle_id dans le corps de la requête
        body: `action=start&difficulty=${selectedDiff}&riddle_id=${riddleIdFromUrl}`
    });
    const data = await res.json();
    if (!data.ok) return;

    const puzzle = data.puzzle;
    maxPoints = puzzle.max_points;
    attempts = 0;
    $('attempts-num').textContent = '0';
    $('answer-feedback').textContent = '';
    $('answer-feedback').className = 'answer-feedback';
    $('answer-input').value = '';

    // Indices
    const ul = $('clues-list');
    ul.innerHTML = '';
    puzzle.clues.forEach((c, i) => {
        const li = document.createElement('li');
        li.className = 'clue-item';
        li.style.animationDelay = `${i * 0.15}s`;
        li.innerHTML = `<span class="clue-num">${i+1}</span><span>${c}</span>`;
        ul.appendChild(li);
    });

    // Question
    $('question-text').innerHTML = puzzle.question;
    $('input-unit').textContent = puzzle.unit;

    // Tag difficulté
    const tagMap = {facile:'🌱 Facile', moyen:'⚡ Moyen', difficile:'🔥 Difficile'};
    $('diff-tag').textContent = tagMap[puzzle.difficulty] || puzzle.difficulty;
    $('diff-tag').className = 'diff-tag diff-' + puzzle.difficulty;

    // Balance visuelle
    renderBalance(puzzle.balance_left, puzzle.balance_right);

    // Score potentiel
    updateScoreBar(1.0);
    $('potential-score').textContent = maxPoints + ' pts';

    // Timer
    clearInterval(timerInterval);
    startTime = Date.now();
    timerInterval = setInterval(updateTimer, 1000);

    showScreen('screen-game');
    setTimeout(() => $('answer-input').focus(), 400);
}

// ── Balance visuelle ────────────────────────────────────────
function renderBalance(left, right) {
    const leftEl  = $('pan-left-items');
    const rightEl = $('pan-right-items');
    leftEl.innerHTML  = '';
    rightEl.innerHTML = '';

    left.forEach(item => leftEl.appendChild(makeItem(item)));
    right.forEach(item => rightEl.appendChild(makeItem(item)));

    // Tilt selon le nombre d'éléments (simpliste mais visuel)
    const beam = $('balance-beam');
    const diff = left.length - right.length;
    beam.style.setProperty('--tilt', diff * 4 + 'deg');
}

function makeItem(label) {
    const el = document.createElement('div');
    el.className = 'pan-item';
    el.textContent = label;
    el.style.setProperty('--hue', Math.floor(Math.random()*360));
    return el;
}

// ── Timer ────────────────────────────────────────────────────
function updateTimer() {
    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    const m = Math.floor(elapsed / 60);
    const s = elapsed % 60;
    $('timer').textContent = `${m}:${s.toString().padStart(2,'0')}`;

    // Dégradation du score potentiel
    const timePenalty = Math.min(Math.floor(elapsed / 10) * 10, maxPoints * 0.7);
    const attemptPenalty = attempts * 15;
    const potential = Math.max(10, maxPoints - timePenalty - attemptPenalty);
    $('potential-score').textContent = potential + ' pts';
    updateScoreBar(potential / maxPoints);
}

function updateScoreBar(ratio) {
    const bar = $('score-bar');
    bar.style.width = (ratio * 100) + '%';
    bar.style.background = ratio > 0.6
        ? 'var(--gold)'
        : ratio > 0.3
            ? 'var(--amber)'
            : 'var(--red-soft)';
}

// ── Réponse ──────────────────────────────────────────────────
$('btn-submit').addEventListener('click', submitAnswer);
$('answer-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') submitAnswer();
});

async function submitAnswer() {
    const val = $('answer-input').value.trim();
    if (val === '') return;

    $('btn-submit').disabled = true;
    const res = await fetch('game.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=answer&answer=${encodeURIComponent(val)}`
    });
    const data = await res.json();
    $('btn-submit').disabled = false;

    if (data.correct) {
        clearInterval(timerInterval);
        showResult(data.score, data.elapsed, data.attempts);
    } else {
        attempts = data.attempts;
        $('attempts-num').textContent = attempts;
        const fb = $('answer-feedback');
        fb.textContent = data.hint;
        fb.className = 'answer-feedback wrong shake';
        setTimeout(() => fb.classList.remove('shake'), 600);
        // Balancer la balance
        $('balance-beam').classList.add('wobble');
        setTimeout(() => $('balance-beam').classList.remove('wobble'), 700);
        $('answer-input').value = '';
        $('answer-input').focus();
    }
}

// ── Indice ────────────────────────────────────────────────────
$('btn-hint').addEventListener('click', async () => {
    const res = await fetch('game.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=hint'
    });
    const data = await res.json();
    const fb = $('answer-feedback');
    fb.textContent = '💡 ' + data.hint;
    fb.className = 'answer-feedback hint-msg';
});

// ── Résultat ──────────────────────────────────────────────────
function showResult(score, elapsed, att) {
    const m = Math.floor(elapsed / 60);
    const s = elapsed % 60;
    $('res-score').textContent = score + ' pts';
    $('res-time').textContent = `${m}:${s.toString().padStart(2,'0')}`;
    $('res-attempts').textContent = att;

    // Icône selon score
    const icon = $('result-icon');
    if (score >= maxPoints * 0.8) { icon.textContent = '🏆'; }
    else if (score >= maxPoints * 0.5) { icon.textContent = '🥈'; }
    else { icon.textContent = '⚖'; }

    showScreen('screen-result');

    // Confetti léger
    launchConfetti();
}

$('btn-replay').addEventListener('click', () => showScreen('screen-home'));

// ── Écrans ────────────────────────────────────────────────────
function showScreen(id) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    $(id).classList.add('active');
}

// ── Confetti ──────────────────────────────────────────────────
function launchConfetti() {
    const container = document.body;
    for (let i = 0; i < 40; i++) {
        const c = document.createElement('div');
        c.className = 'confetti-piece';
        c.style.cssText = `
            left:${Math.random()*100}%;
            animation-delay:${Math.random()*1}s;
            background: hsl(${Math.random()*360},80%,60%);
            width:${6+Math.random()*8}px;
            height:${6+Math.random()*8}px;
        `;
        container.appendChild(c);
        setTimeout(() => c.remove(), 3000);
    }
}
</script>
</body>
</html>