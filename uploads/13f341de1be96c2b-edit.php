<?php require_once "partials/header.php"; ?>

<h1 class="form-title">Modifier le film</h1>

<form method="POST" class="movie-form">

    <input type="text"
           name="titre"
           value="<?= $film['titre'] ?>"
           required>

    <input type="text"
           name="genre"
           value="<?= $film['genre'] ?>"
           required>

    <input type="number"
           name="annee"
           value="<?= $film['annee'] ?>"
           required>

    <input type="number"
           step="0.1"
           name="note"
           value="<?= $film['note'] ?>"
           required>

    <input type="text"
           name="image"
           value="<?= $film['image'] ?>"
           required>

    <textarea name="description">
<?= $film['description'] ?>
    </textarea>

    <button type="submit">Modifier</button>

</form>

<?php require_once "partials/footer.php"; ?>
