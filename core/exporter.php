<?php
class Exporter {
    private $exportPath = "export/";

    public function generate($projectName, $levels, $contents) {
        $projectDir = $this->exportPath . $this->sanitize($projectName);
        if (!is_dir($projectDir)) mkdir($projectDir, 0777, true);

        // Enregistrement des données (Save)
        file_put_contents($projectDir . '/config.json', json_encode(['level' => $levels, 'content' => $contents]));

        // Préparation des données pour la carcasse
        $personaData = $this->formatData($levels, $contents);
        
        // Génération de la carcasse (index.html)
        $htmlContent = $this->buildTemplate($projectName, $personaData);
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

    private function buildTemplate($name, $d) {
        return "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <title>Persona : $name</title>
            <style>
                body { font-family: sans-serif; background: #f4f4f4; padding: 50px; }
                .card { background: white; max-width: 600px; margin: auto; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
                h1 { color: #f39c12; border-bottom: 2px solid #f39c12; padding-bottom: 10px; }
                .meta { color: #666; font-style: italic; margin-bottom: 20px; }
                .section { margin-bottom: 15px; }
                .label { font-weight: bold; color: #333; display: block; }
                .quote { font-size: 1.2em; color: #555; border-left: 4px solid #eee; padding-left: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='card'>
                <h1>$name</h1>
                <p class='meta'>Âge : " . ($d['age'] ?? 'NC') . " ans | Situation : " . ($d['3_sit'] ?? 'NC') . "</p>
                <div class='quote'>« " . ($d['3'] ?? 'Pas de citation') . " »</div>
                <div class='section'><span class='label'>Bio :</span>" . nl2br($d['3_per'] ?? '') . "</div>
                <div class='section'><span class='label'>Habitat :</span>" . ($d['3_loc'] ?? '') . "</div>
                <div class='section'><span class='label'>Traits :</span>" . ($d['3_tra'] ?? '') . "</div>
                <div class='section'><span class='label'>Objectifs :</span>" . ($d['3_mot'] ?? '') . "</div>
                <div class='section'><span class='label'>Frustrations :</span>" . ($d['3_fru'] ?? '') . "</div>
            </div>
        </body>
        </html>";
    }
}