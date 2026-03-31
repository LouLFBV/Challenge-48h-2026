-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 31 mars 2026 à 11:21
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `challenge48_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `general_chat`
--

CREATE TABLE `general_chat` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `riddles`
--

CREATE TABLE `riddles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `answer` varchar(255) NOT NULL,
  `max_points` int(11) DEFAULT 100,
  `difficulty` enum('facile','moyen','difficile') DEFAULT 'facile',
  `game_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `riddles`
--

INSERT INTO `riddles` (`id`, `title`, `description`, `answer`, `max_points`, `difficulty`, `game_url`) VALUES
(1, 'Balance_Games', 'Déduisez le poids des objets mystérieux pour résoudre l\'énigme.', 'admin123', 100, 'difficile', 'games_Balance/game.php'),
(3, 'CIPHER — SYSTEM://BREACH', 'Infiltration système : résolvez les 7 couches de sécurité pour extraire les données.', '2479gx', 1000, 'difficile', 'jeux/hugo/cipher.php');

-- --------------------------------------------------------

--
-- Structure de la table `riddles_balance`
--

CREATE TABLE `riddles_balance` (
  `id` int(11) NOT NULL,
  `id_riddle` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `points` int(11) DEFAULT 0,
  `slug` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `riddles_balance`
--

INSERT INTO `riddles_balance` (`id`, `id_riddle`, `id_user`, `points`, `slug`) VALUES
(1, 1, 1, 100, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT 'default.png',
  `total_score` int(11) DEFAULT 0,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_image`, `total_score`, `is_admin`, `created_at`) VALUES
(1, 'Charles', 'hugo.cabanes@ynov.com', '$2y$10$bsgb7DsHdBQMib1vKg2QLu12ced8ygMXdk9y0F9x5C4Icrx8rrE46', 'default.png', 100, 0, '2026-03-30 13:58:59');

-- --------------------------------------------------------

--
-- Structure de la table `user_scores_per_riddle`
--

CREATE TABLE `user_scores_per_riddle` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `riddle_id` int(11) NOT NULL,
  `obtained_score` int(11) DEFAULT 0,
  `solved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user_scores_per_riddle`
--

INSERT INTO `user_scores_per_riddle` (`id`, `user_id`, `riddle_id`, `obtained_score`, `solved_at`) VALUES
(1, 1, 1, 100, '2026-03-31 09:19:51');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `general_chat`
--
ALTER TABLE `general_chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `riddles`
--
ALTER TABLE `riddles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `riddles_balance`
--
ALTER TABLE `riddles_balance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_riddle` (`id_user`,`id_riddle`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `id_riddle` (`id_riddle`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `user_scores_per_riddle`
--
ALTER TABLE `user_scores_per_riddle`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_solve` (`user_id`,`riddle_id`),
  ADD KEY `riddle_id` (`riddle_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `general_chat`
--
ALTER TABLE `general_chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `riddles`
--
ALTER TABLE `riddles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `riddles_balance`
--
ALTER TABLE `riddles_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `user_scores_per_riddle`
--
ALTER TABLE `user_scores_per_riddle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `general_chat`
--
ALTER TABLE `general_chat`
  ADD CONSTRAINT `general_chat_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `riddles_balance`
--
ALTER TABLE `riddles_balance`
  ADD CONSTRAINT `riddles_balance_ibfk_1` FOREIGN KEY (`id_riddle`) REFERENCES `riddles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `riddles_balance_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_scores_per_riddle`
--
ALTER TABLE `user_scores_per_riddle`
  ADD CONSTRAINT `user_scores_per_riddle_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_scores_per_riddle_ibfk_2` FOREIGN KEY (`riddle_id`) REFERENCES `riddles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
