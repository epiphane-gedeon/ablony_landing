<?php
/**
 * Script pour consulter les inscriptions à la waitlist
 * À utiliser uniquement à des fins de développement/administration
 * Accès: http://votre-site.com/get_waitlist.php?pwd=YOUR_PASSWORD
 */

// Charger les variables d'environnement depuis .env
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// Récupérer le mot de passe depuis les variables d'environnement
$admin_password = getenv('WAITLIST_PASSWORD') ?: 'admin123';
$provided_password = isset($_GET['pwd']) ? $_GET['pwd'] : '';

if ($provided_password !== $admin_password) {
    http_response_code(404);
    echo '<h1>Not Found</h1>';
    echo '<p>The requested URL was not found on this server.</p>';
    echo '<p>Additionally, a 404 Not Found error was encountered while trying to use an ErrorDocument to handle the request.</p>';
    exit;
}

$db_path = __DIR__ . '/data/waitlist.db';

if (!file_exists($db_path)) {
    echo '<h1>❌ Aucune donnée</h1>';
    echo '<p>La base de données n\'existe pas encore. Aucune inscription n\'a été enregistrée.</p>';
    exit;
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer toutes les inscriptions
    $result = $db->query("SELECT * FROM waitlist ORDER BY registered_at DESC");
    $data = $result->fetchAll(PDO::FETCH_ASSOC);

    // Si c'est une requête JSON
    if (isset($_GET['json'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'total' => count($data),
            'data' => $data
        ]);
        exit;
    }

    // Affichage HTML
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Waitlist Ablony</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Be Vietnam Pro', sans-serif; background: #f5f5f5; padding: 20px; }
            .container { max-width: 1000px; margin: 0 auto; }
            h1 { color: #1e40af; margin-bottom: 10px; }
            .stats { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
            .stat-item { display: inline-block; margin-right: 30px; font-size: 1.1rem; }
            .stat-count { font-size: 2rem; font-weight: bold; color: #1e40af; }
            table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
            th { background: #1e40af; color: white; padding: 12px; text-align: left; font-weight: 600; }
            td { padding: 12px; border-bottom: 1px solid #eee; }
            tr:hover { background: #f9f9f9; }
            .empty { text-align: center; font-size: 1.2rem; color: #666; background: white; padding: 40px; border-radius: 8px; }
            .export-btn { 
                background: #1e40af; 
                color: white; 
                padding: 10px 20px; 
                border: none; 
                border-radius: 6px; 
                cursor: pointer; 
                font-size: 1rem;
                margin-bottom: 20px;
            }
            .export-btn:hover { background: #1e3a8a; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>📋 Inscriptions à la Waitlist — Ablony</h1>
            <p style="color: #666; margin-bottom: 20px;">Données en temps réel depuis la base de données SQLite</p>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-count"><?php echo count($data); ?></div>
                    <div>Total d'inscriptions</div>
                </div>
            </div>

            <button class="export-btn" onclick="exportToCSV()">📥 Exporter en CSV</button>

            <?php if (count($data) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Email</th>
                            <th>Date d'inscription</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['registered_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty">
                    Aucune inscription pour le moment 🤷‍♂️
                </div>
            <?php endif; ?>
        </div>

        <script>
            function exportToCSV() {
                fetch('get_waitlist.php?json=1&pwd=<?php echo urlencode($admin_password); ?>')
                    .then(r => r.json())
                    .then(data => {
                        if (!data.data || data.data.length === 0) {
                            alert('Aucune donnée à exporter');
                            return;
                        }

                        // Headers CSV
                        let csv = 'ID,Email,Date d\'inscription\n';
                        
                        // Données
                        data.data.forEach((row, i) => {
                            csv += `${row.id},"${row.email}","${row.registered_at}"\n`;
                        });

                        // Télécharger
                        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        const url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', 'ablony_waitlist_' + new Date().toISOString().split('T')[0] + '.csv');
                        link.click();
                    });
            }
        </script>
    </body>
    </html>
    <?php

} catch (PDOException $e) {
    die('Erreur base de données: ' . $e->getMessage());
}
?>
