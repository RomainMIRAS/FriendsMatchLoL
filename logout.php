<?php
require_once 'includes/utils.php';
require_once 'config/config.php';

// Initialiser la session
session_start();

// Utiliser notre fonction de déconnexion qui gère également le token "Se souvenir de moi"
logout();

// Rediriger vers la page de connexion
header('Location: login.php');
exit;
