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

// Each concurrent PDF job gets its OWN toast node, stacked in a shared
// container. Previously every job reused a single #pdfToast element, so the
// first job to finish called remove() and deleted the shared node out from
// under any still-running job (e.g. a small job downloading wiped the toast of
// a 50k batch still generating). `key` scopes the node; pass a stable unique
// value per job. Calling again with the same key reuses that job's node.
//
// `job` ({ jobId, statusUrl }) scopes this toast's Status button to its own
// job, so clicking Status on one toast opens the modal for THAT job rather than
// whichever job happens to be newest. May be set later via the returned
// setJob() once the job_id is known (the generate request returns it async).
function showPdfToast(message, key, job) {
  // How long a finished toast lingers before auto-removing, so the user can
  // still hit the manual Download link if the automatic download didn't fire.
  const FINISHED_LINGER_MS = 5 * 60 * 1000;
  let currentJob = job || null;

  let stack = document.getElementById('pdfToastStack');
  if (!stack) {
    stack = document.createElement('div');
    stack.id = 'pdfToastStack';
    stack.style.cssText = [
      'position:fixed', 'bottom:24px', 'right:24px', 'z-index:9999',
      'display:flex', 'flex-direction:column-reverse', 'gap:10px',
    ].join(';');
    document.body.appendChild(stack);
  }

  const nodeId = 'pdfToast-' + (key != null ? key : ('t' + Date.now() + Math.random().toString(36).slice(2)));
  let toast = document.getElementById(nodeId);
  if (!toast) {
    toast = document.createElement('div');
    toast.id = nodeId;
    toast.style.cssText = [
      'background:#1e293b', 'color:#f8fafc', 'border-radius:10px',
      'padding:14px 20px', 'display:flex', 'align-items:center', 'gap:12px',
      'box-shadow:0 4px 20px rgba(0,0,0,.35)', 'font-size:14px',
      'min-width:220px', 'transition:opacity .3s ease',
    ].join(';');
    stack.appendChild(toast);
  }
  toast.style.opacity = '1';
  toast.innerHTML =
    '<div class="vs-spinner" style="width:16px;height:16px;flex-shrink:0"></div>' +
    '<span class="pdfToastMsg">' + message + '</span>' +
    '<a class="pdfToastDownload" style="display:none;margin-left:auto;background:#16a34a;color:#fff;border-radius:6px;padding:4px 10px;font-size:12px;text-decoration:none;cursor:pointer">Download</a>' +
    '<button type="button" class="pdfToastStatusBtn" style="margin-left:auto;background:#334155;color:#f8fafc;border:1px solid #475569;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer">Status</button>' +
    '<button type="button" class="pdfToastClose" style="background:none;color:#94a3b8;border:none;font-size:16px;line-height:1;cursor:pointer;padding:0 2px">&times;</button>';

  // The Download anchor sits left of the Status button; once a download link is
  // set, push Status to the right with the auto-margin so layout stays stable.
  const statusBtn = toast.querySelector('.pdfToastStatusBtn');
  if (statusBtn) {
    statusBtn.addEventListener('click', function () {
      const modal = document.getElementById('pdfStatusModal');
      if (!modal) return;
      modal.style.display = 'flex';
      // Scope the modal to THIS toast's job when known; else fall back to the
      // modal's default newest-pending pick.
      if (currentJob && typeof window.openPdfStatusFor === 'function') {
        window.openPdfStatusFor(currentJob);
      } else if (typeof window.refreshPdfStatusModal === 'function') {
        window.refreshPdfStatusModal();
      }
    });
  }

  function removeToast() {
    toast.style.opacity = '0';
    setTimeout(function () {
      if (toast.parentNode) toast.remove();
      // Drop the empty stack so it doesn't linger as a stray fixed element.
      if (stack.parentNode && stack.children.length === 0) stack.remove();
    }, 300);
  }

  const closeBtn = toast.querySelector('.pdfToastClose');
  if (closeBtn) closeBtn.addEventListener('click', removeToast);

  return {
    // update(msg)                 → change the text only.
    // update(msg, true)           → mark done, fade out after 2s (no download).
    // update(msg, true, url)      → mark done, reveal a manual Download link,
    //                               and linger ~5 min before auto-removing.
    update: function (msg, done, downloadUrl) {
      const el = toast.querySelector('.pdfToastMsg');
      if (el) el.textContent = msg;
      if (!done) return;

      const spinner = toast.querySelector('.vs-spinner');
      if (spinner) spinner.style.display = 'none';

      if (downloadUrl) {
        const dl = toast.querySelector('.pdfToastDownload');
        if (dl) {
          dl.href = downloadUrl;
          dl.style.display = 'inline-block';
        }
        setTimeout(removeToast, FINISHED_LINGER_MS);
      } else {
        setTimeout(removeToast, 2000);
      }
    },
    setJob: function (j) { currentJob = j || currentJob; },
    remove: removeToast,
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
