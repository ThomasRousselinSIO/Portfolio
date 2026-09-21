<?php require_once "partials/header.php"; ?>

<form class="movie-form" method="POST" action="index.php?action=add_realisateur">

    <h1 class="form-title">Ajouter un réalisateur 🎬</h1>

    <input type="text" name="prenom" placeholder="Prénom" required>

    <input type="text" name="nom" placeholder="Nom" required>

    <input type="text" name="nationalite" placeholder="Nationalité">

    <button type="submit">Ajouter</button>

</form>

<?php require_once "partials/footer.php"; ?>
