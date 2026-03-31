-- Nettoyage des avatars default.png en NULL
UPDATE users SET profile_image = NULL WHERE profile_image = 'default.png' OR profile_image IS NULL;

-- Changer le DEFAULT du schéma
ALTER TABLE users MODIFY COLUMN profile_image VARCHAR(255) DEFAULT NULL;
