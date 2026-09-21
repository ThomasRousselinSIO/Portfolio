<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=films;charset=utf8",
        "root",
        ""
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

}catch(PDOException $e) {
    die("Erreur connexion : " . $e->getMessage());
}
?>