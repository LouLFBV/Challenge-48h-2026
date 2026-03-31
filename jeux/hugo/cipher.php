<?php
session_start();
require '../../config/database.php';

const CIPHER_BASE_SCORE   = 1000;
const CIPHER_MIN_SCORE    = 100;
const CIPHER_HINT_PENALTY = 50;
const CIPHER_WRONG_PENALTY = 25;

// ─── Initialisation du riddle CIPHER en BDD ──────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM riddles WHERE title = 'CIPHER — SYSTEM://BREACH' LIMIT 1");
$stmt->execute();
$riddle = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$riddle) {
    $stmt = $pdo->prepare("INSERT INTO riddles (title, description, answer, max_points, difficulty)
                            VALUES ('CIPHER — SYSTEM://BREACH', 'Escape game hacker en 7 layers.', '2479gx', 1000, 'difficile')");
    $stmt->execute();
    $riddleId = (int)$pdo->lastInsertId();
} else {
    $riddleId = (int)$riddle['id'];
}

// ─── Réinitialisation ────────────────────────────────────────────────────────
if (isset($_GET['reset'])) {
    unset($_SESSION['layer'], $_SESSION['fragments'], $_SESSION['started'], $_SESSION['hints_used'],
      $_SESSION['hints_layers'], $_SESSION['wrong_answers'], $_SESSION['cipher_score'], $_SESSION['cipher_seconds'],
      $_SESSION['cipher_hints'], $_SESSION['cipher_wrong_answers']);
    header('Location: /jeux/hugo/cipher.php');
    exit;
}

if (!isset($_SESSION['layer'])) {
    $_SESSION['layer']        = 1;
    $_SESSION['fragments']    = [];
    $_SESSION['started']      = time();
    $_SESSION['hints_used']   = 0;
  $_SESSION['wrong_answers'] = 0;
    $_SESSION['hints_layers'] = [];
}

$layers = [
    1 => ['answer'=>'ghost',  'fragment'=>'G', 'hint'=>'Lis la première lettre de chaque ligne du log.'],
    2 => ['answer'=>'71',     'fragment'=>'7', 'hint'=>'Convertis le paquet intrus de binaire en décimal.'],
    3 => ['answer'=>'cipher', 'fragment'=>'X', 'hint'=>'Cherche le commentaire dans le code source affiché.'],
    4 => ['answer'=>'4721',   'fragment'=>'4', 'hint'=>'Trouve la ligne avec le statut CLASSIFIED et note son employee_id.'],
    5 => ['answer'=>'4708',   'fragment'=>'9', 'hint'=>"Assemble le puzzle pour révéler l'adresse IP complète."],
    6 => ['answer'=>'9382',   'fragment'=>'2', 'hint'=>'Un secret est peut-être caché dans le dossier var/'],
    7 => ['answer'=>'2479gx', 'fragment'=>'★', 'hint'=>'Trie les fragments par valeur ASCII croissante.'],
];

// ─── Helpers ─────────────────────────────────────────────────────────────────
function elapsed() {
    $s = time() - $_SESSION['started'];
    return sprintf('%02d:%02d', intdiv($s, 60), $s % 60);
}
function elapsedSeconds(): int {
    return time() - $_SESSION['started'];
}
function computeScore(int $seconds, int $hints, int $wrongAnswers): int {
  return max(
    CIPHER_MIN_SCORE,
    CIPHER_BASE_SCORE
    - intdiv($seconds, 10)
    - ($hints * CIPHER_HINT_PENALTY)
    - ($wrongAnswers * CIPHER_WRONG_PENALTY)
  );
}

// ─── Traitement indice ────────────────────────────────────────────────────────
if (isset($_POST['use_hint'])) {
    $layer = $_SESSION['layer'] ?? 1;
    if (!in_array($layer, $_SESSION['hints_layers'] ?? [])) {
        $_SESSION['hints_used']++;
        $_SESSION['hints_layers'][] = $layer;
    }
    header('Location: /jeux/hugo/cipher.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $current = $_SESSION['layer'];
    $input   = strtolower(trim($_POST['code']));
    $correct = $layers[$current]['answer'];
    if ($input === $correct) {
        $_SESSION['fragments'][] = $layers[$current]['fragment'];

        if ($current < 7) {
            $_SESSION['layer'] = $current + 1;
        } else {
            $_SESSION['layer'] = 8;
            $seconds = elapsedSeconds();
            $hints   = $_SESSION['hints_used'];
          $wrongAnswers = $_SESSION['wrong_answers'] ?? 0;
          $score   = computeScore($seconds, $hints, $wrongAnswers);
            $userId  = $_SESSION['user_id'] ?? null;

            if ($userId) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO user_scores_per_riddle (user_id, riddle_id, obtained_score)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            obtained_score = IF(VALUES(obtained_score) > obtained_score, VALUES(obtained_score), obtained_score),
                            solved_at      = IF(VALUES(obtained_score) > obtained_score, NOW(), solved_at)
                    ");
                    $stmt->execute([$userId, $riddleId, $score]);

                    $stmt = $pdo->prepare("
                        UPDATE users SET total_score = (
                            SELECT COALESCE(SUM(obtained_score), 0)
                            FROM user_scores_per_riddle WHERE user_id = ?
                        ) WHERE id = ?
                    ");
                    $stmt->execute([$userId, $userId]);
                } catch (PDOException $e) {
                    // Score non sauvegardé (utilisateur invalide), on continue quand même
                }
            }

            $_SESSION['cipher_score']   = $score;
            $_SESSION['cipher_seconds'] = $seconds;
            $_SESSION['cipher_hints']   = $hints;
            $_SESSION['cipher_wrong_answers'] = $wrongAnswers;
        }
        header('Location: /jeux/hugo/cipher.php');
        exit;
    }
        $_SESSION['wrong_answers'] = ($_SESSION['wrong_answers'] ?? 0) + 1;
    $error = 'CODE INVALIDE — ACCÈS REFUSÉ -25pts';
}

$currentLayer = $_SESSION['layer'];
$fragments    = $_SESSION['fragments'];
$victory      = ($currentLayer === 8);
$hintsUsed    = $_SESSION['hints_used'] ?? 0;
$wrongAnswers = $_SESSION['wrong_answers'] ?? 0;

// ─── Données victoire ─────────────────────────────────────────────────────────
$leaderboard  = [];
$finalScore   = 0;
$finalSeconds = 0;
$finalHints   = 0;
$finalWrongAnswers = 0;
if ($victory) {
    $finalScore   = $_SESSION['cipher_score']   ?? 0;
    $finalSeconds = $_SESSION['cipher_seconds'] ?? 0;
    $finalHints   = $_SESSION['cipher_hints']   ?? 0;
  $finalWrongAnswers = $_SESSION['cipher_wrong_answers'] ?? 0;

    try {
        $stmt = $pdo->prepare("
            SELECT u.name, s.obtained_score, s.solved_at
            FROM user_scores_per_riddle s
            JOIN users u ON u.id = s.user_id
            WHERE s.riddle_id = ?
            ORDER BY s.obtained_score DESC
            LIMIT 10
        ");
        $stmt->execute([$riddleId]);
        $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $leaderboard = [];
    }
}

require '../../includes/header.php';
?>
<link rel="stylesheet" href="/public/css/style.css">

<div class="cipher-wrap">

  <!-- ── Topbar ── -->
  <div class="cipher-topbar">
    <div class="cipher-logo">CIPHER <span>//</span> SYSTEM://BREACH</div>
    <div class="cipher-meta">
      <?php if (!$victory): ?>
        <strong>LAYER <?= str_pad($currentLayer,2,'0',STR_PAD_LEFT) ?>/07</strong><br>
        SESSION <?= elapsed() ?>
      <?php else: ?>
        <strong>EXTRACTION COMPLETE</strong><br>
        DURÉE <?= elapsed() ?>
      <?php endif; ?>
      &nbsp;·&nbsp;<a href="?reset=1">RESET</a>
    </div>
  </div>

  <?php if ($victory): ?>
  <!-- ══════════════════ VICTOIRE ══════════════════ -->
  <div class="cipher-victory">
    <div class="cipher-victory-glitch">EXTRACTION COMPLETE</div>
    <div class="cipher-victory-sub">FICHIER CLASSIFIÉ RÉCUPÉRÉ — CONNEXION FERMÉE</div>

    <!-- Score breakdown -->
    <div class="cipher-score-breakdown">
      <div class="cipher-score-main"><?= number_format($finalScore) ?> <span>pts</span></div>
      <div class="cipher-score-details">
        <div class="cipher-score-row">
          <span>Score de base</span>
          <span class="cipher-score-val"><?= number_format(CIPHER_BASE_SCORE) ?></span>
        </div>
        <div class="cipher-score-row">
          <span>Pénalité temps (<?= sprintf('%02d:%02d', intdiv($finalSeconds,60), $finalSeconds%60) ?>)</span>
          <span class="cipher-score-val cipher-score-neg">−<?= intdiv($finalSeconds, 10) ?></span>
        </div>
        <div class="cipher-score-row">
          <span>Pénalité indices (×<?= $finalHints ?>)</span>
          <span class="cipher-score-val cipher-score-neg">−<?= $finalHints * CIPHER_HINT_PENALTY ?></span>
        </div>
        <div class="cipher-score-row">
          <span>Mauvaises réponses (×<?= $finalWrongAnswers ?>)</span>
          <span class="cipher-score-val cipher-score-neg">−<?= $finalWrongAnswers * CIPHER_WRONG_PENALTY ?></span>
        </div>
        <div class="cipher-score-row cipher-score-total-row">
          <span>TOTAL</span>
          <span class="cipher-score-val cipher-score-total"><?= number_format($finalScore) ?> pts</span>
        </div>
      </div>
    </div>

    <div class="cipher-victory-time">⏱ <?= sprintf('%02d:%02d', intdiv($finalSeconds, 60), $finalSeconds % 60) ?></div>
    <div class="cipher-victory-label">DURÉE DE SESSION</div>

    <div class="cipher-victory-label" style="margin-top:20px;">FRAGMENTS COLLECTÉS</div>
    <div class="cipher-victory-frags">
      <?php foreach ($fragments as $i => $f): ?>
        <div class="cipher-victory-frag" style="animation-delay:<?= $i * .08 ?>s"><?= htmlspecialchars($f) ?></div>
      <?php endforeach; ?>
    </div>

    <div class="cipher-victory-code">
      Code final : <span>2479GX</span>
    </div>
    <div style="font-size:.68rem;color:var(--text-dim);font-family:var(--font-mono);margin-top:4px;">Triés par valeur ASCII croissante</div>

    <!-- Classement -->
    <?php if (!empty($leaderboard)): ?>
    <div class="cipher-leaderboard">
      <div class="cipher-leaderboard-title">▸ CLASSEMENT CIPHER — TOP <?= count($leaderboard) ?></div>
      <table class="cipher-lb-table">
        <thead>
          <tr><th>#</th><th>JOUEUR</th><th>SCORE</th><th>DATE</th></tr>
        </thead>
        <tbody>
          <?php foreach ($leaderboard as $i => $row):
            $isMe = isset($_SESSION['name']) && $row['name'] === $_SESSION['name'];
          ?>
          <tr class="<?= $isMe ? 'cipher-lb-me' : '' ?>">
            <td class="cipher-lb-rank">
              <?php if ($i===0) echo '🥇';
              elseif ($i===1) echo '🥈';
              elseif ($i===2) echo '🥉';
              else echo '#'.($i+1); ?>
            </td>
            <td><?= htmlspecialchars($row['name']) ?><?= $isMe ? ' ◄' : '' ?></td>
            <td class="cipher-lb-score"><?= number_format($row['obtained_score']) ?></td>
            <td class="cipher-lb-date"><?= date('d/m H:i', strtotime($row['solved_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <a href="?reset=1" class="cipher-btn-reset">[ NOUVELLE PARTIE ]</a>
  </div>

  <?php else: ?>
  <!-- ══════════════════ LAYOUT ══════════════════ -->

  <?php $pct = round(($currentLayer - 1) / 7 * 100); ?>
  <div class="cipher-progress-wrap">
    <div class="cipher-progress-label">
      <span>PROGRESSION</span>
      <span><?= $pct ?>%</span>
    </div>
    <div class="cipher-progress-track">
      <div class="cipher-progress-fill" style="width:<?= $pct ?>%"></div>
    </div>
  </div>

  <!-- Score live -->
  <div class="cipher-score-live">
    SCORE ESTIMÉ : <span id="live-score">—</span> pts
    &nbsp;|&nbsp; TEMPS : <span id="live-time">00:00</span>
    &nbsp;|&nbsp; INDICES : <span class="<?= $hintsUsed > 0 ? 'neg' : '' ?>"><?= $hintsUsed ?></span>
    <?php if ($hintsUsed > 0): ?> <span class="neg">(−<?= $hintsUsed * CIPHER_HINT_PENALTY ?> pts)</span><?php endif; ?>
    &nbsp;|&nbsp; ERREURS : <span class="<?= $wrongAnswers > 0 ? 'neg' : '' ?>"><?= $wrongAnswers ?></span>
    <?php if ($wrongAnswers > 0): ?> <span class="neg">(−<?= $wrongAnswers * CIPHER_WRONG_PENALTY ?> pts)</span><?php endif; ?>
  </div>

  <div class="cipher-layout">

    <!-- ── Panel principal ── -->
    <div>

      <?php if ($currentLayer === 1): ?>
      <!-- ════════ LAYER 1 : ACROSTICHE ════════ -->
      <div class="cipher-panel">
        <div class="cipher-layer-badge">LAYER 01 — AUTHENTIFICATION</div>
        <div class="cipher-layer-title">// SYSTEM <span>LOGIN</span></div>
        <div class="cipher-narrative">Le système de sécurité a détecté une intrusion. Un log d'erreur défile. Quelque chose se cache dans les lignes...</div>

        <div class="cipher-terminal">
          <div class="cipher-terminal-content">
            <span class="cipher-t-line"><span class="cipher-t-dim">[23:47:01]</span> <span class="cipher-t-red">ERR</span> <span class="cipher-t-key">Gateway</span> refused connection on port 443</span>
            <span class="cipher-t-line"><span class="cipher-t-dim">[23:47:02]</span> <span class="cipher-t-red">ERR</span> <span class="cipher-t-key">Handshake</span> timeout after 3000ms — retry #1</span>
            <span class="cipher-t-line"><span class="cipher-t-dim">[23:47:03]</span> <span class="cipher-t-red">ERR</span> <span class="cipher-t-key">Origin</span> address blacklisted by firewall ruleset</span>
            <span class="cipher-t-line"><span class="cipher-t-dim">[23:47:04]</span> <span class="cipher-t-red">ERR</span> <span class="cipher-t-key">Session</span> token expired — regeneration failed</span>
            <span class="cipher-t-line"><span class="cipher-t-dim">[23:47:05]</span> <span class="cipher-t-red">ERR</span> <span class="cipher-t-key">Trace</span> route incomplete — node 7 unreachable<span class="cipher-blink"></span></span>
          </div>
        </div>

        <div class="cipher-input-section">
          <div class="cipher-input-label">ENTREZ LE MOT DE PASSE SYSTÈME</div>
          <form method="POST">
            <div class="cipher-input-row">
              <input type="text" name="code" class="cipher-code-input" placeholder="________" autocomplete="off" autofocus>
              <button type="submit" class="cipher-btn-submit">VALIDER ›</button>
            </div>
            <?php if ($error): ?><div class="cipher-error-msg">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
          </form>
          <form method="POST">
            <button type="submit" name="use_hint" class="cipher-hint-toggle">[ AFFICHER UN INDICE −50pts ]</button>
          </form>
          <?php if (in_array($currentLayer, $_SESSION['hints_layers'] ?? [])): ?><div class="cipher-hint-box" id="hint" style="display:block"><?= $layers[$currentLayer]['hint'] ?></div><?php endif; ?>
        </div>
      </div>

      <?php elseif ($currentLayer === 2):
        $packets = [
          ['bin'=>'01001001','char'=>'I','intrus'=>false],
          ['bin'=>'01001110','char'=>'N','intrus'=>false],
          ['bin'=>'01010100','char'=>'T','intrus'=>false],
          ['bin'=>'01000111','char'=>'?','intrus'=>true],
          ['bin'=>'01010101','char'=>'U','intrus'=>false],
          ['bin'=>'01000100','char'=>'D','intrus'=>false],
          ['bin'=>'01000101','char'=>'E','intrus'=>false],
          ['bin'=>'01010010','char'=>'R','intrus'=>false],
        ];
      ?>
      <!-- ════════ LAYER 2 : BINAIRE ════════ -->
      <div class="cipher-panel">
        <div class="cipher-layer-badge">LAYER 02 — FIREWALL</div>
        <div class="cipher-layer-title">// PACKET <span>INTERCEPT</span></div>
        <div class="cipher-narrative">Le firewall a capturé 8 paquets réseau. Ils semblent former un mot... mais l'un d'eux est corrompu. Identifie l'intrus et convertis-le en décimal.</div>

        <div class="cipher-terminal">
          <div class="cipher-terminal-content">
            <span class="cipher-t-line cipher-t-dim">$ packet-analyzer --stream eth0 --filter corrupted</span>
            <span class="cipher-t-line">&nbsp;</span>
            <span class="cipher-t-line cipher-t-dim">PACKET_ID &nbsp; BINARY &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; DECODED</span>
            <span class="cipher-t-line cipher-t-dim">───────────────────────────────────────</span>
            <?php foreach ($packets as $i => $p): ?>
            <span class="cipher-t-line">
              <span class="cipher-t-dim">PKT_<?= str_pad($i+1,3,'0',STR_PAD_LEFT) ?> &nbsp;&nbsp; </span>
              <span class="<?= $p['intrus'] ? 'cipher-t-red' : 'cipher-t-val' ?>"><?= $p['bin'] ?></span>
              <span class="cipher-t-dim"> &nbsp;&nbsp; </span>
              <span class="<?= $p['intrus'] ? 'cipher-t-red' : 'cipher-t-key' ?>"><?= $p['intrus'] ? '██████' : $p['char'] ?></span>
            </span>
            <?php endforeach; ?>
            <span class="cipher-t-line">&nbsp;</span>
            <span class="cipher-t-line cipher-t-red">⚠ CORRUPTION DETECTED — 1 packet flagged<span class="cipher-blink"></span></span>
          </div>
        </div>

        <div class="cipher-input-section">
          <div class="cipher-input-label">VALEUR DÉCIMALE DU PAQUET CORROMPU</div>
          <form method="POST">
            <div class="cipher-input-row">
              <input type="text" name="code" class="cipher-code-input" placeholder="___" autocomplete="off" autofocus>
              <button type="submit" class="cipher-btn-submit">VALIDER ›</button>
            </div>
            <?php if ($error): ?><div class="cipher-error-msg">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
          </form>
          <form method="POST">
            <button type="submit" name="use_hint" class="cipher-hint-toggle">[ AFFICHER UN INDICE −50pts ]</button>
          </form>
          <?php if (in_array($currentLayer, $_SESSION['hints_layers'] ?? [])): ?><div class="cipher-hint-box" id="hint" style="display:block"><?= $layers[$currentLayer]['hint'] ?></div><?php endif; ?>
        </div>
      </div>

      <?php elseif ($currentLayer === 3): ?>
      <!-- ════════ LAYER 3 : ROT13 ════════ -->
      <div class="cipher-panel">
        <div class="cipher-layer-badge">LAYER 03 — CHIFFREMENT</div>
        <div class="cipher-layer-title">// ENCRYPTED <span>FILE</span></div>
        <div class="cipher-narrative">Un agent a laissé un fichier dans le répertoire temp. Le contenu est chiffré. Mais quelqu'un a laissé un commentaire dans le code source...</div>

        <?php
        $lines = [
          ['num'=>1,  'content'=>'<span class="cipher-t-comment">// agent-message-decoder.js — v0.4.2</span>'],
          ['num'=>2,  'content'=>'<span class="cipher-t-comment">// last modified: 2024-03-14 by ghost_op</span>'],
          ['num'=>3,  'content'=>''],
          ['num'=>4,  'content'=>'<span class="cipher-t-key">const</span> <span class="cipher-t-val">encoded</span> = <span class="cipher-t-dim">"PVCURE"</span>;'],
          ['num'=>5,  'content'=>''],
          ['num'=>6,  'content'=>'<span class="cipher-t-comment">// rot: 13</span>'],
          ['num'=>7,  'content'=>''],
          ['num'=>8,  'content'=>'<span class="cipher-t-key">function</span> <span class="cipher-t-val">decode</span>(str) {'],
          ['num'=>9,  'content'=>'&nbsp;&nbsp;<span class="cipher-t-key">return</span> str.<span class="cipher-t-val">replace</span>(<span class="cipher-t-dim">/[a-z]/gi</span>, ...);'],
          ['num'=>10, 'content'=>'}'],
          ['num'=>11, 'content'=>''],
          ['num'=>12, 'content'=>'<span class="cipher-t-dim">// TODO: remove before deploy</span>'],
        ];
        ?>
        <div class="cipher-code-editor">
          <?php foreach ($lines as $l): ?>
          <div class="cipher-code-line">
            <span class="cipher-line-num"><?= $l['num'] ?></span>
            <span><?= $l['content'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <div style="font-size:.82rem;color:var(--text-dim);margin-bottom:20px;border-left:2px solid var(--neon-purple);padding-left:12px;font-family:var(--font-mono);">
          Message intercepté : <span style="color:var(--neon-cyan);letter-spacing:.1em;">PVCURE</span>
        </div>

        <div class="cipher-input-section">
          <div class="cipher-input-label">MOT DÉCHIFFRÉ</div>
          <form method="POST">
            <div class="cipher-input-row">
              <input type="text" name="code" class="cipher-code-input" placeholder="______" autocomplete="off" autofocus>
              <button type="submit" class="cipher-btn-submit">VALIDER ›</button>
            </div>
            <?php if ($error): ?><div class="cipher-error-msg">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
          </form>
          <form method="POST">
            <button type="submit" name="use_hint" class="cipher-hint-toggle">[ AFFICHER UN INDICE −50pts ]</button>
          </form>
          <?php if (in_array($currentLayer, $_SESSION['hints_layers'] ?? [])): ?><div class="cipher-hint-box" id="hint" style="display:block"><?= $layers[$currentLayer]['hint'] ?></div><?php endif; ?>
        </div>
      </div>

      <?php elseif ($currentLayer === 4):
        $rows = [
          ['id'=>'8831','name'=>'K. MORRISON',  'dept'=>'LOGISTICS',  'clearance'=>'LEVEL-2','status'=>'ACTIVE'],
          ['id'=>'2240','name'=>'T. VASQUEZ',   'dept'=>'FINANCE',    'clearance'=>'LEVEL-1','status'=>'ACTIVE'],
          ['id'=>'5519','name'=>'A. PETROV',    'dept'=>'SECURITY',   'clearance'=>'LEVEL-3','status'=>'SUSPENDED'],
          ['id'=>'4721','name'=>'[REDACTED]',   'dept'=>'DIRECTORATE','clearance'=>'LEVEL-5','status'=>'CLASSIFIED'],
          ['id'=>'7703','name'=>'L. CHEN',      'dept'=>'R&D',        'clearance'=>'LEVEL-2','status'=>'ACTIVE'],
          ['id'=>'1190','name'=>'M. OKONKWO',   'dept'=>'HR',         'clearance'=>'LEVEL-1','status'=>'ACTIVE'],
          ['id'=>'6628','name'=>'D. REINHOLT',  'dept'=>'SECURITY',   'clearance'=>'LEVEL-3','status'=>'ACTIVE'],
          ['id'=>'3347','name'=>'S. NAKAMURA',  'dept'=>'FINANCE',    'clearance'=>'LEVEL-2','status'=>'INACTIVE'],
          ['id'=>'9904','name'=>'F. IBRAHIM',   'dept'=>'LOGISTICS',  'clearance'=>'LEVEL-1','status'=>'ACTIVE'],
          ['id'=>'0055','name'=>'R. CASTELLANO','dept'=>'R&D',        'clearance'=>'LEVEL-4','status'=>'ACTIVE'],
        ];
      ?>
      <!-- ════════ LAYER 4 : SQL ════════ -->
      <div class="cipher-panel">
        <div class="cipher-layer-badge">LAYER 04 — BASE DE DONNÉES</div>
        <div class="cipher-layer-title">// DB ACCESS — <span>employees</span></div>
        <div class="cipher-narrative">Tu as pénétré la base de données RH de la corporation. Quelque chose est caché dans ces entrées...</div>

        <div class="cipher-terminal" style="padding-bottom:16px;">
          <div class="cipher-terminal-content">
            <span class="cipher-t-line cipher-t-dim">$ SELECT * FROM employees ORDER BY employee_id;</span>
            <span class="cipher-t-line">&nbsp;</span>
          </div>
          <table class="cipher-sql-table">
            <thead>
              <tr>
                <th>EMPLOYEE_ID</th><th>NAME</th><th>DEPARTMENT</th><th>CLEARANCE</th><th>STATUS</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
              <tr class="<?= $r['status']==='CLASSIFIED' ? 'cipher-classified' : '' ?>">
                <td><?= $r['id'] ?></td>
                <td><?= $r['name'] ?></td>
                <td><?= $r['dept'] ?></td>
                <td><?= $r['clearance'] ?></td>
                <td class="<?= $r['status']==='CLASSIFIED' ? 'cipher-t-classified' : '' ?>"><?= $r['status'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="cipher-input-section">
          <div class="cipher-input-label">EMPLOYEE_ID DE LA LIGNE CLASSIFIÉE</div>
          <form method="POST">
            <div class="cipher-input-row">
              <input type="text" name="code" class="cipher-code-input" placeholder="____" autocomplete="off" autofocus>
              <button type="submit" class="cipher-btn-submit">VALIDER ›</button>
            </div>
            <?php if ($error): ?><div class="cipher-error-msg">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
          </form>
          <form method="POST">
            <button type="submit" name="use_hint" class="cipher-hint-toggle">[ AFFICHER UN INDICE −50pts ]</button>
          </form>
          <?php if (in_array($currentLayer, $_SESSION['hints_layers'] ?? [])): ?><div class="cipher-hint-box" id="hint" style="display:block"><?= $layers[$currentLayer]['hint'] ?></div><?php endif; ?>
        </div>
      </div>

      <?php elseif ($currentLayer === 5):
        $tiles = [
          ['id'=>0,'label'=>"NODE-A\n192.x"],
          ['id'=>1,'label'=>".168.x\n──────"],
          ['id'=>2,'label'=>"x.x SRV\nGATEWY"],
          ['id'=>3,'label'=>"──┬──\nSWITCH"],
          ['id'=>4,'label'=>"47.08\nVAULT"],
          ['id'=>5,'label'=>"──┴──\nFWALL"],
          ['id'=>6,'label'=>"CLIENT\n└──┐"],
          ['id'=>7,'label'=>"DMZN\n│"],
          ['id'=>8,'label'=>"EXIT\n└──►"],
        ];
        $shuffled = [2,5,0,7,3,8,1,6,4];
      ?>
      <!-- ════════ LAYER 5 : PUZZLE ════════ -->
      <div class="cipher-panel">
        <div class="cipher-layer-badge">LAYER 05 — RÉSEAU</div>
        <div class="cipher-layer-title">// NETWORK MAP <span>FRAGMENT</span></div>
        <div class="cipher-narrative">La carte du réseau interne a été fragmentée par le système de défense. Remets les tuiles dans le bon ordre pour révéler l'adresse IP du vault.</div>

        <div class="cipher-puzzle-grid" id="puzzle">
          <?php foreach ($shuffled as $pos => $tileId): ?>
          <div class="cipher-puzzle-cell"
               id="cell-<?= $pos ?>"
               data-pos="<?= $pos ?>"
               data-tile="<?= $tileId ?>"
               draggable="true">
            <?= nl2br(htmlspecialchars($tiles[$tileId]['label'])) ?>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="cipher-puzzle-reveal" id="puzzle-reveal">
          [ ASSEMBLAGE EN COURS... ]
        </div>

        <div class="cipher-input-section">
          <div class="cipher-input-label">DEUX DERNIERS OCTETS DE L'ADRESSE IP (ex: 4708)</div>
          <form method="POST">
            <div class="cipher-input-row">
              <input type="text" name="code" class="cipher-code-input" placeholder="____" autocomplete="off" autofocus id="puzzle-input">
              <button type="submit" class="cipher-btn-submit">VALIDER ›</button>
            </div>
            <?php if ($error): ?><div class="cipher-error-msg">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
          </form>
          <form method="POST">
            <button type="submit" name="use_hint" class="cipher-hint-toggle">[ AFFICHER UN INDICE −50pts ]</button>
          </form>
          <?php if (in_array($currentLayer, $_SESSION['hints_layers'] ?? [])): ?><div class="cipher-hint-box" id="hint" style="display:block"><?= $layers[$currentLayer]['hint'] ?></div><?php endif; ?>
        </div>
      </div>

      <script>
      const cells = document.querySelectorAll('.cipher-puzzle-cell');
      let dragSrc = null;
      cells.forEach(cell => {
        cell.addEventListener('dragstart', e => { dragSrc = cell; e.dataTransfer.effectAllowed = 'move'; });
        cell.addEventListener('dragover',  e => { e.preventDefault(); cell.classList.add('drag-over'); });
        cell.addEventListener('dragleave', () => cell.classList.remove('drag-over'));
        cell.addEventListener('drop', e => {
          e.preventDefault(); cell.classList.remove('drag-over');
          if (dragSrc === cell) return;
          const tmp = dragSrc.innerHTML, tmpTile = dragSrc.dataset.tile;
          dragSrc.innerHTML = cell.innerHTML; dragSrc.dataset.tile = cell.dataset.tile;
          cell.innerHTML = tmp; cell.dataset.tile = tmpTile;
          checkPuzzle();
        });
      });
      function checkPuzzle() {
        const cells = document.querySelectorAll('.cipher-puzzle-cell');
        let correct = 0;
        cells.forEach((c,i) => {
          const ok = parseInt(c.dataset.tile) === i;
          c.classList.toggle('solved-cell', ok);
          if (ok) correct++;
        });
        const reveal = document.getElementById('puzzle-reveal');
        if (correct === 9) {
          reveal.innerHTML = '<span style="color:var(--neon-green)">✓ IP DÉCODÉE : 192.168.<strong>47.08</strong></span>';
          document.getElementById('puzzle-input').value = '4708';
        } else {
          reveal.textContent = '[ ' + correct + '/9 TUILES EN PLACE ]';
        }
      }
      checkPuzzle();
      </script>

      <?php elseif ($currentLayer === 6): ?>
      <!-- ════════ LAYER 6 : CLI ════════ -->
      <div class="cipher-panel">
        <div class="cipher-layer-badge">LAYER 06 — TERMINAL ROOT</div>
        <div class="cipher-layer-title">// ROOT ACCESS <span>GRANTED</span></div>
        <div class="cipher-narrative">Tu as obtenu un accès root au système central. Le fichier vault est quelque part dans le filesystem. Explore et trouve-le.</div>

        <div class="cipher-cli-box" id="cli-output">
          <div class="cipher-cli-output"><span class="cipher-cli-prompt">root@corp-server:~$</span> <span class="cipher-cli-cmd">whoami</span></div>
          <div class="cipher-cli-output"><span class="cipher-cli-resp">root</span></div>
          <div class="cipher-cli-output">&nbsp;</div>
          <div class="cipher-cli-output"><span class="cipher-cli-resp" style="color:var(--neon-cyan)">Bienvenue dans le système CORP-CENTRAL v4.1.2</span></div>
          <div class="cipher-cli-output"><span class="cipher-cli-resp">Session ouverte — tracé actif dans 180 secondes.</span></div>
          <div class="cipher-cli-output">&nbsp;</div>
        </div>

        <div class="cipher-cli-input-row">
          <span class="cipher-cli-prompt-label">root@corp-server:~$</span>
          <input type="text" class="cipher-cli-input" id="cli-input" placeholder="entrez une commande..." autocomplete="off" autofocus>
        </div>

        <div class="cipher-input-section">
          <div class="cipher-input-label">CODE EXTRAIT DU FICHIER VAULT</div>
          <form method="POST">
            <div class="cipher-input-row">
              <input type="text" name="code" class="cipher-code-input" placeholder="____" autocomplete="off" id="vault-code">
              <button type="submit" class="cipher-btn-submit">VALIDER ›</button>
            </div>
            <?php if ($error): ?><div class="cipher-error-msg">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
          </form>
          <form method="POST">
            <button type="submit" name="use_hint" class="cipher-hint-toggle">[ AFFICHER UN INDICE −50pts ]</button>
          </form>
          <?php if (in_array($currentLayer, $_SESSION['hints_layers'] ?? [])): ?><div class="cipher-hint-box" id="hint" style="display:block"><?= $layers[$currentLayer]['hint'] ?></div><?php endif; ?>
        </div>
      </div>

      <script>
      const cliInput  = document.getElementById('cli-input');
      const cliOutput = document.getElementById('cli-output');
      const vaultCode = document.getElementById('vault-code');
      let cwd = '~';

      const fs = {
        '~':                     {type:'dir', children:['home','var','etc']},
        'home':                  {type:'dir', children:['admin']},
        'home/admin':            {type:'dir', children:['notes.txt','readme.md']},
        'var':                   {type:'dir', children:['log','secret']},
        'var/log':               {type:'dir', children:['system.log','access.log']},
        'var/secret':            {type:'dir', children:['vault.enc']},
        'etc':                   {type:'dir', children:['config.ini']},
        'home/admin/notes.txt':  {type:'file', content:'Accès vault réservé — niveau 5 requis.'},
        'home/admin/readme.md':  {type:'file', content:'Ne pas modifier ce répertoire.'},
        'var/log/system.log':    {type:'file', content:'[INFO] Boot sequence complete.\n[WARN] Anomalous packet detected.\n[ERR]  Intrusion attempt logged.'},
        'var/log/access.log':    {type:'file', content:'2024-03-14 23:47:01 — root login\n2024-03-14 23:47:09 — vault.enc accessed'},
        'var/secret/vault.enc':  {type:'file', content:'ENCRYPTED: Xm7#aQ9382pLz\nRun: decrypt vault.enc'},
        'etc/config.ini':        {type:'file', content:'[system]\nversion=4.1.2\nencryption=AES-256'},
      };

      function print(html, cls='cipher-cli-resp') {
        const div = document.createElement('div');
        div.className = 'cipher-cli-output';
        div.innerHTML = '<span class="'+cls+'">'+html+'</span>';
        cliOutput.appendChild(div);
        cliOutput.scrollTop = cliOutput.scrollHeight;
      }

      function resolvePath(rel) {
        if (rel === '..') { const p = cwd.split('/'); p.pop(); return p.length ? p.join('/') : '~'; }
        return cwd === '~' ? rel : cwd+'/'+rel;
      }

      cliInput.addEventListener('keydown', e => {
        if (e.key !== 'Enter') return;
        const raw = cliInput.value.trim(); cliInput.value = '';
        if (!raw) return;
        const echo = document.createElement('div');
        echo.className = 'cipher-cli-output';
        echo.innerHTML = '<span class="cipher-cli-prompt">root@corp-server:'+cwd+'$</span> <span class="cipher-cli-cmd">'+raw.replace(/</g,'&lt;')+'</span>';
        cliOutput.appendChild(echo);
        const [cmd,...args] = raw.split(' ');
        const arg = args.join(' ');
        if (cmd==='ls') {
          const node = fs[cwd];
          print(node && node.children ? node.children.join('&nbsp;&nbsp;') : 'Répertoire vide.');
        } else if (cmd==='cd') {
          if (!arg||arg==='~') { cwd='~'; print(''); }
          else { const t=resolvePath(arg); fs[t]&&fs[t].type==='dir' ? (cwd=t,print('')) : print('cd: '+arg+': No such directory','cipher-cli-err'); }
        } else if (cmd==='cat') {
          const t=resolvePath(arg);
          fs[t]&&fs[t].type==='file' ? print(fs[t].content.replace(/\n/g,'<br>')) : print('cat: '+arg+': No such file','cipher-cli-err');
        } else if (cmd==='decrypt') {
          if (arg==='vault.enc') {
            if (cwd==='var/secret') {
              print('Déchiffrement en cours...');
              setTimeout(()=>print('▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓ 100%','cipher-cli-ok'),300);
              setTimeout(()=>{ print('CODE VAULT : <strong style="color:var(--neon-cyan);letter-spacing:.2em;">9382</strong>','cipher-cli-ok'); vaultCode.value='9382'; },900);
            } else { print('Fichier introuvable dans ce répertoire.','cipher-cli-err'); }
          } else { print('decrypt: fichier non reconnu.','cipher-cli-err'); }
        } else if (cmd==='pwd') { print('/'+cwd);
        } else if (cmd==='help') { print('Commandes : ls, cd [dir], cat [file], decrypt [file], pwd, clear');
        } else if (cmd==='clear') { cliOutput.innerHTML='';
        } else { print(cmd+': command not found','cipher-cli-err'); }
        cliOutput.scrollTop = cliOutput.scrollHeight;
      });
      </script>

      <?php elseif ($currentLayer === 7):
        $ascii_map = ['G'=>71,'7'=>55,'X'=>88,'4'=>52,'9'=>57,'2'=>50];
      ?>
      <!-- ════════ LAYER 7 : ASCII ════════ -->
      <div class="cipher-panel">
        <div class="cipher-layer-badge">LAYER 07 — EXTRACTION FINALE</div>
        <div class="cipher-layer-title">// VAULT <span>LOCK</span></div>
        <div class="cipher-narrative">Le vault final est verrouillé par un code composite. Tous tes fragments sont là. Mais l'ordre n'est pas celui que tu crois.</div>

        <div class="cipher-terminal">
          <div class="cipher-terminal-content">
            <span class="cipher-t-line cipher-t-dim">$ vault --unlock --fragments <?= implode(',',$fragments) ?></span>
            <span class="cipher-t-line">&nbsp;</span>
            <span class="cipher-t-line cipher-t-red">ERROR: Invalid sequence order.</span>
            <span class="cipher-t-line">&nbsp;</span>
            <span class="cipher-t-line cipher-t-dim">SYSTEM_MSG: La clé ne reconnaît pas l'ordre humain.</span>
            <span class="cipher-t-line cipher-t-dim">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Elle trie par priorité machine.</span>
            <span class="cipher-t-line cipher-t-dim">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Consulte la table de référence.<span class="cipher-blink"></span></span>
          </div>
        </div>

        <div class="cipher-frag-display">
          <?php foreach ($fragments as $f): ?>
          <div class="cipher-frag-card">
            <span class="cipher-frag-char"><?= htmlspecialchars($f) ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-bottom:20px;">
          <span class="cipher-ascii-toggle" onclick="document.getElementById('ascii-table').style.display=document.getElementById('ascii-table').style.display==='none'?'block':'none'">[ ASCII TABLE ]</span>
          <div id="ascii-table" class="cipher-ascii-table">
            <div class="cipher-ascii-grid">
              <?php
              foreach (array_merge(range('0','9'), range('A','Z')) as $c):
                $hl = in_array($c, $fragments) ? 'cipher-ascii-highlight' : '';
              ?>
              <span class="cipher-ascii-entry <?= $hl ?>"><?= $c ?> = <?= ord($c) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="cipher-input-section">
          <div class="cipher-input-label">CODE FINAL (fragments triés par valeur ASCII croissante)</div>
          <form method="POST">
            <div class="cipher-input-row">
              <input type="text" name="code" class="cipher-code-input" placeholder="______" autocomplete="off" autofocus maxlength="6">
              <button type="submit" class="cipher-btn-submit">EXTRAIRE ›</button>
            </div>
            <?php if ($error): ?><div class="cipher-error-msg">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
          </form>
          <form method="POST">
            <button type="submit" name="use_hint" class="cipher-hint-toggle">[ AFFICHER UN INDICE −50pts ]</button>
          </form>
          <?php if (in_array($currentLayer, $_SESSION['hints_layers'] ?? [])): ?><div class="cipher-hint-box" id="hint" style="display:block"><?= $layers[$currentLayer]['hint'] ?></div><?php endif; ?>
        </div>
      </div>

      <?php endif; ?>
    </div><!-- /main -->

    <!-- ── Sidebar ── -->
    <div class="cipher-sidebar">
      <div class="cipher-sidebar-title">▸ DOSSIER DE MISSION</div>

      <div class="cipher-fragments-grid">
        <?php foreach (['G','7','X','4','9','2'] as $f):
          $ok = in_array($f, $fragments); ?>
        <div class="cipher-frag-slot <?= $ok ? 'collected' : '' ?>">
          <?= $ok ? htmlspecialchars($f) : '·' ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="cipher-sidebar-info">
        <?php if ($currentLayer <= 7): ?>
        <strong>LAYER <?= str_pad($currentLayer,2,'0',STR_PAD_LEFT) ?>/07</strong>
        <?= count($fragments) ?>/6 fragments collectés<br><br>
        Score estimé :<br>
        <span id="sidebar-score" style="color:var(--neon-cyan);font-size:1.1rem;font-family:var(--font-display);">—</span> pts<br><br>
        Indices : <span style="color:<?= $hintsUsed > 0 ? '#ef4444' : 'var(--neon-green)' ?>"><?= $hintsUsed ?></span>
        <?php if ($hintsUsed > 0): ?>
        <span style="color:#ef4444;font-size:.68rem;display:block;">−<?= $hintsUsed * CIPHER_HINT_PENALTY ?> pts</span>
        <?php endif; ?>
        Erreurs : <span style="color:<?= $wrongAnswers > 0 ? '#ef4444' : 'var(--neon-green)' ?>"><?= $wrongAnswers ?></span>
        <?php if ($wrongAnswers > 0): ?>
        <span style="color:#ef4444;font-size:.68rem;display:block;">−<?= $wrongAnswers * CIPHER_WRONG_PENALTY ?> pts</span>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /cipher-layout -->
  <?php endif; ?>

</div><!-- /cipher-wrap -->

<!-- Score live -->
<?php if (!$victory): ?>
<script>
const _start = <?= $_SESSION['started'] ?>;
const _hints = <?= $hintsUsed ?>;
const _wrong = <?= $wrongAnswers ?>;
function updateLive() {
  const s     = Math.floor(Date.now() / 1000) - _start;
  const score = Math.max(<?= CIPHER_MIN_SCORE ?>, <?= CIPHER_BASE_SCORE ?> - Math.floor(s / 10) - (_hints * <?= CIPHER_HINT_PENALTY ?>) - (_wrong * <?= CIPHER_WRONG_PENALTY ?>));
  const mm    = String(Math.floor(s / 60)).padStart(2, '0');
  const ss    = String(s % 60).padStart(2, '0');
  const el1   = document.getElementById('live-score');
  const el2   = document.getElementById('sidebar-score');
  const elt   = document.getElementById('live-time');
  if (el1) el1.textContent = score;
  if (el2) el2.textContent = score;
  if (elt) elt.textContent = mm + ':' + ss;
}
updateLive();
setInterval(updateLive, 1000);
</script>
<?php endif; ?>

<script>
function toggleHint() {
  const h = document.getElementById('hint');
  const t = document.querySelector('.cipher-hint-toggle');
  const open = h.style.display === 'block';
  h.style.display = open ? 'none' : 'block';
  t.textContent = open ? '[ AFFICHER UN INDICE ]' : "[ MASQUER L'INDICE ]";
}
</script>

<?php require '../../includes/footer.php'; ?>