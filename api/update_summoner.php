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
if (!isset($_POST['summoner_name']) || !isset($_POST['region'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Données incomplètes');
}

$summonerName = trim($_POST['summoner_name']);
$region = trim($_POST['region']);

// Vérifier que le nom d'invocateur est valide
if (empty($summonerName)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Nom d\'invocateur requis');
}

if (empty($region) || !in_array($region, ['euw1', 'eun1', 'na1', 'kr', 'jp1', 'br1', 'la1', 'la2', 'oc1', 'tr1', 'ru'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Région invalide');
}

$userId = $_SESSION['user_id'];

// Valider l'existence de l'invocateur via l'API Riot (facultatif, à implémenter plus tard)
// Pour l'instant, on enregistre simplement les informations

try {
    // Connexion à la base de données
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mettre à jour les informations de l'invocateur
    $stmt = $db->prepare("UPDATE users SET summoner_name = :summoner_name, region = :region WHERE id = :user_id");
    $stmt->bindParam(':summoner_name', $summonerName);
    $stmt->bindParam(':region', $region);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    // Rediriger vers la page de profil avec un message de succès
    $_SESSION['summoner_updated'] = true;
    header('Location: ../profile.php?summoner_updated=1');
    exit;
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Erreur de base de données: ' . $e->getMessage());
}
if (empty($summonerName)) {
    header('Location: ../profile.php?tab=summoner&error=empty_summoner_name');
    exit;
}

// Vérifier que la région est valide
$validRegions = ['euw1', 'eun1', 'na1', 'kr', 'jp1', 'br1', 'la1', 'la2', 'tr1', 'ru', 'oc1'];
if (!in_array($region, $validRegions)) {
    header('Location: ../profile.php?tab=summoner&error=invalid_region');
    exit;
}

// Récupérer les informations de l'invocateur
$summonerInfo = getSummonerByName($summonerName, $region);

if (!$summonerInfo) {
    header('Location: ../profile.php?tab=summoner&error=summoner_not_found');
    exit;
}

// Dans un environnement de production, vous mettriez à jour les données dans la base de données
// Pour cette démonstration, on simule une mise à jour en stockant dans la session
$_SESSION['summoner_name'] = $summonerName;
$_SESSION['summoner_region'] = $region;

header('Location: ../profile.php?tab=summoner&success=summoner_updated');
exit;
