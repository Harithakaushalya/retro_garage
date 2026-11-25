<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - CUSTOMER AUTHENTICATION (login.php)
 * ==============================================================
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/csrf.php';

$pdo = get_db_connection();
$errors = [];
$activeTab = $_GET['tab'] ?? 'login';

// If already logged in, redirect
if (is_logged_in()) {
    header("Location: " . (is_admin() ? BASE_URL . "/admin/index.php" : BASE_URL . "/profile.php"));
    exit;
}

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Security token expired. Please enter your credentials and sign in again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = 'Email and password are required.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                login_user($user);
                set_flash('success', 'Logged in successfully. Welcome back, ' . $user['name'] . '!');

                if ($user['role'] === 'admin') {
                    header("Location: " . BASE_URL . "/admin/index.php");
                } else {
                    header("Location: " . BASE_URL . "/profile.php");
                }
                exit;
            } else {
                $errors[] = 'Invalid email address or password.';
            }
        }
    }
}

// Handle Register Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    require_csrf();
    $activeTab = 'register';

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $errors[] = 'All registration fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email address already exists. Please sign in.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare("
                INSERT INTO users (name, email, phone, password_hash, role, created_at)
                VALUES (?, ?, ?, ?, 'customer', NOW())
            ");
            $insert->execute([$name, $email, $phone, $hash]);
            $newUserId = (int)$pdo->lastInsertId();

            $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $userStmt->execute([$newUserId]);
            $newUser = $userStmt->fetch();

            login_user($newUser);
            set_flash('success', 'Customer profile registered successfully! Welcome to Speedwell Retro Garage.');
            header("Location: " . BASE_URL . "/profile.php");
            exit;
        }
    }
}

$pageTitle = 'Customer Sign In & Registration • ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 40px 20px 80px 20px;">
  
  <div style="max-width:540px; margin:0 auto; background:var(--color-oil-slate); border:3px solid #000; box-shadow:var(--shadow-hard-lg);">
    
    <div style="background:var(--color-asphalt-dark); padding:20px; border-bottom:2px solid #000; text-align:center;">
      <div class="brand-badge-box" style="display:inline-block; margin-bottom:6px;">RETRO GARAGE AUTH</div>
      <h2 style="font-size:2rem; margin:0; color:var(--color-manual-cream);">SPEEDWELL <span class="text-amber">ACCOUNT</span></h2>
      <p style="font-family:var(--font-mono); font-size:0.8rem; color:var(--color-muted-grey); margin-top:4px;">
        Access your workshop invoices, bay appointments, and vehicle dispatch history.
      </p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="retro-alert retro-alert-danger" style="margin:16px 20px 0 20px;">
        <span class="alert-tag">[ERROR]</span>
        <div><?= implode('<br>', array_map('e', $errors)) ?></div>
      </div>
    <?php endif; ?>

    <div class="modal-tabs">
      <button type="button" class="modal-tab-btn <?= $activeTab === 'login' ? 'active' : '' ?>" data-tab="auth-login">
        Sign In
      </button>
      <button type="button" class="modal-tab-btn <?= $activeTab === 'register' ? 'active' : '' ?>" data-tab="auth-register">
        Create Profile
      </button>
    </div>

    <div style="padding:28px;">
      
      <!-- Login Tab Pane -->
      <div id="auth-login" class="modal-tab-pane" style="display:<?= $activeTab === 'login' ? 'block' : 'none' ?>;">
        <form method="POST" action="">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="login">

          <div class="form-group">
            <label class="form-label">Email Address <span class="required">*</span></label>
            <input type="email" name="email" class="retro-input" required placeholder="customer@demo.local" value="<?= e($_POST['email'] ?? 'customer@demo.local') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Password <span class="required">*</span></label>
            <input type="password" name="password" class="retro-input" required placeholder="customer123" value="customer123">
          </div>

          <button type="submit" class="retro-btn retro-btn-amber retro-btn-block" style="padding:12px; font-size:1.15rem; margin-top:10px;">
            SIGN IN TO ACCOUNT →
          </button>
        </form>

        <div style="background:#111; border:1px dashed #444; padding:12px; margin-top:20px; font-family:var(--font-mono); font-size:0.8rem; color:#aaa;">
          <strong style="color:var(--color-dash-amber);">⚡ DEMO QUICK SIGN-IN:</strong><br>
          Customer: <code>customer@demo.local</code> / <code>customer123</code><br>
          Admin Chief: <code>admin@retrogarage.local</code> / <code>admin123</code>
        </div>
      </div>

      <!-- Register Tab Pane -->
      <div id="auth-register" class="modal-tab-pane" style="display:<?= $activeTab === 'register' ? 'block' : 'none' ?>;">
        <form method="POST" action="">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="register">

          <div class="form-group">
            <label class="form-label">Full Customer Name <span class="required">*</span></label>
            <input type="text" name="name" class="retro-input" required placeholder="e.g. Kasun Perera" value="<?= e($_POST['name'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Email Address <span class="required">*</span></label>
            <input type="email" name="email" class="retro-input" required placeholder="name@domain.com" value="<?= e($_POST['email'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Mobile Phone (WhatsApp Notifications) <span class="required">*</span></label>
            <input type="text" name="phone" class="retro-input" required placeholder="+94 77 123 4567" value="<?= e($_POST['phone'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Create Password <span class="required">*</span></label>
            <input type="password" name="password" class="retro-input" required placeholder="Minimum 6 characters">
          </div>

          <button type="submit" class="retro-btn retro-btn-primary retro-btn-block" style="padding:12px; font-size:1.15rem; margin-top:10px;">
            CREATE CUSTOMER PROFILE →
          </button>
        </form>
      </div>

    </div>

  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
