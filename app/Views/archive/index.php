<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $filters           = $filters ?? [];
    $juniorHighSchools = $juniorHighSchools ?? [];
    $seniorHighSchools = $seniorHighSchools ?? [];
    $schoolYears       = $schoolYears ?? [];
    $filterKeys        = ['school_year','gender','remarks','other_remarks','voucher_status','date_from','date_to','junior_hs','preferred_hs','gwa_min','gwa_max'];
    $f                 = static fn (string $k) => (string) ($filters[$k] ?? '');
    // school_year is the base scope (auto-defaulted to current SY by the
    // controller), not a user-chosen filter — exclude it from the badge count.
    $badgeKeys         = array_values(array_filter($filterKeys, fn ($k) => $k !== 'school_year'));
    $activeFilterCount = count(array_filter($badgeKeys, fn ($k) => $f($k) !== ''));
    $hasSchoolYear     = $f('school_year') !== '';

    // Params the server-side DataTable forwards on every ajax draw so the slice
    // stays scoped to the current SY / filters / keyword.
    $dtFilterParams    = $filters;
    $dtFilterParams['q'] = (string) ($keyword ?? '');
    $dtUrl             = site_url((session()->get('role') === 'admin' ? 'admin/' : '') . 'archive/datatable');
    $dtOptionsUrl      = site_url((session()->get('role') === 'admin' ? 'admin/' : '') . 'archive/filter-options');
?>

<div class="vs-page-header mb-3">
        <div>
            <h4 class="vs-page-title">Archive</h4>
            <p class="vs-page-sub">View Archived Student Records. Use <strong>Filters</strong> To Narrow By School Year.</p>
        </div>
    </div>

    <form method="get" id="archiveFilterForm" class="row g-2 align-items-center mb-3">
        <?php foreach ($filterKeys as $k): ?>
          <input type="hidden" name="<?= esc($k, 'attr') ?>" value="<?= esc($f($k), 'attr') ?>">
        <?php endforeach ?>
        <div class="col-12 col-md">
            <input type="text" name="q" class="vs-input vs-advanced-search-input w-100" placeholder="Enter keyword to search (name, school)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
        </div>
        <div class="col-6 col-md-auto">
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
        <div class="col-12 col-md-auto d-flex align-items-center gap-2">
            <span class="d-none d-md-inline-flex align-items-center" style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
            <button type="button" class="vs-btn vs-btn-warning" id="btnArchiveCurrentData">
                Archive Current Data
            </button>
        </div>
    </form>

    <div class="vs-card">
        <div class="vs-card-body">
            <?php if (!$hasSchoolYear): ?>
                <div class="vs-alert vs-alert-info vs-alert-static vs-archive-empty-message mb-0">
                    Open <strong>Filters</strong> and choose a <strong>SY</strong> to load archived records.
                </div>
            <?php else: ?>
            <div class="vs-table-toolbar d-flex align-items-center gap-2 mb-3 flex-wrap">
                <input type="text" id="customArchiveSearch" class="vs-input vs-page-search" placeholder="Enter keyword to search this page" style="max-width:260px">
                <label class="vs-length-label ms-auto">Show <input type="number" id="archiveLengthInput" class="vs-length-input" value="10" min="1" max="500"> entries</label>
            </div>
            <table id="archivedVouchersTable" class="vs-datatable vs-mobile-primary" data-mobile-primary="1"
                   data-datatable-url="<?= esc($dtUrl, 'attr') ?>"
                   data-filter-params="<?= esc(json_encode($dtFilterParams), 'attr') ?>"
                   style="width:100%">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th style="display:none">Name Sort</th>
                        <th>Rank</th>
                        <th>JHS</th>
                        <th>SHS</th>
                        <th>School Year</th>
                        <th>Remarks</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

<?= pre_modal('archive') ?>
<script>
// Archive filter dropdowns (SY + schools) are lazy-loaded — see the Filters
// modal wiring below — so the page load itself runs no DISTINCT/JOIN scans.
window.__VS.archiveFilterOptionsUrl = <?= json_encode($dtOptionsUrl) ?>;
window.__VS.archiveDefaultSY       = <?= json_encode($f('school_year')) ?>;
</script>

<script>
// Server-side DataTable for the archive listing — pages through the DB instead
// of dumping every matching row into the DOM. Only present when a SY is chosen
// (the table shell is rendered only in that state).
document.addEventListener('DOMContentLoaded', function () {
    var table = document.getElementById('archivedVouchersTable');
    if (!table || !window.jQuery || !$.fn.DataTable || !window.VS) return;

    var url = table.dataset.datatableUrl;
    if (!url) return;

    var filterParams = {};
    try { filterParams = JSON.parse(table.dataset.filterParams || '{}'); } catch (e) {}

    $(table).DataTable({
        destroy:    true,
        serverSide: true,
        processing: true,
        ajax: {
            url:  url,
            type: 'GET',
            data: function (d) { Object.assign(d, filterParams); },
        },
        columns: [
            { data: 'name' },
            { data: 'name_sort', visible: false },
            { data: 'rank_no' },
            { data: 'jhs' },
            { data: 'shs' },
            { data: 'sy' },
            { data: 'remarks' },
            { data: 'voucher_status' },
        ],
        order:      [[1, 'asc']],
        columnDefs: [
            { orderData: [1], targets: [0] },
            { className: 'text-start', targets: [0] },
        ],
        dom:        window.VS.dtBodyDom,
        pageLength: 10,
        lengthMenu: window.VS.dtLengthMenuSS,
        autoWidth:  false,
        language:   window.VS.dtLanguage({
            info:       'Showing _START_ to _END_ of _TOTAL_ matching',
            emptyTable: 'No archived records found for the selected filters.',
            zeroRecords:'No archived records found for the selected filters.',
        }),
    });

    var dt = $(table).DataTable();

    var archiveSearch = document.getElementById('customArchiveSearch');
    if (archiveSearch && window.VS && window.VS.bindFullTableSearch) {
        window.VS.bindFullTableSearch(dt, archiveSearch);
    }

    var archiveLengthInput = document.getElementById('archiveLengthInput');
    if (archiveLengthInput) {
        function applyArchiveLen() {
            var v = parseInt(archiveLengthInput.value, 10);
            if (!isNaN(v) && v > 0) dt.page.len(v).draw();
        }
        archiveLengthInput.addEventListener('change', applyArchiveLen);
        archiveLengthInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') applyArchiveLen();
        });
    }
});
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
        // Filter dropdowns are fetched on first open (the 5 DISTINCT/JOIN scans
        // that used to run on every page load). Cached after the first fetch.
        // Append <option>s to a (possibly select2-wrapped) select. Destroys any
        // existing select2 first so the new options are picked up, then leaves
        // re-init to initVsSelect2. getTxt extracts the label from each item.
        function fillSelect(id, items, getTxt, selectedVal) {
            var sel = document.getElementById(id);
            if (!sel) return;
            if (window.jQuery && $.fn.select2 && $(sel).hasClass('select2-hidden-accessible')) {
                try { $(sel).select2('destroy'); } catch (e) {}
            }
            var blank = sel.options[0];           // keep the empty placeholder option
            sel.innerHTML = '';
            if (blank) sel.appendChild(blank);
            (items || []).forEach(function (item) {
                var txt = getTxt(item);
                if (!txt) return;
                var opt = document.createElement('option');
                opt.value = txt;
                opt.textContent = txt;
                if (selectedVal && txt === selectedVal) opt.selected = true;
                sel.appendChild(opt);
            });
        }

        var optionsLoaded = false;
        function loadFilterOptions(done) {
            if (optionsLoaded) { done(); return; }
            var url = (window.__VS && window.__VS.archiveFilterOptionsUrl) || '';
            if (!url) { done(); return; }
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    data = data || {};
                    var qp = new URLSearchParams(window.location.search);
                    var schoolTxt = function (s) { return (s && typeof s === 'object') ? (s.school_name || '') : s; };
                    fillSelect('afSchoolYear', data.schoolYears,       function (s) { return s; }, qp.get('school_year') || (window.__VS.archiveDefaultSY || ''));
                    fillSelect('afJuniorHs',   data.juniorHighSchools, schoolTxt,                 qp.get('junior_hs'));
                    fillSelect('afPreferredHs', data.seniorHighSchools, schoolTxt,                qp.get('preferred_hs'));
                    optionsLoaded = true;
                })
                .catch(function () {})
                .finally(done);
        }
        function openFilter()  {
          filterModal.style.display = 'flex';
          loadFilterOptions(function () {
            if (typeof window.initVsSelect2 === 'function') window.initVsSelect2(filterModal);
          });
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
            afOtherRemarks:  'other_remarks',
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
                    jobId:   data.job_id,    // survive page navigation
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
