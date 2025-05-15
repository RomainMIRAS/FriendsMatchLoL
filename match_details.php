<?php
require_once 'includes/header.php';
require_once 'config/config.php';
require_once 'api/riot_api.php';
require_once 'includes/utils.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier si les paramètres sont spécifiés
if (!isset($_GET['match_id']) || !isset($_GET['region'])) {
    header('Location: index.php');
    exit;
}

$matchId = $_GET['match_id'];
$region = $_GET['region'];

// Récupérer les détails du match
$matchDetails = getMatchDetailsById($matchId, $region);

if (!$matchDetails) {
    // Si les détails du match ne sont pas disponibles, afficher un message d'erreur
    $errorMessage = "Impossible de récupérer les détails du match.";
}
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Détails du match</h1>
        <a href="javascript:history.back()" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger">
            <?php echo $errorMessage; ?>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h4 class="m-0"><?php echo getQueueTypeName($matchDetails['info']['queueId']); ?></h4>
                    </div>
                    <div class="col-md-4 text-center">
                        <span class="badge bg-light text-dark">
                            <?php echo date('d/m/Y H:i', $matchDetails['info']['gameCreation'] / 1000); ?>
                        </span>
                        <span class="badge bg-light text-dark ms-2">
                            <?php echo formatGameDuration($matchDetails['info']['gameDuration']); ?>
                        </span>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-primary">
                            Patch <?php echo $matchDetails['info']['gameVersion']; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100 <?php echo $matchDetails['info']['teams'][0]['win'] ? 'border-success' : 'border-danger'; ?>">
                            <div class="card-header <?php echo $matchDetails['info']['teams'][0]['win'] ? 'bg-success' : 'bg-danger'; ?> text-white">
                                <h5 class="m-0">Équipe Bleue - <?php echo $matchDetails['info']['teams'][0]['win'] ? 'Victoire' : 'Défaite'; ?></h5>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Joueur</th>
                                            <th>Champion</th>
                                            <th>K/D/A</th>
                                            <th>CS</th>
                                            <th>Items</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($matchDetails['info']['participants'] as $participant): ?>
                                            <?php if ($participant['teamId'] == 100): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($participant['summonerName']); ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="https://ddragon.leagueoflegends.com/cdn/13.1.1/img/champion/<?php echo str_replace(' ', '', $participant['championName']); ?>.png" 
                                                                 alt="<?php echo htmlspecialchars($participant['championName']); ?>" 
                                                                 class="champion-icon me-2" 
                                                                 width="32" height="32">
                                                            <?php echo htmlspecialchars($participant['championName']); ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $participant['kills'] . '/' . $participant['deaths'] . '/' . $participant['assists']; ?></td>
                                                    <td>
                                                        <?php 
                                                        $cs = $participant['totalMinionsKilled'] + (isset($participant['neutralMinionsKilled']) ? $participant['neutralMinionsKilled'] : 0);
                                                        echo $cs;
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex">
                                                            <?php foreach ([$participant['item0'], $participant['item1'], $participant['item2'], $participant['item3'], $participant['item4'], $participant['item5'], $participant['item6']] as $item): ?>
                                                                <?php if ($item > 0): ?>
                                                                    <div class="me-1">
                                                                        <img src="https://ddragon.leagueoflegends.com/cdn/13.1.1/img/item/<?php echo $item; ?>.png" 
                                                                             width="24" height="24" alt="Item <?php echo $item; ?>"
                                                                             class="border" data-bs-toggle="tooltip" title="Item <?php echo $item; ?>">
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card h-100 <?php echo $matchDetails['info']['teams'][1]['win'] ? 'border-success' : 'border-danger'; ?>">
                            <div class="card-header <?php echo $matchDetails['info']['teams'][1]['win'] ? 'bg-success' : 'bg-danger'; ?> text-white">
                                <h5 class="m-0">Équipe Rouge - <?php echo $matchDetails['info']['teams'][1]['win'] ? 'Victoire' : 'Défaite'; ?></h5>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Joueur</th>
                                            <th>Champion</th>
                                            <th>K/D/A</th>
                                            <th>CS</th>
                                            <th>Items</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($matchDetails['info']['participants'] as $participant): ?>
                                            <?php if ($participant['teamId'] == 200): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($participant['summonerName']); ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="https://ddragon.leagueoflegends.com/cdn/13.1.1/img/champion/<?php echo str_replace(' ', '', $participant['championName']); ?>.png" 
                                                                 alt="<?php echo htmlspecialchars($participant['championName']); ?>" 
                                                                 class="champion-icon me-2" 
                                                                 width="32" height="32">
                                                            <?php echo htmlspecialchars($participant['championName']); ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $participant['kills'] . '/' . $participant['deaths'] . '/' . $participant['assists']; ?></td>
                                                    <td>
                                                        <?php 
                                                        $cs = $participant['totalMinionsKilled'] + (isset($participant['neutralMinionsKilled']) ? $participant['neutralMinionsKilled'] : 0);
                                                        echo $cs;
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex">
                                                            <?php foreach ([$participant['item0'], $participant['item1'], $participant['item2'], $participant['item3'], $participant['item4'], $participant['item5'], $participant['item6']] as $item): ?>
                                                                <?php if ($item > 0): ?>
                                                                    <div class="me-1">
                                                                        <img src="https://ddragon.leagueoflegends.com/cdn/13.1.1/img/item/<?php echo $item; ?>.png" 
                                                                             width="24" height="24" alt="Item <?php echo $item; ?>"
                                                                             class="border" data-bs-toggle="tooltip" title="Item <?php echo $item; ?>">
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>Statistiques de l'équipe bleue</h5>
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Tours détruites</td>
                                    <td><?php echo $matchDetails['info']['teams'][0]['objectives']['tower']['kills']; ?></td>
                                </tr>
                                <tr>
                                    <td>Inhibiteurs détruits</td>
                                    <td><?php echo $matchDetails['info']['teams'][0]['objectives']['inhibitor']['kills']; ?></td>
                                </tr>
                                <tr>
                                    <td>Barons tués</td>
                                    <td><?php echo $matchDetails['info']['teams'][0]['objectives']['baron']['kills']; ?></td>
                                </tr>
                                <tr>
                                    <td>Dragons tués</td>
                                    <td><?php echo $matchDetails['info']['teams'][0]['objectives']['dragon']['kills']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Statistiques de l'équipe rouge</h5>
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Tours détruites</td>
                                    <td><?php echo $matchDetails['info']['teams'][1]['objectives']['tower']['kills']; ?></td>
                                </tr>
                                <tr>
                                    <td>Inhibiteurs détruits</td>
                                    <td><?php echo $matchDetails['info']['teams'][1]['objectives']['inhibitor']['kills']; ?></td>
                                </tr>
                                <tr>
                                    <td>Barons tués</td>
                                    <td><?php echo $matchDetails['info']['teams'][1]['objectives']['baron']['kills']; ?></td>
                                </tr>
                                <tr>
                                    <td>Dragons tués</td>
                                    <td><?php echo $matchDetails['info']['teams'][1]['objectives']['dragon']['kills']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
