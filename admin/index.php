<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - ADMIN DASHBOARD (admin/index.php)
 * Analog gauge-style KPI clusters & recent activity telemetry
 * ==============================================================
 */

require_once __DIR__ . '/includes/admin-header.php';

// KPI Computations
$today = date('Y-m-d');

// 1. Total Bookings Today
$bTodayStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = ?");
$bTodayStmt->execute([$today]);
$bookingsToday = (int)$bTodayStmt->fetchColumn();

// 2. Pending Jobs (Pending, Diagnosing, In Progress)
$pJobsStmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('Pending', 'Diagnosing', 'In Progress')");
$pendingJobs = (int)$pJobsStmt->fetchColumn();

// 3. Low Stock Parts (stock_qty <= 8)
$lowStockStmt = $pdo->query("SELECT COUNT(*) FROM parts WHERE stock_qty <= 8");
$lowStockCount = (int)$lowStockStmt->fetchColumn();

// 4. Total Parts Revenue
$revenueStmt = $pdo->query("SELECT SUM(grand_total) AS total_rev, SUM(total_install_cost) AS total_inst FROM orders WHERE payment_status = 'Paid'");
$revData = $revenueStmt->fetch();
$totalRevenue = (float)($revData['total_rev'] ?? 0);
$totalInstallRevenue = (float)($revData['total_inst'] ?? 0);

// Fetch Latest 5 Bookings
$latestBookings = $pdo->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 5")->fetchAll();

// Fetch Latest 5 Orders
$latestOrders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<!-- ============================================================== -->
<!-- ANALOG GAUGE KPI CLUSTER                                       -->
<!-- ============================================================== -->
<div class="gauge-cluster">
  
  <!-- Gauge 1: Today's Bookings (Tachometer Redline Style) -->
  <div class="gauge-card gauge-teal">
    <div class="gauge-header">
      <span class="gauge-title">TODAY'S BAY INTAKE</span>
      <span class="retro-badge" style="background:#000; color:var(--color-gauge-teal);">TACHO</span>
    </div>
    <div class="gauge-visual">
      <!-- SVG Analog Dial -->
      <svg class="gauge-dial-svg" viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="42" fill="#141418" stroke="#333" stroke-width="6"/>
        <path d="M 20 70 A 38 38 0 1 1 80 70" fill="none" stroke="var(--color-gauge-teal)" stroke-width="6" stroke-dasharray="140" stroke-dashoffset="30"/>
        <line x1="50" y1="50" x2="68" y2="35" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
        <circle cx="50" cy="50" r="6" fill="#E8A317"/>
      </svg>
      <div>
        <div class="gauge-metric"><?= $bookingsToday ?></div>
        <div class="gauge-subtext">VEHICLES IN TODAY</div>
      </div>
    </div>
  </div>

  <!-- Gauge 2: Active Pending Jobs (Boost / Warning Gauge) -->
  <div class="gauge-card gauge-amber">
    <div class="gauge-header">
      <span class="gauge-title">ACTIVE REPAIR QUEUE</span>
      <span class="retro-badge" style="background:#000; color:var(--color-dash-amber);">BOOST</span>
    </div>
    <div class="gauge-visual">
      <svg class="gauge-dial-svg" viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="42" fill="#141418" stroke="#333" stroke-width="6"/>
        <path d="M 20 70 A 38 38 0 1 1 80 70" fill="none" stroke="var(--color-dash-amber)" stroke-width="6" stroke-dasharray="140" stroke-dashoffset="50"/>
        <line x1="50" y1="50" x2="35" y2="32" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
        <circle cx="50" cy="50" r="6" fill="#D93829"/>
      </svg>
      <div>
        <div class="gauge-metric" style="color:var(--color-dash-amber);"><?= $pendingJobs ?></div>
        <div class="gauge-subtext">IN PROGRESS / DIAGNOSING</div>
      </div>
    </div>
  </div>

  <!-- Gauge 3: Low Stock Warning (Fuel / Inventory Gauge) -->
  <div class="gauge-card gauge-red">
    <div class="gauge-header">
      <span class="gauge-title">LOW STOCK ALERT</span>
      <span class="retro-badge" style="background:#000; color:var(--color-racing-red);">FUEL E</span>
    </div>
    <div class="gauge-visual">
      <svg class="gauge-dial-svg" viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="42" fill="#141418" stroke="#333" stroke-width="6"/>
        <path d="M 20 70 A 38 38 0 1 1 80 70" fill="none" stroke="var(--color-racing-red)" stroke-width="6" stroke-dasharray="140" stroke-dashoffset="90"/>
        <line x1="50" y1="50" x2="72" y2="58" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
        <circle cx="50" cy="50" r="6" fill="#D93829"/>
      </svg>
      <div>
        <div class="gauge-metric" style="color:var(--color-racing-red);"><?= $lowStockCount ?></div>
        <div class="gauge-subtext">PARTS ≤ 8 UNITS</div>
      </div>
    </div>
  </div>

  <!-- Gauge 4: Total Revenue (Speedometer / Odometer) -->
  <div class="gauge-card gauge-blue">
    <div class="gauge-header">
      <span class="gauge-title">TOTAL REVENUE (PAID)</span>
      <span class="retro-badge" style="background:#000; color:#3a86ff;">ODOMETER</span>
    </div>
    <div class="gauge-visual">
      <svg class="gauge-dial-svg" viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="42" fill="#141418" stroke="#333" stroke-width="6"/>
        <path d="M 20 70 A 38 38 0 1 1 80 70" fill="none" stroke="#3a86ff" stroke-width="6" stroke-dasharray="140" stroke-dashoffset="20"/>
        <line x1="50" y1="50" x2="65" y2="30" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
        <circle cx="50" cy="50" r="6" fill="#fff"/>
      </svg>
      <div>
        <div class="gauge-metric" style="font-size:1.75rem; color:#fff;">
          <?= format_price($totalRevenue) ?>
        </div>
        <div class="gauge-subtext">INCL. <?= format_price($totalInstallRevenue) ?> LABOR</div>
      </div>
    </div>
  </div>

</div>

<!-- Quick Action Toolbar -->
<div class="toolbar-wrap" style="background:var(--color-oil-slate); border:2px solid #000; padding:16px 20px; box-shadow:var(--shadow-hard);">
  <div style="font-family:var(--font-mono); font-size:0.85rem; color:var(--color-manual-cream);">
    ⚡ <strong>CHIEF WORKSHOP DISPATCH:</strong> Manage service intake, parts catalogue, or message clients directly.
  </div>
  <div style="display:flex; gap:10px; flex-wrap:wrap;">
    <a href="<?= BASE_URL ?>/admin/bookings.php" class="retro-btn retro-btn-sm retro-btn-amber">
      Manage Bookings & Bay Progress →
    </a>
    <a href="<?= BASE_URL ?>/admin/parts.php?action=new" class="retro-btn retro-btn-sm retro-btn-teal">
      + Add New Auto Part
    </a>
    <a href="<?= BASE_URL ?>/admin/brands.php" class="retro-btn retro-btn-sm retro-btn-dark">
      Brand Manager
    </a>
  </div>
</div>

<!-- ============================================================== -->
<!-- RECENT BOOKINGS & TELEMETRY TABLE                              -->
<!-- ============================================================== -->
<div class="retro-card" style="margin-top:24px;">
  <div class="retro-card-header">
    <h3 class="retro-card-title text-amber">
      <span>🛠️</span> RECENT SERVICE BOOKINGS & REPAIR STATUS
    </h3>
    <a href="<?= BASE_URL ?>/admin/bookings.php" class="retro-btn retro-btn-sm retro-btn-dark">
      View All Bookings →
    </a>
  </div>

  <div class="admin-table-wrap" style="margin:0; box-shadow:none;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Ticket ID</th>
          <th>Vehicle Number</th>
          <th>Customer Name</th>
          <th>Phone / WhatsApp</th>
          <th>Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($latestBookings as $b): ?>
          <tr>
            <td>
              <strong style="font-family:var(--font-mono); color:var(--color-racing-red); font-size:0.95rem;"><?= e($b['ticket_code']) ?></strong>
            </td>
            <td>
              <span style="font-family:var(--font-mono); font-weight:700; color:var(--color-manual-cream); font-size:1.05rem;"><?= e($b['vehicle_number']) ?></span>
            </td>
            <td><?= e($b['customer_name']) ?></td>
            <td>
              <span style="font-family:var(--font-mono); font-size:0.85rem;"><?= e($b['phone'] ?: 'Walk-in') ?></span>
              <?php if (!empty($b['phone'])): ?>
                <br>
                <a 
                  href="<?= build_whatsapp_url($b['phone'], "Hello " . $b['customer_name'] . ", Speedwell Retro Garage update for vehicle [" . $b['vehicle_number'] . "]: Your job (Ticket: " . $b['ticket_code'] . ") is currently: " . $b['status'] . ".") ?>" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  class="wa-alert-btn"
                  style="margin-top:4px;"
                >
                  💬 WhatsApp
                </a>
              <?php endif; ?>
            </td>
            <td style="font-family:var(--font-mono); font-size:0.85rem;"><?= format_date($b['created_at'], 'd M, h:i A') ?></td>
            <td>
              <span class="retro-badge badge-status-<?= strtolower(str_replace(' ', '-', $b['status'])) ?>">
                <?= e($b['status']) ?>
              </span>
            </td>
            <td>
              <a href="<?= BASE_URL ?>/admin/bookings.php?edit=<?= $b['id'] ?>" class="retro-btn retro-btn-sm retro-btn-teal">
                Update Status / Note
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============================================================== -->
<!-- RECENT PARTS ORDERS TABLE                                      -->
<!-- ============================================================== -->
<div class="retro-card" style="margin-top:24px;">
  <div class="retro-card-header">
    <h3 class="retro-card-title text-teal">
      <span>🧾</span> RECENT PARTS PURCHASES & INSTALLATION ORDERS
    </h3>
    <a href="<?= BASE_URL ?>/admin/orders.php" class="retro-btn retro-btn-sm retro-btn-dark">
      View All Orders →
    </a>
  </div>

  <div class="admin-table-wrap" style="margin:0; box-shadow:none;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order Code</th>
          <th>Customer</th>
          <th>Vehicle</th>
          <th>Parts Cost</th>
          <th>Installation Labor</th>
          <th>Grand Total</th>
          <th>Payment</th>
          <th>Order Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($latestOrders as $ord): ?>
          <tr>
            <td>
              <strong style="font-family:var(--font-mono); color:var(--color-dash-amber); font-size:0.95rem;"><?= e($ord['order_code']) ?></strong>
            </td>
            <td>
              <?= e($ord['customer_name']) ?><br>
              <small style="font-family:var(--font-mono); color:var(--color-muted-grey);"><?= e($ord['customer_phone']) ?></small>
            </td>
            <td style="font-family:var(--font-mono); font-weight:700; color:var(--color-racing-red);">
              <?= e($ord['vehicle_number'] ?: 'Direct Buy') ?>
            </td>
            <td style="font-family:var(--font-mono);"><?= format_price($ord['total_parts_cost']) ?></td>
            <td style="font-family:var(--font-mono); color:var(--color-gauge-teal); font-weight:700;">
              <?= $ord['total_install_cost'] > 0 ? '+ ' . format_price($ord['total_install_cost']) : 'None' ?>
            </td>
            <td style="font-family:var(--font-mono); font-weight:700; color:var(--color-manual-cream);">
              <?= format_price($ord['grand_total']) ?>
            </td>
            <td>
              <span class="retro-badge <?= $ord['payment_status'] === 'Paid' ? 'badge-stock-in' : 'badge-stock-low' ?>">
                <?= e($ord['payment_status']) ?>
              </span>
            </td>
            <td>
              <span class="retro-badge badge-status"><?= e($ord['order_status']) ?></span>
            </td>
            <td>
              <a href="<?= BASE_URL ?>/order-success.php?order=<?= urlencode($ord['order_code']) ?>" target="_blank" class="retro-btn retro-btn-sm retro-btn-cream">
                Invoice 🧾
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
