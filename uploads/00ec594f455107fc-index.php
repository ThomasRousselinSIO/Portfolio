<?php
require_once "../config/database.php";
require_once "../controllers/FilmController.php";
require_once "../controllers/RealisateurController.php";
require_once "../controllers/ActeurController.php";

$action = $_GET['action'] ?? 'list';

switch($action) {

    case 'add':
        FilmController::add($pdo);
        break;

    case 'delete':
        FilmController::delete($pdo);
        break;

    case 'edit':
        FilmController::edit($pdo);
        break;

    case 'realisateurs':
        RealisateurController::index($pdo);
        break;

    case 'add_realisateur':
        RealisateurController::add($pdo);
        break;

    case 'delete_realisateur':
        RealisateurController::delete($pdo);
        break;

    case 'acteurs':
        ActeurController::index($pdo);
        break;

    case 'add_acteur':
        ActeurController::add($pdo);
        break;

    case 'delete_acteur':
        ActeurController::delete($pdo);
        break;

    default:
        FilmController::index($pdo);
}
?>
