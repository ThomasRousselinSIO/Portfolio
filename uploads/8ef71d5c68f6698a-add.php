<?php require_once "partials/header.php"; ?>

<form class="movie-form" method="POST" action="index.php?action=add" enctype="multipart/form-data">

    <h1 class="form-title">Ajouter un film 🎬</h1>

    <input type="text" name="titre" placeholder="Titre du film" required>

    <input type="text" name="genre" placeholder="Genre" required>

    <input type="number" name="annee" placeholder="Année" min="1888" max="2100" required>

    <input type="number" name="note" placeholder="Note sur 10" min="0" max="10" step="0.1" required>

    <textarea name="description" placeholder="Description du film"></textarea>

    <select name="id_realisateur">
        <option value="">-- Choisir un réalisateur --</option>
        <?php foreach($realisateurs as $r): ?>
            <option value="<?= $r['id'] ?>"><?= $r['prenom'] . ' ' . $r['nom'] ?></option>
        <?php endforeach; ?>
    </select>

    <div class="checkbox-group">
        <p class="checkbox-label">Acteurs :</p>
        <?php foreach($acteurs as $a): ?>
            <label class="checkbox-item">
                <input type="checkbox" name="acteurs[]" value="<?= $a['id'] ?>">
                <?= $a['prenom'] . ' ' . $a['nom'] ?>
            </label>
        <?php endforeach; ?>
    </div>

    <div class="image-toggle">
        <button type="button" class="toggle-btn active" data-target="url-input">Lien internet</button>
        <button type="button" class="toggle-btn" data-target="file-input">Fichier</button>
    </div>

    <div id="url-input" class="image-input-group">
        <input type="text" name="image_url" placeholder="https://exemple.com/affiche.jpg">
    </div>

    <div id="file-input" class="image-input-group" style="display:none;">
        <input type="file" name="image_file" accept="image/*">
    </div>

    <button type="submit">Ajouter le film</button>

</form>

<?php require_once "partials/footer.php"; ?>
