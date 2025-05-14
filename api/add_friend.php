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
    header('Location: ../index.php?error=empty_summoner_name');
    exit;
}

// Vérifier que la région est valide
$validRegions = ['euw1', 'eun1', 'na1', 'kr', 'jp1', 'br1', 'la1', 'la2', 'tr1', 'ru', 'oc1'];
if (!in_array($region, $validRegions)) {
    header('Location: ../index.php?error=invalid_region');
    exit;
}

// Récupérer les informations de l'invocateur
$summonerInfo = getSummonerByName($summonerName, $region);

if (!$summonerInfo) {
    header('Location: ../index.php?error=summoner_not_found');
    exit;
}

// Dans un environnement de production, vous inséreriez les données dans la base de données
// Pour cette démonstration, on redirige simplement vers la page d'accueil
header('Location: ../index.php?success=friend_added');
exit;
