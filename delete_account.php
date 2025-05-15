<?php
require_once 'config/config.php';
require_once 'includes/utils.php';

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        $error = 'Le mot de passe est requis pour confirmer la suppression.';
    } else {
        try {
            // Connexion à la base de données
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Vérifier le mot de passe
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $user['password_hash'])) {
                    // Mot de passe correct, procéder à la suppression
                    
                    // Commencer une transaction
                    $db->beginTransaction();
                    
                    try {
                        // 1. Supprimer les amis de l'utilisateur
                        $stmt = $db->prepare("DELETE FROM friends WHERE user_id = :user_id");
                        $stmt->bindParam(':user_id', $userId);
                        $stmt->execute();
                        
                        // 2. Supprimer le compte utilisateur
                        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
                        $stmt->bindParam(':id', $userId);
                        $stmt->execute();
                        
                        // Valider la transaction
                        $db->commit();
                        
                        // Déconnexion
                        logout();
                        
                        // Rediriger vers la page d'accueil avec un message
                        $_SESSION['delete_success'] = true;
                        header('Location: login.php?deleted=1');
                        exit;
                        
                    } catch (PDOException $e) {
                        // Annuler la transaction en cas d'erreur
                        $db->rollBack();
                        $error = 'Erreur lors de la suppression du compte: ' . $e->getMessage();
                    }
                } else {
                    $error = 'Mot de passe incorrect.';
                }
            } else {
                $error = 'Utilisateur non trouvé.';
            }
            
        } catch (PDOException $e) {
            $error = 'Erreur de connexion à la base de données: ' . $e->getMessage();
        }
    }
}

// Si on arrive ici, c'est qu'il y a eu une erreur, rediriger vers la page de profil avec l'erreur
$_SESSION['delete_error'] = $error;
header('Location: profile.php');
exit;
?>
