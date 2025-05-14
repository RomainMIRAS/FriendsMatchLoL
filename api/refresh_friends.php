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
