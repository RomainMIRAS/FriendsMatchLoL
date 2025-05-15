<?php
require_once 'includes/header.php';
require_once 'config/config.php';
require_once 'api/riot_api.php';
require_once 'includes/utils.php';

// Vérifier si l'utilisateur est connecté, sinon rediriger vers login.php
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupérer la liste des amis de l'utilisateur connecté
$friends = getFriendsList($_SESSION['user_id']);

// Gestion des messages d'erreur et de succès
$errorMessages = [
    'empty_riot_id' => 'Veuillez saisir un Riot ID valide (Nom#TAG).',
    'invalid_region' => 'La région sélectionnée n\'est pas valide.',
    'account_not_found' => 'Ce compte Riot n\'a pas été trouvé. Vérifiez le Riot ID saisi.',
    'summoner_not_found' => 'Ce joueur n\'a pas été trouvé sur League of Legends dans la région sélectionnée.',
    'friend_exists' => 'Ce joueur est déjà dans votre liste d\'amis.',
    'database_error' => 'Une erreur est survenue lors de l\'ajout de l\'ami. Veuillez réessayer.',
    'incomplete_data' => 'Veuillez remplir tous les champs du formulaire.'
];

$error = isset($_GET['error']) && isset($errorMessages[$_GET['error']]) 
    ? $errorMessages[$_GET['error']] : null;

$success = isset($_GET['success']) && $_GET['success'] == 'friend_added' 
    ? 'L\'ami a été ajouté avec succès à votre liste.' : null;
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-gamepad me-2"></i>FriendsMatchLoL</h1>
            <p class="lead">Suivez les matchs en cours de vos amis sur League of Legends</p>
        </div>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    <?php endif; ?>

    <!-- Section pour ajouter un ami -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2><i class="fas fa-user-plus me-2"></i>Ajouter un ami</h2>
        </div>
        <div class="card-body">
            <form action="api/add_friend.php" method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="riot_id" class="form-label">Riot ID:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="game_name" name="game_name" placeholder="Pseudo" required>
                            <span class="input-group-text">#</span>
                            <input type="text" class="form-control" id="tag_line" name="tag_line" placeholder="TAG" required>
                        </div>
                        <div class="form-text">Format: NomDeJeu#TAG (ex: Faker#KR1)</div>
                    </div>
                    <div class="col-md-4">
                        <label for="region" class="form-label">Région:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-globe"></i></span>
                            <select class="form-select" id="region" name="region">
                                <option value="europe">Europe (EUW/EUNE/TR/RU)</option>
                                <option value="asia">Asie (KR/JP)</option>
                                <option value="americas">Amériques (NA/BR/LAN/LAS)</option>
                                <option value="sea">Océanie</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle me-1"></i>Ajouter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des amis et leur statut de match -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h2><i class="fas fa-users me-2"></i>Mes amis</h2>
        </div>
        <div class="card-body">
            <?php if (empty($friends)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Vous n'avez pas encore ajouté d'amis. Utilisez le formulaire ci-dessus pour commencer à suivre des joueurs.
                </div>
            <?php else: ?>
                <div class="row" id="friends-list">
                    <?php foreach ($friends as $friend): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 <?php echo $friend['in_game'] ? 'border-success' : 'border-secondary'; ?>">
                                <div class="card-header <?php echo $friend['in_game'] ? 'bg-success text-white' : ''; ?>">
                                    <i class="fas <?php echo $friend['in_game'] ? 'fa-gamepad' : 'fa-user'; ?> me-2"></i>
                                    <?php echo htmlspecialchars($friend['summoner_name']); ?>
                                    <span class="badge <?php echo $friend['in_game'] ? 'bg-warning text-dark' : 'bg-secondary'; ?> float-end">
                                        <?php echo $friend['in_game'] ? 'En jeu' : 'Hors ligne'; ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($friend['riot_id'])): ?>
                                        <p><small><i class="fas fa-id-card me-1"></i> <strong>Riot ID:</strong> <?php echo htmlspecialchars($friend['riot_id']); ?></small></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($friend['in_game']): ?>
                                        <p><strong><i class="fas fa-gamepad me-1"></i>Mode:</strong> <?php echo htmlspecialchars($friend['game_mode']); ?></p>
                                        <p><strong><i class="fas fa-user-astronaut me-1"></i>Champion:</strong> <?php echo htmlspecialchars($friend['champion']); ?></p>
                                        <p><strong><i class="fas fa-clock me-1"></i>Durée:</strong> <?php echo formatGameTime($friend['game_time']); ?></p>
                                        <a href="#" class="btn btn-sm btn-info match-details" data-match="<?php echo $friend['game_id']; ?>">
                                            <i class="fas fa-search me-1"></i>Détails du match
                                        </a>
                                    <?php else: ?>
                                        <p class="text-muted"><i class="fas fa-times-circle me-1"></i>Pas en jeu actuellement</p>
                                        <p class="small text-muted"><i class="fas fa-history me-1"></i>Dernier match: <?php echo $friend['last_game'] ? htmlspecialchars($friend['last_game']) : 'Inconnu'; ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between">
                                        <a href="match_history.php?summoner=<?php echo urlencode($friend['summoner_name']); ?>&region=<?php echo $friend['region']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-history me-1"></i>Historique
                                        </a>
                                        <button class="btn btn-sm btn-danger remove-friend" data-id="<?php echo $friend['id']; ?>">
                                            <i class="fas fa-trash me-1"></i>Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- Modal pour les détails du match -->
                <div class="modal fade" id="matchDetailsModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title"><i class="fas fa-gamepad me-2"></i>Détails du match</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="match-details-content">
                                <!-- Le contenu sera chargé dynamiquement -->
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Chargement...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Actualiser automatiquement la liste des amis toutes les 60 secondes
    const refreshInterval = <?php echo REFRESH_RATE * 1000; ?>;
    setInterval(function() {
        $('#friends-list').load('api/refresh_friends.php');
        console.log('Liste des amis actualisée à ' + new Date().toLocaleTimeString());
    }, refreshInterval);

    // Afficher les détails du match
    $(document).on('click', '.match-details', function(e) {
        e.preventDefault();
        const matchId = $(this).data('match');
        $('#match-details-content').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>');
        $('#matchDetailsModal').modal('show');
        
        $.get('api/get_match_details.php', { match_id: matchId }, function(data) {
            $('#match-details-content').html(data);
        }).fail(function() {
            $('#match-details-content').html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Erreur lors du chargement des détails du match. Veuillez réessayer.</div>');
        });
    });

    // Supprimer un ami
    $(document).on('click', '.remove-friend', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet ami de votre liste ?')) {
            const friendId = $(this).data('id');
            const button = $(this);
            
            // Désactiver le bouton pendant la suppression
            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            
            $.post('api/remove_friend.php', { friend_id: friendId }, function(response) {
                if (response.success) {
                    button.closest('.col-md-4').fadeOut(300, function() {
                        $(this).remove();
                        
                        // Si plus d'amis, afficher le message
                        if ($('#friends-list').children().length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert('Erreur lors de la suppression: ' + response.message);
                    button.prop('disabled', false).html('<i class="fas fa-trash me-1"></i>Supprimer');
                }
            }, 'json').fail(function() {
                alert('Erreur de communication avec le serveur');
                button.prop('disabled', false).html('<i class="fas fa-trash me-1"></i>Supprimer');
            });
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
