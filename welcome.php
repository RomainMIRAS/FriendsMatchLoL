<?php
require_once 'includes/header.php';

// Initialize the session
session_start();

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>

<div class="container mt-5">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="display-4 mb-4">FriendsMatchLoL</h1>
            <h2 class="mb-4">Track your friends' games on League of Legends</h2>
            <p class="lead">
                FriendsMatchLoL allows you to follow your friends' League of Legends games in real-time.
                Receive notifications when they start a game, discover the champions they play,
                and check the details of ongoing matches.
            </p>
            <div class="mt-5">
                <a href="register.php" class="btn btn-primary btn-lg me-3">Sign up for free</a>
                <a href="login.php" class="btn btn-outline-secondary btn-lg">Log in</a>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-5">
                    <img src="https://ddragon.leagueoflegends.com/cdn/img/champion/splash/Lux_0.jpg" alt="League of Legends" class="img-fluid rounded mb-4" style="max-height: 300px; object-fit: cover;">
                    <h3>Main Features</h3>
                    <ul class="list-group list-group-flush text-start">
                        <li class="list-group-item border-0">
                            <i class="fas fa-check-circle text-success me-2"></i> Real-time tracking of your friends' games
                        </li>
                        <li class="list-group-item border-0">
                            <i class="fas fa-check-circle text-success me-2"></i> Complete details of ongoing matches
                        </li>
                        <li class="list-group-item border-0">
                            <i class="fas fa-check-circle text-success me-2"></i> Game start notifications
                        </li>
                        <li class="list-group-item border-0">
                            <i class="fas fa-check-circle text-success me-2"></i> History of previous games
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5 pt-5">
        <div class="col-12 text-center">
            <h2 class="mb-5">How It Works</h2>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width:80px;height:80px">
                        <i class="fas fa-user-plus fa-2x"></i>
                    </div>
                    <h4>1. Add your friends</h4>
                    <p>Add your League of Legends friends' summoner names to start tracking them.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width:80px;height:80px">
                        <i class="fas fa-desktop fa-2x"></i>
                    </div>
                    <h4>2. Track their games</h4>
                    <p>Receive real-time notifications when your friends start a game and check their match details.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4" style="width:80px;height:80px">
                        <i class="fas fa-history fa-2x"></i>
                    </div>
                    <h4>3. View match history</h4>
                    <p>Access the complete history of your friends' previous games with detailed statistics.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5 pt-5">
        <div class="col-md-6">
            <h2>Testimonials</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px">
                                <span class="text-white">JP</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">John P.</h5>
                            <p class="small text-muted mb-2">Player since 2015</p>
                            <p>Thanks to FriendsMatchLoL, I can easily know when my friends are in a game without having to launch the game. A super useful app!</p>
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
                            <h5 class="mb-1">Mary D.</h5>
                            <p class="small text-muted mb-2">Player since 2018</p>
                            <p>I love receiving notifications when my friends start a game. It helps me stay connected with my team even when I'm not playing.</p>
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
                            Is the application free?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes, FriendsMatchLoL is completely free. You just need to create an account to start tracking your friends.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            How do I add friends to track?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            After logging in, use the "Add a friend" form on the homepage. Enter your friend's summoner name and region, then click "Add".
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Does the application work for all regions?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes, FriendsMatchLoL supports all official League of Legends regions, including EUW, EUNE, NA, KR, JP, etc.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            Is the data updated in real-time?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes, the data is automatically refreshed every 60 seconds to provide you with the most recent information about your friends' games.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5 pt-5 text-center">
        <div class="col-12">
            <h2>Ready to start?</h2>
            <p class="lead mb-4">Create your free account and start tracking your friends now!</p>
            <a href="register.php" class="btn btn-primary btn-lg me-3">Sign up</a>
            <a href="login.php" class="btn btn-outline-secondary btn-lg">Log in</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
