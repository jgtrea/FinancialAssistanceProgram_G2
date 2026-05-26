<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $role = $role ?? 'admin' ?>
<?php $prefix = $role === 'admin' ? 'admin' : 'user' ?>
<?php $juniorHighSchools = $juniorHighSchools ?? [] ?>
<?php $seniorHighSchools = $seniorHighSchools ?? [] ?>

<div class="vs-page-header mb-4">
    <div>
      <h4 class="vs-page-title"><?= esc($title) ?></h4>
      <p class="vs-page-sub">Manage student financial assistance records.</p>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="vs-btn vs-btn-primary" id="btnAddVoucher" data-mode="add">
        <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
        Add Voucher
      </button>
      <button type="button" class="vs-btn vs-btn-outline" id="btnOpenImport">
        <?= asset_icon('import') ?>
        Import
      </button>
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
    <div class="d-flex gap-2 ms-auto">
      <button class="vs-btn vs-btn-blue" id="btnGeneratePdf">
        <?= asset_icon('voucher-add') ?>
        Generate Voucher
      </button>
      <button type="button" class="vs-btn vs-btn-success" id="btnOpenExport">
        <?= asset_icon('export') ?>
        Export
      </button>
      <button class="vs-btn vs-btn-danger" id="btnArchive">
        <?= asset_icon('archive') ?>
        Archive Selected
      </button>
    </div>
  </div>

  <div id="studentsAlertBox"></div>

  <div class="vs-card">
    <div class="vs-card-body">
      <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <input type="text" id="customStudentsSearch" class="vs-input" placeholder="Search students..." style="max-width:340px">
        <button type="button" class="vs-btn vs-btn-outline" id="btnOpenFilter">
          Filters
          <span id="filterBadge" class="badge bg-primary" style="display:none;margin-left:.35rem"></span>
        </button>
        
        <label class="vs-length-label ms-auto">Show <input type="number" id="vouchersLengthInput" class="vs-length-input" value="10" min="1" max="500"> entries</label>
      </div>
      <table id="studentsTable" class="vs-datatable" data-search-placeholder="Search students..." style="width:100%">
        <thead>
          <tr>
            <th class="vs-th-check"><input type="checkbox" class="vs-check vs-check-all" aria-label="Select all students"></th>
            <th>Voucher No.</th>
            <th>Name</th>
            <th>Junior High School</th>
            <th>Preferred School</th>
            <th>School Year</th>
            <th>Generate Count</th>
            <th>Last Generated</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vouchers as $v): ?>
          <tr id="row-<?= esc($v['student_id'], 'attr') ?>"
              data-gender="<?= esc((string) ($v['gender'] ?? ''), 'attr') ?>"
              data-remarks="<?= esc((string) ($v['remarks_status'] ?? ''), 'attr') ?>"
              data-voucher-date="<?= esc((string) ($v['voucher_date'] ?? ''), 'attr') ?>"
              data-voucher-status="<?= esc((string) ($v['voucher_status'] ?? ''), 'attr') ?>"
              data-gwa="<?= esc((string) ($v['gwa'] ?? ''), 'attr') ?>">
            <td><input type="checkbox" class="vs-check vs-row-check" value="<?= esc($v['student_id'], 'attr') ?>"></td>
            <td class="js-voucher-no"><?= esc($v['voucher_no'] ?: '-') ?></td>
            <td><?= esc($v['full_name']) ?></td>
            <td><?= esc($v['junior_high_school'] ?: '-') ?></td>
            <td><?= esc($v['preferred_senior_high_school']) ?></td>
            <td><?= esc($v['school_year']) ?></td>
            <td>
              <span class="js-generate-count"><?= esc((string) ($v['generate_count'] ?? 0)) ?></span>
            </td>
            <td><?= !empty($v['generated_at']) ? date('M d, Y', strtotime($v['generated_at'])) : '-' ?></td>
            <td>
              <div class="d-flex gap-1">
                <button type="button" class="vs-tbl-btn vs-tbl-btn-view js-voucher-action" data-mode="view" data-id="<?= esc($v['student_id'], 'attr') ?>">View</button>
                <button type="button" class="vs-tbl-btn vs-tbl-btn-edit js-voucher-action" data-mode="edit" data-id="<?= esc($v['student_id'], 'attr') ?>">Edit</button>
              </div>
            </td>
          </tr>
          <?php endforeach ?>
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

<form id="pdfForm" method="POST" action="<?= site_url($prefix . '/vouchers/generate-pdf') ?>" style="display:none">
  <?= csrf_field() ?>
</form>

<form id="archiveForm" action="<?= site_url($prefix . '/vouchers/archive') ?>" style="display:none">
  <?= csrf_field() ?>
</form>

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
        <em>Voucher No., Voucher Date, Full Name, Rank No., GWA, Gender, Junior High School, Preferred Senior High School, Contact Number, Remarks</em>
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

<!-- Voucher Add/View/Edit modal -->
<div class="vs-modal-overlay" id="voucherModal" style="display:none">
  <div class="vs-modal" style="max-width:780px">
    <div class="vs-modal-header">
      <h5 id="voucherModalTitle">Add Voucher</h5>
      <button class="vs-modal-close" id="voucherModalClose">&times;</button>
    </div>
    <form id="voucherModalForm" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="student_id" id="vmStudentId" value="">

      <div class="vs-modal-body">
        <div id="voucherModalAlert"></div>

        <div class="vs-form-grid vs-form-grid-4">
          <div>
            <label class="vs-label required" for="vmVoucherDate">Voucher Date</label>
            <input id="vmVoucherDate" name="voucher_date" type="date" class="vs-input" required>
          </div>

          <div>
            <label class="vs-label required" for="vmFirstName">First Name</label>
            <input id="vmFirstName" name="first_name" type="text" class="vs-input vs-uppercase" required>
          </div>

          <div>
            <label class="vs-label" for="vmMiddleName">Middle Name</label>
            <input id="vmMiddleName" name="middle_name" type="text" class="vs-input vs-uppercase">
          </div>

          <div>
            <label class="vs-label required" for="vmLastName">Last Name</label>
            <input id="vmLastName" name="last_name" type="text" class="vs-input vs-uppercase" required>
          </div>

          <div>
            <label class="vs-label" for="vmSuffix">Suffix</label>
            <select id="vmSuffix" name="suffix" class="vs-input">
              <option value="">-- Select --</option>
              <option value="JR.">Jr.</option>
              <option value="SR.">Sr.</option>
              <option value="II">II</option>
              <option value="III">III</option>
              <option value="IV">IV</option>
            </select>
          </div>

          <div>
            <label class="vs-label" for="vmGender">Gender</label>
            <select id="vmGender" name="gender" class="vs-input">
              <option value="">-- Select --</option>
              <option value="MALE">Male</option>
              <option value="FEMALE">Female</option>
            </select>
          </div>

          <div>
            <label class="vs-label" for="vmGwa">GWA</label>
            <input id="vmGwa" name="gwa" type="number" step="0.01" class="vs-input">
          </div>

          <div>
            <label class="vs-label" for="vmRankNo">Rank No.</label>
            <input id="vmRankNo" name="rank_no" type="number" class="vs-input">
          </div>

          <div>
            <label class="vs-label" for="vmContactNumber">Contact Number</label>
            <input id="vmContactNumber" name="contact_number" type="text" class="vs-input vs-uppercase">
          </div>

          <div class="vs-span-2">
            <label class="vs-label" for="vmJuniorHs">Junior High School</label>
            <select id="vmJuniorHs" name="junior_high_school" class="vs-input">
              <option value="">-- Select --</option>
              <?php foreach ($juniorHighSchools as $school): ?>
                <?php $schoolName = $school['school_name'] ?? '' ?>
                <option value="<?= esc($schoolName) ?>"><?= esc($schoolName) ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div class="vs-span-2">
            <label class="vs-label required" for="vmPreferredHs">Preferred Senior High School</label>
            <select id="vmPreferredHs" name="preferred_senior_high_school" class="vs-input" required>
              <option value="">-- Select --</option>
              <?php foreach ($seniorHighSchools as $school): ?>
                <?php $schoolName = $school['school_name'] ?? '' ?>
                <option value="<?= esc($schoolName) ?>"><?= esc($schoolName) ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div>
            <label class="vs-label" for="vmRemarks">Remarks</label>
            <select id="vmRemarks" name="remarks_status" class="vs-input">
              <option value="">-- Select --</option>
              <option value="PASSED">Passed</option>
              <option value="FOR REVIEW">For Review</option>
              <option value="FAILED">Failed</option>
            </select>
          </div>

          <div>
            <label class="vs-label required" for="vmSchoolYear">School Year</label>
            <input id="vmSchoolYear" name="school_year" type="text" class="vs-input" placeholder="e.g. 2025-2026" required>
          </div>

          <div>
            <label class="vs-label" for="vmEligibility">Eligibility</label>
            <select id="vmEligibility" name="eligibility_status" class="vs-input">
              <option value="">-- Select --</option>
              <option value="eligible">Eligible</option>
              <option value="not_eligible">Not Eligible</option>
            </select>
          </div>
        </div>
      </div>

      <div class="vs-modal-footer">
        <button type="button" class="vs-btn vs-btn-outline" id="voucherModalCancel">Close</button>
        <button type="submit" class="vs-btn vs-btn-primary" id="voucherModalSubmit">
          <span id="vmSubmitText">Save Voucher</span>
          <span id="vmSubmitSpinner" class="vs-spinner" style="display:none"></span>
        </button>
      </div>
    </form>
  </div>
</div>

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
          <select id="filterSchoolYear" class="vs-input"><option value="">All</option></select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterGender">Gender</label>
          <select id="filterGender" class="vs-input">
            <option value="">All</option>
            <option value="MALE">Male</option>
            <option value="FEMALE">Female</option>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterRemarks">Remarks</label>
          <select id="filterRemarks" class="vs-input">
            <option value="">All</option>
            <option value="PASSED">Passed</option>
            <option value="FOR REVIEW">For Review</option>
            <option value="FAILED">Failed</option>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterVoucherStatus">Voucher Status</label>
          <select id="filterVoucherStatus" class="vs-input">
            <option value="">All</option>
            <option value="generated">Generated</option>
            <option value="not_generated">Pending</option>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterDateFrom">Voucher Date From</label>
          <input type="date" id="filterDateFrom" class="vs-input">
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterDateTo">Voucher Date To</label>
          <input type="date" id="filterDateTo" class="vs-input">
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterJuniorHs">Junior High School</label>
          <select id="filterJuniorHs" class="vs-input">
            <option value="">All</option>
            <?php foreach ($juniorHighSchools as $school): ?>
              <?php $schoolName = $school['school_name'] ?? '' ?>
              <option value="<?= esc($schoolName) ?>"><?= esc($schoolName) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterPreferredHs">Preferred Senior HS</label>
          <select id="filterPreferredHs" class="vs-input">
            <option value="">All</option>
            <?php foreach ($seniorHighSchools as $school): ?>
              <?php $schoolName = $school['school_name'] ?? '' ?>
              <option value="<?= esc($schoolName) ?>"><?= esc($schoolName) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterGwaMin">GWA Min</label>
          <input type="number" step="0.01" id="filterGwaMin" class="vs-input" placeholder="e.g. 80">
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterGwaMax">GWA Max</label>
          <input type="number" step="0.01" id="filterGwaMax" class="vs-input" placeholder="e.g. 100">
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
(function () {
  var csrfName = '<?= csrf_token() ?>';
  var csrfHash = '<?= csrf_hash() ?>';

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

  // ── Voucher Add / View / Edit modal ────────────────────────────────────────
  var voucherModal       = document.getElementById('voucherModal');
  var voucherModalForm   = document.getElementById('voucherModalForm');
  var voucherModalTitle  = document.getElementById('voucherModalTitle');
  var voucherModalClose  = document.getElementById('voucherModalClose');
  var voucherModalCancel = document.getElementById('voucherModalCancel');
  var voucherModalAlert  = document.getElementById('voucherModalAlert');
  var voucherSubmitBtn   = document.getElementById('voucherModalSubmit');
  var vmSubmitText       = document.getElementById('vmSubmitText');
  var vmSubmitSpinner    = document.getElementById('vmSubmitSpinner');
  var btnAddVoucher      = document.getElementById('btnAddVoucher');
  var saveStudentUrl     = '<?= site_url('students/save') ?>';
  var fetchStudentUrl    = '<?= site_url('students/json') ?>';

  // Field map: form input id → form name. Used for clear/populate cycles.
  var vmFieldIds = [
    'vmVoucherDate', 'vmFirstName', 'vmMiddleName', 'vmLastName',
    'vmSuffix', 'vmGender', 'vmGwa', 'vmRankNo', 'vmContactNumber',
    'vmJuniorHs', 'vmPreferredHs', 'vmRemarks', 'vmSchoolYear', 'vmEligibility',
  ];
  var vmFieldToName = {
    vmVoucherDate: 'voucher_date',     vmFirstName:   'first_name',
    vmMiddleName:  'middle_name',      vmLastName:    'last_name',
    vmSuffix:      'suffix',           vmGender:      'gender',
    vmGwa:         'gwa',              vmRankNo:      'rank_no',
    vmContactNumber: 'contact_number', vmJuniorHs:    'junior_high_school',
    vmPreferredHs: 'preferred_senior_high_school',
    vmRemarks:     'remarks_status',   vmSchoolYear:  'school_year',
    vmEligibility: 'eligibility_status',
  };

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (ch) {
      return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[ch];
    });
  }

  function vmShowAlert(msg, type, errors) {
    var html = '<div class="vs-alert vs-alert-' + (type || 'error') + ' mb-3">' + escapeHtml(msg);
    if (errors && Object.keys(errors).length) {
      html += '<ul class="mb-0 mt-2">';
      Object.keys(errors).forEach(function (field) {
        html += '<li><strong>' + escapeHtml(field) + ':</strong> ' + escapeHtml(errors[field]) + '</li>';
      });
      html += '</ul>';
    }
    html += '</div>';
    voucherModalAlert.innerHTML = html;
  }
  function vmClearAlert() { voucherModalAlert.innerHTML = ''; }

  function vmClearFields() {
    document.getElementById('vmStudentId').value = '';
    vmFieldIds.forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      if (el.tagName === 'SELECT') el.selectedIndex = 0;
      else el.value = '';
    });
    document.getElementById('vmEligibility').value = 'eligible';
  }

  function vmPopulateFields(student) {
    document.getElementById('vmStudentId').value = student.student_id || '';
    vmFieldIds.forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      var name = vmFieldToName[id];
      var val  = student[name];
      el.value = (val === null || val === undefined) ? '' : val;
    });
  }

  function vmSetReadOnly(readOnly) {
    vmFieldIds.forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      if (el.tagName === 'SELECT') {
        el.disabled = readOnly;
      } else {
        el.readOnly = readOnly;
      }
    });
    voucherSubmitBtn.style.display = readOnly ? 'none' : 'inline-flex';
  }

  function vmOpen(mode, studentId) {
    vmClearAlert();
    vmClearFields();

    if (mode === 'add') {
      voucherModalTitle.textContent = 'Add Voucher';
      vmSubmitText.textContent = 'Save Voucher';
      vmSetReadOnly(false);
      // Default voucher_date to today for convenience
      document.getElementById('vmVoucherDate').value = new Date().toISOString().slice(0, 10);
      voucherModal.style.display = 'flex';
      return;
    }

    voucherModalTitle.textContent = mode === 'edit' ? 'Edit Voucher' : 'View Voucher';
    vmSubmitText.textContent = 'Update Voucher';
    vmSetReadOnly(mode === 'view');
    voucherModal.style.display = 'flex';

    // Fetch the student data
    fetch(fetchStudentUrl + '/' + studentId, ajaxOptions({ method: 'GET' }))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.status !== 'success') {
          vmShowAlert(data.message || 'Failed to load student.', 'error');
          return;
        }
        vmPopulateFields(data.student);
        // After populate, re-apply readOnly state to anything new
        vmSetReadOnly(mode === 'view');
      })
      .catch(function () {
        vmShowAlert('Failed to load student.', 'error');
      });
  }

  function vmClose() { voucherModal.style.display = 'none'; }

  btnAddVoucher && btnAddVoucher.addEventListener('click', function () { vmOpen('add'); });
  voucherModalClose  && voucherModalClose.addEventListener('click', vmClose);
  voucherModalCancel && voucherModalCancel.addEventListener('click', vmClose);
  voucherModal && voucherModal.addEventListener('click', function (e) {
    if (e.target === voucherModal) vmClose();
  });

  document.querySelectorAll('.js-voucher-action').forEach(function (btn) {
    btn.addEventListener('click', function () {
      vmOpen(btn.getAttribute('data-mode'), btn.getAttribute('data-id'));
    });
  });

  voucherModalForm && voucherModalForm.addEventListener('submit', function (e) {
    e.preventDefault();
    vmClearAlert();

    var fd = new FormData(voucherModalForm);
    var csrf = getCsrfToken && getCsrfToken();
    if (csrf && csrf.name && !fd.get(csrf.name)) {
      fd.append(csrf.name, csrf.token);
    }

    voucherSubmitBtn.disabled = true;
    vmSubmitText.style.display = 'none';
    vmSubmitSpinner.style.display = 'inline-block';

    fetch(saveStudentUrl, ajaxOptions({ method: 'POST', body: fd }))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.status === 'success') {
          vmClose();
          // Refresh the listing so the row reflects the changes
          location.reload();
          return;
        }
        vmShowAlert(data.message || 'Save failed.', 'error', data.errors);
      })
      .catch(function () {
        vmShowAlert('An error occurred while saving.', 'error');
      })
      .finally(function () {
        voucherSubmitBtn.disabled = false;
        vmSubmitText.style.display = 'inline';
        vmSubmitSpinner.style.display = 'none';
      });
  });

  // ── Advanced Filters ───────────────────────────────────────────────────────
  // script.js initializes the DataTable on DOMContentLoaded. Our IIFE runs
  // earlier (at script-eval time), so defer until both DOM + DataTable exist.
  function initFilters() {
    var studentsTable = document.getElementById('studentsTable');
    if (!studentsTable || !window.jQuery || !$.fn.DataTable.isDataTable(studentsTable)) {
      // DataTable not ready yet — retry on next animation frame
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
  };

  var active = {}; // snapshot applied on "Apply"

  // Populate dropdowns from distinct values present in the table columns.
  // Column indices: 3 = Junior HS, 4 = Preferred SHS, 5 = School Year.
  function fillDropdown(colIdx, selectEl) {
    if (!selectEl) return;
    var set = new Set();
    dt.column(colIdx).data().each(function (val) {
      var t = (val || '').toString().trim();
      if (t && t !== '-') set.add(t);
    });
    Array.from(set).sort().forEach(function (v) {
      var opt = document.createElement('option');
      opt.value = v;
      opt.textContent = v;
      selectEl.appendChild(opt);
    });
  }
  fillDropdown(3, fields.juniorHs);
  fillDropdown(4, fields.preferredHs);
  fillDropdown(5, fields.schoolYear);

  // Hide DataTables' built-in search bar — we use a custom input above the table.
  var dtWrap = studentsTable.closest('.dataTables_wrapper');
  var dtSearch = dtWrap ? dtWrap.querySelector('.dataTables_filter') : null;
  if (dtSearch) dtSearch.style.display = 'none';

  // Hide DataTables' built-in length control — we use a custom input above the table.
  var dtLength = dtWrap ? dtWrap.querySelector('.dataTables_length') : null;
  if (dtLength) dtLength.style.display = 'none';

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

  // Wire the custom search input to the DataTable.
  var customSearch = document.getElementById('customStudentsSearch');
  if (customSearch) {
    customSearch.addEventListener('input', function () {
      dt.search(this.value).draw();
    });
  }

  var filterBtn         = document.getElementById('btnOpenFilter');
  var filterModal       = document.getElementById('filterModal');
  var filterModalClose  = document.getElementById('filterModalClose');
  var filterModalCancel = document.getElementById('filterModalCancel');
  var filterApply       = document.getElementById('filterApply');
  var filterClear       = document.getElementById('filterClear');
  var filterBadge       = function () { return document.getElementById('filterBadge'); };

  function openFilter()  { if (filterModal) filterModal.style.display = 'flex'; }
  function closeFilter() { if (filterModal) filterModal.style.display = 'none'; }

  filterBtn         && filterBtn.addEventListener('click', openFilter);
  filterModalClose  && filterModalClose.addEventListener('click', closeFilter);
  filterModalCancel && filterModalCancel.addEventListener('click', closeFilter);
  filterModal       && filterModal.addEventListener('click', function (e) {
    if (e.target === filterModal) closeFilter();
  });

  function activeFilterCount() {
    return Object.keys(active).filter(function (k) {
      var v = active[k];
      return v !== undefined && v !== null && String(v).trim() !== '';
    }).length;
  }

  function updateBadge() {
    var b = filterBadge();
    if (!b) return;
    var n = activeFilterCount();
    b.textContent = n;
    b.style.display = n > 0 ? 'inline-block' : 'none';
  }

  // Single global search hook — checks if this row belongs to #studentsTable,
  // then evaluates the snapshot of active filter values against row data-attrs.
  $.fn.dataTable.ext.search.push(function (settings, rowData, rowIdx, rowObj, counter) {
    if (settings.nTable.id !== 'studentsTable') return true;
    if (activeFilterCount() === 0) return true;

    var row = settings.aoData[rowIdx].nTr;
    if (!row) return true;

    // School Year — exact match against the column cell text (column 5)
    if (active.schoolYear) {
      var cellSY = (rowData[5] || '').toString().trim();
      if (cellSY !== active.schoolYear) return false;
    }

    if (active.gender) {
      var g = (row.getAttribute('data-gender') || '').toUpperCase();
      if (g !== active.gender.toUpperCase()) return false;
    }

    if (active.remarks) {
      var r = (row.getAttribute('data-remarks') || '').toUpperCase();
      if (r !== active.remarks.toUpperCase()) return false;
    }

    if (active.voucherStatus) {
      var vs = (row.getAttribute('data-voucher-status') || '');
      if (vs !== active.voucherStatus) return false;
    }

    if (active.dateFrom || active.dateTo) {
      var d = (row.getAttribute('data-voucher-date') || '');
      if (!d) return false;
      if (active.dateFrom && d < active.dateFrom) return false;
      if (active.dateTo   && d > active.dateTo)   return false;
    }

    if (active.juniorHs) {
      var jhs = (rowData[3] || '').toString().trim();
      if (jhs !== active.juniorHs) return false;
    }

    if (active.preferredHs) {
      var phs = (rowData[4] || '').toString().trim();
      if (phs !== active.preferredHs) return false;
    }

    if (active.gwaMin !== undefined && active.gwaMin !== '') {
      var gMinRaw = row.getAttribute('data-gwa');
      var gMin    = parseFloat(gMinRaw);
      if (isNaN(gMin) || gMin < parseFloat(active.gwaMin)) return false;
    }

    if (active.gwaMax !== undefined && active.gwaMax !== '') {
      var gMaxRaw = row.getAttribute('data-gwa');
      var gMax    = parseFloat(gMaxRaw);
      if (isNaN(gMax) || gMax > parseFloat(active.gwaMax)) return false;
    }

    return true;
  });

  filterApply && filterApply.addEventListener('click', function () {
    active = {
      schoolYear:    fields.schoolYear.value,
      gender:        fields.gender.value,
      remarks:       fields.remarks.value,
      voucherStatus: fields.voucherStatus.value,
      dateFrom:      fields.dateFrom.value,
      dateTo:        fields.dateTo.value,
      juniorHs:      fields.juniorHs.value,
      preferredHs:   fields.preferredHs.value,
      gwaMin:        fields.gwaMin.value,
      gwaMax:        fields.gwaMax.value,
    };
    updateBadge();
    dt.draw();
    closeFilter();
  });

  filterClear && filterClear.addEventListener('click', function () {
    Object.keys(fields).forEach(function (k) {
      if (fields[k]) fields[k].value = '';
    });
    active = {};
    updateBadge();
    dt.draw();
  });
  }
}());
</script>

<?= $this->endSection() ?>
