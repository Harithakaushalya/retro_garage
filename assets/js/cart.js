/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - INTERACTIVE SHOPPING CART (cart.js)
 * Real-time installation toggle, live subtotal breakdown & AJAX sync
 * ==============================================================
 */

document.addEventListener('DOMContentLoaded', () => {
  initProductCardToggles();
  initAddToCartButtons();
  initCartPageControls();
});

// Interactive Installation Toggle on Part Cards
function initProductCardToggles() {
  document.querySelectorAll('.part-card, .part-detail-view').forEach(card => {
    const installCheckbox = card.querySelector('.install-toggle-checkbox');
    const toggleBox = card.querySelector('.install-toggle-box');
    const dynamicPriceEl = card.querySelector('.dynamic-total-price');
    const basePrice = parseFloat(card.dataset.partPrice || 0);
    const installFee = parseFloat(card.dataset.installFee || 0);

    if (installCheckbox && toggleBox) {
      installCheckbox.addEventListener('change', () => {
        if (installCheckbox.checked) {
          toggleBox.classList.add('selected');
        } else {
          toggleBox.classList.remove('selected');
        }
        
        // Update price display if dynamic element exists
        if (dynamicPriceEl) {
          const qtyInput = card.querySelector('.quantity-input');
          const qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
          const currentTotal = (basePrice * qty) + (installCheckbox.checked ? (installFee * qty) : 0);
          const currency = card.dataset.currency || 'Rs.';
          dynamicPriceEl.textContent = `${currency} ${currentTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }
      });
    }

    // Detail page quantity change listener
    const qtyInput = card.querySelector('.quantity-input');
    if (qtyInput && dynamicPriceEl) {
      qtyInput.addEventListener('input', () => {
        const qty = Math.max(1, parseInt(qtyInput.value) || 1);
        const isInstall = installCheckbox ? installCheckbox.checked : false;
        const currentTotal = (basePrice * qty) + (isInstall ? (installFee * qty) : 0);
        const currency = card.dataset.currency || 'Rs.';
        dynamicPriceEl.textContent = `${currency} ${currentTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
      });
    }
  });
}

// AJAX Add to Cart Buttons
function initAddToCartButtons() {
  document.querySelectorAll('.btn-add-to-cart').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const partId = btn.dataset.partId;
      const card = btn.closest('.part-card, .part-detail-view, .product-container');
      
      let quantity = 1;
      let includeInstall = false;

      if (card) {
        const qtyInput = card.querySelector('.quantity-input');
        if (qtyInput) quantity = Math.max(1, parseInt(qtyInput.value) || 1);
        const installCheck = card.querySelector('.install-toggle-checkbox');
        if (installCheck) includeInstall = installCheck.checked;
      }

      const origText = btn.innerHTML;
      btn.innerHTML = '<span>ADDING...</span>';
      btn.disabled = true;

      try {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('part_id', partId);
        formData.append('quantity', quantity);
        formData.append('include_install', includeInstall ? '1' : '0');

        const res = await fetch('api/cart.php', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });

        const data = await res.json();
        if (data.success) {
          updateNavCartBadge(data.cart_count);
          const installLabel = includeInstall ? ' (+ Garage Installation)' : ' (Part Only)';
          window.showRetroToast(`Added ${data.part_name || 'Part'}${installLabel} to cart!`, 'teal');
        } else {
          window.showRetroToast(data.message || 'Error adding part to cart', 'red');
        }
      } catch (err) {
        console.error('Cart error:', err);
        window.showRetroToast('Network error updating cart', 'red');
      } finally {
        btn.innerHTML = origText;
        btn.disabled = false;
      }
    });
  });
}

// Cart Page Interactive Recalculation
function initCartPageControls() {
  const cartTable = document.querySelector('.cart-table');
  if (!cartTable) return;

  // Qty plus/minus
  cartTable.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const row = btn.closest('tr');
      const input = row.querySelector('.qty-input');
      const current = parseInt(input.value) || 1;
      const partId = row.dataset.partId;
      let next = current;

      if (btn.classList.contains('qty-plus')) {
        next = current + 1;
      } else if (btn.classList.contains('qty-minus')) {
        next = Math.max(1, current - 1);
      }

      if (next !== current) {
        input.value = next;
        syncCartItem(partId, next, row.querySelector('.cart-install-checkbox')?.checked);
      }
    });
  });

  // Direct qty input change
  cartTable.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('change', () => {
      const row = input.closest('tr');
      const partId = row.dataset.partId;
      const val = Math.max(1, parseInt(input.value) || 1);
      input.value = val;
      syncCartItem(partId, val, row.querySelector('.cart-install-checkbox')?.checked);
    });
  });

  // Installation checkbox in cart row
  cartTable.querySelectorAll('.cart-install-checkbox').forEach(chk => {
    chk.addEventListener('change', () => {
      const row = chk.closest('tr');
      const partId = row.dataset.partId;
      const qty = parseInt(row.querySelector('.qty-input')?.value) || 1;
      syncCartItem(partId, qty, chk.checked);
    });
  });

  // Remove item button
  cartTable.querySelectorAll('.btn-remove-item').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const row = btn.closest('tr');
      const partId = row.dataset.partId;
      if (!confirm('Remove this part from workshop cart?')) return;

      const formData = new FormData();
      formData.append('action', 'remove');
      formData.append('part_id', partId);

      try {
        const res = await fetch('api/cart.php', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });
        const data = await res.json();
        if (data.success) {
          row.remove();
          updateCartSummaryUI(data);
          updateNavCartBadge(data.cart_count);
          window.showRetroToast('Part removed from cart', 'amber');

          // If cart empty
          if (data.cart_count === 0) {
            location.reload();
          }
        }
      } catch (err) {
        console.error(err);
      }
    });
  });
}

// Sync cart changes with API
async function syncCartItem(partId, qty, includeInstall) {
  const formData = new FormData();
  formData.append('action', 'update');
  formData.append('part_id', partId);
  formData.append('quantity', qty);
  formData.append('include_install', includeInstall ? '1' : '0');

  try {
    const res = await fetch('api/cart.php', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      updateCartSummaryUI(data);
      updateNavCartBadge(data.cart_count);
    }
  } catch (err) {
    console.error('Failed to sync cart item:', err);
  }
}

// Update summary panel & line totals
function updateCartSummaryUI(data) {
  const currency = data.currency || 'Rs.';

  // Update line totals in table
  if (data.items) {
    data.items.forEach(item => {
      const row = document.querySelector(`tr[data-part-id="${item.part_id}"]`);
      if (row) {
        const lineTotalEl = row.querySelector('.item-line-total');
        if (lineTotalEl) {
          lineTotalEl.textContent = `${currency} ${parseFloat(item.line_total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }
        const installSubtotalEl = row.querySelector('.item-install-subtotal');
        if (installSubtotalEl) {
          installSubtotalEl.textContent = item.include_install
            ? `+ ${currency} ${parseFloat(item.install_subtotal).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`
            : 'None';
        }
      }
    });
  }

  // Update Summary Box
  const partsCostEl = document.querySelector('.summary-parts-total');
  const installCostEl = document.querySelector('.summary-install-total');
  const grandTotalEl = document.querySelector('.summary-grand-total');

  if (partsCostEl && data.total_parts !== undefined) {
    partsCostEl.textContent = `${currency} ${parseFloat(data.total_parts).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
  }
  if (installCostEl && data.total_install !== undefined) {
    installCostEl.textContent = `${currency} ${parseFloat(data.total_install).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
  }
  if (grandTotalEl && data.grand_total !== undefined) {
    grandTotalEl.textContent = `${currency} ${parseFloat(data.grand_total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
  }
}

// Update Nav Cart Counter Badge
function updateNavCartBadge(count) {
  const badge = document.querySelector('.cart-count-badge');
  if (badge) {
    badge.textContent = count || 0;
  }
}
