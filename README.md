# 🎮 ÉnYgmes

Bienvenue sur **ÉnYgmes** ! Ce projet est une plateforme de jeux et d'énigmes (logique, cryptographie, investigation) réalisée en seulement **48 heures** dans le cadre du Challenge Ynov Informatique (Mars 2026).

---

## 🛠️ ÉTAPE 1 : Pré-requis (Les outils)

Avant de commencer, vous avez besoin d'un logiciel qui simule un serveur sur votre ordinateur. 
1. **Téléchargez XAMPP** (gratuit) : [https://www.apachefriends.org/fr/index.html](https://www.apachefriends.org/fr/index.html)
2. **Installez-le** en laissant toutes les options par défaut.

---

## 💾 ÉTAPE 2 : Mise en place de la Base de Données

C'est ici que sont stockés les utilisateurs, les scores et les énigmes.

1. Lancez le **XAMPP Control Panel**.
2. Cliquez sur le bouton **Start** à côté de **Apache** et **MySQL**. (Ils doivent devenir verts).
3. Ouvrez votre navigateur (Chrome, Firefox...) et tapez cette adresse : `http://localhost/phpmyadmin/`
4. Dans la colonne de gauche, cliquez sur **Nouvelle base de données** (ou "New").
5. Nommez-la exactement : `challenge48_db` et cliquez sur **Créer**.
6. Cliquez sur l'onglet **Importer** en haut de la page.
7. Cliquez sur "Choisir un fichier" et sélectionnez le fichier `structure.sql` qui se trouve à la racine de ce projet.
8. Descendez tout en bas et cliquez sur **Importer**. 

> ✅ **C'est fini pour la base de données !**

---

## 📂 ÉTAPE 3 : Installation des fichiers du projet

1. Allez dans le dossier où vous avez installé XAMPP (souvent `C:\xampp`).
2. Ouvrez le dossier nommé `htdocs`.
3. C'est ici que vous devez mettre le dossier du projet. 
   * **Via Git :** Faites un clic droit > `Git Bash Here` et tapez :  
     `git clone https://github.com/LouLFBV/EnYgmes.git Challenge-48h-2026`
   * **Via ZIP :** Copiez le dossier téléchargé et renommez-le en `Challenge-48h-2026`.

---

## 🚀 ÉTAPE 4 : Lancer le site

1. Vérifiez que XAMPP (Apache et MySQL) est toujours allumé.
2. Ouvrez votre navigateur et tapez : `http://localhost/Challenge-48h-2026`
3. **Félicitations !** Le site s'affiche.

---

## 🔐 Identifiants de test

Pour tester les différentes interfaces, utilisez ces comptes :

| Rôle | Email | Mot de passe |
| :--- | :--- | :--- |
| **Administrateur** | `admin@challenge.com` | `admin123` |
| **Joueur (Charles)** | `hugo.cabanes@ynov.com` | `admin123` |

---

## 🕹️ Les 5 Énigmes incluses
1. **Balance Games** : Logique et mathématiques.
2. **Cipher Breach** : Cryptographie de haut niveau.
3. **Switchboard** : Combinaisons électriques.
4. **Enigma Grid** : Reconstitution de schémas (Jeu de Maël).
5. **Dead Drop** : Enquête policière narrative.

---

## 👥 L'Équipe
Projet réalisé par 8 étudiants passionnés :
* **Lou Lefebvre** (Lead Dev / B2)
* **Hugo Cabanes** (Dev / B2)
* **Yarkin Oner** (Dev / B2)
* **Teddy Le Moal** (Dev / B2)
* **Anthony Castrale** (Dev / B1)
* **Maël Caetano** (Lead Jeu / B2)
* **Hugo Giordano** (Jeu / B1)
* **Ulysse Prevost Lacaze** (Jeu / B1)

---
*Démo finale le 31/03/2026 à 14h00. Ynov Informatique.*
