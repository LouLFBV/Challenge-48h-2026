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

-- 3. SCORE PAR ÉNIGME (Table de liaison essentielle)
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