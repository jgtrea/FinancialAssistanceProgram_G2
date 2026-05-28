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
    '<span id="pdfToastMsg">' + message + '</span>';
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

function initGenericDataTables() {
  if (!window.jQuery || !$.fn.DataTable) return;

  const controlsDom =
    "<'row align-items-center mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 text-md-end'l>>" +
    "<'row'<'col-sm-12'tr>>" +
    "<'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>";

  document.querySelectorAll('table.js-data-table').forEach(table => {
    if ($.fn.DataTable.isDataTable(table)) return;

    const actionTargets = Array.from(table.querySelectorAll('thead th'))
      .map((th, index) => {
        const isActions = th.classList.contains('actions-column')
          || th.textContent.trim().toLowerCase() === 'actions';

        return isActions ? index : -1;
      })
      .filter(index => index >= 0);

    $(table).DataTable({
      dom: controlsDom,
      pageLength: 10,
      lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
      responsive: true,
      autoWidth: false,
      order: [],
      columnDefs: actionTargets.length ? [{ orderable: false, targets: actionTargets }] : [],
      language: {
        search: '',
        searchPlaceholder: table.dataset.searchPlaceholder || 'Search...',
        emptyTable: table.dataset.emptyText || 'No records found.',
        lengthMenu: 'Show _MENU_ entries',
        info: 'Showing _START_ to _END_ of _TOTAL_',
      },
    });
  });
}

window.VS = window.VS || {};
window.VS.bindCurrentPageSearch = function bindCurrentPageSearch(dt, input) {
  if (!dt || !input) return;
  if (input.dataset.currentPageSearchBound === '1') return;
  input.dataset.currentPageSearchBound = '1';

  function normalize(value) {
    return (value || '').toString().toLowerCase().trim();
  }

  function applySearch() {
    const query = normalize(input.value);
    dt.rows({ page: 'current' }).every(function () {
      const row = this.node();
      if (!row) return;
      const text = normalize(row.textContent);
      row.style.display = !query || text.indexOf(query) !== -1 ? '' : 'none';
    });
  }

  input.addEventListener('input', applySearch);
  dt.on('draw.dt page.dt order.dt length.dt', applySearch);
  applySearch();
};

// Filters across the full set of rows loaded into the DataTable (not just the
// visible page). Use this when the server has pre-loaded a capped slice (e.g.
// the most recent 1000 vouchers) and the in-table input should let the user
// hunt through that whole slice.
window.VS.bindFullTableSearch = function bindFullTableSearch(dt, input) {
  if (!dt || !input) return;
  if (input.dataset.fullTableSearchBound === '1') return;
  input.dataset.fullTableSearchBound = '1';

  input.addEventListener('input', function () {
    dt.search(input.value).draw();
  });
};


/* ============================================================
   PDF JOB TRACKING — survives page navigation via localStorage
   ============================================================ */

const PDF_JOBS_KEY = 'pendingPdfJobs';

function getPendingPdfJobs() {
  try {
    const raw = localStorage.getItem(PDF_JOBS_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (e) {
    return [];
  }
}

function savePendingPdfJob(job) {
  const list = getPendingPdfJobs().filter(j => j.jobId !== job.jobId);
  list.push(job);
  localStorage.setItem(PDF_JOBS_KEY, JSON.stringify(list));
}

function removePendingPdfJob(jobId) {
  const list = getPendingPdfJobs().filter(j => j.jobId !== jobId);
  if (list.length) localStorage.setItem(PDF_JOBS_KEY, JSON.stringify(list));
  else             localStorage.removeItem(PDF_JOBS_KEY);
}

// Poll an in-flight PDF job. Calls onDone(downloadUrl) when ready,
// updates the toast as it goes, and clears localStorage on terminal states.
function pollPdfJob(jobId, statusUrl, toast, onDone) {
  const POLL_INTERVAL_MS = 3000;
  const MAX_POLLS        = 200; // ~10 min
  let attempts = 0;

  const tick = async function () {
    attempts++;
    try {
      const res  = await fetch(statusUrl, ajaxOptions({ method: 'GET' }));
      const data = await res.json();

      if (data.status === 'done' && data.download_url) {
        removePendingPdfJob(jobId);
        if (toast) toast.update('PDF ready! Downloading...', true);
        if (typeof onDone === 'function') onDone(data.download_url);
        else window.location.href = data.download_url;
        return;
      }

      if (data.status === 'failed') {
        removePendingPdfJob(jobId);
        if (toast) toast.remove();
        showAlert('PDF generation failed: ' + (data.error || 'Unknown error'), 'error');
        return;
      }

      if (data.status === 'forbidden' || data.status === 'not_found') {
        removePendingPdfJob(jobId);
        if (toast) toast.remove();
        showAlert('Unable to access PDF job #' + jobId + '.', 'error');
        return;
      }

      if (attempts >= MAX_POLLS) {
        if (toast) toast.remove();
        showAlert('PDF #' + jobId + ' is still processing. Check back later.', 'warning');
        return;
      }

      const elapsed = Math.round(attempts * POLL_INTERVAL_MS / 1000);
      if (toast) toast.update('Generating PDF #' + jobId + '... (' + elapsed + 's)');
      setTimeout(tick, POLL_INTERVAL_MS);
    } catch (err) {
      console.error('Poll failed:', err);
      if (attempts < MAX_POLLS) {
        setTimeout(tick, POLL_INTERVAL_MS);
      } else {
        if (toast) toast.remove();
        showAlert('Lost connection while polling PDF #' + jobId + '.', 'error');
      }
    }
  };

  setTimeout(tick, POLL_INTERVAL_MS);
}

// On every page load, resume polling for any jobs left pending by another page.
document.addEventListener('DOMContentLoaded', function () {
  const jobs = getPendingPdfJobs();
  jobs.forEach(function (job) {
    const toast = showPdfToast('Generating PDF #' + job.jobId + '...');
    pollPdfJob(job.jobId, job.statusUrl, toast);
  });
});


/* ============================================================
   SIDEBAR
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  const sidebar = document.getElementById('sidebar');
  const toggle  = document.getElementById('sidebarToggle');
  const overlay = document.getElementById('sidebarOverlay');

  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('vs-sidebar-open');
      overlay && overlay.classList.toggle('vs-overlay-open');
    });
  }

  if (overlay) {
    overlay.addEventListener('click', function () {
      sidebar && sidebar.classList.remove('vs-sidebar-open');
      overlay.classList.remove('vs-overlay-open');
    });
  }

  initPasswordToggles();
  initAlertDismiss();
  initGenericDataTables();

});


/* ============================================================
   LOGIN PAGE
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  const pwToggle  = document.getElementById('pwToggle');
  const pwField   = document.getElementById('password');
  const pwShow    = document.getElementById('pwIconShow');
  const pwHide    = document.getElementById('pwIconHide');

  if (pwToggle && pwField) {
    pwToggle.addEventListener('click', function () {
      const isPass = pwField.type === 'password';
      pwField.type = isPass ? 'text' : 'password';
      if (pwShow) pwShow.style.display = isPass ? 'none'   : 'inline';
      if (pwHide) pwHide.style.display = isPass ? 'inline' : 'none';
    });
  }

  const loginForm    = document.getElementById('loginForm');
  const loginBtn     = document.getElementById('loginBtn');
  const loginBtnText = document.getElementById('loginBtnText');
  const loginSpinner = document.getElementById('loginBtnSpinner');

  if (loginForm) {
    loginForm.addEventListener('submit', function () {
      if (loginBtn)     loginBtn.disabled = true;
      if (loginBtnText) loginBtnText.style.display = 'none';
      if (loginSpinner) loginSpinner.style.display  = 'inline-block';
    });
  }

});


/* ============================================================
   USERS PAGE — DataTable, delete modal, toggle status
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  const usersTable = document.getElementById('usersTable');
  if (!usersTable) return;

  $('#usersTable').DataTable({
    pageLength: 25,
    order: [[6, 'desc']],
    columnDefs: [{ orderable: false, targets: [4, 7] }],
    language: { search: '', searchPlaceholder: 'Search users...' },
  });

  // ── Delete modal ─────────────────────────────────────────────────────────────
  const deleteModal   = document.getElementById('deleteModal');
  const deleteClose   = document.getElementById('deleteModalClose');
  const deleteCancel  = document.getElementById('deleteModalCancel');
  const deleteConfirm = document.getElementById('deleteConfirm');
  const deleteNameEl  = document.getElementById('deleteUserName');
  const deleteBtnText = document.getElementById('deleteBtnText');
  const deleteSpinner = document.getElementById('deleteBtnSpinner');

  let deleteTargetId = null;

  document.querySelectorAll('.vs-delete-user').forEach(btn => {
    btn.addEventListener('click', function () {
      deleteTargetId = this.dataset.id;
      if (deleteNameEl) deleteNameEl.textContent = this.dataset.name;
      if (deleteModal)  deleteModal.style.display = 'flex';
    });
  });

  const closeDeleteModal = () => { if (deleteModal) deleteModal.style.display = 'none'; };
  if (deleteClose)  deleteClose.addEventListener('click',  closeDeleteModal);
  if (deleteCancel) deleteCancel.addEventListener('click', closeDeleteModal);
  deleteModal && deleteModal.addEventListener('click', e => { if (e.target === deleteModal) closeDeleteModal(); });

  if (deleteConfirm) {
    deleteConfirm.addEventListener('click', async function () {
      if (!deleteTargetId) return;

      deleteBtnText && (deleteBtnText.style.display = 'none');
      deleteSpinner && (deleteSpinner.style.display  = 'inline-block');
      deleteConfirm.disabled = true;

      const csrf     = getCsrfToken();
      const formData = new FormData();
      formData.append(csrf.name, csrf.token);

      const base = window.location.pathname.split('/users')[0];
      const url  = base + '/users/delete/' + deleteTargetId;

      try {
        const res  = await fetch(url, ajaxOptions({ method: 'POST', body: formData }));
        const data = await res.json();
        closeDeleteModal();

        if (data.success) {
          showAlert(data.message, 'success');
          const dt  = $('#usersTable').DataTable();
          const btn = document.querySelector(`.vs-delete-user[data-id="${deleteTargetId}"]`);
          if (btn) dt.row(btn.closest('tr')).remove().draw();
        } else {
          showAlert(data.message || 'Delete failed.', 'error');
        }
      } catch (err) {
        showAlert('An error occurred.', 'error');
        console.error(err);
      } finally {
        deleteBtnText && (deleteBtnText.style.display = 'inline');
        deleteSpinner && (deleteSpinner.style.display  = 'none');
        deleteConfirm.disabled = false;
      }
    });
  }

  // ── Toggle active status ─────────────────────────────────────────────────────
  document.querySelectorAll('.vs-toggle-status').forEach(btn => {
    btn.addEventListener('click', async function () {
      const id       = this.dataset.id;
      const csrf     = getCsrfToken();
      const formData = new FormData();
      formData.append(csrf.name, csrf.token);

      const base = window.location.pathname.split('/users')[0];
      const url  = base + '/users/toggle-status/' + id;

      this.disabled = true;
      try {
        const res  = await fetch(url, ajaxOptions({ method: 'POST', body: formData }));
        const data = await res.json();

        if (data.success) {
          const active = data.is_active;
          this.textContent = active ? 'Active' : 'Inactive';
          this.className   = 'vs-toggle-status ' + (active ? 'vs-toggle-active' : 'vs-toggle-inactive');
          this.dataset.active = active;
        } else {
          showAlert(data.message || 'Status update failed.', 'error');
        }
      } catch (err) {
        showAlert('An error occurred.', 'error');
      } finally {
        this.disabled = false;
      }
    });
  });

});


/* ============================================================
   VOUCHER PAGE — DataTables, checkbox, Generate PDF, Archive
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  // Handles BOTH the voucher generation page (#vouchersTable) and the
  // students listing page (#studentsTable). Both have the same column shape.
  const vouchersTable = document.getElementById('vouchersTable')
                     || document.getElementById('studentsTable');
  if (!vouchersTable) return;

  // Columns vary slightly between the two pages (index.php has more columns).
  // Checkbox is always first, Actions always last — that's what the targets
  // below rely on.
  const dt = $(vouchersTable).DataTable({
    destroy: true,
    dom:
      "<'row align-items-center mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 text-md-end'l>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
    responsive: true,
    autoWidth: false,
    order: [],
    columnDefs: [{ orderable: false, targets: [0, -1] }],
    language: {
      search: '',
      searchPlaceholder: vouchersTable.dataset.searchPlaceholder || 'Search vouchers...',
      lengthMenu: 'Show _MENU_ entries',
      info:       'Showing _START_ to _END_ of _TOTAL_',
      paginate:   { previous: '&#8249;', next: '&#8250;' },
    },
  });

  // The in-table search filters across ALL rows the server loaded (capped at
  // ~1000). The "advanced search" form above the table hits the full DB and
  // reloads the page with a new slice.
  const currentPageSearch = document.getElementById('customStudentsSearch')
                         || document.getElementById('customVouchersSearch');
  if (currentPageSearch && window.VS && window.VS.bindFullTableSearch) {
    const dtWrap = vouchersTable.closest('.dataTables_wrapper');
    const dtSearch = dtWrap ? dtWrap.querySelector('.dataTables_filter') : null;
    if (dtSearch) dtSearch.style.display = 'none';
    window.VS.bindFullTableSearch(dt, currentPageSearch);
  }

  // ── Cross-page selection (Set of string IDs) ──────────────────────────────────
  const selectedIds = new Set();

  const actionBar  = document.getElementById('actionBar');
  const countLabel = document.getElementById('selectedCount');
  const btnOpenExport = document.getElementById('btnOpenExport');
  const exportModal = document.getElementById('exportModal');
  const exportModalClose = document.getElementById('exportModalClose');

  function getCheckAllBoxes() {
    return document.querySelectorAll('.vs-check-all');
  }

  function updateExportLinks() {
    const ids = Array.from(selectedIds).join(',');
    document.querySelectorAll('[data-export-format]').forEach(function (link) {
      const format = link.dataset.exportFormat || 'xlsx';
      if (!link.dataset.exportBase) {
        link.dataset.exportBase = link.href.split('?')[0];
      }
      link.href = link.dataset.exportBase + '?format=' + encodeURIComponent(format)
        + '&ids=' + encodeURIComponent(ids);
    });
  }

  function updateActionBar() {
    const count         = selectedIds.size;
    const totalFiltered = dt.rows({ search: 'applied' }).count();
    if (countLabel) countLabel.textContent = count;
    if (actionBar) actionBar.style.display = count > 0 ? 'flex' : 'none';
    updateExportLinks();
    getCheckAllBoxes().forEach(checkAll => {
      checkAll.checked = totalFiltered > 0 && count >= totalFiltered;
      checkAll.indeterminate = count > 0 && count < totalFiltered;
    });
  }

  // Sync visible checkboxes to reflect the Set after any DataTable redraw
  function syncPageCheckboxes() {
    dt.rows({ page: 'current' }).nodes().each(function (row) {
      const cb = row.querySelector('.vs-row-check');
      if (cb) {
        cb.checked = selectedIds.has(cb.value);
        row.classList.toggle('vs-row-selected', cb.checked);
      }
    });
    updateActionBar();
  }

  dt.on('page.dt search.dt order.dt', syncPageCheckboxes);

  // Select / deselect ALL filtered rows across every page. Rows marked as
  // not-eligible carry a disabled checkbox + data-eligibility="not_eligible"
  // and are excluded from bulk select.
  document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('vs-check-all')) return;

    const filteredIds = [];
    dt.rows({ search: 'applied' }).every(function () {
      const node = this.node();
      if (!node) return;
      if (node.getAttribute('data-eligibility') === 'not_eligible') return;
      const cb = node.querySelector('.vs-row-check');
      if (cb && !cb.disabled) filteredIds.push(cb.value);
    });
    const shouldCheck = selectedIds.size === 0;
    if (shouldCheck) {
      filteredIds.forEach(function (id) { selectedIds.add(id); });
    } else {
      filteredIds.forEach(function (id) { selectedIds.delete(id); });
    }
    getCheckAllBoxes().forEach(checkAll => {
      checkAll.indeterminate = false;
      checkAll.checked = shouldCheck;
    });
    syncPageCheckboxes();
  });

  // Individual row toggle
  vouchersTable.addEventListener('change', function (e) {
    if (!e.target.classList.contains('vs-row-check')) return;
    if (e.target.checked) selectedIds.add(e.target.value);
    else                   selectedIds.delete(e.target.value);
    e.target.closest('tr').classList.toggle('vs-row-selected', e.target.checked);
    updateActionBar();
  });

  // ── Generate PDF ──────────────────────────────────────────────────────────────
  if (btnOpenExport && exportModal) {
    btnOpenExport.addEventListener('click', function () {
      if (!selectedIds.size) return;
      updateExportLinks();
      exportModal.style.display = 'flex';
    });
  }
  exportModalClose && exportModalClose.addEventListener('click', function () {
    exportModal.style.display = 'none';
  });
  exportModal && exportModal.addEventListener('click', function (e) {
    if (e.target === exportModal) exportModal.style.display = 'none';
  });

  const btnGeneratePdf = document.getElementById('btnGeneratePdf');
  const pdfForm        = document.getElementById('pdfForm');
  const pdfModal       = document.getElementById('pdfProgressModal');
  const pdfStatusEl    = document.getElementById('pdfStatusText');

  const MAX_BATCH = 50000;

  if (btnGeneratePdf && pdfForm) {
    btnGeneratePdf.addEventListener('click', async function () {
      if (!selectedIds.size) return;

      if (selectedIds.size > MAX_BATCH) {
        showAlert('You can only generate PDFs for up to ' + MAX_BATCH + ' students at a time. You have ' + selectedIds.size + ' selected.', 'warning');
        return;
      }

      btnGeneratePdf.disabled = true;
      const toast = showPdfToast('Generating PDF...');

      const csrf     = getCsrfToken();
      const formData = new FormData();
      formData.append(csrf.name, csrf.token);
      formData.append('voucher_ids', Array.from(selectedIds).join(','));

      try {
        const res  = await fetch(pdfForm.action, ajaxOptions({ method: 'POST', body: formData }));
        const data = await res.json();

        refreshCsrfToken();
        btnGeneratePdf.disabled = false;

        if (!data.success) {
          toast.remove();
          showAlert(data.message || 'PDF generation failed.', 'error');
          return;
        }

        // Mark rows as Generated regardless of sync/queued path
        const idsForUpdate = Array.from(selectedIds);
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const now = new Date();
        const todayFormatted = months[now.getMonth()] + ' ' + String(now.getDate()).padStart(2, '0') + ', ' + now.getFullYear();
        idsForUpdate.forEach(function (id) {
          const cb  = document.querySelector('.vs-row-check[value="' + id + '"]');
          const row = cb ? cb.closest('tr') : null;
          if (row) {
            const voucherCell = row.querySelector('.js-voucher-no');
            if (voucherCell && data.vouchers && data.vouchers[id]) {
              voucherCell.textContent = data.vouchers[id];
            }

            const countCell = row.querySelector('.js-generate-count');
            if (countCell) {
              const count = parseInt(countCell.textContent, 10) || 0;
              countCell.textContent = count + 1;
            }

            const lastGeneratedCell = row.querySelector('.js-last-generated');
            if (lastGeneratedCell) {
              lastGeneratedCell.textContent = todayFormatted;
            }
          }
        });

        selectedIds.clear();
        syncPageCheckboxes();

        // All PDF jobs queue + poll — the indicator survives page navigation
        if (data.queued && data.status_url) {
          savePendingPdfJob({
            jobId:     data.job_id,
            statusUrl: data.status_url,
            startedAt: Date.now(),
          });
          toast.update('Generating PDF (job #' + data.job_id + ')...');
          pollPdfJob(data.job_id, data.status_url, toast);
          return;
        }

        // Defensive fallback if backend ever returns a direct download_url
        if (data.download_url) {
          toast.update('PDF ready! Downloading...', true);
          window.location.href = data.download_url;
        } else {
          toast.remove();
          showAlert('PDF generation response was malformed.', 'error');
        }

      } catch (err) {
        toast.remove();
        showAlert('Failed to generate PDF.', 'error');
        console.error(err);
        btnGeneratePdf.disabled = false;
      }
    });
  }

  // ── Archive modal ─────────────────────────────────────────────────────────────
  const btnArchive        = document.getElementById('btnArchive');
  const archiveModal      = document.getElementById('archiveModal');
  const archiveModalClose = document.getElementById('archiveModalClose');
  const archiveModalCancel= document.getElementById('archiveModalCancel');
  const archiveConfirm    = document.getElementById('archiveConfirm');
  const archiveCount      = document.getElementById('archiveCount');
  const archiveReason     = document.getElementById('archiveReason');
  const archiveBtnText    = document.getElementById('archiveBtnText');
  const archiveBtnSpinner = document.getElementById('archiveBtnSpinner');

  const archiveForm = document.getElementById('archiveForm');
  const archiveUrl  = archiveForm
    ? archiveForm.action
    : (pdfForm ? pdfForm.action.replace('/generate-pdf', '/archive') : '');

  const closeArchiveModal = () => { if (archiveModal) archiveModal.style.display = 'none'; };

  btnArchive        && btnArchive.addEventListener('click', () => {
    if (archiveCount)  archiveCount.textContent = selectedIds.size;
    if (archiveReason) archiveReason.value = '';
    if (archiveModal)  archiveModal.style.display = 'flex';
  });
  archiveModalClose  && archiveModalClose.addEventListener('click',  closeArchiveModal);
  archiveModalCancel && archiveModalCancel.addEventListener('click', closeArchiveModal);
  archiveModal       && archiveModal.addEventListener('click', e => { if (e.target === archiveModal) closeArchiveModal(); });

  if (archiveConfirm) {
    archiveConfirm.addEventListener('click', async function () {
      if (!selectedIds.size) return;

      if (selectedIds.size > MAX_BATCH) {
        showAlert('You can only archive up to ' + MAX_BATCH + ' students at a time. You have ' + selectedIds.size + ' selected.', 'warning');
        return;
      }

      archiveBtnText    && (archiveBtnText.style.display    = 'none');
      archiveBtnSpinner && (archiveBtnSpinner.style.display = 'inline-block');
      archiveConfirm.disabled = true;

      const csrf     = getCsrfToken();
      const formData = new FormData();
      formData.append(csrf.name, csrf.token);
      formData.append('archive_reason', archiveReason ? archiveReason.value : '');
      formData.append('voucher_ids', Array.from(selectedIds).join(','));

      try {
        const res  = await fetch(archiveUrl, ajaxOptions({ method: 'POST', body: formData }));
        const data = await res.json();
        closeArchiveModal();

        if (data.success) {
          // Soft archive: rows stay in the DataTable but flip to archived
          // state (disabled checkbox + Unarchive-only actions). The cleanest
          // way to redraw that is a full reload — the per-row inline JS does
          // surgical DOM updates, but the bulk flow handles many rows at
          // once and reloading also resets selection state.
          window.location.reload();
        } else {
          showAlert(data.message || 'Archive failed.', 'error');
        }
      } catch (err) {
        showAlert('An error occurred. Please try again.', 'error');
        console.error(err);
      } finally {
        archiveBtnText    && (archiveBtnText.style.display    = 'inline');
        archiveBtnSpinner && (archiveBtnSpinner.style.display = 'none');
        archiveConfirm.disabled = false;
      }
    });
  }

});



/* ============================================================
   AUDIT LOGS PAGE — DataTable init
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  const logsTable = document.getElementById('logsTable');
  if (!logsTable) return;

  $('#logsTable').DataTable({
    dom:
      "<'row align-items-center mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 text-md-end'l>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
    order: [[6, 'desc']],
    columnDefs: [
      { orderable: false, targets: [4] },
      { width: '160px',  targets: [6] },
    ],
    language: {
      search: '',
      searchPlaceholder: 'Search logs...',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_ to _END_ of _TOTAL_',
    },
  });

});
