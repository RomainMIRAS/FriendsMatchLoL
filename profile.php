<?php
require_once 'includes/header.php';
require_once 'config/config.php';
require_once 'api/riot_api.php';

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Dans un environnement de production, ces données seraient récupérées depuis la base de données
$userProfile = [
    'id' => $userId,
    'username' => $username,
    'email' => 'demo@example.com',
    'created_at' => '2024-01-15',
    'summoner_name' => 'DemoSummoner',
    'region' => 'euw1',
    'friends_count' => 4,
    'notifications_enabled' => true
];

// Traitement des modifications du profil
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $notificationsEnabled = isset($_POST['notifications_enabled']);
    
    // Validation simple
    if (!empty($currentPassword)) {
        // Vérification du mot de passe actuel (simulation)
        if ($currentPassword !== 'demo123') {
            $error = 'Mot de passe actuel incorrect.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Les nouveaux mots de passe ne correspondent pas.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
        } else {
            // Mise à jour du mot de passe (simulation)
            $success = true;
        }
    } else {
        // Mise à jour des paramètres de notification (simulation)
        $userProfile['notifications_enabled'] = $notificationsEnabled;
        $success = true;
    }
}
?>

<div class="container mt-5">
    <div class="row">
        <!-- Menu latéral -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3>Menu</h3>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#profile-info" class="list-group-item list-group-item-action active" data-bs-toggle="list">Informations du profil</a>
                    <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="list">Changer le mot de passe</a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list">Paramètres de notification</a>
                    <a href="#summoner" class="list-group-item list-group-item-action" data-bs-toggle="list">Mon compte League of Legends</a>
                </div>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="col-md-9">
            <?php if ($success): ?>
                <div class="alert alert-success">Les modifications ont été enregistrées avec succès.</div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="tab-content">
                <!-- Informations du profil -->
                <div class="tab-pane fade show active" id="profile-info">
                    <div class="card">
                        <div class="card-header">
                            <h3>Informations du profil</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['username']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($userProfile['email']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date d'inscription</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['created_at']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amis suivis</label>
                                <input type="text" class="form-control" value="<?php echo $userProfile['friends_count']; ?>" disabled>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Changement de mot de passe -->
                <div class="tab-pane fade" id="password">
                    <div class="card">
                        <div class="card-header">
                            <h3>Changer le mot de passe</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="profile.php">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mot de passe actuel</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Paramètres de notification -->
                <div class="tab-pane fade" id="notifications">
                    <div class="card">
                        <div class="card-header">
                            <h3>Paramètres de notification</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="profile.php">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="notifications_enabled" name="notifications_enabled" <?php echo $userProfile['notifications_enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notifications_enabled">Activer les notifications lorsque mes amis commencent une partie</label>
                                </div>
                                <button type="submit" class="btn btn-primary">Enregistrer les paramètres</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Mon compte LoL -->
                <div class="tab-pane fade" id="summoner">
                    <div class="card">
                        <div class="card-header">
                            <h3>Mon compte League of Legends</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($userProfile['summoner_name']): ?>
                                <div class="mb-3">
                                    <label class="form-label">Nom d'invocateur</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['summoner_name']); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Région</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['region']); ?>" disabled>
                                </div>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changeSummonerModal">
                                    Changer de compte LoL
                                </button>
                            <?php else: ?>
                                <p>Vous n'avez pas encore associé votre compte League of Legends.</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changeSummonerModal">
                                    Associer mon compte LoL
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour changer/associer le compte LoL -->
<div class="modal fade" id="changeSummonerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $userProfile['summoner_name'] ? 'Changer de compte LoL' : 'Associer mon compte LoL'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="api/update_summoner.php">
                    <div class="mb-3">
                        <label for="summoner_name" class="form-label">Nom d'invocateur</label>
                        <input type="text" class="form-control" id="summoner_name" name="summoner_name" required value="<?php echo htmlspecialchars($userProfile['summoner_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="region" class="form-label">Région</label>
                        <select class="form-control" id="region" name="region">
                            <option value="euw1" <?php echo ($userProfile['region'] ?? '') === 'euw1' ? 'selected' : ''; ?>>Europe Ouest (EUW)</option>
                            <option value="eun1" <?php echo ($userProfile['region'] ?? '') === 'eun1' ? 'selected' : ''; ?>>Europe Nord & Est (EUNE)</option>
                            <option value="na1" <?php echo ($userProfile['region'] ?? '') === 'na1' ? 'selected' : ''; ?>>Amérique du Nord (NA)</option>
                            <option value="kr" <?php echo ($userProfile['region'] ?? '') === 'kr' ? 'selected' : ''; ?>>Corée (KR)</option>
                            <option value="jp1" <?php echo ($userProfile['region'] ?? '') === 'jp1' ? 'selected' : ''; ?>>Japon (JP)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="document.querySelector('#changeSummonerModal form').submit();">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Activer les onglets Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        // Récupérer l'onglet actif depuis l'URL si présent
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        
        if (tab) {
            const tabEl = document.querySelector(`a[href="#${tab}"]`);
            if (tabEl) {
                const tabInstance = new bootstrap.Tab(tabEl);
                tabInstance.show();
            }
        }
        
        // Mettre à jour l'URL lorsqu'un onglet est sélectionné
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
