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

  // Wire Status button to open the existing status modal (lives in vouchers/index + generate views).
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

function initGenericDataTables() {
  if (!window.jQuery || !$.fn.DataTable) return;

  const controlsDom =
    "<'row align-items-center mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 text-md-end'l>>" +
    "<'row'<'col-sm-12'tr>>" +
    "<'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>";

  document.querySelectorAll('table.js-data-table').forEach(table => {
    if ($.fn.DataTable.isDataTable(table)) return;

    const nonOrderableTargets = Array.from(table.querySelectorAll('thead th'))
      .map((th, index) => {
        const isActions = th.classList.contains('actions-column')
          || th.textContent.trim().toLowerCase() === 'actions';
        const isCheckCol = th.classList.contains('vs-th-check');
        return (isActions || isCheckCol) ? index : -1;
      })
      .filter(index => index >= 0);

    $(table).DataTable({
      dom: controlsDom,
      pageLength: 10,
      lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
      responsive: true,
      autoWidth: false,
      order: [],
      columnDefs: nonOrderableTargets.length ? [{ orderable: false, targets: nonOrderableTargets }] : [],
      language: {
        search: '',
        searchPlaceholder: table.dataset.searchPlaceholder || 'Search...',
        emptyTable: table.dataset.emptyText || 'No records found.',
        lengthMenu: 'Show _MENU_ entries',
        info: 'Showing _START_ to _END_ of _TOTAL_',
      },
      drawCallback: function () {
        var tbody = $(this.api().table().body());
        tbody.find('tr.vs-row-archived, tr[data-active="0"]').appendTo(tbody);
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
// Latest JSON-queue PDF job so the Status button can show it even after completion.
const LAST_JSON_PDF_JOB_KEY = 'lastJsonPdfJob';

function saveLastJsonPdfJob(job) {
  try { localStorage.setItem(LAST_JSON_PDF_JOB_KEY, JSON.stringify(job)); } catch (e) {}
}
function getLastJsonPdfJob() {
  try {
    const raw = localStorage.getItem(LAST_JSON_PDF_JOB_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch (e) { return null; }
}

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

  // Server-side mode kicks in when the table has data-datatable-url. Used by
  // /admin/students to handle 30k+ rows without DOM blowup. Otherwise we fall
  // back to the original client-side init that renders all PHP-rendered rows.
  const datatableUrl = vouchersTable.dataset.datatableUrl || null;
  let dt;

  if (datatableUrl) {
    // Filter values were rendered into a data-attr by the view so they survive
    // page refreshes and stay in sync with the advanced-filter form.
    let filterParams = {};
    try { filterParams = JSON.parse(vouchersTable.dataset.filterParams || '{}'); } catch (e) {}

    dt = $(vouchersTable).DataTable({
      destroy: true,
      serverSide: true,
      processing: true,
      ajax: {
        url: datatableUrl,
        type: 'GET',
        data: function (d) {
          // Pass the advanced filter params through every page fetch so the
          // server applies them consistently.
          Object.assign(d, filterParams);
        },
      },
      // Column order must match the <th>s in vouchers/index.php exactly,
      // including the hidden name_sort column (3) used so "Name" can sort
      // by last name instead of by the rendered full-name string.
      columns: [
        { data: 'checkbox',       orderable: false },
        { data: 'voucher_no' },
        { data: 'name' },
        { data: 'name_sort',      visible: false },
        { data: 'jhs' },
        { data: 'shs' },
        { data: 'school_year' },
        { data: 'eligibility' },
        { data: 'status' },
        { data: 'remarks' },
        { data: 'generate_count' },
        { data: 'last_generated' },
        { data: 'actions',        orderable: false },
      ],
      columnDefs: [
        { orderData: [3], targets: [2] },
      ],
      dom:
        "<'row align-items-center mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 text-md-end'l>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
      pageLength: 25,
      lengthMenu: [[10, 25, 50, 100, 250], [10, 25, 50, 100, 250]],
      responsive: true,
      autoWidth: false,
      order: [],
      language: {
        search: '',
        searchPlaceholder: vouchersTable.dataset.searchPlaceholder || 'Search students...',
        lengthMenu: 'Show _MENU_ entries',
        info:       'Showing _START_ to _END_ of _TOTAL_ matching',
        paginate:   { previous: '&#8249;', next: '&#8250;' },
        processing: 'Loading...',
      },
    });

    // In server-side mode the built-in DataTables search box already hits the
    // server, so wire the page's custom search box to it (debounced).
    const currentPageSearch = document.getElementById('customStudentsSearch')
                           || document.getElementById('customVouchersSearch');
    if (currentPageSearch) {
      const dtWrap = vouchersTable.closest('.dataTables_wrapper');
      const dtSearch = dtWrap ? dtWrap.querySelector('.dataTables_filter') : null;
      if (dtSearch) dtSearch.style.display = 'none';

      let searchTimer;
      currentPageSearch.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const val = currentPageSearch.value;
        searchTimer = setTimeout(function () {
          dt.search(val).draw();
        }, 250);
      });
    }
  } else {
    // Client-side mode (unchanged) for pages that still server-render rows.
    dt = $(vouchersTable).DataTable({
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
      drawCallback: function () {
        var tbody = $(vouchersTable).find('tbody');
        tbody.find('tr[data-active="0"]').appendTo(tbody);
      },
    });

    const currentPageSearch = document.getElementById('customStudentsSearch')
                           || document.getElementById('customVouchersSearch');
    if (currentPageSearch && window.VS && window.VS.bindFullTableSearch) {
      const dtWrap = vouchersTable.closest('.dataTables_wrapper');
      const dtSearch = dtWrap ? dtWrap.querySelector('.dataTables_filter') : null;
      if (dtSearch) dtSearch.style.display = 'none';
      window.VS.bindFullTableSearch(dt, currentPageSearch);
    }
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
      checkAll.checked = false;
      checkAll.indeterminate = count > 0;
    });
    // Server-side mode cross-page banner — defined further below; check for it
    // so client-side pages (where the function is undefined) don't error.
    if (typeof updateSelectAllBanner === 'function') {
      updateSelectAllBanner();
    }
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
    syncPageCheckboxes();
  });

  // Individual row toggle
  vouchersTable.addEventListener('change', function (e) {
    if (!e.target.classList.contains('vs-row-check')) return;
    if (e.target.checked) selectedIds.add(e.target.value);
    else                   selectedIds.delete(e.target.value);
    e.target.closest('tr').classList.toggle('vs-row-selected', e.target.checked);
    updateActionBar();
    updateSelectAllBanner();
  });

  // ── Cross-page Select All (server-side mode) ──────────────────────────────
  // In server-side mode the header check-all only flips the 25 visible rows.
  // When that happens, the banner offers to extend the selection to every
  // matching row across all pages via the matching-ids endpoint.
  const matchingIdsUrl       = vouchersTable.dataset.matchingIdsUrl || null;
  const selectAllBanner      = document.getElementById('selectAllBanner');
  const selectAllBannerText  = document.getElementById('selectAllBannerText');
  const selectAllMatchingLink= document.getElementById('selectAllMatchingLink');
  const selectAllClearLink   = document.getElementById('selectAllClearLink');

  function getFilterQueryString() {
    let filterParams = {};
    try { filterParams = JSON.parse(vouchersTable.dataset.filterParams || '{}'); } catch (e) {}
    const search = dt && dt.search ? dt.search() : '';
    const usp    = new URLSearchParams();
    Object.entries(filterParams).forEach(([k, v]) => { if (v !== '' && v != null) usp.append(k, v); });
    if (search) usp.append('q', search);
    return usp.toString();
  }

  function updateSelectAllBanner() {
    if (!selectAllBanner || !matchingIdsUrl) return;

    const info       = dt && dt.page ? dt.page.info() : null;
    const totalMatch = info ? info.recordsDisplay : 0;
    const selSize    = selectedIds.size;

    if (selSize === 0 || totalMatch === 0) {
      selectAllBanner.style.display = 'none';
      return;
    }

    // selSize covers (or exceeds) every matching row → show Clear-only state.
    if (selSize >= totalMatch) {
      selectAllBanner.style.display       = 'block';
      selectAllBannerText.textContent     = 'All ' + totalMatch + ' matching row(s) selected.';
      selectAllMatchingLink.style.display = 'none';
      selectAllClearLink.style.display    = 'inline';
      return;
    }

    // Some selected, more available → show "Select all" prompt.
    selectAllBanner.style.display       = 'block';
    selectAllBannerText.textContent     = selSize + ' selected. ' + totalMatch + ' total matching.';
    selectAllMatchingLink.textContent   = 'Select all ' + totalMatch + ' matching across all pages';
    selectAllMatchingLink.style.display = 'inline';
    selectAllClearLink.style.display    = 'inline';
  }

  if (selectAllMatchingLink) {
    selectAllMatchingLink.addEventListener('click', async function (e) {
      e.preventDefault();
      if (!matchingIdsUrl) return;
      const qs  = getFilterQueryString();
      const url = matchingIdsUrl + (qs ? ('?' + qs) : '');
      selectAllMatchingLink.textContent = 'Loading...';
      try {
        const res  = await fetch(url, ajaxOptions({ method: 'GET' }));
        const data = await res.json();
        (data.ids || []).forEach(id => selectedIds.add(String(id)));
        syncPageCheckboxes();
        updateSelectAllBanner();
      } catch (err) {
        console.error(err);
        showAlert('Failed to fetch all matching IDs.', 'error');
        selectAllMatchingLink.textContent = 'Select all matching';
      }
    });
  }
  if (selectAllClearLink) {
    selectAllClearLink.addEventListener('click', function (e) {
      e.preventDefault();
      selectedIds.clear();
      syncPageCheckboxes();
      updateSelectAllBanner();
    });
  }

  // Recompute banner state whenever DataTables redraws.
  if (dt) {
    dt.on('draw.dt', updateSelectAllBanner);
  }

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
          // Remember the most-recent JSON-queue job so the Status modal can
          // show it on demand even after the polling toast goes away.
          saveLastJsonPdfJob({
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

  let pendingArchiveSingleId = null;

  const closeArchiveModal = () => {
    if (archiveModal) archiveModal.style.display = 'none';
    pendingArchiveSingleId = null;
  };

  function openArchiveModal(ids) {
    if (archiveCount)  archiveCount.textContent = ids.length;
    if (archiveReason) archiveReason.value = '';
    if (archiveModal)  archiveModal.style.display = 'flex';
  }

  btnArchive        && btnArchive.addEventListener('click', () => {
    if (!selectedIds.size) return;
    pendingArchiveSingleId = null;
    openArchiveModal(Array.from(selectedIds));
  });

  // Per-row archive button
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-archive-single');
    if (!btn) return;
    pendingArchiveSingleId = btn.getAttribute('data-id');
    openArchiveModal([pendingArchiveSingleId]);
  });

  archiveModalClose  && archiveModalClose.addEventListener('click',  closeArchiveModal);
  archiveModalCancel && archiveModalCancel.addEventListener('click', closeArchiveModal);
  archiveModal       && archiveModal.addEventListener('click', e => { if (e.target === archiveModal) closeArchiveModal(); });

  if (archiveConfirm) {
    archiveConfirm.addEventListener('click', async function () {
      const ids = pendingArchiveSingleId
        ? [pendingArchiveSingleId]
        : Array.from(selectedIds);
      if (!ids.length) return;

      if (ids.length > MAX_BATCH) {
        showAlert('You can only archive up to ' + MAX_BATCH + ' students at a time. You have ' + ids.length + ' selected.', 'warning');
        return;
      }

      archiveBtnText    && (archiveBtnText.style.display    = 'none');
      archiveBtnSpinner && (archiveBtnSpinner.style.display = 'inline-block');
      archiveConfirm.disabled = true;

      const csrf     = getCsrfToken();
      const formData = new FormData();
      formData.append(csrf.name, csrf.token);
      formData.append('archive_reason', archiveReason ? archiveReason.value : '');
      formData.append('voucher_ids', ids.join(','));

      try {
        const res  = await fetch(archiveUrl, ajaxOptions({ method: 'POST', body: formData }));
        const data = await res.json();
        closeArchiveModal();

        if (data.success) {
          ids.forEach(function (id) {
            var row = document.getElementById('row-' + id);
            if (row) dt.row(row).remove();
            selectedIds.delete(id);
          });
          dt.draw(false);
          syncPageCheckboxes();
          showAlert(data.message || 'Archived successfully.', 'success');
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


/* ============================================================
   GLOBAL PDF STATUS MODAL — wired on every page so the toast
   Status button works after the user navigates away from the
   voucher listing. Modal markup lives in layouts/main.php.
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
  const btnOpenStatus        = document.getElementById('btnOpenStatus');
  const pdfStatusModal       = document.getElementById('pdfStatusModal');
  if (!pdfStatusModal) return;

  const pdfStatusModalClose  = document.getElementById('pdfStatusModalClose');
  const pdfStatusEmpty       = document.getElementById('pdfStatusEmpty');
  const pdfStatusContent     = document.getElementById('pdfStatusContent');
  const pdfStatusJobIdEl     = document.getElementById('pdfStatusJobId');
  const pdfStatusBadge       = document.getElementById('pdfStatusBadge');
  const pdfStatusProgress    = document.getElementById('pdfStatusProgress');
  const pdfStatusErrorLine   = document.getElementById('pdfStatusErrorLine');
  const pdfStatusError       = document.getElementById('pdfStatusError');
  const pdfStatusDownloadWrap= document.getElementById('pdfStatusDownloadWrap');
  const pdfStatusDownload    = document.getElementById('pdfStatusDownload');

  async function refreshPdfStatusModal() {
    const last = getLastJsonPdfJob();
    if (!last || !last.jobId || !last.statusUrl) {
      if (pdfStatusEmpty)   pdfStatusEmpty.style.display = 'block';
      if (pdfStatusContent) pdfStatusContent.style.display = 'none';
      return;
    }

    if (pdfStatusEmpty)   pdfStatusEmpty.style.display = 'none';
    if (pdfStatusContent) pdfStatusContent.style.display = 'block';
    if (pdfStatusJobIdEl) pdfStatusJobIdEl.textContent = '#' + last.jobId;

    try {
      const res  = await fetch(last.statusUrl, ajaxOptions({ method: 'GET' }));
      const data = await res.json();

      const status = data.status || 'unknown';
      if (pdfStatusBadge) {
        pdfStatusBadge.textContent = status;
        pdfStatusBadge.className   = 'vs-badge vs-badge-' + status;
      }

      const p = data.progress || { done: 0, total: 0 };
      if (pdfStatusProgress) {
        pdfStatusProgress.textContent = (p.done || 0) + ' / ' + (p.total || 0)
          + (p.processing ? ' (processing ' + p.processing + ')' : '')
          + (p.queued     ? ' (queued ' + p.queued + ')' : '');
      }

      if (data.error) {
        if (pdfStatusErrorLine) pdfStatusErrorLine.style.display = 'block';
        if (pdfStatusError)     pdfStatusError.textContent = data.error;
      } else {
        if (pdfStatusErrorLine) pdfStatusErrorLine.style.display = 'none';
      }

      if (status === 'done' && data.download_url) {
        if (pdfStatusDownloadWrap) pdfStatusDownloadWrap.style.display = 'block';
        if (pdfStatusDownload)     pdfStatusDownload.href = data.download_url;
      } else {
        if (pdfStatusDownloadWrap) pdfStatusDownloadWrap.style.display = 'none';
      }
    } catch (err) {
      console.error('Status fetch failed:', err);
      if (pdfStatusBadge) {
        pdfStatusBadge.textContent = 'error';
        pdfStatusBadge.className   = 'vs-badge vs-badge-failed';
      }
    }
  }

  // Auto-poll while modal is open. Stops on close or terminal state.
  let pdfStatusPollTimer = null;
  const PDF_STATUS_POLL_MS = 3000;

  function stopPdfStatusPoll() {
    if (pdfStatusPollTimer) {
      clearInterval(pdfStatusPollTimer);
      pdfStatusPollTimer = null;
    }
  }

  async function pollPdfStatusOnce() {
    await refreshPdfStatusModal();
    if (pdfStatusBadge) {
      const txt = (pdfStatusBadge.textContent || '').toLowerCase();
      if (txt === 'done' || txt === 'failed' || txt === 'not_found' || txt === 'forbidden') {
        stopPdfStatusPoll();
      }
    }
  }

  function startPdfStatusPoll() {
    stopPdfStatusPoll();
    pollPdfStatusOnce();
    pdfStatusPollTimer = setInterval(pollPdfStatusOnce, PDF_STATUS_POLL_MS);
  }

  function closePdfStatusModal() {
    pdfStatusModal.style.display = 'none';
    stopPdfStatusPoll();
  }

  if (btnOpenStatus) {
    btnOpenStatus.addEventListener('click', function () {
      pdfStatusModal.style.display = 'flex';
      startPdfStatusPoll();
    });
  }
  if (pdfStatusModalClose) {
    pdfStatusModalClose.addEventListener('click', closePdfStatusModal);
  }
  pdfStatusModal.addEventListener('click', function (e) {
    if (e.target === pdfStatusModal) closePdfStatusModal();
  });

  // Toast Status button (showPdfToast → window.refreshPdfStatusModal()) needs
  // a function that ALSO starts the polling loop when the modal is opened.
  window.refreshPdfStatusModal = function () {
    if (pdfStatusModal.style.display !== 'none' && !pdfStatusPollTimer) {
      startPdfStatusPoll();
    } else {
      refreshPdfStatusModal();
    }
  };
});
