<?php
require_once 'includes/header.php';
require_once 'config/config.php';
require_once 'api/riot_api.php';
require_once 'includes/utils.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$success = false;
$error = '';
$message = '';

// Vérifier si l'invocateur vient d'être mis à jour
if (isset($_GET['summoner_updated']) && $_GET['summoner_updated'] == '1') {
    $success = true;
    $message = 'Informations d\'invocateur mises à jour avec succès.';
}

// Si le formulaire de modification est soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $summonerName = trim($_POST['summoner_name'] ?? '');
    $region = $_POST['region'] ?? '';
    $notificationsEnabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    
    try {
        // Connexion à la base de données
        $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier si l'email existe déjà (pour un autre utilisateur)
        if (!empty($email)) {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Cette adresse email est déjà utilisée par un autre compte.';
            }
        }
        
        // Si changement de mot de passe
        if (!empty($currentPassword) && !empty($newPassword)) {
            if ($newPassword !== $confirmPassword) {
                $error = 'Les nouveaux mots de passe ne correspondent pas.';
            } elseif (strlen($newPassword) < 6) {
                $error = 'Le nouveau mot de passe doit comporter au moins 6 caractères.';
            } else {
                // Vérifier le mot de passe actuel
                $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!password_verify($currentPassword, $user['password_hash'])) {
                    $error = 'Le mot de passe actuel est incorrect.';
                } else {
                    // Mettre à jour le mot de passe
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
                    $stmt->bindParam(':password_hash', $passwordHash);
                    $stmt->bindParam(':id', $userId);
                    $stmt->execute();
                    
                    $message = 'Mot de passe mis à jour avec succès.';
                }
            }
        }
        
        // Si pas d'erreur, mettre à jour les autres informations
        if (empty($error)) {
            $updateFields = [];
            $params = [':id' => $userId];
            
            if (!empty($email)) {
                $updateFields[] = "email = :email";
                $params[':email'] = $email;
            }
            
            if (!empty($summonerName)) {
                $updateFields[] = "summoner_name = :summoner_name";
                $params[':summoner_name'] = $summonerName;
            }
            
            if (!empty($region)) {
                $updateFields[] = "region = :region";
                $params[':region'] = $region;
            }
            
            $updateFields[] = "notifications_enabled = :notifications_enabled";
            $params[':notifications_enabled'] = $notificationsEnabled;
            
            if (!empty($updateFields)) {
                $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                
                $success = true;
                if (empty($message)) {
                    $message = 'Profil mis à jour avec succès.';
                }
            }
        }
        
    } catch (PDOException $e) {
        $error = 'Erreur de base de données: ' . $e->getMessage();
    }
}

// Récupérer les données de l'utilisateur depuis la base de données
try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->prepare("SELECT username, email, summoner_name, region, notifications_enabled, created_at FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    
    $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Erreur lors de la récupération des données: ' . $e->getMessage();
    $userProfile = [
        'username' => $_SESSION['username'],
        'email' => '',
        'summoner_name' => '',
        'region' => '',
        'notifications_enabled' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// Récupérer le nombre d'amis
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM friends WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $friendsCount = $result['count'] ?? 0;
} catch (PDOException $e) {
    $friendsCount = 0;
}

// Nous avons déjà traité les changements de profil plus haut dans le code
?>

<div class="container mt-5">
    <div class="row">
        <!-- Colonne de gauche - Informations de profil -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h2><i class="fas fa-user-circle me-2"></i>Profil</h2>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                    <h3><?php echo htmlspecialchars($userProfile['username']); ?></h3>
                    <?php if (!empty($userProfile['summoner_name'])): ?>
                        <p>
                            <i class="fas fa-gamepad me-2"></i>
                            <?php echo htmlspecialchars($userProfile['summoner_name']); ?> 
                            (<?php echo htmlspecialchars(strtoupper($userProfile['region'])); ?>)
                        </p>
                    <?php endif; ?>
                    <p class="text-muted small">
                        <i class="fas fa-calendar me-2"></i>
                        Inscrit depuis le <?php echo date('d/m/Y', strtotime($userProfile['created_at'])); ?>
                    </p>
                    <div class="border-top pt-3">
                        <div class="row">
                            <div class="col">
                                <h4><?php echo $friendsCount; ?></h4>
                                <p class="text-muted mb-0">Amis suivis</p>
                            </div>
                            <div class="col">
                                <h4><?php echo $userProfile['notifications_enabled'] ? 'Activées' : 'Désactivées'; ?></h4>
                                <p class="text-muted mb-0">Notifications</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fas fa-link me-2"></i>Liens rapides</h4>
                </div>
                <div class="card-body">
                    <a href="index.php" class="btn btn-outline-primary mb-2 w-100"><i class="fas fa-home me-2"></i>Accueil</a>
                    <a href="dashboard.php" class="btn btn-outline-primary mb-2 w-100"><i class="fas fa-chart-pie me-2"></i>Tableau de bord</a>
                    <a href="match_history.php" class="btn btn-outline-primary mb-2 w-100"><i class="fas fa-history me-2"></i>Historique des matchs</a>
                </div>
            </div>
        </div>
        
        <!-- Colonne de droite - Formulaires de modification -->
        <div class="col-lg-8">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h2><i class="fas fa-cog me-2"></i>Modifier votre profil</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="profile.php">
                        <h4 class="mb-3">Informations personnelles</h4>
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($userProfile['username']); ?>" disabled>
                            <div class="form-text">Le nom d'utilisateur ne peut pas être modifié.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userProfile['email']); ?>">
                        </div>
                        
                        <hr>
                        <h4 class="mb-3">Informations League of Legends</h4>
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="summoner_name" class="form-label">Nom d'invocateur</label>
                                <input type="text" class="form-control" id="summoner_name" name="summoner_name" value="<?php echo htmlspecialchars($userProfile['summoner_name']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="region" class="form-label">Région</label>
                                <select class="form-control" id="region" name="region">
                                    <option value="">-- Sélectionner --</option>
                                    <option value="euw1" <?php if ($userProfile['region'] == 'euw1') echo 'selected'; ?>>Europe Ouest (EUW)</option>
                                    <option value="eun1" <?php if ($userProfile['region'] == 'eun1') echo 'selected'; ?>>Europe Nord & Est (EUNE)</option>
                                    <option value="na1" <?php if ($userProfile['region'] == 'na1') echo 'selected'; ?>>Amérique du Nord (NA)</option>
                                    <option value="kr" <?php if ($userProfile['region'] == 'kr') echo 'selected'; ?>>Corée (KR)</option>
                                    <option value="jp1" <?php if ($userProfile['region'] == 'jp1') echo 'selected'; ?>>Japon (JP)</option>
                                    <option value="br1" <?php if ($userProfile['region'] == 'br1') echo 'selected'; ?>>Brésil (BR)</option>
                                    <option value="la1" <?php if ($userProfile['region'] == 'la1') echo 'selected'; ?>>Amérique Latine Nord</option>
                                    <option value="la2" <?php if ($userProfile['region'] == 'la2') echo 'selected'; ?>>Amérique Latine Sud</option>
                                    <option value="oc1" <?php if ($userProfile['region'] == 'oc1') echo 'selected'; ?>>Océanie</option>
                                    <option value="tr1" <?php if ($userProfile['region'] == 'tr1') echo 'selected'; ?>>Turquie</option>
                                    <option value="ru" <?php if ($userProfile['region'] == 'ru') echo 'selected'; ?>>Russie</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="notifications_enabled" name="notifications_enabled" <?php if ($userProfile['notifications_enabled']) echo 'checked'; ?>>
                            <label class="form-check-label" for="notifications_enabled">Activer les notifications de match</label>
                        </div>
                        
                        <hr>
                        <h4 class="mb-3">Modifier le mot de passe</h4>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        <div class="form-text mb-3">Laissez les champs de mot de passe vides si vous ne souhaitez pas le changer.</div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Enregistrer les modifications
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h2><i class="fas fa-exclamation-triangle me-2"></i>Zone de danger</h2>
                </div>
                <div class="card-body">
                    <p>Attention, ces actions sont irréversibles :</p>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="fas fa-trash-alt me-2"></i>Supprimer mon compte
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation pour la suppression du compte -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation de suppression</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer votre compte? Cette action est irréversible et toutes vos données seront définitivement supprimées.</p>
                <form id="deleteForm" method="POST" action="delete_account.php">
                    <div class="mb-3">
                        <label for="delete_password" class="form-label">Saisissez votre mot de passe pour confirmer</label>
                        <input type="password" class="form-control" id="delete_password" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="deleteForm" class="btn btn-danger">
                    <i class="fas fa-trash-alt me-2"></i>Supprimer définitivement
                </button>
            </div>
        </div>
    </div>
</div>
                    </div>
                </div>
                
                <!-- My LoL account -->
                <div class="tab-pane fade" id="summoner">
                    <div class="card">
                        <div class="card-header">
                            <h3>My League of Legends Account</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($userProfile['summoner_name']): ?>
                                <div class="mb-3">
                                    <label class="form-label">Summoner Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['summoner_name']); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Region</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['region']); ?>" disabled>
                                </div>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changeSummonerModal">
                                    Change LoL Account
                                </button>
                            <?php else: ?>
                                <p>You haven't linked your League of Legends account yet.</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changeSummonerModal">
                                    Link my LoL Account
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for changing/linking LoL account -->
<div class="modal fade" id="changeSummonerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $userProfile['summoner_name'] ? 'Change LoL Account' : 'Link my LoL Account'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="api/update_summoner.php">
                    <div class="mb-3">
                        <label for="summoner_name" class="form-label">Summoner Name</label>
                        <input type="text" class="form-control" id="summoner_name" name="summoner_name" required value="<?php echo htmlspecialchars($userProfile['summoner_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="region" class="form-label">Region</label>
                        <select class="form-control" id="region" name="region">
                            <option value="euw1" <?php echo ($userProfile['region'] ?? '') === 'euw1' ? 'selected' : ''; ?>>Europe West (EUW)</option>
                            <option value="eun1" <?php echo ($userProfile['region'] ?? '') === 'eun1' ? 'selected' : ''; ?>>Europe Nordic & East (EUNE)</option>
                            <option value="na1" <?php echo ($userProfile['region'] ?? '') === 'na1' ? 'selected' : ''; ?>>North America (NA)</option>
                            <option value="kr" <?php echo ($userProfile['region'] ?? '') === 'kr' ? 'selected' : ''; ?>>Korea (KR)</option>
                            <option value="jp1" <?php echo ($userProfile['region'] ?? '') === 'jp1' ? 'selected' : ''; ?>>Japan (JP)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="document.querySelector('#changeSummonerModal form').submit();">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Activate Bootstrap tabs
    document.addEventListener('DOMContentLoaded', function() {
        // Get active tab from URL if present
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        
        if (tab) {
            const tabEl = document.querySelector(`a[href="#${tab}"]`);
            if (tabEl) {
                const tabInstance = new bootstrap.Tab(tabEl);
                tabInstance.show();
            }
        }
        
        // Update URL when a tab is selected
        const tabs = document.querySelectorAll('[data-bs-toggle="list"]');
        tabs.forEach(function(tabEl) {
            tabEl.addEventListener('shown.bs.tab', function(event) {
                const id = event.target.getAttribute('href').substring(1);
                const newUrl = window.location.pathname + '?tab=' + id;
                window.history.replaceState(null, '', newUrl);
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
