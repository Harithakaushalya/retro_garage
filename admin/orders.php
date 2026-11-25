<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - ORDERS MANAGER (admin/orders.php)
 * Customer parts purchases, installation status & WhatsApp alerts
 * ==============================================================
 */

require_once __DIR__ . '/includes/admin-header.php';

$errors = [];

// Handle Order Status Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    require_csrf();

    $orderId     = (int)($_POST['order_id'] ?? 0);
    $orderStatus = trim($_POST['order_status'] ?? 'Pending');
    $payStatus   = trim($_POST['payment_status'] ?? 'Pending');

    if ($orderId > 0) {
        $ordStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $ordStmt->execute([$orderId]);
        $order = $ordStmt->fetch();

        if ($order) {
            $upd = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
            $upd->execute([$orderStatus, $payStatus, $orderId]);

            // Dispatch notification
            $notifTitle = "Order {$order['order_code']} Updated: {$orderStatus}";
            $notifMsg = "Your order {$order['order_code']} status has been updated to '{$orderStatus}' (Payment: {$payStatus}).";
            create_notification(
                (int)$order['user_id'],
                null,
                $order['vehicle_number'],
                $notifTitle,
                $notifMsg
            );

            set_flash('success', "Order {$order['order_code']} status updated to '{$orderStatus}'.");
            header("Location: " . BASE_URL . "/admin/orders.php");
            exit;
        }
    }
}

// Fetch Orders
$filterStatus = trim($_GET['status'] ?? 'all');
$filterSearch = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];

if ($filterStatus !== 'all' && !empty($filterStatus)) {
    $sql .= " AND order_status = ?";
    $params[] = $filterStatus;
}
if (!empty($filterSearch)) {
    $sql .= " AND (order_code LIKE ? OR customer_name LIKE ? OR vehicle_number LIKE ?)";
    $params[] = "%{$filterSearch}%";
    $params[] = "%{$filterSearch}%";
    $params[] = "%{$filterSearch}%";
}
$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ordersList = $stmt->fetchAll();

// If Viewing Items of an Order
$viewOrderId = (int)($_GET['view'] ?? 0);
$viewOrder = null;
$viewItems = [];
if ($viewOrderId > 0) {
    $vStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $vStmt->execute([$viewOrderId]);
    $viewOrder = $vStmt->fetch();

    if ($viewOrder) {
        $iStmt = $pdo->prepare("
            SELECT oi.*, p.name AS part_name, p.sku AS part_sku, b.name AS brand_name 
            FROM order_items oi 
            JOIN parts p ON oi.part_id = p.id 
            JOIN brands b ON p.brand_id = b.id 
            WHERE oi.order_id = ?
        ");
        $iStmt->execute([$viewOrderId]);
        $viewItems = $iStmt->fetchAll();
    }
}

$pageHeading = 'CUSTOMER ORDERS & PARTS DISPATCH';
?>

<?php if (!empty($errors)): ?>
  <div class="retro-alert retro-alert-danger" style="margin-bottom:20px;">
    <span class="alert-tag">[ERROR]</span>
    <div><?= implode('<br>', array_map('e', $errors)) ?></div>
  </div>
<?php endif; ?>

<!-- Order Detail Drawer / Modal View -->
<?php if ($viewOrder): ?>
  <div class="retro-card" style="margin-bottom:28px; border-color:var(--color-gauge-teal);">
    <div class="retro-card-header" style="background:#0e2825;">
      <h3 class="retro-card-title text-teal" style="font-size:1.4rem;">
        🧾 ORDER REQUISITION DETAIL: <?= e($viewOrder['order_code']) ?>
      </h3>
      <a href="<?= BASE_URL ?>/admin/orders.php" class="retro-btn retro-btn-sm retro-btn-dark">
        Close View ✕
      </a>
    </div>

    <div class="retro-card-body">
      <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:24px;">
        
        <!-- Items Table -->
        <div>
          <h4 style="font-size:1.1rem; color:var(--color-dash-amber); margin-bottom:10px;">PURCHASED LINE ITEMS:</h4>
          <div class="admin-table-wrap" style="box-shadow:none; margin:0;">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Part Description</th>
                  <th>Qty</th>
                  <th>Unit Cost</th>
                  <th>Fitting Fee</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($viewItems as $it): ?>
                  <tr>
                    <td>
                      <strong><?= e($it['part_name']) ?></strong><br>
                      <small style="font-family:var(--font-mono); color:#aaa;">SKU: <?= e($it['part_sku']) ?> (<?= e($it['brand_name']) ?>)</small>
                    </td>
                    <td style="font-family:var(--font-mono); font-weight:700;"><?= $it['quantity'] ?></td>
                    <td style="font-family:var(--font-mono);"><?= format_price($it['unit_part_price']) ?></td>
                    <td>
                      <?php if ($it['include_install']): ?>
                        <span class="retro-badge" style="background:var(--color-gauge-teal); color:#fff; font-size:0.7rem;">
                          + <?= format_price($it['unit_install_fee'] * $it['quantity']) ?> (Bay Fitting)
                        </span>
                      <?php else: ?>
                        <span style="color:#777; font-size:0.8rem; font-family:var(--font-mono);">Part Only</span>
                      <?php endif; ?>
                    </td>
                    <td style="font-family:var(--font-mono); font-weight:700; color:var(--color-dash-amber);">
                      <?= format_price($it['line_total']) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Order Meta & Status Updater -->
        <div>
          <div style="background:var(--color-asphalt-dark); border:2px solid #000; padding:18px; margin-bottom:18px; font-family:var(--font-mono); font-size:0.85rem;">
            <div style="margin-bottom:8px;"><span style="color:#888;">CUSTOMER:</span> <strong><?= e($viewOrder['customer_name']) ?></strong></div>
            <div style="margin-bottom:8px;"><span style="color:#888;">PHONE:</span> <strong><?= e($viewOrder['customer_phone']) ?></strong></div>
            <div style="margin-bottom:8px;"><span style="color:#888;">EMAIL:</span> <strong><?= e($viewOrder['customer_email']) ?></strong></div>
            <div style="margin-bottom:8px;"><span style="color:#888;">VEHICLE NO:</span> <strong style="color:var(--color-racing-red);"><?= e($viewOrder['vehicle_number'] ?: 'Direct Purchase') ?></strong></div>
            <div style="margin-bottom:8px;"><span style="color:#888;">DELIVERY NOTE:</span> <?= nl2br(e($viewOrder['shipping_address'])) ?></div>
            <div style="margin-bottom:8px;"><span style="color:#888;">GRAND TOTAL:</span> <strong style="color:var(--color-dash-amber); font-size:1.15rem;"><?= format_price($viewOrder['grand_total']) ?></strong></div>
          </div>

          <!-- Fast Status Updater -->
          <form method="POST" action="<?= BASE_URL ?>/admin/orders.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_order_status">
            <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">

            <div class="form-group">
              <label class="form-label">Order Progress Status</label>
              <select name="order_status" class="retro-select">
                <?php foreach (['Pending', 'Paid', 'Packed', 'Ready', 'Installed', 'Delivered', 'Cancelled'] as $ost): ?>
                  <option value="<?= $ost ?>" <?= $viewOrder['order_status'] === $ost ? 'selected' : '' ?>>
                    Order: <?= $ost ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Payment Status</label>
              <select name="payment_status" class="retro-select">
                <?php foreach (['Pending', 'Paid', 'Failed'] as $pst): ?>
                  <option value="<?= $pst ?>" <?= $viewOrder['payment_status'] === $pst ? 'selected' : '' ?>>
                    Payment: <?= $pst ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <button type="submit" class="retro-btn retro-btn-teal retro-btn-block" style="padding:12px;">
              UPDATE STATUS & DISPATCH ALERT
            </button>
          </form>

          <?php if (!empty($viewOrder['customer_phone'])): ?>
            <div style="margin-top:14px;">
              <a 
                href="<?= build_whatsapp_url($viewOrder['customer_phone'], "Hello " . $viewOrder['customer_name'] . ", Speedwell Retro Garage update for Order [" . $viewOrder['order_code'] . "]: Status is currently: " . $viewOrder['order_status'] . ". Grand total: " . format_price($viewOrder['grand_total']) . ". Contact workshop desk for questions.") ?>" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="wa-alert-btn" 
                style="display:flex; justify-content:center; padding:10px;"
              >
                💬 1-Click WhatsApp Client Alert
              </a>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="toolbar-wrap" style="background:var(--color-oil-slate); border:2px solid #000; padding:16px 20px; box-shadow:var(--shadow-hard); margin-bottom:20px;">
  <form method="GET" action="<?= BASE_URL ?>/admin/orders.php" style="display:flex; gap:12px; flex-wrap:wrap; width:100%; align-items:center;">
    
    <div style="flex:1; min-width:240px;">
      <input 
        type="text" 
        name="q" 
        class="retro-input" 
        placeholder="Filter by Order Code, Customer Name, or Vehicle No..." 
        value="<?= e($filterSearch) ?>"
      >
    </div>

    <div>
      <select name="status" class="retro-select" onchange="this.form.submit()">
        <option value="all">All Order Statuses</option>
        <?php foreach (['Pending', 'Paid', 'Packed', 'Ready', 'Installed', 'Delivered', 'Cancelled'] as $st): ?>
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
      <a href="<?= BASE_URL ?>/admin/orders.php" class="retro-btn retro-btn-sm retro-btn-dark">
        Reset ✕
      </a>
    <?php endif; ?>

    <div style="margin-left:auto; font-family:var(--font-mono); font-size:0.82rem; color:var(--color-muted-grey);">
      Showing <?= count($ordersList) ?> Orders
    </div>
  </form>
</div>

<!-- Orders Table -->
<div class="retro-card">
  <div class="retro-card-header">
    <h3 class="retro-card-title text-amber">
      <span>🧾</span> CUSTOMER PARTS PURCHASES & INSTALLATION ORDERS
    </h3>
    <span class="retro-badge" style="background:#000; color:var(--color-dash-amber);">DISPATCH DESK</span>
  </div>

  <div class="admin-table-wrap" style="margin:0; box-shadow:none;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order Code</th>
          <th>Customer Name</th>
          <th>Contact & WhatsApp</th>
          <th>Vehicle Reg</th>
          <th>Parts Cost</th>
          <th>Installation Labor</th>
          <th>Grand Total</th>
          <th>Payment</th>
          <th>Order Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ordersList)): ?>
          <tr>
            <td colspan="10" style="text-align:center; padding:30px; font-family:var(--font-mono); color:var(--color-muted-grey);">
              No customer orders found matching current filter.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($ordersList as $ord): ?>
            <tr>
              <td>
                <strong style="font-family:var(--font-mono); color:var(--color-dash-amber); font-size:1rem;"><?= e($ord['order_code']) ?></strong>
              </td>
              <td>
                <?= e($ord['customer_name']) ?>
              </td>
              <td>
                <span style="font-family:var(--font-mono); font-size:0.85rem;"><?= e($ord['customer_phone']) ?></span>
                <?php if (!empty($ord['customer_phone'])): ?>
                  <br>
                  <a 
                    href="<?= build_whatsapp_url($ord['customer_phone'], "Hello " . $ord['customer_name'] . ", Speedwell Retro Garage update for Order [" . $ord['order_code'] . "]: Status is: " . $ord['order_status'] . ". Total: " . format_price($ord['grand_total']) . ".") ?>" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="wa-alert-btn" 
                    style="margin-top:4px;"
                  >
                    💬 WhatsApp
                  </a>
                <?php endif; ?>
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
                <span class="retro-badge badge-status">
                  <?= e($ord['order_status']) ?>
                </span>
              </td>
              <td>
                <div style="display:flex; gap:6px;">
                  <a href="<?= BASE_URL ?>/admin/orders.php?view=<?= $ord['id'] ?>" class="retro-btn retro-btn-sm retro-btn-teal" title="View details">
                    Inspect 🔍
                  </a>
                  <a href="<?= BASE_URL ?>/order-success.php?order=<?= urlencode($ord['order_code']) ?>" target="_blank" class="retro-btn retro-btn-sm retro-btn-cream" title="Printable Invoice">
                    Invoice 🧾
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
