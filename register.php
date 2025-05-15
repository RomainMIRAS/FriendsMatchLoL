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
$success = false;

// Form processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $summoner_name = trim($_POST['summoner_name'] ?? '');
    $region = $_POST['region'] ?? '';
    
    // Simple validation
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Tous les champs obligatoires doivent être remplis.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit comporter au moins 6 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format d\'email invalide.';
    } else {
        try {
            // Connect to database
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if username already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Ce nom d\'utilisateur existe déjà.';
            } else {
                // Check if email already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error = 'Cette adresse email existe déjà.';
                } else {
                    // Hash the password for security
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert the new user
                    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, summoner_name, region) VALUES (:username, :email, :password_hash, :summoner_name, :region)");
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password_hash', $password_hash);
                    $stmt->bindParam(':summoner_name', $summoner_name);
                    $stmt->bindParam(':region', $region);
                    $stmt->execute();
                    
                    $success = true;
                }
            }
        } catch (PDOException $e) {
            $error = 'Erreur d\'inscription: ' . $e->getMessage();
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2><i class="fas fa-user-plus me-2"></i>Inscription</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <p>Inscription réussie ! Vous pouvez maintenant <a href="login.php">vous connecter</a>.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="register.php">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur*</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email*</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe*</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       pattern=".{6,}" title="Le mot de passe doit contenir au moins 6 caractères" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe*</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <hr>
                            <p class="text-muted">Informations de League of Legends (facultatif)</p>
                            
                            <div class="mb-3">
                                <label for="summoner_name" class="form-label">Nom d'invocateur</label>
                                <input type="text" class="form-control" id="summoner_name" name="summoner_name">
                            </div>
                            <div class="mb-3">
                                <label for="region" class="form-label">Région</label>
                                <select class="form-control" id="region" name="region">
                                    <option value="">-- Sélectionnez votre région --</option>
                                    <option value="euw1">Europe Ouest (EUW)</option>
                                    <option value="eun1">Europe Nord & Est (EUNE)</option>
                                    <option value="na1">Amérique du Nord (NA)</option>
                                    <option value="kr">Corée (KR)</option>
                                    <option value="jp1">Japon (JP)</option>
                                    <option value="br1">Brésil (BR)</option>
                                    <option value="la1">Amérique Latine Nord</option>
                                    <option value="la2">Amérique Latine Sud</option>
                                    <option value="oc1">Océanie</option>
                                    <option value="tr1">Turquie</option>
                                    <option value="ru">Russie</option>
                                </select>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="agreeTos" name="agreeTos" required>
                                <label class="form-check-label" for="agreeTos">J'accepte les <a href="#" data-bs-toggle="modal" data-bs-target="#tosModal">conditions d'utilisation</a>*</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">S'inscrire</button>
                            <p class="small mt-2">* Champs obligatoires</p>
                        </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <p>Déjà inscrit? <a href="login.php">Se connecter</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les conditions d'utilisation -->
<div class="modal fade" id="tosModal" tabindex="-1" aria-labelledby="tosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tosModalLabel">Conditions d'utilisation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>1. Acceptation des conditions</h5>
                <p>En vous inscrivant à FriendsMatchLoL, vous acceptez d'être lié par ces conditions d'utilisation.</p>
                
                <h5>2. Utilisation de l'API Riot Games</h5>
                <p>FriendsMatchLoL utilise l'API Riot Games et se conforme à toutes les politiques, y compris leurs <a href="https://developer.riotgames.com/policies/general" target="_blank">conditions générales</a>.</p>
                
                <h5>3. Confidentialité</h5>
                <p>Nous ne collectons que les informations nécessaires au fonctionnement du service. Vos identifiants de connexion sont stockés de manière sécurisée.</p>
                
                <h5>4. Limitation de responsabilité</h5>
                <p>FriendsMatchLoL n'est pas responsable des interruptions de service, des inexactitudes des données fournies par Riot Games ou de tout dommage résultant de l'utilisation du service.</p>
                
                <h5>5. Clause de non-endossement</h5>
                <p>FriendsMatchLoL n'est pas approuvé par Riot Games et ne reflète pas les opinions ou les points de vue de Riot Games ou de quiconque impliqué dans la production ou la gestion des propriétés de Riot Games.</p>
                
                <h5>6. Modifications des conditions</h5>
                <p>Nous nous réservons le droit de modifier ces conditions à tout moment. Les modifications prendront effet dès leur publication.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">J'ai compris</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
