<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - LIVE VEHICLE JOB TRACKER (track.php)
 * Real-time repair status timeline, mechanic notes & alert feed
 * ==============================================================
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = get_db_connection();
$query = trim($_GET['query'] ?? '');
$booking = null;
$notifications = [];
$searched = false;

if (!empty($query)) {
    $searched = true;
    $cleanQuery = clean_vehicle_no($query);

    $stmt = $pdo->prepare("
        SELECT * FROM bookings 
        WHERE ticket_code = ? OR vehicle_number = ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute([$query, $cleanQuery]);
    $booking = $stmt->fetch();

    if ($booking) {
        // Fetch notifications
        $notifStmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE booking_id = ? OR vehicle_number = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $notifStmt->execute([$booking['id'], $booking['vehicle_number']]);
        $notifications = $notifStmt->fetchAll();
    }
}

// Timeline Stages
$stages = [
    'Pending'          => ['step' => 1, 'label' => 'Intake Registered'],
    'Diagnosing'       => ['step' => 2, 'label' => 'Bay Diagnosis'],
    'In Progress'      => ['step' => 3, 'label' => 'Repair in Progress'],
    'Ready for Pickup' => ['step' => 4, 'label' => 'Ready for Pickup'],
    'Completed'        => ['step' => 5, 'label' => 'Job Completed']
];

$currentStepNum = 1;
if ($booking && isset($stages[$booking['status']])) {
    $currentStepNum = $stages[$booking['status']]['step'];
}

$pageTitle = 'Live Vehicle & Job Progress Tracker • ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 40px 20px 80px 20px;">
  
  <!-- Search Banner -->
  <div style="background:var(--color-oil-slate); border:2px solid #000; box-shadow:var(--shadow-hard); padding:26px; margin-bottom:30px;">
    <div style="font-family:var(--font-mono); font-size:0.8rem; color:var(--color-dash-amber); font-weight:700; letter-spacing:1px; margin-bottom:4px;">
      [ANALOG DASHBOARD TELEMETRY • LIVE REPAIR STATUS]
    </div>
    <h1 style="font-size:2.4rem; margin:0 0 16px 0;">VEHICLE STATUS & REPAIR TRACKER</h1>
    
    <form method="GET" action="<?= BASE_URL ?>/track.php" style="display:flex; gap:12px; max-width:680px; flex-wrap:wrap;">
      <input 
        type="text" 
        name="query" 
        class="retro-input" 
        required 
        placeholder="ENTER VEHICLE NO (e.g. WP CAB-4412) OR TICKET (e.g. GRG-92-0104)..."
        value="<?= e($query) ?>"
        style="flex:1; min-width:260px; font-weight:700; text-transform:uppercase;"
      >
      <button type="submit" class="retro-btn retro-btn-amber" style="padding:10px 22px;">
        LOOKUP TELEMETRY 🔍
      </button>
    </form>
  </div>

  <?php if ($searched && !$booking): ?>
    <!-- Not Found Card -->
    <div style="background:var(--color-asphalt-dark); border:2px solid #000; padding:50px 20px; text-align:center; box-shadow:var(--shadow-hard); max-width:720px; margin:0 auto;">
      <div style="font-size:3rem; margin-bottom:12px;">⚠️</div>
      <h3 style="color:var(--color-racing-red);">NO ACTIVE JOB FOUND FOR "<?= e($query) ?>"</h3>
      <p style="font-family:var(--font-mono); color:var(--color-muted-grey); margin-bottom:20px;">
        Please check your vehicle registration number format (e.g., <code>WP CAB-4412</code>) or ticket code (e.g., <code>GRG-92-0104</code>).
      </p>
      <div style="display:flex; justify-content:center; gap:14px;">
        <a href="<?= BASE_URL ?>/track.php?query=WP+CAB-4412" class="retro-btn retro-btn-sm retro-btn-teal">
          Try Sample: WP CAB-4412
        </a>
        <a href="<?= BASE_URL ?>/track.php?query=GRG-92-0104" class="retro-btn retro-btn-sm retro-btn-amber">
          Try Ticket: GRG-92-0104
        </a>
      </div>
    </div>

  <?php elseif ($booking): ?>
    
    <!-- Found Result Container -->
    <div style="display:grid; grid-template-columns:1.6fr 1fr; gap:30px; align-items:start;">
      
      <!-- Left Column: Status Timeline & Mechanic Logs -->
      <div>
        
        <!-- Job Progress Card -->
        <div class="retro-card" style="margin-bottom:24px;">
          <div class="retro-card-header">
            <div>
              <span class="retro-badge" style="background:var(--color-asphalt); color:var(--color-dash-amber); margin-bottom:4px;">
                TICKET: <?= e($booking['ticket_code']) ?>
              </span>
              <h3 class="retro-card-title" style="font-size:1.6rem;">
                VEHICLE TELEMETRY: <span class="text-red"><?= e($booking['vehicle_number']) ?></span>
              </h3>
            </div>
            
            <div>
              <span class="retro-badge badge-status-<?= strtolower(str_replace(' ', '-', $booking['status'])) ?>" style="font-size:0.95rem; padding:6px 14px;">
                <?= e($booking['status']) ?>
              </span>
            </div>
          </div>

          <div class="retro-card-body">
            
            <!-- Analog Timeline Progress Visualizer -->
            <div class="timeline-track-wrap">
              <div class="timeline-steps">
                <?php foreach ($stages as $stageKey => $sInfo): ?>
                  <?php
                    $isDone = $sInfo['step'] < $currentStepNum;
                    $isCurrent = $sInfo['step'] === $currentStepNum;
                    $stepClass = $isDone ? 'done' : ($isCurrent ? 'current' : '');
                  ?>
                  <div class="timeline-step <?= $stepClass ?>">
                    <div class="timeline-step-node">
                      <?= $isDone ? '✓' : $sInfo['step'] ?>
                    </div>
                    <div class="timeline-step-title"><?= e($sInfo['label']) ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Job Spec Details Grid -->
            <div style="background:var(--color-asphalt-dark); border:2px solid #000; padding:18px; margin-top:20px; display:grid; grid-template-columns:1fr 1fr; gap:14px; font-family:var(--font-mono); font-size:0.88rem;">
              <div>
                <span style="color:var(--color-muted-grey); display:block; font-size:0.75rem;">CUSTOMER NAME:</span>
                <strong style="color:var(--color-manual-cream); font-size:1rem;"><?= e($booking['customer_name']) ?></strong>
              </div>
              <div>
                <span style="color:var(--color-muted-grey); display:block; font-size:0.75rem;">REGISTRATION TIME:</span>
                <strong><?= format_date($booking['created_at']) ?></strong>
              </div>
              <div style="grid-column: span 2;">
                <span style="color:var(--color-muted-grey); display:block; font-size:0.75rem;">REPORTED ISSUE SYMPTOMS:</span>
                <div style="color:var(--color-manual-cream); line-height:1.4; margin-top:2px;">
                  <?= nl2br(e($booking['service_note'] ?: 'Routine diagnostics.')) ?>
                </div>
              </div>
            </div>

            <!-- Mechanic Terminal Notes -->
            <div class="mechanic-notes-terminal">
              <div class="terminal-header">
                [ANALOG CRT TERMINAL • MECHANIC INSPECTION LOG] LAST UPDATED: <?= format_date($booking['updated_at']) ?>
              </div>
              <?= !empty($booking['mechanic_notes']) 
                    ? e($booking['mechanic_notes']) 
                    : "> [SYSTEM] Bay inspection initiated. Awaiting lead technician oscilloscope readings." ?>
            </div>

          </div>
        </div>

      </div>

      <!-- Right Column: Live Notifications Feed & Garage Counter Contact -->
      <div>
        
        <!-- Garage Notifications History for Vehicle -->
        <div class="retro-card" style="margin-bottom:24px;">
          <div class="retro-card-header">
            <h4 class="retro-card-title text-amber" style="font-size:1.2rem;">
              🔔 VEHICLE ALERT DISPATCH LOG
            </h4>
            <span class="retro-badge" style="background:#000; color:var(--color-dash-amber);"><?= count($notifications) ?></span>
          </div>
          <div class="retro-card-body" style="padding:14px;">
            <?php if (empty($notifications)): ?>
              <p style="font-family:var(--font-mono); font-size:0.85rem; color:var(--color-muted-grey); text-align:center; padding:16px;">
                No automated alerts issued yet for this vehicle.
              </p>
            <?php else: ?>
              <ul style="list-style:none;">
                <?php foreach ($notifications as $notif): ?>
                  <li style="padding:12px; border-bottom:1px dashed var(--color-oil-border); margin-bottom:8px; background:var(--color-asphalt-dark);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                      <strong style="font-size:0.88rem; color:var(--color-dash-amber); font-family:var(--font-mono);">
                        <?= e($notif['title']) ?>
                      </strong>
                    </div>
                    <p style="font-size:0.82rem; color:var(--color-manual-cream); margin-bottom:4px; line-height:1.4;">
                      <?= e($notif['message']) ?>
                    </p>
                    <span style="font-family:var(--font-mono); font-size:0.7rem; color:var(--color-muted-grey);">
                      🕒 <?= format_date($notif['created_at']) ?>
                    </span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <!-- Workshop Bay Desk Direct Contact -->
        <div class="order-summary-box">
          <div class="summary-header">
            <h3 style="font-size:1.4rem;">GARAGE BAY COUNTER</h3>
            <span style="font-family:var(--font-mono); font-size:0.75rem; color:#444;">COMMUNICATION DESK</span>
          </div>

          <p style="font-family:var(--font-mono); font-size:0.82rem; margin-bottom:16px;">
            Need to add extra parts or inquire about your vehicle's ETA? Contact the head mechanic at Bay 42 directly.
          </p>

          <a 
            href="<?= build_whatsapp_url(GARAGE_WHATSAPP, 'Inquiry regarding Ticket ' . $booking['ticket_code'] . ' for vehicle [' . $booking['vehicle_number'] . ']') ?>" 
            target="_blank" 
            rel="noopener noreferrer" 
            class="retro-btn retro-btn-teal retro-btn-block"
            style="margin-bottom:10px;"
          >
            💬 WHATSAPP GARAGE COUNTER
          </a>

          <a href="tel:<?= GARAGE_PHONE ?>" class="retro-btn retro-btn-dark retro-btn-block">
            📞 CALL WORKSHOP DESK
          </a>
        </div>

      </div>

    </div>

  <?php else: ?>
    
    <!-- Initial Prompt State (No query entered yet) -->
    <div style="max-width:760px; margin:0 auto; background:var(--color-oil-slate); border:2px solid #000; box-shadow:var(--shadow-hard); padding:30px;">
      <h3 style="color:var(--color-dash-amber); margin-bottom:12px;">HOW VEHICLE TRACKING WORKS</h3>
      <ol style="font-family:var(--font-mono); font-size:0.9rem; line-height:1.8; padding-left:20px; color:var(--color-manual-cream); margin-bottom:24px;">
        <li>Enter your <strong>Vehicle Number</strong> (e.g., <code>WP CAB-4412</code>) or the <strong>Ticket Code</strong> (e.g., <code>GRG-92-0104</code>) given upon booking.</li>
        <li>View live repair progress through the 5 workshop milestones: <em>Intake → Diagnosing → In Progress → Ready for Pickup → Completed</em>.</li>
        <li>Read real-time oscilloscope & inspection notes entered by our mechanics at Bay 42.</li>
        <li>No customer login is required to check repair status.</li>
      </ol>

      <div style="background:var(--color-asphalt-dark); padding:16px; border:1px solid #000;">
        <span style="font-family:var(--font-mono); font-size:0.8rem; color:var(--color-muted-grey);">TRY SAMPLE DEMO TICKETS / PLATES:</span>
        <div style="display:flex; gap:10px; margin-top:8px; flex-wrap:wrap;">
          <a href="<?= BASE_URL ?>/track.php?query=WP+CAB-4412" class="retro-btn retro-btn-sm retro-btn-dark">
            WP CAB-4412 (In Progress)
          </a>
          <a href="<?= BASE_URL ?>/track.php?query=WP+GA-8891" class="retro-btn retro-btn-sm retro-btn-dark">
            WP GA-8891 (Diagnosing)
          </a>
          <a href="<?= BASE_URL ?>/track.php?query=SP+BBD-1102" class="retro-btn retro-btn-sm retro-btn-dark">
            SP BBD-1102 (Ready for Pickup)
          </a>
        </div>
      </div>
    </div>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
