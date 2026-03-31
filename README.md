## ÉnYgmes — Challenge 48H Ynov 🧩

Bienvenue sur **ÉnYgmes**, une plateforme immersive de défis interactifs conçue en 48 heures dans le cadre du module **Challenge 48H** d'Ynov Informatique. 

L'objectif est simple : tester votre logique, votre réflexion et votre persévérance à travers une série d'énigmes à difficulté croissante. Serez-vous capable de dominer le classement général ?

---

## 🚀 Fonctionnalités

### 👤 Joueurs
*   **Authentification complète :** Inscription, connexion et déconnexion sécurisées.
*   **Profil Utilisateur :** Personnalisation de la photo de profil, visualisation du score total et historique détaillé des énigmes résolues avec les scores associés.
*   **Système d'Énigmes :** Une interface intuitive pour parcourir et résoudre des défis (logique, cryptographie, algorithmique).
*   **Classements :** Consultez le classement général des joueurs ou comparez vos performances sur chaque énigme spécifique.
*   **Chat Général :** Un espace de discussion unique pour échanger avec les autres joueurs en temps réel.

### 🛠️ Administration
*   **Dashboard Admin :** Interface dédiée pour gérer la communauté et le contenu.
*   **Gestion des Utilisateurs :** Possibilité de modérer les comptes (CRUD complet).
*   **Gestion des Énigmes :** Ajout, modification ou suppression des défis directement depuis l'interface.

---

## 🛠️ Stack Technique

*   **Frontend :** HTML-CSS-JS
*   **Backend :** PHP
*   **Base de données :** MySQL
*   **Design :** Interfaces immersives et accessibles (Mobile Friendly).

---

## ⚙️ Guide d'Installation

### Pré-requis
*   Un serveur local (WAMP, MAMP, XAMPP) ou un environnement Node.js.
*   MySQL installé.

### 1. Installation de la Base de Données
1. Ouvrez votre gestionnaire de base de données (phpMyAdmin, MySQL Workbench).
2. Créez une base de données nommée `challenge48_db`.
3. Exécutez le script SQL suivant :

```sql
CREATE DATABASE IF NOT EXISTS challenge48_db;
USE challenge48_db;

-- 1. UTILISATEURS
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default.png',
    total_score INT DEFAULT 0,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. ÉNIGMES
CREATE TABLE IF NOT EXISTS riddles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    answer VARCHAR(255) NOT NULL,
    max_points INT DEFAULT 100,
    difficulty ENUM('facile', 'moyen', 'difficile') DEFAULT 'facile'
);

-- 3. SCORE PAR ÉNIGME
CREATE TABLE IF NOT EXISTS user_scores_per_riddle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    riddle_id INT NOT NULL,
    obtained_score INT DEFAULT 0,
    solved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (riddle_id) REFERENCES riddles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_solve (user_id, riddle_id)
);

-- 4. MESSAGES DU CHAT GÉNÉRAL
CREATE TABLE IF NOT EXISTS general_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 2. Lancement du Projet
Clonez le dépôt :

Bash
git clone [https://github.com/LouLFBV/EnYgmes.git](https://github.com/LouLFBV/EnYgmes.git)

 Lancez votre serveur local.

Accédez à l'application via http://localhost/Challenge-48h-2026.

 ### 👥 L'Équipe (Bachelor 1-2-3)
Projet réalisé par une équipe de 8 étudiants passionnés :

[Lou Lefebvre] - [Dev / B2 INFO]

[Hugo Cabanes] - [Dev / B2 INFO]

[Yarkin Oner] - [Dev / B2 INFO]

[Teddy Le Moal] - [Dev / B2 INFO]

[Anthony Castrale] - [Dev / B1 INFO]

[Maël Caetano] - [Jeu / B2 INFO]

[Hugo Giordano] - [Jeu / B1 INFO]

[Ulysee Prevost Lacaze] - [Jeu / B1 INFO]



### 📅 Livrables & Échéances
**Dépôt GIT** : Avant le 31/03/2026 à 18h.

**Démo fonctionnelle** : Le 31/03/2026 à partir de 14h.

---

Projet réalisé dans le cadre pédagogique d'Ynov Informatique - Mars 2026.