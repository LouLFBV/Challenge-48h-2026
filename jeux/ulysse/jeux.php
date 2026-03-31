<?php
session_start();

// ─── Initialisation de la session ───────────────────────────────────────────
if (!isset($_SESSION['switches'])) {
    $_SESSION['switches'] = [false, false, false, false, false]; // 5 interrupteurs
    $_SESSION['attempts'] = 0;
    $_SESSION['solved']   = false;
    $_SESSION['message']  = '';
    $_SESSION['start_time'] = time();

    // Génération aléatoire des connexions : chaque interrupteur contrôle 1 ou 2 lampes
    // On génère une solution secrète (quelle combinaison allume toutes les lampes)
    // Matrice effets[switch][lamp] => true/false
    // On s'assure qu'il existe UNE combinaison qui allume les 3 lampes
    $_SESSION['effects'] = generateEffects();
    $_SESSION['solution'] = findSolution($_SESSION['effects']);
}


// ─── Actions POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action'])) {

        // Basculer un interrupteur
        if ($_POST['action'] === 'toggle' && isset($_POST['sw'])) {
            $i = (int)$_POST['sw'];
            if ($i >= 0 && $i < 5) {
                $_SESSION['switches'][$i] = !$_SESSION['switches'][$i];
            }
        }

        // Valider la combinaison (aller dans la salle des ampoules)
        // Valider la combinaison
        if ($_POST['action'] === 'validate') {
            $_SESSION['attempts']++;
            $lampsState = computeLamps($_SESSION['switches'], $_SESSION['effects']);
            
            if ($lampsState[0] && $lampsState[1] && $lampsState[2]) {
                $_SESSION['solved']  = true;
                $_SESSION['message'] = 'success';
                
                // --- CALCUL DU SCORE ---
                $endTime = time();
                $duration = $endTime - $_SESSION['start_time'];
                // Score = 500 - 1 point par seconde (minimum 0)
                $_SESSION['score'] = max(0, 500 - $duration);
            } else {
                $_SESSION['message'] = 'fail';
            }
        }

        // Recommencer
        if ($_POST['action'] === 'reset') {
            session_destroy();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ─── Fonctions ───────────────────────────────────────────────────────────────

/**
 * Génère une matrice d'effets aléatoire effects[5 switches][3 lamps]
 * Chaque interrupteur peut allumer ou éteindre une ou plusieurs lampes
 */
// ... après l'initialisation de la session et les actions POST ...

// Temps écoulé
$elapsed = time() - $_SESSION['start_time'];

// --- AJOUT : Vérification du temps limite (500s) ---
if (!$solved && $elapsed > 500) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
// --------------------------------------------------

$minutes = floor($elapsed / 60);
$seconds = $elapsed % 60;

function generateEffects(): array {
    // On génère jusqu'à trouver une config qui a au moins une solution
    do {
        $effects = [];
        for ($s = 0; $s < 5; $s++) {
            $effects[$s] = [];
            for ($l = 0; $l < 3; $l++) {
                // 50% de chance qu'un interrupteur affecte une lampe
                $effects[$s][$l] = (bool)rand(0, 1);
            }
        }
        $sol = findSolution($effects);
    } while ($sol === null);

    return $effects;
}

/**
 * Trouve UNE combinaison de switches qui allume les 3 lampes
 * Retourne un tableau [bool x5] ou null si impossible
 */
function findSolution(array $effects): ?array {
    // 2^5 = 32 combinaisons possibles
    for ($mask = 0; $mask < 32; $mask++) {
        $switches = [];
        for ($s = 0; $s < 5; $s++) {
            $switches[$s] = (bool)(($mask >> $s) & 1);
        }
        $lamps = computeLamps($switches, $effects);
        if ($lamps[0] && $lamps[1] && $lamps[2]) {
            return $switches;
        }
    }
    return null;
}

/**
 * Calcule l'état des 3 lampes selon les switches actifs
 * Une lampe est allumée si un nombre IMPAIR de switches qui l'affectent sont ON
 */
function computeLamps(array $switches, array $effects): array {
    $lamps = [false, false, false];
    for ($l = 0; $l < 3; $l++) {
        $count = 0;
        for ($s = 0; $s < 5; $s++) {
            if ($switches[$s] && $effects[$s][$l]) {
                $count++;
            }
        }
        $lamps[$l] = ($count % 2 === 1);
    }
    return $lamps;
}

// ─── Variables pour l'affichage ──────────────────────────────────────────────
$switches   = $_SESSION['switches'];
$effects    = $_SESSION['effects'];
$solved     = $_SESSION['solved'];
$attempts   = $_SESSION['attempts'];
$message    = $_SESSION['message'];

$lampsState = computeLamps($switches, $effects);

// Temps écoulé
$elapsed = time() - $_SESSION['start_time'];
$minutes = floor($elapsed / 60);
$seconds = $elapsed % 60;
$timeStr  = sprintf('%02d:%02d', $minutes, $seconds);

// Labels
$lampLabels   = ['A', 'B', 'C'];
$switchLabels = ['1', '2', '3', '4', '5'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Le Code à Interrupteurs — EnYgmes</title>
  <link rel="stylesheet" href="../../public/css/style.css">
  <style>
    /* ── Jeu spécifique ── */
    .game-wrapper {
      max-width: 900px;
      margin: 40px auto;
      padding: 0 20px 60px;
    }

    /* Titre */
    .game-title {
      text-align: center;
      margin-bottom: 8px;
    }
    .game-title h1 {
      font-family: var(--font-display);
      font-size: 1.8rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      color: var(--text-primary);
      text-shadow: 0 0 24px rgba(0,240,255,0.35);
    }
    .game-title h1 span { color: var(--neon-cyan); }
    .game-subtitle {
      font-family: var(--font-mono);
      font-size: 0.8rem;
      color: var(--text-dim);
      letter-spacing: 0.12em;
      text-align: center;
      margin-bottom: 28px;
    }

    /* Stats bar */
    .game-stats {
      display: flex;
      justify-content: center;
      gap: 32px;
      margin-bottom: 32px;
    }
    .stat-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 2px;
    }
    .stat-label {
      font-family: var(--font-mono);
      font-size: 0.65rem;
      color: var(--text-dim);
      letter-spacing: 0.12em;
      text-transform: uppercase;
    }
    .stat-value {
      font-family: var(--font-display);
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--neon-cyan);
    }

    /* Briefing */
    .briefing {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-left: 3px solid var(--neon-purple);
      border-radius: var(--radius);
      padding: 16px 20px;
      margin-bottom: 32px;
      font-family: var(--font-mono);
      font-size: 0.82rem;
      color: var(--text-muted);
      line-height: 1.7;
    }
    .briefing strong { color: var(--neon-purple); }

    /* Layout deux pièces */
    .rooms {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin-bottom: 28px;
    }

    .room {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 24px;
      position: relative;
      overflow: hidden;
    }
    .room::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 2px;
    }
    .room--switches::before {
      background: linear-gradient(90deg, transparent, var(--neon-cyan), transparent);
    }
    .room--lamps::before {
      background: linear-gradient(90deg, transparent, #f59e0b, transparent);
    }

    .room-label {
      font-family: var(--font-display);
      font-size: 0.65rem;
      font-weight: 700;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .room--switches .room-label { color: var(--neon-cyan); }
    .room--lamps   .room-label { color: #f59e0b; }

    .room-label::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    /* Grille interrupteurs */
    .switches-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      justify-content: center;
    }

    /* Interrupteur stylisé */
    .switch-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      background: none;
      border: none;
      padding: 0;
    }

    .switch-body {
      width: 52px;
      height: 80px;
      border-radius: 8px;
      border: 2px solid var(--border);
      background: var(--bg-panel);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: space-between;
      padding: 8px 6px;
      position: relative;
      transition: all 0.2s ease;
      cursor: pointer;
    }

    .switch-body:hover {
      border-color: var(--neon-cyan);
      box-shadow: 0 0 14px rgba(0,240,255,0.2);
    }

    .switch-body.on {
      border-color: var(--neon-cyan);
      background: rgba(0,240,255,0.06);
      box-shadow: 0 0 18px rgba(0,240,255,0.25);
    }

    .switch-pip {
      width: 28px;
      height: 32px;
      border-radius: 4px;
      transition: all 0.25s ease;
    }

    .switch-body.off .switch-pip {
      background: #374151;
      align-self: flex-end;
    }
    .switch-body.on .switch-pip {
      background: var(--neon-cyan);
      align-self: flex-start;
      box-shadow: 0 0 10px rgba(0,240,255,0.6);
    }

    .switch-indicator {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      transition: all 0.2s;
    }
    .switch-body.off .switch-indicator { background: #374151; }
    .switch-body.on  .switch-indicator {
      background: var(--neon-green);
      box-shadow: 0 0 6px var(--neon-green);
    }

    .switch-num {
      font-family: var(--font-display);
      font-size: 0.7rem;
      font-weight: 700;
      color: var(--text-dim);
      letter-spacing: 0.1em;
    }
    .switch-body.on + .switch-num {
      color: var(--neon-cyan);
    }

    /* Lampes */
    .lamps-grid {
      display: flex;
      justify-content: center;
      gap: 24px;
    }

    .lamp-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
    }

    .lamp-svg {
      transition: all 0.3s ease;
    }

    .lamp-label {
      font-family: var(--font-display);
      font-size: 0.7rem;
      font-weight: 700;
      color: var(--text-dim);
      letter-spacing: 0.1em;
    }

    /* Bouton valider */
    .action-bar {
      display: flex;
      justify-content: center;
      gap: 16px;
      margin-bottom: 24px;
    }

    .btn-validate {
      font-family: var(--font-display);
      font-size: 0.85rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      padding: 14px 32px;
      border-radius: var(--radius);
      border: 1px solid var(--neon-cyan);
      background: rgba(0,240,255,0.1);
      color: var(--neon-cyan);
      cursor: pointer;
      transition: all 0.2s;
      text-transform: uppercase;
    }
    .btn-validate:hover {
      background: rgba(0,240,255,0.2);
      box-shadow: 0 0 20px rgba(0,240,255,0.3);
    }

    .btn-reset {
      font-family: var(--font-mono);
      font-size: 0.8rem;
      padding: 14px 24px;
      border-radius: var(--radius);
      border: 1px solid var(--border);
      background: transparent;
      color: var(--text-muted);
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-reset:hover {
      border-color: #ef4444;
      color: #ef4444;
      background: rgba(239,68,68,0.05);
    }

    /* Message feedback */
    .feedback {
      text-align: center;
      padding: 16px;
      border-radius: var(--radius);
      font-family: var(--font-mono);
      font-size: 0.85rem;
      letter-spacing: 0.05em;
      margin-bottom: 20px;
      animation: fadeIn 0.4s ease;
    }
    @keyframes fadeIn { from { opacity:0; transform: translateY(6px); } to { opacity:1; transform: none; } }

    .feedback.success {
      background: rgba(57,255,20,0.08);
      border: 1px solid var(--neon-green);
      color: var(--neon-green);
    }
    .feedback.fail {
      background: rgba(239,68,68,0.08);
      border: 1px solid #ef4444;
      color: #ef4444;
    }

    /* Ecran victoire */
    .victory-screen {
      text-align: center;
      background: var(--bg-card);
      border: 1px solid var(--neon-green);
      border-radius: 12px;
      padding: 48px 32px;
      box-shadow: 0 0 40px rgba(57,255,20,0.1);
    }
    .victory-screen h2 {
      font-family: var(--font-display);
      font-size: 2rem;
      font-weight: 800;
      color: var(--neon-green);
      text-shadow: 0 0 30px rgba(57,255,20,0.5);
      margin-bottom: 8px;
      letter-spacing: 0.1em;
    }
    .victory-screen p {
      font-family: var(--font-mono);
      color: var(--text-muted);
      margin-bottom: 6px;
      font-size: 0.9rem;
    }
    .victory-screen .big-stat {
      font-family: var(--font-display);
      font-size: 1.5rem;
      color: var(--neon-cyan);
      margin: 20px 0;
    }

    /* Responsive */
    @media (max-width: 640px) {
      .rooms { grid-template-columns: 1fr; }
      .game-stats { gap: 20px; }
    }
  </style>
</head>
<body>

<!-- ── Header ─────────────────────────────────────────────────────────────── -->
<header class="site-header">
  <div class="header-inner">
    <div class="header-brand">
      <div class="brand-logo">
        <svg viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
          <polygon points="19,3 35,32 3,32" fill="none" stroke="#00f0ff" stroke-width="2"/>
          <text x="19" y="27" text-anchor="middle" font-family="monospace" font-size="13" fill="#00f0ff">?</text>
        </svg>
      </div>
      <div>
        <div class="brand-name">En<span>Ygmes</span></div>
        <span class="brand-tag">CHALLENGE 48H // 2026</span>
      </div>
    </div>
    <nav class="header-nav">
      <a href="../../layout/index.php" class="nav-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
        Accueil
      </a>
    </nav>
  </div>
</header>

<!-- ── Contenu ────────────────────────────────────────────────────────────── -->
<main class="game-wrapper">

  <div class="game-title">
    <h1>Le Code à <span>Interrupteurs</span></h1>
  </div>
  <div class="game-subtitle">// ÉNIGME LOGIQUE — NIVEAU INTERMÉDIAIRE</div>

  <!-- Stats -->
  <div class="game-stats">
    <div class="stat-item">
      <span class="stat-label">Tentatives</span>
      <span class="stat-value"><?= $attempts ?></span>
    </div>
    <div class="stat-item">
      <span class="stat-label">Temps</span>
      <span class="stat-value" id="timer"><?= $timeStr ?></span>
    </div>
    <div class="stat-item">
      <span class="stat-label">Statut</span>
      <span class="stat-value" style="color:<?= $solved ? 'var(--neon-green)' : 'var(--neon-purple)' ?>">
        <?= $solved ? 'RÉSOLU' : 'EN COURS' ?>
      </span>
    </div>
    <div class="stat-item">
  <span class="stat-label">Temps Restant</span>
  <span class="stat-value" id="timer" style="color: var(--neon-purple)">
    <?= sprintf('%02d:%02d', floor((500 - $elapsed) / 60), (500 - $elapsed) % 60) ?>
  </span>
</div>
  </div>

  <!-- Briefing -->
  <div class="briefing">
    <strong>// MISSION :</strong>
    Chaque interrupteur peut allumer ou éteindre une ou plusieurs ampoules.
    Trouve la combinaison qui allume <strong>les 3 ampoules simultanément</strong>.
  </div>

<?php if ($solved): ?>

  <div class="victory-screen">
    <h2>🔓 ACCÈS AUTORISÉ</h2>
    <p>Félicitations ! Les 3 ampoules sont allumées.</p>
    
    <div class="big-stat" style="font-size: 2.5rem; margin: 10px 0;">
        <?= $_SESSION['score'] ?> <span style="font-size: 1rem; color: var(--text-dim);">POINTS</span>
    </div>

    Résolu en <?= $timeStr ?: '00:00' ?>

    <form method="post" style="margin-top:28px;">
      <input type="hidden" name="action" value="reset">
      <button type="submit" class="btn-validate" style="border-color:var(--neon-green);color:var(--neon-green);background:rgba(57,255,20,0.08);">
        ↺ Rejouer
      </button>
    </form>
  </div>
<?php else: ?>

  <!-- ── DEUX PIÈCES ─────────────────────────────────────────────────────── -->
  <div class="rooms">

    <!-- Pièce 1 : Interrupteurs -->
    <div class="room room--switches">
      <div class="room-label"> Interrupteurs</div>
      <div class="switches-grid">
        <?php foreach ($switches as $i => $on): ?>
        <form method="post" style="display:inline;">
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="sw" value="<?= $i ?>">
          <button type="submit" class="switch-btn">
            <div class="switch-body <?= $on ? 'on' : 'off' ?>">
              <div class="switch-indicator"></div>
              <div class="switch-pip"></div>
            </div>
            <span class="switch-num"><?= $switchLabels[$i] ?></span>
          </button>
        </form>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Pièce 2 : Ampoules (on ne peut pas les voir sans valider) -->
    <div class="room room--lamps">
      <div class="room-label"> Ampoules</div>
      <div class="lamps-grid">
        <?php foreach ($lampLabels as $l => $label): ?>
          <?php $isOn = $lampsState[$l]; ?>
          <div class="lamp-item">
            <!-- Ampoule SVG -->
            <svg class="lamp-svg" width="64" height="80" viewBox="0 0 64 80" fill="none" xmlns="http://www.w3.org/2000/svg">
              <?php if ($isOn): ?>
                <!-- Lueur externe -->
                <circle cx="32" cy="30" r="26" fill="rgba(251,191,36,0.12)"/>
                <circle cx="32" cy="30" r="20" fill="rgba(251,191,36,0.2)"/>
              <?php endif; ?>
              <!-- Corps ampoule -->
              <path d="M20 28 C20 18 44 18 44 28 C44 36 38 42 38 48 H26 C26 42 20 36 20 28Z"
                fill="<?= $isOn ? '#fbbf24' : '#1e293b' ?>"
                stroke="<?= $isOn ? '#f59e0b' : '#334155' ?>"
                stroke-width="1.5"/>
              <?php if ($isOn): ?>
                <ellipse cx="32" cy="28" rx="7" ry="8" fill="rgba(255,255,255,0.3)"/>
              <?php endif; ?>
              <!-- Base -->
              <rect x="26" y="48" width="12" height="4" rx="1"
                fill="<?= $isOn ? '#d97706' : '#374151' ?>"
                stroke="<?= $isOn ? '#92400e' : '#4b5563' ?>" stroke-width="1"/>
              <rect x="27" y="52" width="10" height="3" rx="1"
                fill="<?= $isOn ? '#b45309' : '#374151' ?>"
                stroke="<?= $isOn ? '#78350f' : '#4b5563' ?>" stroke-width="1"/>
              <!-- Filament (quand éteint) -->
              <?php if (!$isOn): ?>
              <path d="M29 44 Q32 40 35 44" stroke="#4b5563" stroke-width="1.2" fill="none"/>
              <?php endif; ?>
              <!-- Rayons (quand allumé) -->
              <?php if ($isOn): ?>
              <line x1="32" y1="4"  x2="32" y2="10" stroke="#fbbf24" stroke-width="1.5" stroke-linecap="round" opacity="0.7"/>
              <line x1="48" y1="10" x2="44" y2="14" stroke="#fbbf24" stroke-width="1.5" stroke-linecap="round" opacity="0.7"/>
              <line x1="16" y1="10" x2="20" y2="14" stroke="#fbbf24" stroke-width="1.5" stroke-linecap="round" opacity="0.7"/>
              <line x1="54" y1="24" x2="48" y2="26" stroke="#fbbf24" stroke-width="1.5" stroke-linecap="round" opacity="0.5"/>
              <line x1="10" y1="24" x2="16" y2="26" stroke="#fbbf24" stroke-width="1.5" stroke-linecap="round" opacity="0.5"/>
              <?php endif; ?>
            </svg>
            <span class="lamp-label"><?= $label ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Note: dans la réalité les ampoules sont cachées jusqu'à validation -->
    </div>

  </div>

  <!-- Feedback -->
  <?php if ($message === 'fail'): ?>
  <div class="feedback fail">
    ✗ Mauvaise combinaison — les 3 ampoules ne sont pas toutes allumées. Recommence.
  </div>
  <?php endif; ?>

  <!-- Actions -->
  <div class="action-bar">
    <form method="post">
      <input type="hidden" name="action" value="validate">
      <button type="submit" class="btn-validate">
        ➜ Terminer
      </button>
    </form>
    <form method="post">
      <input type="hidden" name="action" value="reset">
      <button type="submit" class="btn-reset">↺ Recommencer</button>
    </form>
  </div>

<?php endif; ?>

</main>

<!-- Timer JS -->
<script>
(function() {
  <?php if (!$solved): ?>
  let timeLeft = <?= 500 - $elapsed ?>; // Calcul du temps restant
  const el = document.getElementById('timer');
  
  const countdown = setInterval(() => {
    timeLeft--;
    
    if (timeLeft <= 0) {
        clearInterval(countdown);
        window.location.reload(); // Recharge la page pour déclencher le reset PHP
        return;
    }

    const m = String(Math.floor(timeLeft / 60)).padStart(2, '0');
    const s = String(timeLeft % 60).padStart(2, '0');
    el.textContent = m + ':' + s;

    // Optionnel : Alerte visuelle quand il reste moins de 30s
    if (timeLeft < 30) {
        el.style.color = '#ef4444';
        el.style.textShadow = '0 0 10px #ef4444';
    }
  }, 1000);
  <?php endif; ?>
})();
</script>

</body>
</html>
