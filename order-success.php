<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - RETRO INVOICE CONFIRMATION (order-success.php)
 * Perforated workshop receipt, itemized labor & print styles
 * ==============================================================
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = get_db_connection();
$orderCode = trim($_GET['order'] ?? '');

if (empty($orderCode)) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ?");
$stmt->execute([$orderCode]);
$order = $stmt->fetch();

if (!$order) {
    die("Order record not found.");
}

// Fetch Order Items
$itemsStmt = $pdo->prepare("
    SELECT oi.*, p.name AS part_name, p.sku AS part_sku, b.name AS brand_name 
    FROM order_items oi 
    JOIN parts p ON oi.part_id = p.id 
    JOIN brands b ON p.brand_id = b.id 
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$order['id']]);
$items = $itemsStmt->fetchAll();

$pageTitle = 'Retro Invoice #' . $order['order_code'] . ' • ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 40px 20px 80px 20px;">
  
  <!-- Success Alert Banner -->
  <div class="retro-alert retro-alert-success no-print" style="max-width:860px; margin:0 auto 24px auto;">
    <span class="alert-tag">[ORDER CONFIRMED]</span>
    <span class="alert-body">
      <strong>Order <?= e($order['order_code']) ?> Successfully Placed!</strong> Stock has been deducted from garage inventory and your workshop bay requisition is saved.
    </span>
  </div>

  <!-- Action Bar -->
  <div class="no-print" style="max-width:860px; margin:0 auto 20px auto; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <a href="<?= BASE_URL ?>/parts.php" class="retro-btn retro-btn-sm retro-btn-dark">
      ← Continue Shopping
    </a>

    <div style="display:flex; gap:10px;">
      <button type="button" onclick="window.print()" class="retro-btn retro-btn-sm retro-btn-cream">
        🖨️ PRINT RETRO INVOICE
      </button>
      <?php if (!empty($order['vehicle_number'])): ?>
        <a href="<?= BASE_URL ?>/track.php?query=<?= urlencode($order['vehicle_number']) ?>" class="retro-btn retro-btn-sm retro-btn-teal">
          📟 TRACK VEHICLE BAY →
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- ============================================================== -->
  <!-- RETRO PERFORATED INVOICE CARD                                  -->
  <!-- ============================================================== -->
  <div class="retro-ticket-wrap" style="max-width:860px;">
    <div class="retro-ticket">
      
      <!-- Invoice Header -->
      <div class="ticket-header">
        <div class="ticket-brand-block">
          <h2>SPEEDWELL RETRO GARAGE</h2>
          <p>OFFICIAL WORKSHOP PARTS & SERVICE INVOICE • EST. 1988</p>
          <div style="font-family:var(--font-mono); font-size:0.75rem; color:#444;">
            <?= GARAGE_ADDRESS ?> • TEL: <?= GARAGE_PHONE ?>
          </div>
        </div>

        <div class="ticket-id-badge">
          <div class="ticket-id-label">OFFICIAL INVOICE NO</div>
          <div class="ticket-id-val"><?= e($order['order_code']) ?></div>
        </div>
      </div>

      <!-- Scalloped Perforation Tear Line -->
      <div class="ticket-perforation-divider">
        <span class="ticket-tear-text">TEAR OFF INVOICE STUB & WARRANTY RECORD</span>
      </div>

      <!-- Invoice Body -->
      <div class="ticket-body">
        
        <!-- Metadata Grid -->
        <div class="ticket-meta-grid" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
          <div class="ticket-meta-item">
            <label>Customer Name</label>
            <div class="val"><?= e($order['customer_name']) ?></div>
          </div>

          <div class="ticket-meta-item">
            <label>Contact Phone</label>
            <div class="val"><?= e($order['customer_phone']) ?></div>
          </div>

          <div class="ticket-meta-item">
            <label>Vehicle Reg Number</label>
            <div class="val ticket-vehicle-no"><?= e($order['vehicle_number'] ?: 'Direct Purchase') ?></div>
          </div>

          <div class="ticket-meta-item">
            <label>Invoice Date</label>
            <div class="val"><?= format_date($order['created_at']) ?></div>
          </div>

          <div class="ticket-meta-item">
            <label>Payment Method</label>
            <div class="val" style="text-transform:uppercase; font-size:0.95rem;">
              <?= str_replace('_', ' ', e($order['payment_method'])) ?>
            </div>
          </div>

          <div class="ticket-meta-item">
            <label>Payment Status</label>
            <div class="val">
              <span class="retro-badge <?= $order['payment_status'] === 'Paid' ? 'badge-stock-in' : 'badge-stock-low' ?>">
                <?= e($order['payment_status']) ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Itemized Parts & Labor Table -->
        <div style="background:#fff; border:2px solid #000; margin-bottom:20px; overflow-x:auto;">
          <table style="width:100%; border-collapse:collapse; font-family:var(--font-mono); font-size:0.88rem;">
            <thead>
              <tr style="background:var(--color-cream-dark); border-bottom:2px solid #000; text-align:left;">
                <th style="padding:10px 14px;">Part SKU & Description</th>
                <th style="padding:10px 14px; text-align:center;">Qty</th>
                <th style="padding:10px 14px; text-align:right;">Unit Price</th>
                <th style="padding:10px 14px; text-align:right;">Labor / Fitting</th>
                <th style="padding:10px 14px; text-align:right;">Line Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <tr style="border-bottom:1px dashed #aaa;">
                  <td style="padding:12px 14px;">
                    <strong><?= e($item['part_name']) ?></strong><br>
                    <span style="font-size:0.75rem; color:#666;">SKU: <?= e($item['part_sku']) ?> (<?= e($item['brand_name']) ?>)</span>
                  </td>
                  <td style="padding:12px 14px; text-align:center; font-weight:700;">
                    <?= $item['quantity'] ?>
                  </td>
                  <td style="padding:12px 14px; text-align:right;">
                    <?= format_price($item['unit_part_price']) ?>
                  </td>
                  <td style="padding:12px 14px; text-align:right; color:var(--color-gauge-teal); font-weight:700;">
                    <?= $item['include_install'] ? '+ ' . format_price($item['unit_install_fee'] * $item['quantity']) : 'None' ?>
                  </td>
                  <td style="padding:12px 14px; text-align:right; font-weight:700;">
                    <?= format_price($item['line_total']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="background:var(--color-cream-light); border-top:2px solid #000;">
                <td colspan="4" style="padding:10px 14px; text-align:right; font-weight:700;">PARTS SUBTOTAL:</td>
                <td style="padding:10px 14px; text-align:right; font-weight:700;"><?= format_price($order['total_parts_cost']) ?></td>
              </tr>
              <tr style="background:var(--color-cream-light);">
                <td colspan="4" style="padding:6px 14px; text-align:right; font-weight:700; color:var(--color-gauge-teal);">WORKSHOP INSTALLATION LABOR:</td>
                <td style="padding:6px 14px; text-align:right; font-weight:700; color:var(--color-gauge-teal);"><?= format_price($order['total_install_cost']) ?></td>
              </tr>
              <tr style="background:var(--color-cream-dark); border-top:2px solid #000; font-size:1.15rem;">
                <td colspan="4" style="padding:12px 14px; text-align:right; font-weight:700; color:var(--color-racing-red);">GRAND TOTAL INVOICED:</td>
                <td style="padding:12px 14px; text-align:right; font-weight:700; color:var(--color-racing-red);"><?= format_price($order['grand_total']) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Dispatch / Collection Details -->
        <div style="background:#fff; border:1px solid #000; padding:12px 16px; margin-bottom:20px; font-family:var(--font-mono); font-size:0.85rem;">
          <span style="color:#666; font-weight:700;">DISPATCH & FITTING ADDRESS:</span><br>
          <?= nl2br(e($order['shipping_address'] ?: 'Counter Collection / Workshop Bay 42')) ?>
        </div>

        <!-- Barcode Simulation -->
        <div class="ticket-barcode-wrap">
          <div class="barcode-lines"></div>
          <div class="barcode-text">*<?= e($order['order_code']) ?>*</div>
          <div style="font-family:var(--font-mono); font-size:0.7rem; color:#666; margin-top:4px;">
            VALID FOR WARRANTY INSPECTION • KEEP ON FILE • RETRO GARAGE SYSTEM
          </div>
        </div>

        <!-- Rubber Stamp Watermark -->
        <?php if ($order['payment_status'] === 'Paid'): ?>
          <div class="rubber-stamp stamp-teal">
            PAID IN FULL
          </div>
        <?php else: ?>
          <div class="rubber-stamp">
            BAY RESERVED
          </div>
        <?php endif; ?>

      </div>

    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
