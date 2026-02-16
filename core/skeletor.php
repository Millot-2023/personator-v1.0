<?php
error_reporting(E_ALL); ini_set('display_errors', 1);

class Skeletor {
    private $dicts = [];
    private $base_path;
    private $tree;

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
        $menu = $this->buildMenu($level);
        
        return "<!DOCTYPE html>\n<html lang='fr'>\n<head>\n<meta charset='UTF-8'>\n<title>$title</title>\n"
             . "<link rel='stylesheet' href='{$relPath}css/styles.css'>\n"
             . "<style>body { line-height: 1.6; max-width: 800px; margin: 40px auto; padding: 20px; }</style>\n"
             . "</head>\n<body>\n<header>\n$menu\n</header>\n"
             . self::DEFAULT_BODY
             . "\n</body>\n</html>";
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