<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - WORKSHOP CART (cart.php)
 * Interactive installation toggle, itemized breakdown & checkout gate
 * ==============================================================
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = get_db_connection();
$cartDetails = get_cart_details($pdo);
$user = current_user();

$pageTitle = 'Workshop Cart & Quotation • ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 40px 20px 80px 20px;">
  
  <div style="background:var(--color-oil-slate); border:2px solid #000; box-shadow:var(--shadow-hard); padding:20px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <div>
      <div style="font-family:var(--font-mono); font-size:0.78rem; color:var(--color-dash-amber); font-weight:700;">[ORDER REQUISITION & LABOR ESTIMATE]</div>
      <h1 style="font-size:2.2rem; margin:0;">WORKSHOP SHOPPING CART</h1>
    </div>

    <a href="<?= BASE_URL ?>/parts.php" class="retro-btn retro-btn-sm retro-btn-dark">
      ← Continue Browsing Parts
    </a>
  </div>

  <?php if (empty($cartDetails['items'])): ?>
    <!-- Empty Cart State -->
    <div style="background:var(--color-asphalt-dark); border:2px solid #000; padding:60px 20px; text-align:center; box-shadow:var(--shadow-hard); max-width:680px; margin:40px auto;">
      <div style="font-size:3.5rem; margin-bottom:12px;">🛒</div>
      <h2>YOUR WORKSHOP CART IS EMPTY</h2>
      <p style="font-family:var(--font-mono); color:var(--color-muted-grey); margin-bottom:24px;">
        You haven't added any auto parts or service items yet. Browse our inventory to configure parts and installation.
      </p>
      <a href="<?= BASE_URL ?>/parts.php" class="retro-btn retro-btn-amber">
        EXPLORE SPARE PARTS STORE →
      </a>
    </div>
  <?php else: ?>
    
    <!-- Cart Grid Layout -->
    <div class="cart-layout">
      
      <!-- Left Column: Items Table -->
      <div>
        <div class="cart-table-wrap">
          <table class="cart-table">
            <thead>
              <tr>
                <th>Part Description</th>
                <th>Qty</th>
                <th>Garage Fitting</th>
                <th>Line Total</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cartDetails['items'] as $item): ?>
                <tr data-part-id="<?= $item['part_id'] ?>">
                  <td>
                    <div class="cart-item-info">
                      <div class="cart-item-img">
                        <img src="<?= e(BASE_URL . '/' . $item['image_path']) ?>" alt="<?= e($item['name']) ?>">
                      </div>
                      <div>
                        <div class="cart-item-title"><?= e($item['name']) ?></div>
                        <div class="cart-item-sku">SKU: <?= e($item['sku']) ?> • Brand: <?= e($item['brand_name']) ?></div>
                        <div style="font-family:var(--font-mono); font-size:0.82rem; color:var(--color-dash-amber); margin-top:2px;">
                          Unit Price: <?= format_price($item['unit_part_price']) ?>
                        </div>
                      </div>
                    </div>
                  </td>

                  <td>
                    <div class="qty-control">
                      <button type="button" class="qty-btn qty-minus">-</button>
                      <input type="text" class="qty-input" value="<?= $item['quantity'] ?>" readonly>
                      <button type="button" class="qty-btn qty-plus">+</button>
                    </div>
                  </td>

                  <td>
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-family:var(--font-mono); font-size:0.82rem;">
                      <input 
                        type="checkbox" 
                        class="cart-install-checkbox" 
                        style="width:18px; height:18px; accent-color:var(--color-gauge-teal);" 
                        <?= $item['include_install'] ? 'checked' : '' ?>
                      >
                      <span>
                        Install at Garage<br>
                        <small class="item-install-subtotal" style="color:var(--color-gauge-teal); font-weight:700;">
                          <?= $item['include_install'] ? '+ ' . format_price($item['install_subtotal']) : 'None' ?>
                        </small>
                      </span>
                    </label>
                  </td>

                  <td>
                    <div class="item-line-total" style="font-family:var(--font-mono); font-size:1.15rem; font-weight:700; color:var(--color-dash-amber); white-space:nowrap;">
                      <?= format_price($item['line_total']) ?>
                    </div>
                  </td>

                  <td>
                    <button type="button" class="btn-remove-item" title="Remove part" style="background:transparent; border:none; color:var(--color-racing-red); font-size:1.3rem; cursor:pointer; padding:4px;">
                      ✕
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div style="font-family:var(--font-mono); font-size:0.82rem; color:var(--color-muted-grey); margin-top:12px;">
          * Toggling <strong>Garage Fitting</strong> automatically reserves a service bay slot and schedules certified mechanical installation.
        </div>
      </div>

      <!-- Right Column: Order Summary & Checkout Trigger -->
      <div>
        <div class="order-summary-box">
          <div class="summary-header">
            <h3>ORDER & LABOR SUMMARY</h3>
            <span style="font-family:var(--font-mono); font-size:0.75rem; color:#444;">ESTIMATED TOTAL BREAKDOWN</span>
          </div>

          <div class="summary-row">
            <span>Parts Cost Subtotal:</span>
            <span class="summary-parts-total"><?= format_price($cartDetails['total_parts']) ?></span>
          </div>

          <div class="summary-row">
            <span>Garage Installation Labor:</span>
            <span class="summary-install-total" style="color:var(--color-gauge-teal); font-weight:700;">
              <?= format_price($cartDetails['total_install']) ?>
            </span>
          </div>

          <div class="summary-row total-row">
            <span>GRAND TOTAL:</span>
            <span class="summary-grand-total"><?= format_price($cartDetails['grand_total']) ?></span>
          </div>

          <div style="background:#fff; border:1px solid #000; padding:12px; margin:16px 0; font-family:var(--font-mono); font-size:0.78rem; line-height:1.4;">
            ℹ️ <strong>Conditional Profile Rule:</strong> No registration required while browsing. You will only be prompted for quick customer details at payment confirmation.
          </div>

          <!-- Checkout Button -->
          <?php if ($user): ?>
            <a href="<?= BASE_URL ?>/checkout.php" class="retro-btn retro-btn-primary retro-btn-block" style="font-size:1.2rem; padding:14px;">
              PROCEED TO PAYMENT →
            </a>
          <?php else: ?>
            <button type="button" onclick="openRetroModal('quick-auth-modal')" class="retro-btn retro-btn-primary retro-btn-block" style="font-size:1.2rem; padding:14px;">
              PROCEED TO PAYMENT →
            </button>
          <?php endif; ?>

          <div style="text-align:center; margin-top:12px;">
            <a href="<?= BASE_URL ?>/parts.php" style="font-family:var(--font-mono); font-size:0.82rem; text-decoration:underline;">
              + Add more items from store
            </a>
          </div>
        </div>
      </div>

    </div>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
