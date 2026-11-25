<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - BRAND MANAGER (admin/brands.php)
 * ==============================================================
 */

require_once __DIR__ . '/includes/admin-header.php';

$errors = [];
$editBrand = null;

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $delId = (int)($_GET['id'] ?? 0);
    if ($delId > 0) {
        $delStmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
        $delStmt->execute([$delId]);
        set_flash('success', 'Brand removed from database.');
        header("Location: " . BASE_URL . "/admin/brands.php");
        exit;
    }
}

// Handle Edit Fetch
if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    $editId = (int)($_GET['id'] ?? 0);
    if ($editId > 0) {
        $fetchStmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
        $fetchStmt->execute([$editId]);
        $editBrand = $fetchStmt->fetch();
    }
}

// Handle Add / Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name    = trim($_POST['name'] ?? '');
    $slug    = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'] ?? '')));
    $country = trim($_POST['country'] ?? 'Japan');
    $brandId = (int)($_POST['brand_id'] ?? 0);

    if (empty($name)) {
        $errors[] = 'Brand name is required.';
    }
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }

    if (empty($errors)) {
        try {
            if ($brandId > 0) {
                // Update
                $updStmt = $pdo->prepare("UPDATE brands SET name = ?, slug = ?, country = ? WHERE id = ?");
                $updStmt->execute([$name, $slug, $country, $brandId]);
                set_flash('success', "Brand '{$name}' updated successfully.");
            } else {
                // Insert
                $insStmt = $pdo->prepare("INSERT INTO brands (name, slug, country, created_at) VALUES (?, ?, ?, NOW())");
                $insStmt->execute([$name, $slug, $country]);
                set_flash('success', "New vehicle brand '{$name}' created.");
            }
            header("Location: " . BASE_URL . "/admin/brands.php");
            exit;
        } catch (Exception $e) {
            $errors[] = 'Failed to save brand: ' . $e->getMessage();
        }
    }
}

// Fetch all brands with parts count
$brandsStmt = $pdo->query("
    SELECT b.*, COUNT(p.id) AS parts_count 
    FROM brands b 
    LEFT JOIN parts p ON b.id = p.brand_id 
    GROUP BY b.id 
    ORDER BY b.name ASC
");
$allBrands = $brandsStmt->fetchAll();

$pageHeading = 'VEHICLE BRAND MANAGER';
?>

<?php if (!empty($errors)): ?>
  <div class="retro-alert retro-alert-danger" style="margin-bottom:20px;">
    <span class="alert-tag">[ERROR]</span>
    <div><?= implode('<br>', array_map('e', $errors)) ?></div>
  </div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1.6fr 1fr; gap:30px; align-items:start;">
  
  <!-- Left Column: Existing Brands List -->
  <div class="retro-card">
    <div class="retro-card-header">
      <h3 class="retro-card-title text-amber">
        <span>🏷️</span> CONFIGURED VEHICLE BRANDS
      </h3>
      <span class="retro-badge" style="background:#000; color:var(--color-dash-amber);"><?= count($allBrands) ?> Brands</span>
    </div>

    <div class="admin-table-wrap" style="margin:0; box-shadow:none;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Brand Name</th>
            <th>Slug</th>
            <th>Country</th>
            <th>Parts Count</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allBrands as $b): ?>
            <tr>
              <td>
                <strong style="font-size:1.05rem; color:var(--color-manual-cream);"><?= e($b['name']) ?></strong>
              </td>
              <td>
                <code style="background:#000; padding:2px 6px; color:var(--color-dash-amber);"><?= e($b['slug']) ?></code>
              </td>
              <td><?= e($b['country']) ?></td>
              <td>
                <span class="retro-badge" style="background:var(--color-oil-light); color:#fff;">
                  <?= $b['parts_count'] ?> Parts
                </span>
              </td>
              <td>
                <div style="display:flex; gap:6px;">
                  <a href="<?= BASE_URL ?>/admin/brands.php?action=edit&id=<?= $b['id'] ?>" class="retro-btn retro-btn-sm retro-btn-dark">
                    Edit ✏️
                  </a>
                  <a 
                    href="<?= BASE_URL ?>/admin/brands.php?action=delete&id=<?= $b['id'] ?>" 
                    class="retro-btn retro-btn-sm retro-btn-primary"
                    onclick="return confirm('Delete brand \'<?= addslashes(e($b['name'])) ?>\'? Assigned parts will also be affected.');"
                  >
                    Delete ✕
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Right Column: Add / Edit Brand Form -->
  <div class="retro-card">
    <div class="retro-card-header">
      <h3 class="retro-card-title text-teal" style="font-size:1.3rem;">
        <?= $editBrand ? '✏️ EDIT BRAND' : '➕ ADD NEW VEHICLE BRAND' ?>
      </h3>
      <?php if ($editBrand): ?>
        <a href="<?= BASE_URL ?>/admin/brands.php" style="color:var(--color-dash-amber); font-family:var(--font-mono); font-size:0.8rem; text-decoration:underline;">
          Cancel Edit
        </a>
      <?php endif; ?>
    </div>

    <div class="retro-card-body">
      <form method="POST" action="<?= BASE_URL ?>/admin/brands.php">
        <?= csrf_field() ?>
        <input type="hidden" name="brand_id" value="<?= $editBrand ? (int)$editBrand['id'] : 0 ?>">

        <div class="form-group">
          <label class="form-label">Brand Name <span class="required">*</span></label>
          <input 
            type="text" 
            name="name" 
            class="retro-input" 
            required 
            placeholder="e.g. Mitsubishi, Subaru, Mazda..." 
            value="<?= e($editBrand['name'] ?? '') ?>"
          >
        </div>

        <div class="form-group">
          <label class="form-label">Brand Slug (URL Safe)</label>
          <input 
            type="text" 
            name="slug" 
            class="retro-input" 
            placeholder="e.g. mitsubishi" 
            value="<?= e($editBrand['slug'] ?? '') ?>"
          >
          <div class="form-hint">Leave blank to automatically slugify from Brand Name.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Origin Country</label>
          <input 
            type="text" 
            name="country" 
            class="retro-input" 
            placeholder="Japan, Germany, USA..." 
            value="<?= e($editBrand['country'] ?? 'Japan') ?>"
          >
        </div>

        <button type="submit" class="retro-btn retro-btn-teal retro-btn-block" style="padding:12px; margin-top:10px;">
          <?= $editBrand ? 'UPDATE BRAND DETAILS' : 'SAVE BRAND TO CATALOG →' ?>
        </button>
      </form>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
