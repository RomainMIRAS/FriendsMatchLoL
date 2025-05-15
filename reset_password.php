<?php
require_once 'includes/header.php';
require_once 'config/config.php';

// Initialize the session
session_start();

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Check if token exists and is not empty
if (empty($token)) {
    $error = 'Token de réinitialisation invalide ou manquant.';
}

// Process the form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($token)) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Simple validation
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit comporter au moins 6 caractères.';
    } else {
        try {
            // Connect to database
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if token is valid and not expired
            $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = :token AND reset_token_expires_at > NOW()");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update user's password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("UPDATE users SET password_hash = :password_hash, reset_token = NULL, reset_token_expires_at = NULL WHERE id = :id");
                $stmt->bindParam(':password_hash', $passwordHash);
                $stmt->bindParam(':id', $user['id']);
                $stmt->execute();
                
                $success = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.";
            } else {
                $error = "Le lien de réinitialisation est invalide ou a expiré. Veuillez demander un nouveau lien.";
            }
        } catch (PDOException $e) {
            $error = 'Erreur de serveur: ' . $e->getMessage();
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] != 'POST' && !empty($token)) {
    // Verify the token is valid
    try {
        // Connect to database
        $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if token exists and is not expired
        $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = :token AND reset_token_expires_at > NOW()");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $error = "Le lien de réinitialisation est invalide ou a expiré. Veuillez demander un nouveau lien.";
        }
    } catch (PDOException $e) {
        $error = 'Erreur de serveur: ' . $e->getMessage();
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2><i class="fas fa-key me-2"></i>Réinitialisation du mot de passe</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="btn btn-outline-primary">Demander un nouveau lien</a>
                        </div>
                    <?php elseif (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-primary">Se connecter</a>
                        </div>
                    <?php else: ?>
                        <p>Saisissez votre nouveau mot de passe ci-dessous.</p>
                        
                        <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           pattern=".{6,}" title="Le mot de passe doit contenir au moins 6 caractères" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Réinitialiser le mot de passe</button>
                        </form>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p><a href="login.php"><i class="fas fa-arrow-left me-2"></i>Retour à la page de connexion</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
