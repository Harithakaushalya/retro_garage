<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - CONDITIONAL CHECKOUT (checkout.php)
 * Quick profile gate, payment simulation & stock deduction
 * ==============================================================
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/csrf.php';

$pdo = get_db_connection();
$cartDetails = get_cart_details($pdo);
$errors = [];

// If cart is empty, redirect back to cart
if (empty($cartDetails['items'])) {
    header("Location: " . BASE_URL . "/cart.php");
    exit;
}

// --------------------------------------------------------------
// Handle Quick Profile Creation from Checkout Gate
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'quick_register') {
    require_csrf();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $errors[] = 'All registration fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } else {
        // Check if email already registered
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists. Please switch to the Sign In tab.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare("
                INSERT INTO users (name, email, phone, password_hash, role, created_at)
                VALUES (?, ?, ?, ?, 'customer', NOW())
            ");
            $insert->execute([$name, $email, $phone, $hash]);
            $newUserId = (int)$pdo->lastInsertId();

            $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $userStmt->execute([$newUserId]);
            $newUser = $userStmt->fetch();

            login_user($newUser);
            set_flash('success', 'Customer profile created! Proceed with payment simulation.');
            header("Location: " . BASE_URL . "/checkout.php");
            exit;
        }
    }
}

// --------------------------------------------------------------
// Handle Quick Login from Checkout Gate
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'quick_login') {
    require_csrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing && password_verify($password, $existing['password_hash'])) {
            login_user($existing);
            set_flash('success', 'Welcome back, ' . $existing['name'] . '! Complete your order below.');
            header("Location: " . BASE_URL . "/checkout.php");
            exit;
        } else {
            $errors[] = 'Invalid email or password. Please try again.';
        }
    }
}

$user = current_user();

// --------------------------------------------------------------
// Handle Final Order Placement
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    require_csrf();

    if (!$user) {
        $errors[] = 'Please create a customer profile or sign in before confirming payment.';
    } else {
        $deliveryType   = $_POST['delivery_type'] ?? 'pickup_garage';
        $shippingAddress = trim($_POST['shipping_address'] ?? 'Counter Collection / Bay 42');
        $vehicleNumber   = clean_vehicle_no($_POST['vehicle_number'] ?? '');
        $paymentMethod   = $_POST['payment_method'] ?? 'pay_at_garage';

        // Payment status based on method simulation
        $paymentStatus = match ($paymentMethod) {
            'card_simulation' => 'Paid',
            'bank_transfer'   => 'Pending',
            'pay_at_garage'   => 'Pending',
            default           => 'Pending'
        };

        $orderStatus = ($paymentStatus === 'Paid') ? 'Paid' : 'Pending';

        // Begin DB Transaction for Atomic Stock Deduction & Order Creation
        try {
            $pdo->beginTransaction();

            // 1. Verify stock availability for all items
            foreach ($cartDetails['items'] as $item) {
                $stockCheck = $pdo->prepare("SELECT stock_qty, name FROM parts WHERE id = ? FOR UPDATE");
                $stockCheck->execute([$item['part_id']]);
                $currentStock = $stockCheck->fetch();

                if (!$currentStock || (int)$currentStock['stock_qty'] < $item['quantity']) {
                    throw new RuntimeException("Part '{$item['name']}' has insufficient stock (Available: " . ($currentStock['stock_qty'] ?? 0) . "). Please adjust cart quantity.");
                }
            }

            // 2. Generate Unique Order Code
            $orderCode = generate_order_code($pdo);

            // 3. Insert into `orders`
            $orderStmt = $pdo->prepare("
                INSERT INTO orders (
                    order_code, user_id, customer_name, customer_email, customer_phone, 
                    vehicle_number, delivery_type, shipping_address, 
                    total_parts_cost, total_install_cost, grand_total, 
                    payment_method, payment_status, order_status, admin_notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $adminNotes = "Order placed via online checkout. Method: {$paymentMethod}.";
            $orderStmt->execute([
                $orderCode,
                $user['id'],
                $user['name'],
                $user['email'],
                $user['phone'],
                $vehicleNumber ?: null,
                $deliveryType,
                $shippingAddress,
                $cartDetails['total_parts'],
                $cartDetails['total_install'],
                $cartDetails['grand_total'],
                $paymentMethod,
                $paymentStatus,
                $orderStatus,
                $adminNotes
            ]);
            $orderId = (int)$pdo->lastInsertId();

            // 4. Insert `order_items` & Deduct `stock_qty`
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_id, part_id, quantity, unit_part_price, 
                    include_install, unit_install_fee, line_total
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $deductStmt = $pdo->prepare("
                UPDATE parts 
                SET stock_qty = stock_qty - ? 
                WHERE id = ?
            ");

            foreach ($cartDetails['items'] as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['part_id'],
                    $item['quantity'],
                    $item['unit_part_price'],
                    $item['include_install'] ? 1 : 0,
                    $item['unit_install_fee'],
                    $item['line_total']
                ]);

                // Deduct stock
                $deductStmt->execute([
                    $item['quantity'],
                    $item['part_id']
                ]);
            }

            // 5. Create Notification
            $notifTitle = "New Order Confirmed: {$orderCode}";
            $notifMsg = "Order {$orderCode} for " . format_price($cartDetails['grand_total']) . " has been placed. Status: {$orderStatus}.";
            create_notification(
                $user['id'],
                null,
                $vehicleNumber ?: null,
                $notifTitle,
                $notifMsg
            );

            // 6. Commit Transaction & Clear Cart
            $pdo->commit();
            clear_cart();

            // Redirect to printable invoice
            header("Location: " . BASE_URL . "/order-success.php?order=" . urlencode($orderCode));
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Checkout failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Secure Checkout & Payment • ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 40px 20px 80px 20px;">
  
  <div style="background:var(--color-oil-slate); border:2px solid #000; box-shadow:var(--shadow-hard); padding:20px; margin-bottom:24px;">
    <div style="font-family:var(--font-mono); font-size:0.78rem; color:var(--color-dash-amber); font-weight:700;">[STAGE 2 CHECKOUT GATE & PAYMENT SIMULATION]</div>
    <h1 style="font-size:2.2rem; margin:0;">ORDER PAYMENT & FITTING RESERVATION</h1>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="retro-alert retro-alert-danger" style="margin-bottom:24px;">
      <span class="alert-tag">[ERROR]</span>
      <div><?= implode('<br>', array_map('e', $errors)) ?></div>
    </div>
  <?php endif; ?>

  <!-- CONDITIONAL GATE: If user NOT logged in, show Quick Profile / Sign In Screen -->
  <?php if (!$user): ?>
    
    <div style="max-width:680px; margin:0 auto; background:var(--color-oil-slate); border:3px solid #000; box-shadow:var(--shadow-hard-lg);">
      <div style="background:var(--color-asphalt-dark); padding:20px; border-bottom:2px solid #000;">
        <div class="brand-badge-box" style="display:inline-block; margin-bottom:6px;">CHECKOUT GATE</div>
        <h2 style="font-size:1.8rem; margin:0; color:var(--color-dash-amber);">QUICK PROFILE OR SIGN IN REQUIRED</h2>
        <p style="font-family:var(--font-mono); font-size:0.85rem; color:var(--color-muted-grey); margin-top:4px;">
          You were not asked to register while browsing or configuring parts. Please create a quick profile now so we can issue your invoice and notify you of dispatch.
        </p>
      </div>

      <div class="modal-tabs">
        <button type="button" class="modal-tab-btn active" data-tab="checkout-register">Create New Profile (10 Seconds)</button>
        <button type="button" class="modal-tab-btn" data-tab="checkout-login">Existing Account? Sign In</button>
      </div>

      <div style="padding:28px;">
        <!-- Register Form -->
        <div id="checkout-register" class="modal-tab-pane">
          <form method="POST" action="<?= BASE_URL ?>/checkout.php?action=quick_register">
            <?= csrf_field() ?>
            
            <div class="form-group">
              <label class="form-label">Full Customer Name <span class="required">*</span></label>
              <input type="text" name="name" class="retro-input" required placeholder="e.g. Kasun Perera" value="<?= e($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label class="form-label">Email Address <span class="required">*</span></label>
              <input type="email" name="email" class="retro-input" required placeholder="name@domain.com" value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label class="form-label">Mobile Phone (for WhatsApp alerts) <span class="required">*</span></label>
              <input type="text" name="phone" class="retro-input" required placeholder="+94 77 123 4567" value="<?= e($_POST['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label class="form-label">Create Password <span class="required">*</span></label>
              <input type="password" name="password" class="retro-input" required placeholder="Minimum 6 characters">
            </div>

            <button type="submit" class="retro-btn retro-btn-primary retro-btn-block" style="padding:14px; font-size:1.15rem; margin-top:10px;">
              CREATE PROFILE & CONTINUE TO PAYMENT →
            </button>
          </form>
        </div>

        <!-- Login Form -->
        <div id="checkout-login" class="modal-tab-pane" style="display:none;">
          <form method="POST" action="<?= BASE_URL ?>/checkout.php?action=quick_login">
            <?= csrf_field() ?>
            
            <div class="form-group">
              <label class="form-label">Email Address <span class="required">*</span></label>
              <input type="email" name="email" class="retro-input" required placeholder="name@domain.com">
            </div>

            <div class="form-group">
              <label class="form-label">Password <span class="required">*</span></label>
              <input type="password" name="password" class="retro-input" required placeholder="Your password">
            </div>

            <button type="submit" class="retro-btn retro-btn-amber retro-btn-block" style="padding:14px; font-size:1.15rem; margin-top:10px;">
              SIGN IN & CONTINUE TO PAYMENT →
            </button>
          </form>
        </div>
      </div>
    </div>

  <!-- If user IS logged in, render the payment simulation & order completion form -->
  <?php else: ?>
    
    <div style="display:grid; grid-template-columns:1.4fr 1fr; gap:30px; align-items:start;">
      
      <!-- Left Column: Checkout Details & Payment Simulation -->
      <div>
        <form method="POST" action="<?= BASE_URL ?>/checkout.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="place_order">

          <!-- Customer Identity Box -->
          <div class="retro-card" style="margin-bottom:20px;">
            <div class="retro-card-header">
              <h4 class="retro-card-title text-amber" style="font-size:1.2rem;">1. CUSTOMER IDENTITY & DISPATCH CONTACT</h4>
              <span class="retro-badge" style="background:var(--color-gauge-teal); color:#fff;">VERIFIED PROFILE</span>
            </div>
            <div class="retro-card-body" style="background:var(--color-asphalt-dark);">
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; font-family:var(--font-mono); font-size:0.9rem;">
                <div>
                  <span style="color:var(--color-muted-grey); display:block; font-size:0.75rem;">CUSTOMER NAME:</span>
                  <strong style="color:var(--color-manual-cream); font-size:1.05rem;"><?= e($user['name']) ?></strong>
                </div>
                <div>
                  <span style="color:var(--color-muted-grey); display:block; font-size:0.75rem;">EMAIL ADDRESS:</span>
                  <strong style="color:var(--color-manual-cream);"><?= e($user['email']) ?></strong>
                </div>
                <div>
                  <span style="color:var(--color-muted-grey); display:block; font-size:0.75rem;">PHONE NUMBER:</span>
                  <strong style="color:var(--color-dash-amber);"><?= e($user['phone']) ?></strong>
                </div>
                <div>
                  <span style="color:var(--color-muted-grey); display:block; font-size:0.75rem;">ACCOUNT ROLE:</span>
                  <strong style="color:var(--color-gauge-teal); text-transform:uppercase;"><?= e($user['role']) ?></strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Service Bay & Delivery Preference -->
          <div class="retro-card" style="margin-bottom:20px;">
            <div class="retro-card-header">
              <h4 class="retro-card-title" style="font-size:1.2rem;">2. DELIVERY & VEHICLE SPECIFICATION</h4>
            </div>
            <div class="retro-card-body">
              
              <div class="form-group">
                <label class="form-label">Delivery & Bay Preference <span class="required">*</span></label>
                <select name="delivery_type" class="retro-select" id="delivery-type-select">
                  <option value="pickup_garage">Workshop Bay Fitting / Counter Collection (Speedwell Bay 42)</option>
                  <option value="courier_delivery">Courier Delivery to Address (Parts Only, Sri Lanka Wide)</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Vehicle Registration Number (Recommended if Installation Chosen)</label>
                <input type="text" name="vehicle_number" class="retro-input" placeholder="e.g. WP CAB-4412" style="text-transform:uppercase; font-weight:700;">
                <div class="form-hint">Enables workshop mechanics to prepare bay diagnostics before vehicle arrives.</div>
              </div>

              <div class="form-group" id="address-group">
                <label class="form-label">Shipping Address / Collection Note</label>
                <textarea name="shipping_address" class="retro-textarea" placeholder="Provide delivery address or special workshop notes">Counter Collection - Speedwell Bay 42</textarea>
              </div>

            </div>
          </div>

          <!-- Payment Method Simulation Box -->
          <div class="retro-card">
            <div class="retro-card-header">
              <h4 class="retro-card-title text-amber" style="font-size:1.2rem;">3. PAYMENT METHOD SIMULATION</h4>
              <span class="retro-badge" style="background:#fff; color:#000;">DEMO SIMULATION</span>
            </div>
            <div class="retro-card-body">
              
              <!-- Payment Options Radios -->
              <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">
                
                <!-- Option 1: Card Simulation -->
                <label style="background:var(--color-asphalt-dark); border:2px solid #000; padding:14px; display:flex; align-items:flex-start; gap:12px; cursor:pointer;">
                  <input type="radio" name="payment_method" value="card_simulation" checked style="margin-top:4px; accent-color:var(--color-dash-amber);">
                  <div>
                    <strong style="font-family:var(--font-mono); font-size:0.95rem; color:var(--color-manual-cream);">
                      💳 Credit / Debit Card (Instant Simulation)
                    </strong>
                    <div style="font-size:0.8rem; color:var(--color-muted-grey); margin-top:2px;">
                      Simulates instant payment authorization with automatic "Paid" status.
                    </div>
                  </div>
                </label>

                <!-- Option 2: Pay at Garage -->
                <label style="background:var(--color-asphalt-dark); border:2px solid #000; padding:14px; display:flex; align-items:flex-start; gap:12px; cursor:pointer;">
                  <input type="radio" name="payment_method" value="pay_at_garage" style="margin-top:4px; accent-color:var(--color-dash-amber);">
                  <div>
                    <strong style="font-family:var(--font-mono); font-size:0.95rem; color:var(--color-manual-cream);">
                      💵 Pay at Garage Counter / Bay Appointment
                    </strong>
                    <div style="font-size:0.8rem; color:var(--color-muted-grey); margin-top:2px;">
                      Pay in cash or card upon collecting parts or completing bay installation.
                    </div>
                  </div>
                </label>

                <!-- Option 3: Bank Transfer -->
                <label style="background:var(--color-asphalt-dark); border:2px solid #000; padding:14px; display:flex; align-items:flex-start; gap:12px; cursor:pointer;">
                  <input type="radio" name="payment_method" value="bank_transfer" style="margin-top:4px; accent-color:var(--color-dash-amber);">
                  <div>
                    <strong style="font-family:var(--font-mono); font-size:0.95rem; color:var(--color-manual-cream);">
                      🏦 Direct Bank Transfer (Commercial Bank / HNB)
                    </strong>
                    <div style="font-size:0.8rem; color:var(--color-muted-grey); margin-top:2px;">
                      Transfer funds to Speedwell Retro Garage account and present slip upon collection.
                    </div>
                  </div>
                </label>

              </div>

              <!-- Card Simulator Fields -->
              <div id="card-fields-box" style="background:#111; border:1px solid var(--color-oil-border); padding:16px; margin-bottom:20px;">
                <div style="font-family:var(--font-mono); font-size:0.75rem; color:var(--color-dash-amber); margin-bottom:10px;">
                  [DEMO TEST CARD - PREFILLED AUTOMATICALLY]
                </div>
                <div style="display:grid; grid-template-columns:2fr 1fr 1fr; gap:10px;">
                  <div>
                    <label class="form-label" style="font-size:0.75rem;">Card Number</label>
                    <input type="text" class="retro-input" value="4532 •••• •••• 8892" readonly style="color:#aaa;">
                  </div>
                  <div>
                    <label class="form-label" style="font-size:0.75rem;">Exp Date</label>
                    <input type="text" class="retro-input" value="12/28" readonly style="color:#aaa;">
                  </div>
                  <div>
                    <label class="form-label" style="font-size:0.75rem;">CVC</label>
                    <input type="text" class="retro-input" value="702" readonly style="color:#aaa;">
                  </div>
                </div>
              </div>

              <!-- Confirm Order Button -->
              <button type="submit" class="retro-btn retro-btn-primary retro-btn-block" style="font-size:1.3rem; padding:16px;">
                CONFIRM ORDER & GENERATE INVOICE →
              </button>

            </div>
          </div>

        </form>
      </div>

      <!-- Right Column: Order Items Summary Breakdown -->
      <div>
        <div class="order-summary-box">
          <div class="summary-header">
            <h3>ORDER REQUISITION</h3>
            <span style="font-family:var(--font-mono); font-size:0.75rem; color:#444;">ITEMIZED BREAKDOWN</span>
          </div>

          <div style="margin-bottom:16px;">
            <?php foreach ($cartDetails['items'] as $item): ?>
              <div style="border-bottom:1px dashed #aaa; padding-bottom:10px; margin-bottom:10px; font-family:var(--font-mono);">
                <div style="display:flex; justify-content:space-between; font-weight:700; font-size:0.9rem;">
                  <span><?= e($item['name']) ?> × <?= $item['quantity'] ?></span>
                  <span><?= format_price($item['parts_subtotal']) ?></span>
                </div>
                <?php if ($item['include_install']): ?>
                  <div style="display:flex; justify-content:space-between; font-size:0.8rem; color:var(--color-gauge-teal); font-weight:600; margin-top:2px;">
                    <span>↳ + Bay Installation (×<?= $item['quantity'] ?>):</span>
                    <span>+ <?= format_price($item['install_subtotal']) ?></span>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="summary-row">
            <span>Total Parts Cost:</span>
            <span><?= format_price($cartDetails['total_parts']) ?></span>
          </div>

          <div class="summary-row">
            <span>Total Installation Fee:</span>
            <span style="color:var(--color-gauge-teal); font-weight:700;"><?= format_price($cartDetails['total_install']) ?></span>
          </div>

          <div class="summary-row total-row">
            <span>GRAND TOTAL:</span>
            <span><?= format_price($cartDetails['grand_total']) ?></span>
          </div>

          <div style="background:#fff; border:1px solid #000; padding:12px; margin-top:16px; font-family:var(--font-mono); font-size:0.75rem;">
            🛡️ <strong>Inventory Protection:</strong> Stock is reserved in real-time upon clicking Confirm Order.
          </div>
        </div>
      </div>

    </div>

  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
