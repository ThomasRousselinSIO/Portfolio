<?php
class Film {

    public static function getAll($pdo) {
        return $pdo->query("SELECT * FROM films");
    }

    public static function create($pdo, $titre, $genre) {
        $sql = "INSERT INTO films (titre, genre) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titre, $genre]);
    }

    public static function delete($pdo, $id) {
        $stmt = $pdo->prepare("DELETE FROM films WHERE id=?");
        $stmt->execute([$id]);
    }
}
?>