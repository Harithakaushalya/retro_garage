/**
 * ==============================================================
 * SPEEDWELL RETRO GARAGE - CORE APP SCRIPTS (retro-app.js)
 * ES6+ Vanilla JS
 * ==============================================================
 */

document.addEventListener('DOMContentLoaded', () => {
  initMobileNav();
  initNotificationBell();
  initVehicleTrackerBar();
  initModalTabs();
});

// Mobile Nav Toggle
function initMobileNav() {
  const toggle = document.querySelector('.mobile-nav-toggle');
  const links = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      links.classList.toggle('open');
    });
  }
}

// Live Notification Bell & Dropdown
function initNotificationBell() {
  const bellBtn = document.querySelector('.notif-bell-btn');
  const dropdown = document.querySelector('.notif-dropdown');
  const badgeDot = document.querySelector('.notif-badge-dot');

  if (bellBtn && dropdown) {
    bellBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdown.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
      if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
        dropdown.classList.remove('show');
      }
    });

    // Mark as read click
    const markReadBtn = document.querySelector('.mark-all-read-btn');
    if (markReadBtn) {
      markReadBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
          const res = await fetch('api/notifications.php?action=mark_read', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });
          const data = await res.json();
          if (data.success) {
            document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
            if (badgeDot) badgeDot.style.display = 'none';
          }
        } catch (err) {
          console.error('Error marking notifications as read:', err);
        }
      });
    }
  }
}

// Vehicle Tracker Bar on Homepage
function initVehicleTrackerBar() {
  const trackerForm = document.querySelector('.tracker-form');
  if (trackerForm) {
    trackerForm.addEventListener('submit', (e) => {
      const input = trackerForm.querySelector('input[name="query"]');
      if (input && !input.value.trim()) {
        e.preventDefault();
        input.focus();
      }
    });
  }
}

// Retro Modal & Tabs
function initModalTabs() {
  const tabBtns = document.querySelectorAll('.modal-tab-btn');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;
      tabBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      document.querySelectorAll('.modal-tab-pane').forEach(pane => {
        pane.style.display = (pane.id === target) ? 'block' : 'none';
      });
    });
  });

  // Modal open/close helpers
  window.openRetroModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
  };

  window.closeRetroModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
  };

  document.querySelectorAll('.modal-close-trigger').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('.retro-modal-backdrop');
      if (modal) modal.classList.remove('active');
    });
  });
}

// Global Toast Notification Helper (Retro Style)
window.showRetroToast = function(message, type = 'amber') {
  let toastContainer = document.querySelector('.retro-toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.className = 'retro-toast-container';
    toastContainer.style.cssText = 'position:fixed; bottom:24px; right:24px; z-index:9999; display:flex; flex-direction:column; gap:10px;';
    document.body.appendChild(toastContainer);
  }

  const toast = document.createElement('div');
  const bg = type === 'red' ? '#D93829' : (type === 'teal' ? '#2A9D8F' : '#E8A317');
  const textColor = type === 'amber' ? '#000000' : '#ffffff';
  
  toast.style.cssText = `background:${bg}; color:${textColor}; font-family:'IBM Plex Mono', monospace; font-size:0.9rem; font-weight:700; padding:12px 18px; border:2px solid #000; box-shadow:4px 4px 0px #000; display:flex; align-items:center; gap:10px; transform:translateY(20px); opacity:0; transition:all 0.2s ease;`;
  toast.innerHTML = `<span>[WORKSHOP]</span> <span>${message}</span>`;
  
  toastContainer.appendChild(toast);
  setTimeout(() => {
    toast.style.transform = 'translateY(0)';
    toast.style.opacity = '1';
  }, 10);

  setTimeout(() => {
    toast.style.transform = 'translateY(20px)';
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 250);
  }, 3500);
};
