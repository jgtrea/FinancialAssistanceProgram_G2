<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
    $keyword      = $keyword ?? '';
    $filters      = $filters ?? [];
    $filterLevel  = $filters['level'] ?? '';
?>

<div class="vs-page-header mb-3">
    <div>
        <h4 class="vs-page-title">Schools</h4>
        <p class="vs-page-sub">Manage Junior And Senior High School Data.</p>
    </div>
</div>

<div id="schoolAlertBox"></div>

<!-- Action bar — appears when rows are checked -->
<div class="vs-action-bar" id="schoolActionBar" style="display:none">
    <span class="vs-action-bar-count"><span id="schoolSelectedCount">0</span> selected</span>
    <div class="d-flex gap-2 ms-auto">
        <button type="button" class="vs-btn vs-btn-success" id="btnOpenExport">
            <?= asset_icon('export') ?>
            Export
        </button>
        <button class="vs-btn vs-btn-danger" id="btnArchiveSelected">
            <?= asset_icon('archive') ?>
            Archive
        </button>
    </div>
</div>

<!-- Search + Level quick filter + action buttons -->
<form method="get" id="schoolSearchForm" class="row g-2 align-items-center mb-3">
    <div class="col-12 col-md">
        <input type="text" name="q" class="vs-input vs-advanced-search-input w-100"
               placeholder="Enter keyword to search (name, acronym, level)"
               value="<?= esc($keyword, 'attr') ?>">
    </div>
    <div class="col-12 col-md-2">
        <select id="schoolLevelFilter" name="level" class="js-filter-select" data-placeholder="Select Level" data-no-search="1" style="width:100%">
            <option value="" <?= $filterLevel === ''    ? 'selected' : '' ?>></option>
            <option value="JHS" <?= $filterLevel === 'JHS' ? 'selected' : '' ?>>JHS</option>
            <option value="SHS" <?= $filterLevel === 'SHS' ? 'selected' : '' ?>>SHS</option>
        </select>
    </div>
    <div class="col-auto d-none d-md-flex align-items-center">
        <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
    </div>
    <div class="col-12 col-md-2 d-flex gap-2">
        <button type="submit" class="vs-btn vs-btn-primary flex-fill">Search</button>
        <a href="<?= site_url('admin/schools') ?>" class="vs-btn vs-btn-danger flex-fill">Clear</a>
    </div>
    <div class="col-12 col-md-auto d-flex align-items-center gap-2">
        <span class="d-none d-md-inline-flex align-items-center" style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
        <div class="d-flex gap-2 flex-fill">
            <button type="button" class="vs-btn vs-btn-dark-green flex-fill flex-md-grow-0 flex-md-shrink-0" id="btnAddSchool">
                <?= asset_icon('add') ?>
                Add School
            </button>
            <button type="button" class="vs-btn vs-btn-success flex-fill flex-md-grow-0 flex-md-shrink-0" id="btnOpenImport">
                <?= asset_icon('import') ?>
                Import
            </button>
        </div>
    </div>
</form>

<div class="vs-card">
    <div class="vs-card-body">
        <div id="schoolSelectAllBanner" style="display:none;margin-bottom:8px;padding:8px 12px;background:#fef3c7;border:1px solid #fcd34d;border-radius:6px;font-size:.875rem">
            <span id="schoolSelectAllBannerText"></span>
            <a href="#" id="schoolSelectAllMatchingLink" style="font-weight:600;margin-left:.5rem;display:none"></a>
            <a href="#" id="schoolClearLink" style="margin-left:.5rem;display:none">Clear</a>
        </div>
        <table id="schoolsTable" class="vs-datatable js-data-table vs-mobile-primary" data-mobile-primary="1" data-page-search="customSchoolsSearch" data-order='[[6,"desc"],[1,"asc"]]' data-col-defs='[{"orderable":false,"targets":5},{"visible":false,"targets":6},{"width":"46%","targets":1},{"width":"16%","targets":2},{"width":"9%","targets":3},{"width":"9%","targets":4}]' style="width:100%">
            <thead>
                <tr>
                    <th class="vs-th-check">
                        <input type="checkbox" class="vs-check" id="schoolCheckAll" aria-label="Select all schools">
                    </th>
                    <th>School Name</th>
                    <th>Acronym</th>
                    <th>Level</th>
                    <th>Status</th>
                    <th class="actions-column actions-column--sm" data-orderable="false">Actions</th>
                    <th style="display:none"></th>
                </tr>
            </thead>
            <tbody>
                <?php
                    usort($schools, fn($a, $b) => strcmp($a['school_name'] ?? '', $b['school_name'] ?? ''));
                ?>
                <?php foreach ($schools as $school): ?>
                    <?php
                        $sid      = (int) $school['school_id'];
                        $isActive = !empty($school['is_active']);
                        $level    = $school['school_level'] ?? '';
                    ?>
                    <tr id="school-row-<?= $sid ?>"
                        data-id="<?= $sid ?>"
                        data-level="<?= esc($level, 'attr') ?>"
                        data-active="<?= $isActive ? '1' : '0' ?>"
                        <?= !$isActive ? 'class="vs-row-archived"' : '' ?>>
                        <td>
                            <input type="checkbox"
                                   class="vs-check school-row-check"
                                   value="<?= $sid ?>"
                                   <?= !$isActive ? 'disabled title="Inactive schools cannot be archived"' : '' ?>>
                        </td>
                        <td><?= esc($school['school_name']) ?></td>
                        <td><?= esc($school['acronym'] ?? '') ?></td>
                        <td><?= esc($level) ?></td>
                        <td>
                            <span style="color:<?= $isActive ? '#16a34a' : '#9ca3af' ?>;display:inline-flex"
                                  title="<?= $isActive ? 'Active' : 'Archived' ?>"
                                  aria-label="<?= $isActive ? 'Active' : 'Archived' ?>">
                                <?= asset_icon($isActive ? 'circle_check' : 'circle_x', ['width' => '18', 'height' => '18']) ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <?php if ($isActive): ?>
                                <div class="dropdown">
                                    <button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle"
                                            data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">Actions</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><button type="button" class="dropdown-item js-school-edit" data-id="<?= $sid ?>">Edit</button></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><button type="button" class="dropdown-item text-danger js-school-archive-single" data-id="<?= $sid ?>">Archive</button></li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div class="dropdown">
                                    <button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle"
                                            data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">Actions</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><button type="button" class="dropdown-item js-school-edit" data-id="<?= $sid ?>">Edit</button></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><button type="button" class="dropdown-item js-school-restore" data-id="<?= $sid ?>">Restore</button></li>
                                    </ul>
                                </div>
                            <?php endif ?>
                        </td>
                        <td style="display:none"><?= $isActive ? '1' : '0' ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?= pre_modal('schools') ?>

<script>
document.addEventListener('vs:modals:ready', function () {
    var csrfName = '<?= csrf_token() ?>';
    var csrfHash = '<?= csrf_hash() ?>';

    function getCsrf() {
        var meta = document.querySelector('meta[name="csrf-token-value"]');
        return { name: csrfName, token: meta ? meta.getAttribute('content') : csrfHash };
    }

    function showAlert(msg, type) {
        var box = document.getElementById('schoolAlertBox');
        box.innerHTML = '<div class="vs-alert vs-alert-' + (type || 'success') + ' mb-3">' + msg + '</div>';
        setTimeout(function () { box.innerHTML = ''; }, 5000);
    }

    function escHtml(v) {
        return String(v || '').replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];
        });
    }

    // ── Single selection set ──────────────────────────────────────────────────
    var schoolSelected = new Set();
    var actionBar      = document.getElementById('schoolActionBar');
    var countLabel     = document.getElementById('schoolSelectedCount');

    function updateBar() {
        if (countLabel) countLabel.textContent = schoolSelected.size;
        if (actionBar)  actionBar.style.display = schoolSelected.size > 0 ? 'flex' : 'none';
        updateSchoolSelectAllBanner();
    }

    function updateSchoolSelectAllBanner() {
        var banner     = document.getElementById('schoolSelectAllBanner');
        var bannerText = document.getElementById('schoolSelectAllBannerText');
        var selectLink = document.getElementById('schoolSelectAllMatchingLink');
        var clearLink  = document.getElementById('schoolClearLink');
        if (!banner) return;

        var selSize = schoolSelected.size;
        if (selSize === 0) { banner.style.display = 'none'; return; }
        banner.style.display = '';

        var totalFiltered = 0;
        var allFilteredIds = [];
        if (window.jQuery && $.fn.DataTable) {
            var tbl = document.getElementById('schoolsTable');
            if (tbl && $.fn.DataTable.isDataTable(tbl)) {
                var dt = $(tbl).DataTable();
                totalFiltered = dt.rows({ search: 'applied' }).count();
                dt.rows({ search: 'applied' }).every(function () {
                    var node = this.node();
                    var cb = node ? node.querySelector('.school-row-check') : null;
                    if (cb && !cb.disabled) allFilteredIds.push(cb.value);
                });
            }
        }

        if (bannerText) bannerText.textContent = selSize + ' selected. ' + totalFiltered + ' total matching.';
        var allSelected = allFilteredIds.length > 0 && allFilteredIds.every(function (id) { return schoolSelected.has(id); });
        if (selectLink) {
            selectLink.textContent = 'Select all ' + allFilteredIds.length + ' matching across all pages';
            selectLink.style.display = allSelected ? 'none' : '';
        }
        if (clearLink) clearLink.style.display = '';
    }

    function updateCheckAll() {
        var tbl = document.getElementById('schoolsTable');
        var pageIds = [];
        if (window.jQuery && tbl && $.fn.DataTable && $.fn.DataTable.isDataTable(tbl)) {
            $(tbl).DataTable().rows({ page: 'current' }).nodes().each(function (row) {
                var cb = row.querySelector('.school-row-check');
                if (cb && !cb.disabled) pageIds.push(cb.value);
            });
        } else {
            document.querySelectorAll('.school-row-check:not(:disabled)').forEach(function (cb) {
                pageIds.push(cb.value);
            });
        }
        var all = document.getElementById('schoolCheckAll');
        if (!all) return;
        var n = pageIds.filter(function (id) { return schoolSelected.has(id); }).length;
        all.checked       = false;
        all.indeterminate = n > 0;
    }

    function syncSchoolPageCheckboxes() {
        if (!window.jQuery || !$.fn.DataTable) return;
        var tbl = document.getElementById('schoolsTable');
        if (!tbl || !$.fn.DataTable.isDataTable(tbl)) return;
        var pageNodes = $(tbl).DataTable().rows({ page: 'current' }).nodes().toArray();
        var pageIds = [];
        pageNodes.forEach(function (row) {
            var cb = row.querySelector('.school-row-check');
            if (!cb) return;
            cb.checked = schoolSelected.has(cb.value);
            row.classList.toggle('vs-row-selected', cb.checked);
            if (!cb.disabled) pageIds.push(cb.value);
        });
        updateCheckAll();
        updateBar();
    }

    var _schoolTbl = document.getElementById('schoolsTable');
    if (_schoolTbl && window.jQuery) $(_schoolTbl).on('draw.dt', syncSchoolPageCheckboxes);

    var checkAll = document.getElementById('schoolCheckAll');
    checkAll && checkAll.addEventListener('change', function () {
        var tbl = document.getElementById('schoolsTable');
        var currentNodes = (window.jQuery && tbl && $.fn.DataTable && $.fn.DataTable.isDataTable(tbl))
            ? $(tbl).DataTable().rows({ page: 'current' }).nodes().toArray()
            : Array.from(document.querySelectorAll('.school-row-check:not(:disabled)')).map(function (cb) { return cb.closest('tr'); });

        var pageIds = [];
        currentNodes.forEach(function (row) {
            var cb = row.querySelector('.school-row-check');
            if (cb && !cb.disabled) pageIds.push(cb.value);
        });

        var allOnPageSelected = pageIds.length > 0 && pageIds.every(function (id) { return schoolSelected.has(id); });
        pageIds.forEach(function (id) {
            if (allOnPageSelected) schoolSelected.delete(id);
            else schoolSelected.add(id);
        });
        syncSchoolPageCheckboxes();
    });

    // Delegated so a row checkbox on ANY DataTables page works. Per-node binding
    // misses off-page rows: DataTables detaches them from the live DOM, so they
    // aren't found when listeners are attached and clicking them on later pages
    // never updates schoolSelected (selection silently capped to page 1).
    document.addEventListener('change', function (e) {
        var cb = e.target;
        if (!cb || !cb.classList || !cb.classList.contains('school-row-check')) return;
        if (cb.checked) schoolSelected.add(cb.value);
        else            schoolSelected.delete(cb.value);
        var tr = cb.closest('tr');
        if (tr) tr.classList.toggle('vs-row-selected', cb.checked);
        updateBar();
        updateCheckAll();
    });

    // ── Select-all-matching + Clear links ────────────────────────────────────
    var _schoolSelectAllLink = document.getElementById('schoolSelectAllMatchingLink');
    var _schoolClearLink     = document.getElementById('schoolClearLink');

    _schoolSelectAllLink && _schoolSelectAllLink.addEventListener('click', function (e) {
        e.preventDefault();
        if (!window.jQuery || !$.fn.DataTable) return;
        var tbl = document.getElementById('schoolsTable');
        if (!tbl || !$.fn.DataTable.isDataTable(tbl)) return;
        $(tbl).DataTable().rows({ search: 'applied' }).every(function () {
            var node = this.node();
            var cb = node ? node.querySelector('.school-row-check') : null;
            if (cb && !cb.disabled) schoolSelected.add(cb.value);
        });
        syncSchoolPageCheckboxes();
        updateBar();
    });

    _schoolClearLink && _schoolClearLink.addEventListener('click', function (e) {
        e.preventDefault();
        schoolSelected.clear();
        syncSchoolPageCheckboxes();
        updateBar();
    });

    // No auto-submit on level change — user clicks Search button.

    // ── Export modal ──────────────────────────────────────────────────────────
    var exportModal = document.getElementById('schoolExportModal');

    function buildExportUrl(format) {
        var base   = '<?= site_url('admin/schools/export') ?>';
        var params = ['format=' + format];
        schoolSelected.forEach(function (id) { params.push('ids[]=' + id); });
        return base + '?' + params.join('&');
    }

    function openExportModal() {
        var excelLink = document.getElementById('exportExcelLink');
        var csvLink   = document.getElementById('exportCsvLink');
        if (excelLink) excelLink.href = buildExportUrl('excel');
        if (csvLink)   csvLink.href   = buildExportUrl('csv');
        if (exportModal) exportModal.style.display = 'flex';
    }

    document.getElementById('btnOpenExport') && document.getElementById('btnOpenExport').addEventListener('click', openExportModal);
    document.getElementById('schoolExportModalClose') && document.getElementById('schoolExportModalClose').addEventListener('click', function () { exportModal.style.display = 'none'; });
    exportModal && exportModal.addEventListener('click', function (e) { if (e.target === exportModal) exportModal.style.display = 'none'; });

    // ── Archive ───────────────────────────────────────────────────────────────
    var archModal      = document.getElementById('schoolArchiveModal');
    var archConfirm    = document.getElementById('schoolArchiveConfirm');
    var archBtnText    = document.getElementById('schoolArchiveBtnText');
    var archBtnSpinner = document.getElementById('schoolArchiveBtnSpinner');
    var pendingArchiveSchoolId = null;

    function closeArchModal() {
        if (archModal) archModal.style.display = 'none';
        pendingArchiveSchoolId = null;
    }

    document.getElementById('schoolArchiveModalClose')  && document.getElementById('schoolArchiveModalClose').addEventListener('click', closeArchModal);
    document.getElementById('schoolArchiveModalCancel') && document.getElementById('schoolArchiveModalCancel').addEventListener('click', closeArchModal);
    archModal && archModal.addEventListener('click', function (e) { if (e.target === archModal) closeArchModal(); });

    document.getElementById('btnArchiveSelected') && document.getElementById('btnArchiveSelected').addEventListener('click', function () {
        if (!schoolSelected.size) return;
        var ct = document.getElementById('schoolArchiveCount');
        if (ct) ct.textContent = schoolSelected.size;
        if (archModal) archModal.style.display = 'flex';
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-school-archive-single');
        if (!btn) return;
        pendingArchiveSchoolId = btn.getAttribute('data-id');
        var ct = document.getElementById('schoolArchiveCount');
        if (ct) ct.textContent = '1';
        if (archModal) archModal.style.display = 'flex';
    });

    function schoolDtRedraw() {
        if (!window.jQuery || !$.fn.DataTable) return;
        var tbl = document.getElementById('schoolsTable');
        if (tbl && $.fn.DataTable.isDataTable(tbl)) $(tbl).DataTable().draw(false);
    }

    function applySchoolArchiveDom(id) {
        var row = document.getElementById('school-row-' + id);
        if (!row) return;
        row.classList.add('vs-row-archived');
        row.setAttribute('data-active', '0');
        // Col 6 (is_active sort) is hidden — use DT API so Actions cell isn't clobbered.
        if (window.jQuery && $.fn.DataTable) {
            var tbl = document.getElementById('schoolsTable');
            if (tbl && $.fn.DataTable.isDataTable(tbl)) {
                $(tbl).DataTable().cell(row, 6).data('0');
            }
        }
        var cb = row.querySelector('.school-row-check');
        if (cb) { cb.disabled = true; cb.checked = false; }
        var actionsCell = row.querySelector('.actions-cell');
        if (actionsCell) {
            actionsCell.innerHTML =
                '<div class="dropdown">' +
                '<button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle" data-bs-toggle="dropdown" data-bs-popper-config=\'{"strategy":"fixed"}\' aria-expanded="false">Actions</button>' +
                '<ul class="dropdown-menu dropdown-menu-end">' +
                '<li><button type="button" class="dropdown-item js-school-edit" data-id="' + id + '">Edit</button></li>' +
                '<li><hr class="dropdown-divider"></li>' +
                '<li><button type="button" class="dropdown-item js-school-restore" data-id="' + id + '">Restore</button></li>' +
                '</ul></div>';
            var newEdit    = actionsCell.querySelector('.js-school-edit');
            var newRestore = actionsCell.querySelector('.js-school-restore');
            if (newEdit)    newEdit.addEventListener('click', function () { smOpen('edit', id); });
            if (newRestore) newRestore.addEventListener('click', handleRestore.bind(newRestore));
        }
    }

    archConfirm && archConfirm.addEventListener('click', function () {
        var idsToArchive = pendingArchiveSchoolId ? [pendingArchiveSchoolId] : Array.from(schoolSelected);
        var csrf = getCsrf();
        var body = csrf.name + '=' + csrf.token;
        idsToArchive.forEach(function (id) { body += '&ids[]=' + id; });

        archConfirm.disabled = true;
        if (archBtnText)    archBtnText.style.display    = 'none';
        if (archBtnSpinner) archBtnSpinner.style.display = 'inline-block';

        fetch('<?= site_url('admin/schools/archive-multiple') ?>', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            closeArchModal();
            if (data.success) {
                idsToArchive.forEach(function (id) {
                    applySchoolArchiveDom(id);
                    schoolSelected.delete(id);
                });
                schoolDtRedraw();
                updateBar();
                updateCheckAll();
                showAlert(data.message || 'Archived successfully.', 'success');
            } else {
                showAlert(data.message || 'Failed to archive.', 'error');
            }
        })
        .catch(function () { showAlert('An error occurred.', 'error'); closeArchModal(); })
        .finally(function () {
            archConfirm.disabled = false;
            if (archBtnText)    archBtnText.style.display    = 'inline';
            if (archBtnSpinner) archBtnSpinner.style.display = 'none';
        });
    });

    // ── Restore ───────────────────────────────────────────────────────────────
    function handleRestore() {
        var id   = this.getAttribute('data-id');
        var btn  = this;
        var csrf = getCsrf();
        btn.disabled = true;

        fetch('<?= site_url('admin/schools/restore-multiple') ?>', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    csrf.name + '=' + csrf.token + '&ids[]=' + id,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                var row = document.getElementById('school-row-' + id);
                if (row) {
                    row.classList.remove('vs-row-archived');
                    row.setAttribute('data-active', '1');
                    var sortCell = row.cells[row.cells.length - 1];
                    if (sortCell) sortCell.textContent = '1';
                    var cb = row.querySelector('.school-row-check');
                    if (cb) { cb.disabled = false; }
                    var actionsCell = row.querySelector('.actions-cell');
                    if (actionsCell) {
                        actionsCell.innerHTML =
                            '<div class="dropdown">' +
                            '<button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle" data-bs-toggle="dropdown" data-bs-popper-config=\'{"strategy":"fixed"}\' aria-expanded="false">Actions</button>' +
                            '<ul class="dropdown-menu dropdown-menu-end">' +
                            '<li><button type="button" class="dropdown-item js-school-edit" data-id="' + id + '">Edit</button></li>' +
                            '<li><hr class="dropdown-divider"></li>' +
                            '<li><button type="button" class="dropdown-item text-danger js-school-archive-single" data-id="' + id + '">Archive</button></li>' +
                            '</ul></div>';
                        var newEdit = actionsCell.querySelector('.js-school-edit');
                        if (newEdit) newEdit.addEventListener('click', function () { smOpen('edit', id); });
                    }
                }
                schoolDtRedraw();
                showAlert(data.message || 'Restored successfully.', 'success');
            } else {
                showAlert(data.message || 'Failed to restore.', 'error');
                btn.disabled = false;
            }
        })
        .catch(function () { showAlert('An error occurred.', 'error'); btn.disabled = false; });
    }

    document.querySelectorAll('.js-school-restore').forEach(function (btn) {
        btn.addEventListener('click', handleRestore.bind(btn));
    });

    // ── Add / Edit Modal ──────────────────────────────────────────────────────
    var schoolModal  = document.getElementById('schoolModal');
    var schoolForm   = document.getElementById('schoolModalForm');
    var schoolTitle  = document.getElementById('schoolModalTitle');
    var schoolAlert  = document.getElementById('schoolModalAlert');
    var smSubmitText = document.getElementById('smSubmitText');
    var smSubmitSpin = document.getElementById('smSubmitSpinner');
    var smSubmitBtn  = document.getElementById('schoolModalSubmit');

    function smInitSelects() {
        if (typeof window.initVsSelect2 === 'function') window.initVsSelect2(schoolModal);
    }

    function smRefreshSelects() {
        if (window.jQuery) {
            jQuery('#smSchoolLevel').trigger('change.select2');
        }
    }

    function smOpenAlert(msg, type) {
        schoolAlert.innerHTML = '<div class="vs-alert vs-alert-' + (type || 'error') + ' mb-3">' + escHtml(msg) + '</div>';
    }
    function smClearAlert() { schoolAlert.innerHTML = ''; }

    function smReset() {
        schoolForm.reset();
        document.getElementById('smSchoolId').value = '';
        document.getElementById('smAcronym').value = '';
        smClearAlert();
        smRefreshSelects();
    }

    function smOpen(mode, id) {
        smInitSelects();
        smReset();
        if (mode === 'add') {
            schoolTitle.textContent  = 'Add School';
            smSubmitText.textContent = 'Save';
            schoolModal.style.display = 'flex';
            return;
        }
        schoolTitle.textContent  = 'Edit School';
        smSubmitText.textContent = 'Update';
        schoolModal.style.display = 'flex';

        fetch('<?= site_url('admin/schools/json') ?>/' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success) { smOpenAlert(data.message || 'Failed to load school.'); return; }
            var s = data.school;
            document.getElementById('smSchoolId').value    = s.school_id;
            document.getElementById('smSchoolName').value  = s.school_name  || '';
            document.getElementById('smSchoolLevel').value = s.school_level || '';
            document.getElementById('smAcronym').value = s.acronym || '';
            smRefreshSelects();
        })
        .catch(function () { smOpenAlert('Failed to load school.'); });
    }

    function smClose() { schoolModal.style.display = 'none'; }

document.getElementById('btnAddSchool')    && document.getElementById('btnAddSchool').addEventListener('click', function () { smOpen('add'); });
    document.getElementById('schoolModalClose')   && document.getElementById('schoolModalClose').addEventListener('click', smClose);
    document.getElementById('schoolModalCancel')  && document.getElementById('schoolModalCancel').addEventListener('click', smClose);
    schoolModal && schoolModal.addEventListener('click', function (e) { if (e.target === schoolModal) smClose(); });

    document.querySelectorAll('.js-school-edit').forEach(function (btn) {
        btn.addEventListener('click', function () { smOpen('edit', btn.getAttribute('data-id')); });
    });

    schoolForm && schoolForm.addEventListener('submit', function (e) {
        e.preventDefault();
        smClearAlert();

        var fd   = new FormData(schoolForm);
        var csrf = getCsrf();
        if (csrf.name && !fd.get(csrf.name)) fd.append(csrf.name, csrf.token);

        smSubmitBtn.disabled       = true;
        smSubmitText.style.display = 'none';
        smSubmitSpin.style.display = 'inline-block';

        fetch('<?= site_url('admin/schools/save') ?>', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body:    fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                smClose();
                showAlert(data.message || 'School saved successfully.', 'success');
                setTimeout(function () { location.reload(); }, 800);
                return;
            }
            smOpenAlert(data.message || 'Save failed.');
        })
        .catch(function () { smOpenAlert('An error occurred while saving.'); })
        .finally(function () {
            smSubmitBtn.disabled       = false;
            smSubmitText.style.display = 'inline';
            smSubmitSpin.style.display = 'none';
        });
    });

    // ── Import Modal ──────────────────────────────────────────────────────────
    var importModal  = document.getElementById('schoolImportModal');
    var importForm   = document.getElementById('schoolImportForm');
    var importAlert  = document.getElementById('schoolImportAlert');
    var siSubmitText = document.getElementById('siSubmitText');
    var siSubmitSpin = document.getElementById('siSubmitSpinner');
    var siSubmitBtn  = document.getElementById('schoolImportSubmit');

    function openImport()  { if (importModal) importModal.style.display = 'flex'; }
    function closeImport() {
        if (importModal) importModal.style.display = 'none';
        importForm  && importForm.reset();
        importAlert && (importAlert.innerHTML = '');
    }

    document.getElementById('btnOpenImport')      && document.getElementById('btnOpenImport').addEventListener('click', openImport);
    document.getElementById('schoolImportClose')  && document.getElementById('schoolImportClose').addEventListener('click', closeImport);
    document.getElementById('schoolImportCancel') && document.getElementById('schoolImportCancel').addEventListener('click', closeImport);
    importModal && importModal.addEventListener('click', function (e) { if (e.target === importModal) closeImport(); });

    importForm && importForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var fd   = new FormData(importForm);
        var csrf = getCsrf();
        if (csrf.name && !fd.get(csrf.name)) fd.append(csrf.name, csrf.token);

        siSubmitBtn.disabled       = true;
        siSubmitText.style.display = 'none';
        siSubmitSpin.style.display = 'inline-block';
        importAlert.innerHTML = '';

        fetch('<?= site_url('admin/schools/import') ?>', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body:    fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                closeImport();
                showAlert(data.message || 'Import successful.', 'success');
                setTimeout(function () { location.reload(); }, 1200);
            } else {
                importAlert.innerHTML = '<div class="vs-alert vs-alert-error mb-3">' + escHtml(data.message || 'Import failed.') + '</div>';
            }
        })
        .catch(function () {
            importAlert.innerHTML = '<div class="vs-alert vs-alert-error mb-3">An error occurred during import.</div>';
        })
        .finally(function () {
            siSubmitBtn.disabled       = false;
            siSubmitText.style.display = 'inline';
            siSubmitSpin.style.display = 'none';
        });
    });

});

// Level filtering is handled server-side via SQL (no client-side column filter).
</script>

<?= $this->endSection() ?>
