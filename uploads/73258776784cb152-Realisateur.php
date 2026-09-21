<?php

class Realisateur {

    public static function getAll($pdo) {
        return $pdo->query("SELECT * FROM realisateur ORDER BY nom ASC");
    }

    // Liste triée par la note du film le plus connu de chaque réalisateur
    public static function getAllSortedByBestFilm($pdo) {
        return $pdo->query(
            "SELECT realisateur.*,
                    MAX(films.note) AS meilleure_note,
                    COUNT(films.id) AS nb_films,
                    GROUP_CONCAT(films.titre SEPARATOR ', ') AS films_titres
             FROM realisateur
             LEFT JOIN films ON films.id_realisateur = realisateur.id
             GROUP BY realisateur.id
             ORDER BY meilleure_note DESC"
        );
    }

    public static function getById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM realisateur WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function add($pdo, $nom, $prenom, $nationalite) {
        $stmt = $pdo->prepare(
            "INSERT INTO realisateur (nom, prenom, nationalite) VALUES (:nom, :prenom, :nationalite)"
        );
        $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'nationalite' => $nationalite
        ]);
    }

    public static function delete($pdo, $id) {
        // les films liés passent automatiquement id_realisateur à NULL (ON DELETE SET NULL)
        $stmt = $pdo->prepare("DELETE FROM realisateur WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
