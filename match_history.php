<?php
require_once 'includes/header.php';
require_once 'config/config.php';
require_once 'api/riot_api.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier si un ami a été spécifié
$friendId = isset($_GET['friend_id']) ? (int)$_GET['friend_id'] : null;

// Si aucun ami n'est spécifié, rediriger vers la page d'accueil
if (!$friendId) {
    header('Location: index.php');
    exit;
}

// Récupérer les informations de l'ami
$friends = getFriendsList($_SESSION['user_id']);
$currentFriend = null;

foreach ($friends as $friend) {
    if ($friend['id'] === $friendId) {
        $currentFriend = $friend;
        break;
    }
}

// Si l'ami n'existe pas, rediriger vers la page d'accueil
if (!$currentFriend) {
    header('Location: index.php?error=friend_not_found');
    exit;
}

// Récupérer l'historique des parties de l'ami
// Dans une implémentation réelle, vous utiliseriez l'API Riot pour récupérer ces données
$matchHistory = [
    [
        'game_id' => 'EUW_12345678',
        'champion' => 'LeBlanc',
        'result' => 'Victoire',
        'kda' => '7/2/10',
        'cs' => 186,
        'duration' => '28:45',
        'date' => 'Il y a 2 heures',
        'queue_type' => 'Classé Solo/Duo'
    ],
    [
        'game_id' => 'EUW_87654321',
        'champion' => 'Syndra',
        'result' => 'Défaite',
        'kda' => '3/5/4',
        'cs' => 156,
        'duration' => '32:12',
        'date' => 'Il y a 4 heures',
        'queue_type' => 'Normal Draft'
    ],
    [
        'game_id' => 'EUW_24681357',
        'champion' => 'Ahri',
        'result' => 'Victoire',
        'kda' => '12/3/8',
        'cs' => 201,
        'duration' => '25:30',
        'date' => 'Il y a 7 heures',
        'queue_type' => 'Classé Solo/Duo'
    ],
    [
        'game_id' => 'EUW_97531642',
        'champion' => 'Zed',
        'result' => 'Victoire',
        'kda' => '8/1/3',
        'cs' => 178,
        'duration' => '22:18',
        'date' => 'Il y a 1 jour',
        'queue_type' => 'ARAM'
    ],
    [
        'game_id' => 'EUW_76543219',
        'champion' => 'Orianna',
        'result' => 'Défaite',
        'kda' => '2/7/9',
        'cs' => 145,
        'duration' => '36:05',
        'date' => 'Il y a 1 jour',
        'queue_type' => 'Classé Solo/Duo'
    ]
];
?>

<div class="container mt-5">
    <h1>Historique des matchs de <?php echo htmlspecialchars($currentFriend['summoner_name']); ?></h1>
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
            <li class="breadcrumb-item active" aria-current="page">Historique de <?php echo htmlspecialchars($currentFriend['summoner_name']); ?></li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Profil d'invocateur</h2>
                <a href="index.php" class="btn btn-secondary">Retour à la liste des amis</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <div class="bg-secondary rounded-circle mb-2 mx-auto d-flex align-items-center justify-content-center" style="width:100px;height:100px">
                        <span class="text-white" style="font-size:24px"><?php echo substr($currentFriend['summoner_name'], 0, 1); ?></span>
                    </div>
                    <h4><?php echo htmlspecialchars($currentFriend['summoner_name']); ?></h4>
                    <p class="text-muted">Région: <?php echo strtoupper($currentFriend['region']); ?></p>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>Parties suivies</h5>
                                    <h3>23</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>Victoires</h5>
                                    <h3>14</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5>Défaites</h5>
                                    <h3>9</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h5>Champions les plus joués</h5>
                        <div class="d-flex">
                            <div class="me-3 text-center">
                                <div class="bg-dark rounded-circle mb-1 d-flex align-items-center justify-content-center" style="width:50px;height:50px">
                                    <span class="text-white">LB</span>
                                </div>
                                <small>LeBlanc</small>
                            </div>
                            <div class="me-3 text-center">
                                <div class="bg-dark rounded-circle mb-1 d-flex align-items-center justify-content-center" style="width:50px;height:50px">
                                    <span class="text-white">SY</span>
                                </div>
                                <small>Syndra</small>
                            </div>
                            <div class="me-3 text-center">
                                <div class="bg-dark rounded-circle mb-1 d-flex align-items-center justify-content-center" style="width:50px;height:50px">
                                    <span class="text-white">AH</span>
                                </div>
                                <small>Ahri</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Parties récentes</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>File d'attente</th>
                            <th>Champion</th>
                            <th>K/D/A</th>
                            <th>CS</th>
                            <th>Durée</th>
                            <th>Résultat</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matchHistory as $match): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($match['date']); ?></td>
                                <td><?php echo htmlspecialchars($match['queue_type']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-dark rounded-circle me-2 d-flex align-items-center justify-content-center" style="width:30px;height:30px">
                                            <span class="text-white" style="font-size:10px"><?php echo substr($match['champion'], 0, 2); ?></span>
                                        </div>
                                        <?php echo htmlspecialchars($match['champion']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($match['kda']); ?></td>
                                <td><?php echo $match['cs']; ?></td>
                                <td><?php echo htmlspecialchars($match['duration']); ?></td>
                                <td>
                                    <span class="badge <?php echo $match['result'] === 'Victoire' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo htmlspecialchars($match['result']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info match-details" data-match="<?php echo $match['game_id']; ?>" data-bs-toggle="modal" data-bs-target="#matchDetailsModal">
                                        Détails
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Pagination de l'historique des matchs">
                    <ul class="pagination">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Précédent</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Suivant</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
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
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p>Chargement des détails du match...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Afficher les détails du match
        const matchDetailsButtons = document.querySelectorAll('.match-details');
        matchDetailsButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const matchId = this.getAttribute('data-match');
                
                // Afficher le spinner de chargement
                document.getElementById('match-details-content').innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p>Chargement des détails du match...</p>
                    </div>
                `;
                
                // Dans une implémentation réelle, vous feriez une requête AJAX
                // Pour cette démonstration, on simule un délai de chargement
                setTimeout(function() {
                    fetch('api/get_match_details.php?match_id=' + matchId)
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('match-details-content').innerHTML = html;
                        })
                        .catch(error => {
                            document.getElementById('match-details-content').innerHTML = `
                                <div class="alert alert-danger">
                                    Une erreur s'est produite lors du chargement des détails du match.
                                </div>
                            `;
                        });
                }, 1000);
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
