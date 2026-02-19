<?php
class Exporter {
    private $exportPath = "export/";

    public function generate($projectName, $levels, $contents, $imageFile = null) {
        $projectDir = $this->exportPath . $this->sanitize($projectName);
        if (!is_dir($projectDir)) mkdir($projectDir, 0777, true);

        // Gestion de l'image (v1.2)
        $imageHtml = "";
        if ($imageFile && $imageFile['error'] === 0) {
            $imgDir = $projectDir . '/img/';
            if (!is_dir($imgDir)) mkdir($imgDir, 0777, true);
            
            $extension = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
            $fileName = "photo." . $extension;
            $destination = $imgDir . $fileName;
            
            if (move_uploaded_file($imageFile['tmp_name'], $destination)) {
                $imageHtml = "<img src='img/$fileName' class='persona-img'>";
            }
        }

        // Enregistrement des données (Save)
        file_put_contents($projectDir . '/config.json', json_encode(['level' => $levels, 'content' => $contents]));

        // Préparation des données pour la carcasse
        $personaData = $this->formatData($levels, $contents);
        
        // Génération de la carcasse (index.html)
        $htmlContent = $this->buildTemplate($projectName, $personaData, $imageHtml);
        file_put_contents($projectDir . '/index.html', $htmlContent);
    }

    private function sanitize($name) {
        return preg_replace('/[^a-zA-Z0-9\-\_]/', '', str_replace(' ', '-', $name));
    }

    private function formatData($levels, $contents) {
        $data = [];
        foreach ($levels as $i => $type) {
            $data[$type] = $contents[$i] ?? '';
        }
        return $data;
    }

    private function formatTags($text) {
        if (empty($text)) return '';
        $tags = explode(',', $text);
        $html = '';
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag) {
                $html .= "<span style='background: #f39c12; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8em; font-weight: bold; display: inline-block; margin-bottom: 5px;'>$tag</span>";
            }
        }
        return $html;
    }

    private function buildTemplate($name, $d, $imageHtml) {
        return "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <title>Persona : $name</title>
            <style>
                body { font-family: sans-serif; background: #f4f4f4; padding: 50px; color: #333; }
                .card { background: white; max-width: 600px; margin: auto; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; }
                .persona-img { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 4px solid #f39c12; margin-bottom: 20px; }
                h1 { color: #f39c12; border-bottom: 2px solid #f39c12; padding-bottom: 10px; margin-top: 0; }
                .meta { color: #666; font-style: italic; margin-bottom: 20px; }
                .section { margin-bottom: 15px; text-align: left; }
                .label { font-weight: bold; color: #333; display: block; margin-bottom: 5px; }
                .quote { font-size: 1.2em; color: #555; border-left: 4px solid #eee; padding-left: 15px; margin: 20px 0; font-style: italic; text-align: left; }
                .tags-container { display: flex; gap: 8px; flex-wrap: wrap; }
            </style>
        </head>
        <body>
            <div class='card'>
                $imageHtml
                <h1>$name</h1>
                <p class='meta'>Âge : " . ($d['age'] ?? 'NC') . " ans | Situation : " . ($d['3_sit'] ?? 'NC') . "</p>
                <div class='quote'>« " . ($d['3'] ?? 'Pas de citation') . " »</div>
                <div class='section'><span class='label'>Bio :</span>" . nl2br($d['3_per'] ?? '') . "</div>
                <div class='section'><span class='label'>Habitat :</span>" . ($d['3_loc'] ?? '') . "</div>
                <div class='section'>
                    <span class='label'>Traits de caractère :</span>
                    <div class='tags-container'>" . $this->formatTags($d['3_tra'] ?? '') . "</div>
                </div>
                <div class='section'><span class='label'>Objectifs :</span>" . ($d['3_mot'] ?? '') . "</div>
                <div class='section'><span class='label'>Frustrations :</span>" . ($d['3_fru'] ?? '') . "</div>
            </div>
        </body>
        </html>";
    }
}