<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - HOMEPAGE (index.php)
 * Express Guest Booking, Live Vehicle Tracker Bar & Featured Parts
 * ==============================================================
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/csrf.php';

$pdo = get_db_connection();
$errors = [];

// Handle Express Guest Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'express_booking') {
    require_csrf();

    $customerName  = trim($_POST['customer_name'] ?? '');
    $vehicleNumber = clean_vehicle_no($_POST['vehicle_number'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $serviceNote   = trim($_POST['service_note'] ?? '');
    $preferredDate = !empty($_POST['preferred_date']) ? $_POST['preferred_date'] : null;

    // Validate Required Fields ONLY
    if (empty($customerName)) {
        $errors[] = 'Customer Name is required.';
    }
    if (empty($vehicleNumber)) {
        $errors[] = 'Vehicle Number (e.g., WP ABC-1234) is required.';
    }

    if (empty($errors)) {
        try {
            $ticketCode = generate_ticket_code($pdo);

            $stmt = $pdo->prepare("
                INSERT INTO bookings (ticket_code, customer_name, vehicle_number, phone, service_note, preferred_date, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())
            ");
            $stmt->execute([
                $ticketCode,
                $customerName,
                $vehicleNumber,
                $phone ?: null,
                $serviceNote ?: 'Routine garage diagnosis & service inspection',
                $preferredDate
            ]);
            $bookingId = (int)$pdo->lastInsertId();

            // Create initial notification in notifications table
            create_notification(
                null,
                $bookingId,
                $vehicleNumber,
                "New Booking Ticket Issued: {$ticketCode}",
                "Booking received for {$customerName} ({$vehicleNumber}). Status set to Pending diagnosis."
            );

            // Redirect to ticket confirmation page
            header("Location: " . BASE_URL . "/booking-success.php?ticket=" . urlencode($ticketCode));
            exit;
        } catch (Exception $e) {
            $errors[] = 'Failed to register booking: ' . $e->getMessage();
        }
    }
}

// Fetch Brands
$brandsStmt = $pdo->query("SELECT * FROM brands ORDER BY name ASC");
$brands = $brandsStmt->fetchAll();

// Fetch Featured Parts
$featuredStmt = $pdo->query("
    SELECT p.*, b.name AS brand_name 
    FROM parts p 
    JOIN brands b ON p.brand_id = b.id 
    ORDER BY p.id ASC 
    LIMIT 6
");
$featuredParts = $featuredStmt->fetchAll();

$pageTitle = APP_NAME . ' • Classic Retro Garage & Speed Shop';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ============================================================== -->
<!-- 1. HERO SECTION                                                -->
<!-- ============================================================== -->
<section class="hero-section">
  <div class="container hero-grid">
    
    <!-- Hero Left Column -->
    <div>
      <div class="hero-tag">
        <span>⚡</span> JDM & CLASSIC VEHICLE SPECIALISTS • EST. 1988
      </div>
      
      <h1 class="hero-title">
        PRECISION RETRO <span class="text-amber">GARAGE</span> & SPEED SHOP.
      </h1>
      
      <p class="hero-subtitle">
        Factory-spec analog maintenance, Japanese domestic performance spare parts, and bespoke engine bay tuning. Drop in for express bay service or order genuine parts delivered directly.
      </p>

      <div style="display:flex; gap:14px; flex-wrap:wrap;">
        <a href="#book-service" class="retro-btn retro-btn-primary">
          <span>🛠️</span> EXPRESS BAY BOOKING
        </a>
        <a href="<?= BASE_URL ?>/parts.php" class="retro-btn retro-btn-amber">
          <span>📦</span> BROWSE SPARE PARTS
        </a>
      </div>

      <!-- Garage Heritage Stats -->
      <div class="hero-stats-row">
        <div class="hero-stat-item">
          <div class="hero-stat-val">3,500+</div>
          <div class="hero-stat-label">Jobs Completed</div>
        </div>
        <div class="hero-stat-item">
          <div class="hero-stat-val">5 BAYS</div>
          <div class="hero-stat-label">Dedicated Rigs</div>
        </div>
        <div class="hero-stat-item">
          <div class="hero-stat-val">100%</div>
          <div class="hero-stat-label">OEM & Tested Parts</div>
        </div>
      </div>
    </div>

    <!-- Hero Right Column: Express Booking Box (NO LOGIN REQUIRED) -->
    <div id="book-service">
      <div class="booking-box-wrap">
        
        <div class="booking-box-header">
          <div>
            <h3>EXPRESS SERVICE BOOKING</h3>
            <span style="font-family:var(--font-mono); font-size:0.75rem; color:#444;">STRICTLY NO REGISTRATION OR LOGIN REQUIRED</span>
          </div>
          <span class="express-badge">INSTANT TICKET</span>
        </div>

        <?php if (!empty($errors)): ?>
          <div class="retro-alert retro-alert-danger" style="padding:10px 14px; font-size:0.88rem; margin-bottom:14px;">
            <span class="alert-tag">[ERROR]</span>
            <div><?= implode('<br>', array_map('e', $errors)) ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/index.php#book-service">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="express_booking">

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">
              Customer Name <span class="required">*</span>
            </label>
            <input type="text" name="customer_name" class="retro-input" required placeholder="e.g. Sunil Jayawardena" value="<?= e($_POST['customer_name'] ?? '') ?>">
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">
              Vehicle Registration Number <span class="required">*</span>
            </label>
            <input type="text" name="vehicle_number" class="retro-input" required placeholder="e.g. WP CAB-4412" style="text-transform:uppercase; font-weight:700;" value="<?= e($_POST['vehicle_number'] ?? '') ?>">
            <div class="form-hint" style="color:#555;">Format: Province Code + Plate Number (e.g. WP GA-8891)</div>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
            <div>
              <label class="form-label" style="font-size:0.78rem;">Phone Number (Optional)</label>
              <input type="tel" name="phone" class="retro-input" placeholder="+94 77 123 4567" value="<?= e($_POST['phone'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label" style="font-size:0.78rem;">Preferred Date (Optional)</label>
              <input type="date" name="preferred_date" class="retro-input" min="<?= date('Y-m-d') ?>" value="<?= e($_POST['preferred_date'] ?? '') ?>">
            </div>
          </div>

          <div class="form-group" style="margin-bottom:18px;">
            <label class="form-label" style="font-size:0.78rem;">Service Note / Issue Symptoms (Optional)</label>
            <textarea name="service_note" class="retro-textarea" placeholder="Describe issue (e.g., Brake vibration, clutch slip, oil service)"><?= e($_POST['service_note'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="retro-btn retro-btn-primary retro-btn-block" style="font-size:1.15rem; padding:12px;">
            GENERATE RETRO SERVICE TICKET →
          </button>
        </form>

      </div>
    </div>

  </div>
</section>

<!-- ============================================================== -->
<!-- 2. TRACK VEHICLE / CHECK ALERTS BAR                            -->
<!-- ============================================================== -->
<section class="tracker-bar-section">
  <div class="container">
    <div class="tracker-bar-inner">
      
      <div class="tracker-headline">
        <span style="font-size:2rem;">📟</span>
        <div>
          <h4>LIVE VEHICLE JOB TRACKER</h4>
          <span style="font-family:var(--font-mono); font-size:0.75rem; color:var(--color-muted-grey);">
            INSTANT DIAGNOSIS & REPAIR PROGRESS CHECK
          </span>
        </div>
      </div>

      <form method="GET" action="<?= BASE_URL ?>/track.php" class="tracker-input-group tracker-form">
        <input 
          type="text" 
          name="query" 
          class="retro-input" 
          required 
          placeholder="ENTER VEHICLE NO (e.g. WP CAB-4412) OR TICKET ID..." 
          style="font-size:0.95rem; font-weight:700;"
        >
        <button type="submit" class="retro-btn retro-btn-teal" style="padding:8px 18px; white-space:nowrap;">
          TRACK VEHICLE →
        </button>
      </form>

    </div>
  </div>
</section>

<!-- ============================================================== -->
<!-- 3. FEATURED AUTO PARTS & DYNAMIC INSTALLATION TOGGLE           -->
<!-- ============================================================== -->
<section style="padding: 60px 0;">
  <div class="container">
    
    <div style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
      <div>
        <div style="font-family:var(--font-mono); font-size:0.8rem; color:var(--color-dash-amber); font-weight:700; letter-spacing:1px; margin-bottom:4px;">
          [STAGE 1 INVENTORY • JAPANESE IMPORT SPEC]
        </div>
        <h2 style="margin:0;">POPULAR REPLACEMENT SPARES</h2>
      </div>

      <a href="<?= BASE_URL ?>/parts.php" class="retro-btn retro-btn-dark retro-btn-sm">
        VIEW ALL PARTS CATALOG (12+) →
      </a>
    </div>

    <!-- Quick Brand Pills -->
    <div class="brand-filter-bar">
      <a href="<?= BASE_URL ?>/parts.php" class="brand-pill-btn active">ALL BRANDS</a>
      <?php foreach ($brands as $b): ?>
        <a href="<?= BASE_URL ?>/parts.php?brand=<?= urlencode($b['slug']) ?>" class="brand-pill-btn">
          <?= e($b['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Featured Product Grid -->
    <div class="parts-grid">
      <?php foreach ($featuredParts as $part): ?>
        <?php
          $stockStatus = (int)$part['stock_qty'] <= 0 ? 'Out of Stock' : ((int)$part['stock_qty'] <= 8 ? 'Low Stock' : 'In Stock');
          $stockClass = (int)$part['stock_qty'] <= 0 ? 'badge-stock-out' : ((int)$part['stock_qty'] <= 8 ? 'badge-stock-low' : 'badge-stock-in');
          $imagePath = !empty($part['image_path']) ? BASE_URL . '/' . $part['image_path'] : BASE_URL . '/assets/images/placeholder-part.svg';
        ?>
        <div 
          class="part-card" 
          data-part-id="<?= $part['id'] ?>" 
          data-part-price="<?= (float)$part['part_price'] ?>" 
          data-install-fee="<?= (float)$part['install_fee'] ?>"
          data-currency="<?= CURRENCY_SYMBOL ?>"
        >
          <!-- Media -->
          <div class="part-card-media">
            <img src="<?= e($imagePath) ?>" alt="<?= e($part['name']) ?>" loading="lazy">
          </div>

          <!-- Body -->
          <div class="part-card-body">
            
            <div class="part-card-meta">
              <span class="retro-badge badge-brand"><?= e($part['brand_name']) ?></span>
              <span class="retro-badge <?= $stockClass ?>"><?= $stockStatus ?> (<?= $part['stock_qty'] ?>)</span>
            </div>

            <h4 class="part-card-title">
              <a href="<?= BASE_URL ?>/part-detail.php?id=<?= $part['id'] ?>">
                <?= e($part['name']) ?>
              </a>
            </h4>

            <p class="part-card-desc">
              <?= e($part['description']) ?>
            </p>

            <!-- Price Breakdown Box -->
            <div class="part-price-box">
              <div class="part-price-row">
                <span class="part-price-label">Part Cost:</span>
                <span class="part-price-val"><?= format_price($part['part_price']) ?></span>
              </div>
            </div>

            <!-- Interactive Installation Toggle Box -->
            <div class="install-toggle-box">
              <label class="install-checkbox-label">
                <input type="checkbox" class="install-toggle-checkbox">
                <div class="install-text-wrap">
                  <span class="install-title">🔧 + Garage Installation</span>
                  <span class="install-fee">+ <?= format_price($part['install_fee']) ?> (Labor & Fitting)</span>
                </div>
              </label>
            </div>

            <!-- Actions -->
            <div class="part-card-actions">
              <a href="<?= BASE_URL ?>/part-detail.php?id=<?= $part['id'] ?>" class="retro-btn retro-btn-dark retro-btn-sm" style="flex:1;">
                Specs
              </a>
              <button 
                type="button" 
                class="retro-btn retro-btn-amber retro-btn-sm btn-add-to-cart" 
                data-part-id="<?= $part['id'] ?>"
                style="flex:2;"
                <?= (int)$part['stock_qty'] <= 0 ? 'disabled' : '' ?>
              >
                <?= (int)$part['stock_qty'] <= 0 ? 'Out of Stock' : 'Add to Cart 🛒' ?>
              </button>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<!-- ============================================================== -->
<!-- 4. WORKSHOP HERITAGE & SPECIALIZED BAYS                        -->
<!-- ============================================================== -->
<section style="background:var(--color-asphalt-dark); border-top:3px solid #000; padding:60px 0;">
  <div class="container">
    <div style="text-align:center; max-width:680px; margin:0 auto 40px auto;">
      <div style="font-family:var(--font-mono); font-size:0.82rem; color:var(--color-racing-red); font-weight:700; letter-spacing:2px;">
        [ANALOG WORKSHOP CAPABILITIES]
      </div>
      <h2>DEDICATED SERVICE BAYS</h2>
      <p style="color:var(--color-muted-grey); font-family:var(--font-mono); font-size:0.9rem;">
        No guesswork, no proprietary scanner lockouts. We combine analog precision instruments with decades of Japanese auto engineering expertise.
      </p>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:24px;">
      
      <!-- Bay 1 -->
      <div class="retro-card" style="margin:0;">
        <div class="retro-card-header" style="background:#1b263b;">
          <h4 class="retro-card-title text-amber" style="font-size:1.3rem;">BAY 1: ENGINE & TRANSMISSION</h4>
        </div>
        <div class="retro-card-body">
          <p style="font-size:0.92rem; color:var(--color-manual-cream); margin-bottom:12px;">
            Full overhaul bays equipped for clutch replacement, timing belt synchronizing, head gasket machining, and cooling system flushes.
          </p>
          <span class="retro-badge" style="background:var(--color-oil-light); color:#fff;">VTEC • 4AGE • RB • SR</span>
        </div>
      </div>

      <!-- Bay 2 -->
      <div class="retro-card" style="margin:0;">
        <div class="retro-card-header" style="background:#2b1b1b;">
          <h4 class="retro-card-title text-red" style="font-size:1.3rem;">BAY 2: BRAKES & ROTOR LATHE</h4>
        </div>
        <div class="retro-card-body">
          <p style="font-size:0.92rem; color:var(--color-manual-cream); margin-bottom:12px;">
            Computerized on-car rotor resurfacing, ceramic pad fitting, master cylinder bleed, and hydraulic stainless steel line upgrades.
          </p>
          <span class="retro-badge" style="background:var(--color-oil-light); color:#fff;">DISC RESURFACING • FLUID PURGE</span>
        </div>
      </div>

      <!-- Bay 3 -->
      <div class="retro-card" style="margin:0;">
        <div class="retro-card-header" style="background:#1b332b;">
          <h4 class="retro-card-title text-teal" style="font-size:1.3rem;">BAY 3: SUSPENSION & ELECTRICAL</h4>
        </div>
        <div class="retro-card-body">
          <p style="font-size:0.92rem; color:var(--color-manual-cream); margin-bottom:12px;">
            Strut replacement, bushing presses, steering rack adjustments, ignition timing analysis with vintage strobe guns and oscilloscopes.
          </p>
          <span class="retro-badge" style="background:var(--color-oil-light); color:#fff;">STRUT COMPRESSION • 4-GAS ANALYZER</span>
        </div>
      </div>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
