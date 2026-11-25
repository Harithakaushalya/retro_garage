<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - PART DETAIL VIEW (part-detail.php)
 * Technical specs, live installation calculator & stock breakdown
 * ==============================================================
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = get_db_connection();
$partId = (int)($_GET['id'] ?? 0);

if ($partId <= 0) {
    header("Location: " . BASE_URL . "/parts.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, b.country AS brand_country 
    FROM parts p 
    JOIN brands b ON p.brand_id = b.id 
    WHERE p.id = ?
");
$stmt->execute([$partId]);
$part = $stmt->fetch();

if (!$part) {
    die("Part not found in inventory.");
}

$stockStatus = (int)$part['stock_qty'] <= 0 ? 'Out of Stock' : ((int)$part['stock_qty'] <= 8 ? 'Low Stock' : 'In Stock');
$stockClass = (int)$part['stock_qty'] <= 0 ? 'badge-stock-out' : ((int)$part['stock_qty'] <= 8 ? 'badge-stock-low' : 'badge-stock-in');
$imagePath = !empty($part['image_path']) ? BASE_URL . '/' . $part['image_path'] : BASE_URL . '/assets/images/placeholder-part.svg';

$pageTitle = e($part['name']) . ' • ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 40px 20px 80px 20px;">
  
  <!-- Breadcrumbs -->
  <div style="font-family:var(--font-mono); font-size:0.85rem; color:var(--color-muted-grey); margin-bottom:20px;">
    <a href="<?= BASE_URL ?>/index.php" style="color:var(--color-manual-cream);">Home</a> / 
    <a href="<?= BASE_URL ?>/parts.php" style="color:var(--color-manual-cream);">Parts Catalog</a> / 
    <a href="<?= BASE_URL ?>/parts.php?brand=<?= urlencode($part['brand_slug']) ?>" style="color:var(--color-dash-amber);"><?= e($part['brand_name']) ?></a> / 
    <span style="color:var(--color-manual-cream);"><?= e($part['sku']) ?></span>
  </div>

  <!-- Product Detail Grid -->
  <div 
    class="part-detail-view" 
    data-part-id="<?= $part['id'] ?>" 
    data-part-price="<?= (float)$part['part_price'] ?>" 
    data-install-fee="<?= (float)$part['install_fee'] ?>"
    data-currency="<?= CURRENCY_SYMBOL ?>"
    style="display:grid; grid-template-columns:1.1fr 1fr; gap:40px; align-items:start;"
  >
    
    <!-- Left Column: Blueprint Image Media Box -->
    <div>
      <div style="background:#111114; border:3px solid #000; box-shadow:var(--shadow-hard); padding:30px; text-align:center;">
        <img 
          src="<?= e($imagePath) ?>" 
          alt="<?= e($part['name']) ?>" 
          style="max-height:380px; width:100%; object-fit:contain; margin:0 auto;"
        >
      </div>

      <!-- Workshop Warranty Notice -->
      <div style="background:var(--color-asphalt-dark); border:2px solid #000; padding:16px; margin-top:16px; font-family:var(--font-mono); font-size:0.82rem; color:var(--color-muted-grey);">
        <strong style="color:var(--color-gauge-teal);">[SPEEDWELL WARRANTY GUARANTEE]</strong><br>
        All genuine components come with a 6-month garage replacement guarantee when fitted by our certified bay mechanics.
      </div>
    </div>

    <!-- Right Column: Specs, Installation Toggle & Purchase Box -->
    <div>
      
      <!-- Badges -->
      <div style="display:flex; gap:10px; align-items:center; margin-bottom:12px;">
        <span class="retro-badge badge-brand"><?= e($part['brand_name']) ?> (<?= e($part['brand_country']) ?>)</span>
        <span class="retro-badge <?= $stockClass ?>"><?= $stockStatus ?> (<?= $part['stock_qty'] ?> Units)</span>
        <span class="retro-badge" style="background:#222; color:#aaa;"><?= e($part['category']) ?></span>
      </div>

      <!-- Title & SKU -->
      <h1 style="font-size:2.4rem; line-height:1.1; margin-bottom:8px;">
        <?= e($part['name']) ?>
      </h1>
      <div style="font-family:var(--font-mono); font-size:0.9rem; color:var(--color-dash-amber); margin-bottom:20px;">
        FACTORY SKU: <strong><?= e($part['sku']) ?></strong>
      </div>

      <!-- Description -->
      <div style="background:var(--color-oil-slate); border:2px solid #000; padding:18px; margin-bottom:24px; font-size:0.95rem; line-height:1.6;">
        <h5 style="font-family:var(--font-mono); font-size:0.8rem; color:var(--color-dash-amber); margin-bottom:8px;">[TECHNICAL DESCRIPTION & COMPATIBILITY]</h5>
        <?= nl2br(e($part['description'])) ?>
      </div>

      <!-- Interactive Pricing & Installation Selection Box -->
      <div class="order-summary-box" style="margin-bottom:24px;">
        <div class="summary-header">
          <h3 style="font-size:1.5rem;">PURCHASE & INSTALLATION OPTIONS</h3>
          <span style="font-family:var(--font-mono); font-size:0.75rem; color:#444;">SELECT SERVICE PREFERENCE</span>
        </div>

        <!-- Quantity Picker -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
          <span style="font-family:var(--font-mono); font-size:0.9rem; font-weight:700;">Purchase Quantity:</span>
          <div class="qty-control" style="background:#fff;">
            <input type="number" class="qty-input quantity-input" value="1" min="1" max="<?= max(1, (int)$part['stock_qty']) ?>" style="color:#000;">
          </div>
        </div>

        <!-- Installation Toggle -->
        <div class="install-toggle-box" style="background:#fff; border:2px solid #000; padding:14px; margin-bottom:16px;">
          <label class="install-checkbox-label">
            <input type="checkbox" class="install-toggle-checkbox">
            <div class="install-text-wrap">
              <span class="install-title" style="color:#000; font-size:0.95rem;">Include Garage Bay Installation</span>
              <span class="install-fee" style="font-size:0.85rem;">
                + <?= format_price($part['install_fee']) ?> per unit (Includes certified bay labor, fitting & road test)
              </span>
            </div>
          </label>
        </div>

        <!-- Calculated Live Total -->
        <div class="summary-row total-row" style="margin-top:10px;">
          <span>CALCULATED TOTAL:</span>
          <span class="dynamic-total-price"><?= format_price($part['part_price']) ?></span>
        </div>

        <!-- CTA Buttons -->
        <div style="display:flex; gap:12px; margin-top:20px;">
          <button 
            type="button" 
            class="retro-btn retro-btn-primary retro-btn-block btn-add-to-cart" 
            data-part-id="<?= $part['id'] ?>"
            style="font-size:1.2rem; padding:12px;"
            <?= (int)$part['stock_qty'] <= 0 ? 'disabled' : '' ?>
          >
            <?= (int)$part['stock_qty'] <= 0 ? 'CURRENTLY OUT OF STOCK' : 'ADD TO WORKSHOP CART 🛒' ?>
          </button>
        </div>
      </div>

    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
