/* ============================================================
   VOUCHER PAGE — DataTables, checkbox, Generate PDF, Archive
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  // Handles BOTH the voucher generation page (#vouchersTable) and the
  // students listing page (#studentsTable).
  const vouchersTable = document.getElementById('vouchersTable')
                     || document.getElementById('studentsTable');
  if (!vouchersTable) return;

  // Server-side mode kicks in when the table has data-datatable-url. Used by
  // /admin/students to handle 30k+ rows without DOM blowup. Otherwise we fall
  // back to the original client-side init that renders all PHP-rendered rows.
  const datatableUrl = vouchersTable.dataset.datatableUrl || null;
  // The students/vouchers listing page renders its own "Show N entries"
  // input — skip DataTables' built-in length dropdown there so it doesn't
  // show twice. The voucher generation page has no custom input, so it
  // keeps the built-in dropdown.
  const customLengthInput = document.getElementById('vouchersLengthInput');
  // The Students page (data-allow-generate="0") drops bulk selection — hide
  // the row-checkbox column there. Other tables (vouchers/generate.php has no
  // data-allow-generate attr) keep showing it.
  const showCheckboxColumn = vouchersTable.dataset.allowGenerate !== '0';
  let dt;
  let dtMode = null;

  function voucherTableMode() {
    return window.VS && window.VS.isMobileTableMode && window.VS.isMobileTableMode(vouchersTable)
      ? 'mobile'
      : 'desktop';
  }

  function resetVoucherTableDom() {
    vouchersTable.querySelectorAll('tbody tr.parent').forEach(row => row.classList.remove('parent'));
    vouchersTable.querySelectorAll('tbody tr.child').forEach(row => row.remove());
  }

  function voucherMobileColumnDefs() {
    return window.VS && window.VS.mobilePrimaryColumnDefs
      ? window.VS.mobilePrimaryColumnDefs(vouchersTable)
      : [];
  }

  function voucherResponsiveConfig() {
    return false;
  }

  function bindVoucherSearch() {
    const currentPageSearch = document.getElementById('customStudentsSearch')
                           || document.getElementById('customVouchersSearch');
    if (!currentPageSearch || !window.VS) return;

    const cleanSearch = currentPageSearch.cloneNode(true);
    cleanSearch.value = currentPageSearch.value;
    cleanSearch.dataset.currentPageSearchBound = '';
    cleanSearch.dataset.fullTableSearchBound = '';
    currentPageSearch.parentNode.replaceChild(cleanSearch, currentPageSearch);

    if (datatableUrl && window.VS.bindFullTableSearch) {
      window.VS.bindFullTableSearch(dt, cleanSearch);
    } else if (window.VS.bindCurrentPageSearch) {
      window.VS.bindCurrentPageSearch(dt, cleanSearch);
    }
  }

  function decorateVoucherWrapper() {
    if (!vouchersTable.classList.contains('vs-mobile-primary') || !dt) return;
    const wrapper = dt.table().container();
    if (wrapper) wrapper.classList.add('vs-mobile-primary-wrapper');
  }

  function bindVoucherDtEvents() {
    if (!dt || typeof syncPageCheckboxes !== 'function') return;
    dt.off('.vsVoucher');
    dt.on('draw.dt.vsVoucher page.dt.vsVoucher search.dt.vsVoucher order.dt.vsVoucher', syncPageCheckboxes);
    if (typeof updateSelectAllBanner === 'function') {
      dt.on('draw.dt.vsVoucher', function () {
        // Reset selectable count on every draw — filter/search scope may have changed.
        if (typeof _selectableCount    !== 'undefined') _selectableCount    = null;
        if (typeof _fetchingSelectable !== 'undefined') _fetchingSelectable = false;
        updateSelectAllBanner();
      });
    }
  }

  function buildVoucherTable(force) {
    const mode = voucherTableMode();
    if (dt && dtMode === mode && !force) return dt;

    if (dt) {
      dt.rows().every(function () {
        if (this.child && this.child.isShown()) this.child.hide();
      });
      dt.destroy();
      dt = null;
      resetVoucherTableDom();
    } else if ($.fn.DataTable.isDataTable(vouchersTable)) {
      $(vouchersTable).DataTable().destroy();
      resetVoucherTableDom();
    }

    dtMode = mode;

    if (datatableUrl) {
      let filterParams = {};
      try { filterParams = JSON.parse(vouchersTable.dataset.filterParams || '{}'); } catch (e) {}

      const initialSearch = vouchersTable.dataset.initialSearch || '';
      const lenInputEl = document.getElementById('vouchersLengthInput');
      const initialPageLen = lenInputEl ? (parseInt(lenInputEl.value, 10) || 10) : 10;

      dt = $(vouchersTable).DataTable({
        destroy: true,
        serverSide: true,
        processing: true,
        ajax: {
          url: datatableUrl,
          type: 'GET',
          data: function (d) {
            Object.assign(d, filterParams);
            if (initialSearch) d.q = initialSearch;
          },
        },
        columns: [
          { data: 'checkbox',       orderable: false, width: '3%' },
          { data: 'voucher_no',     width: '9%' },
          { data: 'name',           width: '16%' },
          { data: 'name_sort',      visible: false },
          { data: 'rank',           width: '5%' },
          { data: 'jhs',            width: '7%' },
          { data: 'shs',            width: '7%' },
          { data: 'remarks',        width: '9%' },
          { data: 'generate_count', width: '9%' },
          { data: 'last_generated', width: '10%' },
          { data: 'status',         width: '6%' },
          { data: 'actions',        orderable: false, width: '10%' },
        ],
        columnDefs: [
          ...voucherMobileColumnDefs(),
          { orderData: [3], targets: [2] },
          ...(showCheckboxColumn ? [] : [{ visible: false, targets: 0 }]),
          ...(showCheckboxColumn ? [] : [{ visible: false, targets: [1, 8, 9] }]),
        ],
        order: [[3, 'asc']],
        dom:        (customLengthInput ? '' : window.VS.dtHeaderDom(false)) + window.VS.dtBodyDom,
        pageLength: initialPageLen,
        lengthMenu: window.VS.dtLengthMenuSS,
        responsive: voucherResponsiveConfig(),
        autoWidth:  false,
        language:   window.VS.dtLanguage({
          searchPlaceholder: vouchersTable.dataset.searchPlaceholder || 'Search students...',
          info:              'Showing _START_ to _END_ of _TOTAL_ matching',
        }),
      });
    } else {
      dt = $(vouchersTable).DataTable({
        destroy: true,
        dom:        (customLengthInput ? '' : window.VS.dtHeaderDom(false)) + window.VS.dtBodyDom,
        pageLength: 10,
        lengthMenu: window.VS.dtLengthMenu,
        responsive: voucherResponsiveConfig(),
        autoWidth:  false,
        order: [],
        columnDefs: [
          ...voucherMobileColumnDefs(),
          { orderable: false, targets: [0, -1] },
        ],
        language:   window.VS.dtLanguage({
          searchPlaceholder: vouchersTable.dataset.searchPlaceholder || 'Search vouchers...',
        }),
      });
    }

    decorateVoucherWrapper();
    bindVoucherSearch();
    return dt;
  }

  buildVoucherTable(true);
  if (window.VS && window.VS.bindMobilePrimaryDetails) {
    window.VS.bindMobilePrimaryDetails(vouchersTable, () => dt);
  }

  // ── Cross-page selection (Set of string IDs) ──────────────────────────────────
  const selectedIds = new Set();

  const actionBar  = document.getElementById('actionBar');
  const countLabel = document.getElementById('selectedCount');
  // Toolbar Export button now drives the selection-based export (the old
  // in-page action bar was removed).
  const btnOpenExport = document.getElementById('btnExportAll');
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
    if (!dt) return;
    const count         = selectedIds.size;
    const totalFiltered = dt.rows({ search: 'applied' }).count();
    if (countLabel) countLabel.textContent = count;
    if (actionBar) actionBar.style.display = count > 0 ? 'flex' : 'none';
    updateExportLinks();
    if (typeof updateSelectAllBanner === 'function') {
      updateSelectAllBanner();
    }
  }

  function syncPageCheckboxes() {
    if (!dt) return;
    const pageNodes = dt.rows({ page: 'current' }).nodes().toArray();
    const pageIds = [];
    pageNodes.forEach(function (row) {
      const cb = row.querySelector('.vs-row-check');
      if (!cb) return;
      cb.checked = !cb.disabled && selectedIds.has(cb.value);
      row.classList.toggle('vs-row-selected', cb.checked);
      if (!cb.disabled) pageIds.push(cb.value);
    });
    const pageSelectedCount = pageIds.filter(id => selectedIds.has(id)).length;
    getCheckAllBoxes().forEach(checkAll => {
      checkAll.checked       = false;
      checkAll.indeterminate = pageSelectedCount > 0;
    });
    updateActionBar();
  }

  // draw.dt fires AFTER AJAX rows are rendered — use it (not page.dt) to
  // re-sync checkboxes so selected rows are highlighted on every page load.
  bindVoucherDtEvents();

  document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('vs-check-all')) return;

    // Collect eligible row IDs on the CURRENT PAGE only.
    const currentNodes = dt.rows({ page: 'current' }).nodes().toArray();
    const pageIds = [];
    currentNodes.forEach(function (node) {
      // if (node.getAttribute('data-eligibility') === 'not_eligible') return;
      const cb = node.querySelector('.vs-row-check');
      if (cb && !cb.disabled) pageIds.push(cb.value);
    });

    // Check current page if not all on this page are selected; uncheck if all are.
    const allOnPageSelected = pageIds.length > 0 && pageIds.every(function (id) {
      return selectedIds.has(id);
    });
    if (allOnPageSelected) {
      pageIds.forEach(function (id) { selectedIds.delete(id); });
    } else {
      pageIds.forEach(function (id) { selectedIds.add(id); });
    }
    syncPageCheckboxes();
  });

  vouchersTable.addEventListener('change', function (e) {
    if (!e.target.classList.contains('vs-row-check')) return;
    if (e.target.checked) selectedIds.add(e.target.value);
    else                   selectedIds.delete(e.target.value);
    e.target.closest('tr').classList.toggle('vs-row-selected', e.target.checked);
    // Re-compute current-page ids and update check-all header state.
    const pageNodes = dt.rows({ page: 'current' }).nodes().toArray();
    const pageIds = pageNodes.reduce(function (acc, row) {
      const cb = row.querySelector('.vs-row-check');
      if (cb && !cb.disabled) acc.push(cb.value);
      return acc;
    }, []);
    const n = pageIds.filter(id => selectedIds.has(id)).length;
    getCheckAllBoxes().forEach(function (checkAll) {
      if (n === 0)                   { checkAll.checked = false; checkAll.indeterminate = false; }
      else if (n === pageIds.length) { checkAll.checked = true;  checkAll.indeterminate = false; }
      else                           { checkAll.checked = false; checkAll.indeterminate = true; }
    });
    updateActionBar();
    updateSelectAllBanner();
  });

  // ── Cross-page Select All (server-side mode) ──────────────────────────────
  const matchingIdsUrl       = vouchersTable.dataset.matchingIdsUrl || null;
  const selectAllBanner      = document.getElementById('selectAllBanner');
  const selectAllBannerText  = document.getElementById('selectAllBannerText');
  const selectAllMatchingLink= document.getElementById('selectAllMatchingLink');
  const selectAllClearLink   = document.getElementById('selectAllClearLink');

  // Tracks the selectable (eligible + active) count from matching-ids endpoint.
  // null = not yet fetched. Reset to null on every draw so filter/search changes pick up fresh count.
  let _selectableCount    = null;
  let _fetchingSelectable = false;

  function getFilterQueryString() {
    let filterParams = {};
    try { filterParams = JSON.parse(vouchersTable.dataset.filterParams || '{}'); } catch (e) {}
    const search = dt && dt.search ? dt.search() : '';
    const usp    = new URLSearchParams();
    Object.entries(filterParams).forEach(([k, v]) => { if (v !== '' && v != null) usp.append(k, v); });
    if (search) usp.append('q', search);
    return usp.toString();
  }

  function fetchSelectableCount() {
    if (_fetchingSelectable || !matchingIdsUrl) return;
    _fetchingSelectable = true;
    const qs  = getFilterQueryString();
    const url = matchingIdsUrl + (qs ? ('?' + qs) : '');
    fetch(url, ajaxOptions({ method: 'GET' }))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        _selectableCount    = data.count ?? (data.ids || []).length;
        _fetchingSelectable = false;
        updateSelectAllBanner();
      })
      .catch(function () { _fetchingSelectable = false; });
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

    // Fetch selectable count in background if not yet known.
    if (_selectableCount === null) fetchSelectableCount();

    const selectable = _selectableCount !== null ? _selectableCount : totalMatch;

    if (selSize >= selectable && selectable > 0) {
      selectAllBanner.style.display       = 'block';
      selectAllBannerText.textContent     = 'All ' + selectable + ' matching row(s) selected.';
      selectAllMatchingLink.style.display = 'none';
      selectAllClearLink.style.display    = 'inline';
      return;
    }

    selectAllBanner.style.display       = 'block';
    selectAllBannerText.textContent     = selSize + ' selected. ' + selectable + ' total matching.';
    selectAllMatchingLink.textContent   = 'Select all ' + selectable + ' matching across all pages';
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
        let ids = data.ids || [];
        _selectableCount = data.count ?? ids.length;

        // Cap the select-all at MAX_BATCH — you can't generate more than that in
        // one go, so don't let the selection exceed it. Take the first MAX_BATCH
        // matching ids and tell the user the rest were left out.
        if (ids.length > MAX_BATCH) {
          ids = ids.slice(0, MAX_BATCH);
          showAlert('You can select up to ' + MAX_BATCH + ' at a time. Selected the first ' + MAX_BATCH + ' of ' + _selectableCount + ' matching; narrow your filters to reach the rest.', 'warning');
        }

        ids.forEach(id => selectedIds.add(String(id)));
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
      _selectableCount    = null;
      _fetchingSelectable = false;
      selectedIds.clear();
      syncPageCheckboxes();
      updateSelectAllBanner();
    });
  }

  bindVoucherDtEvents();

  const voucherBreakpoint = window.matchMedia ? window.matchMedia(window.VS.dtMobileQuery) : null;
  function rebuildVoucherTableForBreakpoint() {
    const previousMode = dtMode;
    buildVoucherTable(false);
    if (dtMode !== previousMode) {
      bindVoucherDtEvents();
      syncPageCheckboxes();
      updateSelectAllBanner();
    }
  }
  if (vouchersTable.classList.contains('vs-mobile-primary')) {
    if (voucherBreakpoint && voucherBreakpoint.addEventListener) {
      voucherBreakpoint.addEventListener('change', rebuildVoucherTableForBreakpoint);
    } else if (voucherBreakpoint && voucherBreakpoint.addListener) {
      voucherBreakpoint.addListener(rebuildVoucherTableForBreakpoint);
    } else {
      window.addEventListener('resize', rebuildVoucherTableForBreakpoint);
    }
  }

  // Styled confirm dialog reusing the bulkAllModal shell (its old driver,
  // runBulkAll, was removed). Shows a count + title and runs opts.onConfirm on
  // confirm. Falls back to window.confirm when the modal isn't present.
  function vsConfirm(opts) {
    var modal      = document.getElementById('bulkAllModal');
    var titleEl    = document.getElementById('bulkAllTitle');
    var msgEl      = document.getElementById('bulkAllMessage');
    var reasonWrap = document.getElementById('bulkAllReasonWrap');
    var confirmBtn = document.getElementById('bulkAllConfirm');

    if (!modal || !titleEl || !msgEl || !confirmBtn) {
      if (window.confirm(opts.plain || 'Are you sure?')) opts.onConfirm();
      return;
    }

    titleEl.textContent = opts.title || 'Confirm';
    msgEl.innerHTML     = opts.html || '';
    if (reasonWrap) reasonWrap.style.display = 'none';

    // Clone the confirm button to drop any prior click handler, then wire this
    // invocation's action onto the fresh node.
    var fresh = confirmBtn.cloneNode(false);
    fresh.id        = 'bulkAllConfirm';
    fresh.className = opts.btnClass || 'vs-btn vs-btn-primary';
    fresh.textContent = opts.confirmLabel || 'Confirm';
    confirmBtn.parentNode.replaceChild(fresh, confirmBtn);

    function close() { modal.style.display = 'none'; }
    fresh.addEventListener('click', function () { close(); opts.onConfirm(); });

    var cancel = document.getElementById('bulkAllCancel');
    var x      = document.getElementById('bulkAllModalClose');
    if (cancel) cancel.onclick = close;
    if (x)      x.onclick = close;
    modal.onclick = function (e) { if (e.target === modal) close(); };

    modal.style.display = 'flex';
  }

  // ── Generate PDF ──────────────────────────────────────────────────────────────
  if (btnOpenExport && exportModal) {
    btnOpenExport.addEventListener('click', function () {
      if (!selectedIds.size) {
        showAlert('Select at least one student to export.', 'warning');
        return;
      }
      vsConfirm({
        title:        'Export Students',
        html:         'Export <strong>' + selectedIds.size + '</strong> selected student(s)?',
        plain:        'Export ' + selectedIds.size + ' selected student(s)?',
        confirmLabel: 'Export',
        btnClass:     'vs-btn vs-btn-success',
        onConfirm: function () {
          updateExportLinks();
          exportModal.style.display = 'flex';
        },
      });
    });
  }
  exportModalClose && exportModalClose.addEventListener('click', function () {
    exportModal.style.display = 'none';
  });
  exportModal && exportModal.addEventListener('click', function (e) {
    if (e.target === exportModal) exportModal.style.display = 'none';
  });

  // Export runs on the background worker now. Intercept the format links
  // (delegated — they're injected by the modal builder), enqueue the export,
  // show a progress toast, and auto-download the finished file. Delegation also
  // handles links added after this script runs.
  document.addEventListener('click', async function (e) {
    var link = e.target.closest('[data-export-format]');
    if (!link) return;
    e.preventDefault();

    updateExportLinks();              // ensure ?format=&ids= is current
    var url = link.href;
    if (exportModal) exportModal.style.display = 'none';

    try {
      var res  = await fetch(url, ajaxOptions({ method: 'GET' }));
      var data = await res.json();
      if (!data.success || !data.queued || !data.status_url) {
        showAlert((data && data.message) || 'Export failed to start.', 'error');
        return;
      }
      trackJob('Exporting', data.status_url, {
        download:  true,
        persist:   true,          // survive navigation/reload like the generate flow
        jobId:     data.job_id,
        doneLabel: function () { return 'Export ready — downloading…'; },
        onError:   function (msg) { showAlert('Export failed: ' + msg, 'error'); },
      });
    } catch (err) {
      console.error(err);
      showAlert('Export failed to start.', 'error');
    }
  });

  // Toolbar Generate Voucher button now drives selection-based generation.
  const btnGeneratePdf = document.getElementById('btnGenerateAll');
  const pdfForm        = document.getElementById('pdfForm');

  const MAX_BATCH = 100000;

  if (btnGeneratePdf && pdfForm) {
    async function doGeneratePdf() {
      btnGeneratePdf.disabled = true;
      // Unique key so this generation's toast is independent of any other
      // job's toast already on screen (job_id isn't known until the response).
      const toast = showPdfToast('Generating PDF...', 'gen-' + Date.now());

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
          // A referenced school is missing its acronym/name — block generation
          // and open that school's edit modal so it can be fixed, then retried.
          if (data.incomplete && data.edit_school_id && typeof window.vsOpenSchoolEdit === 'function') {
            showAlert(data.message || 'A school needs its acronym/name before generating.', 'warning');
            window.vsOpenSchoolEdit(data.edit_school_id);
            return;
          }
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

        // Some selected rows can be skipped server-side (inactive, or missing a
        // preferred senior high school). Let the user know they weren't generated.
        if (data.skipped && data.skipped > 0) {
          showAlert(data.skipped + ' selected student(s) were skipped — inactive or missing a preferred senior high school.', 'warning');
        }

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
          // Now that the job_id is known, scope this toast's Status button to it.
          if (toast.setJob) toast.setJob({ jobId: data.job_id, statusUrl: data.status_url });
          toast.update('Generating PDF (job #' + data.job_id + ')...');
          pollPdfJob(data.job_id, data.status_url, toast);
          return;
        }

        if (data.download_url) {
          toast.update('PDF ready to Download!', true, data.download_url);
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
    }

    btnGeneratePdf.addEventListener('click', function () {
      if (!selectedIds.size) {
        showAlert('Select at least one student to generate vouchers.', 'warning');
        return;
      }
      if (selectedIds.size > MAX_BATCH) {
        showAlert('You can only generate PDFs for up to ' + MAX_BATCH + ' students at a time. You have ' + selectedIds.size + ' selected.', 'warning');
        return;
      }
      vsConfirm({
        title:        'Generate Vouchers',
        html:         'Generate vouchers for <strong>' + selectedIds.size + '</strong> selected student(s)?',
        plain:        'Generate vouchers for ' + selectedIds.size + ' selected student(s)?',
        confirmLabel: 'Generate',
        btnClass:     'vs-btn vs-btn-dark-green',
        onConfirm:    doGeneratePdf,
      });
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

      const removeArchivedRows = function () {
        ids.forEach(function (id) {
          var row = document.getElementById('row-' + id);
          if (row) dt.row(row).remove();
          selectedIds.delete(id);
        });
        dt.draw(false);
        syncPageCheckboxes();
      };

      try {
        const res  = await fetch(archiveUrl, ajaxOptions({ method: 'POST', body: formData }));
        const data = await res.json();
        closeArchiveModal();

        if (!data.success) {
          showAlert(data.message || 'Archive failed.', 'error');
          return;
        }

        // Archiving now runs on the background worker — show a live progress
        // toast (like the generate flow) and drop the rows when it finishes.
        if (data.queued && data.status_url) {
          trackArchiveJob(data.status_url, data.count || ids.length, {
            jobId:   data.job_id,    // survive page navigation
            onDone:  function () { removeArchivedRows(); },
            onError: function (msg) { showAlert('Archive failed: ' + msg, 'error'); },
          });
          return;
        }

        // Fallback: synchronous response (legacy).
        removeArchivedRows();
        showAlert(data.message || 'Archived successfully.', 'success');
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
