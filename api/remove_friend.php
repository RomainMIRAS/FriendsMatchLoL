<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/riot_api.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Non autorisé');
}

// Vérifier si l'ID de l'ami est présent
if (!isset($_POST['friend_id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('ID d\'ami manquant');
}

$friendId = (int)$_POST['friend_id'];

// Dans un environnement de production, vous supprimeriez l'ami de la base de données
// Pour cette démonstration, on renvoie simplement une réponse de succès
header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit;
