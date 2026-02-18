<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

require_once 'core/personator.php';

$backupDir = 'core/backups/';
if (!is_dir($backupDir)) { mkdir($backupDir, 0777, true); }

$loadedData = 'null';
if (isset($_GET['load']) && !empty($_GET['load'])) {
    $fileToLoad = $backupDir . basename($_GET['load']);
    if (file_exists($fileToLoad) && !is_dir($fileToLoad)) { 
        $loadedData = file_get_contents($fileToLoad); 
    }
}

$backups = array_diff(scandir($backupDir), ['.', '..']);

$projectName = !empty($_POST['config_name']) ? basename($_POST['config_name']) : "Nouveau-Persona";
// Mise à jour de l'instanciation pour gérer les fichiers et le POST global
$app = new Personator($projectName, $_POST, $_FILES);
$statusMessage = "";

if (isset($_POST['save_config'])) {
    $name = !empty($_POST['config_name']) ? basename($_POST['config_name']) : 'sans-nom-' . date('Y-m-d_H-i');
    file_put_contents($backupDir . $name . '.json', json_encode($_POST));
    $statusMessage = "Configuration '$name' sauvegardée !";
}

if (isset($_POST['generate']) && isset($_POST['level']) && isset($_POST['title'])) {
    if (!is_dir("export")) { mkdir("export", 0777, true); }
    $exportPath = "export/" . $projectName;
    
    if (is_dir($exportPath)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($exportPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }
        @rmdir($exportPath);
    }
    
    mkdir($exportPath, 0777, true);

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
            if ($parentIndex !== null && $parentIndex !== "" && isset($names[$parentIndex])) {
                $parentName = $names[$parentIndex];
                foreach ($structure as $rootName => &$subFolders) {
                    if (isset($subFolders[$parentName])) {
                        if ($lvl == "3_dir") {
                            if (!is_array($subFolders[$parentName])) { $subFolders[$parentName] = []; }
                            $subFolders[$parentName][$name] = []; 
                        } else {
                            $subFolders[$parentName][] = (string)$name; 
                        }
                        break;
                    }
                }
            }
        }
    }
    
    $app->arborate($structure);
    $statusMessage = "✅ ARBORESCENCE GÉNÉRÉE DANS /export/$projectName !";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personator v1.0</title>
    <style>
        body { font-family: sans-serif; background: #1a1a1a; color: #eee; padding: 20px; margin: 0; }
        .container { max-width: 900px; margin: auto; background: #222; padding: 20px; border: 1px solid #333; border-radius: 8px; }
        h1 { font-size: 1.5rem; text-align: center; }
        .row { display: flex; flex-wrap: wrap; align-items: center; margin-bottom: 10px; background: #2a2a2a; padding: 10px; border-radius: 4px; gap: 10px; }
        .drag-handle { cursor: grab; padding: 5px 10px; color: #666; font-size: 20px; user-select: none; }
        .input-group { display: flex; flex-wrap: wrap; gap: 10px; flex-grow: 1; }
        select, input[type="text"], textarea { padding: 10px; background: #333; color: #fff; border: 1px solid #444; border-radius: 4px; box-sizing: border-box; }
        .level-select { flex: 1 1 150px; }
        .title-input { flex: 2 1 200px; }
        .parent-selector { flex: 1 1 150px; }
        .btn-add { background: #333; border: 1px dashed #555; width: 100%; padding: 15px; cursor: pointer; color: #aaa; margin: 20px 0; border-radius: 4px; }
        .btn-submit { background: #e67e22; color: #fff; border: none; padding: 15px; width: 100%; cursor: pointer; font-weight: bold; border-radius: 4px; font-size: 1rem; }
        .btn-remove { background: #c0392b; color: white; border: none; padding: 10px 15px; cursor: pointer; font-weight: bold; border-radius: 4px; margin-left: auto; }
        .status-bar { color: orange; font-weight: bold; margin-bottom: 15px; text-align: center; }
        .admin-link { display: inline-block; margin-bottom: 20px; color: orange; text-decoration: none; font-size: 0.9rem; border: 1px solid orange; padding: 5px 12px; border-radius: 3px; }
        .load-zone { margin-bottom: 20px; background: #333; padding: 15px; border-radius: 4px; }
        .load-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .save-zone { background: #2a2a2a; padding: 15px; margin-bottom: 20px; border: 1px solid #444; display: flex; flex-wrap: wrap; gap: 10px; border-radius: 4px; }
        .config-name-input { flex: 1 1 250px; padding: 10px; background: #111; color: orange; border: 1px solid #555; }
        .btn-save { background: #e67e22; color: white; border: none; padding: 10px 20px; cursor: pointer; font-weight: bold; border-radius: 4px; flex: 0 1 auto; }
        
        @media (max-width: 600px) {
            .row { padding: 15px; }
            .input-group { flex-direction: column; }
            .level-select, .title-input, .parent-selector { width: 100%; flex: none; }
            .btn-remove { width: 100%; margin: 10px 0 0 0; }
            .config-name-input { width: 100%; }
            .btn-save { width: 100%; }
        }

/* Supprime les flèches sur Chrome, Safari, Edge, Opera */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Supprime les flèches sur Firefox */
input[type="number"] {
    -moz-appearance: textfield;
    background: #333 !important;
    color: orange !important;
    border: 1px solid #444 !important;
}



    </style>
</head>
<body>

<div class="container">
    <h1>Personator v1.0</h1>
    <a href="admin.php" class="admin-link">Admin</a>
    
    <?php if($statusMessage): ?>
        <div class="status-bar"><?php echo $statusMessage; ?></div>
    <?php endif; ?>

    <div class="load-zone">
        <form method="GET" class="load-form">
            <label>Charger :</label>
            <select name="load" style="flex-grow: 1; min-width: 150px;">
                <option value="">-- Projet vierge --</option>
                <?php foreach ($backups as $file): ?>
                    <option value="<?php echo $file; ?>" <?php echo (isset($_GET['load']) && $_GET['load'] == $file) ? 'selected' : ''; ?>>
                        <?php echo str_replace('.json', '', $file); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" style="padding: 10px 20px; cursor:pointer;">OK</button>
            <a href="?" style="color: orange; text-decoration: none; font-size: 0.8rem; font-weight: bold; padding: 5px;">CLEAR</a>
        </form>
    </div>

    <form method="POST" id="main-form" enctype="multipart/form-data">
        
        <div style="background: #2a2a2a; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #444;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <input type="text" name="p_prenom" placeholder="Prénom" style="flex: 1 1 200px;">
                <input type="text" name="p_nom" placeholder="Nom" style="flex: 1 1 200px;">
                <input type="text" name="p_localite" placeholder="Localité" style="flex: 1 1 200px;">
            </div>
            <label style="font-size: 0.8rem; color: #aaa; display: block; margin-bottom: 5px;">Photo du Persona :</label>
            <input type="file" name="p_photo" accept="image/*" style="width: 100%; color: #eee;">
        </div>

        <!--<div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
            <textarea name="p_personnalite" placeholder="Personnalité" style="flex: 1 1 250px; height:80px;"></textarea>
            <textarea name="p_frustrations" placeholder="Frustrations" style="flex: 1 1 250px; height:80px;"></textarea>
            <textarea name="p_objectifs" placeholder="Objectifs" style="flex: 1 1 250px; height:80px;"></textarea>
        </div>-->

        <div id="inputs-container"></div>
        <button type="button" class="btn-add" onclick="addRow()">+ Ajouter une ligne d'info libre</button>

        <div class="save-zone">
            <input type="text" name="config_name" placeholder="Nom du projet" class="config-name-input" value="<?php echo isset($_GET['load']) ? str_replace('.json', '', $_GET['load']) : ''; ?>">
            <button type="submit" name="save_config" class="btn-save">💾 SAUVEGARDER CONFIG</button>
        </div>
        <button type="submit" name="generate" class="btn-submit">GÉNÉRER LE PERSONA</button>
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
            <select name="level[]" class="level-select" onchange="updateRowType(this)">
                <option value="1">Nom du Persona</option>
                <option value="2" hidden>Section (Bloc)</option>
                <option value="age">Âge</option>
                <option value="3">Traits de personnalité</option>
                <option value="3">Aisance numérique</option>
                <option value="3">Outils utilisés</option>
                <option value="3">Objectifs & Besoins</option>
                <option value="3">Frustrations & Freins</option>
                <option value="3">Citation / Verbatim</option>
                <option value="3_dir">Liste à puces (Infos diverses)</option>
            </select>
            <input type="text" name="title[]" placeholder="Texte ou intitulé" class="title-input">
            <input type="hidden" name="parent_folder[]" class="parent-hidden">
            <select class="parent-selector" style="display:none;"></select>
        </div>
        <button type="button" class="btn-remove" onclick="this.closest('.row').remove(); refreshAllLists();">X</button>`;

    newRow.addEventListener('dragstart', handleDragStart);
    newRow.addEventListener('dragover', handleDragOver);
    newRow.addEventListener('drop', handleDrop);
    
    container.appendChild(newRow);
    updateRowType(newRow.querySelector('.level-select'));
    refreshAllLists();
}







function updateRowType(select) {
    const row = select.closest('.row');
    const oldInput = row.querySelector('.title-input');
    let newInput;

    const val = select.value;
    const text = select.options[select.selectedIndex].text;

    if (val === "age") {
        newInput = document.createElement('input');
        newInput.type = "number";
        newInput.placeholder = "Votre age";
        newInput.style.width = "100px";
        newInput.style.flex = "none";
    } 
    else if (text === "Traits de personnalité" || 
             text === "Aisance numérique" || 
             text === "Outils utilisés" || 
             text === "Objectifs & Besoins" || 
             text === "Frustrations & Freins" || 
             val === "3_dir") {
        
        newInput = document.createElement('textarea');
        newInput.placeholder = "Liste d'éléments (un par ligne)";
        newInput.style.height = "40px";
        newInput.style.overflow = "hidden";
        newInput.style.flex = "2 1 200px";
        
        newInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        newInput.addEventListener('focus', function() {
            if (this.value === "") {
                this.value = "• ";
            }
        });

        newInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.substring(0, start) + "\n• " + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 3;
                this.dispatchEvent(new Event('input'));
            }
        });
    } 
    else {
        newInput = document.createElement('input');
        newInput.type = "text";
        newInput.placeholder = "Texte ou intitulé";
        newInput.style.flex = "2 1 200px";
    }

    newInput.name = "title[]";
    newInput.className = "title-input";
    newInput.style.background = "#333";
    newInput.style.color = (val === "age") ? "orange" : "#fff";
    newInput.style.border = "1px solid #444";
    newInput.style.padding = "10px";
    newInput.style.borderRadius = "4px";
    newInput.style.outline = "none";
    newInput.style.resize = "none";

    if (oldInput) {
        oldInput.replaceWith(newInput);
        // Déclencher l'agrandissement si le champ contient déjà du texte
        if (newInput.tagName === "TEXTAREA") {
            newInput.dispatchEvent(new Event('input'));
        }
    }
}















// Nouvelle fonction pour changer le type de champ dynamiquement
function updateInputType(selectElement) {
    const row = selectElement.closest('.row');
    const input = row.querySelector('.title-input');
    if (selectElement.value === "age") {
        input.type = "number";
        input.placeholder = "Ex: 35";
        input.style.flex = "0 1 100px";
    } else {
        input.type = "text";
        input.placeholder = "Texte ou intitulé";
        input.style.flex = "2 1 200px";
    }
    // On force le respect des couleurs du thème
    input.style.background = "#333";
    input.style.color = "#fff";
    input.style.border = "1px solid #444";
    
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
            sel.innerHTML = '<option value="">-- Parent --</option>' + 
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
    if(data) {
        // Remplissage auto des champs fixes si présents dans le JSON
        if(data.p_prenom) document.querySelector('input[name="p_prenom"]').value = data.p_prenom;
        if(data.p_nom) document.querySelector('input[name="p_nom"]').value = data.p_nom;
        if(data.p_localite) document.querySelector('input[name="p_localite"]').value = data.p_localite;
        if(data.p_personnalite) document.querySelector('textarea[name="p_personnalite"]').value = data.p_personnalite;
        if(data.p_frustrations) document.querySelector('textarea[name="p_frustrations"]').value = data.p_frustrations;
        if(data.p_objectifs) document.querySelector('textarea[name="p_objectifs"]').value = data.p_objectifs;

        if(data.level) {
            document.getElementById('inputs-container').innerHTML = '';
            data.level.forEach((lvl, i) => {
                addRow();
                const rows = document.querySelectorAll('.row');
                const r = rows[rows.length - 1];
                r.querySelector('.level-select').value = lvl;
                r.querySelector('.title-input').value = data.title[i];
                if(data.parent_folder && data.parent_folder[i] !== undefined) {
                    r.querySelector('.parent-hidden').value = data.parent_folder[i];
                }
            });
            refreshAllLists();
            document.querySelectorAll('.row').forEach(row => {
                const hid = row.querySelector('.parent-hidden');
                const sel = row.querySelector('.parent-selector');
                if (sel && hid && hid.value !== "") {
                    sel.value = hid.value;
                }
            });
        }
    } else { 
        addRow(); 
    }
};
</script>
</body>
</html>