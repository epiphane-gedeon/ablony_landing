<?php
// Ensure JSON is always returned, even on error
header('Content-Type: application/json; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Configuration
$recipient_email = 'contact@epiphane-gedeon.com';
$response = array();

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $name = isset($_POST['name']) ? trim(strip_tags($_POST['name'])) : '';
    $email = isset($_POST['email']) ? trim(strip_tags($_POST['email'])) : '';
    $subject = isset($_POST['subject']) ? trim(strip_tags($_POST['subject'])) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Validation
    $errors = array();

    if (empty($name)) {
        $errors[] = 'Le nom est requis.';
    }

    if (empty($email)) {
        $errors[] = 'L\'email est requis.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide.';
    }

    if (empty($subject)) {
        $errors[] = 'L\'objet est requis.';
    }

    if (empty($message)) {
        $errors[] = 'Le message est requis.';
    }

    // Vérifier que le message n'est pas trop court
    if (strlen($message) < 10) {
        $errors[] = 'Le message doit contenir au moins 10 caractères.';
    }

    // Si des erreurs, renvoyer la réponse
    if (!empty($errors)) {
        $response['success'] = false;
        $response['errors'] = $errors;
        echo json_encode($response);
        exit;
    }

    // Construire le contenu de l'email
    $email_subject = '[Ablony] ' . $subject;
    
    $email_body = "Nouveau message reçu depuis le formulaire de contact\n";
    $email_body .= "==========================================\n\n";
    $email_body .= "Nom: " . $name . "\n";
    $email_body .= "Email: " . $email . "\n";
    $email_body .= "Objet: " . $subject . "\n\n";
    $email_body .= "Message:\n";
    $email_body .= "----------\n";
    $email_body .= $message . "\n";
    $email_body .= "----------\n\n";
    $email_body .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $email_body .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";

    // En-têtes email
    $headers = "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Envoyer l'email
    $mail_sent = mail($recipient_email, $email_subject, $email_body, $headers);

    if ($mail_sent) {
        // Envoyer un email de confirmation à l'utilisateur
        $confirm_subject = 'Confirmation de réception - ' . $subject;
        $confirm_body = "Bonjour " . $name . ",\n\n";
        $confirm_body .= "Merci d'avoir pris contact via le formulaire de contact.\n";
        $confirm_body .= "Votre message a été reçu avec succès et nous vous répondront dans les plus brefs délais.\n\n";
        $confirm_body .= "Récapitulatif de votre message:\n";
        $confirm_body .= "- Objet: " . $subject . "\n";
        $confirm_body .= "- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $confirm_body .= "Cordialement,\nL'équipe Ablony";

        $confirm_headers = "From: " . $recipient_email . "\r\n";
        $confirm_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        mail($email, $confirm_subject, $confirm_body, $confirm_headers);

        $response['success'] = true;
        $response['message'] = 'Message envoyé avec succès! Vous recevrez une confirmation par email.';
    } else {
        $response['success'] = false;
        $response['errors'] = array('Erreur lors de l\'envoi du message. Veuillez réessayer plus tard.');
    }
} else {
    $response['success'] = false;
    $response['errors'] = array('Méthode de requête non autorisée.');
}

// Retourner la réponse JSON
echo json_encode($response);
exit;
