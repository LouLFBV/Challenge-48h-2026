<?php
/**
 * header.php — EnYgmes (MERGED VERSION)
 * Combine la flexibilité nécessaire pour les jeux (Lou) 
 * avec la robustesse du chat (Anthony)
 */

// ── Headers anti-cache ──
if (!headers_sent()) {
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// ── Session : démarrer seulement si pas déjà active ──
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Connexion à la base de données (lazy load) ──
if (!isset($pdo)) {
    $dbPath = __DIR__ . '/../config/database.php';
    if (file_exists($dbPath)) {
        require_once $dbPath;
    }
}

// ── Initialisation flexiblede $user ──
$user ??= null;
$userPhotoData = null;

// Récupérer depuis BD si connecté
if (!$user && !empty($_SESSION['user_id'])) {
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, email, profile_image, role, total_score, is_admin FROM users WHERE id = :uid");
            $stmt->execute(['uid' => $_SESSION['user_id']]);
            $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dbUser) {
                $userPhotoData = $dbUser['profile_image'] ?? null;
                $_SESSION['name'] = $dbUser['username'];
                $_SESSION['profile_image'] = $userPhotoData;
                $_SESSION['is_admin'] = (bool)($dbUser['is_admin'] ?? false);

                $user = [
                    'id'       => $dbUser['id'],
                    'name'     => $dbUser['username'],
                    'username' => $dbUser['username'],
                    'email'    => $dbUser['email'] ?? '',
                    'avatar'   => $userPhotoData,
                    'role'     => $dbUser['role'] ?? 'user',
                    'is_admin' => (bool)($dbUser['is_admin'] ?? false),
                    'score'    => $dbUser['total_score'] ?? 0,
                ];
            }
        } catch (Exception $e) {
            // Fallback à la session
        }
    }
}

// Fallback: utiliser la session si on n'a pas pu charger depuis BD
if (!$user && !empty($_SESSION['user_id'])) {
    $username = $_SESSION['username'] ?? $_SESSION['name'] ?? 'Utilisateur';
    $userPhotoData = $_SESSION['profile_image'] ?? null;
    $user = [
        'id'       => $_SESSION['user_id'] ?? null,
        'name'     => $username,
        'username' => $username,
        'email'    => $_SESSION['email'] ?? '',
        'avatar'   => $userPhotoData,
        'role'     => $_SESSION['role'] ?? 'user',
        'is_admin' => $_SESSION['is_admin'] ?? false,
        'score'    => $_SESSION['score'] ?? 0,
    ];
}

// ── Variables du header ──
$page    ??= '';
$isAdmin = !empty($user['is_admin']);
$initial = $user ? strtoupper(substr($user['username'] ?? $user['name'] ?? 'U', 0, 1)) : '';

// ── Ranking (optionnel, charge que si nécessaire) ──
$userRank  = null;
$userScore = null;

if ($user && !empty($_SESSION['user_id'])) {
    // On n'inclut la database que si nécessaire
    if (!isset($pdo)) {
        // Utilisation de __DIR__ pour être sûr du chemin peu importe d'où on l'appelle
        $dbPath = dirname(__DIR__) . '/config/database.php';
        if (file_exists($dbPath)) {
            require_once $dbPath;
        }
    }

    if (isset($pdo)) {
        try {
            $rankStmt = $pdo->prepare("
                SELECT user_rank, total_score
                FROM (
                    SELECT id, total_score, username,
                           ROW_NUMBER() OVER (ORDER BY total_score DESC, username ASC) AS user_rank
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
            // Silencieux
        }
    }
}

// ── Fonction badge ranking ──
if (!function_exists('getRankBadge')) {
    function getRankBadge(int $rank): string {
        return match(true) {
            $rank === 1 => '🥇',
            $rank === 2 => '🥈',
            $rank === 3 => '🥉',
            default     => '#' . $rank,
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
  <link rel="stylesheet" href="/Challenge-48h-2026/public/css/style.css">
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
      <a href="/layout/chat.php"
         class="nav-btn nav-btn--chat<?= $page === 'chat' ? ' nav-btn--active' : '' ?>"
         aria-current="<?= $page === 'chat' ? 'page' : 'false' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <span>Chat Global</span>
      </a>

      <!-- Classement -->
      <a href="/Challenge-48h-2026/layout/classement.php"
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
        <a href="/Challenge-48h-2026/auth/login.php" class="nav-btn nav-btn--login">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
            <polyline points="10 17 15 12 10 7"/>
            <line x1="15" y1="12" x2="3" y2="12"/>
          </svg>
          <span>Connexion</span>
        </a>
        <a href="/Challenge-48h-2026/auth/register.php" class="nav-btn nav-btn--register">
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

            <?php
            // Avatar handling : photo data URL ou placeholder
            $imgSrc = null;
            
            if (!empty($userPhotoData) && strpos($userPhotoData, 'data:') === 0) {
                $imgSrc = $userPhotoData;
            } elseif (!empty($_SESSION['profile_image']) && strpos($_SESSION['profile_image'], 'data:') === 0) {
                $imgSrc = $_SESSION['profile_image'];
            }
            
            if (!empty($imgSrc)): ?>
              <img src="<?= htmlspecialchars($imgSrc) ?>"
                   alt="Avatar de <?= htmlspecialchars($user['name'] ?? 'Utilisateur') ?>"
                   class="user-avatar"
                   style="border-radius: 50%; object-fit: cover; width: 32px; height: 32px;">
            <?php else: ?>
              <div class="user-avatar-placeholder" 
                   style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #a855f7, #00f0ff); font-weight: bold; color: white; font-size: 12px;">
                <?= htmlspecialchars($initial) ?>
              </div>
            <?php endif; ?>
          </button>

          <!-- Dropdown menu -->
          <div class="user-dropdown" id="userDropdown" aria-hidden="true">
            <div class="user-dropdown-profile">
              <div class="user-dropdown-avatar">
                <?php if (!empty($imgSrc)): ?>
                  <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Avatar" style="border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                  <div class="avatar-placeholder-large"><?= htmlspecialchars($initial) ?></div>
                <?php endif; ?>
              </div>
              <div>
                <div class="user-dropdown-name"><?= htmlspecialchars($user['name'] ?? 'Utilisateur') ?></div>
                <div class="user-dropdown-rank">
                  Rank: <?= $userRank ? getRankBadge($userRank) . ' (' . $userRank . ')' : 'Non classé' ?>
                </div>
                <div class="user-dropdown-score">Score: <?= $userScore ?? 0 ?></div>
              </div>
            </div>

            <hr>

            <a href="/Challenge-48h-2026/layout/profil.php" class="dropdown-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
              <span>Mon Profil</span>
            </a>

            <a href="/Challenge-48h-2026/layout/parametres.php" class="dropdown-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="1"/>
                <circle cx="19" cy="12" r="1"/>
                <circle cx="5" cy="12" r="1"/>
              </svg>
              <span>Paramètres</span>
            </a>

            <?php if ($isAdmin): ?>
              <hr>
              <a href="/Challenge-48h-2026/layout/admin.php" class="dropdown-item dropdown-item--admin">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                </svg>
                <span>Admin</span>
              </a>
            <?php endif; ?>

            <hr>
            <a href="/Challenge-48h-2026/auth/logout.php" class="dropdown-item dropdown-item--logout">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
              </svg>
              <span>Déconnexion</span>
            </a>
          </div>
        </div>

      <?php endif; ?>
    </nav>
  </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const userTrigger = document.getElementById('userTrigger');
  const userDropdown = document.getElementById('userDropdown');
  const userMenu = document.getElementById('userMenu');

  if (userTrigger && userDropdown) {
    userTrigger.addEventListener('click', () => {
      const isExpanded = userTrigger.getAttribute('aria-expanded') === 'true';
      userTrigger.setAttribute('aria-expanded', !isExpanded);
      userDropdown.setAttribute('aria-hidden', isExpanded);
    });

    // Ferme le dropdown si clic en dehors
    document.addEventListener('click', (e) => {
      if (!userMenu.contains(e.target)) {
        userTrigger.setAttribute('aria-expanded', 'false');
        userDropdown.setAttribute('aria-hidden', 'true');
      }
    });
  }
});
</script>
