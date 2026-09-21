<?php

class Film {

    public static function getAll($pdo) {
        return $pdo->query(
            "SELECT films.*, realisateur.nom AS real_nom, realisateur.prenom AS real_prenom
             FROM films
             LEFT JOIN realisateur ON films.id_realisateur = realisateur.id
             ORDER BY films.id DESC"
        );
    }

    public static function getById($pdo, $id) {
        $stmt = $pdo->prepare(
            "SELECT films.*, realisateur.nom AS real_nom, realisateur.prenom AS real_prenom
             FROM films
             LEFT JOIN realisateur ON films.id_realisateur = realisateur.id
             WHERE films.id = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function add($pdo, $titre, $genre, $annee, $note, $description, $image, $idRealisateur = null, $acteurs = []) {

        $stmt = $pdo->prepare(
            "INSERT INTO films (titre, genre, annee, note, description, image, id_realisateur)
             VALUES (:titre, :genre, :annee, :note, :description, :image, :idRealisateur)"
        );

        $stmt->execute([
            'titre' => $titre,
            'genre' => $genre,
            'annee' => $annee,
            'note' => $note,
            'description' => $description,
            'image' => $image,
            'idRealisateur' => $idRealisateur ?: null
        ]);

        $idFilm = $pdo->lastInsertId();

        self::syncActeurs($pdo, $idFilm, $acteurs);
    }

    public static function update($pdo, $id, $titre, $genre, $annee, $note, $description, $image, $idRealisateur = null, $acteurs = []) {

        $stmt = $pdo->prepare(
            "UPDATE films SET
                titre = :titre,
                genre = :genre,
                annee = :annee,
                note = :note,
                description = :description,
                image = :image,
                id_realisateur = :idRealisateur
             WHERE id = :id"
        );

        $stmt->execute([
            'titre' => $titre,
            'genre' => $genre,
            'annee' => $annee,
            'note' => $note,
            'description' => $description,
            'image' => $image,
            'idRealisateur' => $idRealisateur ?: null,
            'id' => $id
        ]);

        self::syncActeurs($pdo, $id, $acteurs);
    }

    public static function delete($pdo, $id) {
        // film_acteur est en ON DELETE CASCADE, pas besoin de le vider manuellement
        $stmt = $pdo->prepare("DELETE FROM films WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    // Remplace la liste des acteurs liés à un film (utilisé par add() et update())
    private static function syncActeurs($pdo, $idFilm, $acteurs) {

        $stmt = $pdo->prepare("DELETE FROM film_acteur WHERE id_film = :idFilm");
        $stmt->execute(['idFilm' => $idFilm]);

        if (!empty($acteurs)) {
            $stmt = $pdo->prepare(
                "INSERT INTO film_acteur (id_film, id_acteur) VALUES (:idFilm, :idActeur)"
            );
            foreach ($acteurs as $idActeur) {
                $stmt->execute(['idFilm' => $idFilm, 'idActeur' => $idActeur]);
            }
        }
    }
}
