<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Connexion MySQLi
$mysqli = new mysqli("localhost", "root", "", "challenge48_db");
if ($mysqli->connect_error) { die("Échec de la connexion : " . $mysqli->connect_error); }

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

// --- LOGIQUE DE SUPPRESSION ---
if (isset($_GET['del_riddle'])) {
    $id = intval($_GET['del_riddle']);
    $mysqli->query("DELETE FROM riddles WHERE id = $id");
    header("Location: admin.php?msg=Enigme+supprimee"); exit;
}
if (isset($_GET['del_user'])) {
    $id = intval($_GET['del_user']);
    if ($id !== $_SESSION['user_id']) {
        $mysqli->query("DELETE FROM users WHERE id = $id");
        header("Location: admin.php?msg=Joueur+banni"); exit;
    }
}
if (isset($_GET['del_msg'])) {
    $id = intval($_GET['del_msg']);
    $mysqli->query("DELETE FROM general_chat WHERE id = $id");
    header("Location: admin.php?msg=Message+efface"); exit;
}

// --- AJOUT ENIGME ---
if (isset($_POST['add_riddle'])) {
    $title = $mysqli->real_escape_string($_POST['title']);
    $desc = $mysqli->real_escape_string($_POST['description']);
    $ans = $mysqli->real_escape_string($_POST['answer']);
    $pts = intval($_POST['max_points']);
    $diff = $mysqli->real_escape_string($_POST['difficulty']);
    $mysqli->query("INSERT INTO riddles (title, description, answer, max_points, difficulty) VALUES ('$title', '$desc', '$ans', $pts, '$diff')");
    header("Location: admin.php?msg=Enigme+ajoutee"); exit;
}

// --- DONNÉES ---
$all_riddles = $mysqli->query("SELECT * FROM riddles ORDER BY id DESC");
$all_users = $mysqli->query("SELECT * FROM users WHERE id != " . $_SESSION['user_id'] . " ORDER BY total_score DESC"); 
$recent_messages = $mysqli->query("SELECT general_chat.*, users.username FROM general_chat JOIN users ON general_chat.user_id = users.id ORDER BY created_at DESC LIMIT 8");

require_once '../includes/header.php';
?>

<div class="admin-container">
    <header class="admin-topbar">
        <div>
            <h1 class="neon-text">PAGE ADMIN</h1>
            <p class="cyber-tag">> SYSTEM STATUS: <span class="status-online">ONLINE</span></p>
        </div>
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert-success">⚡ <?= htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>
    </header>

    <div class="admin-grid">
        
        <!-- SECTION : AJOUT -->
        <section class="admin-card neon-border">
            <div class="card-header">
                <span class="icon">🧩</span>
                <h3>Nouvelle Énigme</h3>
            </div>
            <form method="POST" class="cyber-form">
                <input type="text" name="title" placeholder="Titre de l'unité..." required>
                <textarea name="description" placeholder="Consigne de l'énigme..." required></textarea>
                <input type="text" name="answer" placeholder="Clé de décryptage (Réponse)" required>
                <div class="form-row">
                    <input type="number" name="max_points" value="100">
                    <select name="difficulty">
                        <option value="facile">FACILE</option>
                        <option value="moyen">MOYEN</option>
                        <option value="difficile">DIFFICILE</option>
                    </select>
                </div>
                <button type="submit" name="add_riddle" class="btn-cyber">INITIALISER_DATA</button>
            </form>
        </section>

        <!-- SECTION : CHAT -->
        <section class="admin-card neon-border-purple">
            <div class="card-header">
                <span class="icon">💬</span>
                <h3>Flux Terminal (Chat)</h3>
            </div>
            <div class="chat-monitor">
                <?php while($m = $recent_messages->fetch_assoc()): ?>
                    <div class="chat-line">
                        <span class="chat-time">[<?= date('H:i', strtotime($m['created_at'])) ?>]</span>
                        <span class="chat-user"><?= htmlspecialchars($m['username']) ?>:</span>
                        <span class="chat-msg"><?= htmlspecialchars($m['message']) ?></span>
                        <a href="admin.php?del_msg=<?= $m['id'] ?>" class="text-danger">×</a>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>

        <!-- SECTION : JOUEURS -->
        <section class="admin-card grid-wide">
            <div class="card-header">
                <span class="icon">👥</span>
                <h3>Base de données Sujets (Joueurs)</h3>
            </div>
            <div class="table-scroll">
                <table class="cyber-table">
                    <thead>
                        <tr>
                            <th>IDENTIFIANT</th>
                            <th>SCORE_TOTAL</th>
                            <th style="text-align:right;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($u = $all_users->fetch_assoc()): ?>
                        <tr>
                            <td><span class="user-id">#<?= $u['id'] ?></span> <?= htmlspecialchars($u['username']) ?></td>
                            <td class="text-neon"><?= $u['total_score'] ?> PTS</td>
                            <td style="text-align:right;">
                                <a href="admin.php?del_user=<?= $u['id'] ?>" class="btn-small danger" onclick="return confirm('BANNIR?')">BAN_USER</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</div>

<style>
/* --- DESIGN SYSTEM --- */
:root {
    --bg-dark: #0a0c10;
    --card-bg: #12151d;
    --neon-blue: #00f0ff;
    --neon-purple: #a855f7;
    --text-gray: #94a3b8;
}

body { background-color: var(--bg-dark); color: white; font-family: 'Orbitron', sans-serif; }

.admin-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

.neon-text { color: var(--neon-blue); text-shadow: 0 0 10px rgba(0,240,255,0.5); letter-spacing: 2px; }

.admin-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 25px; 
}

.grid-wide { grid-column: span 2; }

/* --- CARDS --- */
.admin-card {
    background: var(--card-bg);
    border-radius: 4px;
    padding: 20px;
    border-left: 4px solid #334155;
    transition: 0.3s;
}

.neon-border { border-left-color: var(--neon-blue); box-shadow: -5px 0 15px rgba(0,240,255,0.1); }
.neon-border-purple { border-left-color: var(--neon-purple); box-shadow: -5px 0 15px rgba(168,85,247,0.1); }

.card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #1e293b; padding-bottom: 10px; }
.card-header h3 { font-size: 0.9rem; text-transform: uppercase; margin: 0; color: #f8fafc; }

/* --- FORMS --- */
.cyber-form input, .cyber-form textarea, .cyber-form select {
    background: #0f172a;
    border: 1px solid #334155;
    color: white;
    padding: 12px;
    margin-bottom: 10px;
    width: 100%;
    box-sizing: border-box;
    font-family: monospace;
}

.form-row { display: flex; gap: 10px; }

.btn-cyber {
    background: transparent;
    border: 1px solid var(--neon-blue);
    color: var(--neon-blue);
    padding: 12px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
}

.btn-cyber:hover { background: var(--neon-blue); color: black; box-shadow: 0 0 20px var(--neon-blue); }

/* --- CHAT MONITOR --- */
.chat-monitor { font-family: monospace; height: 250px; overflow-y: auto; background: #000; padding: 10px; border: 1px solid #1e293b; }
.chat-line { font-size: 0.85rem; margin-bottom: 6px; border-bottom: 1px solid #111; padding-bottom: 4px; }
.chat-time { color: #475569; }
.chat-user { color: var(--neon-purple); font-weight: bold; }
.chat-msg { color: #cbd5e1; }
.text-danger { color: #ef4444; text-decoration: none; font-weight: bold; margin-left: 5px; }

/* --- TABLES --- */
.cyber-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
.cyber-table th { text-align: left; font-size: 0.75rem; color: #64748b; padding: 10px; border-bottom: 2px solid #1e293b; }
.cyber-table td { padding: 15px 10px; border-bottom: 1px solid #1e293b; }
.text-neon { color: var(--neon-blue); font-weight: bold; }
.user-id { color: #475569; font-size: 0.8rem; margin-right: 5px; }

.btn-small { padding: 5px 10px; font-size: 0.7rem; text-decoration: none; border: 1px solid #334155; color: #94a3b8; }
.btn-small.danger:hover { border-color: #ef4444; color: #ef4444; box-shadow: 0 0 10px #ef4444; }

.alert-success { background: rgba(34,197,94,0.1); border: 1px solid #22c55e; color: #22c55e; padding: 10px 20px; border-radius: 4px; font-size: 0.8rem; }
</style>

<?php require_once '../includes/footer.php'; ?>