<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/riot_api.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Non autorisé');
}

// Récupérer la liste mise à jour des amis
$friends = getFriendsList($_SESSION['user_id']);

// Générer le HTML pour la liste des amis
foreach ($friends as $friend): ?>
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
