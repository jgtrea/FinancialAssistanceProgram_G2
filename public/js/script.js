/* ============================================================
   GLOBAL UTILITIES
   ============================================================ */

function getCsrfToken() {
  const input = document.querySelector('input[name^="csrf"]');
  return input ? { name: input.name, token: input.value } : { name: 'csrf_token', token: '' };
}

function refreshCsrfToken() {
  // Read the updated token from the CSRF cookie and sync it back into the DOM input
  const cookieMatch = document.cookie.match(/csrf_cookie_name=([^;]+)/);
  if (!cookieMatch) return;
  const newToken = decodeURIComponent(cookieMatch[1]);
  const input = document.querySelector('input[name^="csrf"]');
  if (input) input.value = newToken;
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

  const vouchersTable = document.getElementById('vouchersTable');
  if (!vouchersTable) return;

  // Columns: 0=checkbox 1=StudentID 2=VoucherNo 3=Name 4=School 5=SchoolYear
  //          6=Eligibility 7=Status 8=Date 9=Actions
  const dt = $('#vouchersTable').DataTable({
    destroy: true,
    pageLength: 25,
    order: [[8, 'desc']],
    columnDefs: [{ orderable: false, targets: [0, 9] }],
    language: {
      search: '',
      searchPlaceholder: 'Search vouchers...',
      lengthMenu: 'Show _MENU_ entries',
      info:       'Showing _START_–_END_ of _TOTAL_',
      paginate:   { previous: '&#8249;', next: '&#8250;' },
    },
  });

  // ── Cross-page selection (Set of string IDs) ──────────────────────────────────
  const selectedIds = new Set();

  const checkAll   = document.getElementById('checkAll');
  const actionBar  = document.getElementById('actionBar');
  const countLabel = document.getElementById('selectedCount');

  function updateActionBar() {
    const count         = selectedIds.size;
    const totalFiltered = dt.rows({ search: 'applied' }).count();
    countLabel.textContent  = count;
    actionBar.style.display = count > 0 ? 'flex' : 'none';
    checkAll.checked        = count > 0 && count >= totalFiltered;
    checkAll.indeterminate  = count > 0 && count < totalFiltered;
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

  // Select / deselect ALL filtered rows across every page
  checkAll.addEventListener('change', function () {
    const filteredIds = dt.rows({ search: 'applied' }).ids().toArray()
      .map(function (rid) { return rid.replace('row-', ''); });
    if (this.checked) {
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
  });

  // ── Generate PDF ──────────────────────────────────────────────────────────────
  const btnGeneratePdf = document.getElementById('btnGeneratePdf');
  const pdfForm        = document.getElementById('pdfForm');
  const pdfModal       = document.getElementById('pdfProgressModal');
  const pdfStatusEl    = document.getElementById('pdfStatusText');

  if (btnGeneratePdf && pdfForm) {
    btnGeneratePdf.addEventListener('click', async function () {
      if (!selectedIds.size) return;

      pdfModal.style.display  = 'flex';
      pdfStatusEl.textContent = 'Generating PDF, please wait...';
      btnGeneratePdf.disabled = true;

      const csrf     = getCsrfToken();
      const formData = new FormData();
      formData.append(csrf.name, csrf.token);
      selectedIds.forEach(id => formData.append('voucher_ids[]', id));

      try {
        const res  = await fetch(pdfForm.action, ajaxOptions({ method: 'POST', body: formData }));
        const data = await res.json();

        if (!data.success) {
          pdfModal.style.display = 'none';
          showAlert(data.message || 'Failed to generate PDF.', 'error');
          return;
        }

        // Update status badge for generated rows still visible on current page
        selectedIds.forEach(id => {
          const cb  = document.querySelector(`.vs-row-check[value="${id}"]`);
          const row = cb ? cb.closest('tr') : null;
          if (row) {
            const cell = row.cells[7]; // Status column
            if (cell) cell.innerHTML = '<span class="vs-status-badge vs-status-generated">Generated</span>';
          }
        });

        selectedIds.clear();
        syncPageCheckboxes();
        refreshCsrfToken();

        pdfStatusEl.textContent = 'PDF ready! Downloading...';
        setTimeout(() => {
          pdfModal.style.display = 'none';
          window.location.href   = data.download_url;
        }, 400);
      } catch (err) {
        pdfModal.style.display = 'none';
        showAlert('Failed to generate PDF.', 'error');
        console.error(err);
      } finally {
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

  const archiveUrl = pdfForm ? pdfForm.action.replace('/generate-pdf', '/archive') : '';

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

      archiveBtnText    && (archiveBtnText.style.display    = 'none');
      archiveBtnSpinner && (archiveBtnSpinner.style.display = 'inline-block');
      archiveConfirm.disabled = true;

      const csrf     = getCsrfToken();
      const formData = new FormData();
      formData.append(csrf.name, csrf.token);
      formData.append('archive_reason', archiveReason ? archiveReason.value : '');
      selectedIds.forEach(id => formData.append('voucher_ids[]', id));

      try {
        const res  = await fetch(archiveUrl, ajaxOptions({ method: 'POST', body: formData }));
        const data = await res.json();
        closeArchiveModal();

        if (data.success) {
          showAlert(data.message, 'success');
          // Remove archived rows from DataTable (only those currently in DOM)
          selectedIds.forEach(id => {
            const cb  = document.querySelector(`.vs-row-check[value="${id}"]`);
            const row = cb ? cb.closest('tr') : null;
            if (row) dt.row(row).remove();
          });
          dt.draw();
          selectedIds.clear();
          syncPageCheckboxes();
          refreshCsrfToken();
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

  // ── CSV Export ────────────────────────────────────────────────────────────────
  const btnExport = document.getElementById('btnExport');
  if (btnExport) {
    btnExport.addEventListener('click', function () {
      const rows    = dt.rows({ search: 'applied' }).data();
      const headers = ['Voucher No', 'Name', 'Preferred School', 'School Year', 'Eligibility', 'Status', 'Date'];
      const clean   = str => '"' + String(str).replace(/<[^>]*>/g, '').replace(/"/g, '""').trim() + '"';
      const csvRows = [headers.join(',')];

      rows.each(row => csvRows.push([
        clean(row[2]), clean(row[3]), clean(row[4]),
        clean(row[5]), clean(row[6]), clean(row[7]), clean(row[8]),
      ].join(',')));

      const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
      const url  = URL.createObjectURL(blob);
      const a    = document.createElement('a');
      a.href = url; a.download = 'vouchers_' + new Date().toISOString().slice(0, 10) + '.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      setTimeout(function () { URL.revokeObjectURL(url); }, 100);
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
    pageLength: 25,
    order: [[6, 'desc']],
    columnDefs: [
      { orderable: false, targets: [4] },
      { width: '160px',  targets: [6] },
    ],
    language: { search: '', searchPlaceholder: 'Search logs...' },
  });

});
