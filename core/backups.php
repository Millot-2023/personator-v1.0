<?php
// On crée le dossier s'il n'existe pas
if (!is_dir('core/backups')) { mkdir('core/backups', 0777, true); }

// Si on demande à charger un fichier spécifique
$loadedData = 'null';
if (isset($_GET['load'])) {
    $fileToLoad = 'core/backups/' . basename($_GET['load']);
    if (file_exists($fileToLoad)) {
        $loadedData = file_get_contents($fileToLoad);
    }
}

// Lister les sauvegardes disponibles
$backups = array_diff(scandir('core/backups'), ['.', '..']);
?>

<?php
// 1. BLOC PHP (EN HAUT DU FICHIER)
if (!is_dir('core/backups')) { mkdir('core/backups', 0777, true); }

$loadedData = 'null';
if (!empty($_GET['load'])) {
    $fileToLoad = 'core/backups/' . basename($_GET['load']);
    if (file_exists($fileToLoad)) {
        $loadedData = file_get_contents($fileToLoad);
    }
}
$backups = array_diff(scandir('core/backups'), ['.', '..']);
// ... reste du PHP (Skeletor, generate, etc.)
?>

<script>
// ... tes fonctions addRow, handleDrag, refreshAllLists ...

// 2. BLOC JS (A LA FIN DU SCRIPT) - REMPLACE TON ANCIEN LOADER PAR CELUI-CI :
const initialData = <?php echo $loadedData; ?>;

window.onload = function() {
    const container = document.getElementById('inputs-container');

    if (initialData && initialData.level) {
        // C'est ICI que l'on nettoie le formulaire pour éviter les doublons
        container.innerHTML = ''; 
        
        initialData.level.forEach((lvl, index) => {
            addRow(); 
            
            const rows = document.querySelectorAll('.row');
            const currentRow = rows[index];
            
            currentRow.querySelector('.level-select').value = lvl;
            currentRow.querySelector('.title-input').value = initialData.title[index] || '';
            
            if (lvl === "3" && initialData.parent_folder) {
                const pSelect = currentRow.querySelector('.parent-selector');
                setTimeout(() => {
                    pSelect.value = initialData.parent_folder[index];
                }, 10);
            }
        });
        refreshAllLists();
    }
};
</script>