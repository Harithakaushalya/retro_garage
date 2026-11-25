<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - ADMIN HEADER (admin/includes/admin-header.php)
 * ==============================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_admin();

$pdo = get_db_connection();
$adminUser = current_user();
$currentAdminScript = basename($_SERVER['SCRIPT_NAME']);
$pageTitle = $pageTitle ?? 'Admin Command Center • ' . APP_NAME;

// Count pending jobs for sidebar badge
$pendingCount = 0;
try {
    $pStmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('Pending', 'Diagnosing', 'In Progress')");
    $pendingCount = (int)$pStmt->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  
  <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo.svg">
  
  <!-- CSS Stylesheets -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/retro-main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tickets.css">
</head>
<body class="admin-body">

  <!-- Racing Stripe -->
  <div class="racing-stripe-bar"></div>

  <div class="admin-wrapper">
    
    <!-- Sidebar -->
    <aside class="admin-sidebar">
      
      <div class="sidebar-header">
        <a href="<?= BASE_URL ?>/admin/index.php" style="display:block;">
          <div class="sidebar-brand">SPEED<span>WELL</span></div>
          <span class="sidebar-badge">WORKSHOP COMMAND</span>
        </a>
      </div>

      <ul class="sidebar-nav">
        <li>
          <a href="<?= BASE_URL ?>/admin/index.php" class="sidebar-link <?= $currentAdminScript === 'index.php' ? 'active' : '' ?>">
            <span>📟</span> Dashboard Overview
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/bookings.php" class="sidebar-link <?= $currentAdminScript === 'bookings.php' ? 'active' : '' ?>">
            <span>🛠️</span> Service Bookings
            <?php if ($pendingCount > 0): ?>
              <span class="retro-badge badge-stock-low" style="margin-left:auto; font-size:0.7rem; padding:1px 6px;">
                <?= $pendingCount ?>
              </span>
            <?php endif; ?>
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/parts.php" class="sidebar-link <?= $currentAdminScript === 'parts.php' ? 'active' : '' ?>">
            <span>📦</span> Parts & Inventory
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/brands.php" class="sidebar-link <?= $currentAdminScript === 'brands.php' ? 'active' : '' ?>">
            <span>🏷️</span> Vehicle Brands
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/orders.php" class="sidebar-link <?= $currentAdminScript === 'orders.php' ? 'active' : '' ?>">
            <span>🧾</span> Customer Orders
          </a>
        </li>
      </ul>

      <div class="sidebar-footer">
        <div style="font-family:var(--font-mono); font-size:0.78rem; color:var(--color-muted-grey); margin-bottom:8px;">
          LOGGED IN CHIEF:<br>
          <strong style="color:var(--color-dash-amber);"><?= e($adminUser['name']) ?></strong>
        </div>
        <div style="display:flex; gap:8px;">
          <a href="<?= BASE_URL ?>/index.php" target="_blank" class="retro-btn retro-btn-sm retro-btn-dark" style="flex:1;">
            Storefront ↗
          </a>
          <a href="<?= BASE_URL ?>/admin/logout.php" class="retro-btn retro-btn-sm retro-btn-primary">
            Logout
          </a>
        </div>
      </div>

    </aside>

    <!-- Main Content Area -->
    <main class="admin-main">
      
      <!-- Topbar -->
      <header class="admin-topbar">
        <h2 class="admin-page-title"><?= e($pageHeading ?? 'COMMAND CENTER') ?></h2>
        
        <div style="display:flex; align-items:center; gap:16px;">
          <div style="font-family:var(--font-mono); font-size:0.82rem; color:var(--color-muted-grey);">
            <?= date('l, d M Y') ?> • <span style="color:var(--color-dash-amber); font-weight:700;">GARAGE SYSTEM ONLINE</span>
          </div>
          <a href="<?= BASE_URL ?>/index.php" class="retro-btn retro-btn-sm retro-btn-amber" target="_blank">
            Live Website ↗
          </a>
        </div>
      </header>

      <!-- Content Area -->
      <div class="admin-content-area">
        <?= render_flashes() ?>
