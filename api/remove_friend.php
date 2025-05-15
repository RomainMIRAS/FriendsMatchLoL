<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/riot_api.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier si l'ID de l'ami est présent
if (!isset($_POST['friend_id'])) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID d\'ami manquant']);
    exit;
}

$friendId = (int)$_POST['friend_id'];
$userId = (int)$_SESSION['user_id'];

try {
    // Connexion à la base de données
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier que l'ami appartient bien à l'utilisateur connecté
    $stmt = $db->prepare("SELECT id FROM friends WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $friendId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas le droit de supprimer cet ami']);
        exit;
    }
    
    // Supprimer l'ami
    $stmt = $db->prepare("DELETE FROM friends WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $friendId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    // Répondre avec un succès
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Ami supprimé avec succès']);
    exit;
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    exit;
}
