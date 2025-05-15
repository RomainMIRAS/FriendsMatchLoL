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
        // Déterminer le type d'endpoint pour un meilleur logging
        $endpointType = "Generic";
        if (strpos($endpoint, '/riot/account/v1/accounts/by-riot-id/') !== false) {
            $endpointType = "Riot ID";
        } elseif (strpos($endpoint, '/lol/summoner/v4/summoners/by-puuid/') !== false) {
            $endpointType = "Summoner by PUUID";
        } elseif (strpos($endpoint, '/lol/summoner/v4/summoners/by-name/') !== false) {
            $endpointType = "Summoner by Name";
        } elseif (strpos($endpoint, '/lol/spectator/v4/active-games/by-summoner/') !== false) {
            $endpointType = "Active Game";
        }
        
        logAPIError($httpCode, $apiUrl, $response, $endpointType);
        
        // Pour les erreurs 404 sur les Riot IDs, fournir une erreur plus descriptive
        if ($httpCode === 404 && $endpointType === "Riot ID") {
            error_log("Riot ID non trouvé: $apiUrl");
        } else {
            error_log("API Error: $httpCode | URL: $apiUrl | Response: $response");
        }
        
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Log les erreurs d'API
 * 
 * @param int $code Le code HTTP de l'erreur
 * @param string $url L'URL appelée
 * @param string $response La réponse de l'API
 * @param string $endpoint Le type d'endpoint utilisé (ex: "Riot ID", "Summoner", etc.)
 */
function logAPIError($code, $url, $response, $endpoint = "Generic") {
    $logFile = __DIR__ . '/../logs/api_errors.log';
    
    // Analyser la réponse pour obtenir des détails d'erreur
    $errorDetails = json_decode($response, true);
    $errorStatus = isset($errorDetails['status']) ? $errorDetails['status']['message'] : 'Unknown error';
    
    // Format amélioré du message d'erreur
    $errorMessage = sprintf(
        "[%s] | Endpoint: %s | Code: %d | Status: %s | URL: %s | Response: %s\n",
        date('Y-m-d H:i:s'),
        $endpoint,
        $code,
        $errorStatus,
        $url,
        $response
    );
    
    try {
        // Créer le dossier logs s'il n'existe pas
        if (!is_dir(dirname($logFile))) {
            if (!@mkdir(dirname($logFile), 0755, true)) {
                error_log("Failed to create log directory: " . dirname($logFile));
                return;
            }
        }
        
        // Essayer d'écrire dans le fichier
        if (!@file_put_contents($logFile, $errorMessage, FILE_APPEND)) {
            error_log("Failed to write to log file: $logFile");
        }
    } catch (Exception $e) {
        error_log("Error logging API error: " . $e->getMessage());
    }
}

/**
 * Récupère les informations d'un invocateur par son nom
 */
/**
 * Récupère les informations d'un compte par Riot ID (nouveau format gameName#tagLine)
 * 
 * @param string $gameName Le nom de jeu (partie avant le #)
 * @param string $tagLine Le tag (partie après le #)
 * @param string $region La région continentale (europe, americas, asia, sea)
 * @return array|null Informations du compte ou null en cas d'erreur
 */
function getAccountByRiotId($gameName, $tagLine, $region) {
    $endpoint = '/riot/account/v1/accounts/by-riot-id/' . urlencode($gameName) . '/' . urlencode($tagLine);
    return callRiotAPI($region, $endpoint);
}

/**
 * Récupère les informations d'un invocateur par PUUID
 * 
 * @param string $puuid L'identifiant unique universel du joueur
 * @param string $region La région de jeu (euw1, na1, etc.)
 * @return array|null Informations de l'invocateur ou null en cas d'erreur
 */
function getSummonerByPUUID($puuid, $region) {
    $endpoint = '/lol/summoner/v4/summoners/by-puuid/' . $puuid;
    return callRiotAPI($region, $endpoint);
}

/**
 * Récupère les informations d'un invocateur par son nom (ancienne méthode)
 * 
 * @param string $summonerName Le nom d'invocateur
 * @param string $region La région de jeu
 * @return array|null Informations de l'invocateur ou null en cas d'erreur
 * @deprecated Utiliser getAccountByRiotId() puis getSummonerByPUUID() à la place
 */
function getSummonerByName($summonerName, $region) {
    $endpoint = '/lol/summoner/v4/summoners/by-name/' . urlencode($summonerName);
    return callRiotAPI($region, $endpoint);
}

/**
 * Récupère l'historique des matchs pour un joueur donné
 * 
 * @param string $puuid L'identifiant PUUID du joueur
 * @param string $region La région du joueur
 * @param int $count Nombre de matchs à récupérer (max 100)
 * @param int $start Index de départ pour la pagination
 * @return array|null Liste des identifiants de match ou null en cas d'erreur
 */
function getMatchHistory($puuid, $region, $count = 10, $start = 0) {
    // Conversion de la région de jeu en région continentale pour l'API Match v5
    $regionMapping = [
        'br1' => 'americas', 'eun1' => 'europe', 'euw1' => 'europe', 'jp1' => 'asia',
        'kr' => 'asia', 'la1' => 'americas', 'la2' => 'americas', 'na1' => 'americas',
        'oc1' => 'sea', 'ru' => 'europe', 'tr1' => 'europe'
    ];
    
    $continentalRegion = isset($regionMapping[$region]) ? $regionMapping[$region] : 'europe';
    
    // Endpoint pour récupérer la liste des matchs
    $endpoint = '/lol/match/v5/matches/by-puuid/' . $puuid . '/ids';
    $params = ['start' => $start, 'count' => min($count, 100)];
    
    // Appel API avec la région continentale
    $matchIds = callRiotAPI($continentalRegion, $endpoint, $params);
    
    if (!$matchIds) {
        return [];
    }
    
    return $matchIds;
}

/**
 * Récupère les détails d'un match spécifique de l'historique
 * 
 * @param string $matchId L'identifiant du match
 * @param string $region La région du joueur
 * @return array|null Détails du match ou null en cas d'erreur
 */
function getMatchDetails($matchId, $region) {
    // Conversion de la région de jeu en région continentale pour l'API Match v5
    $regionMapping = [
        'br1' => 'americas', 'eun1' => 'europe', 'euw1' => 'europe', 'jp1' => 'asia',
        'kr' => 'asia', 'la1' => 'americas', 'la2' => 'americas', 'na1' => 'americas',
        'oc1' => 'sea', 'ru' => 'europe', 'tr1' => 'europe'
    ];
    
    $continentalRegion = isset($regionMapping[$region]) ? $regionMapping[$region] : 'europe';
    
    // Endpoint pour récupérer les détails du match
    $endpoint = '/lol/match/v5/matches/' . $matchId;
    
    // Vérifier d'abord si les données sont en cache
    $cacheFile = __DIR__ . '/../cache/match_details_' . $matchId . '.json';
    
    // Si le cache existe et date de moins de 24 heures, on l'utilise (les matchs terminés ne changent pas)
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    
    // Appel API avec la région continentale
    $matchData = callRiotAPI($continentalRegion, $endpoint);
    
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
 * Vérifie si un invocateur est actuellement en partie
 * 
 * @param string $summonerId L'ID du summoner
 * @param string $region La région du jeu
 * @param string $puuid PUUID optionnel si le summoner_id n'est pas disponible
 * @return array|null Informations sur la partie en cours ou null
 */
function getCurrentMatch($summonerId, $region, $puuid = null) {
    // Si nous avons un ID de sommoner, on l'utilise directement
    if ($summonerId) {
        $endpoint = '/lol/spectator/v4/active-games/by-summoner/' . $summonerId;
        return callRiotAPI($region, $endpoint);
    } 
    // Si nous n'avons qu'un PUUID, on essaie d'abord d'obtenir l'ID du sommoner
    elseif ($puuid) {
        $summonerData = getSummonerByPUUID($puuid, $region);
        if ($summonerData && isset($summonerData['id'])) {
            $endpoint = '/lol/spectator/v4/active-games/by-summoner/' . $summonerData['id'];
            return callRiotAPI($region, $endpoint);
        }
    }
    
    return null;
}

/**
 * Récupère les détails d'un match en cours basé sur l'ID du jeu
 */
function getCurrentGameDetails($gameId, $region) {
    // Cette fonction utilise un mélange de l'API de spectateur et du cache local
    // pour obtenir des données complètes sur un match en cours
    
    // D'abord, on vérifie si les données sont en cache
    $cacheFile = __DIR__ . '/../cache/match_' . $gameId . '.json';
    
    // Si le cache existe et date de moins de 30 secondes, on l'utilise
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 30)) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    
    // Sinon, on appelle l'API
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
 * Formate le temps de jeu en minutes:secondes
 */
function formatGameTime($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return sprintf('%d:%02d', $minutes, $remainingSeconds);
}