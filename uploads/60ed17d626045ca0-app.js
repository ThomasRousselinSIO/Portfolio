    // MovieZone - app.js
// Validation des formulaires et petites interactions UI

document.addEventListener('DOMContentLoaded', function () {

    // Validation du formulaire d'ajout/édition de film
    const form = document.querySelector('.movie-form');

    if (form) {
        form.addEventListener('submit', function (e) {
            const titre = form.querySelector('[name="titre"]');
            const annee = form.querySelector('[name="annee"]');
            const note = form.querySelector('[name="note"]');

            let errors = [];

            if (titre && titre.value.trim() === '') {
                errors.push("Le titre est obligatoire.");
            }

            if (annee && (annee.value < 1888 || annee.value > 2100)) {
                errors.push("L'année doit être comprise entre 1888 et 2100.");
            }

            if (note && (note.value < 0 || note.value > 10)) {
                errors.push("La note doit être comprise entre 0 et 10.");
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });
    }

    // Confirmation avant suppression (en plus de l'attribut onclick déjà présent)
    document.querySelectorAll('.delete').forEach(function (link) {
        link.addEventListener('click', function (e) {
            const confirmed = confirm('Supprimer ce film définitivement ?');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    });

    // Bascule entre "Lien internet" et "Fichier" pour l'image du film
    const toggleButtons = document.querySelectorAll('.toggle-btn');

    toggleButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {

            toggleButtons.forEach(function (b) {
                b.classList.remove('active');
            });
            btn.classList.add('active');

            document.querySelectorAll('.image-input-group').forEach(function (group) {
                group.style.display = 'none';
            });

            const target = document.getElementById(btn.dataset.target);
            if (target) {
                target.style.display = 'flex';
            }
        });
    });

});
