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
      order: [[3, 'asc']],
      dom:
        "<'row align-items-center mb-3'<'col-sm-12 text-end'l>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
      pageLength: 25,
      lengthMenu: [[10, 25, 50, 100, 250], [10, 25, 50, 100, 250]],
      responsive: true,
      autoWidth: false,
      language: {
        search: '',
        searchPlaceholder: vouchersTable.dataset.searchPlaceholder || 'Search students...',
        lengthMenu: 'Show _MENU_ entries',
        info:       'Showing _START_ to _END_ of _TOTAL_ matching',
        paginate:   { previous: '&#8249;', next: '&#8250;' },
        processing: 'Loading...',
      },
    });

    const currentPageSearch = document.getElementById('customStudentsSearch')
                           || document.getElementById('customVouchersSearch');
    if (currentPageSearch && window.VS && window.VS.bindCurrentPageSearch) {
      window.VS.bindCurrentPageSearch(dt, currentPageSearch);
    }

    const advInput = document.querySelector('.vs-advanced-search-input');
    if (advInput && window.VS && window.VS.bindFullTableSearch) {
      window.VS.bindFullTableSearch(dt, advInput);
    }
  } else {
    // Client-side mode for pages that still server-render rows.
    dt = $(vouchersTable).DataTable({
      destroy: true,
      dom:
        "<'row align-items-center mb-3'<'col-sm-12 text-end'l>>" +
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

    const currentPageSearch = document.getElementById('customStudentsSearch')
                           || document.getElementById('customVouchersSearch');
    if (currentPageSearch && window.VS && window.VS.bindCurrentPageSearch) {
      window.VS.bindCurrentPageSearch(dt, currentPageSearch);
    }

    const advInput = document.querySelector('.vs-advanced-search-input');
    if (advInput && window.VS && window.VS.bindFullTableSearch) {
      window.VS.bindFullTableSearch(dt, advInput);
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
    if (typeof updateSelectAllBanner === 'function') {
      updateSelectAllBanner();
    }
  }

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

  vouchersTable.addEventListener('change', function (e) {
    if (!e.target.classList.contains('vs-row-check')) return;
    if (e.target.checked) selectedIds.add(e.target.value);
    else                   selectedIds.delete(e.target.value);
    e.target.closest('tr').classList.toggle('vs-row-selected', e.target.checked);
    updateActionBar();
    updateSelectAllBanner();
  });

  // ── Cross-page Select All (server-side mode) ──────────────────────────────
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

    if (selSize >= totalMatch) {
      selectAllBanner.style.display       = 'block';
      selectAllBannerText.textContent     = 'All ' + totalMatch + ' matching row(s) selected.';
      selectAllMatchingLink.style.display = 'none';
      selectAllClearLink.style.display    = 'inline';
      return;
    }

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

        if (data.queued && data.status_url) {
          savePendingPdfJob({
            jobId:     data.job_id,
            statusUrl: data.status_url,
            startedAt: Date.now(),
          });
          saveLastJsonPdfJob({
            jobId:     data.job_id,
            statusUrl: data.status_url,
            startedAt: Date.now(),
          });
          toast.update('Generating PDF (job #' + data.job_id + ')...');
          pollPdfJob(data.job_id, data.status_url, toast);
          return;
        }

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

  btnArchive && btnArchive.addEventListener('click', () => {
    if (!selectedIds.size) return;
    pendingArchiveSingleId = null;
    openArchiveModal(Array.from(selectedIds));
  });

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
