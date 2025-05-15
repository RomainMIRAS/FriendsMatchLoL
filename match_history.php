<?php
require_once 'includes/header.php';
require_once 'config/config.php';
require_once 'api/riot_api.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if a friend has been specified
$friendId = isset($_GET['friend_id']) ? (int)$_GET['friend_id'] : null;

// If no friend is specified, redirect to the home page
if (!$friendId) {
    header('Location: index.php');
    exit;
}

// Charger les utilitaires
require_once 'includes/utils.php';

// Get friend's information
$friends = getFriendsList($_SESSION['user_id']);
$currentFriend = null;

foreach ($friends as $friend) {
    if ($friend['id'] === $friendId) {
        $currentFriend = $friend;
        break;
    }
}

// If the friend doesn't exist, redirect to the home page
if (!$currentFriend) {
    header('Location: index.php?error=friend_not_found');
    exit;
}

// Get friend's match history using Riot API
$page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
$matchesPerPage = 10;
$start = $page * $matchesPerPage;

// Récupérer l'historique des matchs depuis l'API Riot
$matchIds = getMatchHistory($currentFriend['puuid'], $currentFriend['region'], $matchesPerPage, $start);
$matchHistory = [];

if ($matchIds) {
    foreach ($matchIds as $matchId) {
        $matchDetails = getMatchDetails($matchId, $currentFriend['region']);
        if ($matchDetails) {
            // Trouver les données du joueur dans le match
            $participantIndex = -1;
            foreach ($matchDetails['info']['participants'] as $index => $participant) {
                if ($participant['puuid'] === $currentFriend['puuid']) {
                    $participantIndex = $index;
                    break;
                }
            }

            if ($participantIndex >= 0) {
                $playerData = $matchDetails['info']['participants'][$participantIndex];
                $teamId = $playerData['teamId'];
                $win = $playerData['win'];

                // Formatage des données pour l'affichage
                $kda = $playerData['kills'] . '/' . $playerData['deaths'] . '/' . $playerData['assists'];
                $cs = $playerData['totalMinionsKilled'] + (isset($playerData['neutralMinionsKilled']) ? $playerData['neutralMinionsKilled'] : 0);
                $duration = floor($matchDetails['info']['gameDuration'] / 60) . ':' . str_pad($matchDetails['info']['gameDuration'] % 60, 2, '0', STR_PAD_LEFT);
                $date = humanTimeDiff($matchDetails['info']['gameCreation'] / 1000);
                $queueType = getQueueTypeName($matchDetails['info']['queueId']);

                $matchHistory[] = [
                    'game_id' => $matchId,
                    'champion' => $playerData['championName'],
                    'result' => $win ? 'Victory' : 'Defeat',
                    'kda' => $kda,
                    'cs' => $cs,
                    'duration' => $duration,
                    'date' => $date,
                    'queue_type' => $queueType,
                    'champion_id' => $playerData['championId'],
                    'items' => [
                        $playerData['item0'], $playerData['item1'], $playerData['item2'],
                        $playerData['item3'], $playerData['item4'], $playerData['item5'], $playerData['item6']
                    ],
                    'win' => $win
                ];
            }
        }
    }
}

// Si aucun match n'a été trouvé via l'API, on ajoute des données de démonstration
if (empty($matchHistory)) {
    $matchHistory = [
        [
            'game_id' => 'EUW_87654321',
            'champion' => 'Syndra',
            'result' => 'Defeat',
            'kda' => '3/5/4',
            'cs' => 156,
            'duration' => '32:12',
            'date' => '4 hours ago',
            'queue_type' => 'Normal Draft',
            'win' => false,
            'items' => [3020, 3157, 3089, 3135, 3165, 3116, 3363]
        ],
        [
            'game_id' => 'EUW_24681357',
            'champion' => 'Ahri',
            'result' => 'Victory',
            'kda' => '12/3/8',
            'cs' => 201,
            'duration' => '25:30',
            'date' => '7 hours ago',
            'queue_type' => 'Ranked Solo/Duo',
            'win' => true,
            'items' => [3020, 3165, 3089, 3152, 3102, 3135, 3363]
        ],
        [
            'game_id' => 'EUW_97531642',
            'champion' => 'Zed',
            'result' => 'Victory',
            'kda' => '8/1/3',
            'cs' => 178,
            'duration' => '22:18',
            'date' => '1 day ago',
            'queue_type' => 'ARAM',
            'win' => true,
            'items' => [3142, 3814, 3074, 3156, 3071, 3047, 3364]
        ],
        [
            'game_id' => 'EUW_76543219',
            'champion' => 'Orianna',
            'result' => 'Defeat',
            'kda' => '2/7/9',
            'cs' => 145,
            'duration' => '36:05',
            'date' => '1 day ago',
            'queue_type' => 'Ranked Solo/Duo',
            'win' => false,
            'items' => [3020, 3157, 3089, 3135, 3115, 3151, 3363]
        ]
    ];
}
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Historique des matchs de <?php echo htmlspecialchars($currentFriend['summoner_name']); ?></h1>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
            <li class="breadcrumb-item active" aria-current="page">Historique de <?php echo htmlspecialchars($currentFriend['summoner_name']); ?></li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Summoner Profile</h2>
                <a href="index.php" class="btn btn-secondary">Back to Friends List</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <div class="bg-secondary rounded-circle mb-2 mx-auto d-flex align-items-center justify-content-center" style="width:100px;height:100px">
                        <span class="text-white" style="font-size:24px"><?php echo substr($currentFriend['summoner_name'], 0, 1); ?></span>
                    </div>
                    <h4><?php echo htmlspecialchars($currentFriend['summoner_name']); ?></h4>
                    <p class="text-muted">Region: <?php echo strtoupper($currentFriend['region']); ?></p>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>Tracked Games</h5>
                                    <h3>23</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>Victories</h5>
                                    <h3>14</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5>Defeats</h5>
                                    <h3>9</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h5>Most Played Champions</h5>
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
            <h2>Recent Games</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Queue</th>
                            <th>Champion</th>
                            <th>K/D/A</th>
                            <th>CS</th>
                            <th>Duration</th>
                            <th>Result</th>
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
                                    <span class="badge <?php echo $match['result'] === 'Victory' ? 'bg-success' : 'bg-danger'; ?>"></span>
                                        <?php echo htmlspecialchars($match['result']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info match-details" data-match="<?php echo $match['game_id']; ?>" data-bs-toggle="modal" data-bs-target="#matchDetailsModal">
                                        Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Match history pagination"></nav>
                    <ul class="pagination">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Match details modal -->
<div class="modal fade" id="matchDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Match Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="match-details-content">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading match details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Display match details
        const matchDetailsButtons = document.querySelectorAll('.match-details');
        matchDetailsButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const matchId = this.getAttribute('data-match');
                
                // Show loading spinner
                document.getElementById('match-details-content').innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Loading match details...</p>
                    </div>
                `;
                
                // In a real implementation, you would make an AJAX request
                // For this demo, we simulate a loading delay
                setTimeout(function() {
                    fetch('api/get_match_details.php?match_id=' + matchId)
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('match-details-content').innerHTML = html;
                        })
                        .catch(error => {
                            document.getElementById('match-details-content').innerHTML = `
                                <div class="alert alert-danger">
                                    An error occurred while loading match details.
                                </div>
                            `;
                        });
                }, 1000);
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
