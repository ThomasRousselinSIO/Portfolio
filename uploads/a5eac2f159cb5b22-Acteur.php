<?php

class Acteur {

    public static function getAll($pdo) {
        return $pdo->query("SELECT * FROM acteur ORDER BY nom ASC");
    }

    // Liste triée par la note du film le plus connu de chaque acteur
    public static function getAllSortedByBestFilm($pdo) {
        return $pdo->query(
            "SELECT acteur.*,
                    MAX(films.note) AS meilleure_note,
                    COUNT(film_acteur.id_film) AS nb_films,
                    GROUP_CONCAT(films.titre SEPARATOR ', ') AS films_titres
             FROM acteur
             LEFT JOIN film_acteur ON film_acteur.id_acteur = acteur.id
             LEFT JOIN films ON films.id = film_acteur.id_film
             GROUP BY acteur.id
             ORDER BY meilleure_note DESC"
        );
    }

    public static function getById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM acteur WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function add($pdo, $nom, $prenom) {
        $stmt = $pdo->prepare(
            "INSERT INTO acteur (nom, prenom) VALUES (:nom, :prenom)"
        );
        $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom
        ]);
    }

    public static function delete($pdo, $id) {
        // les liens film_acteur sont supprimés automatiquement (ON DELETE CASCADE)
        $stmt = $pdo->prepare("DELETE FROM acteur WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    // Récupère tous les acteurs d'un film donné
    public static function getByFilm($pdo, $idFilm) {
        $stmt = $pdo->prepare(
            "SELECT acteur.* FROM acteur
             JOIN film_acteur ON acteur.id = film_acteur.id_acteur
             WHERE film_acteur.id_film = :idFilm"
        );
        $stmt->execute(['idFilm' => $idFilm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
