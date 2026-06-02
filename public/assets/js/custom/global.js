/* ============================================================
   GLOBAL UTILITIES
   ============================================================ */

function getCsrfToken() {
  const input = document.querySelector('input[name^="csrf"]');
  if (input) return { name: input.name, token: input.value };

  const metaName  = document.querySelector('meta[name="csrf-token-name"]');
  const metaValue = document.querySelector('meta[name="csrf-token-value"]');
  if (metaName && metaValue) {
    return { name: metaName.content, token: metaValue.content };
  }
  return { name: 'csrf_token', token: '' };
}

function refreshCsrfToken() {
  const cookieMatch = document.cookie.match(/csrf_cookie_name=([^;]+)/);
  if (!cookieMatch) return;
  const newToken = decodeURIComponent(cookieMatch[1]);
  const input = document.querySelector('input[name^="csrf"]');
  if (input) input.value = newToken;
}

function showPdfToast(message) {
  let toast = document.getElementById('pdfToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'pdfToast';
    toast.style.cssText = [
      'position:fixed', 'bottom:24px', 'right:24px', 'z-index:9999',
      'background:#1e293b', 'color:#f8fafc', 'border-radius:10px',
      'padding:14px 20px', 'display:flex', 'align-items:center', 'gap:12px',
      'box-shadow:0 4px 20px rgba(0,0,0,.35)', 'font-size:14px',
      'min-width:220px', 'transition:opacity .3s ease',
    ].join(';');
    document.body.appendChild(toast);
  }
  toast.style.opacity = '1';
  toast.innerHTML =
    '<div class="vs-spinner" style="width:16px;height:16px;flex-shrink:0"></div>' +
    '<span id="pdfToastMsg">' + message + '</span>' +
    '<button type="button" id="pdfToastStatusBtn" style="margin-left:auto;background:#334155;color:#f8fafc;border:1px solid #475569;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer">Status</button>';

  const statusBtn = document.getElementById('pdfToastStatusBtn');
  if (statusBtn) {
    statusBtn.addEventListener('click', function () {
      const modal = document.getElementById('pdfStatusModal');
      if (modal) {
        modal.style.display = 'flex';
        if (typeof window.refreshPdfStatusModal === 'function') {
          window.refreshPdfStatusModal();
        }
      }
    });
  }

  return {
    update: function (msg, done) {
      const el = document.getElementById('pdfToastMsg');
      if (el) el.textContent = msg;
      if (done) {
        const spinner = toast.querySelector('.vs-spinner');
        if (spinner) spinner.style.display = 'none';
        setTimeout(function () {
          toast.style.opacity = '0';
          setTimeout(function () { if (toast.parentNode) toast.remove(); }, 300);
        }, 2000);
      }
    },
    remove: function () {
      toast.style.opacity = '0';
      setTimeout(function () { if (toast.parentNode) toast.remove(); }, 300);
    },
  };
}

function showAlert(message, type = 'success') {
  const map = { success: 'vs-alert-success', error: 'vs-alert-error', warning: 'vs-alert-warning' };
  const el  = document.createElement('div');
  el.className = `vs-alert ${map[type] ?? map.success} mb-3`;
  el.textContent = message;

  const main = document.querySelector('.vs-content') || document.querySelector('main');
  if (main) {
    main.prepend(el);
    setTimeout(() => el.remove(), 5000);
  }
}

function ajaxOptions(options = {}) {
  return {
    ...options,
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.headers || {}),
    },
  };
}

function initPasswordToggles() {
  document.querySelectorAll('.vs-pw-toggle').forEach(btn => {
    btn.addEventListener('click', function () {
      const field = document.getElementById(this.dataset.target || 'password');
      if (field) field.type = field.type === 'password' ? 'text' : 'password';
    });
  });
}

function initAlertDismiss() {
  document.querySelectorAll('.vs-alert').forEach(el => {
    setTimeout(() => el.remove(), 4000);
  });
}

/**
 * Initialise Select2 on every `.js-filter-select` and `.js-school-select`.
 * Idempotent — re-runs safely after AJAX-rebuilt modals. Anchors the Select2
 * dropdown inside the nearest .vs-modal so it stacks above the modal overlay.
 */
window.initVsSelect2 = function initVsSelect2(root) {
  if (typeof $ === 'undefined' || !$.fn.select2) return;
  var scope = root || document;
  $(scope).find('.js-filter-select, .js-school-select').each(function () {
    var $el = $(this);
    if ($el.hasClass('select2-hidden-accessible')) return;
    var $modal = $el.closest('.vs-modal-overlay');
    var noSearch = this.dataset.noSearch === '1';
    $el.select2({
      tags: $el.hasClass('js-school-select'),  // school inputs allow new values
      placeholder: this.dataset.placeholder || 'All',
      allowClear: true,
      width: '100%',
      dropdownParent: $modal.length ? $modal : $(document.body),
      minimumResultsForSearch: noSearch ? Infinity : 0,
    });
  });
};

document.addEventListener('DOMContentLoaded', function () {
  if (typeof window.initVsSelect2 === 'function') window.initVsSelect2(document);
});

// Allow Bootstrap dropdowns to overflow .vs-card (overflow:hidden) while open.
document.addEventListener('show.bs.dropdown', function (e) {
  e.target.closest('.vs-card')?.classList.add('vs-card--dropdown-open');
});
document.addEventListener('hidden.bs.dropdown', function (e) {
  e.target.closest('.vs-card')?.classList.remove('vs-card--dropdown-open');
});
