<?php
/**
 * ==============================================================
 * Customer Account & Order History (profile.php)
 * ==============================================================
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();

$pdo = get_db_connection();
$user = current_user();

// Fetch Customer Orders
$ordersStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$ordersStmt->execute([$user['id']]);
$customerOrders = $ordersStmt->fetchAll();

// Fetch Customer Bookings by Phone or Name
$bookingsStmt = $pdo->prepare("SELECT * FROM bookings WHERE phone = ? OR customer_name = ? ORDER BY created_at DESC");
$bookingsStmt->execute([$user['phone'], $user['name']]);
$customerBookings = $bookingsStmt->fetchAll();

$pageTitle = 'Customer Profile • ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 40px 20px 80px 20px;">
  
  <!-- Profile Header Banner -->
  <div style="background:var(--color-oil-slate); border:2px solid #000; box-shadow:var(--shadow-hard); padding:24px; margin-bottom:30px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
    <div>
      <div style="font-family:var(--font-mono); font-size:0.8rem; color:var(--color-dash-amber); font-weight:700;">[VERIFIED CUSTOMER PROFILE]</div>
      <h1 style="font-size:2.2rem; margin:0;"><?= e($user['name']) ?></h1>
      <div style="font-family:var(--font-mono); font-size:0.85rem; color:var(--color-muted-grey); margin-top:4px;">
        Email: <?= e($user['email']) ?> • Phone: <?= e($user['phone'] ?: '—') ?>
      </div>
    </div>

    <div style="display:flex; gap:10px;">
      <a href="<?= BASE_URL ?>/parts.php" class="retro-btn retro-btn-sm retro-btn-amber">
        + Buy Spare Parts
      </a>
      <a href="<?= BASE_URL ?>/index.php#book-service" class="retro-btn retro-btn-sm retro-btn-teal">
        + Book Service Bay
      </a>
      <a href="<?= BASE_URL ?>/logout.php" class="retro-btn retro-btn-sm retro-btn-dark">
        Sign Out
      </a>
    </div>
  </div>

  <!-- Orders History Section -->
  <div class="retro-card" style="margin-bottom:34px;">
    <div class="retro-card-header">
      <h3 class="retro-card-title text-amber" style="font-size:1.4rem;">📦 MY SPARE PARTS ORDERS & INVOICES</h3>
      <span class="retro-badge" style="background:#000; color:var(--color-dash-amber);"><?= count($customerOrders) ?> Orders</span>
    </div>
    <div class="retro-card-body">
      <?php if (empty($customerOrders)): ?>
        <p style="font-family:var(--font-mono); color:var(--color-muted-grey); text-align:center; padding:20px;">
          No part orders found under this account yet.
        </p>
      <?php else: ?>
        <div class="admin-table-wrap" style="box-shadow:none; margin:0;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Invoice Code</th>
                <th>Order Date</th>
                <th>Vehicle Reg</th>
                <th>Payment</th>
                <th>Order Status</th>
                <th>Grand Total</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($customerOrders as $ord): ?>
                <tr>
                  <td>
                    <strong style="font-family:var(--font-mono); color:var(--color-dash-amber); font-size:1rem;"><?= e($ord['order_code']) ?></strong>
                  </td>
                  <td style="font-family:var(--font-mono); font-size:0.85rem;"><?= format_date($ord['created_at']) ?></td>
                  <td style="font-family:var(--font-mono); font-weight:700; color:var(--color-racing-red);"><?= e($ord['vehicle_number'] ?: '—') ?></td>
                  <td>
                    <span class="retro-badge <?= $ord['payment_status'] === 'Paid' ? 'badge-stock-in' : 'badge-stock-low' ?>">
                      <?= e($ord['payment_status']) ?>
                    </span>
                  </td>
                  <td>
                    <span class="retro-badge badge-status"><?= e($ord['order_status']) ?></span>
                  </td>
                  <td style="font-family:var(--font-mono); font-weight:700; color:var(--color-manual-cream);">
                    <?= format_price($ord['grand_total']) ?>
                  </td>
                  <td>
                    <a href="<?= BASE_URL ?>/order-success.php?order=<?= urlencode($ord['order_code']) ?>" class="retro-btn retro-btn-sm retro-btn-cream">
                      View Retro Invoice 🧾
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Bookings History Section -->
  <div class="retro-card">
    <div class="retro-card-header">
      <h3 class="retro-card-title text-teal" style="font-size:1.4rem;">🛠️ MY SERVICE BAY APPOINTMENTS</h3>
      <span class="retro-badge" style="background:#000; color:var(--color-gauge-teal);"><?= count($customerBookings) ?> Bookings</span>
    </div>
    <div class="retro-card-body">
      <?php if (empty($customerBookings)): ?>
        <p style="font-family:var(--font-mono); color:var(--color-muted-grey); text-align:center; padding:20px;">
          No service bookings registered under this phone/name yet.
        </p>
      <?php else: ?>
        <div class="admin-table-wrap" style="box-shadow:none; margin:0;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Ticket Code</th>
                <th>Vehicle Number</th>
                <th>Service Date</th>
                <th>Reported Symptoms</th>
                <th>Status</th>
                <th>Live Tracker</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($customerBookings as $bk): ?>
                <tr>
                  <td>
                    <strong style="font-family:var(--font-mono); color:var(--color-racing-red); font-size:1rem;"><?= e($bk['ticket_code']) ?></strong>
                  </td>
                  <td style="font-family:var(--font-mono); font-weight:700; color:var(--color-manual-cream); font-size:1.05rem;">
                    <?= e($bk['vehicle_number']) ?>
                  </td>
                  <td style="font-family:var(--font-mono); font-size:0.85rem;">
                    <?= $bk['preferred_date'] ? date('d M Y', strtotime($bk['preferred_date'])) : format_date($bk['created_at']) ?>
                  </td>
                  <td style="font-size:0.85rem; max-width:280px;"><?= e($bk['service_note']) ?></td>
                  <td>
                    <span class="retro-badge badge-status-<?= strtolower(str_replace(' ', '-', $bk['status'])) ?>">
                      <?= e($bk['status']) ?>
                    </span>
                  </td>
                  <td>
                    <a href="<?= BASE_URL ?>/track.php?query=<?= urlencode($bk['ticket_code']) ?>" class="retro-btn retro-btn-sm retro-btn-teal">
                      Track Live Progress 📟
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
