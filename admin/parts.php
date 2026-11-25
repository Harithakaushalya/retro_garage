<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - PARTS & INVENTORY MANAGER (admin/parts.php)
 * ==============================================================
 */

require_once __DIR__ . '/includes/admin-header.php';

$errors = [];
$editPart = null;
$action = $_GET['action'] ?? 'list';

// Fetch Brands for dropdown
$brands = $pdo->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();

// Handle Delete Part
if ($action === 'delete') {
    $delId = (int)($_GET['id'] ?? 0);
    if ($delId > 0) {
        $delStmt = $pdo->prepare("DELETE FROM parts WHERE id = ?");
        $delStmt->execute([$delId]);
        set_flash('success', 'Auto part removed from inventory.');
        header("Location: " . BASE_URL . "/admin/parts.php");
        exit;
    }
}

// Handle Edit Fetch
if ($action === 'edit') {
    $editId = (int)($_GET['id'] ?? 0);
    if ($editId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM parts WHERE id = ?");
        $stmt->execute([$editId]);
        $editPart = $stmt->fetch();
    }
}

// Handle Add / Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $partId      = (int)($_POST['part_id'] ?? 0);
    $brandId     = (int)($_POST['brand_id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $sku         = strtoupper(trim($_POST['sku'] ?? ''));
    $category    = trim($_POST['category'] ?? 'Engine & Transmission');
    $description = trim($_POST['description'] ?? '');
    $stockQty    = max(0, (int)($_POST['stock_qty'] ?? 0));
    $partPrice   = max(0.0, (float)($_POST['part_price'] ?? 0));
    $installFee  = max(0.0, (float)($_POST['install_fee'] ?? 0));

    if (empty($name)) {
        $errors[] = 'Part name is required.';
    }
    if ($brandId <= 0) {
        $errors[] = 'Please select a valid vehicle brand.';
    }
    if (empty($sku)) {
        $sku = 'PRT-' . mt_rand(100, 999) . '-' . substr(strtoupper(preg_replace('/[^A-Z]/', '', $name)), 0, 3);
    }

    // Image Upload Handling
    $imagePath = $_POST['existing_image'] ?? null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        try {
            $uploadedPath = handle_part_image_upload($_FILES['image']);
            if ($uploadedPath) {
                $imagePath = $uploadedPath;
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            if ($partId > 0) {
                // Update
                $updStmt = $pdo->prepare("
                    UPDATE parts SET 
                        brand_id = ?, name = ?, sku = ?, category = ?, 
                        description = ?, stock_qty = ?, part_price = ?, 
                        install_fee = ?, image_path = ? 
                    WHERE id = ?
                ");
                $updStmt->execute([
                    $brandId, $name, $sku, $category,
                    $description, $stockQty, $partPrice,
                    $installFee, $imagePath, $partId
                ]);
                set_flash('success', "Auto part '{$name}' updated successfully.");
            } else {
                // Insert
                $insStmt = $pdo->prepare("
                    INSERT INTO parts (
                        brand_id, name, sku, category, description, 
                        stock_qty, part_price, install_fee, image_path, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $insStmt->execute([
                    $brandId, $name, $sku, $category, $description,
                    $stockQty, $partPrice, $installFee, $imagePath
                ]);
                set_flash('success', "New auto part '{$name}' added to inventory.");
            }
            header("Location: " . BASE_URL . "/admin/parts.php");
            exit;
        } catch (Exception $e) {
            $errors[] = 'Failed to save part: ' . $e->getMessage();
        }
    }
}

// Filter or Fetch All Parts
$filterBrand = trim($_GET['brand'] ?? 'all');
$filterSearch = trim($_GET['q'] ?? '');

$sql = "SELECT p.*, b.name AS brand_name 
        FROM parts p 
        JOIN brands b ON p.brand_id = b.id 
        WHERE 1=1";
$params = [];

if ($filterBrand !== 'all' && !empty($filterBrand)) {
    $sql .= " AND b.id = ?";
    $params[] = (int)$filterBrand;
}
if (!empty($filterSearch)) {
    $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%{$filterSearch}%";
    $params[] = "%{$filterSearch}%";
}
$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$partsList = $stmt->fetchAll();

$pageHeading = 'PARTS & INVENTORY MANAGER';
?>

<?php if (!empty($errors)): ?>
  <div class="retro-alert retro-alert-danger" style="margin-bottom:20px;">
    <span class="alert-tag">[ERROR]</span>
    <div><?= implode('<br>', array_map('e', $errors)) ?></div>
  </div>
<?php endif; ?>

<!-- Subheader Actions -->
<div class="toolbar-wrap" style="background:var(--color-oil-slate); border:2px solid #000; padding:16px 20px; box-shadow:var(--shadow-hard); margin-bottom:24px;">
  <div>
    <h3 style="margin:0; font-size:1.5rem; color:var(--color-manual-cream);">INVENTORY CATALOG</h3>
    <span style="font-family:var(--font-mono); font-size:0.75rem; color:var(--color-muted-grey);">
      TOTAL REGISTERED PARTS: <?= count($partsList) ?>
    </span>
  </div>

  <div style="display:flex; gap:12px; align-items:center;">
    <a href="<?= BASE_URL ?>/admin/parts.php?action=new" class="retro-btn retro-btn-sm retro-btn-amber">
      ➕ ADD NEW AUTO PART
    </a>
  </div>
</div>

<?php if ($action === 'new' || $action === 'edit' || $editPart): ?>
  <!-- ============================================================== -->
  <!-- ADD / EDIT PART FORM                                           -->
  <!-- ============================================================== -->
  <div class="retro-card" style="margin-bottom:30px;">
    <div class="retro-card-header">
      <h3 class="retro-card-title text-teal" style="font-size:1.4rem;">
        <?= $editPart ? '✏️ EDIT AUTO PART: ' . e($editPart['name']) : '➕ REGISTER NEW AUTO PART TO CATALOG' ?>
      </h3>
      <a href="<?= BASE_URL ?>/admin/parts.php" class="retro-btn retro-btn-sm retro-btn-dark">
        Cancel / Back to List
      </a>
    </div>

    <div class="retro-card-body">
      <form method="POST" action="<?= BASE_URL ?>/admin/parts.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="part_id" value="<?= $editPart ? (int)$editPart['id'] : 0 ?>">
        <input type="hidden" name="existing_image" value="<?= e($editPart['image_path'] ?? '') ?>">

        <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:24px;">
          
          <!-- Column 1 -->
          <div>
            <div class="form-group">
              <label class="form-label">Vehicle Brand <span class="required">*</span></label>
              <select name="brand_id" class="retro-select" required>
                <option value="">-- Select Brand --</option>
                <?php foreach ($brands as $b): ?>
                  <option value="<?= $b['id'] ?>" <?= ($editPart && (int)$editPart['brand_id'] === (int)$b['id']) ? 'selected' : '' ?>>
                    <?= e($b['name']) ?> (<?= e($b['country']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Part Name <span class="required">*</span></label>
              <input type="text" name="name" class="retro-input" required placeholder="e.g. Honda - Heavy Duty Clutch Kit" value="<?= e($editPart['name'] ?? '') ?>">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
              <div class="form-group">
                <label class="form-label">Part SKU / Code</label>
                <input type="text" name="sku" class="retro-input" placeholder="e.g. HND-CL-091" value="<?= e($editPart['sku'] ?? '') ?>">
                <div class="form-hint">Leave blank to auto-generate.</div>
              </div>

              <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="retro-input" placeholder="e.g. Brakes & Suspension" value="<?= e($editPart['category'] ?? 'Engine & Transmission') ?>">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Technical Description & Compatibility</label>
              <textarea name="description" class="retro-textarea" placeholder="Detailed mechanical specs, fitment notes, torque ratings..."><?= e($editPart['description'] ?? '') ?></textarea>
            </div>
          </div>

          <!-- Column 2: Pricing, Stock & Image -->
          <div>
            <div style="background:var(--color-asphalt-dark); border:2px solid #000; padding:18px; margin-bottom:20px;">
              <h5 style="font-family:var(--font-mono); font-size:0.85rem; color:var(--color-dash-amber); margin-bottom:14px;">
                [PRICING & STOCK METRICS]
              </h5>

              <div class="form-group">
                <label class="form-label">Stock Quantity (Units) <span class="required">*</span></label>
                <input type="number" name="stock_qty" class="retro-input" min="0" required value="<?= $editPart ? (int)$editPart['stock_qty'] : 10 ?>">
              </div>

              <div class="form-group">
                <label class="form-label">Part Price (Buy Part Only) (<?= CURRENCY_SYMBOL ?>) <span class="required">*</span></label>
                <input type="number" step="0.01" name="part_price" class="retro-input" required value="<?= $editPart ? (float)$editPart['part_price'] : '0.00' ?>">
              </div>

              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Installation / Replacement Labor Fee (<?= CURRENCY_SYMBOL ?>) <span class="required">*</span></label>
                <input type="number" step="0.01" name="install_fee" class="retro-input" required value="<?= $editPart ? (float)$editPart['install_fee'] : '0.00' ?>">
                <div class="form-hint">Additional labor fee charged when customer selects "+ Garage Installation".</div>
              </div>
            </div>

            <!-- Image Upload & Preview -->
            <div class="form-group">
              <label class="form-label">Part Image (JPG, PNG, WEBP, SVG)</label>
              <input type="file" name="image" class="retro-input part-image-input" accept="image/png, image/jpeg, image/webp, image/svg+xml">
              
              <div style="margin-top:10px; background:#111; border:1px solid #333; padding:10px; text-align:center;">
                <span style="font-family:var(--font-mono); font-size:0.7rem; color:#888; display:block; margin-bottom:6px;">IMAGE PREVIEW / FALLBACK:</span>
                <img 
                  src="<?= !empty($editPart['image_path']) ? BASE_URL . '/' . $editPart['image_path'] : BASE_URL . '/assets/images/placeholder-part.svg' ?>" 
                  class="part-image-preview" 
                  style="max-height:120px; max-width:100%; object-fit:contain; margin:0 auto;"
                  alt="Preview"
                >
              </div>
            </div>

            <button type="submit" class="retro-btn retro-btn-primary retro-btn-block" style="padding:14px; font-size:1.15rem;">
              <?= $editPart ? 'SAVE PART MODIFICATIONS' : 'ADD PART TO INVENTORY →' ?>
            </button>
          </div>

        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- ============================================================== -->
<!-- PARTS LIST TABLE                                               -->
<!-- ============================================================== -->
<div class="retro-card">
  <div class="retro-card-header">
    <h3 class="retro-card-title text-amber">
      <span>📦</span> INVENTORY STOCK CONTROL
    </h3>
    <span class="retro-badge" style="background:#000; color:var(--color-dash-amber);">READY FOR ORDERS</span>
  </div>

  <div class="admin-table-wrap" style="margin:0; box-shadow:none;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Thumb</th>
          <th>Brand</th>
          <th>Part Name & SKU</th>
          <th>Stock Status</th>
          <th>Part Price</th>
          <th>Installation Fee</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($partsList as $p): ?>
          <?php
            $stockStatus = (int)$p['stock_qty'] <= 0 ? 'Out of Stock' : ((int)$p['stock_qty'] <= 8 ? 'Low Stock' : 'In Stock');
            $stockClass = (int)$p['stock_qty'] <= 0 ? 'badge-stock-out' : ((int)$p['stock_qty'] <= 8 ? 'badge-stock-low' : 'badge-stock-in');
            $thumb = !empty($p['image_path']) ? BASE_URL . '/' . $p['image_path'] : BASE_URL . '/assets/images/placeholder-part.svg';
          ?>
          <tr>
            <td style="width:60px;">
              <div style="width:50px; height:50px; background:#111; border:1px solid #000; display:flex; align-items:center; justify-content:center; padding:2px;">
                <img src="<?= e($thumb) ?>" alt="<?= e($p['name']) ?>" style="max-height:100%; max-width:100%; object-fit:contain;">
              </div>
            </td>
            <td>
              <span class="retro-badge badge-brand"><?= e($p['brand_name']) ?></span>
            </td>
            <td>
              <strong style="color:var(--color-manual-cream); font-size:1rem;"><?= e($p['name']) ?></strong><br>
              <span style="font-family:var(--font-mono); font-size:0.75rem; color:var(--color-dash-amber);">SKU: <?= e($p['sku']) ?></span>
            </td>
            <td>
              <span class="retro-badge <?= $stockClass ?>">
                <?= $stockStatus ?> (<?= $p['stock_qty'] ?>)
              </span>
            </td>
            <td style="font-family:var(--font-mono); font-weight:700; color:var(--color-dash-amber);">
              <?= format_price($p['part_price']) ?>
            </td>
            <td style="font-family:var(--font-mono); font-weight:700; color:var(--color-gauge-teal);">
              + <?= format_price($p['install_fee']) ?>
            </td>
            <td>
              <div style="display:flex; gap:6px;">
                <a href="<?= BASE_URL ?>/part-detail.php?id=<?= $p['id'] ?>" target="_blank" class="retro-btn retro-btn-sm retro-btn-cream" title="View in Store">
                  👁️
                </a>
                <a href="<?= BASE_URL ?>/admin/parts.php?action=edit&id=<?= $p['id'] ?>" class="retro-btn retro-btn-sm retro-btn-dark">
                  Edit ✏️
                </a>
                <a 
                  href="<?= BASE_URL ?>/admin/parts.php?action=delete&id=<?= $p['id'] ?>" 
                  class="retro-btn retro-btn-sm retro-btn-primary"
                  onclick="return confirm('Delete part \'<?= addslashes(e($p['name'])) ?>\' from inventory?');"
                >
                  ✕
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
