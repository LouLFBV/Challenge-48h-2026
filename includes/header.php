<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}

$user = null;
if (!empty($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :uid");
        $stmt->execute(['uid' => $_SESSION['user_id']]);
        $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dbUser) {
            if(!empty($dbUser['name'])) {
                $_SESSION['name'] = $dbUser['name'];
            }

            $user = [
                'name'   => !empty($dbUser['name']) ? $dbUser['name'] : (!empty($_SESSION['name']) ? $_SESSION['name'] : 'Agent'),
                'email'  => $dbUser['email']  ?? $_SESSION['email'] ?? '',
                'avatar' => $dbUser['avatar'] ?? $_SESSION['avatar'] ?? null,
                'role'   => $dbUser['role']   ?? 'user',
            ];
        }
    } catch (Exception $e) {
        if (!empty($_SESSION['name'])) {
            $user = [
                'name'   => $_SESSION['name'],
                'email'  => $_SESSION['email']  ?? '',
                'avatar' => $_SESSION['avatar'] ?? null,
                'role'   => $_SESSION['role']   ?? 'user',
            ];
        }
    }
}

$page    ??= '';
$isAdmin   = isset($user['role']) && $user['role'] === 'admin';
$initial   = $user ? strtoupper(substr($user['name'], 0, 1)) : '?';

$userRank  = null;
$userScore = null;
if ($user && !empty($_SESSION['user_id'])) {
    try {
        $rankStmt = $pdo->prepare("
            SELECT user_rank, total_score
            FROM (
                SELECT id,
                       total_score,
                       name,
                       ROW_NUMBER() OVER (ORDER BY total_score DESC, name ASC) AS user_rank
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
    } catch (Exception $e) { }
}

if (!function_exists('getRankBadge')) {
    function getRankBadge(int $rank): string {
        return match(true) {
            $rank === 1 => '🥇',
            $rank === 2 => '🥈',
            $rank === 3 => '🥉',
            default      => '#' . $rank,
        };
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EnYgmes</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../public/css/style.css">
  <?php if (!$user): ?>
    <link rel="stylesheet" href="../public/css/auth.css">
  <?php endif; ?>
</head>
<body class="cyberpunk-theme">

<header class="site-header" role="banner">
  <div class="header-inner">

    <a href="../layout/index.php" class="header-brand" aria-label="EnYgmes — Accueil">
      <div class="brand-logo">
        <svg viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <polygon points="19,2 35,10.5 35,27.5 19,36 3,27.5 3,10.5" fill="none" stroke="#00f0ff" stroke-width="1.5" />
          <text x="19" y="25" text-anchor="middle" font-family="Orbitron, monospace" font-size="14" font-weight="800" fill="#00f0ff">E</text>
          <circle cx="28" cy="10" r="5" fill="#0a0c10" stroke="#a855f7" stroke-width="1.2"/>
          <text x="28" y="13.5" text-anchor="middle" font-family="Orbitron, monospace" font-size="7" font-weight="700" fill="#a855f7">?</text>
        </svg>
      </div>
      <div>
        <span class="brand-name">En<span>Ygmes</span></span>
        <span class="brand-tag">&gt; solve_it.exe</span>
      </div>
    </a>

    <nav class="header-nav" role="navigation" aria-label="Navigation principale">
      <a href="../layout/chat.php" class="nav-btn nav-btn--chat<?= $page === 'chat' ? ' nav-btn--active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <span>Chat Global</span>
      </a>

      <a href="../layout/classement.php" class="nav-btn<?= $page === 'classement' ? ' nav-btn--active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
          <polyline points="17 6 23 6 23 12"/>
        </svg>
        <span>Classement</span>
      </a>

      <div class="nav-divider" aria-hidden="true"></div>

      <?php if (!$user): ?>
        <a href="../auth/login.php" class="nav-btn nav-btn--login">
          <span>Connexion</span>
        </a>
      <?php else: ?>
        <div class="user-menu" id="userMenu">
          <button class="user-trigger" id="userTrigger" aria-haspopup="true" aria-expanded="false">
            
            <div class="avatar-container" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; overflow: hidden; background: #0a0c10; border: 1px solid #00f0ff;">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="../public/uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>" 
                         alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user-astronaut" style="color: #00f0ff; font-size: 14px;"></i>
                <?php endif; ?>
            </div>

            <div class="user-info">
              <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
            </div>

            <svg class="user-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>

          <div class="user-dropdown" id="userDropdown" role="menu">
            <div class="dropdown-header">
              <div class="dropdown-username"><?= htmlspecialchars($user['name']) ?></div>
              <div class="dropdown-role">&gt; <?= $isAdmin ? 'ADMIN' : 'MEMBRE' ?></div>
            </div>

            <a href="../layout/profil.php" class="dropdown-item" role="menuitem">
              <i class="fas fa-user" style="margin-right: 8px;"></i> Mon profil
            </a>

            <a href="../layout/parametres.php" class="dropdown-item" role="menuitem">
              <i class="fas fa-cog" style="margin-right: 8px;"></i> Paramètres
            </a>

            <?php if ($isAdmin): ?>
              <div class="dropdown-sep"></div>
              <a href="../layout/admin.php" class="dropdown-item dropdown-item--admin" role="menuitem">
                <i class="fas fa-shield-alt" style="margin-right: 8px;"></i> Panel Admin
              </a>
            <?php endif; ?>

            <div class="dropdown-sep"></div>
            <a href="../auth/logout.php" class="dropdown-item dropdown-item--logout" role="menuitem">
              <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i> Déconnexion
            </a>
          </div>
        </div>
      <?php endif; ?>
    </nav>
  </div>
</header>

<script>
(function () {
  const menu = document.getElementById('userMenu');
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