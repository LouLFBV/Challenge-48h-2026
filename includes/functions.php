<?php
function formatPrix($montant) {
    return number_format($montant, 2, ',', ' ') . " €";
}

function checkConnexion() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function isAdmin($role) {
    return $role === 'admin';
}
?>