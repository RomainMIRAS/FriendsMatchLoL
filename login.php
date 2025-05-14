<?php
require_once 'includes/header.php';
require_once 'config/config.php';

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validation simple
    if (empty($username) || empty($password)) {
        $error = 'Veuillez saisir un nom d\'utilisateur et un mot de passe.';
    } else {
        // Simulation d'authentification pour démonstration
        // En production, vérifiez les identifiants dans la base de données
        if ($username === 'demo' && $password === 'demo123') {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'demo';
            header('Location: index.php');
            exit;
        } else {
            $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Connexion</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Se connecter</button>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <div class="text-center">
                        <p class="small">Pour la démo, utilisez le nom d'utilisateur <strong>demo</strong> et le mot de passe <strong>demo123</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
