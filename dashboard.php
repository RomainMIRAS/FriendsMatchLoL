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
$stats = [];

try {
    // Connexion à la base de données
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Total des amis suivis
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM friends WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_friends'] = $result['count'];
    
    // 2. Nombre d'amis actuellement en jeu
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM friends f 
        JOIN active_games g ON f.summoner_id = g.summoner_id AND f.region = g.region
        WHERE f.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['friends_in_game'] = $result['count'];
    
    // 3. Total des matchs suivis (historique)
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT match_id) as count 
        FROM match_history 
        WHERE summoner_id IN (
            SELECT summoner_id FROM friends WHERE user_id = :user_id
        )
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_matches'] = $result['count'] ?? 0;
    
    // 4. Champions les plus joués par les amis
    $stmt = $db->prepare("
        SELECT champion_name, COUNT(*) as count
        FROM match_history
        WHERE summoner_id IN (
            SELECT summoner_id FROM friends WHERE user_id = :user_id
        )
        GROUP BY champion_name
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $stats['top_champions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Modes de jeu les plus joués
    $stmt = $db->prepare("
        SELECT game_mode, COUNT(*) as count
        FROM match_history
        WHERE summoner_id IN (
            SELECT summoner_id FROM friends WHERE user_id = :user_id
        )
        GROUP BY game_mode
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $stats['top_game_modes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Erreur de base de données: ' . $e->getMessage();
}
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-chart-pie me-2"></i>Tableau de Bord</h1>
            <p class="lead">Statistiques globales de vos amis et leurs parties</p>
        </div>
    </div>
    
    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center h-100 bg-light">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Amis suivis</h5>
                    <h1 class="display-4"><?php echo $stats['total_friends']; ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100 bg-light">
                <div class="card-body">
                    <i class="fas fa-gamepad fa-3x text-success mb-3"></i>
                    <h5 class="card-title">En jeu actuellement</h5>
                    <h1 class="display-4"><?php echo $stats['friends_in_game']; ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100 bg-light">
                <div class="card-body">
                    <i class="fas fa-history fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Matchs suivis</h5>
                    <h1 class="display-4"><?php echo $stats['total_matches']; ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100 bg-light">
                <div class="card-body">
                    <i class="fas fa-percentage fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">% En jeu</h5>
                    <h1 class="display-4">
                        <?php 
                        echo $stats['total_friends'] > 0 
                             ? round(($stats['friends_in_game'] / $stats['total_friends']) * 100) 
                             : 0; 
                        ?>%
                    </h1>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Graphiques et statistiques détaillées -->
    <div class="row">
        <!-- Champions les plus joués -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2><i class="fas fa-trophy me-2"></i>Top Champions</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($stats['top_champions'])): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Pas encore assez de données pour afficher les champions les plus joués.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Champion</th>
                                        <th>Nombre de parties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 1; foreach ($stats['top_champions'] as $champion): ?>
                                        <tr>
                                            <td><?php echo $i++; ?></td>
                                            <td><?php echo htmlspecialchars($champion['champion_name']); ?></td>
                                            <td><?php echo $champion['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Modes de jeu les plus joués -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2><i class="fas fa-gamepad me-2"></i>Modes de jeu populaires</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($stats['top_game_modes'])): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Pas encore assez de données pour afficher les modes de jeu populaires.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Mode de jeu</th>
                                        <th>Nombre de parties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 1; foreach ($stats['top_game_modes'] as $mode): ?>
                                        <tr>
                                            <td><?php echo $i++; ?></td>
                                            <td><?php echo htmlspecialchars($mode['game_mode']); ?></td>
                                            <td><?php echo $mode['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Boutons d'action -->
    <div class="row mb-4">
        <div class="col text-center">
            <a href="index.php" class="btn btn-primary me-2">
                <i class="fas fa-users me-2"></i>Voir mes amis
            </a>
            <a href="profile.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-user-cog me-2"></i>Gérer mon profil
            </a>
            <button id="refresh-stats" class="btn btn-success">
                <i class="fas fa-sync-alt me-2"></i>Actualiser les statistiques
            </button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Bouton d'actualisation des statistiques
    $('#refresh-stats').click(function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Actualisation...');
        
        // Recharger la page pour actualiser les statistiques
        setTimeout(function() {
            window.location.reload();
        }, 1000);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
