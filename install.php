<?php
/**
 * Script d'installation pour FriendsMatchLoL
 * Ce script crée les tables nécessaires dans la base de données
 */

// Charger la configuration
require_once 'config/config.php';

// Message d'installation
echo "====================================================\n";
echo "Configuration de la base de données pour FriendsMatchLoL\n";
echo "====================================================\n\n";

// Connexion à la base de données
try {
    $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connexion à la base de données établie.\n\n";
    
    // Créer la base de données si elle n'existe pas
    $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Base de données '" . DB_NAME . "' créée ou vérifiée.\n\n";
    
    // Sélectionner la base de données
    $conn->exec("USE " . DB_NAME);
    
    // Créer la table des utilisateurs
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        summoner_name VARCHAR(100),
        region VARCHAR(10),
        notifications_enabled BOOLEAN DEFAULT TRUE,
        remember_token VARCHAR(64),
        reset_token VARCHAR(64),
        reset_token_expires_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "✓ Table 'users' créée ou vérifiée.\n";
    
    // Créer la table des amis
    $sql = "CREATE TABLE IF NOT EXISTS friends (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        summoner_name VARCHAR(100) NOT NULL,
        region VARCHAR(10) NOT NULL,
        summoner_id VARCHAR(100) NOT NULL,
        puuid VARCHAR(100) NOT NULL,
        account_id VARCHAR(100) DEFAULT NULL,
        riot_id VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, summoner_id, region)
    )";
    $conn->exec($sql);
    echo "✓ Table 'friends' créée ou vérifiée.\n";
    
    // Créer la table de statut des matchs
    $sql = "CREATE TABLE IF NOT EXISTS match_status (
        id INT AUTO_INCREMENT PRIMARY KEY,
        friend_id INT NOT NULL,
        game_id VARCHAR(100),
        in_game BOOLEAN DEFAULT FALSE,
        game_start_time BIGINT,
        game_length INT,
        champion_id INT,
        queue_type_id INT,
        last_checked TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (friend_id) REFERENCES friends(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "✓ Table 'match_status' créée ou vérifiée.\n";
    
    // Créer la table d'historique des matchs
    $sql = "CREATE TABLE IF NOT EXISTS match_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        friend_id INT NOT NULL,
        game_id VARCHAR(100) NOT NULL,
        champion_id INT NOT NULL,
        queue_type_id INT NOT NULL,
        win BOOLEAN,
        kills INT,
        deaths INT,
        assists INT,
        cs INT,
        game_duration INT,
        game_date TIMESTAMP,
        data_json JSON,
        FOREIGN KEY (friend_id) REFERENCES friends(id) ON DELETE CASCADE,
        UNIQUE KEY (friend_id, game_id)
    )";
    $conn->exec($sql);
    echo "✓ Table 'match_history' créée ou vérifiée.\n";
    
    // Créer la table des notifications
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        friend_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        content TEXT,
        `read` BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (friend_id) REFERENCES friends(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "✓ Table 'notifications' créée ou vérifiée.\n\n";
    
    echo "Configuration de la base de données terminée avec succès !\n";
    echo "\nVous pouvez maintenant utiliser FriendsMatchLoL.\n";
    echo "====================================================\n";
    
} catch(PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

$conn = null;
