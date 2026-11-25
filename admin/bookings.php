<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - BOOKINGS & NOTIFICATIONS MANAGER (admin/bookings.php)
 * Status progression, custom mechanic notes & 1-click WhatsApp alerts
 * ==============================================================
 */

require_once __DIR__ . '/includes/admin-header.php';

$errors = [];
$editBooking = null;

// Handle Status & Mechanic Notes Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_booking') {
    require_csrf();

    $bookingId     = (int)($_POST['booking_id'] ?? 0);
    $newStatus     = trim($_POST['status'] ?? 'Pending');
    $mechanicNotes = trim($_POST['mechanic_notes'] ?? '');
    $customNotif   = trim($_POST['custom_notification'] ?? '');

    $allowedStatuses = ['Pending', 'Diagnosing', 'In Progress', 'Ready for Pickup', 'Completed', 'Cancelled'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        $errors[] = 'Invalid booking status selected.';
    }

    if ($bookingId > 0 && empty($errors)) {
        try {
            // Fetch existing booking
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
            $stmt->execute([$bookingId]);
            $currentBooking = $stmt->fetch();

            if ($currentBooking) {
                // Update booking
                $updStmt = $pdo->prepare("UPDATE bookings SET status = ?, mechanic_notes = ?, updated_at = NOW() WHERE id = ?");
                $updStmt->execute([$newStatus, $mechanicNotes, $bookingId]);

                // Create automated notification in notifications table
                $notifTitle = "Status Update: " . $currentBooking['vehicle_number'] . " is now " . $newStatus;
                $notifMsg = $customNotif ?: ("Your service ticket " . $currentBooking['ticket_code'] . " status was changed to: " . $newStatus . ". " . ($mechanicNotes ? "Mechanic Note: " . $mechanicNotes : ""));

                create_notification(
                    null,
                    $bookingId,
                    $currentBooking['vehicle_number'],
                    $notifTitle,
                    $notifMsg
                );

                set_flash('success', "Ticket {$currentBooking['ticket_code']} updated to '{$newStatus}' & notification dispatched.");
                header("Location: " . BASE_URL . "/admin/bookings.php");
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to update booking: ' . $e->getMessage();
        }
    }
}

// Handle Direct Edit View
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$editId]);
    $editBooking = $stmt->fetch();
}

// Fetch Filtered Bookings
$filterStatus = trim($_GET['status'] ?? 'all');
$filterSearch = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM bookings WHERE 1=1";
$params = [];

if ($filterStatus !== 'all' && !empty($filterStatus)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
}
if (!empty($filterSearch)) {
    $cleanVeh = clean_vehicle_no($filterSearch);
    $sql .= " AND (vehicle_number LIKE ? OR ticket_code LIKE ? OR customer_name LIKE ?)";
    $params[] = "%{$cleanVeh}%";
    $params[] = "%{$filterSearch}%";
    $params[] = "%{$filterSearch}%";
}
$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookingsList = $stmt->fetchAll();

$pageHeading = 'SERVICE BOOKINGS & NOTIFICATION DISPATCH';
?>

<?php if (!empty($errors)): ?>
  <div class="retro-alert retro-alert-danger" style="margin-bottom:20px;">
    <span class="alert-tag">[ERROR]</span>
    <div><?= implode('<br>', array_map('e', $errors)) ?></div>
  </div>
<?php endif; ?>

<!-- Edit Modal / Card (If Editing) -->
<?php if ($editBooking): ?>
  <div class="retro-card" style="margin-bottom:28px; border-color:var(--color-dash-amber);">
    <div class="retro-card-header" style="background:#241d08;">
      <h3 class="retro-card-title text-amber" style="font-size:1.4rem;">
        🛠️ UPDATE JOB PROGRESS & NOTIFY CLIENT: <?= e($editBooking['ticket_code']) ?>
      </h3>
      <a href="<?= BASE_URL ?>/admin/bookings.php" class="retro-btn retro-btn-sm retro-btn-dark">
        Cancel ✕
      </a>
    </div>

    <div class="retro-card-body">
      <form method="POST" action="<?= BASE_URL ?>/admin/bookings.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_booking">
        <input type="hidden" name="booking_id" value="<?= $editBooking['id'] ?>">

        <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:24px;">
          
          <!-- Column 1 -->
          <div>
            <div style="background:var(--color-asphalt-dark); border:2px solid #000; padding:16px; margin-bottom:18px; font-family:var(--font-mono); font-size:0.85rem; display:grid; grid-template-columns:1fr 1fr; gap:12px;">
              <div>
                <span style="color:var(--color-muted-grey); display:block; font-size:0.75rem;">VEHICLE REG NUMBER:</span>
                <strong style="color:var(--color-racing-red); font-size:1.15rem;"><?= e($editBooking['vehicle_number']) ?></strong>
              </div>
              <div>
                <span style="color:var(--color-muted-grey); display:block; font-size:0.75rem;">CUSTOMER NAME:</span>
                <strong style="color:var(--color-manual-cream); font-size:1rem;"><?= e($editBooking['customer_name']) ?></strong>
              </div>
              <div>
                <span style="color:var(--color-muted-grey); display:block; font-size:0.75rem;">CONTACT PHONE:</span>
                <strong style="color:var(--color-dash-amber);"><?= e($editBooking['phone'] ?: 'Walk-in') ?></strong>
              </div>
              <div>
                <span style="color:var(--color-muted-grey); display:block; font-size:0.75rem;">DATE REGISTERED:</span>
                <strong><?= format_date($editBooking['created_at']) ?></strong>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Update Job Progress Milestone <span class="required">*</span></label>
              <select name="status" class="retro-select" required style="font-size:1.05rem; font-weight:700;">
                <?php foreach (['Pending', 'Diagnosing', 'In Progress', 'Ready for Pickup', 'Completed', 'Cancelled'] as $st): ?>
                  <option value="<?= $st ?>" <?= $editBooking['status'] === $st ? 'selected' : '' ?>>
                    Milestone: <?= $st ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Mechanic Telemetry & Diagnostic Notes</label>
              <textarea name="mechanic_notes" class="retro-textarea" style="min-height:100px;" placeholder="e.g. Compression test results: Cyl 1 (175psi), Cyl 2 (170psi). Rotor resurfacing completed on lathe."><?= e($editBooking['mechanic_notes']) ?></textarea>
              <div class="form-hint">Displayed on the customer's live vehicle tracking CRT terminal.</div>
            </div>
          </div>

          <!-- Column 2: Notification & WhatsApp -->
          <div>
            <div style="background:var(--color-asphalt-dark); border:2px solid #000; padding:18px; margin-bottom:18px;">
              <h5 style="font-family:var(--font-mono); font-size:0.85rem; color:var(--color-gauge-teal); margin-bottom:12px;">
                [AUTOMATED DATABASE & WHATSAPP ALERT]
              </h5>

              <div class="form-group">
                <label class="form-label">Custom Alert Message (Optional)</label>
                <textarea name="custom_notification" class="retro-textarea" style="min-height:80px;" placeholder="Leave empty for auto-generated milestone alert."></textarea>
              </div>

              <?php if (!empty($editBooking['phone'])): ?>
                <div style="background:#111; border:1px solid #333; padding:12px; margin-top:14px;">
                  <span style="font-family:var(--font-mono); font-size:0.75rem; color:#aaa; display:block; margin-bottom:6px;">1-CLICK WHATSAPP DISPATCH PREVIEW:</span>
                  <a 
                    href="<?= build_whatsapp_url($editBooking['phone'], "Hello " . $editBooking['customer_name'] . ", Speedwell Retro Garage update for vehicle [" . $editBooking['vehicle_number'] . "]: Your job (Ticket: " . $editBooking['ticket_code'] . ") is currently: " . $editBooking['status'] . ". Contact workshop desk for details.") ?>" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="wa-alert-btn" 
                    style="font-size:0.88rem; padding:8px 14px;"
                  >
                    💬 Open WhatsApp Pre-Filled Message
                  </a>
                </div>
              <?php endif; ?>
            </div>

            <button type="submit" class="retro-btn retro-btn-primary retro-btn-block" style="padding:14px; font-size:1.15rem;">
              UPDATE STATUS & DISPATCH ALERT →
            </button>
          </div>

        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- Filter & Search Bar -->
<div class="toolbar-wrap" style="background:var(--color-oil-slate); border:2px solid #000; padding:16px 20px; box-shadow:var(--shadow-hard); margin-bottom:20px;">
  <form method="GET" action="<?= BASE_URL ?>/admin/bookings.php" style="display:flex; gap:12px; flex-wrap:wrap; width:100%; align-items:center;">
    
    <div style="flex:1; min-width:240px;">
      <input 
        type="text" 
        name="q" 
        class="retro-input" 
        placeholder="Filter by vehicle plate (e.g. WP CAB-4412), ticket code, or name..." 
        value="<?= e($filterSearch) ?>"
      >
    </div>

    <div>
      <select name="status" class="retro-select" onchange="this.form.submit()">
        <option value="all">All Statuses</option>
        <?php foreach (['Pending', 'Diagnosing', 'In Progress', 'Ready for Pickup', 'Completed', 'Cancelled'] as $st): ?>
          <option value="<?= $st ?>" <?= $filterStatus === $st ? 'selected' : '' ?>>
            Status: <?= $st ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="retro-btn retro-btn-sm retro-btn-amber">
      Filter
    </button>

    <?php if (!empty($filterSearch) || $filterStatus !== 'all'): ?>
      <a href="<?= BASE_URL ?>/admin/bookings.php" class="retro-btn retro-btn-sm retro-btn-dark">
        Reset ✕
      </a>
    <?php endif; ?>

    <div style="margin-left:auto; font-family:var(--font-mono); font-size:0.82rem; color:var(--color-muted-grey);">
      Showing <?= count($bookingsList) ?> Bookings
    </div>
  </form>
</div>

<!-- Bookings List Table -->
<div class="retro-card">
  <div class="retro-card-header">
    <h3 class="retro-card-title text-amber">
      <span>🛠️</span> WORKSHOP BAY JOB INTAKE ROSTER
    </h3>
    <span class="retro-badge" style="background:#000; color:var(--color-gauge-teal);">LIVE SYNC</span>
  </div>

  <div class="admin-table-wrap" style="margin:0; box-shadow:none;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Ticket ID</th>
          <th>Vehicle Number</th>
          <th>Customer Name</th>
          <th>Contact & WhatsApp</th>
          <th>Target Date</th>
          <th>Current Status</th>
          <th>Mechanic Notes</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($bookingsList)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding:30px; font-family:var(--font-mono); color:var(--color-muted-grey);">
              No bookings found matching current filters.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($bookingsList as $b): ?>
            <tr>
              <td>
                <strong style="font-family:var(--font-mono); color:var(--color-racing-red); font-size:1rem;"><?= e($b['ticket_code']) ?></strong>
              </td>
              <td>
                <span style="font-family:var(--font-mono); font-weight:700; color:var(--color-manual-cream); font-size:1.1rem;"><?= e($b['vehicle_number']) ?></span>
              </td>
              <td>
                <?= e($b['customer_name']) ?>
              </td>
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
              <td style="font-family:var(--font-mono); font-size:0.85rem;">
                <?= $b['preferred_date'] ? date('d M Y', strtotime($b['preferred_date'])) : format_date($b['created_at']) ?>
              </td>
              <td>
                <span class="retro-badge badge-status-<?= strtolower(str_replace(' ', '-', $b['status'])) ?>">
                  <?= e($b['status']) ?>
                </span>
              </td>
              <td style="font-family:var(--font-mono); font-size:0.8rem; color:#ccc; max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                <?= e($b['mechanic_notes'] ?: '—') ?>
              </td>
              <td>
                <div style="display:flex; gap:6px;">
                  <a href="<?= BASE_URL ?>/booking-success.php?ticket=<?= urlencode($b['ticket_code']) ?>" target="_blank" class="retro-btn retro-btn-sm retro-btn-cream" title="View Printable Ticket">
                    Ticket 🧾
                  </a>
                  <a href="<?= BASE_URL ?>/admin/bookings.php?edit=<?= $b['id'] ?>" class="retro-btn retro-btn-sm retro-btn-teal">
                    Update ✏️
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
