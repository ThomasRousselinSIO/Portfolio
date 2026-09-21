<h1>Liste des films</h1>

<a href="index.php?action=add">Ajouter</a>

<ul>
<?php foreach($films as $film): ?>
    <li>
        <?= $film['titre'] ?> (<?= $film['genre'] ?>)
        <a href="index.php?action=delete&id=<?= $film['id'] ?>">Supprimer</a>
    </li>
<?php endforeach; ?>
</ul>