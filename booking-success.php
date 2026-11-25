<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - BOOKING CONFIRMATION & RETRO TICKET
 * ==============================================================
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = get_db_connection();
$ticketCode = trim($_GET['ticket'] ?? '');

if (empty($ticketCode)) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM bookings WHERE ticket_code = ?");
$stmt->execute([$ticketCode]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Ticket not found. Please verify your ticket code.");
}

$pageTitle = 'Service Ticket #' . $booking['ticket_code'] . ' • ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 40px 20px;">

  <!-- Page Alert Notice -->
  <div class="retro-alert retro-alert-success no-print" style="max-width:820px; margin:0 auto 24px auto;">
    <span class="alert-tag">[CONFIRMED]</span>
    <span class="alert-body">
      <strong>Service Ticket Generated Successfully!</strong> No login was required. Keep this ticket number or save this page to track your vehicle's live workshop progress.
    </span>
  </div>

  <!-- Action Bar (Print & Track) -->
  <div class="no-print" style="max-width:820px; margin:0 auto 20px auto; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <a href="<?= BASE_URL ?>/index.php" class="retro-btn retro-btn-sm retro-btn-dark">
      ← Return to Home
    </a>
    
    <div style="display:flex; gap:10px;">
      <button type="button" onclick="window.print()" class="retro-btn retro-btn-sm retro-btn-cream">
        🖨️ PRINT TICKET
      </button>
      <a href="<?= BASE_URL ?>/track.php?query=<?= urlencode($booking['ticket_code']) ?>" class="retro-btn retro-btn-sm retro-btn-teal">
        📟 TRACK LIVE JOB →
      </a>
    </div>
  </div>

  <!-- ============================================================== -->
  <!-- RETRO PERFORATED WORKSHOP TICKET CARD                          -->
  <!-- ============================================================== -->
  <div class="retro-ticket-wrap">
    <div class="retro-ticket">
      
      <!-- Ticket Header -->
      <div class="ticket-header">
        <div class="ticket-brand-block">
          <h2>SPEEDWELL RETRO GARAGE</h2>
          <p>OFFICIAL WORKSHOP JOB TICKET • JDM SPEED SHOP BAY INTAKE</p>
        </div>

        <div class="ticket-id-badge">
          <div class="ticket-id-label">SERVICE TICKET NO</div>
          <div class="ticket-id-val"><?= e($booking['ticket_code']) ?></div>
        </div>
      </div>

      <!-- Scalloped Perforation Tear Line -->
      <div class="ticket-perforation-divider">
        <span class="ticket-tear-text">TEAR OFF WORKSHOP STUB FOR VEHICLE DASHBOARD</span>
      </div>

      <!-- Ticket Body -->
      <div class="ticket-body">
        
        <!-- Metadata Grid -->
        <div class="ticket-meta-grid">
          <div class="ticket-meta-item">
            <label>Vehicle Reg Number</label>
            <div class="val ticket-vehicle-no"><?= e($booking['vehicle_number']) ?></div>
          </div>

          <div class="ticket-meta-item">
            <label>Customer Name</label>
            <div class="val"><?= e($booking['customer_name']) ?></div>
          </div>

          <div class="ticket-meta-item">
            <label>Customer Phone</label>
            <div class="val"><?= e($booking['phone'] ?: 'Counter Walk-in') ?></div>
          </div>

          <div class="ticket-meta-item">
            <label>Intake Date / Time</label>
            <div class="val"><?= format_date($booking['created_at']) ?></div>
          </div>

          <div class="ticket-meta-item">
            <label>Preferred Target Date</label>
            <div class="val"><?= $booking['preferred_date'] ? date('d M Y', strtotime($booking['preferred_date'])) : 'Standard Queue' ?></div>
          </div>

          <div class="ticket-meta-item">
            <label>Current Status</label>
            <div class="val">
              <span class="retro-badge badge-status-pending"><?= e($booking['status']) ?></span>
            </div>
          </div>
        </div>

        <!-- Service / Issue Notes -->
        <div style="background:#fff; border:2px solid #000; padding:16px; margin-bottom:20px;">
          <label style="display:block; font-family:var(--font-mono); font-size:0.75rem; color:#666; font-weight:700; text-transform:uppercase; margin-bottom:6px;">
            Reported Issue / Work Order Request:
          </label>
          <div style="font-family:var(--font-mono); font-size:0.95rem; color:#111; line-height:1.5;">
            <?= nl2br(e($booking['service_note'] ?: 'General service inspection.')) ?>
          </div>
        </div>

        <!-- Barcode Graphic Simulation -->
        <div class="ticket-barcode-wrap">
          <div class="barcode-lines"></div>
          <div class="barcode-text">*<?= e($booking['ticket_code']) ?>*</div>
          <div style="font-family:var(--font-mono); font-size:0.7rem; color:#666; margin-top:4px;">
            <?= APP_NAME ?> • BAY 42, HERITAGE MOTORWAY • TEL: <?= GARAGE_PHONE ?>
          </div>
        </div>

        <!-- Rubber Stamp Watermark -->
        <div class="rubber-stamp">
          BAY ALLOCATED
        </div>

      </div>

    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
