<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/riot_api.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Non autorisé');
}

// Vérifier si l'ID du match est présent
if (!isset($_GET['match_id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('ID de match manquant');
}

$matchId = $_GET['match_id'];

// Déterminer la région à partir de l'ID du match
// Format supposé : RÉGION_NUMEROID (ex: EUW_12345678)
list($region, $numericId) = explode('_', $matchId, 2);
$region = strtolower($region);

// Récupérer les détails du match
$matchDetails = getMatchDetails($numericId, $region);

if (!$matchDetails) {
    echo '<div class="alert alert-danger">Impossible de récupérer les détails du match.</div>';
    exit;
}

// Remplacer par des données fictives pour cette démonstration
$matchDetails = [
    'gameId' => $numericId,
    'gameType' => 'MATCHED_GAME',
    'gameMode' => 'CLASSIC',
    'mapId' => 11,
    'gameQueueConfigId' => 420,
    'participants' => [
        // Équipe bleue (100)
        [
            'teamId' => 100,
            'championId' => 7,
            'summonerName' => 'Faker',
            'summonerId' => '12345',
            'spell1Id' => 4,
            'spell2Id' => 12
        ],
        [
            'teamId' => 100,
            'championId' => 11,
            'summonerName' => 'Joueur2',
            'summonerId' => '23456',
            'spell1Id' => 4,
            'spell2Id' => 7
        ],
        [
            'teamId' => 100,
            'championId' => 13,
            'summonerName' => 'Joueur3',
            'summonerId' => '34567',
            'spell1Id' => 4,
            'spell2Id' => 14
        ],
        [
            'teamId' => 100,
            'championId' => 15,
            'summonerName' => 'Joueur4',
            'summonerId' => '45678',
            'spell1Id' => 4,
            'spell2Id' => 6
        ],
        [
            'teamId' => 100,
            'championId' => 2,
            'summonerName' => 'Joueur5',
            'summonerId' => '56789',
            'spell1Id' => 3,
            'spell2Id' => 4
        ],
        // Équipe rouge (200)
        [
            'teamId' => 200,
            'championId' => 8,
            'summonerName' => 'Caps',
            'summonerId' => '67890',
            'spell1Id' => 4,
            'spell2Id' => 12
        ],
        [
            'teamId' => 200,
            'championId' => 5,
            'summonerName' => 'Joueur7',
            'summonerId' => '78901',
            'spell1Id' => 1,
            'spell2Id' => 4
        ],
        [
            'teamId' => 200,
            'championId' => 9,
            'summonerName' => 'Joueur8',
            'summonerId' => '89012',
            'spell1Id' => 4,
            'spell2Id' => 11
        ],
        [
            'teamId' => 200,
            'championId' => 10,
            'summonerName' => 'Joueur9',
            'summonerId' => '90123',
            'spell1Id' => 4,
            'spell2Id' => 7
        ],
        [
            'teamId' => 200,
            'championId' => 12,
            'summonerName' => 'Joueur10',
            'summonerId' => '01234',
            'spell1Id' => 3,
            'spell2Id' => 4
        ]
    ],
    'gameStartTime' => time() - 1200, // Il y a 20 minutes
    'gameLength' => 1200 // 20 minutes
];

// Obtenir le type de file d'attente
$queueType = getQueueTypeById($matchDetails['gameQueueConfigId']);

// Organiser les participants par équipe
$blueTeam = [];
$redTeam = [];

foreach ($matchDetails['participants'] as $participant) {
    $participantData = [
        'championName' => getChampionNameById($participant['championId']),
        'summonerName' => $participant['summonerName'],
    ];
    
    if ($participant['teamId'] === 100) {
        $blueTeam[] = $participantData;
    } else {
        $redTeam[] = $participantData;
    }
}

// Afficher les détails du match
?>

<div class="match-details-container">
    <div class="match-header mb-4">
        <h3><?php echo $queueType; ?></h3>
        <p><strong>Durée de la partie :</strong> <?php echo formatGameTime($matchDetails['gameLength']); ?></p>
    </div>
    
    <div class="row">
        <!-- Équipe bleue -->
        <div class="col-md-6">
            <div class="team-header team-blue p-2 mb-2">
                <h4>Équipe bleue</h4>
            </div>
            <?php foreach ($blueTeam as $player): ?>
                <div class="player-row team-blue">
                    <div class="d-flex align-items-center">
                        <div class="champion-icon-container me-2">
                            <div class="champion-icon-placeholder bg-secondary rounded-circle" style="width:32px;height:32px"></div>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($player['championName']); ?></strong><br>
                            <span><?php echo htmlspecialchars($player['summonerName']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Équipe rouge -->
        <div class="col-md-6">
            <div class="team-header team-red p-2 mb-2">
                <h4>Équipe rouge</h4>
            </div>
            <?php foreach ($redTeam as $player): ?>
                <div class="player-row team-red">
                    <div class="d-flex align-items-center">
                        <div class="champion-icon-container me-2">
                            <div class="champion-icon-placeholder bg-secondary rounded-circle" style="width:32px;height:32px"></div>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($player['championName']); ?></strong><br>
                            <span><?php echo htmlspecialchars($player['summonerName']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="match-footer mt-4">
        <p class="small text-muted">Notez que ces données sont en temps réel et peuvent changer pendant que vous consultez cette page.</p>
    </div>
</div>
