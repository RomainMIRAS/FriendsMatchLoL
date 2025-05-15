<?php
/**
 * Fichier utilitaire pour FriendsMatchLoL
 * Contient des fonctions couramment utilisées dans l'application
 */

/**
 * Convertit un timestamp en texte relative (par exemple "il y a 2 heures")
 * 
 * @param int $timestamp Le timestamp à formatter
 * @return string Texte formaté
 */
function humanTimeDiff($timestamp) {
    $current = time();
    $diff = $current - $timestamp;
    
    if ($diff < 60) {
        return "il y a quelques secondes";
    } elseif ($diff < 3600) {
        $minutes = round($diff / 60);
        return "il y a " . $minutes . " minute" . ($minutes > 1 ? "s" : "");
    } elseif ($diff < 86400) {
        $hours = round($diff / 3600);
        return "il y a " . $hours . " heure" . ($hours > 1 ? "s" : "");
    } elseif ($diff < 604800) {
        $days = round($diff / 86400);
        return "il y a " . $days . " jour" . ($days > 1 ? "s" : "");
    } else {
        return date('d/m/Y', $timestamp);
    }
}

/**
 * Récupère le nom du type de file d'attente à partir de l'ID
 * 
 * @param int $queueId L'ID de la file d'attente
 * @return string Le nom de la file
 */
function getQueueTypeName($queueId) {
    $queueTypes = [
        400 => 'Normal Draft',
        420 => 'Ranked Solo/Duo',
        430 => 'Normal Blind',
        440 => 'Ranked Flex',
        450 => 'ARAM',
        700 => 'Clash',
        830 => 'Co-op vs AI Intro',
        840 => 'Co-op vs AI Beginner',
        850 => 'Co-op vs AI Intermediate',
        900 => 'URF',
        1400 => 'Ultimate Spellbook'
    ];
    
    return isset($queueTypes[$queueId]) ? $queueTypes[$queueId] : 'Mode personnalisé';
}

/**
 * Formate la durée d'un match
 * 
 * @param int $seconds Durée en secondes
 * @return string Durée formatée (MM:SS)
 */
function formatGameDuration($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return $minutes . ':' . str_pad($remainingSeconds, 2, '0', STR_PAD_LEFT);
}

/**
 * Génère une URL pour l'image d'un champion
 * 
 * @param string $championName Nom du champion
 * @return string URL de l'image
 */
function getChampionImageUrl($championName) {
    // Supprimer les espaces et caractères spéciaux du nom du champion
    $formattedName = str_replace([' ', '\'', '.'], '', $championName);
    
    // Pour certains champions qui ont des noms spéciaux dans l'API
    $specialNames = [
        'MonkeyKing' => 'Wukong',
        'Nunu&Willump' => 'Nunu',
        'RenataGlasc' => 'Renata'
    ];
    
    if (isset($specialNames[$formattedName])) {
        $formattedName = $specialNames[$formattedName];
    }
    
    return 'https://ddragon.leagueoflegends.com/cdn/13.9.1/img/champion/' . $formattedName . '.png';
}

/**
 * Génère une URL pour l'image d'un item
 * 
 * @param int $itemId ID de l'item
 * @return string URL de l'image ou null si itemId est 0
 */
function getItemImageUrl($itemId) {
    if ($itemId === 0) {
        return null;
    }
    return 'https://ddragon.leagueoflegends.com/cdn/13.9.1/img/item/' . $itemId . '.png';
}

/**
 * Récupère la liste des amis d'un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @return array Liste des amis
 */
function getFriendsList($userId) {
    global $db;
    
    if (!isset($db)) {
        try {
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM friends WHERE user_id = :user_id ORDER BY summoner_name ASC");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Vérifie si un utilisateur est connecté
 * 
 * @return boolean
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie le token "Se souvenir de moi" et connecte l'utilisateur si valide
 * Cette fonction doit être appelée au début de chaque page
 */
function checkRememberMeToken() {
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        try {
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $db->prepare("SELECT id, username FROM users WHERE remember_token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Créer une session pour l'utilisateur
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Rafraîchir le token pour la sécurité
                $newToken = bin2hex(random_bytes(32));
                setcookie('remember_token', $newToken, time() + 86400 * 30, '/'); // 30 jours
                
                $stmt = $db->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
                $stmt->bindParam(':token', $newToken);
                $stmt->bindParam(':id', $user['id']);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            // Gérer l'erreur silencieusement
            error_log("Erreur lors de la vérification du token: " . $e->getMessage());
        }
    }
}

/**
 * Déconnecter un utilisateur
 */
function logout() {
    // Supprimer les variables de session
    $_SESSION = array();
    
    // Supprimer le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Détruire la session
    session_destroy();
    
    // Supprimer le cookie "Se souvenir de moi"
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

/**
 * Récupérer les informations d'un utilisateur par son ID
 * 
 * @param int $userId ID de l'utilisateur
 * @return array|false Données de l'utilisateur ou false si non trouvé
 */
function getUserById($userId) {
    try {
        $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $db->prepare("SELECT id, username, email, summoner_name, region, notifications_enabled, created_at 
                             FROM users WHERE id = :id");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de l'utilisateur: " . $e->getMessage());
    }
    
    return false;
}
