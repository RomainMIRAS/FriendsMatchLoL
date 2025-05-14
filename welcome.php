<?php
require_once 'includes/header.php';

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>

<div class="container mt-5">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="display-4 mb-4">FriendsMatchLoL</h1>
            <h2 class="mb-4">Suivez les parties de vos amis sur League of Legends</h2>
            <p class="lead">
                FriendsMatchLoL vous permet de suivre en temps réel les parties de vos amis sur League of Legends.
                Recevez des notifications quand ils commencent une partie, découvrez les champions qu'ils jouent
                et consultez les détails des matchs en cours.
            </p>
            <div class="mt-5">
                <a href="register.php" class="btn btn-primary btn-lg me-3">S'inscrire gratuitement</a>
                <a href="login.php" class="btn btn-outline-secondary btn-lg">Se connecter</a>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-5">
                    <img src="https://ddragon.leagueoflegends.com/cdn/img/champion/splash/Lux_0.jpg" alt="League of Legends" class="img-fluid rounded mb-4" style="max-height: 300px; object-fit: cover;">
                    <h3>Fonctionnalités principales</h3>
                    <ul class="list-group list-group-flush text-start">
                        <li class="list-group-item border-0">
                            <i class="fas fa-check-circle text-success me-2"></i> Suivi en temps réel des parties de vos amis
                        </li>
                        <li class="list-group-item border-0">
                            <i class="fas fa-check-circle text-success me-2"></i> Détails complets des matchs en cours
                        </li>
                        <li class="list-group-item border-0">
                            <i class="fas fa-check-circle text-success me-2"></i> Notifications de début de partie
                        </li>
                        <li class="list-group-item border-0">
                            <i class="fas fa-check-circle text-success me-2"></i> Historique des parties précédentes
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5 pt-5">
        <div class="col-12 text-center">
            <h2 class="mb-5">Comment ça marche</h2>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width:80px;height:80px">
                        <i class="fas fa-user-plus fa-2x"></i>
                    </div>
                    <h4>1. Ajoutez vos amis</h4>
                    <p>Ajoutez les noms d'invocateur de vos amis de League of Legends pour commencer à les suivre.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width:80px;height:80px">
                        <i class="fas fa-desktop fa-2x"></i>
                    </div>
                    <h4>2. Suivez leurs parties</h4>
                    <p>Recevez des notifications en temps réel quand vos amis commencent une partie et consultez les détails de leurs matchs.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width:80px;height:80px">
                        <i class="fas fa-history fa-2x"></i>
                    </div>
                    <h4>3. Consultez l'historique</h4>
                    <p>Accédez à l'historique complet des parties précédentes de vos amis avec des statistiques détaillées.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5 pt-5">
        <div class="col-md-6">
            <h2>Témoignages</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px">
                                <span class="text-white">JP</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">Jean Pierre</h5>
                            <p class="small text-muted mb-2">Joueur depuis 2015</p>
                            <p>Grâce à FriendsMatchLoL, je peux facilement savoir quand mes amis sont en partie sans avoir à lancer le jeu. Une application super utile !</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px">
                                <span class="text-white">MD</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">Marie Dupont</h5>
                            <p class="small text-muted mb-2">Joueuse depuis 2018</p>
                            <p>J'adore recevoir des notifications quand mes amis commencent une partie. Ça m'aide à rester connectée avec ma team même quand je ne joue pas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <h2>FAQ</h2>
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            L'application est-elle gratuite ?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Oui, FriendsMatchLoL est entièrement gratuit. Il vous suffit de créer un compte pour commencer à suivre vos amis.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Comment ajouter des amis à suivre ?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Après vous être connecté, utilisez le formulaire "Ajouter un ami" sur la page d'accueil. Entrez le nom d'invocateur de votre ami et sa région, puis cliquez sur "Ajouter".
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Est-ce que l'application fonctionne pour toutes les régions ?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Oui, FriendsMatchLoL prend en charge toutes les régions officielles de League of Legends, notamment EUW, EUNE, NA, KR, JP, etc.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            Les données sont-elles mises à jour en temps réel ?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Oui, les données sont automatiquement rafraîchies toutes les 60 secondes pour vous fournir les informations les plus récentes sur les parties de vos amis.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5 pt-5 text-center">
        <div class="col-12">
            <h2>Prêt à commencer ?</h2>
            <p class="lead mb-4">Créez votre compte gratuitement et commencez à suivre vos amis dès maintenant !</p>
            <a href="register.php" class="btn btn-primary btn-lg me-3">S'inscrire</a>
            <a href="login.php" class="btn btn-outline-secondary btn-lg">Se connecter</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
