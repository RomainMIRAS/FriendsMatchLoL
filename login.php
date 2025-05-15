<?php
require_once 'includes/header.php';
require_once 'config/config.php';
require_once 'includes/utils.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Vérifier si l'utilisateur vient de supprimer son compte
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $success = 'Votre compte a été supprimé avec succès.';
}

// Form processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple validation
    if (empty($username) || empty($password)) {
        $error = 'Veuillez entrer votre nom d\'utilisateur et votre mot de passe.';
    } else {
        try {
            // Connect to database
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check credentials
            $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify the password
                if (password_verify($password, $user['password_hash'])) {
                    // Password is correct, create session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    // If "remember me" is checked, set a cookie
                    if (isset($_POST['remember_me']) && $_POST['remember_me'] == 1) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + 86400 * 30, '/'); // 30 days
                        
                        // Store the token in database for verification later
                        $stmt = $db->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
                        $stmt->bindParam(':token', $token);
                        $stmt->bindParam(':id', $user['id']);
                        $stmt->execute();
                    }
                    
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
                }
            } else {
                $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion: ' . $e->getMessage();
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2><i class="fas fa-sign-in-alt me-2"></i>Connexion</h2>
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
                    <?php endif; ?>
                    
                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me" value="1">
                            <label class="form-check-label" for="remember_me">Se souvenir de moi</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Se connecter</button>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p>Vous n'avez pas de compte ? <a href="register.php">S'inscrire</a></p>
                        <p class="small"><a href="forgot_password.php">Mot de passe oublié ?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
