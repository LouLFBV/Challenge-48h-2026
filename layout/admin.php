<?php
require_once '../includes/header.php';
checkConnexion();

// Vérification du rôle admin (basé sur ta colonne is_admin)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

// --- LOGIQUE DE SUPPRESSION & MODÉRATION ---

// 1. Supprimer une Énigme
if (isset($_GET['del_riddle'])) {
    $id_riddle = intval($_GET['del_riddle']);
    $mysqli->query("DELETE FROM riddles WHERE id = $id_riddle");
    header("Location: admin.php?msg=Énigme supprimée"); exit;
}

// 2. Supprimer un Utilisateur
if (isset($_GET['del_user'])) {
    $id_u = intval($_GET['del_user']);
    if ($id_u !== $_SESSION['user_id']) {
        // Le ON DELETE CASCADE dans ta DB gère déjà la suppression des scores et messages !
        $mysqli->query("DELETE FROM users WHERE id = $id_u");
        header("Location: admin.php?msg=Utilisateur banni avec succès"); 
        exit;
    }
}

// 3. Modération du Chat (Supprimer un message)
if (isset($_GET['del_msg'])) {
    $id_msg = intval($_GET['del_msg']);
    $mysqli->query("DELETE FROM general_chat WHERE id = $id_msg");
    header("Location: admin.php?msg=Message supprimé"); exit;
}

// 4. Ajouter une Énigme (Logique simplifiée pour le dashboard)
if (isset($_POST['add_riddle'])) {
    $title = $mysqli->real_escape_string($_POST['title']);
    $desc = $mysqli->real_escape_string($_POST['description']);
    $ans = $mysqli->real_escape_string($_POST['answer']);
    $pts = intval($_POST['max_points']);
    $diff = $mysqli->real_escape_string($_POST['difficulty']);

    $mysqli->query("INSERT INTO riddles (title, description, answer, max_points, difficulty) 
                    VALUES ('$title', '$desc', '$ans', $pts, '$diff')");
    header("Location: admin.php?msg=Nouvelle énigme ajoutée"); exit;
}

// --- RÉCUPÉRATION DES DONNÉES ---
$all_riddles = $mysqli->query("SELECT * FROM riddles ORDER BY id DESC");
$all_users = $mysqli->query("SELECT * FROM users WHERE id != " . $_SESSION['user_id'] . " ORDER BY total_score DESC"); 
$recent_messages = $mysqli->query("SELECT general_chat.*, users.username FROM general_chat JOIN users ON general_chat.user_id = users.id ORDER BY created_at DESC LIMIT 10");
?>

<div class="admin-header" style="margin-bottom: 30px;">
    <h1>Tableau de Bord Admin — Challenge 48H</h1>
    <p style="color: #666;">Gestion des joueurs, des énigmes et du chat.</p>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #c3e6cb;">
        <strong>Succès :</strong> <?php echo htmlspecialchars($_GET['msg']); ?>
    </div>
<?php endif; ?>

<div class="admin-grid">
    
    <!-- AJOUTER UNE ÉNIGME -->
    <div class="admin-card">
        <h3>🧩 Ajouter une Énigme</h3>
        <form method="POST" style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
            <input type="text" name="title" placeholder="Titre de l'énigme" required style="padding:10px; border:1px solid #ddd; border-radius:6px;">
            <textarea name="description" placeholder="Consigne / Question" required style="padding:10px; border:1px solid #ddd; border-radius:6px;"></textarea>
            <input type="text" name="answer" placeholder="Réponse attendue" required style="padding:10px; border:1px solid #ddd; border-radius:6px;">
            <div style="display: flex; gap: 10px;">
                <input type="number" name="max_points" placeholder="Points" value="100" style="width: 50%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                <select name="difficulty" style="width: 50%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                    <option value="facile">Facile</option>
                    <option value="moyen">Moyen</option>
                    <option value="difficile">Difficile</option>
                </select>
            </div>
            <button type="submit" name="add_riddle" class="btn-submit" style="padding:10px; background:#007bff; color:white; border:none; border-radius:6px; cursor:pointer;">Créer l'énigme</button>
        </form>
    </div>

    <!-- GESTION DES JOUEURS -->
    <div class="admin-card">
        <h3>👥 Joueurs Inscrits</h3>
        <table class="admin-table" style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; text-align: left;">
                    <th>Username</th>
                    <th>Score</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($u = $all_users->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                    <td><?php echo $u['total_score']; ?> pts</td>
                    <td style="text-align:right; padding: 10px 0;">
                        <a href="admin.php?del_user=<?php echo $u['id']; ?>" onclick="return confirm('Bannir ce joueur ?')" style="text-decoration:none;">🗑️ Bannir</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- LISTE DES ÉNIGMES -->
    <div class="admin-card">
        <h3>📜 Énigmes en ligne</h3>
        <table class="admin-table" style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; text-align: left;">
                    <th>Titre</th>
                    <th>Difficulté</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($r = $all_riddles->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td><?php echo htmlspecialchars($r['title']); ?></td>
                    <td><span class="badge-diff <?php echo $r['difficulty']; ?>"><?php echo ucfirst($r['difficulty']); ?></span></td>
                    <td style="text-align:right; padding: 10px 0;">
                        <a href="admin.php?del_riddle=<?php echo $r['id']; ?>" onclick="return confirm('Supprimer l\'énigme ?')" style="text-decoration:none;">🗑️</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- MODÉRATION CHAT -->
    <div class="admin-card">
        <h3>💬 Modération Chat (10 derniers)</h3>
        <div style="max-height: 300px; overflow-y: auto;">
            <?php while($m = $recent_messages->fetch_assoc()): ?>
                <div style="padding: 10px; border-bottom: 1px solid #f9f9f9; font-size: 0.9em;">
                    <strong><?php echo htmlspecialchars($m['username']); ?>:</strong> 
                    <?php echo htmlspecialchars($m['message']); ?>
                    <a href="admin.php?del_msg=<?php echo $m['id']; ?>" style="color: red; float: right; text-decoration: none;">Supprimer</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

</div>

<style>
    .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-top: 20px; }
    .admin-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .badge-diff { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; color: white; }
    .facile { background: #2ecc71; }
    .moyen { background: #f1c40f; }
    .difficile { background: #e74c3c; }
</style>

<?php require_once 'includes/footer.php'; ?>