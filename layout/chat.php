<?php
/**
 * chat.php — EnYgmes · Chat Global
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirection si non connecté
if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$page = 'chat';
require '../includes/header.php';

$currentUserId   = (int) $_SESSION['user_id'];
$currentUsername = htmlspecialchars($_SESSION['name'] ?? 'Utilisateur');
?>


<!-- ═══ PAGE CHAT ═══ -->
<div class="chat-page">

  <!-- Header -->
  <div class="chat-header">
    <div class="chat-header-left">
      <div class="chat-title"># <span>global</span></div>
      <div class="online-badge">
        <div class="online-dot"></div>
        EN LIGNE
      </div>
    </div>
    <div class="users-count" id="usersCount">&nbsp;</div>
  </div>

  <!-- Messages -->
  <div class="chat-messages" id="chatMessages">
    <!-- Skeletons de chargement -->
    <?php for ($i = 0; $i < 4; $i++): ?>
    <div class="msg-skeleton">
      <div class="skel-avatar"></div>
      <div class="skel-lines">
        <div class="skel-line" style="width:<?= [80,120,90,110][$i] ?>px"></div>
        <div class="skel-line" style="width:<?= [200,160,240,180][$i] ?>px"></div>
      </div>
    </div>
    <?php endfor; ?>
  </div>

  <!-- Indicateur nouveau message -->
  <div class="new-msg-indicator" id="newMsgIndicator">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
         stroke-linecap="round" stroke-linejoin="round">
      <polyline points="6 9 12 15 18 9"/>
    </svg>
    Nouveau message
  </div>

  <!-- Saisie -->
  <div class="chat-input-area">
    <div class="chat-input-wrap">
      <textarea id="msgInput"
                rows="1"
                placeholder="Envoie un message… (Entrée pour envoyer)"
                maxlength="1000"
                aria-label="Message"></textarea>
      <button class="send-btn" id="sendBtn" aria-label="Envoyer" disabled>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <line x1="22" y1="2" x2="11" y2="13"/>
          <polygon points="22 2 15 22 11 13 2 9 22 2"/>
        </svg>
      </button>
    </div>
    <div class="input-hint">
      <span id="charCount">0</span>/1000 · Maj+Entrée pour saut de ligne
    </div>
  </div>
</div>

<!-- Toast -->
<div class="chat-toast" id="chatToast"></div>

<script>
(function () {
  'use strict';

  /* ── Config ── */
  const POLL_INTERVAL = 2000;   // ms entre chaque polling
  const ME_ID         = <?= $currentUserId ?>;
  const ME_NAME       = <?= json_encode($currentUsername) ?>;
  const POLL_URL      = '../layout/chat_poll.php';

  /* ── DOM ── */
  const $messages  = document.getElementById('chatMessages');
  const $input     = document.getElementById('msgInput');
  const $sendBtn   = document.getElementById('sendBtn');
  const $toast     = document.getElementById('chatToast');
  const $indicator = document.getElementById('newMsgIndicator');

  /* ── État ── */
  let lastId        = 0;
  let pollTimer     = null;
  let sending       = false;
  let lastDate      = null;
  let userAtBottom  = true;

  /* ══════════════════════════════
     UTILS
  ══════════════════════════════ */

  function escapeHtml(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function isAtBottom() {
    return $messages.scrollHeight - $messages.scrollTop - $messages.clientHeight < 60;
  }

  function scrollToBottom(smooth = true) {
    $messages.scrollTo({
      top: $messages.scrollHeight,
      behavior: smooth ? 'smooth' : 'instant'
    });
  }

  function showToast(msg) {
    $toast.textContent = msg;
    $toast.classList.add('show');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => $toast.classList.remove('show'), 3500);
  }

  /* ══════════════════════════════
     RENDU DES MESSAGES
  ══════════════════════════════ */

  function buildAvatar(msg) {
    const initial = msg.username.charAt(0).toUpperCase();
    const isMe = (msg.username === ME_NAME);
    const avatarHtml = msg.avatar && msg.avatar !== 'default.png'
      ? `<img class="msg-avatar"
               src="../public/uploads/${escapeHtml(msg.avatar)}"
               alt="${escapeHtml(msg.username)}"
               width="34" height="34"
               onerror="this.outerHTML=buildAvatarFallback('${escapeHtml(initial)}')">`
      : `<div class="msg-avatar-placeholder" aria-hidden="true">${escapeHtml(initial)}</div>`;
    
    return isMe 
      ? avatarHtml
      : `<a href="../layout/profil.php?user_id=${msg.user_id}" style="text-decoration: none;">${avatarHtml}</a>`;
  }

  function buildDateSeparator(date) {
    const el = document.createElement('div');
    el.className = 'date-separator';
    el.innerHTML = `<span>${escapeHtml(date)}</span>`;
    return el;
  }

  function buildMessageEl(msg) {
    const isMe = (msg.username === ME_NAME);
    const wrap = document.createElement('div');
    wrap.className = `msg${isMe ? ' msg--me' : ''}`;
    wrap.dataset.id = msg.id;

    const authorHtml = isMe 
      ? `<span class="msg-author">${escapeHtml(msg.username)}</span>`
      : `<a href="../layout/profil.php?user_id=${msg.user_id}" class="msg-author" style="cursor: pointer; text-decoration: none; color: inherit; hover-color: #00f0ff;">${escapeHtml(msg.username)}</a>`;

    wrap.innerHTML = `
      ${buildAvatar(msg)}
      <div class="msg-body">
        <div class="msg-meta">
          ${authorHtml}
          <span class="msg-time">${escapeHtml(msg.time)}</span>
        </div>
        <div class="msg-text">${escapeHtml(msg.message)}</div>
      </div>`;
    return wrap;
  }

  function appendMessages(msgs, initial = false) {
    if (initial) {
      // Vider les skeletons
      $messages.innerHTML = '';
      if (msgs.length === 0) {
        $messages.innerHTML = `
          <div style="text-align:center;padding:40px 0;font-family:var(--font-mono);
                      font-size:0.8rem;color:var(--text-dim);">
            <div style="font-size:1.5rem;margin-bottom:10px;opacity:0.4">[ ]</div>
            Aucun message pour l'instant.<br>Soyez le premier à écrire !
          </div>`;
        return;
      }
    }

    const wasAtBottom = isAtBottom();

    msgs.forEach(msg => {
      // Séparateur de date
      if (msg.date !== lastDate) {
        $messages.appendChild(buildDateSeparator(msg.date));
        lastDate = msg.date;
      }
      $messages.appendChild(buildMessageEl(msg));
      if (msg.id > lastId) lastId = msg.id;
    });

    if (initial) {
      scrollToBottom(false);
    } else if (wasAtBottom || msgs.some(m => m.username === ME_NAME)) {
      scrollToBottom(true);
      $indicator.classList.remove('show');
    } else {
      // L'utilisateur est en train de scroller vers le haut
      $indicator.classList.add('show');
    }
  }

  /* ══════════════════════════════
     CHARGEMENT INITIAL
  ══════════════════════════════ */

  async function loadInitial() {
    try {
      const res  = await fetch(POLL_URL + '?last_id=0');
      const data = await res.json();
      if (data.messages) {
        appendMessages(data.messages, true);
        startPolling();
      }
    } catch (e) {
      showToast('Impossible de charger le chat.');
    }
  }

  /* ══════════════════════════════
     POLLING TEMPS RÉEL
  ══════════════════════════════ */

  async function poll() {
    try {
      const res  = await fetch(`${POLL_URL}?last_id=${lastId}`);
      if (!res.ok) return;
      const data = await res.json();
      if (data.messages && data.messages.length > 0) {
        appendMessages(data.messages);
      }
    } catch (e) {
      // Silencieux en cas de perte réseau temporaire
    } finally {
      pollTimer = setTimeout(poll, POLL_INTERVAL);
    }
  }

  function startPolling() {
    clearTimeout(pollTimer);
    pollTimer = setTimeout(poll, POLL_INTERVAL);
  }

  /* ══════════════════════════════
     ENVOI DE MESSAGE
  ══════════════════════════════ */

  async function sendMessage() {
    const text = $input.value.trim();
    if (!text || sending) return;

    sending = true;
    $sendBtn.disabled = true;
    $input.disabled   = true;

    try {
      const res  = await fetch(POLL_URL, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ message: text }),
      });
      const data = await res.json();

      if (data.message) {
        // Arrêter le polling le temps d'insérer le message localement
        clearTimeout(pollTimer);
        appendMessages([data.message]);
        startPolling();
        $input.value = '';
        $input.style.height = 'auto';
        updateCharCount();
      } else {
        showToast(data.error || 'Erreur lors de l\'envoi.');
      }
    } catch (e) {
      showToast('Impossible d\'envoyer le message.');
    } finally {
      sending = false;
      $sendBtn.disabled = false;
      $input.disabled   = false;
      $input.focus();
    }
  }

  /* ══════════════════════════════
     EVENTS
  ══════════════════════════════ */

  // Auto-resize textarea
  $input.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    updateCharCount();
    $sendBtn.disabled = this.value.trim() === '';
  });

  function updateCharCount() {
    document.getElementById('charCount').textContent = $input.value.length;
  }

  // Entrée pour envoyer, Maj+Entrée pour saut de ligne
  $input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  $sendBtn.addEventListener('click', sendMessage);

  // Détecter si l'utilisateur scroll vers le haut
  $messages.addEventListener('scroll', function () {
    userAtBottom = isAtBottom();
    if (userAtBottom) $indicator.classList.remove('show');
  });

  // Cliquer sur l'indicateur ramène en bas
  $indicator.addEventListener('click', function () {
    scrollToBottom(true);
    $indicator.classList.remove('show');
  });

  // Stopper le polling quand l'onglet est caché
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      clearTimeout(pollTimer);
    } else {
      poll(); // reprend immédiatement
    }
  });

  /* ── Init ── */
  loadInitial();
  $input.focus();

})();
</script>

<?php require '../includes/footer.php'; ?>