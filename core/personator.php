<?php
error_reporting(E_ALL); ini_set('display_errors', 1);

class Personator {
    private $dicts = [];
    private $base_path;
    private $tree;
    private $personaData; // Stocke les données du formulaire

    const DEFAULT_BODY = "<h1>Fiche Persona</h1>\n<p>LOREM_TEXT</p>"; 
    
    public function __construct($export_name, $post = [], $files = []) {
        $this->base_path = "export/" . $export_name . "/";
        $this->personaData = $post; // On stocke le $_POST global

        if (!file_exists('dicts/structure.json') || !file_exists('dicts/classes.json')) {
            die("ERREUR : Fichiers dicts manquants.");
        }
        $this->dicts['struct'] = json_decode(file_get_contents('dicts/structure.json'), true);
        $this->dicts['classes'] = json_decode(file_get_contents('dicts/classes.json'), true);

        // Gestion de la photo
        if (isset($files['p_photo']) && $files['p_photo']['error'] === 0) {
            if (!file_exists($this->base_path)) { mkdir($this->base_path, 0777, true); }
            $photoName = "avatar-" . time() . "." . pathinfo($files['p_photo']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($files['p_photo']['tmp_name'], $this->base_path . $photoName);
            $this->personaData['photo_path'] = $photoName;
        }
    }

    public function arborate($tree) {
        $this->tree = $tree;
        if (!file_exists($this->base_path)) { mkdir($this->base_path, 0777, true); }

        foreach ($tree as $parentName => $subs) {
            $this->generateFile($this->base_path, "index", 0);

            if (!empty($subs)) {
                foreach ($subs as $subName => $content) {
                    $slugDir = $this->slugify($subName);
                    $subPath = $this->base_path . $slugDir . "/";
                    if (!file_exists($subPath)) { mkdir($subPath, 0777, true); }

                    if (is_array($content)) {
                        foreach ($content as $key => $val) {
                            $nameToSlug = is_array($val) ? $key : $val;
                            $this->generateFile($subPath, $nameToSlug, 1);
                        }
                    }
                }
            }
        }
    }

    private function generateFile($path, $name, $level) {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $cleanName = str_replace(['.php', '.css', '.js'], '', $name);
        
        if ($extension === 'css') {
            $fileName = $this->slugify($cleanName) . ".css";
            file_put_contents($path . $fileName, "/* Fichier CSS généré */\nbody { }");
        } 
        elseif ($extension === 'js') {
            $fileName = $this->slugify($cleanName) . ".js";
            file_put_contents($path . $fileName, "// Fichier JS généré");
        }
        else {
            $fileName = $this->slugify($cleanName) . ".php";
            $html = $this->prepareHTML($cleanName, $level);
            file_put_contents($path . $fileName, $html);
        }
    }

    private function prepareHTML($title, $level) {
        $relPath = ($level == 0) ? "./" : str_repeat("../", $level);
        
        // Données dynamiques
        $prenom = $this->personaData['p_prenom'] ?? $title;
        $nom = $this->personaData['p_nom'] ?? "";
        $localite = $this->personaData['p_localite'] ?? "Non spécifiée";
        $photo = !empty($this->personaData['photo_path']) ? $relPath . $this->personaData['photo_path'] : "";
        $imgTag = $photo ? "<img src='$photo' style='width:150px; height:150px; border-radius:50%; object-fit:cover;'>" : "👤";

        $psychologie = !empty($this->personaData['p_personnalite']) ? nl2br($this->personaData['p_personnalite']) : "LOREM_TEXT";
        $objectifs = !empty($this->personaData['p_objectifs']) ? nl2br($this->personaData['p_objectifs']) : "LOREM_TEXT";
        $frustrations = !empty($this->personaData['p_frustrations']) ? nl2br($this->personaData['p_frustrations']) : "LOREM_TEXT";

        return "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Persona : $prenom $nom</title>
    <style>
        :root { --primary: #2c3e50; --accent: #3498db; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--primary); margin: 0; padding: 20px; }
        .persona-container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: grid; grid-template-columns: 300px 1fr; }
        .sidebar { background: var(--primary); color: white; padding: 30px; text-align: center; }
        .photo-placeholder { width: 150px; height: 150px; background: #bdc3c7; border-radius: 50%; margin: 0 auto 20px; border: 5px solid rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 50px; overflow:hidden; }
        .content { padding: 40px; }
        .grid-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: #fff; border: 1px solid #eee; padding: 15px; border-radius: 8px; border-left: 5px solid var(--accent); }
        h1 { margin: 0; font-size: 24px; }
        h3 { color: var(--accent); margin-top: 0; }
    </style>
</head>
<body>
    <div class='persona-container'>
        <div class='sidebar'>
            <div class='photo-placeholder'>$imgTag</div>
            <h1>$prenom $nom</h1>
            <p>📍 $localite</p>
            <p><i>LOREM_TEXT</i></p>
        </div>
        <div class='content'>
            <div class='grid-info'>
                <div class='card'><h3>Psychologie</h3><p>$psychologie</p></div>
                <div class='card'><h3>Objectifs</h3><p>$objectifs</p></div>
                <div class='card'><h3>Besoins</h3><p>LOREM_TEXT</p></div>
                <div class='card'><h3>Freins</h3><p>$frustrations</p></div>
            </div>
        </div>
    </div>
</body>
</html>";
    }

    private function buildMenu($currentLevel) {
        $rel = ($currentLevel == 0) ? "./" : str_repeat("../", $currentLevel);
        $itemsHtml = "";
        foreach ($this->tree as $parent => $subs) {
            $url = $rel . "index.php";
            $tagA = $this->dicts['struct']['a'] ?? '<a href="{url}">{text}</a>';
            $link = str_replace(['{url}', '{text}'], [$url, $parent], $tagA);
            $itemsHtml .= "<li>$link</li>";
        }
        return "<nav><ul>$itemsHtml</ul></nav>";
    }

    private function slugify($text) {
        if (is_array($text)) { return "folder"; } 
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
    }
}