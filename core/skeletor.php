<?php
error_reporting(E_ALL); ini_set('display_errors', 1);

class Skeletor {
    private $dicts = [];
    private $base_path;
    private $tree;

    // Ton modèle de contenu
    const DEFAULT_BODY = "<h1>Page générée par Skeletor</h1>\n<p>LOREM_TEXT</p>";

    public function __construct($export_name) {
        $this->base_path = "export/" . $export_name . "/";
        if (!file_exists('dicts/structure.json') || !file_exists('dicts/classes.json')) {
            die("ERREUR : Fichiers dicts manquants.");
        }
        $this->dicts['struct'] = json_decode(file_get_contents('dicts/structure.json'), true);
        $this->dicts['classes'] = json_decode(file_get_contents('dicts/classes.json'), true);
    }

    public function arborate($tree) {
        $this->tree = $tree;
        if (!file_exists($this->base_path)) { mkdir($this->base_path, 0777, true); }
        $this->generateCSS();

        foreach ($tree as $parentName => $subs) {
            // NIVEAU 1
            $this->generateFile($this->base_path, $parentName, 0);

            if (!empty($subs)) {
                foreach ($subs as $subName => $content) {
                    // NIVEAU 2
                    $subPath = $this->base_path . $this->slugify($subName) . "/";
                    if (!file_exists($subPath)) { mkdir($subPath, 0777, true); }
                    
                    $this->generateFile($subPath, "index", 1);

                    if (is_array($content)) {
                        foreach ($content as $fileName) {
                            // NIVEAU 3
                            $this->generateFile($subPath, $fileName, 1);
                        }
                    }
                }
            }
        }
    }

    private function generateFile($path, $name, $level) {
        $fileName = $this->slugify($name) . ".php";
        $html = $this->prepareHTML($name, $level);
        file_put_contents($path . $fileName, $html);
    }

    private function prepareHTML($title, $level) {
        $relPath = ($level == 0) ? "./" : str_repeat("../", $level);
        $menu = $this->buildMenu($level);
        
        // Utilisation du squelette HTML avec ton contenu
        return "<!DOCTYPE html>\n<html lang='fr'>\n<head>\n<meta charset='UTF-8'>\n<title>$title</title>\n"
             . "<link rel='stylesheet' href='{$relPath}style.css'>\n"
             . "<style>body { line-height: 1.6; max-width: 800px; margin: 40px auto; padding: 20px; }</style>\n"
             . "</head>\n<body>\n<header>\n$menu\n</header>\n"
             . self::DEFAULT_BODY
             . "\n</body>\n</html>";
    }

    private function buildMenu($currentLevel) {
        $rel = ($currentLevel == 0) ? "./" : str_repeat("../", $currentLevel);
        $itemsHtml = "";
        foreach ($this->tree as $parent => $subs) {
            $url = $rel . $this->slugify($parent) . ".php";
            $tagA = $this->dicts['struct']['a'] ?? '<a href="{url}">{text}</a>';
            $link = str_replace(['{url}', '{text}'], [$url, $parent], $tagA);
            $itemsHtml .= "<li>$link</li>";
        }
        return "<nav><ul>$itemsHtml</ul></nav>";
    }

    private function generateCSS() {
        $css = "body { font-family: sans-serif; background: #1a1a1a; color: #eee; } nav ul { display: flex; gap: 15px; list-style: none; padding: 0; } nav a { color: orange; text-decoration: none; }";
        file_put_contents($this->base_path . "style.css", $css);
    }

    private function slugify($text) {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
    }
}