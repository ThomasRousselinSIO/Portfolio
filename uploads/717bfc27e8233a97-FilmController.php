<?php
require_once "../models/Film.php";
require_once "../models/Realisateur.php";
require_once "../models/Acteur.php";

class FilmController {

    public static function index($pdo) {

        $films = Film::getAll($pdo);

        require_once "../views/home.php";
    }

    public static function add($pdo) {

        if($_SERVER['REQUEST_METHOD'] === 'POST') {

            $titre = $_POST['titre'];
            $genre = $_POST['genre'];
            $annee = $_POST['annee'];
            $note = $_POST['note'];
            $description = $_POST['description'];
            $idRealisateur = $_POST['id_realisateur'] ?? null;
            $acteurs = $_POST['acteurs'] ?? [];

            // Gestion de l'image : upload de fichier OU lien internet
            $image = '';

            if (!empty($_FILES['image_file']['name'])) {

                $uploadDir = __DIR__ . "/../public/assets/uploads/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

                if (in_array($_FILES['image_file']['type'], $allowedTypes)) {

                    $filename = uniqid() . "_" . basename($_FILES['image_file']['name']);
                    $targetPath = $uploadDir . $filename;

                    move_uploaded_file($_FILES['image_file']['tmp_name'], $targetPath);

                    $image = "assets/uploads/" . $filename;
                }

            } elseif (!empty($_POST['image_url'])) {
                $image = $_POST['image_url'];
            }

            Film::add(
                $pdo,
                $titre,
                $genre,
                $annee,
                $note,
                $description,
                $image,
                $idRealisateur,
                $acteurs
            );

            header("Location: index.php");
            exit;
        }

        $realisateurs = Realisateur::getAll($pdo);
        $acteurs = Acteur::getAll($pdo);

        require_once "../views/add.php";
    }

    public static function delete($pdo) {

        if(isset($_GET['id'])) {
            Film::delete($pdo, $_GET['id']);
        }

        header("Location: index.php");
    }

    public static function edit($pdo) {

        $film = Film::getById($pdo, $_GET['id']);
        $acteursDuFilm = Acteur::getByFilm($pdo, $_GET['id']);
        $acteursDuFilmIds = array_column($acteursDuFilm, 'id');

        if($_SERVER['REQUEST_METHOD'] === 'POST') {

            Film::update(
                $pdo,
                $_GET['id'],
                $_POST['titre'],
                $_POST['genre'],
                $_POST['annee'],
                $_POST['note'],
                $_POST['description'],
                $_POST['image'],
                $_POST['id_realisateur'] ?? null,
                $_POST['acteurs'] ?? []
            );

            header("Location: index.php");
            exit;
        }

        $realisateurs = Realisateur::getAll($pdo);
        $acteurs = Acteur::getAll($pdo);

        require_once "../views/edit.php";
    }
}
?>
