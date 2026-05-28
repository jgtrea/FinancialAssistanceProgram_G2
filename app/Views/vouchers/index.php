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
      <button type="button" class="vs-btn vs-btn-danger" id="btnArchiveAll" title="Archive every student matching the current search and filters (full database, not just the loaded rows)">
        <?= asset_icon('archive') ?>
        Archive All
      </button>
      <!-- TEMP — testing helper for the Archive All flow. Remove after testing. -->
      <button type="button" class="vs-btn vs-btn-outline" id="btnRestoreAllArchive" title="[TEMP/TEST] Move every row from student_archive back into students" style="border-color:#a02622;color:#a02622">
        Restore All (test)
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
        Archive
      </button>
    </div>
  </div>

  <div id="studentsAlertBox"></div>

  <form method="get" id="vouchersFilterForm" class="vs-advanced-search vs-advanced-search-outside mb-3">
    <input type="text" name="q" class="vs-input vs-advanced-search-input" placeholder="Advanced search all students..." value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
    <button type="button" class="vs-btn vs-btn-outline" id="btnOpenFilter">
      Filters
      <span id="filterBadge" class="badge bg-primary" style="display:<?= $activeFilterCount > 0 ? 'inline-block' : 'none' ?>;margin-left:.35rem"><?= $activeFilterCount > 0 ? esc($activeFilterCount) : '' ?></span>
    </button>
    <?php foreach ($filterKeys as $k): ?>
      <input type="hidden" name="<?= esc($k, 'attr') ?>" value="<?= esc($f($k), 'attr') ?>">
    <?php endforeach ?>
  </form>

  <div class="vs-card">
    <div class="vs-card-body">
      <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <input type="text" id="customStudentsSearch" class="vs-input vs-page-search" placeholder="Search this page..." style="max-width:260px">
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
            <th>Eligibility</th>
            <th>Generate Count</th>
            <th>Last Generated</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vouchers as $v): ?>
          <?php $notEligible = ($v['eligibility_status'] ?? '') === 'not_eligible' ?>
          <?php $isArchived  = !empty($v['is_archived']) ?>
          <?php $cbDisabled  = $notEligible || $isArchived ?>
          <?php $cbTitle     = $isArchived
                                  ? 'Archived — unarchive to interact with this row'
                                  : ($notEligible ? 'Not eligible — cannot be selected' : '') ?>
          <tr id="row-<?= esc($v['student_id'], 'attr') ?>"
              data-gender="<?= esc((string) ($v['gender'] ?? ''), 'attr') ?>"
              data-remarks="<?= esc((string) ($v['remarks_status'] ?? ''), 'attr') ?>"
              data-voucher-date="<?= esc((string) ($v['voucher_date'] ?? ''), 'attr') ?>"
              data-voucher-status="<?= esc((string) ($v['voucher_status'] ?? ''), 'attr') ?>"
              data-eligibility="<?= esc((string) ($v['eligibility_status'] ?? ''), 'attr') ?>"
              data-archived="<?= $isArchived ? '1' : '0' ?>"
              data-gwa="<?= esc((string) ($v['gwa'] ?? ''), 'attr') ?>"<?= $isArchived ? ' class="vs-row-archived"' : '' ?>>
            <td><input type="checkbox" class="vs-check vs-row-check" value="<?= esc($v['student_id'], 'attr') ?>"<?= $cbDisabled ? ' disabled' : '' ?><?= $cbTitle !== '' ? ' title="' . esc($cbTitle, 'attr') . '"' : '' ?>></td>
            <td class="js-voucher-no"><?= esc($v['voucher_no'] ?: '-') ?></td>
            <td><?= esc($v['full_name']) ?></td>
            <td><?= esc($v['junior_high_school'] ?: '-') ?></td>
            <td><?= esc($v['preferred_senior_high_school']) ?></td>
            <td><?= esc($v['school_year']) ?></td>
            <td>
              <?php $elig = (string) ($v['eligibility_status'] ?? '') ?>
              <?php $eligLabel = $elig === 'eligible' ? 'Eligible' : ($elig === 'not_eligible' ? 'Not eligible' : '—') ?>
              <?php if ($elig === 'eligible' || $elig === 'not_eligible'): ?>
                <span class="vs-eligibility-icon vs-eligibility-icon-<?= esc($elig, 'attr') ?>" title="<?= esc($eligLabel, 'attr') ?>" aria-label="<?= esc($eligLabel, 'attr') ?>">
                  <?= asset_icon($elig === 'eligible' ? 'check' : 'cross') ?>
                </span>
              <?php else: ?>
                <span aria-label="Unknown">—</span>
              <?php endif ?>
            </td>
            <td>
              <span class="js-generate-count"><?= esc((string) ($v['generate_count'] ?? 0)) ?></span>
            </td>
            <td class="js-last-generated"><?= !empty($v['generated_at']) ? date('M d, Y', strtotime($v['generated_at'])) : '-' ?></td>
            <td>
              <div class="d-flex gap-1 js-actions-cell">
                <?php if ($isArchived): ?>
                  <button type="button" class="vs-tbl-btn vs-tbl-btn-view js-unarchive-one"
                          data-id="<?= esc($v['student_id'], 'attr') ?>"
                          data-name="<?= esc($v['full_name'], 'attr') ?>">Unarchive</button>
                <?php else: ?>
                  <button type="button" class="vs-tbl-btn vs-tbl-btn-view js-voucher-action" data-mode="view" data-id="<?= esc($v['student_id'], 'attr') ?>">View</button>
                  <button type="button" class="vs-tbl-btn vs-tbl-btn-edit js-voucher-action" data-mode="edit" data-id="<?= esc($v['student_id'], 'attr') ?>">Edit</button>
                  <button type="button" class="vs-tbl-btn vs-tbl-btn-delete js-archive-one"
                          data-id="<?= esc($v['student_id'], 'attr') ?>"
                          data-name="<?= esc($v['full_name'], 'attr') ?>">Archive</button>
                <?php endif ?>
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

<!-- Archive One modal — confirmation for the per-row Archive button. Independent
     of the bulk-selection flow so it works on any student, including ineligible
     ones whose row checkbox is disabled. -->
<div class="vs-modal-overlay" id="archiveOneModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5>Archive Student</h5>
      <button class="vs-modal-close" id="archiveOneModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <div id="archiveOneAlert"></div>
      <p>Archive <strong id="archiveOneName">—</strong>? This moves the record to the archive.</p>
      <label class="vs-label" for="archiveOneReason">Reason (optional)</label>
      <input type="text" id="archiveOneReason" class="vs-input" placeholder="e.g. End of school year">
    </div>
    <div class="vs-modal-footer">
      <button class="vs-btn vs-btn-outline" id="archiveOneModalCancel">Cancel</button>
      <button class="vs-btn vs-btn-danger" id="archiveOneConfirm">
        <span id="archiveOneBtnText">Confirm Archive</span>
        <span id="archiveOneBtnSpinner" class="vs-spinner" style="display:none"></span>
      </button>
    </div>
  </div>
</div>

<!-- Archive All modal — bulk-archives every student matching current search + filters across the full DB. -->
<div class="vs-modal-overlay" id="archiveAllModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5>Archive All Matching Students</h5>
      <button class="vs-modal-close" id="archiveAllModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <div id="archiveAllAlert"></div>
      <p>
        This will archive <strong id="archiveAllCount">—</strong> student(s) across the
        <strong>entire database</strong> matching the current search and filters
        (not just the rows shown on this page). Not-eligible students are included.
      </p>
      <p class="text-muted small mb-3">This action cannot be undone in bulk — restoration would have to be done individually from the Archive page.</p>
      <label class="vs-label" for="archiveAllReason">Reason (optional)</label>
      <input type="text" id="archiveAllReason" class="vs-input" placeholder="e.g. End of school year">
    </div>
    <div class="vs-modal-footer">
      <button class="vs-btn vs-btn-outline" id="archiveAllModalCancel">Cancel</button>
      <button class="vs-btn vs-btn-danger" id="archiveAllConfirm" disabled>
        <span id="archiveAllBtnText">Confirm Archive All</span>
        <span id="archiveAllBtnSpinner" class="vs-spinner" style="display:none"></span>
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
            <label class="vs-label required" for="vmJuniorHs">Junior High School</label>
            <select id="vmJuniorHs" name="junior_high_school" class="vs-input" required>
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
          <select id="filterSchoolYear" class="vs-input">
            <option value="">All</option>
            <?php foreach (($filterOptions['school_years'] ?? []) as $sy): ?>
              <option value="<?= esc($sy) ?>" <?= $f('school_year') === $sy ? 'selected' : '' ?>><?= esc($sy) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterGender">Gender</label>
          <select id="filterGender" class="vs-input">
            <option value="">All</option>
            <option value="MALE" <?= $f('gender') === 'MALE' ? 'selected' : '' ?>>Male</option>
            <option value="FEMALE" <?= $f('gender') === 'FEMALE' ? 'selected' : '' ?>>Female</option>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterRemarks">Remarks</label>
          <select id="filterRemarks" class="vs-input">
            <option value="">All</option>
            <option value="PASSED" <?= $f('remarks') === 'PASSED' ? 'selected' : '' ?>>Passed</option>
            <option value="FOR REVIEW" <?= $f('remarks') === 'FOR REVIEW' ? 'selected' : '' ?>>For Review</option>
            <option value="FAILED" <?= $f('remarks') === 'FAILED' ? 'selected' : '' ?>>Failed</option>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterVoucherStatus">Voucher Status</label>
          <select id="filterVoucherStatus" class="vs-input">
            <option value="">All</option>
            <option value="generated" <?= $f('voucher_status') === 'generated' ? 'selected' : '' ?>>Generated</option>
            <option value="not_generated" <?= $f('voucher_status') === 'not_generated' ? 'selected' : '' ?>>Pending</option>
          </select>
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
          <select id="filterJuniorHs" class="vs-input">
            <option value="">All</option>
            <?php foreach (($filterOptions['junior_high_schools'] ?? []) as $schoolName): ?>
              <option value="<?= esc($schoolName) ?>" <?= $f('junior_hs') === $schoolName ? 'selected' : '' ?>><?= esc($schoolName) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="filterPreferredHs">Preferred Senior HS</label>
          <select id="filterPreferredHs" class="vs-input">
            <option value="">All</option>
            <?php foreach (($filterOptions['senior_high_schools'] ?? []) as $schoolName): ?>
              <option value="<?= esc($schoolName) ?>" <?= $f('preferred_hs') === $schoolName ? 'selected' : '' ?>><?= esc($schoolName) ?></option>
            <?php endforeach ?>
          </select>
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
          <select id="filterEligibility" class="vs-input">
            <option value="">All</option>
            <option value="eligible"     <?= $f('eligibility') === 'eligible'     ? 'selected' : '' ?>>Eligible</option>
            <option value="not_eligible" <?= $f('eligibility') === 'not_eligible' ? 'selected' : '' ?>>Not Eligible</option>
          </select>
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
    if (btn.dataset.voucherActionBound === '1') return;
    btn.dataset.voucherActionBound = '1';
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

  // Wire the custom search input across ALL loaded rows (server caps the load
  // at ~1000 most-recent records — see VoucherModel::LISTING_DEFAULT_LIMIT).
  // The "advanced search" form above the table reloads the page against the
  // full DB.
  var customSearch = document.getElementById('customStudentsSearch');
  if (window.VS && window.VS.bindFullTableSearch) {
    window.VS.bindFullTableSearch(dt, customSearch);
  }

  var filterBtn         = document.getElementById('btnOpenFilter');
  var filterModal       = document.getElementById('filterModal');
  var filterModalClose  = document.getElementById('filterModalClose');
  var filterModalCancel = document.getElementById('filterModalCancel');
  var filterApply       = document.getElementById('filterApply');
  var filterClear       = document.getElementById('filterClear');

  function openFilter()  { if (filterModal) filterModal.style.display = 'flex'; }
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
      filterForm.submit();
    }
  });
  }

  // ── Archive One / Unarchive One (per-row buttons) ──────────────────────────
  // Both flip students.is_archived without copying to student_archive — that
  // hard-archive purge is reserved for the Archive All button. After success
  // we mutate the row in-place: swap the action buttons, toggle the
  // disabled/title state on the checkbox, and dim the row.
  var archiveOneModal      = document.getElementById('archiveOneModal');
  var archiveOneModalClose = document.getElementById('archiveOneModalClose');
  var archiveOneModalCancel= document.getElementById('archiveOneModalCancel');
  var archiveOneConfirm    = document.getElementById('archiveOneConfirm');
  var archiveOneName       = document.getElementById('archiveOneName');
  var archiveOneReason     = document.getElementById('archiveOneReason');
  var archiveOneAlert      = document.getElementById('archiveOneAlert');
  var archiveOneBtnText    = document.getElementById('archiveOneBtnText');
  var archiveOneBtnSpinner = document.getElementById('archiveOneBtnSpinner');
  var softArchiveUrlBase   = '<?= site_url($prefix . '/vouchers/soft-archive') ?>';
  var unarchiveUrlBase     = '<?= site_url($prefix . '/vouchers/unarchive') ?>';
  var pendingArchiveId     = null;

  function closeArchiveOneModal() {
    if (archiveOneModal) archiveOneModal.style.display = 'none';
    pendingArchiveId = null;
  }

  function setArchiveOneAlert(msg, type) {
    if (!archiveOneAlert) return;
    archiveOneAlert.innerHTML = msg
      ? '<div class="vs-alert vs-alert-' + (type || 'error') + ' mb-3">' + msg + '</div>'
      : '';
  }

  // Replace the actions cell + checkbox state for a given row to reflect the
  // new archived state. Called after a successful soft-archive or unarchive.
  function setRowArchivedState(rowEl, archived) {
    if (!rowEl) return;
    var id = (rowEl.id || '').replace(/^row-/, '');
    var nameAttr = '';
    var existingActionBtn = rowEl.querySelector('.js-archive-one, .js-unarchive-one');
    if (existingActionBtn) nameAttr = existingActionBtn.getAttribute('data-name') || '';
    var nameEsc = nameAttr.replace(/"/g, '&quot;');

    rowEl.setAttribute('data-archived', archived ? '1' : '0');
    rowEl.classList.toggle('vs-row-archived', archived);

    var cb = rowEl.querySelector('.vs-row-check');
    if (cb) {
      var notEligible = (rowEl.getAttribute('data-eligibility') || '') === 'not_eligible';
      cb.checked = false;
      cb.disabled = archived || notEligible;
      cb.title = archived
        ? 'Archived — unarchive to interact with this row'
        : (notEligible ? 'Not eligible — cannot be selected' : '');
    }

    var actionsCell = rowEl.querySelector('.js-actions-cell');
    if (actionsCell) {
      if (archived) {
        actionsCell.innerHTML =
          '<button type="button" class="vs-tbl-btn vs-tbl-btn-view js-unarchive-one"' +
          ' data-id="' + id + '" data-name="' + nameEsc + '">Unarchive</button>';
      } else {
        actionsCell.innerHTML =
          '<button type="button" class="vs-tbl-btn vs-tbl-btn-view js-voucher-action" data-mode="view" data-id="' + id + '">View</button>' +
          '<button type="button" class="vs-tbl-btn vs-tbl-btn-edit js-voucher-action" data-mode="edit" data-id="' + id + '">Edit</button>' +
          '<button type="button" class="vs-tbl-btn vs-tbl-btn-delete js-archive-one"' +
          ' data-id="' + id + '" data-name="' + nameEsc + '">Archive</button>';
      }
      // Re-wire the new buttons since the original event listeners attached
      // by querySelectorAll on page load don't reach replaced elements.
      wireRowActionButtons(actionsCell);
    }
  }

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

  function wireArchiveOneButton(btn) {
    btn.addEventListener('click', function () {
      pendingArchiveId = btn.getAttribute('data-id');
      if (!pendingArchiveId || !archiveOneModal) return;
      setArchiveOneAlert('');
      if (archiveOneReason) archiveOneReason.value = '';
      if (archiveOneName)   archiveOneName.textContent = btn.getAttribute('data-name') || ('student #' + pendingArchiveId);
      archiveOneConfirm.disabled = false;
      archiveOneModal.style.display = 'flex';
    });
  }

  function wireUnarchiveOneButton(btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-id');
      if (!id) return;
      btn.disabled = true;

      var fd = new FormData();
      fd.append(csrfName, csrfHash);

      fetch(unarchiveUrlBase + '/' + id, {
        method:  'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body:    fd,
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.success) {
            btn.disabled = false;
            alert(data.message || 'Unarchive failed.');
            return;
          }
          setRowArchivedState(document.getElementById('row-' + id), false);
          flashSuccess(data.message || 'Student unarchived.');
          if (data.csrf_token) csrfHash = data.csrf_token;
        })
        .catch(function () {
          btn.disabled = false;
          alert('An error occurred. Please try again.');
        });
    });
  }

  // Wires the action buttons inside a specific actions cell (or the whole
  // document if no element is passed). Used on initial load and after a
  // soft-archive / unarchive replaces a row's buttons.
  function wireRowActionButtons(scope) {
    var root = scope || document;
    root.querySelectorAll('.js-archive-one').forEach(wireArchiveOneButton);
    root.querySelectorAll('.js-unarchive-one').forEach(wireUnarchiveOneButton);
    // Re-wire View/Edit so newly-inserted buttons open the modal.
    root.querySelectorAll('.js-voucher-action').forEach(function (b) {
      if (b.dataset.voucherActionBound === '1') return;
      b.dataset.voucherActionBound = '1';
      b.addEventListener('click', function () {
        vmOpen(b.getAttribute('data-mode'), b.getAttribute('data-id'));
      });
    });
  }
  wireRowActionButtons();

  archiveOneModalClose  && archiveOneModalClose.addEventListener('click', closeArchiveOneModal);
  archiveOneModalCancel && archiveOneModalCancel.addEventListener('click', closeArchiveOneModal);
  archiveOneModal       && archiveOneModal.addEventListener('click', function (e) {
    if (e.target === archiveOneModal) closeArchiveOneModal();
  });

  archiveOneConfirm && archiveOneConfirm.addEventListener('click', function () {
    if (!pendingArchiveId) return;
    setArchiveOneAlert('');
    archiveOneConfirm.disabled = true;
    if (archiveOneBtnText)    archiveOneBtnText.style.display    = 'none';
    if (archiveOneBtnSpinner) archiveOneBtnSpinner.style.display = 'inline-block';

    var fd = new FormData();
    fd.append(csrfName, csrfHash);

    var idForRequest = pendingArchiveId;
    fetch(softArchiveUrlBase + '/' + idForRequest, {
      method:  'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body:    fd,
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        archiveOneConfirm.disabled = false;
        if (archiveOneBtnText)    archiveOneBtnText.style.display    = 'inline';
        if (archiveOneBtnSpinner) archiveOneBtnSpinner.style.display = 'none';
        if (!data.success) {
          setArchiveOneAlert(data.message || 'Archive failed.', 'error');
          return;
        }
        setRowArchivedState(document.getElementById('row-' + idForRequest), true);
        closeArchiveOneModal();
        flashSuccess(data.message || 'Student archived.');
        if (data.csrf_token) csrfHash = data.csrf_token;
      })
      .catch(function () {
        setArchiveOneAlert('An error occurred. Please try again.', 'error');
        archiveOneConfirm.disabled = false;
        if (archiveOneBtnText)    archiveOneBtnText.style.display    = 'inline';
        if (archiveOneBtnSpinner) archiveOneBtnSpinner.style.display = 'none';
      });
  });

  // ── Archive All ─────────────────────────────────────────────────────────────
  // Opens a confirmation modal that first AJAX-fetches the count of students
  // matching the current search + filter scope (from the URL query string), so
  // the user knows exactly how many rows are about to be archived across the
  // whole DB — not just the loaded 1000.
  var btnArchiveAll        = document.getElementById('btnArchiveAll');
  var archiveAllModal      = document.getElementById('archiveAllModal');
  var archiveAllModalClose = document.getElementById('archiveAllModalClose');
  var archiveAllModalCancel= document.getElementById('archiveAllModalCancel');
  var archiveAllConfirm    = document.getElementById('archiveAllConfirm');
  var archiveAllCount      = document.getElementById('archiveAllCount');
  var archiveAllReason     = document.getElementById('archiveAllReason');
  var archiveAllAlert      = document.getElementById('archiveAllAlert');
  var archiveAllBtnText    = document.getElementById('archiveAllBtnText');
  var archiveAllBtnSpinner = document.getElementById('archiveAllBtnSpinner');
  var countMatchingUrl     = '<?= site_url($prefix . '/vouchers/count-matching') ?>';
  var archiveAllUrl        = '<?= site_url($prefix . '/vouchers/archive-all') ?>';

  // TEMP — handler for the "Restore All (test)" button. Remove with the
  // button + route + controller method when Archive All is done being tested.
  var btnRestoreAllArchive = document.getElementById('btnRestoreAllArchive');
  var restoreAllArchiveUrl = '<?= site_url($prefix . '/vouchers/restore-all-archive') ?>';
  btnRestoreAllArchive && btnRestoreAllArchive.addEventListener('click', function () {
    if (!window.confirm('[TEST] Move every row from student_archive back into students?\n\nThis is a destructive testing shortcut — only use it while testing Archive All.')) {
      return;
    }
    btnRestoreAllArchive.disabled = true;
    var fd = new FormData();
    fd.append(csrfName, csrfHash);
    fetch(restoreAllArchiveUrl, {
      method:  'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body:    fd,
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        alert((data && data.message) || (data && data.success ? 'Restored.' : 'Restore failed.'));
        if (data && data.success) window.location.reload();
        else btnRestoreAllArchive.disabled = false;
      })
      .catch(function () {
        alert('Restore request failed.');
        btnRestoreAllArchive.disabled = false;
      });
  });

  function closeArchiveAllModal() {
    if (archiveAllModal) archiveAllModal.style.display = 'none';
  }

  function setArchiveAllAlert(msg, type) {
    if (!archiveAllAlert) return;
    archiveAllAlert.innerHTML = msg
      ? '<div class="vs-alert vs-alert-' + (type || 'error') + ' mb-3">' + msg + '</div>'
      : '';
  }

  function currentListingQuery() {
    // Use the current URL's search params so the count + archive operate on
    // exactly the same scope the listing was rendered with (keyword + every
    // hidden filter input that the filter form submitted on its last apply).
    return new URLSearchParams(window.location.search);
  }

  btnArchiveAll && btnArchiveAll.addEventListener('click', function () {
    if (!archiveAllModal) return;
    setArchiveAllAlert('');
    if (archiveAllReason) archiveAllReason.value = '';
    if (archiveAllCount)  archiveAllCount.textContent = '…';
    if (archiveAllConfirm) archiveAllConfirm.disabled = true;
    archiveAllModal.style.display = 'flex';

    var qs = currentListingQuery().toString();
    fetch(countMatchingUrl + (qs ? '?' + qs : ''), {
      method: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) {
          setArchiveAllAlert(data.message || 'Failed to count matching records.', 'error');
          return;
        }
        var n = parseInt(data.count, 10) || 0;
        if (archiveAllCount) archiveAllCount.textContent = n.toLocaleString();
        if (archiveAllConfirm) archiveAllConfirm.disabled = n === 0;
        if (n === 0) {
          setArchiveAllAlert('No students match the current search/filter — nothing to archive.', 'info');
        }
      })
      .catch(function () {
        setArchiveAllAlert('Failed to count matching records.', 'error');
      });
  });

  archiveAllModalClose  && archiveAllModalClose.addEventListener('click', closeArchiveAllModal);
  archiveAllModalCancel && archiveAllModalCancel.addEventListener('click', closeArchiveAllModal);
  archiveAllModal       && archiveAllModal.addEventListener('click', function (e) {
    if (e.target === archiveAllModal) closeArchiveAllModal();
  });

  archiveAllConfirm && archiveAllConfirm.addEventListener('click', function () {
    if (archiveAllConfirm.disabled) return;
    setArchiveAllAlert('');
    archiveAllConfirm.disabled = true;
    if (archiveAllBtnText)    archiveAllBtnText.style.display    = 'none';
    if (archiveAllBtnSpinner) archiveAllBtnSpinner.style.display = 'inline-block';

    var fd = new FormData();
    fd.append(csrfName, csrfHash);
    fd.append('archive_reason', archiveAllReason ? archiveAllReason.value : '');
    // Mirror the URL's q + filter params into the POST body so the server
    // archives exactly the same scope the user is viewing.
    currentListingQuery().forEach(function (value, key) {
      fd.append(key, value);
    });

    fetch(archiveAllUrl, {
      method:  'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body:    fd,
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) {
          setArchiveAllAlert(data.message || 'Archive All failed.', 'error');
          archiveAllConfirm.disabled = false;
          if (archiveAllBtnText)    archiveAllBtnText.style.display    = 'inline';
          if (archiveAllBtnSpinner) archiveAllBtnSpinner.style.display = 'none';
          return;
        }
        // Refresh the page to reflect the archived rows leaving the listing.
        // Preserve the user's current search/filter scope in the URL.
        window.location.reload();
      })
      .catch(function () {
        setArchiveAllAlert('An error occurred. Please try again.', 'error');
        archiveAllConfirm.disabled = false;
        if (archiveAllBtnText)    archiveAllBtnText.style.display    = 'inline';
        if (archiveAllBtnSpinner) archiveAllBtnSpinner.style.display = 'none';
      });
  });
}());
</script>

<?= $this->endSection() ?>
