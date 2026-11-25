<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - ADMIN AUTHENTICATION (admin/login.php)
 * ==============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = get_db_connection();
$errors = [];

// If already admin, redirect to dashboard
if (is_admin()) {
    header("Location: " . BASE_URL . "/admin/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Security token expired. Please enter credentials and submit again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = 'Admin email and password are required.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                login_user($admin);
                set_flash('success', 'Authenticated as Workshop Chief Officer. Dashboard unlocked.');
                header("Location: " . BASE_URL . "/admin/index.php");
                exit;
            } else {
                $errors[] = 'Invalid administrator credentials. Access restricted.';
            }
        }
    }
}

$pageTitle = 'Admin Command Login • ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo.svg">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/retro-main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body style="background:#0e0e11; min-height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center; padding:20px;">

  <div class="racing-stripe-bar" style="position:fixed; top:0; left:0;"></div>

  <div style="width:100%; max-width:440px; background:var(--color-oil-slate); border:3px solid #000; box-shadow:8px 8px 0px #000;">
    
    <!-- Header -->
    <div style="background:var(--color-asphalt-dark); padding:24px; border-bottom:3px solid #000; text-align:center;">
      <div class="brand-badge-box" style="display:inline-block; margin-bottom:8px;">SECURITY GATE</div>
      <h1 style="font-size:2.2rem; margin:0; color:var(--color-manual-cream);">
        SPEED<span class="text-amber">WELL</span>
      </h1>
      <div style="font-family:var(--font-mono); font-size:0.75rem; letter-spacing:2px; color:var(--color-muted-grey); margin-top:4px;">
        WORKSHOP COMMAND CENTER
      </div>
    </div>

    <!-- Body -->
    <div style="padding:28px;">
      
      <?= render_flashes() ?>

      <?php if (!empty($errors)): ?>
        <div class="retro-alert retro-alert-danger" style="margin-bottom:20px;">
          <span class="alert-tag">[DENIED]</span>
          <div><?= implode('<br>', array_map('e', $errors)) ?></div>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <?= csrf_field() ?>

        <div class="form-group">
          <label class="form-label">Chief Officer Email <span class="required">*</span></label>
          <input type="email" name="email" class="retro-input" required placeholder="admin@retrogarage.local" value="admin@retrogarage.local">
        </div>

        <div class="form-group">
          <label class="form-label">Command Password <span class="required">*</span></label>
          <input type="password" name="password" class="retro-input" required placeholder="admin123" value="admin123">
        </div>

        <button type="submit" class="retro-btn retro-btn-primary retro-btn-block" style="padding:14px; font-size:1.15rem; margin-top:10px;">
          UNLOCK COMMAND CENTER →
        </button>
      </form>

      <!-- Demo Credentials Helper Note -->
      <div style="background:#09090b; border:1px dashed #444; padding:12px; margin-top:20px; font-family:var(--font-mono); font-size:0.75rem; color:#aaa;">
        <span style="color:var(--color-dash-amber); font-weight:700;">DEFAULT DEMO CREDENTIALS:</span><br>
        Email: <code>admin@retrogarage.local</code><br>
        Password: <code>admin123</code>
      </div>

      <div style="text-align:center; margin-top:20px;">
        <a href="<?= BASE_URL ?>/index.php" style="font-family:var(--font-mono); font-size:0.8rem; color:var(--color-muted-grey); text-decoration:underline;">
          ← Back to Customer Storefront
        </a>
      </div>

    </div>

  </div>

</body>
</html>
