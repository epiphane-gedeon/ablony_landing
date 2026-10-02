<?php
/**
 * Page d'envoi de campagnes email — Ablony.
 *
 * Réutilise le service mail du site (mail_templates.php, mail() natif). Accès
 * protégé par le même mot de passe que la waitlist (WAITLIST_PASSWORD, en ?pwd=).
 *
 * - GET  ?pwd=...            → l'interface (gauche : template + variables +
 *                             destinataires ; droite : aperçu rendu serveur).
 * - POST action=preview      → renvoie le HTML rendu (pour l'aperçu).
 * - POST action=send         → envoie 1 mail par destinataire (max 10),
 *                             renvoie le résultat par destinataire.
 *
 * Envoi INDIVIDUEL (un mail() par personne, jamais un envoi groupé) : c'est ce
 * qui limite le classement en spam, avec le plafond de 10 destinataires.
 */

require_once __DIR__ . '/mail_templates.php';

// --- Charger .env (même méthode que get_waitlist.php) ---
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($k, $v) = explode('=', $line, 2);
            putenv(trim($k) . '=' . trim($v));
        }
    }
}
$admin_password = getenv('WAITLIST_PASSWORD') ?: 'admin123';

$pwd = $_POST['pwd'] ?? $_GET['pwd'] ?? '';
$authorized = is_string($pwd) && hash_equals((string) $admin_password, $pwd);

$MAX_RECIPIENTS = 10;

// =====================================================================
// API (POST)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!$authorized) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Mot de passe invalide']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $templateId = $_POST['template'] ?? '';
    $templates = email_templates();
    if (!isset($templates[$templateId])) {
        echo json_encode(['success' => false, 'error' => 'Template inconnu']);
        exit;
    }

    $vars = (isset($_POST['vars']) && is_array($_POST['vars'])) ? $_POST['vars'] : [];

    if ($action === 'preview') {
        try {
            $rendered = render_email_template($templateId, $vars);
            echo json_encode([
                'success' => true,
                'html' => $rendered['html'],
                'subject' => $templates[$templateId]['subject'],
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'send') {
        $recipientsIn = (isset($_POST['recipients']) && is_array($_POST['recipients']))
            ? $_POST['recipients'] : [];

        // Nettoyage + validation, plafonné à MAX_RECIPIENTS.
        $recipients = [];
        foreach ($recipientsIn as $r) {
            $email = trim(strtolower((string) ($r['email'] ?? '')));
            if ($email === '') {
                continue;
            }
            $recipients[] = [
                'email' => $email,
                'name' => trim((string) ($r['name'] ?? '')),
            ];
        }

        if (count($recipients) === 0) {
            echo json_encode(['success' => false, 'error' => 'Aucun destinataire.']);
            exit;
        }
        if (count($recipients) > $MAX_RECIPIENTS) {
            echo json_encode([
                'success' => false,
                'error' => "Maximum {$MAX_RECIPIENTS} destinataires par envoi.",
            ]);
            exit;
        }

        $subject = $templates[$templateId]['subject'];
        $results = [];
        foreach ($recipients as $r) {
            if (!filter_var($r['email'], FILTER_VALIDATE_EMAIL)) {
                $results[] = ['email' => $r['email'], 'ok' => false, 'error' => 'Email invalide'];
                continue;
            }
            // Le prénom est propre à chaque destinataire ; les autres variables
            // sont communes.
            $vv = $vars;
            $vv['name'] = $r['name'];
            try {
                $rendered = render_email_template($templateId, $vv);
                $ok = send_email($r['email'], $subject, $rendered['html'], $rendered['text']);
                $results[] = [
                    'email' => $r['email'],
                    'ok' => $ok,
                    'error' => $ok ? null : "Échec de l'envoi",
                ];
            } catch (Throwable $e) {
                $results[] = ['email' => $r['email'], 'ok' => false, 'error' => $e->getMessage()];
            }
        }
        echo json_encode(['success' => true, 'results' => $results]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    exit;
}

// =====================================================================
// UI (GET) — 404 si mot de passe absent/incorrect (comme get_waitlist.php)
// =====================================================================
if (!$authorized) {
    http_response_code(404);
    echo '<h1>Not Found</h1><p>The requested URL was not found on this server.</p>';
    exit;
}

$templatesJson = json_encode(email_templates(), JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Envoi de campagnes — Ablony</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Be Vietnam Pro', system-ui, sans-serif; background: #f5f7fa; color: #0f172a; }
  header { background: #2385AE; color: #fff; padding: 16px 24px; font-weight: 600; font-size: 18px; }
  .wrap { display: flex; gap: 20px; padding: 20px; align-items: flex-start; flex-wrap: wrap; }
  .col { flex: 1 1 420px; min-width: 320px; }
  .card { background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 1px 8px rgba(15,23,42,.06); margin-bottom: 16px; }
  h2 { font-size: 14px; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin-bottom: 12px; }
  label { display: block; font-size: 13px; color: #475569; margin: 10px 0 4px; }
  input, textarea, select { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font: inherit; }
  textarea { min-height: 90px; resize: vertical; }
  .recipient { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; }
  .recipient input { flex: 1; }
  .recipient .email { flex: 2; }
  .btn { background: #2385AE; color: #fff; border: 0; border-radius: 8px; padding: 12px 18px; font: inherit; font-weight: 600; cursor: pointer; }
  .btn:disabled { opacity: .5; cursor: not-allowed; }
  .btn-quiet { background: #e2e8f0; color: #0f172a; padding: 8px 12px; font-size: 13px; }
  .btn-x { background: transparent; color: #ef4444; border: 0; cursor: pointer; font-size: 18px; padding: 0 6px; }
  .hint { font-size: 12px; color: #94a3b8; margin-top: 6px; }
  iframe { width: 100%; height: 720px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
  .results { margin-top: 12px; font-size: 14px; }
  .results .ok { color: #16a34a; }
  .results .ko { color: #ef4444; }
  .subject { font-size: 13px; color: #475569; margin-bottom: 8px; }
  .row-actions { display: flex; gap: 8px; align-items: center; justify-content: space-between; margin-top: 10px; }
</style>
</head>
<body>
  <header>📧 Envoi de campagnes — Ablony</header>
  <div class="wrap">
    <!-- GAUCHE : configuration -->
    <div class="col">
      <div class="card">
        <h2>Template</h2>
        <select id="template"></select>
      </div>

      <div class="card">
        <h2>Variables</h2>
        <div id="globalVars"></div>
      </div>

      <div class="card">
        <h2>Destinataires (max <?php echo $MAX_RECIPIENTS; ?>)</h2>
        <div id="recipients"></div>
        <div class="row-actions">
          <button class="btn-quiet" id="addRecipient" type="button">+ Ajouter un destinataire</button>
          <span class="hint" id="recipientCount"></span>
        </div>
        <p class="hint">Le prénom est optionnel et personnalise « Bonjour X ». Envoi individuel, un mail par personne.</p>
      </div>

      <div class="card">
        <button class="btn" id="sendBtn" type="button">Envoyer</button>
        <div class="results" id="results"></div>
      </div>
    </div>

    <!-- DROITE : aperçu -->
    <div class="col">
      <div class="card">
        <h2>Aperçu</h2>
        <div class="subject" id="subjectPreview"></div>
        <iframe id="preview" title="Aperçu de l'email"></iframe>
        <p class="hint">L'aperçu utilise le prénom du 1er destinataire (sinon générique). Boutons masqués si le lien manque.</p>
      </div>
    </div>
  </div>

<script>
const TEMPLATES = <?php echo $templatesJson; ?>;
const PWD = <?php echo json_encode($pwd); ?>;
const MAX = <?php echo (int) $MAX_RECIPIENTS; ?>;

const $ = (id) => document.getElementById(id);
const selectEl = $('template');
const globalVarsEl = $('globalVars');
const recipientsEl = $('recipients');

// Remplir le sélecteur de templates
for (const [id, t] of Object.entries(TEMPLATES)) {
  const opt = document.createElement('option');
  opt.value = id; opt.textContent = t.label;
  selectEl.appendChild(opt);
}

let currentId = Object.keys(TEMPLATES)[0] || '';

function currentTemplate() { return TEMPLATES[currentId]; }
function globalVarDefs() { return (currentTemplate().variables || []).filter(v => v.scope !== 'recipient'); }
function hasRecipientName() { return (currentTemplate().variables || []).some(v => v.scope === 'recipient' && v.key === 'name'); }

// --- Champs des variables globales ---
function renderGlobalVars() {
  globalVarsEl.innerHTML = '';
  for (const v of globalVarDefs()) {
    const label = document.createElement('label');
    label.textContent = v.label; label.htmlFor = 'v_' + v.key;
    const field = v.type === 'textarea' ? document.createElement('textarea') : document.createElement('input');
    if (v.type !== 'textarea') field.type = (v.type === 'url' ? 'url' : 'text');
    field.id = 'v_' + v.key; field.dataset.key = v.key;
    field.placeholder = v.placeholder || '';
    field.value = v.default || '';
    field.addEventListener('input', debouncedPreview);
    globalVarsEl.appendChild(label);
    globalVarsEl.appendChild(field);
  }
}

function globalVarValues() {
  const vals = {};
  globalVarsEl.querySelectorAll('[data-key]').forEach(el => { vals[el.dataset.key] = el.value; });
  return vals;
}

// --- Destinataires ---
function addRecipientRow(email = '', name = '') {
  if (recipientsEl.children.length >= MAX) return;
  const row = document.createElement('div');
  row.className = 'recipient';
  const em = document.createElement('input');
  em.type = 'email'; em.placeholder = 'email@exemple.com'; em.className = 'email'; em.value = email;
  em.addEventListener('input', () => { if (recipientsEl.firstChild === row) debouncedPreview(); });
  row.appendChild(em);
  if (hasRecipientName()) {
    const nm = document.createElement('input');
    nm.type = 'text'; nm.placeholder = 'Prénom (opt.)'; nm.className = 'name'; nm.value = name;
    nm.addEventListener('input', () => { if (recipientsEl.firstChild === row) debouncedPreview(); });
    row.appendChild(nm);
  }
  const x = document.createElement('button');
  x.type = 'button'; x.className = 'btn-x'; x.textContent = '×';
  x.addEventListener('click', () => { row.remove(); updateRecipientCount(); debouncedPreview(); });
  row.appendChild(x);
  recipientsEl.appendChild(row);
  updateRecipientCount();
}

function recipientValues() {
  const list = [];
  recipientsEl.querySelectorAll('.recipient').forEach(row => {
    const email = row.querySelector('.email')?.value.trim() || '';
    const name = row.querySelector('.name')?.value.trim() || '';
    if (email !== '') list.push({ email, name });
  });
  return list;
}

function updateRecipientCount() {
  const n = recipientsEl.children.length;
  $('recipientCount').textContent = `${n} / ${MAX}`;
  $('addRecipient').disabled = n >= MAX;
}

// --- Aperçu (rendu serveur) ---
let previewTimer = null;
function debouncedPreview() { clearTimeout(previewTimer); previewTimer = setTimeout(refreshPreview, 350); }

async function refreshPreview() {
  const vars = globalVarValues();
  // Prénom du 1er destinataire pour l'aperçu (sinon générique).
  if (hasRecipientName()) {
    const first = recipientValues()[0];
    vars.name = first ? first.name : '';
  }
  const fd = new FormData();
  fd.append('action', 'preview');
  fd.append('pwd', PWD);
  fd.append('template', currentId);
  for (const [k, val] of Object.entries(vars)) fd.append('vars[' + k + ']', val);
  try {
    const r = await fetch('campagne.php', { method: 'POST', body: fd });
    const data = await r.json();
    if (data.success) {
      $('preview').srcdoc = data.html;
      $('subjectPreview').textContent = 'Objet : ' + data.subject;
    } else {
      $('subjectPreview').textContent = 'Erreur aperçu : ' + (data.error || '');
    }
  } catch (e) {
    $('subjectPreview').textContent = 'Erreur réseau (aperçu).';
  }
}

// --- Envoi ---
async function send() {
  const recipients = recipientValues();
  const resultsEl = $('results');
  if (recipients.length === 0) { resultsEl.innerHTML = '<span class="ko">Ajoute au moins un destinataire.</span>'; return; }
  if (recipients.length > MAX) { resultsEl.innerHTML = `<span class="ko">Maximum ${MAX} destinataires.</span>`; return; }
  if (!confirm(`Envoyer à ${recipients.length} destinataire(s) ?`)) return;

  const btn = $('sendBtn');
  btn.disabled = true; btn.textContent = 'Envoi…';
  resultsEl.innerHTML = '';

  const fd = new FormData();
  fd.append('action', 'send');
  fd.append('pwd', PWD);
  fd.append('template', currentId);
  const vars = globalVarValues();
  for (const [k, val] of Object.entries(vars)) fd.append('vars[' + k + ']', val);
  recipients.forEach((r, i) => {
    fd.append(`recipients[${i}][email]`, r.email);
    fd.append(`recipients[${i}][name]`, r.name);
  });

  try {
    const r = await fetch('campagne.php', { method: 'POST', body: fd });
    const data = await r.json();
    if (!data.success) {
      resultsEl.innerHTML = `<span class="ko">${data.error || 'Échec.'}</span>`;
    } else {
      resultsEl.innerHTML = data.results.map(res =>
        res.ok
          ? `<div class="ok">✓ ${res.email}</div>`
          : `<div class="ko">✗ ${res.email} — ${res.error || 'échec'}</div>`
      ).join('');
    }
  } catch (e) {
    resultsEl.innerHTML = '<span class="ko">Erreur réseau (envoi).</span>';
  } finally {
    btn.disabled = false; btn.textContent = 'Envoyer';
  }
}

// --- Init ---
function loadTemplate() {
  currentId = selectEl.value || Object.keys(TEMPLATES)[0];
  renderGlobalVars();
  recipientsEl.innerHTML = '';
  addRecipientRow();
  refreshPreview();
}

selectEl.addEventListener('change', loadTemplate);
$('addRecipient').addEventListener('click', () => addRecipientRow());
$('sendBtn').addEventListener('click', send);

selectEl.value = currentId;
loadTemplate();
</script>
</body>
</html>
