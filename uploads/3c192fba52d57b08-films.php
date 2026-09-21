<?php
require_once "../models/Film.php";

class FilmController {

    public static function index($pdo) {
        $films = Film::getAll($pdo);
        require "../views/list.php";
    }

    public static function add($pdo) {
        if ($_POST) {
            Film::create($pdo, $_POST['titre'], $_POST['genre']);
            header("Location: index.php");
        }
        require "../views/add.php";
    }

    public static function delete($pdo) {
        Film::delete($pdo, $_GET['id']);
        header("Location: index.php");
    }
}
