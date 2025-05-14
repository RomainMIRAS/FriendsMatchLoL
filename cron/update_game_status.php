<?php
/**
 * Script de mise à jour des statuts des matchs
 * Ce script est conçu pour être exécuté via une tâche cron
 * 
 * Exemple de configuration cron (toutes les 2 minutes) :
 * */2 * * * * php /var/www/html/FriendsMatchLoL/cron/update_game_status.php
 */

// Définir le chemin absolu du dossier racine
define('ROOT_PATH', dirname(__DIR__));

// Charger la configuration et l'API
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/api/riot_api.php';

// Se connecter à la base de données
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    writeLog("Erreur de connexion à la base de données: " . $e->getMessage());
    exit(1);
}

// Début du traitement
$startTime = microtime(true);
writeLog("=== Début de la mise à jour des statuts de jeu ===");

// Récupérer tous les amis enregistrés
$stmt = $conn->prepare("SELECT f.id, f.summoner_id, f.summoner_name, f.region, f.user_id, u.notifications_enabled 
                        FROM friends f 
                        JOIN users u ON f.user_id = u.id");
$stmt->execute();
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

writeLog("Nombre d'amis à vérifier: " . count($friends));

$updatedCount = 0;
$notificationCount = 0;

// Pour chaque ami, vérifier s'il est en jeu
foreach ($friends as $friend) {
    // Récupérer le statut actuel du match
    $stmt = $conn->prepare("SELECT * FROM match_status WHERE friend_id = :friend_id");
    $stmt->bindParam(':friend_id', $friend['id']);
    $stmt->execute();
    $currentStatus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier si l'ami est en jeu
    $matchData = getCurrentMatch($friend['summoner_id'], $friend['region']);
    $isInGame = ($matchData !== null);
    
    if ($isInGame) {
        // L'ami est en jeu
        $gameId = $matchData['gameId'];
        $gameStartTime = $matchData['gameStartTime'];
        $gameLength = isset($matchData['gameLength']) ? $matchData['gameLength'] : 0;
        $championId = null;
        $queueTypeId = $matchData['gameQueueConfigId'];
        
        // Trouver le champion que l'ami joue
        foreach ($matchData['participants'] as $participant) {
            if ($participant['summonerId'] === $friend['summoner_id']) {
                $championId = $participant['championId'];
                break;
            }
        }
        
        // Mettre à jour ou insérer le statut du match
        if ($currentStatus) {
            // Mise à jour du statut existant
            $stmt = $conn->prepare("UPDATE match_status SET 
                                    game_id = :game_id, 
                                    in_game = TRUE, 
                                    game_start_time = :game_start_time, 
                                    game_length = :game_length, 
                                    champion_id = :champion_id, 
                                    queue_type_id = :queue_type_id, 
                                    last_checked = NOW() 
                                    WHERE friend_id = :friend_id");
        } else {
            // Insertion d'un nouveau statut
            $stmt = $conn->prepare("INSERT INTO match_status 
                                    (friend_id, game_id, in_game, game_start_time, game_length, champion_id, queue_type_id) 
                                    VALUES 
                                    (:friend_id, :game_id, TRUE, :game_start_time, :game_length, :champion_id, :queue_type_id)");
        }
        
        $stmt->bindParam(':friend_id', $friend['id']);
        $stmt->bindParam(':game_id', $gameId);
        $stmt->bindParam(':game_start_time', $gameStartTime);
        $stmt->bindParam(':game_length', $gameLength);
        $stmt->bindParam(':champion_id', $championId);
        $stmt->bindParam(':queue_type_id', $queueTypeId);
        $stmt->execute();
        
        // Envoyer une notification si l'ami n'était pas en jeu avant et que les notifications sont activées
        if ((!$currentStatus || !$currentStatus['in_game']) && $friend['notifications_enabled']) {
            $championName = getChampionNameById($championId);
            $queueType = getQueueTypeById($queueTypeId);
            
            $stmt = $conn->prepare("INSERT INTO notifications 
                                    (user_id, friend_id, type, content) 
                                    VALUES 
                                    (:user_id, :friend_id, 'game_start', :content)");
            $content = json_encode([
                'summoner_name' => $friend['summoner_name'],
                'champion_name' => $championName,
                'queue_type' => $queueType,
                'game_id' => $gameId
            ]);
            
            $stmt->bindParam(':user_id', $friend['user_id']);
            $stmt->bindParam(':friend_id', $friend['id']);
            $stmt->bindParam(':content', $content);
            $stmt->execute();
            
            $notificationCount++;
        }
        
        $updatedCount++;
    } else {
        // L'ami n'est pas en jeu
        if ($currentStatus && $currentStatus['in_game']) {
            // S'il était en jeu avant, enregistrer le match dans l'historique
            // En pratique, vous utiliseriez l'API Match-V5 pour obtenir les détails complets du match
            // Pour ce projet de démonstration, on simule juste la fin du match
            
            $stmt = $conn->prepare("UPDATE match_status SET 
                                    in_game = FALSE, 
                                    last_checked = NOW() 
                                    WHERE friend_id = :friend_id");
            $stmt->bindParam(':friend_id', $friend['id']);
            $stmt->execute();
            
            $updatedCount++;
        } elseif ($currentStatus) {
            // Mettre à jour la date de dernière vérification
            $stmt = $conn->prepare("UPDATE match_status SET 
                                    last_checked = NOW() 
                                    WHERE friend_id = :friend_id");
            $stmt->bindParam(':friend_id', $friend['id']);
            $stmt->execute();
        } else {
            // Créer un nouveau statut "pas en jeu"
            $stmt = $conn->prepare("INSERT INTO match_status 
                                    (friend_id, in_game) 
                                    VALUES 
                                    (:friend_id, FALSE)");
            $stmt->bindParam(':friend_id', $friend['id']);
            $stmt->execute();
        }
    }
}

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

writeLog("Mise à jour terminée. $updatedCount statuts mis à jour, $notificationCount notifications créées.");
writeLog("Temps d'exécution: $executionTime secondes");
writeLog("=== Fin de la mise à jour ===\n");

/**
 * Fonction pour écrire dans le journal
 */
function writeLog($message) {
    $logFile = ROOT_PATH . '/logs/cron.log';
    
    // Créer le dossier logs s'il n'existe pas
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Afficher le message si le script est exécuté en ligne de commande
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
}
