<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - HEADER TEMPLATE (includes/header.php)
 * ==============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';

$pdo = get_db_connection();
$user = current_user();
$cart = get_cart();
$cartCount = array_sum(array_column($cart, 'quantity'));

// Fetch unread notifications for bell
$notifications = [];
$unreadNotifCount = 0;
try {
    if ($user) {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? OR (user_id IS NULL AND vehicle_number IS NULL) ORDER BY created_at DESC LIMIT 8");
        $stmt->execute([$user['id']]);
        $notifications = $stmt->fetchAll();

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR (user_id IS NULL AND vehicle_number IS NULL)) AND is_read = 0");
        $countStmt->execute([$user['id']]);
        $unreadNotifCount = (int)$countStmt->fetchColumn();
    } else {
        // Guest mode: show recent public garage updates
        $stmt = $pdo->query("SELECT * FROM notifications WHERE user_id IS NULL ORDER BY created_at DESC LIMIT 6");
        $notifications = $stmt->fetchAll();
        $unreadNotifCount = count($notifications);
    }
} catch (Exception $e) {
    // Graceful fallback if table is empty or error
}

$pageTitle = $pageTitle ?? APP_NAME . ' • ' . APP_TAGLINE;
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  
  <!-- Favicon / SVG Logo -->
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo.svg">
  
  <!-- Retro CSS Stylesheets -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/retro-main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tickets.css">
</head>
<body>

  <!-- Top Racing Tricolor Stripe -->
  <div class="racing-stripe-bar"></div>

  <!-- Retro Navigation Bar -->
  <header class="retro-navbar">
    <div class="container nav-container">
      
      <!-- Brand Logo & Title -->
      <a href="<?= BASE_URL ?>/index.php" class="brand-logo-wrap" title="<?= APP_NAME ?>">
        <div class="brand-badge-box">1988</div>
        <div>
          <div class="brand-title">SPEED<span>WELL</span></div>
          <span class="brand-sub">RETRO GARAGE & SPEED SHOP</span>
        </div>
      </a>

      <!-- Navigation Links -->
      <ul class="nav-links">
        <li>
          <a href="<?= BASE_URL ?>/index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/parts.php" class="nav-link <?= $currentPage === 'parts.php' || $currentPage === 'part-detail.php' ? 'active' : '' ?>">Spare Parts Store</a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/index.php#book-service" class="nav-link">Book Service</a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/track.php" class="nav-link <?= $currentPage === 'track.php' ? 'active' : '' ?>">Track Vehicle</a>
        </li>
        <?php if (is_admin()): ?>
          <li>
            <a href="<?= BASE_URL ?>/admin/index.php" class="nav-link text-amber" style="border:1px dashed var(--color-dash-amber); padding:4px 8px;">
              Admin Panel
            </a>
          </li>
        <?php endif; ?>
      </ul>

      <!-- Navbar Actions (Cart, Notifications, Account) -->
      <div class="nav-actions">
        
        <!-- Live Notification Bell -->
        <div class="notif-bell-wrap">
          <button type="button" class="notif-bell-btn" title="Garage Alerts & Job Updates" aria-label="Notifications">
            🔔
            <?php if ($unreadNotifCount > 0): ?>
              <span class="notif-badge-dot"><?= $unreadNotifCount ?></span>
            <?php endif; ?>
          </button>
          
          <div class="notif-dropdown">
            <div class="notif-header">
              <h5>[GARAGE NOTIFICATIONS]</h5>
              <?php if ($user && $unreadNotifCount > 0): ?>
                <a href="#" class="mark-all-read-btn text-amber" style="font-size:0.75rem; text-decoration:underline;">Mark all read</a>
              <?php endif; ?>
            </div>
            <ul class="notif-list">
              <?php if (empty($notifications)): ?>
                <li class="notif-empty">No active notifications</li>
              <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                  <li class="notif-item <?= empty($n['is_read']) ? 'unread' : '' ?>">
                    <span class="notif-title">
                      <?= e($n['title']) ?>
                      <?php if (!empty($n['vehicle_number'])): ?>
                        <span class="retro-badge" style="font-size:0.65rem; background:#000; color:#E8A317; margin-left:4px;"><?= e($n['vehicle_number']) ?></span>
                      <?php endif; ?>
                    </span>
                    <p style="margin:4px 0; color:#ddd; font-size:0.82rem;"><?= e($n['message']) ?></p>
                    <span class="notif-time"><?= format_date($n['created_at']) ?></span>
                  </li>
                <?php endforeach; ?>
              <?php endif; ?>
            </ul>
            <div style="background:#111; padding:8px 12px; text-align:center; border-top:1px solid #333;">
              <a href="<?= BASE_URL ?>/track.php" style="font-family:var(--font-mono); font-size:0.78rem; color:var(--color-dash-amber);">
                → Track vehicle by registration number
              </a>
            </div>
          </div>
        </div>

        <!-- Shopping Cart Button with Item Counter -->
        <a href="<?= BASE_URL ?>/cart.php" class="nav-cart-btn" title="Shopping Cart">
          <span>🛒 CART</span>
          <span class="cart-count-badge"><?= $cartCount ?></span>
        </a>

        <!-- User Profile / Auth State -->
        <?php if ($user): ?>
          <div class="user-menu-pill">
            <span style="color:var(--color-dash-amber);">👤 <?= e($user['name']) ?></span>
            <a href="<?= BASE_URL ?>/logout.php" style="color:var(--color-racing-red); text-decoration:underline; font-size:0.8rem; margin-left:4px;">Logout</a>
          </div>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/login.php" class="retro-btn retro-btn-sm retro-btn-amber" style="padding:6px 12px; font-size:0.88rem;">
            Sign In
          </a>
        <?php endif; ?>

        <!-- Mobile Menu Toggle Button -->
        <button type="button" class="mobile-nav-toggle" aria-label="Toggle navigation">
          ☰
        </button>

      </div>
    </div>
  </header>

  <!-- Flash Messages Banner -->
  <div class="container">
    <?= render_flashes() ?>
  </div>
