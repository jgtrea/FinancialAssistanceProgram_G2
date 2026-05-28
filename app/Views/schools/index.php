<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
    $keyword      = $keyword ?? '';
    $filters      = $filters ?? [];
    $filterStatus = $filters['status'] ?? '';

    $jhsSchools = array_values(array_filter($schools, fn($s) => $s['school_level'] === 'JHS'));
    $shsSchools = array_values(array_filter($schools, fn($s) => $s['school_level'] === 'SHS'));
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
    <?php if ($filterStatus !== ''): ?>
        <input type="hidden" name="status" value="<?= esc($filterStatus, 'attr') ?>">
    <?php endif ?>
    <button type="button" class="vs-btn vs-btn-outline" id="btnOpenSchoolFilter">
        Filters
        <span id="schoolFilterBadge" class="badge bg-primary"
              style="display:<?= $filterStatus !== '' ? '' : 'none' ?>;margin-left:.35rem">
            <?= $filterStatus !== '' ? 1 : '' ?>
        </span>
    </button>
</form>

<!-- ── Tabs ───────────────────────────────────────────────────────────────── -->
<div style="display:flex;gap:0;border-bottom:2px solid #e5e7eb;margin-bottom:1rem">
    <button type="button" id="tabJhs" data-tab="jhs"
            class="school-tab"
            style="background:transparent;border:none;border-bottom:3px solid #2563eb;color:#2563eb;padding:.6rem 1.4rem;font-weight:600;cursor:pointer;margin-bottom:-2px;font-size:.95rem">
        Junior High School
        <span class="badge ms-1" style="background-color:#2563eb" id="jhsBadge"><?= count($jhsSchools) ?></span>
    </button>
    <button type="button" id="tabShs" data-tab="shs"
            class="school-tab"
            style="background:transparent;border:none;border-bottom:3px solid transparent;color:#6b7280;padding:.6rem 1.4rem;font-weight:600;cursor:pointer;margin-bottom:-2px;font-size:.95rem">
        Senior High School
        <span class="badge ms-1" style="background-color:#1e3a8a" id="shsBadge"><?= count($shsSchools) ?></span>
    </button>
</div>

<!-- ── JHS Panel ─────────────────────────────────────────────────────────── -->
<div id="panel-jhs">
    <div class="vs-card">
        <div class="vs-card-body">
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <input type="text" id="customJhsSearch" class="vs-input vs-page-search"
                       placeholder="Search JHS..." style="max-width:260px">
                <label class="vs-length-label ms-auto">Show
                    <input type="number" id="jhsLengthInput" class="vs-length-input" value="10" min="1" max="500">
                entries</label>
            </div>

            <table id="jhsTable" class="vs-datatable js-data-table" style="width:100%">
                <thead>
                    <tr>
                        <th class="vs-th-check">
                            <input type="checkbox" class="vs-check" id="jhsCheckAll" aria-label="Select all JHS">
                        </th>
                        <th>School Name</th>
                        <th class="actions-column actions-column--sm" data-orderable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jhsSchools as $school): ?>
                        <?php
                            $sid      = (int) $school['school_id'];
                            $isActive = !empty($school['is_active']);
                        ?>
                        <tr id="school-row-<?= $sid ?>"
                            data-id="<?= $sid ?>"
                            data-level="JHS"
                            data-active="<?= $isActive ? '1' : '0' ?>"
                            <?= !$isActive ? 'class="vs-row-archived"' : '' ?>>
                            <td>
                                <input type="checkbox"
                                       class="vs-check school-row-check jhs-check"
                                       value="<?= $sid ?>"
                                       <?= !$isActive ? 'disabled title="Inactive schools cannot be archived"' : '' ?>>
                            </td>
                            <td><?= esc($school['school_name']) ?></td>
                            <td class="actions-cell">
                                <?php if ($isActive): ?>
                                    <div class="dropdown">
                                        <button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle"
                                                data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><button type="button" class="dropdown-item js-school-edit" data-id="<?= $sid ?>">Edit</button></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><button type="button" class="dropdown-item text-danger js-school-archive-single" data-id="<?= $sid ?>">Archive</button></li>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <div class="dropdown">
                                        <button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle"
                                                data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button type="button" class="dropdown-item js-school-edit"
                                                        data-id="<?= $sid ?>">Edit</button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button" class="dropdown-item js-school-restore"
                                                        data-id="<?= $sid ?>">Restore</button>
                                            </li>
                                        </ul>
                                    </div>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── SHS Panel ─────────────────────────────────────────────────────────── -->
<div id="panel-shs" style="display:none">
    <div class="vs-card">
        <div class="vs-card-body">
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <input type="text" id="customShsSearch" class="vs-input vs-page-search"
                       placeholder="Search SHS..." style="max-width:260px">
                <label class="vs-length-label ms-auto">Show
                    <input type="number" id="shsLengthInput" class="vs-length-input" value="10" min="1" max="500">
                entries</label>
            </div>

            <table id="shsTable" class="vs-datatable js-data-table" style="width:100%">
                <thead>
                    <tr>
                        <th class="vs-th-check">
                            <input type="checkbox" class="vs-check" id="shsCheckAll" aria-label="Select all SHS">
                        </th>
                        <th>School Name</th>
                        <th class="actions-column actions-column--sm" data-orderable="false">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shsSchools as $school): ?>
                        <?php
                            $sid      = (int) $school['school_id'];
                            $isActive = !empty($school['is_active']);
                        ?>
                        <tr id="school-row-<?= $sid ?>"
                            data-id="<?= $sid ?>"
                            data-level="SHS"
                            data-active="<?= $isActive ? '1' : '0' ?>"
                            <?= !$isActive ? 'class="vs-row-archived"' : '' ?>>
                            <td>
                                <input type="checkbox"
                                       class="vs-check school-row-check shs-check"
                                       value="<?= $sid ?>"
                                       <?= !$isActive ? 'disabled title="Inactive schools cannot be archived"' : '' ?>>
                            </td>
                            <td><?= esc($school['school_name']) ?></td>
                            <td class="actions-cell">
                                <?php if ($isActive): ?>
                                    <div class="dropdown">
                                        <button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle"
                                                data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><button type="button" class="dropdown-item js-school-edit" data-id="<?= $sid ?>">Edit</button></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><button type="button" class="dropdown-item text-danger js-school-archive-single" data-id="<?= $sid ?>">Archive</button></li>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <div class="dropdown">
                                        <button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle"
                                                data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button type="button" class="dropdown-item js-school-edit"
                                                        data-id="<?= $sid ?>">Edit</button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button" class="dropdown-item js-school-restore"
                                                        data-id="<?= $sid ?>">Restore</button>
                                            </li>
                                        </ul>
                                    </div>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Archive Confirmation Modal ─────────────────────────────────────────── -->
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

<!-- ── Export Modal ───────────────────────────────────────────────────────── -->
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

<!-- ── Filter Modal ───────────────────────────────────────────────────────── -->
<div class="vs-modal-overlay" id="schoolFilterModal" style="display:none">
    <div class="vs-modal" style="max-width:380px">
        <div class="vs-modal-header">
            <h5>Filter Schools</h5>
            <button class="vs-modal-close" id="schoolFilterClose">&times;</button>
        </div>
        <div class="vs-modal-body">
            <div class="d-flex flex-column gap-3">
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

<!-- ── Add / Edit Modal ───────────────────────────────────────────────────── -->
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

<!-- ── Import Modal ───────────────────────────────────────────────────────── -->
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

    // ── Tab state ─────────────────────────────────────────────────────────────
    var activeTab    = 'jhs';
    var jhsSelected  = new Set();
    var shsSelected  = new Set();

    function getActiveSelected() { return activeTab === 'jhs' ? jhsSelected : shsSelected; }

    function switchTab(tab) {
        activeTab = tab;

        // Clear the opposite tab's selections
        var opposite = tab === 'jhs' ? shsSelected : jhsSelected;
        opposite.forEach(function (id) {
            var cb = document.querySelector('.school-row-check[value="' + id + '"]');
            if (cb) { cb.checked = false; cb.closest('tr').classList.remove('vs-row-selected'); }
        });
        opposite.clear();

        // Panel visibility
        document.getElementById('panel-jhs').style.display = tab === 'jhs' ? '' : 'none';
        document.getElementById('panel-shs').style.display = tab === 'shs' ? '' : 'none';

        // Tab button active styles
        var jhsBtn = document.getElementById('tabJhs');
        var shsBtn = document.getElementById('tabShs');
        if (tab === 'jhs') {
            jhsBtn.style.borderBottomColor = '#2563eb';
            jhsBtn.style.color = '#2563eb';
            jhsBtn.style.fontWeight = '600';
            shsBtn.style.borderBottomColor = 'transparent';
            shsBtn.style.color = '#6b7280';
            shsBtn.style.fontWeight = '600';
        } else {
            shsBtn.style.borderBottomColor = '#1e3a8a';
            shsBtn.style.color = '#1e3a8a';
            shsBtn.style.fontWeight = '600';
            jhsBtn.style.borderBottomColor = 'transparent';
            jhsBtn.style.color = '#6b7280';
            jhsBtn.style.fontWeight = '600';
        }

        // Redraw the newly-shown DataTable so columns align
        if (window.jQuery && $.fn.DataTable) {
            var tblId = tab === 'jhs' ? 'jhsTable' : 'shsTable';
            var el = document.getElementById(tblId);
            if (el && $.fn.DataTable.isDataTable(el)) {
                $(el).DataTable().columns.adjust().draw(false);
            }
        }

        updateBar();
        updateCheckAll(tab === 'jhs' ? 'jhsCheckAll' : 'shsCheckAll',
                       tab === 'jhs' ? jhsSelected : shsSelected,
                       tab === 'jhs' ? '.jhs-check' : '.shs-check');
    }

    document.getElementById('tabJhs').addEventListener('click', function () { switchTab('jhs'); });
    document.getElementById('tabShs').addEventListener('click', function () { switchTab('shs'); });

    // ── Action Bar ────────────────────────────────────────────────────────────
    var actionBar  = document.getElementById('schoolActionBar');
    var countLabel = document.getElementById('schoolSelectedCount');

    function updateBar() {
        var sel = getActiveSelected();
        if (countLabel) countLabel.textContent = sel.size;
        if (actionBar)  actionBar.style.display = sel.size > 0 ? 'flex' : 'none';
    }

    function updateCheckAll(allId, selected, checkboxSelector) {
        var all      = document.getElementById(allId);
        if (!all) return;
        var enabled  = Array.prototype.filter.call(
            document.querySelectorAll(checkboxSelector),
            function (cb) { return !cb.disabled; }
        );
        all.checked       = false;
        all.indeterminate = selected.size > 0;
    }

    function bindCheckboxGroup(checkAllId, rowClass, selected) {
        var checkAll = document.getElementById(checkAllId);
        checkAll && checkAll.addEventListener('change', function () {
            var toCheck = selected.size === 0;
            Array.prototype.filter.call(
                document.querySelectorAll('.' + rowClass),
                function (cb) { return !cb.disabled; }
            ).forEach(function (cb) {
                cb.checked = toCheck;
                if (toCheck) selected.add(cb.value);
                else         selected.delete(cb.value);
                cb.closest('tr').classList.toggle('vs-row-selected', toCheck);
            });
            updateBar();
            updateCheckAll(checkAllId, selected, '.' + rowClass);
        });

        document.querySelectorAll('.' + rowClass).forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (cb.checked) selected.add(cb.value);
                else            selected.delete(cb.value);
                cb.closest('tr').classList.toggle('vs-row-selected', cb.checked);
                updateBar();
                updateCheckAll(checkAllId, selected, '.' + rowClass);
            });
        });
    }

    bindCheckboxGroup('jhsCheckAll', 'jhs-check', jhsSelected);
    bindCheckboxGroup('shsCheckAll', 'shs-check', shsSelected);

    // ── Export modal ──────────────────────────────────────────────────────────
    var exportModal = document.getElementById('schoolExportModal');

    function buildExportUrl(format) {
        var base   = '<?= site_url('admin/schools/export') ?>';
        var params = ['format=' + format];
        getActiveSelected().forEach(function (id) { params.push('ids[]=' + id); });
        return base + '?' + params.join('&');
    }

    function openExportModal() {
        var excelLink = document.getElementById('exportExcelLink');
        var csvLink   = document.getElementById('exportCsvLink');
        if (excelLink) excelLink.href = buildExportUrl('excel');
        if (csvLink)   csvLink.href   = buildExportUrl('csv');
        if (exportModal) exportModal.style.display = 'flex';
    }

    document.getElementById('btnOpenExport')     && document.getElementById('btnOpenExport').addEventListener('click', openExportModal);
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
        var sel = getActiveSelected();
        if (!sel.size) return;
        var ct = document.getElementById('schoolArchiveCount');
        if (ct) ct.textContent = sel.size;
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

    function applySchoolArchiveDom(id) {
        var row = document.getElementById('school-row-' + id);
        if (!row) return;
        row.classList.add('vs-row-archived');
        row.setAttribute('data-active', '0');
        var cb = row.querySelector('.school-row-check');
        if (cb) { cb.disabled = true; cb.checked = false; }
        var actionsCell = row.querySelector('.actions-cell');
        if (actionsCell) {
            actionsCell.innerHTML =
                '<div class="dropdown">' +
                '<button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>' +
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
        var idsToArchive = pendingArchiveSchoolId ? [pendingArchiveSchoolId] : Array.from(getActiveSelected());
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
                var sel = getActiveSelected();
                idsToArchive.forEach(function (id) {
                    applySchoolArchiveDom(id);
                    sel.delete(id);
                });
                updateBar();
                updateCheckAll(
                    activeTab === 'jhs' ? 'jhsCheckAll' : 'shsCheckAll',
                    activeTab === 'jhs' ? jhsSelected : shsSelected,
                    activeTab === 'jhs' ? '.jhs-check' : '.shs-check'
                );
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
                    var actionsCell = row.querySelector('.actions-cell');
                    if (actionsCell) {
                        actionsCell.innerHTML =
                            '<div class="dropdown">' +
                            '<button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>' +
                            '<ul class="dropdown-menu dropdown-menu-end">' +
                            '<li><button type="button" class="dropdown-item js-school-edit" data-id="' + id + '">Edit</button></li>' +
                            '<li><hr class="dropdown-divider"></li>' +
                            '<li><button type="button" class="dropdown-item text-danger js-school-archive-single" data-id="' + id + '">Archive</button></li>' +
                            '</ul></div>';
                        var newEdit = actionsCell.querySelector('.js-school-edit');
                        if (newEdit) newEdit.addEventListener('click', function () { smOpen('edit', id); });
                    }
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
    var schoolModal  = document.getElementById('schoolModal');
    var schoolForm   = document.getElementById('schoolModalForm');
    var schoolTitle  = document.getElementById('schoolModalTitle');
    var schoolAlert  = document.getElementById('schoolModalAlert');
    var smSubmitText = document.getElementById('smSubmitText');
    var smSubmitSpin = document.getElementById('smSubmitSpinner');
    var smSubmitBtn  = document.getElementById('schoolModalSubmit');

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
            // Pre-select level based on active tab
            document.getElementById('smSchoolLevel').value = activeTab === 'jhs' ? 'JHS' : 'SHS';
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
        })
        .catch(function () { smOpenAlert('Failed to load school.'); });
    }

    function smClose() { schoolModal.style.display = 'none'; }

    document.getElementById('btnAddSchool')   && document.getElementById('btnAddSchool').addEventListener('click', function () { smOpen('add'); });
    document.getElementById('schoolModalClose') && document.getElementById('schoolModalClose').addEventListener('click', smClose);
    document.getElementById('schoolModalCancel') && document.getElementById('schoolModalCancel').addEventListener('click', smClose);
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
                var schoolId = document.getElementById('smSchoolId').value;
                var newName  = document.getElementById('smSchoolName').value.trim().toUpperCase();
                var newLevel = document.getElementById('smSchoolLevel').value;
                var row = schoolId ? document.getElementById('school-row-' + schoolId) : null;
                var oldLevel = row ? row.getAttribute('data-level') : null;

                if (row && oldLevel === newLevel) {
                    // Edit, same level — update in-place, preserve active tab
                    var nameTd = row.querySelector('td:nth-child(2)');
                    if (nameTd) nameTd.textContent = newName;
                    var tblId = newLevel === 'JHS' ? 'jhsTable' : 'shsTable';
                    if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + tblId)) {
                        $('#' + tblId).DataTable().row(row).invalidate().draw(false);
                    }
                    showAlert(data.message || 'School updated successfully.', 'success');
                } else {
                    showAlert(data.message || 'School saved successfully.', 'success');
                    setTimeout(function () { location.reload(); }, 800);
                }
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

    // ── Filter Modal ──────────────────────────────────────────────────────────
    var filterModal = document.getElementById('schoolFilterModal');
    var sfStatus    = document.getElementById('sfStatus');

    function openFilter()  { if (filterModal) filterModal.style.display = 'flex'; }
    function closeFilter() { if (filterModal) filterModal.style.display = 'none'; }

    document.getElementById('btnOpenSchoolFilter') && document.getElementById('btnOpenSchoolFilter').addEventListener('click', openFilter);
    document.getElementById('schoolFilterClose')   && document.getElementById('schoolFilterClose').addEventListener('click', closeFilter);
    document.getElementById('schoolFilterCancel')  && document.getElementById('schoolFilterCancel').addEventListener('click', closeFilter);
    filterModal && filterModal.addEventListener('click', function (e) { if (e.target === filterModal) closeFilter(); });

    document.getElementById('schoolFilterClear') && document.getElementById('schoolFilterClear').addEventListener('click', function () {
        if (sfStatus) sfStatus.value = '';
    });

    document.getElementById('schoolFilterApply') && document.getElementById('schoolFilterApply').addEventListener('click', function () {
        var q      = '<?= esc($keyword, 'js') ?>';
        var status = sfStatus ? sfStatus.value : '';
        var params = [];
        if (q)      params.push('q='      + encodeURIComponent(q));
        if (status) params.push('status=' + encodeURIComponent(status));
        window.location.href = '<?= site_url('admin/schools') ?>' + (params.length ? '?' + params.join('&') : '');
    });

}());

// ── DataTable init ────────────────────────────────────────────────────────────
(function initSeparatedTables() {
    function initTable(tableId, searchId, lenId) {
        var table = document.getElementById(tableId);
        if (!table || !window.jQuery || !$.fn.DataTable || !$.fn.DataTable.isDataTable(table)) {
            return setTimeout(function () { initTable(tableId, searchId, lenId); }, 50);
        }
        var dt     = $(table).DataTable();
        var dtWrap = table.closest('.dataTables_wrapper');

        var dtSearch = dtWrap ? dtWrap.querySelector('.dataTables_filter') : null;
        if (dtSearch) dtSearch.style.display = 'none';
        var dtLength = dtWrap ? dtWrap.querySelector('.dataTables_length') : null;
        if (dtLength) dtLength.style.display = 'none';

        var lenInput = document.getElementById(lenId);
        if (lenInput) {
            function applyLen() {
                var v = parseInt(lenInput.value, 10);
                if (!isNaN(v) && v > 0) dt.page.len(v).draw();
            }
            lenInput.addEventListener('change', applyLen);
            lenInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') applyLen(); });
        }

        var searchInput = document.getElementById(searchId);
        if (window.VS && window.VS.bindCurrentPageSearch) {
            window.VS.bindCurrentPageSearch(dt, searchInput);
        }
    }

    initTable('jhsTable', 'customJhsSearch', 'jhsLengthInput');
    initTable('shsTable', 'customShsSearch', 'shsLengthInput');
}());
</script>

<?= $this->endSection() ?>
