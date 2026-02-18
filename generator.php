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
$app = new Personator($projectName, $_POST, $_FILES);
$statusMessage = "";

if (isset($_POST['save_config'])) {
    $name = !empty($_POST['config_name']) ? basename($_POST['config_name']) : 'sans-nom-' . date('Y-m-d_H-i');
    file_put_contents($backupDir . $name . '.json', json_encode($_POST));
    $statusMessage = "Configuration '$name' sauvegardée !";
}

if (isset($_POST['generate']) && isset($_POST['level']) && isset($_POST['content'])) {
    if (!is_dir("export")) { mkdir("export", 0777, true); }
    $app->arborate($_POST);
    $statusMessage = "✅ PERSONA GÉNÉRÉ DANS /export/$projectName !";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personator v1.0</title>
    <style>
        * { box-sizing: border-box; }
        body { background-color: #1a1a1a; color: #eee; font-family: sans-serif; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }

        .header-main { display: flex; align-items: center; margin-bottom: 20px; position: relative; }
        
        .admin-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 15px;
            border: 1px solid #f39c12;
            color: #f39c12;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.8em;
            flex: none;
            position: relative;
            z-index: 10; /* Priorité sur le survol */
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .admin-link:hover {
            background-color: #f39c12;
            color: #ffffff;
        }

        .header-main h1 { 
            flex: 1; 
            text-align: center; 
            margin: 0; 
            transform: translateX(-35px); /* Ajusté pour éviter de couvrir le bouton */
            pointer-events: none; /* Le titre ne bloque plus les clics autour de lui */
        }

        .load-zone, .save-zone-container { background: #222; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #333; }
        .load-form, .save-zone { display: flex; gap: 10px; align-items: center; }
        .load-form select, .save-zone input[type="text"] { flex: 1; height: 40px; padding: 0 10px; background: #111; color: #fff; border: 1px solid #444; }
        .btn-ok, .btn-save { height: 40px; padding: 0 20px; flex: none; cursor: pointer; border: none; border-radius: 4px; font-weight: bold; }
        .btn-ok { background: #eee; color: #333; }
        .btn-save { background: #f39c12; color: white; }
        .clear-btn { color: #f39c12; font-weight: bold; text-decoration: none; font-size: 0.9em; margin-left: 5px; }

        .row { display: flex; gap: 10px; margin-bottom: 10px; align-items: stretch; background: #222; padding: 10px; border-radius: 4px; border: 1px solid transparent; }
        .row.dragging { opacity: 0.5; border: 1px dashed #f39c12; }
        .drag-handle { display: flex; align-items: center; cursor: grab; color: #555; font-size: 20px; padding: 0 5px; }
        
        .input-group { display: flex; gap: 10px; flex: 1; align-items: stretch; }
        .content-wrapper { flex: 1; min-width: 0; }
        
        .level-select, .content-input, .btn-remove { 
            height: 40px !important; 
            border: 1px solid #444;
            border-radius: 4px;
            background: #333;
            color: #fff;
            font-size: 14px;
        }
        .level-select { flex: 0 0 250px; padding: 0 10px; }
        .content-input { width: 100%; padding: 0 10px; background: #111; }
        textarea.content-input { padding: 10px; height: 40px !important; resize: none; }

        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
        
        .btn-remove { background: #c0392b; color: white; border: none; width: 40px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .btn-submit { width: 100%; padding: 15px; background: #f39c12; color: white; border: none; font-weight: bold; font-size: 1.1em; border-radius: 4px; cursor: pointer; text-transform: uppercase; }
        
        @media (max-width: 768px) {
            .header-main h1 { transform: none; }
            .input-group { flex-direction: column; }
            .level-select { flex: 1 1 auto; width: 100%; }
            .content-wrapper { width: 100%; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header-main">
            <a href="./admin.php" class="admin-link">Admin</a>
            <h1>Personator v1.0</h1>
        </div>
        
        <div class="load-zone">
            <form method="GET" class="load-form">
                <span style="color:#ccc;">Charger :</span>
                <select name="load">
                    <option value="">-- Projet vierge --</option>
                    <?php foreach ($backups as $file): ?>
                        <option value="<?php echo $file; ?>" <?php echo (isset($_GET['load']) && $_GET['load'] == $file) ? 'selected' : ''; ?>>
                            <?php echo str_replace('.json', '', $file); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-ok">OK</button>
                <a href="?" class="clear-btn">CLEAR</a>
            </form>
        </div>

        <form method="POST" id="main-form">
            <div id="inputs-container"></div>
            <button type="button" onclick="addRow()" style="width:100%; padding:12px; margin-bottom:20px; background: #333; color: #999; border: 1px dashed #555; cursor:pointer; border-radius:4px;">+ Ajouter une ligne</button>
            <div class="save-zone-container">
                <div class="save-zone">
                    <input type="text" name="config_name" placeholder="Nom du projet" value="<?php echo isset($_GET['load']) ? str_replace('.json', '', $_GET['load']) : ''; ?>">
                    <button type="submit" name="save_config" class="btn-save">💾 SAUVEGARDER CONFIG</button>
                </div>
            </div>
            <button type="submit" name="generate" class="btn-submit">GÉNÉRER LE PERSONA</button>
        </form>
    </div>

    <script>
        let dragSrcEl = null;

        function handleDragStart(e) {
            dragSrcEl = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        }
        function handleDragOver(e) { e.preventDefault(); return false; }
        function handleDragEnd(e) { this.classList.remove('dragging'); }
        function handleDrop(e) {
            e.stopPropagation();
            if (dragSrcEl !== this) {
                const list = document.getElementById('inputs-container');
                const allNodes = Array.from(list.children);
                if (allNodes.indexOf(dragSrcEl) < allNodes.indexOf(this)) {
                    list.insertBefore(dragSrcEl, this.nextSibling);
                } else {
                    list.insertBefore(dragSrcEl, this);
                }
            }
            return false;
        }

        function addRow() {
            const container = document.getElementById('inputs-container');
            const row = document.createElement('div');
            row.className = 'row';
            row.draggable = true;
            row.addEventListener('dragstart', handleDragStart);
            row.addEventListener('dragover', handleDragOver);
            row.addEventListener('drop', handleDrop);
            row.addEventListener('dragend', handleDragEnd);

            row.innerHTML = `
                <div class="drag-handle">☰</div>
                <div class="input-group">
                    <select name="level[]" class="level-select" onchange="updateRowType(this)">
                        <optgroup label="STRUCTURE">
                            <option value="1">Dossier Racine (Niveau 1)</option>
                            <option value="2">Sous-Dossier (Niveau 2)</option>
                        </optgroup>
                        <optgroup label="CONTENU">
                            <option value="3">Citation / Verbatim</option>
                            <option value="3_sit">Situation familiale</option>
                            <option value="3_loc">Lieu de vie (Type d'habitat)</option>
                            <option value="3_met">Métier / Secteur</option>
                            <option value="3_per">Personnalité (Bio)</option>
                            <option value="3_tra">Traits de caractère</option>
                            <option value="3_mot">Motivations / Objectifs</option>
                            <option value="3_fru">Frustrations / Freins</option>
                            <option value="age">Âge</option>
                        </optgroup>
                    </select>
                    <div class="content-wrapper"></div>
                </div>
                <button type="button" class="btn-remove" onclick="this.closest('.row').remove()">X</button>
            `;
            container.appendChild(row);
            updateRowType(row.querySelector('.level-select'));
        }

        function updateRowType(select) {
            const wrapper = select.closest('.row').querySelector('.content-wrapper');
            const val = select.value;
            let input;
            if (val === "age") {
                input = document.createElement('input');
                input.type = "number";
                input.placeholder = "Âge...";
            } else if (val === "1" || val === "2") {
                input = document.createElement('input');
                input.type = "text";
                input.placeholder = "Nom du dossier...";
            } else {
                input = document.createElement('textarea');
                input.placeholder = "Votre réponse ici !";
            }
            input.name = "content[]";
            input.className = "content-input";
            wrapper.innerHTML = '';
            wrapper.appendChild(input);
        }

        window.onload = () => {
            const data = <?php echo $loadedData; ?>;
            if (data && data.level) {
                data.level.forEach((lvl, i) => {
                    addRow();
                    const r = document.querySelectorAll('.row')[i];
                    r.querySelector('.level-select').value = lvl;
                    updateRowType(r.querySelector('.level-select'));
                    r.querySelector('.content-input').value = data.content[i] || "";
                });
            } else {
                ["1", "age", "3", "3_sit", "3_loc", "3_met", "3_per", "3_tra", "3_mot", "3_fru"].forEach(d => {
                    addRow();
                    const r = document.querySelector('.row:last-child');
                    r.querySelector('.level-select').value = d;
                    updateRowType(r.querySelector('.level-select'));
                });
            }
        };
    </script>
</body>
</html>