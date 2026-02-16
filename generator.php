<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

require_once 'core/skeletor.php';

$backupDir = 'core/backups/';
if (!is_dir($backupDir)) { mkdir($backupDir, 0777, true); }

$loadedData = 'null';
if (!empty($_GET['load'])) {
    $fileToLoad = $backupDir . basename($_GET['load']);
    if (file_exists($fileToLoad) && !is_dir($fileToLoad)) { 
        $loadedData = file_get_contents($fileToLoad); 
    }
}

$backups = array_diff(scandir($backupDir), ['.', '..']);

$projectName = !empty($_POST['config_name']) ? basename($_POST['config_name']) : "MonNouveauProjet";
$app = new Skeletor($projectName);
$statusMessage = "";

if (isset($_POST['save_config'])) {
    $name = !empty($_POST['config_name']) ? basename($_POST['config_name']) : 'sans-nom-' . date('Y-m-d_H-i');
    file_put_contents($backupDir . $name . '.json', json_encode($_POST));
    $statusMessage = "Configuration '$name' sauvegardée !";
}

if (isset($_POST['generate']) && isset($_POST['level']) && isset($_POST['title'])) {
    $exportPath = "export/" . $projectName;
    if (is_dir($exportPath)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($exportPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
    }

    $structure = [];
    $levels = $_POST['level'];
    $names = $_POST['title'];
    $parents = $_POST['parent_folder'] ?? [];

    foreach ($levels as $index => $lvl) {
        $name = !empty($names[$index]) ? $names[$index] : "sans-nom";
        
        if ($lvl == "1") {
            $structure[$name] = [];
        } elseif ($lvl == "2") {
            $root = "";
            for ($i = $index; $i >= 0; $i--) { 
                if ($levels[$i] == "1") { $root = $names[$i]; break; } 
            }
            if ($root) $structure[$root][$name] = [];
        } elseif ($lvl == "3" || $lvl == "3_dir") {
            $parentIndex = $parents[$index] ?? null;
            if ($parentIndex !== null && $parentIndex !== "") {
                $parentName = $names[$parentIndex];
                foreach ($structure as $rootName => &$subFolders) {
                    if (isset($subFolders[$parentName])) {
                        if ($lvl == "3_dir") {
                            $subFolders[$parentName][$name] = []; 
                        } else {
                            $subFolders[$parentName][] = $name; 
                        }
                        break;
                    }
                }
            }
        }
    }
    $app->arborate($structure);
    $statusMessage = "ARBORESCENCE GÉNÉRÉE DANS /export/$projectName !";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Skeletor v1.0</title>
    <style>
        body { font-family: sans-serif; background: #1a1a1a; color: #eee; padding: 50px; }
        .container { max-width: 900px; margin: auto; background: #222; padding: 30px; border: 1px solid #333; }
        .row { display: flex; align-items: center; margin-bottom: 10px; background: #2a2a2a; padding: 10px; border-radius: 4px; }
        .drag-handle { cursor: grab; padding: 0 15px; color: #666; font-size: 20px; user-select: none; }
        .input-group { display: flex; gap: 10px; flex-grow: 1; }
        select, input[type="text"] { padding: 8px; background: #333; color: #fff; border: 1px solid #444; }
        input[type="text"] { flex-grow: 1; }
        .btn-add { background: #333; border: 1px dashed #555; width: 100%; padding: 10px; cursor: pointer; color: #aaa; margin: 20px 0; }
        .btn-submit { background: #e67e22; color: #fff; border: none; padding: 15px; width: 100%; cursor: pointer; font-weight: bold; }
        .btn-remove { background: #c0392b; color: white; border: none; padding: 8px 12px; cursor: pointer; font-weight: bold; margin-left: 10px; }
        .status-bar { color: orange; font-weight: bold; margin-bottom: 15px; }
        .admin-link { display: inline-block; margin-bottom: 20px; color: orange; text-decoration: none; font-size: 0.9rem; border: 1px solid orange; padding: 2px 8px; border-radius: 3px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Skeletor v1.0</h1>
    <a href="admin.php" class="admin-link">Admin</a>
    
    <?php if($statusMessage): ?>
        <div class="status-bar"><?php echo $statusMessage; ?></div>
    <?php endif; ?>

    <div style="margin-bottom: 20px; background: #333; padding: 10px; border-radius: 4px;">
        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
            <select name="load" style="flex-grow: 1;">
                <option value="">-- Projet vierge --</option>
                <?php foreach ($backups as $file): ?>
                    <option value="<?php echo $file; ?>" <?php echo (isset($_GET['load']) && $_GET['load'] == $file) ? 'selected' : ''; ?>>
                        <?php echo str_replace('.json', '', $file); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">OK</button>
            <a href="?" style="color: orange; text-decoration: none; font-size: 0.8rem; font-weight: bold;">CLEAR</a>
        </form>
    </div>

    <form method="POST" id="main-form">
        <div id="inputs-container"></div>
        <button type="button" class="btn-add" onclick="addRow()">+ Ajouter une ligne</button>
        <div style="background: #2a2a2a; padding: 15px; margin-bottom: 20px; border: 1px solid #444;">
            <input type="text" name="config_name" placeholder="Nom du projet" style="width: 60%; color: orange;" value="<?php echo isset($_GET['load']) ? str_replace('.json', '', $_GET['load']) : ''; ?>">
            <button type="submit" name="save_config" style="background: #e67e22; color: white; border: none; padding: 10px 20px; cursor: pointer; font-weight: bold;">💾 SAUVEGARDER</button>
        </div>
        <button type="submit" name="generate" class="btn-submit">GÉNÉRER L'ARBORESCENCE</button>
    </form>
</div>

<script>
let dragSrcEl = null;

function handleDragStart(e) { dragSrcEl = this; e.dataTransfer.effectAllowed = 'move'; }
function handleDragOver(e) { e.preventDefault(); }
function handleDrop(e) {
    e.stopPropagation();
    if (dragSrcEl !== this) {
        const list = this.parentNode;
        const allNodes = Array.from(list.children);
        if (allNodes.indexOf(dragSrcEl) < allNodes.indexOf(this)) { list.insertBefore(dragSrcEl, this.nextSibling); }
        else { list.insertBefore(dragSrcEl, this); }
        refreshAllLists();
    }
}

function addRow() {
    const container = document.getElementById('inputs-container');
    const newRow = document.createElement('div');
    newRow.className = 'row';
    newRow.draggable = true;
    newRow.innerHTML = `
        <div class="drag-handle">☰</div>
        <div class="input-group">
            <select name="level[]" class="level-select">
                <option value="1">Niveau 1 (Racine)</option>
                <option value="2">Niveau 2 (Dossier)</option>
                <option value="3">Niveau 3 (Fichier)</option>
                <option value="3_dir">Niveau 3 (Sous-Dossier)</option>
            </select>
            <input type="text" name="title[]" placeholder="Nom" class="title-input">
            <input type="hidden" name="parent_folder[]" class="parent-hidden">
            <select class="parent-selector" style="display:none;"></select>
        </div>
        <button type="button" class="btn-remove" onclick="this.closest('.row').remove(); refreshAllLists();">X</button>`;
    
    newRow.addEventListener('dragstart', handleDragStart);
    newRow.addEventListener('dragover', handleDragOver);
    newRow.addEventListener('drop', handleDrop);
    
    newRow.querySelector('.level-select').onchange = refreshAllLists;
    newRow.querySelector('.title-input').oninput = refreshAllLists;
    
    container.appendChild(newRow);
    refreshAllLists();
}

function refreshAllLists() {
    const rows = document.querySelectorAll('.row');
    const dossiers = [];
    
    rows.forEach((row, i) => {
        if(row.querySelector('.level-select').value === "2") {
            dossiers.push({i: i, name: row.querySelector('.title-input').value || "Dossier " + (i + 1)});
        }
    });

    rows.forEach(row => {
        const lvl = row.querySelector('.level-select').value;
        const sel = row.querySelector('.parent-selector');
        const hid = row.querySelector('.parent-hidden');
        
        if(lvl.startsWith("3")) {
            const old = row.getAttribute('data-temp') || hid.value;
            sel.innerHTML = '<option value="">-- Choisir Dossier --</option>' + 
                dossiers.map(d => `<option value="${d.i}" ${d.i == old ? 'selected' : ''}>${d.name}</option>`).join('');
            sel.style.display = 'inline-block';
            sel.onchange = () => { hid.value = sel.value; row.removeAttribute('data-temp'); };
            hid.value = sel.value;
        } else {
            sel.style.display = 'none';
            hid.value = "";
        }
    });
}

const data = <?php echo $loadedData; ?>;
window.onload = () => {
    if(data && data.level) {
        data.level.forEach((lvl, i) => {
            addRow();
            const r = document.querySelectorAll('.row')[i];
            r.querySelector('.level-select').value = lvl;
            r.querySelector('.title-input').value = data.title[i];
            if(lvl.startsWith("3")) r.setAttribute('data-temp', data.parent_folder[i]);
        });
        refreshAllLists();
    } else { addRow(); }
};
</script>
</body>
</html>