<?php
/**
 * Fichier de gestion de l'API Riot pour FriendsMatchLoL
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Effectue un appel à l'API Riot
 *
 * @param string $region La région cible (ex: euw1, na1)
 * @param string $endpoint L'endpoint API (ex: /lol/summoner/v4/summoners/by-name/)
 * @param array $params Paramètres optionnels pour la requête
 * @return array|null Les données de réponse ou null en cas d'erreur
 */
function callRiotAPI($region, $endpoint, $params = []) {
    $apiUrl = sprintf(API_ENDPOINT_TEMPLATE, $region) . $endpoint;
    
    if (!empty($params)) {
        $apiUrl .= '?' . http_build_query($params);
    }
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Riot-Token: ' . RIOT_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        logAPIError($httpCode, $apiUrl, $response);
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Log les erreurs d'API
 */
function logAPIError($code, $url, $response) {
    $logFile = __DIR__ . '/../logs/api_errors.log';
    $errorMessage = date('Y-m-d H:i:s') . " | Code: $code | URL: $url | Response: $response\n";
    
    // Créer le dossier logs s'il n'existe pas
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $errorMessage, FILE_APPEND);
}

/**
 * Récupère les informations d'un invocateur par son nom
 */
function getSummonerByName($summonerName, $region) {
    $endpoint = '/lol/summoner/v4/summoners/by-name/' . urlencode($summonerName);
    return callRiotAPI($region, $endpoint);
}

/**
 * Vérifie si un invocateur est actuellement en partie
 */
function getCurrentMatch($summonerId, $region) {
    $endpoint = '/lol/spectator/v4/active-games/by-summoner/' . $summonerId;
    return callRiotAPI($region, $endpoint);
}

/**
 * Récupère les détails d'un match en cours
 */
function getMatchDetails($gameId, $region) {
    // Cette fonction utilise un mélange de l'API de spectateur et du cache local
    // pour obtenir des données complètes sur un match en cours
    
    // D'abord, on vérifie si les données sont en cache
    $cacheFile = __DIR__ . '/../cache/match_' . $gameId . '.json';
    
    // Si le cache existe et date de moins de 30 secondes, on l'utilise
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 30)) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    
    // Sinon, on appelle l'API
    // Cet endpoint est fictif car l'API Riot n'a pas d'endpoint spécifique pour les détails d'un match en cours
    // En pratique, on utiliserait l'API spectator pour récupérer les données
    $endpoint = '/lol/spectator/v4/active-games/' . $gameId;
    $matchData = callRiotAPI($region, $endpoint);
    
    // Si on a des données, on les met en cache
    if ($matchData) {
        // Créer le dossier cache s'il n'existe pas
        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }
        
        file_put_contents($cacheFile, json_encode($matchData));
    }
    
    return $matchData;
}

/**
 * Récupère les dernières parties jouées par un invocateur
 */
function getRecentMatches($puuid, $region, $count = 5) {
    // Convertir la région en région continentale pour l'API Match
    $continentalRegion = regionToContinental($region);
    
    $endpoint = '/lol/match/v5/matches/by-puuid/' . $puuid . '/ids';
    $params = ['count' => $count];
    
    $matchIds = callRiotAPI($continentalRegion, $endpoint, $params);
    
    if (!$matchIds) {
        return null;
    }
    
    $matches = [];
    foreach ($matchIds as $matchId) {
        $matchEndpoint = '/lol/match/v5/matches/' . $matchId;
        $matchData = callRiotAPI($continentalRegion, $matchEndpoint);
        
        if ($matchData) {
            $matches[] = $matchData;
        }
    }
    
    return $matches;
}

/**
 * Convertit une région en région continentale pour l'API Match
 */
function regionToContinental($region) {
    $mapping = [
        'euw1' => 'europe',
        'eun1' => 'europe',
        'tr1' => 'europe',
        'ru' => 'europe',
        'na1' => 'americas',
        'br1' => 'americas',
        'la1' => 'americas',
        'la2' => 'americas',
        'kr' => 'asia',
        'jp1' => 'asia',
    ];
    
    return isset($mapping[$region]) ? $mapping[$region] : 'europe';
}

/**
 * Récupère le nom du champion à partir de son ID
 */
function getChampionNameById($championId) {
    // En pratique, il faudrait télécharger et mettre en cache la liste des champions
    // depuis le Data Dragon de Riot
    // Simulation avec quelques champions courants
    $champions = [
        1 => 'Annie',
        2 => 'Olaf',
        3 => 'Galio',
        4 => 'Twisted Fate',
        5 => 'Xin Zhao',
        6 => 'Urgot',
        7 => 'LeBlanc',
        8 => 'Vladimir',
        9 => 'Fiddlesticks',
        10 => 'Kayle',
        11 => 'Master Yi',
        12 => 'Alistar',
        13 => 'Ryze',
        14 => 'Sion',
        15 => 'Sivir',
        // ... et bien d'autres
    ];
    
    return isset($champions[$championId]) ? $champions[$championId] : 'Inconnu';
}

/**
 * Récupère le nom de la file d'attente à partir de son ID
 */
function getQueueTypeById($queueId) {
    $queueTypes = [
        400 => 'Normal Draft',
        420 => 'Classé Solo/Duo',
        430 => 'Normal Blind',
        440 => 'Classé Flex',
        450 => 'ARAM',
        700 => 'Clash',
        1400 => 'Ultimate Spellbook',
        // ... et d'autres modes de jeu
    ];
    
    return isset($queueTypes[$queueId]) ? $queueTypes[$queueId] : 'Mode personnalisé';
}

/**
 * Récupère la liste des amis d'un utilisateur
 */
function getFriendsList($userId) {
    global $conn;
    
    // Simulation des données pour ce projet de démonstration
    // En réalité, ces données viendraient de la base de données
    
    return [
        [
            'id' => 1,
            'summoner_name' => 'Faker',
            'region' => 'kr',
            'in_game' => true,
            'game_mode' => 'Classé Solo/Duo',
            'champion' => 'LeBlanc',
            'game_time' => 1245,
            'game_id' => 'KR_12345678',
            'last_game' => null
        ],
        [
            'id' => 2,
            'summoner_name' => 'Caps',
            'region' => 'euw1',
            'in_game' => true,
            'game_mode' => 'Normal Draft',
            'champion' => 'Syndra',
            'game_time' => 895,
            'game_id' => 'EUW_23456789',
            'last_game' => null
        ],
        [
            'id' => 3,
            'summoner_name' => 'Doublelift',
            'region' => 'na1',
            'in_game' => false,
            'game_mode' => null,
            'champion' => null,
            'game_time' => null,
            'game_id' => null,
            'last_game' => 'Il y a 2 heures'
        ],
        [
            'id' => 4,
            'summoner_name' => 'Rekkles',
            'region' => 'euw1',
            'in_game' => false,
            'game_mode' => null,
            'champion' => null,
            'game_time' => null,
            'game_id' => null,
            'last_game' => 'Il y a 30 minutes'
        ]
    ];
}

/**
 * Formate le temps de jeu en minutes:secondes
 */
function formatGameTime($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return sprintf('%d:%02d', $minutes, $remainingSeconds);
}
