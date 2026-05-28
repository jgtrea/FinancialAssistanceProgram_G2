<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
    $keyword = $keyword ?? '';
    $filters = $filters ?? [];
    $filterLevel  = $filters['level']  ?? '';
    $filterStatus = $filters['status'] ?? '';
?>

<div class="vs-page-header mb-4">
    <div>
        <h4 class="vs-page-title">Schools</h4>
        <p class="vs-page-sub">Manage junior and senior high school data.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="vs-btn vs-btn-primary" id="btnAddSchool">
            <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
            Add School
        </button>
        <button type="button" class="vs-btn vs-btn-outline" id="btnOpenImport">
            <?= asset_icon('import') ?>
            Import
        </button>
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

<!-- Advanced search -->
<form method="get" class="vs-advanced-search vs-advanced-search-outside mb-3" id="schoolSearchForm">
    <input type="text" name="q" class="vs-input vs-advanced-search-input"
           placeholder="Advanced search all schools..."
           value="<?= esc($keyword, 'attr') ?>">
    <?php if ($filterLevel !== ''): ?>
        <input type="hidden" name="level" value="<?= esc($filterLevel, 'attr') ?>">
    <?php endif ?>
    <?php if ($filterStatus !== ''): ?>
        <input type="hidden" name="status" value="<?= esc($filterStatus, 'attr') ?>">
    <?php endif ?>
    <button type="button" class="vs-btn vs-btn-outline" id="btnOpenSchoolFilter">
        Filters
        <span id="schoolFilterBadge" class="badge bg-primary" style="display:<?= ($filterLevel !== '' || $filterStatus !== '') ? '' : 'none' ?>;margin-left:.35rem">
            <?= (int)($filterLevel !== '') + (int)($filterStatus !== '') ?: '' ?>
        </span>
    </button>
</form>

<div class="vs-card">
    <div class="vs-card-body">
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <input type="text" id="customSchoolSearch" class="vs-input vs-page-search"
                   placeholder="Search this page..." style="max-width:260px">
            <label class="vs-length-label ms-auto">Show
                <input type="number" id="schoolLengthInput" class="vs-length-input" value="10" min="1" max="500">
            entries</label>
        </div>

        <table id="schoolsTable" class="vs-datatable js-data-table" style="width:100%">
            <thead>
                <tr>
                    <th class="vs-th-check">
                        <input type="checkbox" class="vs-check" id="schoolCheckAll" aria-label="Select all">
                    </th>
                    <th>School Name</th>
                    <th>Level</th>
                    <th class="actions-column actions-column--sm" data-orderable="false">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schools as $school): ?>
                    <?php
                        $sid      = (int) $school['school_id'];
                        $isActive = !empty($school['is_active']);
                    ?>
                    <tr id="school-row-<?= $sid ?>"
                        data-id="<?= $sid ?>"
                        data-active="<?= $isActive ? '1' : '0' ?>"
                        <?= !$isActive ? 'class="vs-row-archived"' : '' ?>>
                        <td>
                            <input type="checkbox"
                                   class="vs-check school-row-check"
                                   value="<?= $sid ?>"
                                   <?= !$isActive ? 'disabled title="Inactive schools cannot be archived"' : '' ?>>
                        </td>
                        <td><?= esc($school['school_name']) ?></td>
                        <td>
                            <span class="badge text-white" style="background-color:<?= $school['school_level'] === 'SHS' ? '#1e3a8a' : '#2563eb' ?>">
                                <?= esc($school['school_level']) ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <button type="button"
                                    class="vs-tbl-btn vs-tbl-btn-edit js-school-edit"
                                    data-id="<?= $sid ?>">
                                Edit
                            </button>
                            <?php if (!$isActive): ?>
                                <button type="button"
                                        class="vs-tbl-btn vs-tbl-btn-view js-school-restore"
                                        data-id="<?= $sid ?>">
                                    Restore
                                </button>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Archive Confirmation Modal ─────────────────────────────────────── -->
<div class="vs-modal-overlay" id="schoolArchiveModal" style="display:none">
    <div class="vs-modal">
        <div class="vs-modal-header">
            <h5>Archive Schools</h5>
            <button class="vs-modal-close" id="schoolArchiveModalClose">&times;</button>
        </div>
        <div class="vs-modal-body">
            <p>You are about to archive <strong id="schoolArchiveCount">0</strong> school(s).
               Archived schools will no longer appear in the voucher school picker.</p>
        </div>
        <div class="vs-modal-footer">
            <button class="vs-btn vs-btn-outline" id="schoolArchiveModalCancel">Cancel</button>
            <button class="vs-btn vs-btn-danger" id="schoolArchiveConfirm">
                <span id="schoolArchiveBtnText">Confirm Archive</span>
                <span id="schoolArchiveBtnSpinner" class="vs-spinner" style="display:none"></span>
            </button>
        </div>
    </div>
</div>

<!-- ── Export Modal ──────────────────────────────────────────────────── -->
<div class="vs-modal-overlay" id="schoolExportModal" style="display:none">
    <div class="vs-modal">
        <div class="vs-modal-header">
            <h5>Export Schools</h5>
            <button class="vs-modal-close" id="schoolExportModalClose">&times;</button>
        </div>
        <div class="vs-modal-body">
            <p>Choose the file format to export the selected school records.</p>
            <div class="d-flex gap-3 mt-3">
                <a href="<?= site_url('admin/schools/export?format=excel') ?>"
                   id="exportExcelLink"
                   class="vs-btn vs-btn-outline flex-fill text-center">
                    Excel (.xlsx)
                </a>
                <a href="<?= site_url('admin/schools/export?format=csv') ?>"
                   id="exportCsvLink"
                   class="vs-btn vs-btn-outline flex-fill text-center">
                    CSV (.csv)
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ── Filter Modal ───────────────────────────────────────────────────── -->
<div class="vs-modal-overlay" id="schoolFilterModal" style="display:none">
    <div class="vs-modal" style="max-width:380px">
        <div class="vs-modal-header">
            <h5>Filter Schools</h5>
            <button class="vs-modal-close" id="schoolFilterClose">&times;</button>
        </div>
        <div class="vs-modal-body">
            <div class="d-flex flex-column gap-3">
                <div>
                    <label class="vs-label" for="sfLevel">Level</label>
                    <select id="sfLevel" class="vs-input">
                        <option value="">All</option>
                        <option value="JHS" <?= $filterLevel === 'JHS' ? 'selected' : '' ?>>JHS</option>
                        <option value="SHS" <?= $filterLevel === 'SHS' ? 'selected' : '' ?>>SHS</option>
                    </select>
                </div>
                <div>
                    <label class="vs-label" for="sfStatus">Status</label>
                    <select id="sfStatus" class="vs-input">
                        <option value="">All</option>
                        <option value="1" <?= $filterStatus === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $filterStatus === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="vs-modal-footer">
            <button type="button" class="vs-btn vs-btn-outline" id="schoolFilterClear">Clear All</button>
            <button type="button" class="vs-btn vs-btn-outline" id="schoolFilterCancel">Cancel</button>
            <button type="button" class="vs-btn vs-btn-primary" id="schoolFilterApply">Apply</button>
        </div>
    </div>
</div>

<!-- ── Add / Edit Modal ───────────────────────────────────────────────── -->
<div class="vs-modal-overlay" id="schoolModal" style="display:none">
    <div class="vs-modal" style="max-width:480px">
        <div class="vs-modal-header">
            <h5 id="schoolModalTitle">Add School</h5>
            <button class="vs-modal-close" id="schoolModalClose">&times;</button>
        </div>
        <form id="schoolModalForm" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="school_id" id="smSchoolId" value="">

            <div class="vs-modal-body">
                <div id="schoolModalAlert"></div>

                <div class="d-flex flex-column gap-3">
                    <div>
                        <label class="vs-label required" for="smSchoolName">School Name</label>
                        <input id="smSchoolName" name="school_name" type="text"
                               class="vs-input vs-uppercase" required
                               placeholder="e.g. TANDAG NATIONAL HIGH SCHOOL">
                    </div>
                    <div>
                        <label class="vs-label required" for="smSchoolLevel">Level</label>
                        <select id="smSchoolLevel" name="school_level" class="vs-input" required>
                            <option value="">-- Select --</option>
                            <option value="JHS">JHS (Junior High School)</option>
                            <option value="SHS">SHS (Senior High School)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="vs-modal-footer">
                <button type="button" class="vs-btn vs-btn-outline" id="schoolModalCancel">Cancel</button>
                <button type="submit" class="vs-btn vs-btn-primary" id="schoolModalSubmit">
                    <span id="smSubmitText">Save</span>
                    <span id="smSubmitSpinner" class="vs-spinner" style="display:none"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── Import Modal ───────────────────────────────────────────────────── -->
<div class="vs-modal-overlay" id="schoolImportModal" style="display:none">
    <div class="vs-modal" style="max-width:500px">
        <div class="vs-modal-header">
            <h5>Import Schools</h5>
            <button class="vs-modal-close" id="schoolImportClose">&times;</button>
        </div>
        <form id="schoolImportForm" novalidate enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="vs-modal-body">
                <div id="schoolImportAlert"></div>

                <p class="text-muted" style="font-size:.875rem">
                    Upload a <strong>.csv</strong>, <strong>.xlsx</strong>, or <strong>.xls</strong> file
                    with columns: <strong>School Name</strong> and <strong>Level</strong> (JHS or SHS).
                    Duplicate entries will be skipped automatically.
                </p>
                <div class="mt-3">
                    <label class="vs-label" for="schoolFileInput">File</label>
                    <input id="schoolFileInput" name="school_file" type="file"
                           class="vs-input" accept=".csv,.xlsx,.xls" required>
                </div>
            </div>
            <div class="vs-modal-footer">
                <button type="button" class="vs-btn vs-btn-outline" id="schoolImportCancel">Cancel</button>
                <button type="submit" class="vs-btn vs-btn-primary" id="schoolImportSubmit">
                    <span id="siSubmitText">Import</span>
                    <span id="siSubmitSpinner" class="vs-spinner" style="display:none"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var csrfName = '<?= csrf_token() ?>';
    var csrfHash = '<?= csrf_hash() ?>';

    function getCsrf() {
        var meta = document.querySelector('meta[name="csrf-token-value"]');
        return {
            name:  csrfName,
            token: meta ? meta.getAttribute('content') : csrfHash,
        };
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

    // ── Checkbox + Action Bar ─────────────────────────────────────────────────
    var selectedIds = new Set();
    var actionBar   = document.getElementById('schoolActionBar');
    var countLabel  = document.getElementById('schoolSelectedCount');

    function getEnabledCheckboxes() {
        return Array.prototype.filter.call(
            document.querySelectorAll('.school-row-check'),
            function (cb) { return !cb.disabled; }
        );
    }

    function updateBar() {
        if (countLabel) countLabel.textContent = selectedIds.size;
        if (actionBar)  actionBar.style.display = selectedIds.size > 0 ? 'flex' : 'none';
        var all = document.getElementById('schoolCheckAll');
        if (all) {
            var enabled = getEnabledCheckboxes();
            all.checked = enabled.length > 0 && selectedIds.size >= enabled.length;
            all.indeterminate = selectedIds.size > 0 && selectedIds.size < enabled.length;
        }
    }

    var checkAll = document.getElementById('schoolCheckAll');
    checkAll && checkAll.addEventListener('change', function () {
        var toCheck = selectedIds.size === 0;
        getEnabledCheckboxes().forEach(function (cb) {
            cb.checked = toCheck;
            if (toCheck) selectedIds.add(cb.value);
            else         selectedIds.delete(cb.value);
            cb.closest('tr').classList.toggle('vs-row-selected', toCheck);
        });
        updateBar();
    });

    document.querySelectorAll('.school-row-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (cb.checked) selectedIds.add(cb.value);
            else            selectedIds.delete(cb.value);
            cb.closest('tr').classList.toggle('vs-row-selected', cb.checked);
            updateBar();
        });
    });

    // ── Export modal ──────────────────────────────────────────────────────────
    var exportModal      = document.getElementById('schoolExportModal');
    var exportModalClose = document.getElementById('schoolExportModalClose');
    var exportExcelLink  = document.getElementById('exportExcelLink');
    var exportCsvLink    = document.getElementById('exportCsvLink');

    function buildExportUrl(format) {
        var base   = '<?= site_url('admin/schools/export') ?>';
        var params = ['format=' + format];
        selectedIds.forEach(function (id) { params.push('ids[]=' + id); });
        return base + '?' + params.join('&');
    }

    function openExportModal() {
        if (exportExcelLink) exportExcelLink.href = buildExportUrl('excel');
        if (exportCsvLink)   exportCsvLink.href   = buildExportUrl('csv');
        if (exportModal)     exportModal.style.display = 'flex';
    }

    document.getElementById('btnOpenExport') && document.getElementById('btnOpenExport').addEventListener('click', openExportModal);
    exportModalClose && exportModalClose.addEventListener('click', function () { exportModal.style.display = 'none'; });
    exportModal && exportModal.addEventListener('click', function (e) { if (e.target === exportModal) exportModal.style.display = 'none'; });

    // ── Archive ───────────────────────────────────────────────────────────────
    var archModal     = document.getElementById('schoolArchiveModal');
    var archConfirm   = document.getElementById('schoolArchiveConfirm');
    var archCancel    = document.getElementById('schoolArchiveModalCancel');
    var archClose     = document.getElementById('schoolArchiveModalClose');
    var archCount     = document.getElementById('schoolArchiveCount');
    var archBtnText   = document.getElementById('schoolArchiveBtnText');
    var archBtnSpinner= document.getElementById('schoolArchiveBtnSpinner');

    function openArchModal()  { if (archModal) archModal.style.display = 'flex'; }
    function closeArchModal() { if (archModal) archModal.style.display = 'none'; }

    archClose  && archClose.addEventListener('click', closeArchModal);
    archCancel && archCancel.addEventListener('click', closeArchModal);
    archModal  && archModal.addEventListener('click', function (e) { if (e.target === archModal) closeArchModal(); });

    document.getElementById('btnArchiveSelected') && document.getElementById('btnArchiveSelected').addEventListener('click', function () {
        if (!selectedIds.size) return;
        if (archCount) archCount.textContent = selectedIds.size;
        openArchModal();
    });

    archConfirm && archConfirm.addEventListener('click', function () {
        var csrf = getCsrf();
        var body = csrf.name + '=' + csrf.token;
        selectedIds.forEach(function (id) { body += '&ids[]=' + id; });

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
                selectedIds.forEach(function (id) {
                    var row = document.getElementById('school-row-' + id);
                    if (row) {
                        row.classList.add('vs-row-archived');
                        row.setAttribute('data-active', '0');
                        // Disable checkbox
                        var cb = row.querySelector('.school-row-check');
                        if (cb) { cb.disabled = true; cb.checked = false; }
                        // Add Restore button if not already there
                        var actionsCell = row.querySelector('.actions-cell');
                        if (actionsCell && !actionsCell.querySelector('.js-school-restore')) {
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'vs-tbl-btn vs-tbl-btn-view js-school-restore';
                            btn.setAttribute('data-id', id);
                            btn.textContent = 'Restore';
                            btn.addEventListener('click', handleRestore);
                            actionsCell.appendChild(btn);
                        }
                    }
                });
                selectedIds.clear();
                updateBar();
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
                    var cb = row.querySelector('.school-row-check');
                    if (cb) { cb.disabled = false; }
                    btn.remove();
                }
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
    var schoolModal   = document.getElementById('schoolModal');
    var schoolForm    = document.getElementById('schoolModalForm');
    var schoolTitle   = document.getElementById('schoolModalTitle');
    var schoolClose   = document.getElementById('schoolModalClose');
    var schoolCancel  = document.getElementById('schoolModalCancel');
    var schoolAlert   = document.getElementById('schoolModalAlert');
    var smSubmitText  = document.getElementById('smSubmitText');
    var smSubmitSpin  = document.getElementById('smSubmitSpinner');
    var smSubmitBtn   = document.getElementById('schoolModalSubmit');

    function smOpenAlert(msg, type) {
        schoolAlert.innerHTML = '<div class="vs-alert vs-alert-' + (type || 'error') + ' mb-3">' + escHtml(msg) + '</div>';
    }
    function smClearAlert() { schoolAlert.innerHTML = ''; }

    function smReset() {
        schoolForm.reset();
        document.getElementById('smSchoolId').value = '';
        smClearAlert();
    }

    function smOpen(mode, id) {
        smReset();
        if (mode === 'add') {
            schoolTitle.textContent = 'Add School';
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
            document.getElementById('smSchoolId').value      = s.school_id;
            document.getElementById('smSchoolName').value    = s.school_name || '';
            document.getElementById('smSchoolLevel').value   = s.school_level || '';
        })
        .catch(function () { smOpenAlert('Failed to load school.'); });
    }

    function smClose() { schoolModal.style.display = 'none'; }

    document.getElementById('btnAddSchool') && document.getElementById('btnAddSchool').addEventListener('click', function () { smOpen('add'); });
    schoolClose  && schoolClose.addEventListener('click', smClose);
    schoolCancel && schoolCancel.addEventListener('click', smClose);
    schoolModal  && schoolModal.addEventListener('click', function (e) { if (e.target === schoolModal) smClose(); });

    document.querySelectorAll('.js-school-edit').forEach(function (btn) {
        btn.addEventListener('click', function () { smOpen('edit', btn.getAttribute('data-id')); });
    });

    schoolForm && schoolForm.addEventListener('submit', function (e) {
        e.preventDefault();
        smClearAlert();

        var fd   = new FormData(schoolForm);
        var csrf = getCsrf();
        if (csrf.name && !fd.get(csrf.name)) fd.append(csrf.name, csrf.token);

        smSubmitBtn.disabled    = true;
        smSubmitText.style.display = 'none';
        smSubmitSpin.style.display = 'inline-block';

        fetch('<?= site_url('admin/schools/save') ?>', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body:    fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) { smClose(); location.reload(); return; }
            smOpenAlert(data.message || 'Save failed.');
        })
        .catch(function () { smOpenAlert('An error occurred while saving.'); })
        .finally(function () {
            smSubmitBtn.disabled    = false;
            smSubmitText.style.display = 'inline';
            smSubmitSpin.style.display = 'none';
        });
    });

    // ── Import Modal ──────────────────────────────────────────────────────────
    var importModal   = document.getElementById('schoolImportModal');
    var importForm    = document.getElementById('schoolImportForm');
    var importAlert   = document.getElementById('schoolImportAlert');
    var siSubmitText  = document.getElementById('siSubmitText');
    var siSubmitSpin  = document.getElementById('siSubmitSpinner');
    var siSubmitBtn   = document.getElementById('schoolImportSubmit');

    function openImport()  { if (importModal) importModal.style.display = 'flex'; }
    function closeImport() { if (importModal) importModal.style.display = 'none'; importForm && importForm.reset(); importAlert && (importAlert.innerHTML = ''); }

    document.getElementById('btnOpenImport')   && document.getElementById('btnOpenImport').addEventListener('click', openImport);
    document.getElementById('schoolImportClose') && document.getElementById('schoolImportClose').addEventListener('click', closeImport);
    document.getElementById('schoolImportCancel') && document.getElementById('schoolImportCancel').addEventListener('click', closeImport);
    importModal && importModal.addEventListener('click', function (e) { if (e.target === importModal) closeImport(); });

    importForm && importForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var fd   = new FormData(importForm);
        var csrf = getCsrf();
        if (csrf.name && !fd.get(csrf.name)) fd.append(csrf.name, csrf.token);

        siSubmitBtn.disabled    = true;
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
            siSubmitBtn.disabled    = false;
            siSubmitText.style.display = 'inline';
            siSubmitSpin.style.display = 'none';
        });
    });

    // ── Filter Modal ──────────────────────────────────────────────────────────
    var filterModal  = document.getElementById('schoolFilterModal');
    var filterBadge  = document.getElementById('schoolFilterBadge');
    var sfLevel      = document.getElementById('sfLevel');
    var sfStatus     = document.getElementById('sfStatus');

    function openFilter()  { if (filterModal) filterModal.style.display = 'flex'; }
    function closeFilter() { if (filterModal) filterModal.style.display = 'none'; }

    document.getElementById('btnOpenSchoolFilter') && document.getElementById('btnOpenSchoolFilter').addEventListener('click', openFilter);
    document.getElementById('schoolFilterClose')   && document.getElementById('schoolFilterClose').addEventListener('click', closeFilter);
    document.getElementById('schoolFilterCancel')  && document.getElementById('schoolFilterCancel').addEventListener('click', closeFilter);
    filterModal && filterModal.addEventListener('click', function (e) { if (e.target === filterModal) closeFilter(); });

    document.getElementById('schoolFilterClear') && document.getElementById('schoolFilterClear').addEventListener('click', function () {
        if (sfLevel)  sfLevel.value  = '';
        if (sfStatus) sfStatus.value = '';
    });

    document.getElementById('schoolFilterApply') && document.getElementById('schoolFilterApply').addEventListener('click', function () {
        var q      = '<?= esc($keyword, 'js') ?>';
        var level  = sfLevel  ? sfLevel.value  : '';
        var status = sfStatus ? sfStatus.value : '';
        var params = [];
        if (q)      params.push('q='      + encodeURIComponent(q));
        if (level)  params.push('level='  + encodeURIComponent(level));
        if (status) params.push('status=' + encodeURIComponent(status));
        window.location.href = '<?= site_url('admin/schools') ?>' + (params.length ? '?' + params.join('&') : '');
    });

}());

// ── DataTable custom search + length ─────────────────────────────────────────
(function initSchoolTable() {
    var table = document.getElementById('schoolsTable');
    if (!table || !window.jQuery || !$.fn.DataTable || !$.fn.DataTable.isDataTable(table)) {
        return setTimeout(initSchoolTable, 50);
    }
    var dt     = $(table).DataTable();
    var dtWrap = table.closest('.dataTables_wrapper');

    var dtSearch = dtWrap ? dtWrap.querySelector('.dataTables_filter') : null;
    if (dtSearch) dtSearch.style.display = 'none';
    var dtLength = dtWrap ? dtWrap.querySelector('.dataTables_length') : null;
    if (dtLength) dtLength.style.display = 'none';

    var lenInput = document.getElementById('schoolLengthInput');
    if (lenInput) {
        function applyLen() {
            var v = parseInt(lenInput.value, 10);
            if (!isNaN(v) && v > 0) dt.page.len(v).draw();
        }
        lenInput.addEventListener('change', applyLen);
        lenInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') applyLen(); });
    }

    var searchInput = document.getElementById('customSchoolSearch');
    if (window.VS && window.VS.bindCurrentPageSearch) {
        window.VS.bindCurrentPageSearch(dt, searchInput);
    }
}());
</script>

<?= $this->endSection() ?>
