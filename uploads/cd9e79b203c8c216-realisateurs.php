<?php require_once "partials/header.php"; ?>

<section class="hero">
    <h1>Réalisateurs 🎬</h1>
    <p>Triés par la note du film le plus connu.</p>
</section>

<div class="page-actions">
    <a class="btn-primary" href="index.php?action=add_realisateur">+ Ajouter un réalisateur</a>
</div>

<div class="person-grid">

<?php foreach($realisateurs as $r): ?>

<div class="person-card">

    <div class="person-content">

        <h2><?= $r['prenom'] . ' ' . $r['nom'] ?></h2>

        <?php if($r['nationalite']): ?>
            <div class="badge"><?= $r['nationalite'] ?></div>
        <?php endif; ?>

        <p class="person-films">
            <?= $r['films_titres'] ?: 'Aucun film enregistré' ?>
        </p>

        <div class="info">
            <span>🎞️ <?= $r['nb_films'] ?> film(s)</span>
            <?php if($r['meilleure_note']): ?>
                <span>⭐ <?= $r['meilleure_note'] ?>/10</span>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a class="delete"
               href="index.php?action=delete_realisateur&id=<?= $r['id'] ?>"
               onclick="return confirm('Supprimer ce réalisateur ?')">
                Supprimer
            </a>
        </div>

    </div>

</div>

<?php endforeach; ?>

</div>

<?php require_once "partials/footer.php"; ?>
