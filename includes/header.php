<?php
/**
 * header.php — EnYgmes
 *
 * Variables optionnelles (définies avant l'include) :
 *   $user  = null | tableau utilisateur (auto-rempli depuis $_SESSION si absent)
 *   $page  = string — page active : 'chat'|'classement'|'profil'|...
 */

// ── Session : démarrer seulement si pas déjà active ──
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Reconstruction de $user depuis la session ──
$user ??= null;
if (!$user && !empty($_SESSION['user_id'])) {
    $user = [
        'name'   => $_SESSION['name']   ?? 'Utilisateur',
        'avatar' => $_SESSION['avatar'] ?? null,
        'is_admin' => $_SESSION['is_admin'] ?? false,
    ];
}

$page    ??= '';
$isAdmin = !empty($user['is_admin']);
$initial   = $user ? strtoupper(substr($user['name'], 0, 1)) : '';

// ── Classement de l'utilisateur connecté ──
$userRank  = null;
$userScore = null;
if ($user && !empty($_SESSION['user_id'])) {
    // Charger $pdo si pas encore fait (cas où database.php n'est pas require avant le header)
    if (!isset($pdo)) {
        require_once __DIR__ . '/../config/database.php';
    }
    try {
        $rankStmt = $pdo->prepare("
            SELECT user_rank, total_score
            FROM (
                SELECT id,
                       total_score,
                       RANK() OVER (ORDER BY total_score DESC) AS user_rank
                FROM users
            ) ranked
            WHERE id = :uid
        ");
        $rankStmt->execute(['uid' => $_SESSION['user_id']]);
        $rankRow = $rankStmt->fetch(PDO::FETCH_ASSOC);
        if ($rankRow) {
            $userRank  = (int) $rankRow['user_rank'];
            $userScore = (int) $rankRow['total_score'];
        }
    } catch (Exception $e) {
        // Silencieux : la table peut ne pas encore exister
    }
}

// Médaille selon le rang
function getRankBadge(int $rank): string {
    return match(true) {
        $rank === 1 => '🥇',
        $rank === 2 => '🥈',
        $rank === 3 => '🥉',
        default     => '#' . $rank,
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EnYgmes</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <link rel="stylesheet" href="../public/css/auth.css">
</head>
<body>

<header class="site-header" role="banner">
  <div class="header-inner">

    <!-- ═══ BRAND (gauche) ═══ -->
    <a href="/Challenge-48h-2026/layout/index.php" class="header-brand" aria-label="EnYgmes — Accueil">
      <div class="brand-logo">
        <svg viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <polygon
            points="19,2 35,10.5 35,27.5 19,36 3,27.5 3,10.5"
            fill="none"
            stroke="#00f0ff"
            stroke-width="1.5"
          />
          <text
            x="19" y="25"
            text-anchor="middle"
            font-family="Orbitron, monospace"
            font-size="14"
            font-weight="800"
            fill="#00f0ff"
          >E</text>
          <circle cx="28" cy="10" r="5" fill="#0a0c10" stroke="#a855f7" stroke-width="1.2"/>
          <text
            x="28" y="13.5"
            text-anchor="middle"
            font-family="Orbitron, monospace"
            font-size="7"
            font-weight="700"
            fill="#a855f7"
          >?</text>
        </svg>
      </div>

      <div>
        <span class="brand-name">En<span>Ygmes</span></span>
        <span class="brand-tag">&gt; solve_it.exe</span>
      </div>
    </a>

    <!-- ═══ NAV (droite) ═══ -->
    <nav class="header-nav" role="navigation" aria-label="Navigation principale">

      <!-- Chat Global -->
      <a href="../layout/chat.php"
         class="nav-btn nav-btn--chat<?= $page === 'chat' ? ' nav-btn--active' : '' ?>"
         aria-current="<?= $page === 'chat' ? 'page' : 'false' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <span>Chat Global</span>
      </a>

      <!-- Classement -->
      <a href="../layout/classement.php"
         class="nav-btn<?= $page === 'classement' ? ' nav-btn--active' : '' ?>"
         aria-current="<?= $page === 'classement' ? 'page' : 'false' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
          <polyline points="17 6 23 6 23 12"/>
        </svg>
        <span>Classement</span>
      </a>

      <div class="nav-divider" aria-hidden="true"></div>

      <?php if (!$user): ?>
        <!-- ── GUEST : Login + Register ── -->
        <a href="../auth/login.php" class="nav-btn nav-btn--login">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
            <polyline points="10 17 15 12 10 7"/>
            <line x1="15" y1="12" x2="3" y2="12"/>
          </svg>
          <span>Connexion</span>
        </a>
        <a href="../auth/register.php" class="nav-btn nav-btn--register">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="8.5" cy="7" r="4"/>
            <line x1="20" y1="8" x2="20" y2="14"/>
            <line x1="23" y1="11" x2="17" y2="11"/>
          </svg>
          <span>Inscription</span>
        </a>

      <?php else: ?>
        <!-- ── CONNECTÉ : User dropdown ── -->
        <div class="user-menu" id="userMenu">
          <button class="user-trigger"
                  aria-haspopup="true"
                  aria-expanded="false"
                  aria-controls="userDropdown"
                  id="userTrigger">

            <?php if (!empty($user['avatar'])): ?>
              <img src="<?= htmlspecialchars($user['avatar']) ?>"
                   alt="Avatar de <?= htmlspecialchars($user['name']) ?>"
                   class="user-avatar"
                   width="32" height="32">
            <?php else: ?>
              <div class="user-avatar-placeholder" aria-hidden="true">
                <?= htmlspecialchars($initial) ?>
              </div>
            <?php endif; ?>

            <div class="user-info">
              <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
              <?php if ($userRank !== null): ?>
              <span class="user-rank-badge">
                <?= $userRank <= 3 ? getRankBadge($userRank) : '' ?>
                <?= $userRank > 3 ? '<span class="rank-hash">#</span>' . $userRank : '' ?>
              </span>
              <?php endif; ?>
            </div>

            <svg class="user-caret" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>

          <!-- Dropdown -->
          <div class="user-dropdown" id="userDropdown" role="menu" aria-labelledby="userTrigger">

            <div class="dropdown-header">
              <div class="dropdown-username"><?= htmlspecialchars($user['name']) ?></div>
              <div class="dropdown-role">&gt; <?= $isAdmin ? 'ADMIN' : 'MEMBRE' ?></div>
              <?php if ($userRank !== null): ?>
              <div class="dropdown-rank">
                <span class="dropdown-rank-pos"><?= getRankBadge($userRank) ?><?= $userRank > 3 ? htmlspecialchars($userRank) : '' ?></span>
                <span class="dropdown-rank-score"><?= number_format($userScore, 0, ',', ' ') ?> pts</span>
              </div>
              <?php endif; ?>
            </div>

            <a href="../layout/profil.php" class="dropdown-item" role="menuitem">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
              Mon profil
            </a>

            <a href="../layout/parametres.php" class="dropdown-item" role="menuitem">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06
                         a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09
                         A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83
                         l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09
                         A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83
                         l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09
                         a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83
                         l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09
                         a1.65 1.65 0 0 0-1.51 1z"/>
              </svg>
              Paramètres
            </a>

            <?php if ($isAdmin): ?>
              <div class="dropdown-sep" role="separator"></div>
              <a href="../layout/admin.php" class="dropdown-item dropdown-item--admin" role="menuitem">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                  <line x1="8" y1="21" x2="16" y2="21"/>
                  <line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
                Panel Admin
              </a>
            <?php endif; ?>

            <div class="dropdown-sep" role="separator"></div>

            <a href="../auth/logout.php" class="dropdown-item dropdown-item--logout" role="menuitem">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
              </svg>
              Déconnexion
            </a>
          </div>
        </div>

      <?php endif; ?>
    </nav>
  </div>
</header>

<script>
(function () {
  const menu    = document.getElementById('userMenu');
  const trigger = document.getElementById('userTrigger');
  if (!menu || !trigger) return;

  function toggleMenu(force) {
    const isOpen = typeof force !== 'undefined' ? force : !menu.classList.contains('open');
    menu.classList.toggle('open', isOpen);
    trigger.setAttribute('aria-expanded', String(isOpen));
  }

  trigger.addEventListener('click', function (e) {
    e.stopPropagation();
    toggleMenu();
  });

  document.addEventListener('click', function () { toggleMenu(false); });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') toggleMenu(false);
  });
})();
</script>