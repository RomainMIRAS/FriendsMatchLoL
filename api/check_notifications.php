<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/riot_api.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Récupérer les préférences de notification de l'utilisateur
// Dans un vrai environnement, ces données viendraient de la base de données
$notificationsEnabled = true; // Simuler que les notifications sont activées

if (!$notificationsEnabled) {
    header('Content-Type: application/json');
    echo json_encode(['notifications' => []]);
    exit;
}

// Vérifier si l'utilisateur a une session de notifications
if (!isset($_SESSION['last_notification_check'])) {
    $_SESSION['last_notification_check'] = time();
    $_SESSION['friends_game_status'] = [];
}

// Récupérer la liste des amis
$friends = getFriendsList($_SESSION['user_id']);

$notifications = [];

// Vérifier les nouvelles parties pour chaque ami
foreach ($friends as $friend) {
    $friendId = $friend['id'];
    $wasInGame = isset($_SESSION['friends_game_status'][$friendId]) && $_SESSION['friends_game_status'][$friendId]['in_game'];
    $isInGame = $friend['in_game'];
    
    // Mettre à jour le statut en jeu
    $_SESSION['friends_game_status'][$friendId] = [
        'in_game' => $isInGame,
        'game_id' => $friend['game_id'] ?? null,
        'game_mode' => $friend['game_mode'] ?? null
    ];
    
    // Si l'ami vient de commencer une partie, créer une notification
    if ($isInGame && !$wasInGame) {
        $notifications[] = [
            'id' => uniqid(),
            'type' => 'friend_in_game',
            'friend_id' => $friendId,
            'friend_name' => $friend['summoner_name'],
            'game_mode' => $friend['game_mode'],
            'champion' => $friend['champion'],
            'timestamp' => time()
        ];
    }
}

// Mettre à jour la dernière vérification
$_SESSION['last_notification_check'] = time();

// Renvoyer les notifications
header('Content-Type: application/json');
echo json_encode(['notifications' => $notifications]);
exit;
