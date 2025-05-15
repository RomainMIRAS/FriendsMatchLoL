<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/riot_api.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Non autorisé');
}

// Vérifier si les données nécessaires sont présentes
if (!isset($_POST['game_name']) || !isset($_POST['tag_line']) || !isset($_POST['region'])) {
    header('Location: ../index.php?error=incomplete_data');
    exit;
}

$gameName = trim($_POST['game_name']);
$tagLine = trim($_POST['tag_line']);
$continentRegion = trim($_POST['region']);

// Vérifier que les informations sont valides
if (empty($gameName) || empty($tagLine)) {
    header('Location: ../index.php?error=empty_riot_id');
    exit;
}

// Vérifier que la région continentale est valide
$validRegions = ['europe', 'asia', 'americas', 'sea'];
if (!in_array($continentRegion, $validRegions)) {
    header('Location: ../index.php?error=invalid_region');
    exit;
}

// Récupérer les informations du compte avec Riot ID
$accountInfo = getAccountByRiotId($gameName, $tagLine, $continentRegion);

if (!$accountInfo || !isset($accountInfo['puuid'])) {
    header('Location: ../index.php?error=account_not_found');
    exit;
}

// Correspondance entre région continentale et région de jeu pour l'API Summoner
$gameRegions = [
    'europe' => ['euw1', 'eun1', 'tr1', 'ru'],
    'asia' => ['kr', 'jp1'],
    'americas' => ['na1', 'br1', 'la1', 'la2'],
    'sea' => ['oc1']
];

// Essayer de trouver l'invocateur dans les régions de jeu correspondantes
$summonerInfo = null;
$foundRegion = null;

foreach ($gameRegions[$continentRegion] as $gameRegion) {
    $summonerInfo = getSummonerByPUUID($accountInfo['puuid'], $gameRegion);
    if ($summonerInfo) {
        $foundRegion = $gameRegion;
        break;
    }
}

if (!$summonerInfo || !$foundRegion) {
    header('Location: ../index.php?error=summoner_not_found');
    exit;
}

// Vérifier et compléter les données manquantes si nécessaire
if (!isset($summonerInfo['id']) || empty($summonerInfo['id'])) {
    // Générer un ID unique basé sur PUUID si celui du summoner est manquant
    $summonerInfo['id'] = 'gen_' . substr(md5($accountInfo['puuid']), 0, 16);
    error_log("Summoner ID manquant, utilisation d'un ID généré: " . $summonerInfo['id']);
}

if (!isset($summonerInfo['name']) || empty($summonerInfo['name'])) {
    // Utiliser le gameName comme fallback
    $summonerInfo['name'] = $gameName;
    error_log("Summoner Name manquant, utilisation du gameName: " . $summonerInfo['name']);
}

// Vérifier si cet ami existe déjà dans la liste de l'utilisateur
try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    
    // Vérifier l'existence de l'ami par PUUID (plus fiable que summoner_id)
    $stmt = $db->prepare("
        SELECT id FROM friends 
        WHERE user_id = :user_id 
        AND (puuid = :puuid OR (summoner_id = :summoner_id AND region = :region))
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':puuid', $accountInfo['puuid']);
    $stmt->bindParam(':summoner_id', $summonerInfo['id']);
    $stmt->bindParam(':region', $foundRegion);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        header('Location: ../index.php?error=friend_exists');
        exit;
    }
    
    // Ajouter l'ami dans la base de données
    $stmt = $db->prepare("
        INSERT INTO friends (user_id, summoner_id, summoner_name, puuid, region, riot_id) 
        VALUES (:user_id, :summoner_id, :summoner_name, :puuid, :region, :riot_id)
    ");
    
    // S'assurer que le summoner_name n'est jamais NULL
    $summonerName = isset($summonerInfo['name']) && !empty($summonerInfo['name']) 
        ? $summonerInfo['name'] 
        : $gameName; // Utiliser le gameName comme fallback
    
    $riotId = $gameName . '#' . $tagLine;
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':summoner_id', $summonerInfo['id']);
    $stmt->bindParam(':summoner_name', $summonerName);
    $stmt->bindParam(':puuid', $accountInfo['puuid']);
    $stmt->bindParam(':region', $foundRegion);
    $stmt->bindParam(':riot_id', $riotId);
    
    // Ajouter un log pour le débogage
    error_log("Ajout d'ami - PUUID: {$accountInfo['puuid']}, Summoner Name: {$summonerName}, Riot ID: {$riotId}, Region: {$foundRegion}");
    
    $stmt->execute();
    
    header('Location: ../index.php?success=friend_added');
    exit;
    
} catch (PDOException $e) {
    // Enregistrer l'erreur dans les logs
    error_log('Erreur lors de l\'ajout d\'un ami: ' . $e->getMessage(), 0);
    header('Location: ../index.php?error=database_error');
    exit;
}
