<?php
require_once "../models/Realisateur.php";

class RealisateurController {

    public static function index($pdo) {
        $realisateurs = Realisateur::getAllSortedByBestFilm($pdo);
        require_once "../views/realisateurs.php";
    }

    public static function add($pdo) {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            Realisateur::add(
                $pdo,
                $_POST['nom'],
                $_POST['prenom'],
                $_POST['nationalite']
            );

            header("Location: index.php?action=realisateurs");
            exit;
        }

        require_once "../views/add_realisateur.php";
    }

    public static function delete($pdo) {

        if (isset($_GET['id'])) {
            Realisateur::delete($pdo, $_GET['id']);
        }

        header("Location: index.php?action=realisateurs");
        exit;
    }
}
