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

$error = '';
$success = '';
$email = '';

// Form processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    // Simple validation
    if (empty($email)) {
        $error = 'Veuillez entrer votre adresse email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format d\'email invalide.';
    } else {
        try {
            // Connect to database
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if email exists
            $stmt = $db->prepare("SELECT id, username FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Generate a token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // Token expires in 1 hour
                
                // Store the reset token in the database
                $stmt = $db->prepare("UPDATE users SET reset_token = :token, reset_token_expires_at = :expires WHERE id = :id");
                $stmt->bindParam(':token', $token);
                $stmt->bindParam(':expires', $expires);
                $stmt->bindParam(':id', $user['id']);
                $stmt->execute();
                
                // In a real application, send an email with the reset link
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/FriendsMatchLoL/reset_password.php?token=" . $token;
                
                // For demo, we just show the success message and the link
                $success = "Un email de réinitialisation a été envoyé à " . htmlspecialchars($email) . ". ";
                $success .= "Le lien sera valide pendant 1 heure.";
                
                // For demo purposes only, include the reset link in the page
                // In a real app, this should be sent by email
                $success .= "<br><br><strong>Lien de réinitialisation (pour démo uniquement):</strong><br>";
                $success .= "<a href='" . $resetLink . "'>" . $resetLink . "</a>";
                
                $email = ''; // Clear the email field
            } else {
                // Don't reveal if the email exists or not for security
                $success = "Si cette adresse email existe dans notre système, un email de réinitialisation sera envoyé.";
            }
        } catch (PDOException $e) {
            $error = 'Erreur de serveur: ' . $e->getMessage();
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2><i class="fas fa-key me-2"></i>Mot de passe oublié</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php else: ?>
                        <p>Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
                        
                        <form method="POST" action="forgot_password.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Envoyer le lien de réinitialisation</button>
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
