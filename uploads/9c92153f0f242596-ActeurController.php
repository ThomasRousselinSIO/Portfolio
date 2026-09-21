<?php
require_once "../models/Acteur.php";

class ActeurController {

    public static function index($pdo) {
        $acteurs = Acteur::getAllSortedByBestFilm($pdo);
        require_once "../views/acteurs.php";
    }

    public static function add($pdo) {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            Acteur::add(
                $pdo,
                $_POST['nom'],
                $_POST['prenom']
            );

            header("Location: index.php?action=acteurs");
            exit;
        }

        require_once "../views/add_acteur.php";
    }

    public static function delete($pdo) {

        if (isset($_GET['id'])) {
            Acteur::delete($pdo, $_GET['id']);
        }

        header("Location: index.php?action=acteurs");
        exit;
    }
}
