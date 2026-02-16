<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

$backupDir = 'core/backups/';

if (isset($_GET['delete'])) {
    $fileToDelete = $backupDir . basename($_GET['delete']);
    if (file_exists($fileToDelete)) {
        unlink($fileToDelete);
        header("Location: admin.php?status=deleted");
        exit;
    }
}

$backups = array_diff(scandir($backupDir), ['.', '..']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Skeletor Admin</title>
    <style>
        body { font-family: sans-serif; background: #1a1a1a; color: #eee; padding: 50px; }
        .container { max-width: 800px; margin: auto; background: #222; padding: 30px; border: 1px solid #333; }
        h1 { border-bottom: 2px solid orange; padding-bottom: 10px; color: orange; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; background: #333; padding: 10px; color: #aaa; }
        td { padding: 12px 10px; border-bottom: 1px solid #333; }
        .btn { padding: 6px 12px; text-decoration: none; border-radius: 3px; font-size: 0.85rem; font-weight: bold; }
        .btn-load { background: #e67e22; color: white; }
        .btn-delete { background: #c0392b; color: white; margin-left: 5px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #888; text-decoration: none; border-bottom: 1px solid #444; }
    </style>
</head>
<body>

<div class="container">
    <a href="generator.php" class="back-link">← Retour au générateur</a>
    <h1>Gestion des projets</h1>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
        <p style="color: #c0392b; font-weight: bold; background: #321; padding: 10px; border-radius: 4px;">Projet supprimé.</p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Nom du fichier JSON</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($backups as $file): ?>
            <tr>
                <td><?php echo str_replace('.json', '', $file); ?></td>
                <td>
                    <a href="generator.php?load=<?php echo urlencode($file); ?>" class="btn btn-load">CHARGER</a>
                    <a href="admin.php?delete=<?php echo urlencode($file); ?>" class="btn btn-delete" onclick="return confirm('Supprimer ce projet définitivement ?')">SUPPRIMER</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($backups)): ?>
            <tr>
                <td colspan="2" style="text-align: center; color: #666; padding: 40px;">Aucune sauvegarde trouvée dans /core/backups/</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>