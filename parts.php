<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - SPARE PARTS STORE (parts.php)
 * Dynamic brand filtering, search, stock levels & installation toggle
 * ==============================================================
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = get_db_connection();

$selectedBrand = trim($_GET['brand'] ?? 'all');
$searchQuery = trim($_GET['q'] ?? '');
$selectedCategory = trim($_GET['category'] ?? 'all');
$sort = trim($_GET['sort'] ?? 'newest');

// Fetch Brands
$brandsStmt = $pdo->query("SELECT * FROM brands ORDER BY name ASC");
$brands = $brandsStmt->fetchAll();

// Fetch Categories
$catStmt = $pdo->query("SELECT DISTINCT category FROM parts WHERE category IS NOT NULL ORDER BY category ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Build Query
$sql = "SELECT p.*, b.name AS brand_name, b.slug AS brand_slug 
        FROM parts p 
        JOIN brands b ON p.brand_id = b.id 
        WHERE 1=1";
$params = [];

if (!empty($selectedBrand) && $selectedBrand !== 'all') {
    $sql .= " AND (b.slug = ? OR b.name = ?)";
    $params[] = $selectedBrand;
    $params[] = $selectedBrand;
}

if (!empty($selectedCategory) && $selectedCategory !== 'all') {
    $sql .= " AND p.category = ?";
    $params[] = $selectedCategory;
}

if (!empty($searchQuery)) {
    $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
    $wildcard = '%' . $searchQuery . '%';
    $params[] = $wildcard;
    $params[] = $wildcard;
    $params[] = $wildcard;
}

$sql .= match ($sort) {
    'price_asc'  => " ORDER BY p.part_price ASC",
    'price_desc' => " ORDER BY p.part_price DESC",
    'name_asc'   => " ORDER BY p.name ASC",
    default      => " ORDER BY p.id DESC"
};

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$parts = $stmt->fetchAll();

$pageTitle = 'Genuine Japanese Spare Parts & Drivetrain Store • ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 30px 20px 60px 20px;">
  
  <!-- Header Banner -->
  <div style="background:var(--color-oil-slate); border:2px solid #000; box-shadow:var(--shadow-hard); padding:24px; margin-bottom:30px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
      <div>
        <div style="font-family:var(--font-mono); font-size:0.8rem; color:var(--color-dash-amber); font-weight:700; letter-spacing:1px;">
          [FACTORY STAGE INVENTORY • LKR PRICING • OPTIONAL INSTALLATION]
        </div>
        <h1 style="font-size:2.6rem; margin:0;">SPARE PARTS STORE & PERFORMANCE SHOP</h1>
      </div>

      <div style="font-family:var(--font-mono); font-size:0.9rem; background:var(--color-asphalt-dark); padding:10px 16px; border:1px solid #000;">
        PARTS IN CATALOG: <strong style="color:var(--color-dash-amber);"><?= count($parts) ?></strong>
      </div>
    </div>
  </div>

  <!-- Search & Filter Controls Panel -->
  <div style="background:var(--color-oil-slate); border:2px solid #000; padding:20px; margin-bottom:24px; box-shadow:var(--shadow-hard);">
    <form method="GET" action="<?= BASE_URL ?>/parts.php" id="parts-filter-form">
      
      <!-- Top Row: Search and Sort -->
      <div style="display:grid; grid-template-columns:2fr 1fr 1fr; gap:14px; margin-bottom:16px;">
        <div>
          <label class="form-label">Search Part Name, SKU, or Keyword</label>
          <input 
            type="text" 
            name="q" 
            class="retro-input" 
            placeholder="e.g. Clutch Plate, Spark Plugs, HND-CL-091..." 
            value="<?= e($searchQuery) ?>"
          >
        </div>

        <div>
          <label class="form-label">Filter Category</label>
          <select name="category" class="retro-select" onchange="this.form.submit()">
            <option value="all">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= e($cat) ?>" <?= $selectedCategory === $cat ? 'selected' : '' ?>>
                <?= e($cat) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label">Sort Pricing / Name</label>
          <select name="sort" class="retro-select" onchange="this.form.submit()">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Stock</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
          </select>
        </div>
      </div>

      <!-- Bottom Row: Dynamic Brand Pills -->
      <div>
        <label class="form-label">Filter by Vehicle Brand:</label>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <a 
            href="<?= BASE_URL ?>/parts.php?brand=all&q=<?= urlencode($searchQuery) ?>&category=<?= urlencode($selectedCategory) ?>" 
            class="brand-pill-btn <?= ($selectedBrand === 'all' || empty($selectedBrand)) ? 'active' : '' ?>"
          >
            ALL BRANDS
          </a>
          <?php foreach ($brands as $b): ?>
            <a 
              href="<?= BASE_URL ?>/parts.php?brand=<?= urlencode($b['slug']) ?>&q=<?= urlencode($searchQuery) ?>&category=<?= urlencode($selectedCategory) ?>" 
              class="brand-pill-btn <?= ($selectedBrand === $b['slug'] || $selectedBrand === $b['name']) ? 'active' : '' ?>"
            >
              <?= e($b['name']) ?>
            </a>
          <?php endforeach; ?>

          <?php if (!empty($searchQuery) || $selectedBrand !== 'all' || $selectedCategory !== 'all'): ?>
            <a href="<?= BASE_URL ?>/parts.php" class="retro-btn retro-btn-sm retro-btn-dark" style="margin-left:auto;">
              Reset Filters ✕
            </a>
          <?php endif; ?>
        </div>
      </div>

    </form>
  </div>

  <!-- Parts Grid -->
  <?php if (empty($parts)): ?>
    <div style="background:var(--color-asphalt-dark); border:2px solid #000; padding:50px; text-align:center; box-shadow:var(--shadow-hard);">
      <div style="font-size:3rem; margin-bottom:14px;">🔍</div>
      <h3>NO SPARE PARTS FOUND</h3>
      <p style="font-family:var(--font-mono); color:var(--color-muted-grey); margin-bottom:20px;">
        No auto parts matched your search or brand filter. Try clearing filters or searching another keyword.
      </p>
      <a href="<?= BASE_URL ?>/parts.php" class="retro-btn retro-btn-amber">
        SHOW ALL PARTS CATALOG
      </a>
    </div>
  <?php else: ?>
    <div class="parts-grid">
      <?php foreach ($parts as $part): ?>
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
          <!-- Media Thumbnail -->
          <div class="part-card-media">
            <img src="<?= e($imagePath) ?>" alt="<?= e($part['name']) ?>" loading="lazy">
          </div>

          <!-- Body -->
          <div class="part-card-body">
            
            <div class="part-card-meta">
              <span class="retro-badge badge-brand"><?= e($part['brand_name']) ?></span>
              <span class="retro-badge <?= $stockClass ?>"><?= $stockStatus ?> (<?= $part['stock_qty'] ?>)</span>
            </div>

            <div style="font-family:var(--font-mono); font-size:0.75rem; color:var(--color-muted-grey); margin-bottom:4px;">
              SKU: <strong style="color:var(--color-dash-amber);"><?= e($part['sku']) ?></strong>
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
                <span class="part-price-label">Part Only:</span>
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
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
