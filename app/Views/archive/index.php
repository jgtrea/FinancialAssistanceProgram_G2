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
    $hasResults        = $hasSchoolYear && !empty($vouchers);
?>

<div class="vs-page-header mb-3">
        <div>
            <h4 class="vs-page-title">Archive</h4>
            <p class="vs-page-sub">View Archived Student Records. Use <strong>Filters</strong> To Narrow By SY.</p>
        </div>
    </div>

    <form method="get" id="archiveFilterForm" class="row g-2 align-items-center mb-3">
        <?php foreach ($filterKeys as $k): ?>
          <input type="hidden" name="<?= esc($k, 'attr') ?>" value="<?= esc($f($k), 'attr') ?>">
        <?php endforeach ?>
        <div class="col-12 col-md-5">
            <input type="text" name="q" class="vs-input vs-advanced-search-input w-100" placeholder="Enter keyword to search (name, school)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
        </div>
        <div class="col-6 col-md-2">
            <button type="button" class="vs-btn vs-btn-outline w-100" id="btnOpenArchiveFilter">
                Filters
                <span id="archiveFilterBadge" class="badge bg-primary" style="display:<?= $activeFilterCount > 0 ? 'inline-block' : 'none' ?>;margin-left:.35rem"><?= $activeFilterCount > 0 ? esc($activeFilterCount) : '' ?></span>
            </button>
        </div>
        <div class="col-auto d-none d-md-flex align-items-center">
            <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
        </div>
        <div class="col-6 col-md-2 d-flex gap-2">
            <button type="submit" class="vs-btn vs-btn-primary flex-fill">Search</button>
            <a href="<?= site_url('admin/archive') ?>" class="vs-btn vs-btn-danger flex-fill">Clear</a>
        </div>
        <div class="col-auto d-none d-md-flex align-items-center">
            <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
        </div>
        <div class="col-12 col-md-2">
            <button type="button" class="vs-btn vs-btn-danger w-100" id="btnArchiveCurrentData">
                Archive Current Data
            </button>
        </div>
    </form>

    <div class="vs-card">
        <div class="vs-card-body">
            <?php if (!$hasResults): ?>
                <div class="vs-alert vs-alert-info vs-alert-static vs-archive-empty-message mb-0">
                    <?php if (!$hasSchoolYear): ?>
                        Open <strong>Filters</strong> and choose a <strong>SY</strong> to load archived records.
                    <?php else: ?>
                        No archived records found for <strong><?= esc($f('school_year')) ?></strong>. Try a different SY or adjust your filters.
                    <?php endif ?>
                </div>
            <?php else: ?>
            <table id="archivedVouchersTable" class="vs-datatable js-data-table vs-mobile-primary" data-mobile-primary="1" data-page-search="customArchiveSearch"
                   data-search-placeholder="Search archived vouchers..."
                   data-order='[[2,"asc"]]'
                   data-col-defs='[{"orderData":[2],"targets":[1]},{"visible":false,"targets":2}]'
                   style="width:100%">
                <thead>
                    <tr>
                        <th>Voucher No.</th>
                        <th>Name</th>
                        <th style="display:none">Name Sort</th>
                        <th>Junior High School</th>
                        <th>Preferred School</th>
                        <th>SY</th>
                        <th>Remarks</th>
                        <th>Printed</th>
                        <th>Last Generated</th>
                        <th>Archived At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vouchers as $voucher): ?>
                        <?php
                            $aLn = trim((string) ($voucher['last_name']   ?? ''));
                            $aFm = implode(' ', array_filter([trim((string) ($voucher['first_name'] ?? '')), trim((string) ($voucher['middle_name'] ?? ''))]));
                            $aDn = $aLn !== '' ? $aLn . ($aFm !== '' ? ', ' . $aFm : '') : $aFm;
                            $aSort = trim($aLn . ' ' . $aFm);
                        ?>
                        <tr data-archived-date="<?= !empty($voucher['archived_at']) ? esc(date('Y-m-d', strtotime($voucher['archived_at']))) : '' ?>">
                            <td><?= esc($voucher['voucher_no'] ?: '-') ?></td>
                            <td><?= esc($aDn) ?></td>
                            <td style="display:none"><?= esc($aSort) ?></td>
                            <td><?= esc($voucher['junior_high_school'] ?? '') ?></td>
                            <td><?= esc($voucher['preferred_senior_high_school'] ?? '') ?></td>
                            <td><?= esc($voucher['school_year'] ?? '') ?></td>
                            <td><?= esc($voucher['remarks_status'] ?: '-') ?></td>
                            <td><?= esc((string) ($voucher['generate_count'] ?? 0)) ?></td>
                            <td><?= !empty($voucher['generated_at']) ? esc(date('M d, Y', strtotime($voucher['generated_at']))) : '-' ?></td>
                            <td><?= !empty($voucher['archived_at']) ? esc(date('M d, Y h:i A', strtotime($voucher['archived_at']))) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

<?= pre_modal('archive') ?>
<script>
window.__VS.pageData = {
    schoolYears:       <?= json_encode($schoolYears) ?>,
    juniorHighSchools: <?= json_encode($juniorHighSchools) ?>,
    seniorHighSchools: <?= json_encode($seniorHighSchools) ?>,
};
</script>

<script>
document.addEventListener('vs:modals:ready', function () {
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
            window.location.href = '<?= site_url('archive?type=voucher') ?>';
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
            if (!data.success) {
                alert(data.message || 'Archive failed.');
                return;
            }
            // Archiving runs on the background worker now — show a live progress
            // toast (like generate) and reload when it finishes so the listing
            // reflects the moved rows.
            if (data.queued && data.status_url && typeof trackArchiveJob === 'function') {
                trackArchiveJob(data.status_url, data.count || 0, {
                    onDone:  function () { location.reload(); },
                    onError: function (msg) { alert('Archive failed: ' + msg); },
                });
                return;
            }
            location.reload();
        })
        .catch(function () { alert('An error occurred.'); })
        .finally(function () {
            archCurrentConfirm.disabled = false;
            if (archCurrentBtnText) archCurrentBtnText.style.display = 'inline';
            if (archCurrentSpinner) archCurrentSpinner.style.display = 'none';
        });
    });
});
</script>

<?= $this->endSection() ?>
