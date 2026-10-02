<?php
// Templates d'emails — reprend la charte graphique du site (styles.css : #2385AE, Be Vietnam Pro)

/**
 * Balises <head> qui CHARGENT réellement Be Vietnam Pro (Google Fonts).
 * Sans ça, `font-family:'Be Vietnam Pro'` est déclaré mais jamais chargé : les
 * clients (et l'aperçu) retombent sur Arial. Le <link> couvre les clients type
 * Apple Mail et l'aperçu navigateur ; le @import aide certains webmails. Les
 * clients qui ne supportent pas les webfonts (Gmail) retombent proprement sur
 * Arial via la pile `font-family`.
 */
function email_font_head(): string {
    $url = 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap';
    return '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link href="' . $url . '" rel="stylesheet">'
        . '<style type="text/css">@import url(\'' . $url . '\');</style>';
}

function build_waitlist_thankyou_email(): array {
    $fontHead = email_font_head();
    $logoUrl = 'https://ablony.net/images/logo_bleu.png';
    $instagramUrl = 'https://instagram.com/ablony_officiel';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bienvenue sur la liste d'attente Ablony</title>
{$fontHead}
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:'Be Vietnam Pro',Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width:520px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(15,23,42,0.08);" cellpadding="0" cellspacing="0">
          <tr>
            <td style="background-color:#2385AE;padding:28px 32px;text-align:center;">
              <img src="{$logoUrl}" alt="Ablony" width="140" style="display:block;margin:0 auto;border:0;">
            </td>
          </tr>
          <tr>
            <td style="padding:36px 32px 8px;text-align:center;">
              <div style="font-size:40px;line-height:1;margin-bottom:12px;">🚀</div>
              <h1 style="margin:0 0 12px;font-size:22px;color:#0f172a;font-family:'Be Vietnam Pro',Arial,sans-serif;">Tu es sur la liste !</h1>
              <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#64748b;">
                Merci de ton inscription à la liste d'attente <strong style="color:#0f172a;">Ablony</strong>, l'app de mode et de seconde main pensée pour l'Afrique.
                Tu seras informé(e) en avant-première dès que l'application sera disponible.
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:0 32px 32px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#e8f4fa;border-radius:12px;">
                <tr>
                  <td style="padding:20px 24px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                      <tr><td style="padding:6px 0;font-size:14px;color:#0f172a;">✓ Accès en avant-première à l'application</td></tr>
                      <tr><td style="padding:6px 0;font-size:14px;color:#0f172a;">✓ Badge exclusif « Fondateur » sur ton profil</td></tr>
                      <tr><td style="padding:6px 0;font-size:14px;color:#0f172a;">✓ Invitations pour des offres exclusives</td></tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:0 32px 36px;text-align:center;">
              <a href="{$instagramUrl}" style="display:inline-block;background-color:#2385AE;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:14px 28px;border-radius:10px;">
                Suivre @ablony_officiel sur Instagram
              </a>
            </td>
          </tr>
          <tr>
            <td style="padding:20px 32px;background-color:#f8fafc;text-align:center;border-top:1px solid #e2e8f0;">
              <p style="margin:0;font-size:12px;color:#94a3b8;">Tu reçois cet email car tu t'es inscrit(e) sur ablony.net.<br>L'équipe Ablony</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    $text = "Tu es sur la liste !\n\n"
        . "Merci de ton inscription à la liste d'attente Ablony, l'app de mode et de seconde main pensée pour l'Afrique.\n"
        . "Tu seras informé(e) en avant-première dès que l'application sera disponible.\n\n"
        . "Avantages :\n"
        . "- Accès en avant-première à l'application\n"
        . "- Badge exclusif \"Fondateur\" sur ton profil\n"
        . "- Invitations pour des offres exclusives\n\n"
        . "Suis-nous sur Instagram : {$instagramUrl}\n\n"
        . "L'équipe Ablony";

    return ['html' => $html, 'text' => $text];
}

/**
 * Envoi générique d'un email HTML + texte (multipart/alternative), depuis
 * contact@ablony.net. Utilisé par le mail de remerciement ET par la page
 * d'envoi de campagnes (campagne.php).
 */
function send_email(string $to, string $subject, string $html, string $text): bool {
    $boundary = md5(uniqid((string) time(), true));
    $from = 'contact@ablony.net';

    $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $headers = "From: Ablony <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= $text . "\r\n\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $body .= $html . "\r\n\r\n";
    $body .= "--{$boundary}--";

    return mail($to, $subjectEnc, $body, $headers);
}

function send_waitlist_thankyou_email(string $to): bool {
    $content = build_waitlist_thankyou_email();
    return send_email(
        $to,
        "Bienvenue sur la liste d'attente Ablony 🚀",
        $content['html'],
        $content['text']
    );
}

// ============================================================================
// REGISTRE DES TEMPLATES (page d'envoi campagne.php)
// ============================================================================
//
// Chaque template déclare : son libellé, son sujet, et ses variables (pour
// générer le formulaire de gauche). Le rendu est fait par `render_email_template`
// — une seule source de vérité, partagée entre l'aperçu et l'envoi réel.

function email_templates(): array {
    return [
        'app_available' => [
            'label' => 'Ablony est disponible',
            'subject' => 'Ablony est disponible !',
            'variables' => [
                [
                    'key' => 'name',
                    'label' => 'Prénom (optionnel)',
                    'type' => 'text',
                    'placeholder' => 'Awa',
                    'default' => '',
                    'scope' => 'recipient',
                ],
                [
                    'key' => 'playstore_url',
                    'scope' => 'global',
                    'label' => 'Lien Google Play (optionnel)',
                    'type' => 'url',
                    'placeholder' => 'https://play.google.com/store/apps/details?id=net.ablony',
                    'default' => '',
                ],
                [
                    'key' => 'appstore_url',
                    'scope' => 'global',
                    'label' => 'Lien App Store (optionnel)',
                    'type' => 'url',
                    'placeholder' => 'https://apps.apple.com/app/idXXXXXXXX',
                    'default' => '',
                ],
                [
                    'key' => 'intro_message',
                    'scope' => 'global',
                    'label' => "Message d'intro (modifiable)",
                    'type' => 'textarea',
                    'placeholder' => '',
                    'default' => "Ça y est, le grand jour est arrivé : Ablony est enfin disponible ! "
                        . "L'app de mode et de seconde main pensée pour l'Afrique t'attend. "
                        . "Télécharge-la et profite de tes avantages de membre fondateur.",
                ],
            ],
        ],
    ];
}

/**
 * Rend un template avec ses variables. Renvoie ['html' => ..., 'text' => ...].
 * Lève une exception si le template est inconnu.
 */
function render_email_template(string $id, array $vars): array {
    switch ($id) {
        case 'app_available':
            return build_app_available_email($vars);
        default:
            throw new InvalidArgumentException('Template inconnu : ' . $id);
    }
}

/**
 * Template « Ablony est disponible ». Les boutons Play Store / App Store ne
 * s'affichent QUE si le lien correspondant est fourni. Si aucun lien n'est
 * fourni, une note remplace les boutons (le message s'adapte tout seul), et
 * le texte d'intro reste modifiable à la volée.
 */
function build_app_available_email(array $vars): array {
    $logoUrl = 'https://ablony.net/images/logo_blanc.png';

    $name = trim((string) ($vars['name'] ?? ''));
    $play = trim((string) ($vars['playstore_url'] ?? ''));
    $apple = trim((string) ($vars['appstore_url'] ?? ''));
    $intro = trim((string) ($vars['intro_message'] ?? ''));

    // On n'accepte que des URLs http(s) valides : un lien bidon ne fabrique pas
    // de bouton (et ne casse pas le HTML).
    $play = filter_var($play, FILTER_VALIDATE_URL) ? $play : '';
    $apple = filter_var($apple, FILTER_VALIDATE_URL) ? $apple : '';

    if ($intro === '') {
        $intro = "Ça y est, le grand jour est arrivé : Ablony est enfin disponible ! "
            . "L'app de mode et de seconde main pensée pour l'Afrique t'attend. "
            . "Télécharge-la et profite de tes avantages de membre fondateur.";
    }

    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeIntro = nl2br(htmlspecialchars($intro, ENT_QUOTES, 'UTF-8'));
    $greetingHtml = $safeName !== '' ? "Bonjour {$safeName},<br><br>" : '';

    // Boutons de téléchargement (conditionnels)
    $btnStyle = 'display:inline-block;background-color:#2385AE;color:#ffffff;'
        . 'text-decoration:none;font-size:14px;font-weight:600;padding:14px 24px;'
        . "border-radius:10px;margin:6px;font-family:'Be Vietnam Pro',Arial,sans-serif;";
    $buttonsHtml = '';
    if ($play !== '') {
        $safePlay = htmlspecialchars($play, ENT_QUOTES, 'UTF-8');
        $buttonsHtml .= "<a href=\"{$safePlay}\" style=\"{$btnStyle}\">Google Play</a>";
    }
    if ($apple !== '') {
        $safeApple = htmlspecialchars($apple, ENT_QUOTES, 'UTF-8');
        $buttonsHtml .= "<a href=\"{$safeApple}\" style=\"{$btnStyle}\"> App Store</a>";
    }
    if ($buttonsHtml === '') {
        // Aucun lien fourni : on adapte le message plutôt que de laisser un vide.
        $buttonsHtml = '<p style="margin:0;font-size:14px;color:#64748b;'
            . "font-family:'Be Vietnam Pro',Arial,sans-serif;\">"
            . 'Les liens de téléchargement arrivent très bientôt — reste connecté(e) !</p>';
    }

    // Liens texte (version texte de l'email)
    $linksText = '';
    if ($play !== '') {
        $linksText .= "Google Play : {$play}\n";
    }
    if ($apple !== '') {
        $linksText .= "App Store : {$apple}\n";
    }
    if ($linksText === '') {
        $linksText = "Les liens de téléchargement arrivent très bientôt.\n";
    }

    $fontHead = email_font_head();

    $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ablony est disponible</title>
{$fontHead}
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:'Be Vietnam Pro',Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width:520px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(15,23,42,0.08);" cellpadding="0" cellspacing="0">
          <tr>
            <td style="background-color:#2385AE;padding:28px 32px;text-align:center;">
              <img src="{$logoUrl}" alt="Ablony" width="140" style="display:block;margin:0 auto;border:0;">
            </td>
          </tr>
          <tr>
            <td style="padding:36px 32px 8px;text-align:center;">
              <div style="font-size:40px;line-height:1;margin-bottom:12px;">🎉</div>
              <h1 style="margin:0 0 16px;font-size:22px;color:#0f172a;font-family:'Be Vietnam Pro',Arial,sans-serif;">Ablony est disponible !</h1>
              <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#64748b;text-align:left;font-family:'Be Vietnam Pro',Arial,sans-serif;">
                {$greetingHtml}{$safeIntro}
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:0 32px 36px;text-align:center;">
              {$buttonsHtml}
            </td>
          </tr>
          <tr>
            <td style="padding:20px 32px;background-color:#f8fafc;text-align:center;border-top:1px solid #e2e8f0;">
              <p style="margin:0;font-size:12px;color:#94a3b8;font-family:'Be Vietnam Pro',Arial,sans-serif;">Tu reçois cet email car tu t'es inscrit(e) sur ablony.net.<br>L'équipe Ablony</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    $text = ($name !== '' ? "Bonjour {$name},\n\n" : '')
        . "Ablony est disponible !\n\n"
        . $intro . "\n\n"
        . $linksText . "\n"
        . "L'équipe Ablony";

    return ['html' => $html, 'text' => $text];
}
