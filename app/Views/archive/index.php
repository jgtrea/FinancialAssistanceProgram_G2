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

<div class="vs-page-header mb-3">
        <div>
            <h4 class="vs-page-title">Archive</h4>
            <p class="vs-page-sub">View archived student records. Use <strong>Filters</strong> to narrow by school year.</p>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 mb-3">
        <form method="get" id="archiveFilterForm" style="flex:1;min-width:0;display:flex;align-items:center;gap:0.5rem">
            <input type="text" name="q" class="vs-input vs-advanced-search-input" placeholder="Enter keyword to search (name, school)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>" style="flex:1;min-width:0">
            <button type="button" class="vs-btn vs-btn-outline" id="btnOpenArchiveFilter" style="flex-shrink:0">
                Filters
                <span id="archiveFilterBadge" class="badge bg-primary" style="display:<?= $activeFilterCount > 0 ? 'inline-block' : 'none' ?>;margin-left:.35rem"><?= $activeFilterCount > 0 ? esc($activeFilterCount) : '' ?></span>
            </button>
            <?php foreach ($filterKeys as $k): ?>
              <input type="hidden" name="<?= esc($k, 'attr') ?>" value="<?= esc($f($k), 'attr') ?>">
            <?php endforeach ?>
            <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none;flex-shrink:0">|</span>
            <button type="submit" class="vs-btn vs-btn-primary" style="flex-shrink:0">Search</button>
            <a href="<?= site_url('admin/archive') ?>" class="vs-btn vs-btn-outline" style="flex-shrink:0">Clear</a>
        </form>
        <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none;flex-shrink:0">|</span>
        <div style="display:flex;gap:0.5rem;flex-shrink:0">
            <button type="button" class="vs-btn vs-btn-danger" id="btnArchiveCurrentData">
                Archive Current Data
            </button>
        </div>
    </div>

    <div class="vs-card">
        <div class="vs-card-body">
            <?php if (!$hasSchoolYear): ?>
                <div class="vs-alert vs-alert-info mb-0">
                    Open <strong>Filters</strong> and choose a <strong>School Year</strong> to load archived records.
                </div>
            <?php else: ?>
            <table id="archivedVouchersTable" class="vs-datatable js-data-table" data-page-search="customArchiveSearch"
                   data-search-placeholder="Search archived vouchers..."
                   data-order='[[3,"asc"]]'
                   data-col-defs='[{"orderData":[3],"targets":[0]},{"visible":false,"targets":[3]}]'
                   style="width:100%">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>School</th>
                        <th>Archived At</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vouchers as $voucher): ?>
                        <?php
                            $aLn = trim((string) ($voucher['last_name']   ?? ''));
                            $aFm = implode(' ', array_filter([trim((string) ($voucher['first_name'] ?? '')), trim((string) ($voucher['middle_name'] ?? ''))]));
                            $aDn = $aLn !== '' ? $aLn . ($aFm !== '' ? ', ' . $aFm : '') : $aFm;
                        ?>
                        <tr data-archived-date="<?= !empty($voucher['archived_at']) ? esc(date('Y-m-d', strtotime($voucher['archived_at']))) : '' ?>">
                            <td><?= esc($aDn) ?></td>
                            <td><?= esc($voucher['preferred_senior_high_school']) ?></td>
                            <td><?= !empty($voucher['archived_at']) ? esc(date('M d, Y h:i A', strtotime($voucher['archived_at']))) : '-' ?></td>
                            <td><?= esc($voucher['name_sort'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

<!-- Archive Current Data confirmation modal -->
<div class="vs-modal-overlay" id="archiveCurrentModal" style="display:none">
  <div class="vs-modal" style="max-width:500px">
    <div class="vs-modal-header">
      <h5 id="archiveCurrentModalTitle">Archive Current Data</h5>
      <button class="vs-modal-close" id="archiveCurrentModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <div id="archiveCurrentModalBody"></div>
      <div class="mt-3">
        <label class="vs-label" for="archiveCurrentReason">Reason (optional)</label>
        <input type="text" id="archiveCurrentReason" class="vs-input" placeholder="e.g. End of school year">
      </div>
    </div>
    <div class="vs-modal-footer">
      <button class="vs-btn vs-btn-outline" id="archiveCurrentModalCancel">Cancel</button>
      <button class="vs-btn vs-btn-danger" id="archiveCurrentModalConfirm">
        <span id="archiveCurrentBtnText">Confirm Archive</span>
        <span id="archiveCurrentBtnSpinner" class="vs-spinner" style="display:none"></span>
      </button>
    </div>
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
          <select id="afSchoolYear" class="vs-input js-filter-select" data-placeholder="— Select a school year —">
            <option></option>
            <?php foreach ($schoolYears as $sy): ?>
              <option value="<?= esc($sy) ?>" <?= $f('school_year') === $sy ? 'selected' : '' ?>><?= esc($sy) ?></option>
            <?php endforeach ?>
            <?php if ($f('school_year') !== '' && !in_array($f('school_year'), $schoolYears, true)): ?>
              <option value="<?= esc($f('school_year')) ?>" selected><?= esc($f('school_year')) ?></option>
            <?php endif ?>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afGender">Sex</label>
          <select id="afGender" class="vs-input js-filter-select" data-placeholder="All" data-no-search="1">
            <option></option>
            <option value="MALE"   <?= $f('gender') === 'MALE'   ? 'selected' : '' ?>>MALE</option>
            <option value="FEMALE" <?= $f('gender') === 'FEMALE' ? 'selected' : '' ?>>FEMALE</option>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afRemarks">Remarks</label>
          <select id="afRemarks" class="vs-input js-filter-select" data-placeholder="All" data-no-search="1">
            <option></option>
            <option value="PASSED"     <?= $f('remarks') === 'PASSED'     ? 'selected' : '' ?>>PASSED</option>
            <option value="FOR REVIEW" <?= $f('remarks') === 'FOR REVIEW' ? 'selected' : '' ?>>FOR REVIEW</option>
            <option value="FAILED"     <?= $f('remarks') === 'FAILED'     ? 'selected' : '' ?>>FAILED</option>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afVoucherStatus">Voucher Status</label>
          <select id="afVoucherStatus" class="vs-input js-filter-select" data-placeholder="All" data-no-search="1">
            <option></option>
            <option value="generated"     <?= $f('voucher_status') === 'generated'     ? 'selected' : '' ?>>generated</option>
            <option value="not_generated" <?= $f('voucher_status') === 'not_generated' ? 'selected' : '' ?>>not_generated</option>
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
          <select id="afJuniorHs" class="vs-input js-filter-select" data-placeholder="All">
            <option></option>
            <?php $jhsNames = array_map(static fn($s) => $s['school_name'] ?? '', $juniorHighSchools); ?>
            <?php foreach ($jhsNames as $schoolName): ?>
              <option value="<?= esc($schoolName) ?>" <?= $f('junior_hs') === $schoolName ? 'selected' : '' ?>><?= esc($schoolName) ?></option>
            <?php endforeach ?>
            <?php if ($f('junior_hs') !== '' && !in_array($f('junior_hs'), $jhsNames, true)): ?>
              <option value="<?= esc($f('junior_hs')) ?>" selected><?= esc($f('junior_hs')) ?></option>
            <?php endif ?>
          </select>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="afPreferredHs">Preferred Senior HS</label>
          <select id="afPreferredHs" class="vs-input js-filter-select" data-placeholder="All">
            <option></option>
            <?php $shsNames = array_map(static fn($s) => $s['school_name'] ?? '', $seniorHighSchools); ?>
            <?php foreach ($shsNames as $schoolName): ?>
              <option value="<?= esc($schoolName) ?>" <?= $f('preferred_hs') === $schoolName ? 'selected' : '' ?>><?= esc($schoolName) ?></option>
            <?php endforeach ?>
            <?php if ($f('preferred_hs') !== '' && !in_array($f('preferred_hs'), $shsNames, true)): ?>
              <option value="<?= esc($f('preferred_hs')) ?>" selected><?= esc($f('preferred_hs')) ?></option>
            <?php endif ?>
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

    function wireFilterModal() {
        var filterModal = document.getElementById('archiveFilterModal');
        var filterForm  = document.getElementById('archiveFilterForm');
        if (!filterModal || !filterForm) return;
        function openFilter()  {
          filterModal.style.display = 'flex';
          if (typeof window.initVsSelect2 === 'function') window.initVsSelect2(filterModal);
        }
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
                if (modalEl) {
                    modalEl.value = '';
                    if (window.jQuery && modalEl.classList.contains('js-filter-select')) {
                        $(modalEl).val('').trigger('change');
                    }
                }
                if (hidden) hidden.value = '';
            });
            closeFilter();
            if (filterForm) filterForm.submit();
        });
    }
    // ── Archive Current Data button ───────────────────────────────────────────
    var btnArchiveCurrent  = document.getElementById('btnArchiveCurrentData');
    var archCurrentModal   = document.getElementById('archiveCurrentModal');
    var archCurrentTitle   = document.getElementById('archiveCurrentModalTitle');
    var archCurrentBody    = document.getElementById('archiveCurrentModalBody');
    var archCurrentReason  = document.getElementById('archiveCurrentReason');
    var archCurrentClose   = document.getElementById('archiveCurrentModalClose');
    var archCurrentCancel  = document.getElementById('archiveCurrentModalCancel');
    var archCurrentConfirm = document.getElementById('archiveCurrentModalConfirm');
    var archCurrentBtnText = document.getElementById('archiveCurrentBtnText');
    var archCurrentSpinner = document.getElementById('archiveCurrentBtnSpinner');

    var previewUrl       = '<?= site_url(session()->get('role') === 'admin' ? 'admin' : 'user') ?>/vouchers/archive-preview';
    var archByFilterBase = '<?= site_url(session()->get('role') === 'admin' ? 'admin' : 'user') ?>/vouchers/archive-by-filter';
    var csrfName         = '<?= csrf_token() ?>';

    function getCsrfValue() {
        var meta = document.querySelector('meta[name="csrf-token-value"]');
        return meta ? meta.getAttribute('content') : '<?= csrf_hash() ?>';
    }

    function closeArchCurrentModal() {
        if (archCurrentModal)  archCurrentModal.style.display = 'none';
        if (archCurrentReason) archCurrentReason.value = '';
    }

    archCurrentClose  && archCurrentClose.addEventListener('click', closeArchCurrentModal);
    archCurrentCancel && archCurrentCancel.addEventListener('click', closeArchCurrentModal);
    archCurrentModal  && archCurrentModal.addEventListener('click', function (e) {
        if (e.target === archCurrentModal) closeArchCurrentModal();
    });

    btnArchiveCurrent && btnArchiveCurrent.addEventListener('click', function () {
        var form = document.getElementById('archiveFilterForm');
        var params = form ? new URLSearchParams(new FormData(form)).toString() : '';

        btnArchiveCurrent.disabled = true;

        fetch(previewUrl + '?' + params, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            btnArchiveCurrent.disabled = false;
            if (!data.success) {
                alert(data.message || 'Could not load preview.');
                return;
            }
            if (!data.count) {
                alert('No active students match the current filters — nothing to archive.');
                return;
            }

            var sys = data.schoolYears || [];
            if (sys.length <= 1) {
                var syLabel = sys.length === 1 ? ' from school year <strong>' + sys[0] + '</strong>' : '';
                archCurrentBody.innerHTML =
                    '<p>You are about to archive <strong>' + data.count + '</strong> student(s)' + syLabel + '. This will move them to the archive.</p>';
            } else {
                archCurrentBody.innerHTML =
                    '<div class="vs-alert vs-alert-warning mb-3" style="background:#fff8e1;border-left:4px solid #f6c633;padding:.75rem 1rem;border-radius:6px">' +
                    '<strong>Multiple school years detected:</strong> ' + sys.join(', ') + '.<br>' +
                    'All ' + data.count + ' matching students will be archived together. You may want to filter by a specific school year first.' +
                    '</div>' +
                    '<p>Do you want to proceed or cancel and filter first?</p>';
            }

            if (archCurrentModal) archCurrentModal.style.display = 'flex';
        })
        .catch(function () {
            btnArchiveCurrent.disabled = false;
            alert('An error occurred while loading preview.');
        });
    });

    archCurrentConfirm && archCurrentConfirm.addEventListener('click', function () {
        var form = document.getElementById('archiveFilterForm');
        var params = form ? new URLSearchParams(new FormData(form)).toString() : '';
        var reason = archCurrentReason ? archCurrentReason.value.trim() : '';

        archCurrentConfirm.disabled = true;
        if (archCurrentBtnText) archCurrentBtnText.style.display = 'none';
        if (archCurrentSpinner) archCurrentSpinner.style.display = 'inline-block';

        fetch(archByFilterBase + '?' + params, {
            method:  'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'same-origin',
            body: csrfName + '=' + encodeURIComponent(getCsrfValue())
                + (reason ? '&archive_reason=' + encodeURIComponent(reason) : ''),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            closeArchCurrentModal();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Archive failed.');
            }
        })
        .catch(function () { alert('An error occurred.'); })
        .finally(function () {
            archCurrentConfirm.disabled = false;
            if (archCurrentBtnText) archCurrentBtnText.style.display = 'inline';
            if (archCurrentSpinner) archCurrentSpinner.style.display = 'none';
        });
    });
}());
</script>

<?= $this->endSection() ?>
