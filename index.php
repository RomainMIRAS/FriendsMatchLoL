<?php
require_once 'includes/header.php';
require_once 'config/config.php';
require_once 'api/riot_api.php';

// Vérifier si l'utilisateur est connecté, sinon le rediriger vers login.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer la liste des amis de l'utilisateur connecté
$friends = getFriendsList($_SESSION['user_id']);
?>

<div class="container mt-5">
    <h1>FriendsMatchLoL</h1>
    <p class="lead">Suivez les matchs en cours de vos amis sur League of Legends</p>

    <!-- Section pour ajouter un ami -->
    <div class="card mb-4">
        <div class="card-header">
            <h2>Ajouter un ami</h2>
        </div>
        <div class="card-body">
            <form action="api/add_friend.php" method="POST">
                <div class="form-group">
                    <label for="summoner_name">Nom d'invocateur :</label>
                    <input type="text" class="form-control" id="summoner_name" name="summoner_name" required>
                </div>
                <div class="form-group mt-2">
                    <label for="region">Région :</label>
                    <select class="form-control" id="region" name="region">
                        <option value="euw1">Europe Ouest (EUW)</option>
                        <option value="eun1">Europe Nord & Est (EUNE)</option>
                        <option value="na1">Amérique du Nord (NA)</option>
                        <option value="kr">Corée (KR)</option>
                        <option value="jp1">Japon (JP)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Ajouter</button>
            </form>
        </div>
    </div>

    <!-- Liste des amis et leurs statuts de match -->
    <div class="card">
        <div class="card-header">
            <h2>Mes amis</h2>
        </div>
        <div class="card-body">
            <?php if (empty($friends)): ?>
                <p>Vous n'avez pas encore ajouté d'amis. Utilisez le formulaire ci-dessus pour commencer à suivre des joueurs.</p>
            <?php else: ?>
                <div class="row" id="friends-list">
                    <?php foreach ($friends as $friend): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 <?php echo $friend['in_game'] ? 'border-success' : 'border-secondary'; ?>">
                                <div class="card-header <?php echo $friend['in_game'] ? 'bg-success text-white' : ''; ?>">
                                    <?php echo htmlspecialchars($friend['summoner_name']); ?>
                                    <span class="badge <?php echo $friend['in_game'] ? 'bg-warning text-dark' : 'bg-secondary'; ?> float-end">
                                        <?php echo $friend['in_game'] ? 'En jeu' : 'Hors ligne'; ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <?php if ($friend['in_game']): ?>
                                        <p><strong>Mode :</strong> <?php echo htmlspecialchars($friend['game_mode']); ?></p>
                                        <p><strong>Champion :</strong> <?php echo htmlspecialchars($friend['champion']); ?></p>
                                        <p><strong>Durée :</strong> <?php echo formatGameTime($friend['game_time']); ?></p>
                                        <a href="#" class="btn btn-sm btn-info match-details" data-match="<?php echo $friend['game_id']; ?>">Détails du match</a>
                                    <?php else: ?>
                                        <p>Pas en jeu actuellement</p>
                                        <p><small>Dernière partie: <?php echo $friend['last_game'] ? htmlspecialchars($friend['last_game']) : 'Inconnue'; ?></small></p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <button class="btn btn-sm btn-danger remove-friend" data-id="<?php echo $friend['id']; ?>">Supprimer</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- Modal pour les détails du match -->
                <div class="modal fade" id="matchDetailsModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Détails du match</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="match-details-content">
                                <!-- Le contenu sera chargé dynamiquement -->
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Rafraîchir automatiquement la liste des amis toutes les 60 secondes
    setInterval(function() {
        $('#friends-list').load('api/refresh_friends.php');
    }, 60000);

    // Afficher les détails du match
    $(document).on('click', '.match-details', function(e) {
        e.preventDefault();
        const matchId = $(this).data('match');
        $('#match-details-content').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Chargement...</span></div></div>');
        $('#matchDetailsModal').modal('show');
        
        $.get('api/get_match_details.php', { match_id: matchId }, function(data) {
            $('#match-details-content').html(data);
        });
    });

    // Supprimer un ami
    $(document).on('click', '.remove-friend', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet ami ?')) {
            const friendId = $(this).data('id');
            $.post('api/remove_friend.php', { friend_id: friendId }, function() {
                location.reload();
            });
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
