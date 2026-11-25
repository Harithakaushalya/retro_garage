/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - ADMIN COMMAND SCRIPTS (admin.js)
 * Live status updates, WhatsApp generator & inventory management
 * ==============================================================
 */

document.addEventListener('DOMContentLoaded', () => {
  initStatusChangeModals();
  initWhatsAppButtons();
  initImageUploadPreviews();
});

// Status change & mechanic notes quick modal
function initStatusChangeModals() {
  const statusSelects = document.querySelectorAll('.booking-status-select');
  statusSelects.forEach(select => {
    select.addEventListener('change', () => {
      const row = select.closest('tr');
      const form = row.querySelector('.status-update-form');
      if (form) {
        form.submit();
      }
    });
  });
}

// WhatsApp generator helper
function initWhatsAppButtons() {
  document.querySelectorAll('.open-wa-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const phone = btn.dataset.phone;
      const name = btn.dataset.name;
      const vehicle = btn.dataset.vehicle;
      const status = btn.dataset.status;
      const ticket = btn.dataset.ticket || '';

      if (!phone) {
        alert('No phone number recorded for this booking/order.');
        e.preventDefault();
        return;
      }

      let message = `Hello ${name}, Speedwell Retro Garage update for your vehicle [${vehicle}]: Your job (Ticket: ${ticket}) status is now: *${status}*. Contact workshop counter for details.`;
      
      const cleanPhone = phone.replace(/[^0-9]/g, '');
      const formattedPhone = cleanPhone.startsWith('0') && cleanPhone.length === 10
        ? '94' + cleanPhone.substring(1)
        : cleanPhone;

      const url = `https://wa.me/${formattedPhone}?text=${encodeURIComponent(message)}`;
      window.open(url, '_blank');
      e.preventDefault();
    });
  });
}

// Image upload preview
function initImageUploadPreviews() {
  const fileInput = document.querySelector('input[type="file"].part-image-input');
  const previewImg = document.querySelector('.part-image-preview');

  if (fileInput && previewImg) {
    fileInput.addEventListener('change', () => {
      const file = fileInput.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          previewImg.src = e.target.result;
          previewImg.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  }
}
