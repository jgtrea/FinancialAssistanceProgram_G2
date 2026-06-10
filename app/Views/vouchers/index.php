<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $role = $role ?? 'admin' ?>
<?php $prefix = $role === 'admin' ? 'admin' : 'user' ?>
<?php $listingPath = $listingPath ?? 'vouchers' ?>
<?php $listingUrl = site_url($prefix . '/' . $listingPath) ?>
<?php $allowGenerate = (bool) ($allowGenerate ?? true) ?>
<?php $juniorHighSchools = $juniorHighSchools ?? [] ?>
<?php $seniorHighSchools = $seniorHighSchools ?? [] ?>
<?php $filterOptions = $filterOptions ?? ['junior_high_schools' => [], 'senior_high_schools' => []] ?>
<?php $filters = $filters ?? [] ?>
<?php $filterKeys = ['gender','remarks','voucher_status','date_from','date_to','junior_hs','preferred_hs','gwa_min','gwa_max', /* 'eligibility' */] ?>
<?php $f = static fn (string $k) => (string) ($filters[$k] ?? '') ?>
<?php $activeFilterCount = count(array_filter($filterKeys, fn ($k) => $f($k) !== '')) ?>

<div class="vs-page-header mb-3">
    <div>
      <h4 class="vs-page-title"><?= esc($title) ?></h4>
      <p class="vs-page-sub">Manage Student Financial Assistance Records.</p>
    </div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="vs-alert vs-alert-error mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif ?>
  <?php if (session()->getFlashdata('message')): ?>
    <div class="vs-alert vs-alert-success mb-3"><?= esc(session()->getFlashdata('message')) ?></div>
  <?php endif ?>

  <?php if ($allowGenerate): ?>
  <div class="vs-action-bar" id="actionBar" style="display:none">
    <span class="vs-action-bar-count"><span id="selectedCount">0</span> selected</span>
    <div class="vs-action-bar-buttons d-flex gap-2 ms-auto align-items-center">
        <button class="vs-btn vs-btn-dark-green" id="btnGeneratePdf">
          <?= asset_icon('voucher_add') ?>
          Generate Voucher
        </button>
      <button type="button" class="vs-btn vs-btn-success" id="btnOpenExport">
        <?= asset_icon('export') ?>
        Export
      </button>
    </div>
  </div>
  <?php endif ?>

  <div id="studentsAlertBox"></div>

  <form method="get" id="vouchersFilterForm" class="vs-page-toolbar row g-2 align-items-center mb-3">
    <?php foreach ($filterKeys as $k): ?>
      <input type="hidden" name="<?= esc($k, 'attr') ?>" value="<?= esc($f($k), 'attr') ?>">
    <?php endforeach ?>
    <div class="col-12 col-md-5">
      <input type="text" name="q" class="vs-input vs-advanced-search-input w-100" placeholder="Enter keyword to search (voucher no, name)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
    </div>
    <div class="col-6 col-md-2">
      <button type="button" class="vs-btn vs-btn-outline w-100" id="btnOpenFilter">
        Filters
        <span id="filterBadge" class="badge bg-primary" style="display:<?= $activeFilterCount > 0 ? 'inline-block' : 'none' ?>;margin-left:.35rem"><?= $activeFilterCount > 0 ? esc($activeFilterCount) : '' ?></span>
      </button>
    </div>
    <div class="col-auto d-none d-md-flex align-items-center">
      <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
    </div>
    <div class="col-6 col-md-2 d-flex gap-2">
      <button type="submit" class="vs-btn vs-btn-primary flex-fill">Search</button>
      <a href="<?= $listingUrl ?>" class="vs-btn vs-btn-danger flex-fill">Clear</a>
    </div>
    <div class="col-auto d-none d-md-flex align-items-center">
      <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
    </div>
    <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
<?php if ($allowGenerate): ?>
      <button type="button" class="vs-btn vs-btn-dark-green flex-fill flex-md-grow-0 flex-md-shrink-0" id="btnGenerateAll">
        <?= asset_icon('voucher_add') ?>
        Generate Voucher
      </button>
      <button type="button" class="vs-btn vs-btn-success flex-fill flex-md-grow-0 flex-md-shrink-0" id="btnExportAll">
        <?= asset_icon('export') ?>
        Export
      </button>
<?php else: ?>
      <button type="button" class="vs-btn vs-btn-success flex-fill flex-md-grow-0 flex-md-shrink-0" id="btnAddVoucher" data-mode="add">
        <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
        Add Voucher
      </button>
      <button type="button" class="vs-btn vs-btn-info flex-fill flex-md-grow-0 flex-md-shrink-0" id="btnOpenImport">
        <?= asset_icon('import') ?>
        Import
      </button>
<?php endif ?>
    </div>
  </form>

  <div class="vs-card">
    <div class="vs-card-body">
      <div class="vs-table-toolbar d-flex align-items-center gap-2 mb-3 flex-wrap">
        <input type="text" id="customStudentsSearch" class="vs-input vs-page-search" placeholder="Enter keyword to search this page" style="max-width:260px">
        <label class="vs-length-label ms-auto">Show <input type="number" id="vouchersLengthInput" class="vs-length-input" value="10" min="1" max="500"> entries</label>
      </div>
      <?php if ($allowGenerate): ?>
      <div id="selectAllBanner" style="display:none; margin-bottom:8px; padding:8px 12px; background:#fef3c7; border:1px solid #fcd34d; border-radius:6px">
        <span id="selectAllBannerText"></span>
        <a href="#" id="selectAllMatchingLink" style="font-weight:600; margin-left:8px">Select all matching</a>
        <a href="#" id="selectAllClearLink" style="margin-left:8px; display:none">Clear</a>
      </div>
      <?php endif ?>
      <table id="studentsTable" class="vs-datatable vs-mobile-primary"
             data-mobile-primary="2"
             data-allow-generate="<?= $allowGenerate ? '1' : '0' ?>"
             data-search-placeholder="Search students..."
             data-datatable-url="<?= site_url($prefix . '/students/datatable') ?>"
             data-matching-ids-url="<?= site_url($prefix . '/students/matching-ids') ?>"
             data-filter-params='<?= json_encode($filters ?? []) ?>'
             data-initial-search="<?= esc((string) ($keyword ?? ''), 'attr') ?>"
             style="width:100%">
        <thead>
          <tr>
            <th class="vs-th-check"><input type="checkbox" class="vs-check vs-check-all" aria-label="Select all students"></th>
            <th>Voucher No.</th>
            <th>Name</th>
            <th style="display:none">Name Sort</th>
            <th>Rank</th>
            <th>JHS</th>
            <th>SHS</th>
            <th>Remarks</th>
            <th>Printed</th>
            <th>Last Generated</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Rows loaded by DataTables via AJAX from the data-datatable-url endpoint. -->
          <!-- Schema matches the <th>s above: checkbox, voucher_no, name, name_sort (hidden),
               rank, jhs, shs, remarks, generate_count,
               last_generated, status, actions. Server-side cell HTML lives in
               Admin\Voucher::renderStudentRowForDatatable(). -->
        </tbody>
      </table>
    </div>
  </div>

<?php if ($allowGenerate): ?>
  <form id="pdfForm" method="POST" action="<?= site_url($prefix . '/vouchers/json-generate-pdf') ?>" style="display:none">
    <?= csrf_field() ?>
  </form>
<?php endif ?>

<form id="archiveForm" action="<?= site_url($prefix . '/vouchers/archive') ?>" style="display:none">
  <?= csrf_field() ?>
</form>

<?= pre_modal('vouchers') ?>
<?php if (!$allowGenerate): ?>
<?= modal_assets('importModal') ?>
<?php endif ?>

<script>
window.__VS.pageData = { filterOptions: <?= json_encode($filterOptions) ?> };
</script>

<script>
window.VM_CONFIG = {
  saveUrl:          '<?= site_url('students/save') ?>',
  fetchUrl:         '<?= site_url('students/json') ?>',
  schoolOptionsUrl: '<?= site_url($prefix . '/schools/options') ?>',
};
</script>

<script>
document.addEventListener('vs:modals:ready', function () {
  var csrfName = '<?= csrf_token() ?>';
  var csrfHash = '<?= csrf_hash() ?>';
  var schoolOptionsUrl = '<?= site_url($prefix . '/schools/options') ?>';

<?php if (!$allowGenerate): ?>
  // ── Import ──────────────────────────────────────────────────────────────────
  var importModal  = document.getElementById('importModal');
  var importFile   = document.getElementById('importFile');
  var importBtn    = document.getElementById('importConfirm');
  var importText   = document.getElementById('importBtnText');
  var importSpinner = document.getElementById('importBtnSpinner');

  document.getElementById('btnOpenImport').addEventListener('click', function () {
    importFile.value = '';
    importModal.style.display = 'flex';
  });
  document.getElementById('importModalClose').addEventListener('click', function () {
    importModal.style.display = 'none';
  });
  document.getElementById('importModalCancel').addEventListener('click', function () {
    importModal.style.display = 'none';
  });

  importBtn.addEventListener('click', function () {
    if (!importFile.files.length) {
      alert('Please select a file first.');
      return;
    }

    var fd = new FormData();
    var _importCsrf = (typeof getCsrfToken === 'function') ? getCsrfToken() : { name: csrfName, token: csrfHash };
    fd.append(_importCsrf.name, _importCsrf.token);
    fd.append('excel_file', importFile.files[0]);

    importBtn.disabled = true;
    importText.style.display = 'none';
    importSpinner.style.display = 'inline-block';

    fetch('<?= site_url('import_data') ?>', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        importModal.style.display = 'none';
        if (!data.success) {
          alert(data.message || 'Import failed.');
          return;
        }
        // Importing runs on the background worker now — show a live progress
        // toast (like generate/archive), reload when done. Validation rejects
        // come back via onError with the specific message.
        if (data.queued && data.status_url && typeof trackJob === 'function') {
          trackJob('Importing', data.status_url, {
            doneLabel: function (d) {
              if (d && d.message) return d.message;
              var n = (d && d.result && typeof d.result.imported === 'number') ? d.result.imported : 0;
              return n.toLocaleString() + ' record(s) imported.';
            },
            onDone:  function () { location.reload(); },
            onError: function (msg) { alert('Import failed: ' + msg); },
          });
          return;
        }
        if (data.message) alert(data.message);
        location.reload();
      })
      .catch(function () {
        alert('An error occurred while uploading. Please try again.');
      })
      .finally(function () {
        importBtn.disabled = false;
        importText.style.display = 'inline';
        importSpinner.style.display = 'none';
      });
  });

  importModal.addEventListener('click', function (e) {
    if (e.target === importModal) importModal.style.display = 'none';
  });
<?php endif ?>

  // ── Generate Voucher (toolbar) ─────────────────────────────────────────────
  var btnGenerateAll = document.getElementById('btnGenerateAll');
  btnGenerateAll && btnGenerateAll.addEventListener('click', function () {
    runBulkAll('generate');
  });

  // ── Export ──────────────────────────────────────────────────────────────────
  var exportModal = document.getElementById('exportModal');

  function updateExportLinksForFilters() {
    var query = buildCountQuery();
    document.querySelectorAll('[data-export-format]').forEach(function (link) {
      var format = link.dataset.exportFormat || 'xlsx';
      if (!link.dataset.exportBase) link.dataset.exportBase = link.href.split('?')[0];
      link.href = link.dataset.exportBase + '?format=' + encodeURIComponent(format)
        + (query ? '&' + query : '');
    });
  }

  var btnOpenExport = document.getElementById('btnOpenExport');
  if (btnOpenExport) {
    btnOpenExport.addEventListener('click', function () {
      exportModal.style.display = 'flex';
    });
  }
  var btnExportAll = document.getElementById('btnExportAll');
  btnExportAll && btnExportAll.addEventListener('click', function () {
    updateExportLinksForFilters();
    exportModal.style.display = 'flex';
  });
  document.getElementById('exportModalClose').addEventListener('click', function () {
    exportModal.style.display = 'none';
  });

  exportModal.addEventListener('click', function (e) {
    if (e.target === exportModal) exportModal.style.display = 'none';
  });

  // ── Advanced Filters ───────────────────────────────────────────────────────
  // script.js initializes the DataTable on DOMContentLoaded. Our IIFE runs
  // earlier (at script-eval time), so defer until both DOM + DataTable exist.
  function initFilters() {
    var studentsTable = document.getElementById('studentsTable');
    if (!studentsTable || !window.jQuery || !$.fn.DataTable || !$.fn.DataTable.isDataTable(studentsTable)) {
      // DataTable plugin or table not ready yet — retry shortly.
      // The $.fn.DataTable existence check must precede the .isDataTable() call,
      // otherwise we'd dereference undefined and the retry chain dies.
      return setTimeout(initFilters, 50);
    }
    setupFilters(studentsTable);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFilters);
  } else {
    initFilters();
  }

  function setupFilters(studentsTable) {
  var dt = $(studentsTable).DataTable();

  var fields = {
    gender:         document.getElementById('filterGender'),
    remarks:        document.getElementById('filterRemarks'),
    voucherStatus:  document.getElementById('filterVoucherStatus'),
    dateFrom:       document.getElementById('filterDateFrom'),
    dateTo:         document.getElementById('filterDateTo'),
    juniorHs:       document.getElementById('filterJuniorHs'),
    preferredHs:    document.getElementById('filterPreferredHs'),
    gwaMin:         document.getElementById('filterGwaMin'),
    gwaMax:         document.getElementById('filterGwaMax'),
    // eligibility:    document.getElementById('filterEligibility'),
  };

  var filterForm = document.getElementById('vouchersFilterForm');

  // Map of modal field id → form hidden-input name. Used to copy values from
  // modal → form on Apply, and to clear both on Clear All.
  var filterFieldToParam = {
    gender:        'gender',
    remarks:       'remarks',
    voucherStatus: 'voucher_status',
    dateFrom:      'date_from',
    dateTo:        'date_to',
    juniorHs:      'junior_hs',
    preferredHs:   'preferred_hs',
    gwaMin:        'gwa_min',
    gwaMax:        'gwa_max',
    // eligibility:   'eligibility',
  };

  // JHS / SHS dropdowns are fully populated server-side from
  // DISTINCT values in the students table (see VoucherModel::getListingFilterOptions).

  // Wire custom length input.
  var lenInput = document.getElementById('vouchersLengthInput');
  if (lenInput) {
    function applyVoucherLen() {
      var v = parseInt(lenInput.value, 10);
      if (!isNaN(v) && v > 0) dt.page.len(v).draw();
    }
    lenInput.addEventListener('change', applyVoucherLen);
    lenInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') applyVoucherLen(); });
  }

  var customSearch = document.getElementById('customStudentsSearch');
  if (customSearch && window.VS && window.VS.bindCurrentPageSearch) {
    window.VS.bindCurrentPageSearch(dt, customSearch);
  }

  var filterBtn         = document.getElementById('btnOpenFilter');
  var filterModal       = document.getElementById('filterModal');
  var filterModalClose  = document.getElementById('filterModalClose');
  var filterModalCancel = document.getElementById('filterModalCancel');
  var filterApply       = document.getElementById('filterApply');
  var filterClear       = document.getElementById('filterClear');

  function rebuildSelectKeepingSelection(select, items) {
    if (!select) return;
    var current = select.value;
    while (select.firstChild) select.removeChild(select.firstChild);
    select.appendChild(document.createElement('option')); // placeholder
    var seen = {};
    var currentSeen = false;
    // Items are {school_id, school_name} objects from /schools/options. The
    // option VALUE must be school_id (the filter matches students.junior_high_school,
    // an FK) while the visible label is school_name. (Falls back to plain strings
    // for any legacy caller.)
    items.forEach(function (item) {
      var id, label;
      if (item && typeof item === 'object') {
        id    = String(item.school_id != null ? item.school_id : (item.id != null ? item.id : ''));
        label = item.school_name != null ? item.school_name : (item.name != null ? item.name : id);
      } else {
        id = label = (item == null ? '' : String(item));
      }
      if (!id || seen[id]) return;
      seen[id] = true;
      var opt = document.createElement('option');
      opt.value = id;
      opt.textContent = label;
      if (current === id) { opt.selected = true; currentSeen = true; }
      select.appendChild(opt);
    });
    if (current && !currentSeen) {
      var opt = document.createElement('option');
      opt.value = current;
      opt.textContent = current;
      opt.selected = true;
      select.appendChild(opt);
    }
    if (window.jQuery) $(select).trigger('change.select2');
  }

  function refreshFilterSchoolOptions() {
    fetch(schoolOptionsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      rebuildSelectKeepingSelection(document.getElementById('filterJuniorHs'),    Array.isArray(data.jhs) ? data.jhs : []);
      rebuildSelectKeepingSelection(document.getElementById('filterPreferredHs'), Array.isArray(data.shs) ? data.shs : []);
    })
    .catch(function () {});
  }

  function openFilter()  {
    if (filterModal) filterModal.style.display = 'flex';
    refreshFilterSchoolOptions();
    if (typeof window.initVsSelect2 === 'function') window.initVsSelect2(filterModal);
  }
  function closeFilter() { if (filterModal) filterModal.style.display = 'none'; }

  filterBtn         && filterBtn.addEventListener('click', openFilter);
  filterModalClose  && filterModalClose.addEventListener('click', closeFilter);
  filterModalCancel && filterModalCancel.addEventListener('click', closeFilter);
  filterModal       && filterModal.addEventListener('click', function (e) {
    if (e.target === filterModal) closeFilter();
  });

  // Filters are applied server-side: we copy modal values into the hidden
  // inputs in #vouchersFilterForm and submit. The form GETs the same page with
  // `q` + filter params, which the controller passes through to the model.
  function syncFormFromModal() {
    if (!filterForm) return;
    Object.keys(filterFieldToParam).forEach(function (k) {
      var input = filterForm.elements[filterFieldToParam[k]];
      if (input && fields[k]) input.value = fields[k].value;
    });
  }

  filterApply && filterApply.addEventListener('click', function () {
    syncFormFromModal();
    if (filterForm) filterForm.submit();
  });

  filterClear && filterClear.addEventListener('click', function () {
    Object.keys(fields).forEach(function (k) {
      var el = fields[k];
      if (!el) return;
      el.value = '';
      if (window.jQuery && el.classList && el.classList.contains('js-filter-select')) {
        $(el).val('').trigger('change');
      }
    });
    if (filterForm) {
      Object.keys(filterFieldToParam).forEach(function (k) {
        var input = filterForm.elements[filterFieldToParam[k]];
        if (input) input.value = '';
      });
    }
    closeFilter();
    window.location.href = '<?= $listingUrl ?>';
  });
  }

  // ── Per-row Toggle-active + Bulk Activate / Deactivate ────────────────────
  var toggleActiveUrlBase       = '<?= site_url($prefix . '/vouchers/toggle-active') ?>';
  // var toggleEligibilityUrlBase  = '<?= site_url($prefix . '/vouchers/toggle-eligibility') ?>';
  var activateMultipleUrl       = '<?= site_url($prefix . '/vouchers/activate-multiple') ?>';
  var deactivateMultipleUrl     = '<?= site_url($prefix . '/vouchers/deactivate-multiple') ?>';

  var statusIcons = {
    active:   <?= json_encode(asset_icon('circle_check', ['width' => '18', 'height' => '18'])) ?>,
    inactive: <?= json_encode(asset_icon('circle_x',     ['width' => '18', 'height' => '18'])) ?>,
  };

  /*
  var eligIcons = {
    eligible:   <?= json_encode(asset_icon('circle_check', ['width' => '18', 'height' => '18'])) ?>,
    ineligible: <?= json_encode(asset_icon('circle_x',     ['width' => '18', 'height' => '18'])) ?>,
  };
  */

  function flashSuccess(msg) {
    var alertBox = document.getElementById('studentsAlertBox');
    if (!alertBox) return;
    var el = document.createElement('div');
    el.className = 'vs-alert vs-alert-success mb-3';
    el.textContent = msg;
    alertBox.innerHTML = '';
    alertBox.appendChild(el);
    setTimeout(function () { el.remove(); }, 5000);
  }

  function handleToggleActive(btn) {
    if (!btn || btn.disabled) return;
    var id = btn.getAttribute('data-id');
    if (!id) return;
    btn.disabled = true;

    var fd = new FormData();
    fd.append(csrfName, csrfHash);

    fetch(toggleActiveUrlBase + '/' + id, {
      method:      'POST',
      headers:     { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body:        fd,
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        btn.disabled = false;
        if (!data.success) { alert(data.message || 'Toggle failed.'); return; }
        if (data.csrf_token) csrfHash = data.csrf_token;
        var isActive = data.is_active === 1 || data.is_active === '1';
        var rowEl = document.getElementById('row-' + id);
        if (rowEl) {
          rowEl.setAttribute('data-active', isActive ? '1' : '0');
          if (isActive) rowEl.classList.remove('vs-row-archived');
          else          rowEl.classList.add('vs-row-archived');
          var iconSpan = rowEl.querySelector('.js-status-icon');
          if (iconSpan) {
            iconSpan.innerHTML   = isActive ? statusIcons.active : statusIcons.inactive;
            iconSpan.style.color = isActive ? '#16a34a' : '#9ca3af';
            iconSpan.title       = isActive ? 'Active' : 'Inactive';
            iconSpan.setAttribute('aria-label', isActive ? 'Active' : 'Inactive');
          }
        }
        btn.textContent = isActive ? 'Deactivate' : 'Activate';
        btn.setAttribute('data-active', isActive ? '1' : '0');
        btn.classList.toggle('text-danger', isActive);
        flashSuccess(data.message || 'Student ' + (isActive ? 'activated' : 'deactivated') + '.');
        if (window.jQuery && $.fn.DataTable) {
          var tbl = document.getElementById('studentsTable');
          if (tbl && $.fn.DataTable.isDataTable(tbl)) $(tbl).DataTable().draw(false);
        }
      })
      .catch(function () { btn.disabled = false; alert('An error occurred. Please try again.'); });
  }

  /*
  function handleToggleEligibility(btn) {
    if (!btn || btn.disabled) return;
    var id = btn.getAttribute('data-id');
    if (!id) return;
    btn.disabled = true;

    var fd = new FormData();
    fd.append(csrfName, csrfHash);

    fetch(toggleEligibilityUrlBase + '/' + id, {
      method:      'POST',
      headers:     { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body:        fd,
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        btn.disabled = false;
        if (!data.success) { alert(data.message || 'Toggle failed.'); return; }
        if (data.csrf_token) csrfHash = data.csrf_token;
        var newElig = data.eligibility_status;
        var rowEl = document.getElementById('row-' + id);
        if (rowEl) {
          rowEl.setAttribute('data-eligibility', newElig);
          var iconSpan = rowEl.querySelector('.js-elig-icon');
          if (iconSpan) {
            iconSpan.innerHTML   = newElig === 'eligible' ? eligIcons.eligible : eligIcons.ineligible;
            iconSpan.style.color = newElig === 'eligible' ? '#16a34a' : '#9ca3af';
            iconSpan.title       = newElig === 'eligible' ? 'Eligible' : 'Not eligible';
            iconSpan.setAttribute('aria-label', newElig === 'eligible' ? 'Eligible' : 'Not eligible');
          }
          var cb = rowEl.querySelector('.vs-row-check');
          if (cb) {
            cb.disabled = newElig === 'not_eligible';
            if (newElig === 'not_eligible') { cb.checked = false; }
            cb.title = newElig === 'not_eligible' ? 'Not eligible — cannot be selected' : '';
          }
        }
        btn.textContent = newElig === 'not_eligible' ? 'Mark Eligible' : 'Mark Not Eligible';
        btn.setAttribute('data-eligibility', newElig);
        if (data.remarks_status && rowEl) {
          var remarksSpan = rowEl.querySelector('.js-remarks-cell');
          if (remarksSpan) remarksSpan.textContent = data.remarks_status;
          rowEl.setAttribute('data-remarks', data.remarks_status);
        }
        flashSuccess(data.message || 'Eligibility updated.');
      })
      .catch(function () { btn.disabled = false; alert('An error occurred. Please try again.'); });
  }
  */

  // Event delegation — covers both static rows and AJAX-rendered DataTable rows
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-toggle-active');
    if (btn) { handleToggleActive(btn); return; }
    // var btn2 = e.target.closest('.js-toggle-eligibility');
    // if (btn2) { handleToggleEligibility(btn2); return; }
  });

  // ── Bulk Activate / Deactivate ─────────────────────────────────────────────
  function getSelectedIds() {
    return Array.from(document.querySelectorAll('.vs-row-check:checked:not([disabled])'))
      .map(function (cb) { return cb.value; }).filter(Boolean);
  }

  function bulkActiveAction(url, actionLabel, newActiveState) {
    var ids = getSelectedIds();
    if (!ids.length) { alert('No students selected.'); return; }
    var fd = new FormData();
    fd.append(csrfName, csrfHash);
    fd.append('voucher_ids', ids.join(','));
    fetch(url, {
      method:      'POST',
      headers:     { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body:        fd,
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) { alert(data.message || actionLabel + ' failed.'); return; }
        if (data.csrf_token) csrfHash = data.csrf_token;
        var isActive = newActiveState === 1;
        ids.forEach(function (id) {
          var rowEl = document.getElementById('row-' + id);
          if (!rowEl) return;
          rowEl.setAttribute('data-active', isActive ? '1' : '0');
          if (isActive) rowEl.classList.remove('vs-row-archived');
          else          rowEl.classList.add('vs-row-archived');
          var iconSpan = rowEl.querySelector('.js-status-icon');
          if (iconSpan) {
            iconSpan.innerHTML   = isActive ? statusIcons.active : statusIcons.inactive;
            iconSpan.style.color = isActive ? '#16a34a' : '#9ca3af';
            iconSpan.title       = isActive ? 'Active' : 'Inactive';
            iconSpan.setAttribute('aria-label', isActive ? 'Active' : 'Inactive');
          }
          var toggleBtn = rowEl.querySelector('.js-toggle-active');
          if (toggleBtn) {
            toggleBtn.textContent = isActive ? 'Deactivate' : 'Activate';
            toggleBtn.setAttribute('data-active', isActive ? '1' : '0');
          }
        });
        if (window.jQuery && $.fn.DataTable) {
          var tbl = document.getElementById('studentsTable');
          if (tbl && $.fn.DataTable.isDataTable(tbl)) $(tbl).DataTable().draw(false);
        }
        flashSuccess(data.message || actionLabel + ' successful.');
      })
      .catch(function () { alert('An error occurred. Please try again.'); });
  }

  // ── More Actions dropdown — sweep DB by current search + filter scope ──────
  var activateAllUrl   = '<?= site_url($prefix . '/vouchers/activate-all') ?>';
  var deactivateAllUrl = '<?= site_url($prefix . '/vouchers/deactivate-all') ?>';
  var archiveAllUrl    = '<?= site_url($prefix . '/vouchers/archive-all') ?>';
  var generateAllUrl   = '<?= site_url($prefix . '/vouchers/generate-all') ?>';
  var countMatchingUrl = '<?= site_url($prefix . '/vouchers/count-matching') ?>';

  function collectFilterScope() {
    var fd = new FormData();
    fd.append(csrfName, csrfHash);
    var form = document.getElementById('vouchersFilterForm');
    if (form) {
      var qInput = form.querySelector('input[name="q"]');
      if (qInput) fd.append('q', qInput.value || '');
      Array.from(form.querySelectorAll('input[type="hidden"]')).forEach(function (inp) {
        if (inp.name && inp.name !== csrfName) fd.append(inp.name, inp.value || '');
      });
    }
    return fd;
  }

  function buildCountQuery() {
    var params = new URLSearchParams();
    var form = document.getElementById('vouchersFilterForm');
    if (form) {
      var qInput = form.querySelector('input[name="q"]');
      if (qInput) params.append('q', qInput.value || '');
      Array.from(form.querySelectorAll('input[type="hidden"]')).forEach(function (inp) {
        if (inp.name && inp.name !== csrfName) params.append(inp.name, inp.value || '');
      });
    }
    return params.toString();
  }

  // ── Generic info modal (replaces window.alert for bulk flows) ─────────────
  var infoModal       = document.getElementById('infoModal');
  var infoModalTitle  = document.getElementById('infoModalTitle');
  var infoModalMsg    = document.getElementById('infoModalMessage');
  var infoModalClose  = document.getElementById('infoModalClose');
  var infoModalOk     = document.getElementById('infoModalOk');

  function showInfo(message, title) {
    if (!infoModal) { alert(message); return; }
    infoModalTitle.textContent = title || 'Notice';
    infoModalMsg.textContent   = message;
    infoModal.style.display    = 'flex';
  }
  function closeInfo() { if (infoModal) infoModal.style.display = 'none'; }
  infoModalClose && infoModalClose.addEventListener('click', closeInfo);
  infoModalOk    && infoModalOk.addEventListener('click', closeInfo);
  infoModal      && infoModal.addEventListener('click', function (e) {
    if (e.target === infoModal) closeInfo();
  });

  var bulkAllModal       = document.getElementById('bulkAllModal');
  var bulkAllTitle       = document.getElementById('bulkAllTitle');
  var bulkAllMessage     = document.getElementById('bulkAllMessage');
  var bulkAllCount       = document.getElementById('bulkAllCount');
  var bulkAllReasonWrap  = document.getElementById('bulkAllReasonWrap');
  var bulkAllReason      = document.getElementById('bulkAllReason');
  var bulkAllCancel      = document.getElementById('bulkAllCancel');
  var bulkAllClose       = document.getElementById('bulkAllModalClose');
  var bulkAllConfirm     = document.getElementById('bulkAllConfirm');
  var bulkAllBtnText     = document.getElementById('bulkAllBtnText');
  var bulkAllBtnSpinner  = document.getElementById('bulkAllBtnSpinner');
  var pendingBulkAction  = null;

  function closeBulkAllModal() {
    if (bulkAllModal) bulkAllModal.style.display = 'none';
    pendingBulkAction = null;
  }
  bulkAllCancel && bulkAllCancel.addEventListener('click', closeBulkAllModal);
  bulkAllClose  && bulkAllClose.addEventListener('click', closeBulkAllModal);
  bulkAllModal  && bulkAllModal.addEventListener('click', function (e) {
    if (e.target === bulkAllModal) closeBulkAllModal();
  });

  function openBulkAllModal(action, count) {
    var titleMap = { activate: 'Activate All', deactivate: 'Deactivate All', archive: 'Archive All', generate: 'Generate Vouchers' };
    var verbMap  = { activate: 'activate', deactivate: 'deactivate', archive: 'archive', generate: 'generate vouchers for' };
    var btnClass = action === 'archive' ? 'vs-btn vs-btn-danger' : (action === 'generate' ? 'vs-btn vs-btn-dark-green' : 'vs-btn vs-btn-primary');

    bulkAllTitle.textContent   = titleMap[action] || 'Confirm';
    bulkAllCount.textContent   = count;
    bulkAllMessage.innerHTML   = 'You are about to <strong>' + verbMap[action] + '</strong> '
      + '<strong id="bulkAllCount">' + count + '</strong> student(s) matching the current search/filters.'
      + (action === 'archive' ? ' This cannot be undone.' : '')
      + (action === 'generate' ? ' Students that are inactive or missing a preferred school will be skipped.' : '');
    bulkAllReasonWrap.style.display = action === 'archive' ? 'block' : 'none';
    if (bulkAllReason) bulkAllReason.value = '';
    bulkAllConfirm.className = btnClass;
    bulkAllBtnText.textContent = 'Confirm ' + (titleMap[action] || '');
    pendingBulkAction = action;
    bulkAllModal.style.display = 'flex';
  }

  function runBulkAll(action) {
    fetch(countMatchingUrl + '?' + buildCountQuery(), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var count = (data && data.count) ? parseInt(data.count, 10) : 0;
        if (!count) {
          showInfo('No students match the current search/filter.', 'No matches');
          return;
        }
        openBulkAllModal(action, count);
      })
      .catch(function () { showInfo('Failed to count matching students.', 'Error'); });
  }

  bulkAllConfirm && bulkAllConfirm.addEventListener('click', function () {
    if (!pendingBulkAction) return;
    var action = pendingBulkAction;
    var urlMap = { activate: activateAllUrl, deactivate: deactivateAllUrl, archive: archiveAllUrl, generate: generateAllUrl };
    var url    = urlMap[action];
    if (!url) return;

    var fd = collectFilterScope();
    if (action === 'archive') {
      var reason = bulkAllReason && bulkAllReason.value.trim();
      fd.append('archive_reason', reason || 'Bulk archive (Archive All)');
    }

    bulkAllConfirm.disabled = true;
    bulkAllBtnText.style.display    = 'none';
    bulkAllBtnSpinner.style.display = 'inline-block';

    fetch(url, {
      method:      'POST',
      headers:     { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body:        fd,
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) {
          showInfo(data.message || (action + ' failed.'), 'Error');
          return;
        }
        // Archive All / Generate All queue a background job — show a live
        // progress toast and reload when it finishes. Activate/Deactivate All
        // stay synchronous (no status_url).
        if (data.queued && data.status_url) {
          if (action === 'generate') {
            trackJob('Generating', data.status_url, {
              count: data.count || 0,
              doneLabel: function () {
                return (data.count || 0).toLocaleString() + ' voucher(s) generated.';
              },
              onDone:  function () { location.reload(); },
              onError: function (msg) { showInfo('Generate failed: ' + msg, 'Error'); },
            });
            return;
          }
          trackArchiveJob(data.status_url, data.count || 0, {
            onDone:  function () { location.reload(); },
            onError: function (msg) { showInfo('Archive failed: ' + msg, 'Error'); },
          });
          return;
        }
        flashSuccess(data.message || (action + ' successful.'));
        location.reload();
      })
      .catch(function () { showInfo('An error occurred. Please try again.', 'Error'); })
      .finally(function () {
        bulkAllConfirm.disabled = false;
        bulkAllBtnText.style.display    = 'inline';
        bulkAllBtnSpinner.style.display = 'none';
        closeBulkAllModal();
      });
  });

  // ── TEMP: Unarchive All — restore every student_archive row to students ────
  var restoreAllUrl = '<?= site_url($prefix . '/vouchers/restore-all-archive') ?>';
  var btnRestoreAllArchive = document.getElementById('btnRestoreAllArchive');
  btnRestoreAllArchive && btnRestoreAllArchive.addEventListener('click', function () {
    if (!window.confirm('TEMP: Restore every row from student_archive back into students? Use only for testing Archive All.')) return;
    var fd = new FormData();
    fd.append(csrfName, csrfHash);
    btnRestoreAllArchive.disabled = true;
    fetch(restoreAllUrl, {
      method:      'POST',
      headers:     { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body:        fd,
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        btnRestoreAllArchive.disabled = false;
        if (!data.success) { showInfo(data.message || 'Restore failed.', 'Error'); return; }
        flashSuccess(data.message || 'Restored from archive.');
        location.reload();
      })
      .catch(function () { btnRestoreAllArchive.disabled = false; showInfo('An error occurred. Please try again.', 'Error'); });
  });
});
</script>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/custom/voucher_modal.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/custom/voucher_modal.js') ?: time() ?>"></script>
<?= $this->endSection() ?>
