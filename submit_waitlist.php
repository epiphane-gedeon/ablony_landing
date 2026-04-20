<?php
// Ensure JSON is always returned, even on error
header('Content-Type: application/json; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

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

// Configuration
$response = array();
$db_path = __DIR__ . '/data/waitlist.db';

// Créer le répertoire data s'il n'existe pas
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer l'email
    $email = isset($_POST['email']) ? trim(strtolower($_POST['email'])) : '';

    // Validation
    $errors = array();

    if (empty($email)) {
        $errors[] = 'L\'email est requis.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide.';
    }

    // Si des erreurs, renvoyer la réponse
    if (!empty($errors)) {
        $response['success'] = false;
        $response['errors'] = $errors;
        echo json_encode($response);
        exit;
    }

    try {
        // Connexion à SQLite
        $db = new PDO('sqlite:' . $db_path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Créer la table si elle n'existe pas
        $db->exec("CREATE TABLE IF NOT EXISTS waitlist (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Vérifier si l'email est déjà inscrit
        $check = $db->prepare("SELECT id FROM waitlist WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->fetch()) {
            $response['success'] = false;
            $response['errors'] = array('Cet email est déjà inscrit sur la liste d\'attente.');
            echo json_encode($response);
            exit;
        }

        // Insérer le nouvel email
        $insert = $db->prepare("INSERT INTO waitlist (email) VALUES (?)");
        $insert->execute([$email]);

        $response['success'] = true;
        $response['message'] = 'Email ajouté à la liste d\'attente avec succès !';

    } catch (PDOException $e) {
        $response['success'] = false;
        $response['errors'] = array('Erreur lors de l\'inscription. Veuillez réessayer plus tard.');
        error_log('Waitlist DB Error: ' . $e->getMessage());
    }

} else {
    $response['success'] = false;
    $response['errors'] = array('Méthode de requête non autorisée.');
}

// Retourner la réponse JSON
echo json_encode($response);
exit;
