<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $filters           = $filters ?? [];
    $juniorHighSchools = $juniorHighSchools ?? [];
    $seniorHighSchools = $seniorHighSchools ?? [];
    $schoolYears       = $schoolYears ?? [];
    $filterKeys        = ['school_year','gender','remarks','voucher_status','date_from','date_to','junior_hs','preferred_hs','gwa_min','gwa_max'];
    $f                 = static fn (string $k) => (string) ($filters[$k] ?? '');
    $activeFilterCount = count(array_filter($filterKeys, fn ($k) => $f($k) !== ''));
    $hasSchoolYear     = $f('school_year') !== '';
?>

<div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">Archive</h4>
            <p class="vs-page-sub">Pick a school year from <strong>Filters</strong> to load archived records.</p>
        </div>
    </div>

    <form method="get" id="archiveFilterForm" class="vs-advanced-search vs-advanced-search-outside mb-3">
        <input type="text" name="q" class="vs-input vs-advanced-search-input" placeholder="Advanced search all archived vouchers..." value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
        <button type="button" class="vs-btn vs-btn-outline" id="btnOpenArchiveFilter">
            Filters
            <span id="archiveFilterBadge" class="badge bg-primary" style="display:<?= $activeFilterCount > 0 ? 'inline-block' : 'none' ?>;margin-left:.35rem"><?= $activeFilterCount > 0 ? esc($activeFilterCount) : '' ?></span>
        </button>
        <?php foreach ($filterKeys as $k): ?>
          <input type="hidden" name="<?= esc($k, 'attr') ?>" value="<?= esc($f($k), 'attr') ?>">
        <?php endforeach ?>
    </form>

    <div class="vs-card">
        <div class="vs-card-body">
            <?php if (!$hasSchoolYear): ?>
                <div class="vs-alert vs-alert-info mb-0">
                    Open <strong>Filters</strong> and choose a <strong>School Year</strong> to load archived records.
                </div>
            <?php else: ?>
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <input type="text" id="customArchiveSearch" class="vs-input vs-page-search" placeholder="Search this page..." style="max-width:260px">
                <label class="vs-length-label ms-auto">Show <input type="number" id="archiveLengthInput" class="vs-length-input" value="10" min="1" max="500"> entries</label>
            </div>
            <table id="archivedVouchersTable" class="vs-datatable js-data-table" data-search-placeholder="Search archived vouchers..." style="width:100%">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>School</th>
                        <th>Archived At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vouchers as $voucher): ?>
                        <tr data-archived-date="<?= !empty($voucher['archived_at']) ? esc(date('Y-m-d', strtotime($voucher['archived_at']))) : '' ?>">
                            <td><?= esc($voucher['full_name']) ?></td>
                            <td><?= esc($voucher['preferred_senior_high_school']) ?></td>
                            <td><?= !empty($voucher['archived_at']) ? esc(date('M d, Y h:i A', strtotime($voucher['archived_at']))) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

<div class="vs-modal-overlay" id="archiveFilterModal" style="display:none">
  <div class="vs-modal" style="max-width:680px">
    <div class="vs-modal-header">
      <h5>Advanced Filters</h5>
      <button class="vs-modal-close" id="archiveFilterClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <div class="vs-form-grid vs-form-grid-4">
        <div class="vs-span-2">
          <label class="vs-label required" for="afSchoolYear">School Year</label>
          <select id="afSchoolYear" class="vs-input">
            <option value="">— Select a school year —</option>
            <?php foreach ($schoolYears as $sy): ?>
              <option value="<?= esc($sy) ?>" <?= $f('school_year') === $sy ? 'selected' : '' ?>><?= esc($sy) ?></option>
            <?php endforeach ?>
            <?php if ($f('school_year') !== '' && !in_array($f('school_year'), $schoolYears, true)): ?>
              <option value="<?= esc($f('school_year')) ?>" selected><?= esc($f('school_year')) ?></option>
            <?php endif ?>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afGender">Gender</label>
          <select id="afGender" class="vs-input">
            <option value="">All</option>
            <option value="MALE" <?= $f('gender') === 'MALE' ? 'selected' : '' ?>>Male</option>
            <option value="FEMALE" <?= $f('gender') === 'FEMALE' ? 'selected' : '' ?>>Female</option>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afRemarks">Remarks</label>
          <select id="afRemarks" class="vs-input">
            <option value="">All</option>
            <option value="PASSED" <?= $f('remarks') === 'PASSED' ? 'selected' : '' ?>>Passed</option>
            <option value="FOR REVIEW" <?= $f('remarks') === 'FOR REVIEW' ? 'selected' : '' ?>>For Review</option>
            <option value="FAILED" <?= $f('remarks') === 'FAILED' ? 'selected' : '' ?>>Failed</option>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afVoucherStatus">Voucher Status</label>
          <select id="afVoucherStatus" class="vs-input">
            <option value="">All</option>
            <option value="generated" <?= $f('voucher_status') === 'generated' ? 'selected' : '' ?>>Generated</option>
            <option value="not_generated" <?= $f('voucher_status') === 'not_generated' ? 'selected' : '' ?>>Pending</option>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afDateFrom">Voucher Date From</label>
          <input type="date" id="afDateFrom" class="vs-input" value="<?= esc($f('date_from'), 'attr') ?>">
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afDateTo">Voucher Date To</label>
          <input type="date" id="afDateTo" class="vs-input" value="<?= esc($f('date_to'), 'attr') ?>">
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afJuniorHs">Junior High School</label>
          <select id="afJuniorHs" class="vs-input">
            <option value="">All</option>
            <?php foreach ($juniorHighSchools as $school): ?>
              <?php $schoolName = $school['school_name'] ?? '' ?>
              <option value="<?= esc($schoolName) ?>" <?= $f('junior_hs') === $schoolName ? 'selected' : '' ?>><?= esc($schoolName) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afPreferredHs">Preferred Senior HS</label>
          <select id="afPreferredHs" class="vs-input">
            <option value="">All</option>
            <?php foreach ($seniorHighSchools as $school): ?>
              <?php $schoolName = $school['school_name'] ?? '' ?>
              <option value="<?= esc($schoolName) ?>" <?= $f('preferred_hs') === $schoolName ? 'selected' : '' ?>><?= esc($schoolName) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afGwaMin">GWA Min</label>
          <input type="number" step="0.01" id="afGwaMin" class="vs-input" placeholder="e.g. 80" value="<?= esc($f('gwa_min'), 'attr') ?>">
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afGwaMax">GWA Max</label>
          <input type="number" step="0.01" id="afGwaMax" class="vs-input" placeholder="e.g. 100" value="<?= esc($f('gwa_max'), 'attr') ?>">
        </div>
      </div>
    </div>
    <div class="vs-modal-footer">
      <button type="button" class="vs-btn vs-btn-outline" id="archiveFilterClear">Clear All</button>
      <button type="button" class="vs-btn vs-btn-outline" id="archiveFilterCancel">Cancel</button>
      <button type="button" class="vs-btn vs-btn-primary" id="archiveFilterApply">Apply Filters</button>
    </div>
  </div>
</div>

<script>
(function () {
    // Filter modal wiring runs unconditionally — the page may be in its empty
    // state (no school year selected) where the table isn't rendered at all,
    // but the user still needs to open Filters to pick a year.
    wireFilterModal();

    // DataTable-dependent wiring runs only when the table exists, and only
    // once both jQuery and the DataTables plugin are loaded AND script.js has
    // initialized this table.
    var table = document.getElementById('archivedVouchersTable');
    if (table) {
        initArchiveDataTable(table);
    }

    function initArchiveDataTable(table) {
        if (!window.jQuery
            || !$.fn.DataTable
            || !$.fn.DataTable.isDataTable(table)
        ) {
            return setTimeout(function () { initArchiveDataTable(table); }, 50);
        }
        var dt = $(table).DataTable();
        var dtWrap = table.closest('.dataTables_wrapper');

        var dtSearch = dtWrap ? dtWrap.querySelector('.dataTables_filter') : null;
        if (dtSearch) dtSearch.style.display = 'none';

        var dtLength = dtWrap ? dtWrap.querySelector('.dataTables_length') : null;
        if (dtLength) dtLength.style.display = 'none';

        var lenInput = document.getElementById('archiveLengthInput');
        if (lenInput) {
            var applyArchiveLen = function () {
                var v = parseInt(lenInput.value, 10);
                if (!isNaN(v) && v > 0) dt.page.len(v).draw();
            };
            lenInput.addEventListener('change', applyArchiveLen);
            lenInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') applyArchiveLen(); });
        }

        // In-table search filters across ALL loaded rows (server caps the load
        // at ~1000 — see ArchiveModel::LISTING_DEFAULT_LIMIT). The
        // advanced-search + filters above reload the page against the full DB.
        var searchInput = document.getElementById('customArchiveSearch');
        if (window.VS && window.VS.bindFullTableSearch) {
            window.VS.bindFullTableSearch(dt, searchInput);
        }
    }

    function wireFilterModal() {
        var filterModal = document.getElementById('archiveFilterModal');
        var filterForm  = document.getElementById('archiveFilterForm');
        if (!filterModal || !filterForm) return;
        function openFilter()  { filterModal.style.display = 'flex'; }
        function closeFilter() { filterModal.style.display = 'none'; }

        var btnOpen   = document.getElementById('btnOpenArchiveFilter');
        var btnClose  = document.getElementById('archiveFilterClose');
        var btnCancel = document.getElementById('archiveFilterCancel');
        var btnClear  = document.getElementById('archiveFilterClear');
        var btnApply  = document.getElementById('archiveFilterApply');

        btnOpen   && btnOpen.addEventListener('click', openFilter);
        btnClose  && btnClose.addEventListener('click', closeFilter);
        btnCancel && btnCancel.addEventListener('click', closeFilter);
        filterModal.addEventListener('click', function (e) {
            if (e.target === filterModal) closeFilter();
        });

        var modalFieldToParam = {
            afSchoolYear:    'school_year',
            afGender:        'gender',
            afRemarks:       'remarks',
            afVoucherStatus: 'voucher_status',
            afDateFrom:      'date_from',
            afDateTo:        'date_to',
            afJuniorHs:      'junior_hs',
            afPreferredHs:   'preferred_hs',
            afGwaMin:        'gwa_min',
            afGwaMax:        'gwa_max',
        };

        function syncFormFromModal() {
            Object.keys(modalFieldToParam).forEach(function (id) {
                var modalEl = document.getElementById(id);
                var hidden  = filterForm.elements[modalFieldToParam[id]];
                if (modalEl && hidden) hidden.value = modalEl.value;
            });
        }

        btnApply && btnApply.addEventListener('click', function () {
            syncFormFromModal();
            filterForm.submit();
        });

        btnClear && btnClear.addEventListener('click', function () {
            Object.keys(modalFieldToParam).forEach(function (id) {
                var modalEl = document.getElementById(id);
                var hidden  = filterForm.elements[modalFieldToParam[id]];
                if (modalEl) modalEl.value = '';
                if (hidden) hidden.value = '';
            });
            filterForm.submit();
        });
    }
}());
</script>

<?= $this->endSection() ?>
