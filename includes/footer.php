<?php
/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - FOOTER TEMPLATE (includes/footer.php)
 * ==============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/csrf.php';
?>

  <!-- Retro Footer -->
  <footer class="retro-footer">
    <div class="container">
      <div class="footer-grid">
        
        <!-- Garage Identity -->
        <div class="footer-col">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
            <div class="brand-badge-box" style="padding:2px 6px; font-size:0.75rem;">EST. 1988</div>
            <h5 style="margin:0; font-size:1.6rem;"><?= APP_NAME ?></h5>
          </div>
          <p style="font-family:var(--font-mono); font-size:0.85rem; color:var(--color-muted-grey); margin-bottom:14px; line-height:1.5;">
            <?= APP_TAGLINE ?><br>
            Factory-spec analog maintenance, Japanese domestic performance parts, and custom fabrication works.
          </p>
          <div style="font-family:var(--font-mono); font-size:0.82rem; color:var(--color-dash-amber);">
            📍 <?= GARAGE_ADDRESS ?>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-col">
          <h5>GARAGE SERVICES</h5>
          <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>/index.php#book-service">» Express Service Booking</a></li>
            <li><a href="<?= BASE_URL ?>/track.php">» Live Vehicle Job Tracker</a></li>
            <li><a href="<?= BASE_URL ?>/parts.php">» Genuine Parts Catalog</a></li>
            <li><a href="<?= BASE_URL ?>/cart.php">» Workshop Cart & Quotation</a></li>
          </ul>
        </div>

        <!-- Vehicle Brands Supported -->
        <div class="footer-col">
          <h5>BRAND SPECIALTIES</h5>
          <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>/parts.php?brand=honda">» Honda VTEC & Type-R</a></li>
            <li><a href="<?= BASE_URL ?>/parts.php?brand=toyota">» Toyota Heritage & GR</a></li>
            <li><a href="<?= BASE_URL ?>/parts.php?brand=suzuki">» Suzuki Compacts & 4x4</a></li>
            <li><a href="<?= BASE_URL ?>/parts.php?brand=nissan">» Nissan RB & SR Series</a></li>
            <li><a href="<?= BASE_URL ?>/parts.php?brand=yamaha">» Yamaha Sports Drivetrain</a></li>
          </ul>
        </div>

        <!-- Working Hours & Hotline -->
        <div class="footer-col">
          <h5>OPERATIONS & CONTACT</h5>
          <p style="font-family:var(--font-mono); font-size:0.82rem; color:var(--color-manual-cream); margin-bottom:10px;">
            🕒 <strong>Workshop Hours:</strong><br>
            <?= GARAGE_HOURS ?>
          </p>
          <p style="font-family:var(--font-mono); font-size:0.82rem; color:var(--color-dash-amber); margin-bottom:14px;">
            📞 <strong>Direct Hotline:</strong><br>
            <a href="tel:<?= GARAGE_PHONE ?>" style="color:var(--color-dash-amber); font-weight:700;"><?= GARAGE_PHONE ?></a>
          </p>
          <a href="https://wa.me/<?= GARAGE_WHATSAPP ?>" target="_blank" rel="noopener noreferrer" class="retro-btn retro-btn-sm retro-btn-teal" style="font-size:0.85rem;">
            💬 WhatsApp Garage Desk
          </a>
        </div>

      </div>

      <!-- Bottom Bar -->
      <div class="footer-bottom">
        <div>
          &copy; <?= date('Y') ?> <?= APP_NAME ?>. All Rights Reserved. Engineered for Classic & Modern JDM Works.
        </div>
        <div>
          <span style="color:var(--color-muted-grey);">ADMINISTRATIVE GATE:</span>
          <a href="<?= BASE_URL ?>/admin/index.php" style="color:var(--color-dash-amber); margin-left:6px; font-weight:700; text-decoration:underline;">Command Center</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- ============================================================== -->
  <!-- QUICK PROFILE & CHECKOUT MODAL (For non-logged in checkout)   -->
  <!-- ============================================================== -->
  <div id="quick-auth-modal" class="retro-modal-backdrop">
    <div class="retro-modal-content">
      
      <div class="modal-header">
        <h4 style="margin:0; font-size:1.4rem; color:var(--color-dash-amber); display:flex; align-items:center; gap:8px;">
          <span>⚡</span> QUICK PROFILE & CHECKOUT GATE
        </h4>
        <button type="button" class="modal-close-btn modal-close-trigger">&times;</button>
      </div>

      <div class="modal-tabs">
        <button type="button" class="modal-tab-btn active" data-tab="tab-create-profile">Create Profile</button>
        <button type="button" class="modal-tab-btn" data-tab="tab-quick-login">Already Registered? Sign In</button>
      </div>

      <div class="modal-body">
        
        <!-- Tab 1: Create Profile -->
        <div id="tab-create-profile" class="modal-tab-pane">
          <p style="font-family:var(--font-mono); font-size:0.85rem; color:var(--color-muted-grey); margin-bottom:18px;">
            Enter your details once to track this order, receive SMS/WhatsApp dispatch updates, and access your invoice.
          </p>
          
          <form method="POST" action="<?= BASE_URL ?>/checkout.php?action=quick_register">
            <?= csrf_field() ?>
            
            <div class="form-group">
              <label class="form-label">Full Name <span class="required">*</span></label>
              <input type="text" name="name" class="retro-input" required placeholder="e.g. Kasun Perera">
            </div>

            <div class="form-group">
              <label class="form-label">Email Address <span class="required">*</span></label>
              <input type="email" name="email" class="retro-input" required placeholder="name@domain.com">
            </div>

            <div class="form-group">
              <label class="form-label">Mobile Phone (for WhatsApp alerts) <span class="required">*</span></label>
              <input type="text" name="phone" class="retro-input" required placeholder="+94 77 123 4567">
            </div>

            <div class="form-group">
              <label class="form-label">Create Password <span class="required">*</span></label>
              <input type="password" name="password" class="retro-input" required placeholder="Minimum 6 characters">
            </div>

            <button type="submit" class="retro-btn retro-btn-primary retro-btn-block" style="margin-top:10px;">
              CREATE PROFILE & CONTINUE TO PAYMENT →
            </button>
          </form>
        </div>

        <!-- Tab 2: Quick Sign In -->
        <div id="tab-quick-login" class="modal-tab-pane" style="display:none;">
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

            <button type="submit" class="retro-btn retro-btn-amber retro-btn-block" style="margin-top:10px;">
              SIGN IN & CONTINUE TO PAYMENT →
            </button>
          </form>
        </div>

      </div>
    </div>
  </div>

  <!-- Core JavaScript Bundles -->
  <script src="<?= BASE_URL ?>/assets/js/retro-app.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/cart.js"></script>

</body>
</html>
