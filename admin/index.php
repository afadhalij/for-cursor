<?php
/**
 * Inganzo Ngari — Admin Panel
 * Single-file admin: login, edit content, upload logo / background, change password.
 *
 * Storage:
 *   ../content.json          editable site content (JSON)
 *   credentials.json         password_hash for the admin user
 *   ../assets/logo.webp      site logo (replaceable via upload)
 *   ../assets/troupe.webp    background photo (replaceable via upload)
 */

declare(strict_types=1);

/* ---------- Paths ---------- */
const ROOT          = __DIR__ . '/..';
const CONTENT_FILE  = ROOT . '/content.json';
const CREDS_FILE    = __DIR__ . '/credentials.json';
const ASSETS_DIR    = ROOT . '/assets';
const SLIDES_DIR    = ASSETS_DIR . '/slides';
const SLIDES_REL    = 'assets/slides';
const LOGO_FILE     = ASSETS_DIR . '/logo.webp';

/* ---------- Session ---------- */
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

/* ---------- Helpers ---------- */
function load_json(string $path, array $fallback = []): array {
    if (!is_file($path)) return $fallback;
    $raw = @file_get_contents($path);
    if ($raw === false) return $fallback;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $fallback;
}

function save_json(string $path, array $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $tmp = $path . '.tmp';
    $payload = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    if ($payload === false) return false;
    if (@file_put_contents($tmp, $payload) === false) return false;
    return @rename($tmp, $path);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): bool {
    return !empty($_POST['csrf'])
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

function is_logged_in(): bool {
    return !empty($_SESSION['admin_authed']);
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $to): void {
    header('Location: ' . $to);
    exit;
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Default content used if content.json is missing or partial. */
function default_content(): array {
    return [
        'launchDate' => '2026-06-01T19:00:00+02:00',
        'email' => 'info@inganzongari.com',
        'social' => [
            'instagram' => '#', 'facebook' => '#', 'youtube' => '#', 'x' => '#',
        ],
        'slideshow' => [
            'intervalMs' => 6000,
            'fadeMs'     => 2200,
            'slides'     => [
                'assets/slides/01-troupe-portrait.webp',
                'assets/slides/02-women-yellow.webp',
                'assets/slides/03-women-pink.webp',
                'assets/slides/04-warriors-leap.webp',
                'assets/slides/05-women-purple.webp',
            ],
        ],
        'i18n' => [
            'en' => [
                'eyebrow' => 'Coming Soon',
                'tagline' => 'Where tradition meets the stage',
                'subtitle' => 'A celebration of Rwandan rhythm, dance and heritage — soon to be unveiled.',
                'days' => 'Days', 'hours' => 'Hours', 'minutes' => 'Minutes', 'seconds' => 'Seconds',
                'launchLabel' => 'Curtain rises',
                'launched' => 'We are live. Karibu!',
                'rights' => 'All rights reserved',
            ],
            'rw' => [
                'eyebrow' => 'Bizahita Bigaragara',
                'tagline' => "Aho umuco uhurira n'urubuga",
                'subtitle' => "Ibirori by'umuco nyarwanda, imbyino n'umurage — bigiye gufungurwa.",
                'days' => 'Iminsi', 'hours' => 'Amasaha', 'minutes' => 'Iminota', 'seconds' => 'Amasegonda',
                'launchLabel' => 'Umutaka uzazamuka',
                'launched' => 'Twatangiye. Murakaza neza!',
                'rights' => 'Uburenganzira bwose burabitswe',
            ],
            'fr' => [
                'eyebrow' => 'Bientôt Disponible',
                'tagline' => 'Là où la tradition rencontre la scène',
                'subtitle' => 'Une célébration du rythme, de la danse et du patrimoine rwandais — bientôt dévoilée.',
                'days' => 'Jours', 'hours' => 'Heures', 'minutes' => 'Minutes', 'seconds' => 'Secondes',
                'launchLabel' => 'Lever de rideau',
                'launched' => 'Nous sommes en ligne. Bienvenue !',
                'rights' => 'Tous droits réservés',
            ],
        ],
    ];
}

function deep_merge(array $base, array $over): array {
    foreach ($over as $k => $v) {
        if (is_array($v) && isset($base[$k]) && is_array($base[$k])) {
            $base[$k] = deep_merge($base[$k], $v);
        } else {
            $base[$k] = $v;
        }
    }
    return $base;
}

function get_content(): array {
    return deep_merge(default_content(), load_json(CONTENT_FILE));
}

/**
 * Convert a "datetime-local" string (e.g. "2026-06-01T19:00") into an ISO
 * string with the +02:00 (Africa/Kigali) timezone suffix.
 * If the input already has a timezone, preserve it.
 */
function normalize_launch_date(string $input): string {
    $input = trim($input);
    if ($input === '') return default_content()['launchDate'];
    if (preg_match('/[+-]\d{2}:?\d{2}$|Z$/', $input)) return $input;
    if (strlen($input) === 16) $input .= ':00';
    return $input . '+02:00';
}

function for_datetime_local(string $iso): string {
    try {
        $d = new DateTimeImmutable($iso);
        return $d->setTimezone(new DateTimeZone('Africa/Kigali'))->format('Y-m-d\TH:i');
    } catch (Throwable $e) {
        return '2026-06-01T19:00';
    }
}

/* ---------- File upload handling ---------- */
const ALLOWED_IMAGE_MIME = [
    'image/webp', 'image/png', 'image/jpeg', 'image/gif',
    'image/x-icon', 'image/vnd.microsoft.icon',
];
const MAX_UPLOAD_BYTES = 8 * 1024 * 1024; // 8 MB

function detect_mime(string $tmpPath, string $fallback = ''): string {
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $m = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            if ($m) return (string) $m;
        }
    }
    return $fallback;
}

function ext_for_mime(string $mime): string {
    switch ($mime) {
        case 'image/webp': return 'webp';
        case 'image/png':  return 'png';
        case 'image/jpeg': return 'jpg';
        case 'image/gif':  return 'gif';
        case 'image/x-icon':
        case 'image/vnd.microsoft.icon': return 'ico';
    }
    return 'bin';
}

/** Upload a single file and overwrite the given destination path. */
function handle_image_upload(string $field, string $destination): ?string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'Upload failed (error code ' . (int) $file['error'] . ').';
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return 'File is larger than 8 MB.';
    }
    $mime = detect_mime($file['tmp_name'], (string) ($file['type'] ?? ''));
    if (!in_array($mime, ALLOWED_IMAGE_MIME, true)) {
        return 'Unsupported image type (' . h((string) $mime) . ').';
    }
    if (!is_dir(dirname($destination))) @mkdir(dirname($destination), 0775, true);
    if (!@move_uploaded_file($file['tmp_name'], $destination)) {
        return 'Could not write file to ' . h($destination) . '.';
    }
    return null;
}

/**
 * Upload one or more slide files. Returns an array with:
 *   ['saved' => string[] relative paths, 'errors' => string[]]
 */
function handle_slide_uploads(string $field): array {
    $saved = [];
    $errors = [];
    if (empty($_FILES[$field]) || empty($_FILES[$field]['name'])) {
        return ['saved' => $saved, 'errors' => $errors];
    }
    $files = $_FILES[$field];
    $names = (array) $files['name'];
    if (!is_dir(SLIDES_DIR)) @mkdir(SLIDES_DIR, 0775, true);

    foreach ($names as $i => $origName) {
        $err = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) continue;
        if ($err !== UPLOAD_ERR_OK) {
            $errors[] = 'Slide "' . h((string) $origName) . '" upload error (code ' . (int) $err . ').';
            continue;
        }
        $size = (int) ($files['size'][$i] ?? 0);
        if ($size > MAX_UPLOAD_BYTES) {
            $errors[] = 'Slide "' . h((string) $origName) . '" is larger than 8 MB.';
            continue;
        }
        $tmp = (string) $files['tmp_name'][$i];
        $mime = detect_mime($tmp, (string) ($files['type'][$i] ?? ''));
        if (!in_array($mime, ALLOWED_IMAGE_MIME, true)) {
            $errors[] = 'Slide "' . h((string) $origName) . '" has unsupported type (' . h($mime) . ').';
            continue;
        }
        $ext = ext_for_mime($mime);
        $base = 'slide-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
        $dest = SLIDES_DIR . '/' . $base;
        if (!@move_uploaded_file($tmp, $dest)) {
            $errors[] = 'Could not save slide "' . h((string) $origName) . '".';
            continue;
        }
        $saved[] = SLIDES_REL . '/' . $base;
    }
    return ['saved' => $saved, 'errors' => $errors];
}

/** Restrict any deletion path to the slides directory and prevent directory traversal. */
function safe_slide_path(string $rel): ?string {
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if (strpos($rel, SLIDES_REL . '/') !== 0) return null;
    if (strpos($rel, '..') !== false) return null;
    $abs = realpath(ROOT . '/' . $rel);
    $base = realpath(SLIDES_DIR);
    if ($abs === false || $base === false) return null;
    if (strpos($abs, $base . DIRECTORY_SEPARATOR) !== 0 && $abs !== $base) return null;
    return $abs;
}

/* ---------- Auth actions ---------- */
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    redirect(strtok($_SERVER['REQUEST_URI'], '?'));
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        flash('error', 'Session expired. Please try again.');
        redirect(strtok($_SERVER['REQUEST_URI'], '?'));
    }
    $creds = load_json(CREDS_FILE);
    $hash = $creds['password_hash'] ?? '';
    $password = (string) ($_POST['password'] ?? '');
    if ($hash !== '' && password_verify($password, $hash)) {
        session_regenerate_id(true);
        $_SESSION['admin_authed'] = true;
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
        flash('success', 'Welcome back.');
        redirect(strtok($_SERVER['REQUEST_URI'], '?'));
    }
    flash('error', 'Wrong password.');
    redirect(strtok($_SERVER['REQUEST_URI'], '?'));
}

/* ---------- Authenticated POST actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    if (!csrf_check()) {
        flash('error', 'Invalid session token. Please try again.');
        redirect(strtok($_SERVER['REQUEST_URI'], '?'));
    }

    if ($action === 'save_content') {
        $content = get_content();
        $content['launchDate'] = normalize_launch_date((string) ($_POST['launchDate'] ?? ''));
        $content['email'] = trim((string) ($_POST['email'] ?? ''));
        $social = $_POST['social'] ?? [];
        foreach (['instagram', 'facebook', 'youtube', 'x'] as $k) {
            $val = trim((string) ($social[$k] ?? ''));
            $content['social'][$k] = $val === '' ? '#' : $val;
        }
        $i18n = $_POST['i18n'] ?? [];
        foreach (['en', 'rw', 'fr'] as $lang) {
            if (empty($i18n[$lang]) || !is_array($i18n[$lang])) continue;
            foreach ([
                'eyebrow', 'tagline', 'subtitle',
                'days', 'hours', 'minutes', 'seconds',
                'launchLabel', 'launched', 'rights',
            ] as $key) {
                if (isset($i18n[$lang][$key])) {
                    $content['i18n'][$lang][$key] = trim((string) $i18n[$lang][$key]);
                }
            }
        }

        $errors = [];

        $logoErr = handle_image_upload('logo', LOGO_FILE);
        if ($logoErr) $errors[] = 'Logo: ' . $logoErr;

        // Slideshow timing.
        $interval = (int) ($_POST['slideshow_interval'] ?? 0);
        $fade     = (int) ($_POST['slideshow_fade']     ?? 0);
        if ($interval >= 1500 && $interval <= 30000) {
            $content['slideshow']['intervalMs'] = $interval;
        }
        if ($fade >= 200 && $fade <= 8000) {
            $content['slideshow']['fadeMs'] = $fade;
        }

        // Existing slides: only keep those still posted (handles reorder + delete).
        $keep = [];
        $posted = $_POST['slides'] ?? [];
        if (is_array($posted)) {
            foreach ($posted as $rel) {
                $rel = (string) $rel;
                if (in_array($rel, $content['slideshow']['slides'] ?? [], true)) {
                    $keep[] = $rel;
                }
            }
        }

        // Remove deleted slide files from disk.
        $removed = array_diff($content['slideshow']['slides'] ?? [], $keep);
        foreach ($removed as $rel) {
            $abs = safe_slide_path($rel);
            if ($abs && is_file($abs)) @unlink($abs);
        }

        // Add newly uploaded slides to the end.
        $up = handle_slide_uploads('new_slides');
        if (!empty($up['errors'])) {
            $errors = array_merge($errors, $up['errors']);
        }
        $content['slideshow']['slides'] = array_values(array_merge($keep, $up['saved']));

        if (!save_json(CONTENT_FILE, $content)) {
            $errors[] = 'Could not write content.json. Check that the web server has write permission to ' . h(CONTENT_FILE) . '.';
        }
        if ($errors) {
            flash('error', implode(' ', $errors));
        } else {
            flash('success', 'Content saved. Front-end updated.');
        }
        redirect(strtok($_SERVER['REQUEST_URI'], '?'));
    }

    if ($action === 'change_password') {
        $current = (string) ($_POST['current'] ?? '');
        $new     = (string) ($_POST['new'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');
        $creds   = load_json(CREDS_FILE);
        $hash    = $creds['password_hash'] ?? '';

        if (!password_verify($current, $hash)) {
            flash('error', 'Current password is wrong.');
        } elseif (strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } elseif ($new !== $confirm) {
            flash('error', 'New password and confirmation do not match.');
        } else {
            $creds['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
            unset($creds['_note']);
            if (save_json(CREDS_FILE, $creds)) {
                flash('success', 'Password updated.');
            } else {
                flash('error', 'Could not write credentials.json.');
            }
        }
        redirect(strtok($_SERVER['REQUEST_URI'], '?'));
    }
}

/* ---------- Render ---------- */
$flash = take_flash();
$content = get_content();
$creds   = load_json(CREDS_FILE);
$isDefaultPwd = !empty($creds['password_hash']) && password_verify('admin123', $creds['password_hash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Admin · Inganzo Ngari</title>
<link rel="icon" type="image/webp" href="../assets/logo.webp">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#0a1f17; --bg-2:#0f2a20; --panel:#10261d; --panel-2:#163127;
    --ink:#f5e6c8; --ink-soft:#cfbf9c; --muted:#8c9b8e;
    --gold:#e9b962; --gold-dark:#b8862e; --line:rgba(245,230,200,.14);
    --ok:#3d9b6a; --err:#c0392b;
  }
  *{box-sizing:border-box}
  html,body{margin:0;padding:0;background:var(--bg);color:var(--ink);font-family:'Inter',system-ui,sans-serif;font-size:14px;line-height:1.55}
  a{color:var(--gold)}
  body{min-height:100vh;background:radial-gradient(ellipse at 20% 0%, rgba(44,126,184,.18), transparent 50%),radial-gradient(ellipse at 80% 100%, rgba(192,57,43,.12), transparent 50%),linear-gradient(160deg,#0a2a1f 0%,#06140f 100%)}
  header.topbar{display:flex;align-items:center;justify-content:space-between;padding:18px 28px;border-bottom:1px solid var(--line);background:rgba(0,0,0,.25)}
  .brand{display:flex;align-items:center;gap:12px;font-family:'Cinzel',serif;letter-spacing:.18em;font-weight:700;font-size:13px}
  .brand img{width:36px;height:36px;border-radius:8px;object-fit:cover}
  .brand small{display:block;color:var(--gold);font-size:10px;letter-spacing:.3em}
  .topbar nav{display:flex;align-items:center;gap:14px}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:9px 16px;border-radius:8px;border:1px solid var(--line);background:rgba(245,230,200,.05);color:var(--ink);font-weight:600;font-size:13px;cursor:pointer;text-decoration:none;transition:all .2s}
  .btn:hover{border-color:rgba(245,230,200,.35);background:rgba(245,230,200,.1)}
  .btn-primary{background:var(--gold);color:#1a1a1a;border-color:var(--gold);box-shadow:0 4px 14px rgba(233,185,98,.3)}
  .btn-primary:hover{background:#f3d27a;border-color:#f3d27a;color:#1a1a1a}
  .btn-ghost{background:transparent}
  .btn-danger{color:#ffb4ad;border-color:rgba(192,57,43,.45);background:rgba(192,57,43,.08)}
  .btn-danger:hover{background:rgba(192,57,43,.18);border-color:rgba(192,57,43,.7)}
  main{max-width:980px;margin:0 auto;padding:32px 24px 64px}
  .card{background:linear-gradient(180deg, var(--panel-2), var(--panel));border:1px solid var(--line);border-radius:14px;padding:24px;margin-bottom:18px;box-shadow:0 8px 30px rgba(0,0,0,.3)}
  .card h2{font-family:'Cinzel',serif;font-weight:700;font-size:16px;letter-spacing:.18em;text-transform:uppercase;color:var(--gold);margin:0 0 4px}
  .card p.help{color:var(--muted);margin:0 0 18px;font-size:13px}
  .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
  .grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
  @media (max-width:740px){.grid,.grid-3{grid-template-columns:1fr}}
  label{display:block;font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--ink-soft);margin:0 0 6px}
  input[type=text],input[type=email],input[type=url],input[type=password],input[type=datetime-local],textarea{width:100%;padding:11px 12px;border-radius:8px;border:1px solid var(--line);background:rgba(0,0,0,.3);color:var(--ink);font-family:inherit;font-size:14px;transition:border-color .2s,background .2s}
  input:focus,textarea:focus{outline:none;border-color:var(--gold);background:rgba(0,0,0,.45);box-shadow:0 0 0 3px rgba(233,185,98,.15)}
  textarea{resize:vertical;min-height:64px}
  .field{margin:0 0 14px}
  .lang-tabs{display:flex;gap:6px;margin-bottom:14px;border-bottom:1px solid var(--line);padding-bottom:0}
  .lang-tab{padding:10px 18px;cursor:pointer;background:none;border:none;color:var(--muted);font-weight:700;font-size:12px;letter-spacing:.18em;border-bottom:2px solid transparent;transition:color .2s,border-color .2s}
  .lang-tab.active{color:var(--gold);border-bottom-color:var(--gold)}
  .lang-pane{display:none}
  .lang-pane.active{display:block}
  .upload-row{display:flex;align-items:center;gap:16px}
  .upload-row img{width:64px;height:64px;border-radius:10px;object-fit:cover;border:1px solid var(--line);background:#000}
  .upload-row input[type=file]{flex:1;color:var(--ink-soft);font-size:13px}
  /* Slideshow editor */
  .slides-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-top:8px}
  .slide-card{position:relative;display:block;border:1px solid var(--line);border-radius:10px;overflow:hidden;background:#000;cursor:pointer;transition:border-color .2s,transform .2s}
  .slide-card:hover{border-color:rgba(245,230,200,.4);transform:translateY(-2px)}
  .slide-card img{display:block;width:100%;aspect-ratio:16/10;object-fit:cover}
  .slide-card input[type=checkbox]{position:absolute;top:8px;left:8px;width:20px;height:20px;cursor:pointer;accent-color:var(--gold);z-index:2}
  .slide-card .slide-name{display:block;padding:8px 10px;font-size:11px;color:var(--ink-soft);background:rgba(0,0,0,.55);border-top:1px solid var(--line);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .slide-card .slide-keep{position:absolute;top:8px;right:8px;font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;padding:3px 8px;border-radius:999px;background:rgba(233,185,98,.85);color:#1a1a1a}
  .slide-card .slide-remove{position:absolute;top:8px;right:8px;font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;padding:3px 8px;border-radius:999px;background:rgba(192,57,43,.9);color:#fff;display:none}
  .slide-card:has(input:not(:checked)) img{filter:grayscale(.7) brightness(.4)}
  .slide-card:has(input:not(:checked)) .slide-keep{display:none}
  .slide-card:has(input:not(:checked)) .slide-remove{display:inline-block}
  .alert{padding:12px 16px;border-radius:10px;margin-bottom:18px;font-size:13px;border:1px solid}
  .alert.success{background:rgba(61,155,106,.12);border-color:rgba(61,155,106,.4);color:#a5d8b9}
  .alert.error{background:rgba(192,57,43,.12);border-color:rgba(192,57,43,.4);color:#ffb4ad}
  .alert.warn{background:rgba(233,185,98,.1);border-color:rgba(233,185,98,.4);color:#f3d27a}
  .actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}
  /* Login screen */
  .login-wrap{max-width:380px;margin:8vh auto;padding:0 24px}
  .login-wrap .card{padding:28px}
  .login-wrap h1{font-family:'Cinzel',serif;font-weight:700;font-size:22px;letter-spacing:.18em;text-align:center;color:var(--gold);margin:8px 0 4px}
  .login-wrap .sub{text-align:center;color:var(--muted);font-size:12px;letter-spacing:.18em;text-transform:uppercase;margin:0 0 22px}
  .login-wrap .actions{justify-content:center}
  .login-logo{display:block;width:64px;height:64px;border-radius:14px;margin:0 auto 8px;object-fit:cover;box-shadow:0 0 0 1px rgba(245,230,200,.18),0 0 24px rgba(233,185,98,.18)}
</style>
</head>
<body>

<?php if (!is_logged_in()): ?>
  <main class="login-wrap">
    <?php if ($flash): ?>
      <div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
    <?php endif; ?>
    <div class="card">
      <img class="login-logo" src="../assets/logo.webp" alt="Inganzo Ngari">
      <h1>Inganzo Ngari</h1>
      <p class="sub">Admin Sign-in</p>
      <form method="post" autocomplete="off">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required autofocus>
        </div>
        <div class="actions">
          <button class="btn btn-primary" type="submit">Sign in</button>
        </div>
      </form>
      <p style="margin:18px 0 0;color:var(--muted);font-size:11px;text-align:center;letter-spacing:.1em">
        Default password is <code style="color:var(--gold)">admin123</code>. Change it immediately after first login.
      </p>
    </div>
  </main>
<?php else: ?>
  <header class="topbar">
    <div class="brand">
      <img src="../assets/logo.webp?_=<?= time() ?>" alt="">
      <div>
        Inganzo Ngari
        <small>Admin</small>
      </div>
    </div>
    <nav>
      <a class="btn btn-ghost" href="../" target="_blank" rel="noopener">View site &rarr;</a>
      <form method="post" style="display:inline">
        <input type="hidden" name="action" value="logout">
        <button class="btn btn-danger" type="submit">Log out</button>
      </form>
    </nav>
  </header>

  <main>
    <?php if ($flash): ?>
      <div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($isDefaultPwd): ?>
      <div class="alert warn">You are still using the default password. Please change it in the &ldquo;Account&rdquo; section below.</div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save_content">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

      <!-- Launch + contact -->
      <section class="card">
        <h2>Launch &amp; Contact</h2>
        <p class="help">When the countdown should run out, and how visitors can reach you.</p>
        <div class="grid">
          <div class="field">
            <label for="launchDate">Launch date &amp; time (Africa/Kigali)</label>
            <input type="datetime-local" id="launchDate" name="launchDate" value="<?= h(for_datetime_local($content['launchDate'])) ?>" required>
          </div>
          <div class="field">
            <label for="email">Contact email</label>
            <input type="email" id="email" name="email" value="<?= h($content['email']) ?>" required>
          </div>
        </div>
      </section>

      <!-- Social -->
      <?php
        // Render empty when value is the "#" placeholder so the URL input doesn't
        // reject it. Saving an empty value resets it back to "#" server-side.
        $socialView = function (string $key) use ($content): string {
            $v = (string) ($content['social'][$key] ?? '');
            return $v === '#' ? '' : $v;
        };
      ?>
      <section class="card">
        <h2>Social Links</h2>
        <p class="help">Paste the full URL (e.g. <code>https://instagram.com/inganzongari</code>). Leave blank to hide the link.</p>
        <div class="grid">
          <div class="field">
            <label for="ig">Instagram</label>
            <input type="url" id="ig" name="social[instagram]" value="<?= h($socialView('instagram')) ?>" placeholder="https://instagram.com/...">
          </div>
          <div class="field">
            <label for="fb">Facebook</label>
            <input type="url" id="fb" name="social[facebook]" value="<?= h($socialView('facebook')) ?>" placeholder="https://facebook.com/...">
          </div>
          <div class="field">
            <label for="yt">YouTube</label>
            <input type="url" id="yt" name="social[youtube]" value="<?= h($socialView('youtube')) ?>" placeholder="https://youtube.com/@...">
          </div>
          <div class="field">
            <label for="x">X (Twitter)</label>
            <input type="url" id="x" name="social[x]" value="<?= h($socialView('x')) ?>" placeholder="https://x.com/...">
          </div>
        </div>
      </section>

      <!-- Translations -->
      <section class="card">
        <h2>Text &amp; Translations</h2>
        <p class="help">All visible copy on the site, in English, Kinyarwanda and French. Switch between tabs to edit each language.</p>
        <div class="lang-tabs" role="tablist">
          <button type="button" class="lang-tab active" data-target="pane-en">English</button>
          <button type="button" class="lang-tab" data-target="pane-rw">Kinyarwanda</button>
          <button type="button" class="lang-tab" data-target="pane-fr">Français</button>
        </div>
        <?php foreach (['en' => 'English', 'rw' => 'Kinyarwanda', 'fr' => 'Français'] as $code => $label): ?>
          <div id="pane-<?= h($code) ?>" class="lang-pane <?= $code === 'en' ? 'active' : '' ?>">
            <div class="grid">
              <div class="field">
                <label>Eyebrow (small text above title)</label>
                <input type="text" name="i18n[<?= $code ?>][eyebrow]" value="<?= h((string)($content['i18n'][$code]['eyebrow'] ?? '')) ?>">
              </div>
              <div class="field">
                <label>Tagline</label>
                <input type="text" name="i18n[<?= $code ?>][tagline]" value="<?= h((string)($content['i18n'][$code]['tagline'] ?? '')) ?>">
              </div>
            </div>
            <div class="field">
              <label>Subtitle (longer paragraph)</label>
              <textarea name="i18n[<?= $code ?>][subtitle]"><?= h((string)($content['i18n'][$code]['subtitle'] ?? '')) ?></textarea>
            </div>
            <div class="grid">
              <div class="field">
                <label>"Curtain rises" label</label>
                <input type="text" name="i18n[<?= $code ?>][launchLabel]" value="<?= h((string)($content['i18n'][$code]['launchLabel'] ?? '')) ?>">
              </div>
              <div class="field">
                <label>Message after launch</label>
                <input type="text" name="i18n[<?= $code ?>][launched]" value="<?= h((string)($content['i18n'][$code]['launched'] ?? '')) ?>">
              </div>
            </div>
            <div class="grid-3">
              <div class="field">
                <label>"Days" label</label>
                <input type="text" name="i18n[<?= $code ?>][days]" value="<?= h((string)($content['i18n'][$code]['days'] ?? '')) ?>">
              </div>
              <div class="field">
                <label>"Hours" label</label>
                <input type="text" name="i18n[<?= $code ?>][hours]" value="<?= h((string)($content['i18n'][$code]['hours'] ?? '')) ?>">
              </div>
              <div class="field">
                <label>"Minutes" label</label>
                <input type="text" name="i18n[<?= $code ?>][minutes]" value="<?= h((string)($content['i18n'][$code]['minutes'] ?? '')) ?>">
              </div>
            </div>
            <div class="grid">
              <div class="field">
                <label>"Seconds" label</label>
                <input type="text" name="i18n[<?= $code ?>][seconds]" value="<?= h((string)($content['i18n'][$code]['seconds'] ?? '')) ?>">
              </div>
              <div class="field">
                <label>"All rights reserved" footer text</label>
                <input type="text" name="i18n[<?= $code ?>][rights]" value="<?= h((string)($content['i18n'][$code]['rights'] ?? '')) ?>">
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </section>

      <!-- Logo -->
      <section class="card">
        <h2>Logo</h2>
        <p class="help">Replace the logo shown in the top-left of the public site and admin. PNG, JPG, WebP, or GIF, up to 8 MB. Leave blank to keep the current image.</p>
        <div class="field">
          <div class="upload-row">
            <img src="../assets/logo.webp?_=<?= time() ?>" alt="Current logo">
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif">
          </div>
        </div>
      </section>

      <!-- Slideshow -->
      <?php
        $slides = $content['slideshow']['slides'] ?? [];
        $intervalMs = (int) ($content['slideshow']['intervalMs'] ?? 6000);
        $fadeMs     = (int) ($content['slideshow']['fadeMs']     ?? 2200);
      ?>
      <section class="card">
        <h2>Background Slideshow</h2>
        <p class="help">The faint photos rotating behind the title. Untick a slide to remove it (file is deleted on save). Add new slides at the bottom &mdash; you can select multiple files at once.</p>

        <div class="grid">
          <div class="field">
            <label for="slideshow_interval">Time per slide (ms)</label>
            <input type="number" id="slideshow_interval" name="slideshow_interval" min="1500" max="30000" step="100" value="<?= h((string) $intervalMs) ?>">
          </div>
          <div class="field">
            <label for="slideshow_fade">Cross-fade duration (ms)</label>
            <input type="number" id="slideshow_fade" name="slideshow_fade" min="200" max="8000" step="100" value="<?= h((string) $fadeMs) ?>">
          </div>
        </div>

        <div class="slides-grid">
          <?php if (empty($slides)): ?>
            <p style="color:var(--muted)">No slides yet. Add some below.</p>
          <?php else: ?>
            <?php foreach ($slides as $rel): ?>
              <label class="slide-card">
                <input type="checkbox" name="slides[]" value="<?= h($rel) ?>" checked>
                <img src="../<?= h($rel) ?>?_=<?= time() ?>" alt="Slide">
                <span class="slide-name"><?= h(basename($rel)) ?></span>
                <span class="slide-keep">Keep</span>
                <span class="slide-remove">&#10006; Remove</span>
              </label>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="field" style="margin-top:18px">
          <label for="new_slides">Add new slides</label>
          <input type="file" id="new_slides" name="new_slides[]" accept="image/png,image/jpeg,image/webp,image/gif" multiple>
          <p style="color:var(--muted);font-size:12px;margin:6px 0 0">Up to 8 MB each. Tip: use landscape (16:9 or wider) photos at 1600&times;900 or larger for best results.</p>
        </div>
      </section>

      <div class="actions">
        <a class="btn btn-ghost" href="../" target="_blank" rel="noopener">Preview site</a>
        <button class="btn btn-primary" type="submit">Save changes</button>
      </div>
    </form>

    <!-- Account -->
    <section class="card" style="margin-top:32px">
      <h2>Account</h2>
      <p class="help">Change the password used to sign in to this admin panel. Use at least 8 characters.</p>
      <form method="post" autocomplete="off">
        <input type="hidden" name="action" value="change_password">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <div class="grid-3">
          <div class="field">
            <label>Current password</label>
            <input type="password" name="current" required>
          </div>
          <div class="field">
            <label>New password</label>
            <input type="password" name="new" minlength="8" required>
          </div>
          <div class="field">
            <label>Confirm new password</label>
            <input type="password" name="confirm" minlength="8" required>
          </div>
        </div>
        <div class="actions">
          <button class="btn btn-primary" type="submit">Update password</button>
        </div>
      </form>
    </section>
  </main>

  <script>
    document.querySelectorAll('.lang-tab').forEach(function(tab){
      tab.addEventListener('click', function(){
        var target = tab.getAttribute('data-target');
        document.querySelectorAll('.lang-tab').forEach(function(t){ t.classList.toggle('active', t === tab); });
        document.querySelectorAll('.lang-pane').forEach(function(p){ p.classList.toggle('active', p.id === target); });
      });
    });
  </script>
<?php endif; ?>
</body>
</html>
