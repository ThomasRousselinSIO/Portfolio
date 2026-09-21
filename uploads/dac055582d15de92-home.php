<?php require_once "partials/header.php"; ?>

<section class="hero">
    <h1>Bienvenue sur MovieZone 🍿</h1>
    <p>Gère ta collection de films avec une interface moderne.</p>
</section>

<div class="stats">
    <div class="card-stat">
        <h2><?= $films->rowCount() ?></h2>
        <p>Films enregistrés</p>
    </div>
</div>

<div class="film-grid">

<?php foreach($films as $film): ?>

<div class="film-card">

    <img src="<?= $film['image'] ?>" alt="film">

    <div class="film-content">

        <h2><?= $film['titre'] ?></h2>

        <div class="badge">
            <?= $film['genre'] ?>
        </div>

        <p>
            <?= $film['description'] ?>
        </p>

        <div class="info">
            <span>📅 <?= $film['annee'] ?></span>
            <span>⭐ <?= $film['note'] ?>/10</span>
        </div>

        <div class="actions">
            <a class="edit" href="index.php?action=edit&id=<?= $film['id'] ?>">
                Modifier
            </a>

            <a class="delete"
               href="index.php?action=delete&id=<?= $film['id'] ?>"
               onclick="return confirm('Supprimer ce film ?')">
                Supprimer
            </a>
        </div>

    </div>

</div>

<?php endforeach; ?>

</div>

<?php require_once "partials/footer.php"; ?>
