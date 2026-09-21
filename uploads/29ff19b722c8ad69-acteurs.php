<?php require_once "partials/header.php"; ?>

<section class="hero">
    <h1>Acteurs 🎭</h1>
    <p>Triés par la note du film le plus connu.</p>
</section>

<div class="page-actions">
    <a class="btn-primary" href="index.php?action=add_acteur">+ Ajouter un acteur</a>
</div>

<div class="person-grid">

<?php foreach($acteurs as $a): ?>

<div class="person-card">

    <div class="person-content">

        <h2><?= trim($a['prenom'] . ' ' . $a['nom']) ?></h2>

        <p class="person-films">
            <?= $a['films_titres'] ?: 'Aucun film enregistré' ?>
        </p>

        <div class="info">
            <span>🎞️ <?= $a['nb_films'] ?> film(s)</span>
            <?php if($a['meilleure_note']): ?>
                <span>⭐ <?= $a['meilleure_note'] ?>/10</span>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a class="delete"
               href="index.php?action=delete_acteur&id=<?= $a['id'] ?>"
               onclick="return confirm('Supprimer cet acteur ?')">
                Supprimer
            </a>
        </div>

    </div>

</div>

<?php endforeach; ?>

</div>

<?php require_once "partials/footer.php"; ?>
