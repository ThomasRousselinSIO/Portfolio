<?php require_once "partials/header.php"; ?>

<form class="movie-form" method="POST" action="index.php?action=add_acteur">

    <h1 class="form-title">Ajouter un acteur 🎭</h1>

    <input type="text" name="prenom" placeholder="Prénom" required>

    <input type="text" name="nom" placeholder="Nom" required>

    <button type="submit">Ajouter</button>

</form>

<?php require_once "partials/footer.php"; ?>
