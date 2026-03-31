<?php
session_start();
require '../../config/database.php';

// ─── Initialisation du riddle en BDD ─────────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM riddles WHERE title = 'DEAD DROP — L\\'Affaire du Mercredi' LIMIT 1");
$stmt->execute();
$riddle = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$riddle) {
    $stmt = $pdo->prepare("INSERT INTO riddles (title, description, answer, max_points, difficulty)
                            VALUES ('DEAD DROP — L\\'Affaire du Mercredi', 'Enquête policière en 4 actes.', 'iris', 500, 'difficile')");
    $stmt->execute();
    $riddleId = (int)$pdo->lastInsertId();
} else {
    $riddleId = (int)$riddle['id'];
}

// ─── Reset ────────────────────────────────────────────────────────────────────
if (isset($_GET['reset'])) {
    unset($_SESSION['dd_enquete'], $_SESSION['dd_started'], $_SESSION['dd_hints'],
          $_SESSION['dd_score'], $_SESSION['dd_seconds'], $_SESSION['dd_final_hints'],
          $_SESSION['dd_hint_enquete']);
    header('Location: /jeux/hugo/dead-drop.php');
    exit;
}

// ─── Initialisation session ───────────────────────────────────────────────────
if (!isset($_SESSION['dd_enquete'])) {
    $_SESSION['dd_enquete'] = 1;
    $_SESSION['dd_started'] = time();
    $_SESSION['dd_hints']   = 0;
    $_SESSION['dd_wrong']   = 0;
}
if (!isset($_SESSION['dd_hints'])) $_SESSION['dd_hints'] = 0;
if (!isset($_SESSION['dd_wrong']))  $_SESSION['dd_wrong']  = 0;

// ─── Helpers ──────────────────────────────────────────────────────────────────
function dd_elapsed(): string {
    $s = time() - $_SESSION['dd_started'];
    return sprintf('%02d:%02d', intdiv($s, 60), $s % 60);
}
function dd_seconds(): int {
    return time() - $_SESSION['dd_started'];
}
function dd_score(int $s, int $h, int $w = 0): int {
    return max(100, 500 - intdiv($s, 10) - ($h * 50) - ($w * 25));
}

// ─── Traitement indice ────────────────────────────────────────────────────────
if (isset($_POST['use_hint'])) {
    // Ne déduire que si l'indice n'a pas déjà été acheté pour cet acte
    if (!isset($_SESSION['dd_hint_enquete']) || $_SESSION['dd_hint_enquete'] !== $_SESSION['dd_enquete']) {
        $_SESSION['dd_hints']++;
        $_SESSION['dd_hint_enquete'] = $_SESSION['dd_enquete'];
    }
    header('Location: /jeux/hugo/dead-drop.php');
    exit;
}

// ─── Traitement formulaire ────────────────────────────────────────────────────
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rep'])) {
    $current = $_SESSION['dd_enquete'];
    $input   = strtolower(trim($_POST['rep']));

    $correct = match($current) {
        1 => 'viktor',
        2 => '157',
        3 => 'saul',
        4 => 'iris|coupable', // géré différemment
        default => ''
    };

    // Enquête 4 : vérification des 4 rôles
    $valid = false;
    if ($current === 4) {
        $roles = $_POST['roles'] ?? [];
        $valid = (
            isset($roles['viktor'], $roles['marlene'], $roles['saul'], $roles['iris']) &&
            $roles['viktor']  === 'innocent' &&
            $roles['marlene'] === 'innocent' &&
            $roles['saul']    === 'complice' &&
            $roles['iris']    === 'coupable'
        );
        if (!$valid) {
            $error = 'VERDICT INCORRECT — RÉEXAMINEZ LES PREUVES -25pts';
            $_SESSION['dd_wrong']++;
        }
    } else {
        $valid = ($input === $correct);
        if (!$valid) {
            $error = 'RÉPONSE INCORRECTE — CONTINUEZ L\'ENQUÊTE -25pts';
            $_SESSION['dd_wrong']++;
        }
    }

    if ($valid) {
        if ($current < 4) {
            $_SESSION['dd_enquete']++;
            unset($_SESSION['dd_hint_enquete']);
        } else {
            // Victoire
            $_SESSION['dd_enquete'] = 5;
            $seconds = dd_seconds();
            $hints   = $_SESSION['dd_hints'];
            $wrong   = $_SESSION['dd_wrong'] ?? 0;
            $score   = dd_score($seconds, $hints, $wrong);
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
                    // Score non sauvegardé, on continue quand même
                }
            }

            $_SESSION['dd_score']        = $score;
            $_SESSION['dd_seconds']      = $seconds;
            $_SESSION['dd_final_hints']  = $hints;
            $_SESSION['dd_final_wrong']  = $wrong;
        }
        header('Location: /jeux/hugo/dead-drop.php');
        exit;
    }
}

$enquete    = $_SESSION['dd_enquete'];
$victory    = ($enquete === 5);
$hintsUsed  = $_SESSION['dd_hints'] ?? 0;
$wrongCount = $_SESSION['dd_wrong'] ?? 0;
$hintActive = isset($_SESSION['dd_hint_enquete']) && (int)$_SESSION['dd_hint_enquete'] === (int)$enquete;

$hints = [
    1 => 'Un film de 2h commencé à 20h se termine à quelle heure ?',
    2 => 'Chaque feuille d\'un livre contient 2 pages (recto + verso).',
    3 => 'Le restaurant ne sert pas de viande le mercredi — regardez la commande.',
    4 => 'Saul était au restaurant à 22h47. La victime est morte à 23h15. Qui était libre à ce moment ?',
];

// Classement
$leaderboard = [];
$finalScore = $finalSeconds = $finalHints = $finalWrong = 0;
if ($victory) {
    $finalScore   = $_SESSION['dd_score']       ?? 0;
    $finalSeconds = $_SESSION['dd_seconds']     ?? 0;
    $finalHints   = $_SESSION['dd_final_hints'] ?? 0;
    $finalWrong   = $_SESSION['dd_final_wrong'] ?? 0;

    try {
        $stmt = $pdo->prepare("
            SELECT u.username, s.obtained_score, s.solved_at
            FROM user_scores_per_riddle s
            JOIN users u ON u.id = s.user_id
            WHERE s.riddle_id = ?
            ORDER BY s.obtained_score DESC LIMIT 10
        ");
        $stmt->execute([$riddleId]);
        $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $leaderboard = [];
    }
}
?>
<?php require '../../includes/header.php'; ?>
<link rel="stylesheet" href="/public/css/style.css">

<div class="dd-wrap">

  <!-- ── Topbar ── -->
  <div class="dd-topbar">
    <div class="dd-logo">DEAD <span>DROP</span></div>
    <div class="dd-meta">
      <?php if (!$victory): ?>
        <strong>ENQUÊTE <?= str_pad($enquete,2,'0',STR_PAD_LEFT) ?>/04</strong><br>
        SESSION <?= dd_elapsed() ?>
      <?php else: ?>
        <strong>AFFAIRE CLASSÉE</strong><br>
        DURÉE <?= sprintf('%02d:%02d', intdiv($finalSeconds,60), $finalSeconds%60) ?>
      <?php endif; ?>
      &nbsp;·&nbsp;<a href="?reset=1">RESET</a>
    </div>
  </div>

  <?php if ($victory): ?>
  <!-- ══════════════════ VICTOIRE ══════════════════ -->
  <div class="dd-victory">
    <div class="dd-victory-title">Affaire Classée.</div>
    <div class="dd-victory-sub">L'enquête est terminée — le coupable a été identifié</div>

    <!-- Score -->
    <div class="dd-score-breakdown">
      <div class="dd-score-main"><?= number_format($finalScore) ?> <span>pts</span></div>
      <div class="dd-score-details">
        <div class="dd-score-row">
          <span>Score de base</span>
          <span class="dd-score-val">500</span>
        </div>
        <div class="dd-score-row">
          <span>Pénalité temps (<?= sprintf('%02d:%02d', intdiv($finalSeconds,60), $finalSeconds%60) ?>)</span>
          <span class="dd-score-val dd-score-neg">−<?= intdiv($finalSeconds, 10) ?></span>
        </div>
        <div class="dd-score-row">
          <span>Pénalité indices (×<?= $finalHints ?>)</span>
          <span class="dd-score-val dd-score-neg">−<?= $finalHints * 50 ?></span>
        </div>
        <div class="dd-score-row">
          <span>Pénalité erreurs (×<?= $finalWrong ?>)</span>
          <span class="dd-score-val dd-score-neg">−<?= $finalWrong * 25 ?></span>
        </div>
        <div class="dd-score-row dd-score-total-row">
          <span>TOTAL</span>
          <span class="dd-score-val dd-score-total"><?= number_format($finalScore) ?> pts</span>
        </div>
      </div>
    </div>

    <!-- Verdict final -->
    <div style="font-family:var(--font-mono);font-size:.65rem;color:var(--text-muted);letter-spacing:.15em;margin-bottom:12px;text-transform:uppercase;">Verdict</div>
    <div class="dd-verdict-grid">
      <div class="dd-verdict-card" style="animation-delay:.0s">
        <div class="dd-verdict-name">Viktor</div>
        <span class="dd-verdict-role innocent">INNOCENT</span>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:8px;font-family:var(--font-mono);">Espionnait Marlène</div>
      </div>
      <div class="dd-verdict-card" style="animation-delay:.08s">
        <div class="dd-verdict-name">Marlène</div>
        <span class="dd-verdict-role innocent">INNOCENT</span>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:8px;font-family:var(--font-mono);">Se cachait chez elle</div>
      </div>
      <div class="dd-verdict-card" style="animation-delay:.16s">
        <div class="dd-verdict-name">Saul</div>
        <span class="dd-verdict-role complice">COMPLICE</span>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:8px;font-family:var(--font-mono);">A fourni l'arme à Iris</div>
      </div>
      <div class="dd-verdict-card" style="animation-delay:.24s">
        <div class="dd-verdict-name">Iris</div>
        <span class="dd-verdict-role coupable">COUPABLE</span>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:8px;font-family:var(--font-mono);">Acte commis à 23h15</div>
      </div>
    </div>

    <!-- Classement -->
    <?php if (!empty($leaderboard)): ?>
    <div class="dd-leaderboard">
      <div class="dd-leaderboard-title">▸ Classement — L'Affaire du Mercredi</div>
      <table class="dd-lb-table">
        <thead>
          <tr><th>#</th><th>DÉTECTIVE</th><th>SCORE</th><th>DATE</th></tr>
        </thead>
        <tbody>
          <?php foreach ($leaderboard as $i => $row):
            $isMe = isset($_SESSION['username']) && $row['username'] === $_SESSION['username'];
          ?>
          <tr class="<?= $isMe ? 'dd-lb-me' : '' ?>">
            <td class="dd-lb-rank">
              <?php if ($i===0) echo '🥇';
              elseif ($i===1) echo '🥈';
              elseif ($i===2) echo '🥉';
              else echo '#'.($i+1); ?>
            </td>
            <td><?= htmlspecialchars($row['username']) ?><?= $isMe ? ' ◄' : '' ?></td>
            <td class="dd-lb-score"><?= number_format($row['obtained_score']) ?></td>
            <td class="dd-lb-date"><?= date('d/m H:i', strtotime($row['solved_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <a href="?reset=1" class="dd-btn-reset">[ NOUVELLE ENQUÊTE ]</a>
  </div>

  <?php else: ?>
  <!-- ══════════════════ LAYOUT ══════════════════ -->

  <?php $pct = round(($enquete - 1) / 4 * 100); ?>
  <div class="dd-progress-wrap">
    <div class="dd-progress-label">
      <span>PROGRESSION</span>
      <span><?= $pct ?>%</span>
    </div>
    <div class="dd-progress-track">
      <div class="dd-progress-fill" style="width:<?= $pct ?>%"></div>
    </div>
  </div>

  <div class="dd-score-live">
    SCORE ESTIMÉ : <span id="dd-live-score">—</span> pts
    &nbsp;|&nbsp; TEMPS : <span id="dd-live-time">00:00</span>
    &nbsp;|&nbsp; INDICES : <span class="<?= $hintsUsed > 0 ? 'neg' : '' ?>"><?= $hintsUsed ?></span>
    <?php if ($hintsUsed > 0): ?><span class="neg">(−<?= $hintsUsed * 50 ?> pts)</span><?php endif; ?>
  </div>

  <div class="dd-layout">
    <div>

      <?php if ($enquete === 1): ?>
      <!-- ════════ ENQUÊTE 1 : L'ALIBI ════════ -->
      <div class="dd-panel">
        <div class="dd-enquete-badge">ENQUÊTE 01 — LES ALIBIS</div>
        <div class="dd-enquete-title">L'Alibi du <span>Mercredi</span></div>
        <div class="dd-narrative">
          La victime a été retrouvée morte mercredi soir. Quatre suspects, quatre alibis.
          L'un d'eux ment. Le détective reçoit une note anonyme : <em>"L'assassin se cache parmi eux."</em>
        </div>

        <div class="dd-dossier">
          <div class="dd-dossier-label">📁 Dossier — Dépositions du mercredi soir</div>

          <div class="dd-suspect-card">
            <div class="dd-suspect-name">Viktor <span class="dd-tag">SUSPECT A</span></div>
            <div class="dd-suspect-alibi">"J'étais au cinéma. Le film a commencé à 20h et durait 2h. Je suis rentré chez moi à minuit."</div>
          </div>

          <div class="dd-suspect-card">
            <div class="dd-suspect-name">Marlène <span class="dd-tag">SUSPECT B</span></div>
            <div class="dd-suspect-alibi">"Je lisais dans ma chambre. J'ai terminé mon livre ce soir-là — la dernière page, le numéro 314."</div>
          </div>

          <div class="dd-suspect-card">
            <div class="dd-suspect-name">Saul <span class="dd-tag">SUSPECT C</span></div>
            <div class="dd-suspect-alibi">"Je dînais seul au restaurant Le Vieux Port. J'ai payé en cash, pas de reçu."</div>
          </div>

          <div class="dd-suspect-card">
            <div class="dd-suspect-name">Iris <span class="dd-tag">SUSPECT D</span></div>
            <div class="dd-suspect-alibi">"Je travaillais tard au bureau. Les néons s'éteignent automatiquement à 23h — je suis partie juste avant."</div>
          </div>
        </div>

        <div class="dd-proof">
          <div class="dd-proof-content">
            <span class="dd-proof-line"><span class="dd-proof-key">HEURE DU DÉCÈS</span> <span class="dd-proof-val">— 23h15, mercredi</span></span>
            <span class="dd-proof-line"><span class="dd-proof-key">NOTE DU MÉDECIN</span> <span class="dd-proof-val">— mort entre 23h00 et 23h30</span></span>
            <span class="dd-proof-line">&nbsp;</span>
            <span class="dd-proof-line dd-proof-dim">Analysez les alibis. L'un contient une contradiction factuelle.<span class="dd-blink"></span></span>
          </div>
        </div>

        <div class="dd-input-section">
          <div class="dd-input-label">QUEL SUSPECT MENT SUR SON ALIBI ?</div>
          <form method="POST">
            <div class="dd-input-row">
              <input type="text" name="rep" class="dd-code-input" placeholder="NOM DU SUSPECT" autocomplete="off" autofocus>
              <button type="submit" class="dd-btn-submit">ACCUSER ›</button>
            </div>
            <?php if ($error): ?><div class="dd-error-msg">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
          </form>
          <?php if ($hintActive): ?>
            <div class="dd-hint-box"><?= htmlspecialchars($hints[1]) ?></div>
          <?php else: ?>
            <form method="POST">
              <button type="submit" name="use_hint" class="dd-hint-toggle">[ INDICE −50pts ]</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <?php elseif ($enquete === 2): ?>
      <!-- ════════ ENQUÊTE 2 : LE LIVRE ════════ -->
      <div class="dd-panel">
        <div class="dd-enquete-badge">ENQUÊTE 02 — LE TÉMOIGNAGE DE MARLÈNE</div>
        <div class="dd-enquete-title">Le Livre de <span>314 Pages</span></div>
        <div class="dd-narrative">
          Viktor craque. Il admet avoir quitté le cinéma à 22h pour espionner Marlène.
          Le détective retourne voir Marlène. Elle lui tend son livre pour prouver sa bonne foi.
          Il le feuillette machinalement... et remarque quelque chose.
        </div>

        <div class="dd-dossier">
          <div class="dd-dossier-label">📁 Observation — Le livre de Marlène</div>

          <div class="dd-proof" style="margin-bottom:0;">
            <div class="dd-proof-content">
              <span class="dd-proof-line"><span class="dd-proof-key">TITRE</span> <span class="dd-proof-val">— "Les Nuits de Prague", 314 pages</span></span>
              <span class="dd-proof-line"><span class="dd-proof-key">ÉTAT</span> <span class="dd-proof-val">— signet coincé à la dernière page</span></span>
              <span class="dd-proof-line">&nbsp;</span>
              <span class="dd-proof-line"><span class="dd-proof-key">OBSERVATION</span></span>
              <span class="dd-proof-line dd-proof-dim">La page 1 est à droite (recto).</span>
              <span class="dd-proof-line dd-proof-dim">La page 2 est à gauche (verso de la page 1).</span>
              <span class="dd-proof-line dd-proof-dim">Chaque feuille physique contient donc 2 pages.</span>
              <span class="dd-proof-line">&nbsp;</span>
              <span class="dd-proof-line"><span class="dd-proof-key">QUESTION</span> <span class="dd-proof-val">— Combien de feuilles physiques dans ce livre ?</span></span>
              <span class="dd-proof-line">&nbsp;</span>
              <span class="dd-proof-line dd-proof-dim">Une personne qui a <em>vraiment</em> lu ce livre ce soir-là saurait répondre sans hésiter.<span class="dd-blink"></span></span>
            </div>
          </div>
        </div>

        <div class="dd-input-section">
          <div class="dd-input-label">COMBIEN DE FEUILLES DANS UN LIVRE DE 314 PAGES ?</div>
          <form method="POST">
            <div class="dd-input-row">
              <input type="text" name="rep" class="dd-code-input" placeholder="___" autocomplete="off" autofocus>
              <button type="submit" class="dd-btn-submit">VALIDER ›</button>
            </div>
            <?php if ($error): ?><div class="dd-error-msg">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
          </form>
          <?php if ($hintActive): ?>
            <div class="dd-hint-box"><?= htmlspecialchars($hints[2]) ?></div>
          <?php else: ?>
            <form method="POST">
              <button type="submit" name="use_hint" class="dd-hint-toggle">[ INDICE −50pts ]</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <?php elseif ($enquete === 3):
        // Ticket en 4 morceaux — ordre mélangé affiché, ordre correct = [0,1,2,3]
        $pieces = [
          ['id'=>0, 'label'=>"LE VIEUX PORT\nMercredi 22:47\nTable 4"],
          ['id'=>1, 'label'=>"1x Sole meunière\n  14,00€"],
          ['id'=>2, 'label'=>"1x Agneau rôti\n  24,00€"],
          ['id'=>3, 'label'=>"Total : 38,00€\nMerci de votre visite"],
        ];
        $shuffled = [2, 0, 3, 1];
      ?>
      <!-- ════════ ENQUÊTE 3 : LE TICKET ════════ -->
      <div class="dd-panel">
        <div class="dd-enquete-badge">ENQUÊTE 03 — L'APPARTEMENT DE SAUL</div>
        <div class="dd-enquete-title">Le Ticket <span>Déchiré</span></div>
        <div class="dd-narrative">
          Marlène ne répond pas à la question du livre. Elle ment aussi — mais elle n'est pas la meurtrière.
          Le détective fouille l'appartement de Saul. Saul avait dit <em>"pas de reçu"</em>.
          Dans la poubelle : un ticket déchiré en 4 morceaux, et dans une pièce cachée — un arsenal d'armes.
        </div>

        <div class="dd-dossier">
          <div class="dd-dossier-label">📁 Preuve — Ticket de restaurant reconstitué</div>
          <div class="dd-ticket-grid" id="dd-ticket">
            <?php foreach ($shuffled as $pos => $pieceId): ?>
            <div class="dd-ticket-piece"
                 data-pos="<?= $pos ?>"
                 data-piece="<?= $pieceId ?>"
                 draggable="true">
              <?= nl2br(htmlspecialchars($pieces[$pieceId]['label'])) ?>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="dd-ticket-reveal" id="dd-ticket-reveal">[ ASSEMBLEZ LES MORCEAUX... ]</div>

          <div class="dd-proof" style="margin-bottom:0;">
            <div class="dd-proof-content">
              <span class="dd-proof-line"><span class="dd-proof-key">INFO RESTAURANT</span></span>
              <span class="dd-proof-line dd-proof-dim">Le Vieux Port est un restaurant de fruits de mer.</span>
              <span class="dd-proof-line dd-proof-red">Règle de la maison depuis 1987 : PAS DE VIANDE le mercredi.</span>
              <span class="dd-proof-line">&nbsp;</span>
              <span class="dd-proof-line dd-proof-dim">Saul prétendait ne pas avoir de reçu. Ce ticket prouve qu'il ment.<span class="dd-blink"></span></span>
            </div>
          </div>
        </div>

        <div class="dd-input-section">
          <div class="dd-input-label">QUI A UN ALIBI FABRIQUÉ DE TOUTES PIÈCES ?</div>
          <form method="POST">
            <div class="dd-input-row">
              <input type="text" name="rep" class="dd-code-input" placeholder="NOM DU SUSPECT" autocomplete="off" autofocus>
              <button type="submit" class="dd-btn-submit">ACCUSER ›</button>
            </div>
            <?php if ($error): ?><div class="dd-error-msg">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
          </form>
          <?php if ($hintActive): ?>
            <div class="dd-hint-box"><?= htmlspecialchars($hints[3]) ?></div>
          <?php else: ?>
            <form method="POST">
              <button type="submit" name="use_hint" class="dd-hint-toggle">[ INDICE −50pts ]</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <script>
      const pieces = document.querySelectorAll('.dd-ticket-piece');
      let dragSrc = null;
      pieces.forEach(p => {
        p.addEventListener('dragstart', e => { dragSrc = p; e.dataTransfer.effectAllowed = 'move'; });
        p.addEventListener('dragover',  e => { e.preventDefault(); p.classList.add('drag-over'); });
        p.addEventListener('dragleave', () => p.classList.remove('drag-over'));
        p.addEventListener('drop', e => {
          e.preventDefault(); p.classList.remove('drag-over');
          if (dragSrc === p) return;
          const tmp = dragSrc.innerHTML, tmpPiece = dragSrc.dataset.piece;
          dragSrc.innerHTML = p.innerHTML; dragSrc.dataset.piece = p.dataset.piece;
          p.innerHTML = tmp; p.dataset.piece = tmpPiece;
          checkTicket();
        });
      });
      function checkTicket() {
        let ok = 0;
        document.querySelectorAll('.dd-ticket-piece').forEach((p, i) => {
          const correct = parseInt(p.dataset.piece) === i;
          p.classList.toggle('solved-piece', correct);
          if (correct) ok++;
        });
        const reveal = document.getElementById('dd-ticket-reveal');
        if (ok === 4) {
          reveal.innerHTML = '<span style="color:var(--neon-green)">✓ TICKET RECONSTITUÉ — Agneau rôti commandé un mercredi</span>';
        } else {
          reveal.textContent = '[ ' + ok + '/4 MORCEAUX EN PLACE ]';
        }
      }
      checkTicket();
      </script>

      <?php elseif ($enquete === 4): ?>
      <!-- ════════ ENQUÊTE 4 : LA TIMELINE ════════ -->
      <div class="dd-panel">
        <div class="dd-enquete-badge">ENQUÊTE 04 — LA NUIT DU CRIME</div>
        <div class="dd-enquete-title">La <span>Vérité</span></div>
        <div class="dd-narrative">
          Saul ment — mais le ticket prouve qu'il était au restaurant à 22h47.
          Il ne pouvait pas commettre le meurtre à 23h15. Mais il a fabriqué son alibi pour couvrir quelqu'un.
          Dans la pièce cachée : des armes illégales. Il en a fourni une. À qui ?
          Remettez les événements dans l'ordre pour reconstituer la nuit du crime.
        </div>

        <div class="dd-dossier">
          <div class="dd-dossier-label">📁 Chronologie — Mercredi soir</div>

          <?php
          $events = [
            ['id'=>0, 'time'=>'20h00', 'text'=>'Viktor entre au cinéma (film de 2h)'],
            ['id'=>1, 'time'=>'22h00', 'text'=>'Viktor quitte le cinéma et surveille l\'appartement de Marlène'],
            ['id'=>2, 'time'=>'22h30', 'text'=>'Iris appelle Saul depuis le bureau — dernier appel enregistré'],
            ['id'=>3, 'time'=>'22h47', 'text'=>'Saul règle son repas au Vieux Port (ticket retrouvé)'],
            ['id'=>4, 'time'=>'23h00', 'text'=>'Les néons du bureau s\'éteignent — Iris part'],
            ['id'=>5, 'time'=>'23h15', 'text'=>'Heure du décès — arme de calibre 9mm, fournie par Saul'],
          ];
          $shuffledEvents = [3, 5, 0, 2, 4, 1];
          ?>

          <div class="dd-timeline" id="dd-timeline">
            <?php foreach ($shuffledEvents as $pos => $evId): ?>
            <div class="dd-timeline-item"
                 data-pos="<?= $pos ?>"
                 data-ev="<?= $evId ?>"
                 draggable="true">
              <div class="dd-timeline-dot"></div>
              <span class="dd-timeline-time"><?= $events[$evId]['time'] ?></span>
              <span><?= htmlspecialchars($events[$evId]['text']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="dd-ticket-reveal" id="dd-tl-reveal">[ RECONSTITUEZ LA CHRONOLOGIE... ]</div>
        </div>

        <div class="dd-dossier" style="margin-top:16px;">
          <div class="dd-dossier-label">⚖️ Verdict — Assignez un rôle à chaque suspect</div>
          <form method="POST" id="dd-verdict-form">
            <div class="dd-role-grid">
              <?php foreach (['Viktor','Marlène','Saul','Iris'] as $name):
                $key = strtolower(str_replace('è','e',$name)); ?>
              <div class="dd-role-row">
                <span class="dd-role-name"><?= $name ?></span>
                <div class="dd-role-options">
                  <?php foreach (['innocent','complice','coupable'] as $role): ?>
                  <button type="button"
                          class="dd-role-btn"
                          data-suspect="<?= $key ?>"
                          data-role="<?= $role ?>"
                          onclick="selectRole('<?= $key ?>','<?= $role ?>',this)">
                    <?= strtoupper($role) ?>
                  </button>
                  <?php endforeach; ?>
                  <input type="hidden" name="roles[<?= $key ?>]" id="role-<?= $key ?>" value="">
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="rep" value="verdict">
            <?php if ($error): ?><div class="dd-error-msg">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
            <div class="dd-input-section" style="border-top:none;padding-top:0;">
              <button type="submit" class="dd-btn-submit" style="width:100%;margin-top:16px;">RENDRE LE VERDICT ›</button>
            </div>
          </form>
          <?php if ($hintActive): ?>
            <div class="dd-hint-box"><?= htmlspecialchars($hints[4]) ?></div>
          <?php else: ?>
            <form method="POST">
              <button type="submit" name="use_hint" class="dd-hint-toggle">[ INDICE −50pts ]</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <script>
      // Timeline drag & drop
      const tlItems = document.querySelectorAll('.dd-timeline-item');
      let tlDragSrc = null;
      tlItems.forEach(item => {
        item.addEventListener('dragstart', e => { tlDragSrc = item; e.dataTransfer.effectAllowed = 'move'; });
        item.addEventListener('dragover',  e => { e.preventDefault(); item.classList.add('drag-over'); });
        item.addEventListener('dragleave', () => item.classList.remove('drag-over'));
        item.addEventListener('drop', e => {
          e.preventDefault(); item.classList.remove('drag-over');
          if (tlDragSrc === item) return;
          const tmp = tlDragSrc.innerHTML, tmpEv = tlDragSrc.dataset.ev;
          tlDragSrc.innerHTML = item.innerHTML; tlDragSrc.dataset.ev = item.dataset.ev;
          item.innerHTML = tmp; item.dataset.ev = tmpEv;
          checkTimeline();
        });
      });
      function checkTimeline() {
        let ok = 0;
        document.querySelectorAll('.dd-timeline-item').forEach((item, i) => {
          const correct = parseInt(item.dataset.ev) === i;
          item.classList.toggle('solved-item', correct);
          if (correct) ok++;
        });
        const reveal = document.getElementById('dd-tl-reveal');
        if (ok === 6) {
          reveal.innerHTML = '<span style="color:var(--neon-green)">✓ CHRONOLOGIE RECONSTITUÉE — Iris était libre à 23h00</span>';
        } else {
          reveal.textContent = '[ ' + ok + '/6 ÉVÉNEMENTS EN ORDRE ]';
        }
      }
      checkTimeline();

      // Sélection des rôles
      function selectRole(suspect, role, btn) {
        // Désélectionner les autres boutons du même suspect
        document.querySelectorAll('[data-suspect="'+suspect+'"]').forEach(b => {
          b.classList.remove('selected-innocent','selected-complice','selected-coupable');
        });
        btn.classList.add('selected-'+role);
        document.getElementById('role-'+suspect).value = role;
      }
      </script>

      <?php endif; ?>
    </div><!-- /main -->

    <!-- ── Sidebar ── -->
    <div class="dd-sidebar">
      <div class="dd-sidebar-title">▸ Dossier d'enquête</div>

      <div class="dd-suspects-list">
        <?php
        $names = ['Viktor','Marlène','Saul','Iris'];
        foreach ($names as $i => $n):
          $cleared = ($enquete > $i + 1);
        ?>
        <div class="dd-suspect-item <?= $cleared ? 'cleared' : '' ?>">
          <?= $n ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="dd-sidebar-info">
        <strong>ENQUÊTE <?= str_pad($enquete,2,'0',STR_PAD_LEFT) ?>/04</strong>
        Score estimé :<br>
        <span id="dd-sidebar-score" style="color:#fbbf24;font-size:1.1rem;font-family:var(--font-mono);">—</span> pts<br><br>
        Indices : <span style="color:<?= $hintsUsed > 0 ? '#f87171' : 'var(--neon-green)' ?>"><?= $hintsUsed ?></span>
        <?php if ($hintsUsed > 0): ?>
        <span style="color:#f87171;font-size:.65rem;display:block;">−<?= $hintsUsed * 50 ?> pts</span>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /dd-layout -->
  <?php endif; ?>

</div><!-- /dd-wrap -->

<?php if (!$victory): ?>
<script>
const _ddStart = <?= $_SESSION['dd_started'] ?>;
const _ddHints = <?= $hintsUsed ?>;
const _ddWrong = <?= $wrongCount ?>;
function ddUpdateLive() {
  const s     = Math.floor(Date.now() / 1000) - _ddStart;
  const score = Math.max(100, 500 - Math.floor(s / 10) - (_ddHints * 50) - (_ddWrong * 25));
  const mm    = String(Math.floor(s / 60)).padStart(2, '0');
  const ss    = String(s % 60).padStart(2, '0');
  const el1   = document.getElementById('dd-live-score');
  const el2   = document.getElementById('dd-sidebar-score');
  const elt   = document.getElementById('dd-live-time');
  if (el1) el1.textContent = score;
  if (el2) el2.textContent = score;
  if (elt) elt.textContent = mm + ':' + ss;
}
ddUpdateLive();
setInterval(ddUpdateLive, 1000);
</script>
<?php endif; ?>

<?php require '../../includes/footer.php'; ?>