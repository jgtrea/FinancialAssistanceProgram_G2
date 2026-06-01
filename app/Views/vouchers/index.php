<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $role = $role ?? 'admin' ?>
<?php $prefix = $role === 'admin' ? 'admin' : 'user' ?>
<?php $juniorHighSchools = $juniorHighSchools ?? [] ?>
<?php $seniorHighSchools = $seniorHighSchools ?? [] ?>
<?php $filterOptions = $filterOptions ?? ['junior_high_schools' => [], 'senior_high_schools' => [], 'school_years' => []] ?>
<?php $filters = $filters ?? [] ?>
<?php $filterKeys = ['school_year','gender','remarks','voucher_status','date_from','date_to','junior_hs','preferred_hs','gwa_min','gwa_max','eligibility'] ?>
<?php $f = static fn (string $k) => (string) ($filters[$k] ?? '') ?>
<?php $activeFilterCount = count(array_filter($filterKeys, fn ($k) => $f($k) !== '')) ?>

<div class="vs-page-header mb-3">
    <div>
      <h4 class="vs-page-title"><?= esc($title) ?></h4>
      <p class="vs-page-sub">Manage student financial assistance records.</p>
    </div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="vs-alert vs-alert-error mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif ?>
  <?php if (session()->getFlashdata('message')): ?>
    <div class="vs-alert vs-alert-success mb-3"><?= esc(session()->getFlashdata('message')) ?></div>
  <?php endif ?>

  <div class="vs-action-bar" id="actionBar" style="display:none">
    <span class="vs-action-bar-count"><span id="selectedCount">0</span> selected</span>
    <div class="d-flex gap-2 ms-auto align-items-center">
      <button class="vs-btn vs-btn-dark-green" id="btnGeneratePdf">
        <?= asset_icon('voucher-add') ?>
        Generate Voucher
      </button>
      <button type="button" class="vs-btn vs-btn-outline" id="btnOpenStatus">
        Status
      </button>
      <button type="button" class="vs-btn vs-btn-success" id="btnOpenExport">
        <?= asset_icon('export') ?>
        Export
      </button>
    </div>
  </div>

  <div id="studentsAlertBox"></div>

  <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
    <form method="get" id="vouchersFilterForm" class="vs-advanced-search vs-advanced-search-outside">
      <input type="text" name="q" class="vs-input vs-advanced-search-input" placeholder="Enter keyword to search (voucher no, name, etc.)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
      <button type="button" class="vs-btn vs-btn-outline" id="btnOpenFilter">
        Filters
        <span id="filterBadge" class="badge bg-primary" style="display:<?= $activeFilterCount > 0 ? 'inline-block' : 'none' ?>;margin-left:.35rem"><?= $activeFilterCount > 0 ? esc($activeFilterCount) : '' ?></span>
      </button>
      <?php foreach ($filterKeys as $k): ?>
        <input type="hidden" name="<?= esc($k, 'attr') ?>" value="<?= esc($f($k), 'attr') ?>">
      <?php endforeach ?>
    </form>
    <div class="ms-auto d-flex gap-2">
      <button type="button" class="vs-btn vs-btn-primary" id="btnAddVoucher" data-mode="add">
        <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
        Add Voucher
      </button>
      <button type="button" class="vs-btn vs-btn-outline" id="btnOpenImport">
        <?= asset_icon('import') ?>
        Import
      </button>
      <button type="button" class="vs-btn vs-btn-outline" id="btnActivateAll">Activate All</button>
    </div>
  </div>

  <div class="vs-card">
    <div class="vs-card-body">
      <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <input type="text" id="customStudentsSearch" class="vs-input vs-page-search" placeholder="Search all matching students..." style="max-width:260px">
        <label class="vs-length-label ms-auto">Show <input type="number" id="vouchersLengthInput" class="vs-length-input" value="10" min="1" max="500"> entries</label>
      </div>
      <!-- Cross-page select banner — appears when user checks the page header
           checkbox in server-side mode so they can extend the selection to
           every matching row across all pages, not just the visible page. -->
      <div id="selectAllBanner" style="display:none; margin-bottom:8px; padding:8px 12px; background:#fef3c7; border:1px solid #fcd34d; border-radius:6px">
        <span id="selectAllBannerText"></span>
        <a href="#" id="selectAllMatchingLink" style="font-weight:600; margin-left:8px">Select all matching</a>
        <a href="#" id="selectAllClearLink" style="margin-left:8px; display:none">Clear</a>
      </div>
      <table id="studentsTable" class="vs-datatable"
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
            <th>Junior High School</th>
            <th>Preferred School</th>
            <th>School Year</th>
            <th>Eligibility</th>
            <th>Status</th>
            <th>Remarks</th>
            <th>Generate Count</th>
            <th>Last Generated</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Rows loaded by DataTables via AJAX from the data-datatable-url endpoint. -->
          <!-- Schema matches the <th>s above: checkbox, voucher_no, name, name_sort (hidden),
               jhs, shs, school_year, eligibility, status, remarks, generate_count,
               last_generated, actions. Server-side cell HTML lives in
               Admin\Voucher::renderStudentRowForDatatable(). -->
        </tbody>
      </table>
    </div>
  </div>

<!-- Archive modal -->
<div class="vs-modal-overlay" id="archiveModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5>Archive Students</h5>
      <button class="vs-modal-close" id="archiveModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <p>You are about to archive <strong id="archiveCount">0</strong> student(s). This will move them to the archive.</p>
      <label class="vs-label" for="archiveReason">Reason (optional)</label>
      <input type="text" id="archiveReason" class="vs-input" placeholder="e.g. End of school year">
    </div>
    <div class="vs-modal-footer">
      <button class="vs-btn vs-btn-outline" id="archiveModalCancel">Cancel</button>
      <button class="vs-btn vs-btn-danger" id="archiveConfirm">
        <span id="archiveBtnText">Confirm Archive</span>
        <span id="archiveBtnSpinner" class="vs-spinner" style="display:none"></span>
      </button>
    </div>
  </div>
</div>

<form id="pdfForm" method="POST" action="<?= site_url($prefix . '/vouchers/json-generate-pdf') ?>" style="display:none">
  <?= csrf_field() ?>
</form>

<!-- Status modal now lives in layouts/main.php so the toast Status button works on every page. -->

<form id="archiveForm" action="<?= site_url($prefix . '/vouchers/archive') ?>" style="display:none">
  <?= csrf_field() ?>
</form>

<!-- Generic info/error modal -->
<div class="vs-modal-overlay" id="infoModal" style="display:none">
  <div class="vs-modal" style="max-width:420px">
    <div class="vs-modal-header">
      <h5 id="infoModalTitle">Notice</h5>
      <button class="vs-modal-close" id="infoModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <p id="infoModalMessage" class="mb-0"></p>
    </div>
    <div class="vs-modal-footer">
      <button class="vs-btn vs-btn-primary" id="infoModalOk">OK</button>
    </div>
  </div>
</div>

<!-- Bulk-All confirm modal (Activate / Deactivate / Archive All) -->
<div class="vs-modal-overlay" id="bulkAllModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5 id="bulkAllTitle">Confirm</h5>
      <button class="vs-modal-close" id="bulkAllModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <p id="bulkAllMessage">You are about to update <strong id="bulkAllCount">0</strong> student(s) matching the current search/filters.</p>
      <div id="bulkAllReasonWrap" style="display:none">
        <label class="vs-label" for="bulkAllReason">Reason (optional)</label>
        <input type="text" id="bulkAllReason" class="vs-input" placeholder="e.g. End of school year">
      </div>
    </div>
    <div class="vs-modal-footer">
      <button class="vs-btn vs-btn-outline" id="bulkAllCancel">Cancel</button>
      <button class="vs-btn vs-btn-primary" id="bulkAllConfirm">
        <span id="bulkAllBtnText">Confirm</span>
        <span id="bulkAllBtnSpinner" class="vs-spinner" style="display:none"></span>
      </button>
    </div>
  </div>
</div>

<!-- Import modal -->
<div class="vs-modal-overlay" id="importModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5>Import Students</h5>
      <button class="vs-modal-close" id="importModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <p class="text-muted small mb-3">
        Upload an <strong>.xlsx</strong>, <strong>.xls</strong>, or <strong>.csv</strong> file.<br>
        Columns must be in this exact order:
        <em>Voucher No., Voucher Date, Full Name, Rank No., GWA, Sex, Junior High School, Preferred Senior High School, Contact Number, Remarks</em>
      </p>
      <label class="vs-label" for="importFile">File</label>
      <input type="file" id="importFile" class="vs-input" accept=".xlsx,.xls,.csv">
    </div>
    <div class="vs-modal-footer">
      <button class="vs-btn vs-btn-outline" id="importModalCancel">Cancel</button>
      <button class="vs-btn vs-btn-primary" id="importConfirm">
        <span id="importBtnText">Import</span>
        <span id="importBtnSpinner" class="vs-spinner" style="display:none"></span>
      </button>
    </div>
  </div>
</div>

<?= $this->include('vouchers/_voucher_modal') ?>

<!-- Advanced Filters modal -->
<div class="vs-modal-overlay" id="filterModal" style="display:none">
  <div class="vs-modal" style="max-width:680px">
    <div class="vs-modal-header">
      <h5>Advanced Filters</h5>
      <button class="vs-modal-close" id="filterModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <div class="vs-form-grid vs-form-grid-4">
        <div class="vs-span-2">
          <label class="vs-label" for="filterSchoolYear">School Year</label>
          <input id="filterSchoolYear" type="text" list="dl-filter-school-year" class="vs-input" placeholder="All" value="<?= esc($f('school_year'), 'attr') ?>">
          <datalist id="dl-filter-school-year">
            <?php foreach (($filterOptions['school_years'] ?? []) as $sy): ?>
              <option value="<?= esc($sy) ?>">
            <?php endforeach ?>
          </datalist>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterGender">Sex</label>
          <input list="filterGender-list" id="filterGender" class="vs-input" placeholder="All" value="<?= esc($f('gender'), 'attr') ?>">
          <datalist id="filterGender-list">
            <option value="MALE">
            <option value="FEMALE">
          </datalist>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterRemarks">Remarks</label>
          <input list="filterRemarks-list" id="filterRemarks" class="vs-input" placeholder="All" value="<?= esc($f('remarks'), 'attr') ?>">
          <datalist id="filterRemarks-list">
            <option value="PASSED">
            <option value="FOR REVIEW">
            <option value="FAILED">
          </datalist>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterVoucherStatus">Voucher Status</label>
          <input list="filterVoucherStatus-list" id="filterVoucherStatus" class="vs-input" placeholder="All" value="<?= esc($f('voucher_status'), 'attr') ?>">
          <datalist id="filterVoucherStatus-list">
            <option value="generated">
            <option value="not_generated">
          </datalist>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterDateFrom">Voucher Date From</label>
          <input type="date" id="filterDateFrom" class="vs-input" value="<?= esc($f('date_from'), 'attr') ?>">
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterDateTo">Voucher Date To</label>
          <input type="date" id="filterDateTo" class="vs-input" value="<?= esc($f('date_to'), 'attr') ?>">
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterJuniorHs">Junior High School</label>
          <input list="filterJuniorHs-list" id="filterJuniorHs" class="vs-input" placeholder="All" value="<?= esc($f('junior_hs'), 'attr') ?>">
          <datalist id="filterJuniorHs-list">
            <?php foreach (($filterOptions['junior_high_schools'] ?? []) as $schoolName): ?>
              <option value="<?= esc($schoolName) ?>">
            <?php endforeach ?>
          </datalist>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterPreferredHs">Preferred Senior HS</label>
          <input list="filterPreferredHs-list" id="filterPreferredHs" class="vs-input" placeholder="All" value="<?= esc($f('preferred_hs'), 'attr') ?>">
          <datalist id="filterPreferredHs-list">
            <?php foreach (($filterOptions['senior_high_schools'] ?? []) as $schoolName): ?>
              <option value="<?= esc($schoolName) ?>">
            <?php endforeach ?>
          </datalist>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterGwaMin">GWA Min</label>
          <input type="number" step="0.01" id="filterGwaMin" class="vs-input" placeholder="e.g. 80" value="<?= esc($f('gwa_min'), 'attr') ?>">
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterGwaMax">GWA Max</label>
          <input type="number" step="0.01" id="filterGwaMax" class="vs-input" placeholder="e.g. 100" value="<?= esc($f('gwa_max'), 'attr') ?>">
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterEligibility">Eligibility Status</label>
          <input list="filterEligibility-list" id="filterEligibility" class="vs-input" placeholder="All" value="<?= esc($f('eligibility'), 'attr') ?>">
          <datalist id="filterEligibility-list">
            <option value="eligible">
            <option value="not_eligible">
          </datalist>
        </div>
      </div>
    </div>
    <div class="vs-modal-footer">
      <button class="vs-btn vs-btn-outline" id="filterClear">Clear All</button>
      <button class="vs-btn vs-btn-outline" id="filterModalCancel">Cancel</button>
      <button class="vs-btn vs-btn-primary" id="filterApply">Apply Filters</button>
    </div>
  </div>
</div>

<!-- Export modal -->
<div class="vs-modal-overlay" id="exportModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5>Export Students</h5>
      <button class="vs-modal-close" id="exportModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <p>Choose the file format to export the selected student records.</p>
      <div class="d-flex gap-3 mt-3">
        <a href="<?= site_url('vouchers/export?format=xlsx') ?>" data-export-format="xlsx" class="vs-btn vs-btn-outline flex-fill text-center">
          Excel (.xlsx)
        </a>
        <a href="<?= site_url('vouchers/export?format=csv') ?>" data-export-format="csv" class="vs-btn vs-btn-outline flex-fill text-center">
          CSV (.csv)
        </a>
      </div>
    </div>
  </div>
</div>

<script>
window.VM_CONFIG = {
  saveUrl:          '<?= site_url('students/save') ?>',
  fetchUrl:         '<?= site_url('students/json') ?>',
  schoolOptionsUrl: '<?= site_url($prefix . '/schools/options') ?>',
};
</script>

<script>
(function () {
  var csrfName = '<?= csrf_token() ?>';
  var csrfHash = '<?= csrf_hash() ?>';
  var schoolOptionsUrl = '<?= site_url($prefix . '/schools/options') ?>';

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
    fd.append(csrfName, csrfHash);
    fd.append('excel_file', importFile.files[0]);

    importBtn.disabled = true;
    importText.style.display = 'none';
    importSpinner.style.display = 'inline-block';

    fetch('<?= site_url('import_data') ?>', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        importModal.style.display = 'none';
        alert(data.message);
        if (data.success) location.reload();
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

  // ── Export ──────────────────────────────────────────────────────────────────
  var exportModal = document.getElementById('exportModal');

  var btnOpenExport = document.getElementById('btnOpenExport');
  if (btnOpenExport) {
    btnOpenExport.addEventListener('click', function () {
      exportModal.style.display = 'flex';
    });
  }
  document.getElementById('exportModalClose').addEventListener('click', function () {
    exportModal.style.display = 'none';
  });

  exportModal.addEventListener('click', function (e) {
    if (e.target === exportModal) exportModal.style.display = 'none';
  });
  importModal.addEventListener('click', function (e) {
    if (e.target === importModal) importModal.style.display = 'none';
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
    schoolYear:     document.getElementById('filterSchoolYear'),
    gender:         document.getElementById('filterGender'),
    remarks:        document.getElementById('filterRemarks'),
    voucherStatus:  document.getElementById('filterVoucherStatus'),
    dateFrom:       document.getElementById('filterDateFrom'),
    dateTo:         document.getElementById('filterDateTo'),
    juniorHs:       document.getElementById('filterJuniorHs'),
    preferredHs:    document.getElementById('filterPreferredHs'),
    gwaMin:         document.getElementById('filterGwaMin'),
    gwaMax:         document.getElementById('filterGwaMax'),
    eligibility:    document.getElementById('filterEligibility'),
  };

  var filterForm = document.getElementById('vouchersFilterForm');

  // Map of modal field id → form hidden-input name. Used to copy values from
  // modal → form on Apply, and to clear both on Clear All.
  var filterFieldToParam = {
    schoolYear:    'school_year',
    gender:        'gender',
    remarks:       'remarks',
    voucherStatus: 'voucher_status',
    dateFrom:      'date_from',
    dateTo:        'date_to',
    juniorHs:      'junior_hs',
    preferredHs:   'preferred_hs',
    gwaMin:        'gwa_min',
    gwaMax:        'gwa_max',
    eligibility:   'eligibility',
  };

  // School Year / JHS / SHS dropdowns are fully populated server-side from
  // DISTINCT values in the students table (see VoucherModel::getListingFilterOptions).

  // Hide the DT header row (length control) — replaced by custom row above.
  var dtWrap = studentsTable.closest('.dataTables_wrapper');
  var dtLengthEl = dtWrap ? dtWrap.querySelector('.dataTables_length') : null;
  if (dtLengthEl) (dtLengthEl.closest('.row') || dtLengthEl.parentElement).style.display = 'none';

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

  function refreshFilterSchoolOptions() {
    fetch(schoolOptionsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      var jhsList = document.getElementById('filterJuniorHs-list');
      var shsList = document.getElementById('filterPreferredHs-list');
      if (jhsList && Array.isArray(data.jhs)) {
        jhsList.innerHTML = '';
        data.jhs.forEach(function (name) {
          var opt = document.createElement('option');
          opt.value = name;
          jhsList.appendChild(opt);
        });
      }
      if (shsList && Array.isArray(data.shs)) {
        shsList.innerHTML = '';
        data.shs.forEach(function (name) {
          var opt = document.createElement('option');
          opt.value = name;
          shsList.appendChild(opt);
        });
      }
    })
    .catch(function () {});
  }

  function openFilter()  { if (filterModal) filterModal.style.display = 'flex'; refreshFilterSchoolOptions(); }
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
      if (fields[k]) fields[k].value = '';
    });
    if (filterForm) {
      Object.keys(filterFieldToParam).forEach(function (k) {
        var input = filterForm.elements[filterFieldToParam[k]];
        if (input) input.value = '';
      });
    }
  });
  }

  // ── Per-row Toggle-active + Bulk Activate / Deactivate ────────────────────
  var toggleActiveUrlBase       = '<?= site_url($prefix . '/vouchers/toggle-active') ?>';
  var toggleEligibilityUrlBase  = '<?= site_url($prefix . '/vouchers/toggle-eligibility') ?>';
  var activateMultipleUrl       = '<?= site_url($prefix . '/vouchers/activate-multiple') ?>';
  var deactivateMultipleUrl     = '<?= site_url($prefix . '/vouchers/deactivate-multiple') ?>';

  var statusIcons = {
    active:   <?= json_encode(asset_icon('circle_check', ['width' => '18', 'height' => '18'])) ?>,
    inactive: <?= json_encode(asset_icon('circle_x',     ['width' => '18', 'height' => '18'])) ?>,
  };

  var eligIcons = {
    eligible:   <?= json_encode(asset_icon('circle_check', ['width' => '18', 'height' => '18'])) ?>,
    ineligible: <?= json_encode(asset_icon('circle_x',     ['width' => '18', 'height' => '18'])) ?>,
  };

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
        flashSuccess(data.message || 'Student ' + (isActive ? 'activated' : 'deactivated') + '.');
      })
      .catch(function () { btn.disabled = false; alert('An error occurred. Please try again.'); });
  }

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
        flashSuccess(data.message || 'Eligibility updated.');
      })
      .catch(function () { btn.disabled = false; alert('An error occurred. Please try again.'); });
  }

  // Event delegation — covers both static rows and AJAX-rendered DataTable rows
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-toggle-active');
    if (btn) { handleToggleActive(btn); return; }
    var btn2 = e.target.closest('.js-toggle-eligibility');
    if (btn2) { handleToggleEligibility(btn2); return; }
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
    var titleMap = { activate: 'Activate All', deactivate: 'Deactivate All', archive: 'Archive All' };
    var verbMap  = { activate: 'activate', deactivate: 'deactivate', archive: 'archive' };
    var btnClass = action === 'archive' ? 'vs-btn vs-btn-danger' : 'vs-btn vs-btn-primary';

    bulkAllTitle.textContent   = titleMap[action] || 'Confirm';
    bulkAllCount.textContent   = count;
    bulkAllMessage.innerHTML   = 'You are about to <strong>' + verbMap[action] + '</strong> '
      + '<strong id="bulkAllCount">' + count + '</strong> student(s) matching the current search/filters.'
      + (action === 'archive' ? ' This cannot be undone.' : '');
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
    var urlMap = { activate: activateAllUrl, deactivate: deactivateAllUrl, archive: archiveAllUrl };
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

  var btnActivateAll = document.getElementById('btnActivateAll');
  btnActivateAll && btnActivateAll.addEventListener('click', function () {
    runBulkAll('activate');
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
}());
</script>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/custom/voucher-modal.js') ?>"></script>
<?= $this->endSection() ?>
