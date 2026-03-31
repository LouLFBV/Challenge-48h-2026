-- Nettoyage complet
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `user_scores_per_riddle`, `riddles_balance`, `general_chat`, `riddles`, `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. UTILISATEURS
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `username` varchar(100) NOT NULL UNIQUE,
    `email` varchar(100) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `profile_image` varchar(255) DEFAULT 'default.png',
    `total_score` int(11) DEFAULT 0,
    `is_admin` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertion Charles + Admin (MDP par défaut : admin123)
INSERT INTO `users` (`username`, `email`, `password`, `is_admin`) VALUES
('Charles', 'hugo.cabanes@ynov.com', '$2y$10$bsgb7DsHdBQMib1vKg2QLu12ced8ygMXdk9y0F9x5C4Icrx8rrE46', 0),
('Admin', 'admin@challenge.com', '$2y$10$bsgb7DsHdBQMib1vKg2QLu12ced8ygMXdk9y0F9x5C4Icrx8rrE46', 1);

-- 2. ÉNIGMES
CREATE TABLE `riddles` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `title` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `answer` varchar(255) NOT NULL,
    `max_points` int(11) DEFAULT 100,
    `difficulty` enum('facile','moyen','difficile') DEFAULT 'facile',
    `game_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertion des 5 jeux
INSERT INTO `riddles` (`id`, `title`, `description`, `answer`, `max_points`, `difficulty`, `game_url`) VALUES
(1, 'Balance_Games', 'Déduisez le poids des objets mystérieux.', 'admin123', 100, 'difficile', 'games_Balance/game.php'),
(3, 'CIPHER — BREACH', 'Infiltration système : 7 couches de sécurité.', '2479gx', 1000, 'difficile', 'jeux/hugo/cipher.php'),
(4, 'SWITCHBOARD — OVERLOAD', 'Combinaison d\'interrupteurs.', 'combinaison_gagnante', 400, 'moyen', 'jeux/ulysse/jeux.php'),
(5, 'ENIGMA GRID', 'Reconstitution de schéma énergétique.', 'schema_complet', 600, 'moyen', 'jeux/mael/index.php'),
(6, 'DEAD DROP', 'Enquête policière : L\'Affaire du Mercredi.', 'iris', 500, 'facile', 'jeux/hugo/dead-drop.php');

-- 3. SCORE PAR ÉNIGME
CREATE TABLE `user_scores_per_riddle` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` int(11) NOT NULL,
    `riddle_id` int(11) NOT NULL,
    `obtained_score` int(11) DEFAULT 0,
    `solved_at` timestamp NOT NULL DEFAULT current_timestamp(),
    UNIQUE KEY `unique_solve` (`user_id`,`riddle_id`),
    CONSTRAINT `fk_user_score` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_riddle_score` FOREIGN KEY (`riddle_id`) REFERENCES `riddles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. MESSAGES DU CHAT GÉNÉRAL
CREATE TABLE `general_chat` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` int(11) NOT NULL,
    `message` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. DONNÉES SPÉCIFIQUES BALANCE
CREATE TABLE `riddles_balance` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_riddle` int(11) NOT NULL,
    `id_user` int(11) NOT NULL,
    `points` int(11) DEFAULT 0,
    `slug` varchar(255) UNIQUE,
    CONSTRAINT `fk_balance_riddle` FOREIGN KEY (`id_riddle`) REFERENCES `riddles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_balance_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;