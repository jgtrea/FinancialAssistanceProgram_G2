<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $prefixOptions = $prefixOptions ?? ['', 'DR.', 'ENGR.', 'HON.', 'MR.', 'MRS.', 'MS.', 'PROF.'];
    $suffixOptions = $suffixOptions ?? ['', 'JR.', 'SR.', 'II', 'III', 'IV', 'V'];
    $degreeOptions = $degreeOptions ?? [
        'None', 'MPA', 'BSc', 'BA',
        'Master', 'MSc', 'MA', 'MBA',
        'Doctorate', 'PhD', 'MD', 'JD', 'LLB', 'DDS', 'EdD',
        'Other',
    ];
?>

<div class="vs-page-header mb-3">
        <div>
            <h4 class="vs-page-title">Signatories</h4>
            <p class="vs-page-sub">Manage Active Voucher Signatories.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="vs-alert vs-alert-success mb-3">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <div id="sigAlertBox"></div>

    <!-- Action bar — shown when rows are checked -->
    <div class="vs-action-bar" id="sigActionBar" style="display:none">
        <span class="vs-action-bar-count"><span id="sigSelectedCount">0</span> selected</span>
        <button class="vs-btn vs-btn-danger" id="btnArchiveSelected">
            <?= asset_icon('archive') ?>
            Deactivate
        </button>
    </div>

    <form method="get" id="sigSearchForm" class="row g-2 align-items-center mb-3">
        <div class="col">
            <input type="text" name="q" class="vs-input vs-advanced-search-input w-100" placeholder="Enter keyword to search (name, position)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
        </div>
        <div class="col-auto">
            <select id="sfStatus" name="status" class="js-filter-select" data-placeholder="Select Status" data-no-search="1" style="width:140px">
                <option></option>
                <option value="active"   <?= ($filterStatus ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($filterStatus ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-auto">
            <select id="sfPosition" name="position" class="js-filter-select" data-placeholder="Select Position" style="width:160px">
                <option></option>
                <?php foreach (($allPositions ?? []) as $pos): ?>
                    <option value="<?= esc($pos) ?>" <?= ($filterPosition ?? '') === $pos ? 'selected' : '' ?>><?= esc($pos) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="col-auto d-flex align-items-center">
            <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
        </div>
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="vs-btn vs-btn-primary">Search</button>
            <a href="<?= site_url('signatories') ?>" class="vs-btn vs-btn-danger">Clear</a>
        </div>
        <div class="col-auto d-flex align-items-center gap-2">
            <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
            <button type="button" class="vs-btn vs-btn-success" id="btnAddSignatory">
                Add Signatory
            </button>
        </div>
    </form>

    <div class="vs-card">
        <div class="vs-card-body">
            <div id="sigSelectAllBanner" style="display:none;margin-bottom:8px;padding:8px 12px;background:#fef3c7;border:1px solid #fcd34d;border-radius:6px;font-size:.875rem">
                <span id="sigSelectAllBannerText"></span>
                <a href="#" id="sigSelectAllMatchingLink" style="font-weight:600;margin-left:.5rem;display:none"></a>
                <a href="#" id="sigClearLink" style="margin-left:.5rem;display:none">Clear</a>
            </div>
            <table id="signatoriesTable" class="vs-datatable js-data-table vs-mobile-primary" data-mobile-primary="1" data-page-search="customSignatoriesSearch"
                   data-search-placeholder="Search signatories..."
                   data-order='[[7,"desc"],[6,"asc"]]'
                   data-col-defs='[{"orderData":[6],"targets":[1]},{"orderData":[7],"targets":[4]},{"visible":false,"targets":6},{"visible":false,"targets":7},{"width":"4%","targets":0},{"width":"35%","targets":1},{"width":"22%","targets":2},{"width":"13%","targets":3},{"width":"8%","targets":4},{"width":"10%","targets":5},{"className":"text-start","targets":[1]}]'
                   style="width:100%">
            <thead>
                <tr>
                    <th class="vs-th-check"><input type="checkbox" class="vs-check" id="sigCheckAll" aria-label="Select all"></th>
                    <th>Full Name</th>
                    <th>Position Title</th>
                    <th data-orderable="false">Signature</th>
                    <th>Status</th>
                    <th class="actions-column actions-column--sm">Actions</th>
                    <th style="display:none"></th>
                    <th style="display:none"></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($signatories as $signatory): ?>
                    <?php
                        $sLn = trim((string) ($signatory['last_name']   ?? ''));
                        $sFn = trim((string) ($signatory['first_name']  ?? ''));
                        $sMn = trim((string) ($signatory['middle_name'] ?? ''));
                        $sFm = implode(' ', array_filter([$sFn, $sMn]));
                        $fullName  = $sLn !== '' ? $sLn . ($sFm !== '' ? ', ' . $sFm : '') : $sFm;
                        $sigDegree = trim((string) ($signatory['degree'] ?? ''));
                        if ($sigDegree !== '' && strcasecmp($sigDegree, 'None') !== 0) {
                            $fullName .= ', ' . $sigDegree;
                        }
                        $isArchived  = empty($signatory['is_active']);
                        $sid         = (int) $signatory['signatory_id'];
                        $nameSortKey = trim(implode(' ', array_filter([$sLn, $sFn, $sMn])));
                    ?>

                    <tr id="sig-row-<?= $sid ?>" data-archived="<?= $isArchived ? '1' : '0' ?>"<?= $isArchived ? ' class="vs-row-archived"' : '' ?>>
                        <td><input type="checkbox" class="vs-check sig-row-check" value="<?= $sid ?>"<?= $isArchived ? ' disabled title="Archived signatories cannot be bulk-archived"' : '' ?>></td>
                        <td><?= esc($fullName) ?></td>
                        <td><span class="sig-position-value"><?= esc($signatory['position_title']) ?></span></td>
                        <td>
                            <?php if (!empty($signatory['signature_image'])): ?>
                                <img src="<?= base_url('signatories/signature/' . $sid) ?>"
                                     alt="Signature of <?= esc($fullName) ?>"
                                     style="max-height: 40px; max-width: 140px;">
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $isArchived ? 'bg-danger' : 'bg-success' ?>" id="sig-status-<?= $sid ?>">
                                <?= $isArchived ? 'Inactive' : 'Active' ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <div class="dropdown">
                                <button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle"
                                        data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">Actions</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button type="button" class="dropdown-item js-sig-edit"
                                                data-id="<?= $sid ?>">Edit</button>
                                    </li>
                                    <?php if ($isArchived): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item sig-restore-btn"
                                                    data-id="<?= $sid ?>"
                                                    id="sig-restore-<?= $sid ?>">Activate</button>
                                        </li>
                                    <?php else: ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item text-danger js-sig-archive-single"
                                                    data-id="<?= $sid ?>">Deactivate</button>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </td>
                        <td style="display:none"><?= esc($nameSortKey) ?></td>
                        <td style="display:none"><?= $isArchived ? '0' : '1' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </div>

<?= pre_modal('signatories') ?>

<script>
document.addEventListener('vs:modals:ready', function () {
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
        var box = document.getElementById('sigAlertBox');
        box.innerHTML = '<div class="vs-alert vs-alert-' + (type || 'success') + ' mb-3">' + msg +
            '<button type="button" class="vs-alert-dismiss" onclick="this.closest(\'.vs-alert\').remove()">×</button></div>';
    }

    // ── Signatory Add / Edit modal ──────────────────────────────────────────
    var sigModal        = document.getElementById('signatoryModal');
    var sigModalForm    = document.getElementById('signatoryModalForm');
    var sigModalTitle   = document.getElementById('signatoryModalTitle');
    var sigModalClose   = document.getElementById('signatoryModalClose');
    var sigModalCancel  = document.getElementById('signatoryModalCancel');
    var sigModalAlert   = document.getElementById('signatoryModalAlert');
    var sigSubmitBtn    = document.getElementById('signatoryModalSubmit');
    var smSubmitText    = document.getElementById('smSubmitText');
    var smSubmitSpinner = document.getElementById('smSubmitSpinner');
    var btnAddSig       = document.getElementById('btnAddSignatory');
    var sigSaveUrl      = '<?= base_url('signatories/save') ?>';
    var sigFetchUrl     = '<?= base_url('signatories/json') ?>';

    var smFieldIds = ['smPrefix', 'smFirstName', 'smMiddleName', 'smLastName', 'smSuffix', 'smDegree', 'smPositionTitle'];
    var smFieldToName = {
        smPrefix:        'prefix',
        smFirstName:     'first_name',
        smMiddleName:    'middle_name',
        smLastName:      'last_name',
        smSuffix:        'suffix',
        smDegree:        'degree',
        smPositionTitle: 'position_title',
    };

    function smInitSelects() {
        if (typeof window.initVsSelect2 === 'function') window.initVsSelect2(sigModal);
    }

    function smRefreshSelects() {
        if (window.jQuery) {
            $('#smPrefix, #smSuffix, #smDegree').trigger('change.select2');
        }
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (ch) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[ch];
        });
    }

    function smShowAlert(msg, type, errors) {
        var html = '<div class="vs-alert vs-alert-' + (type || 'error') + ' mb-3">' + escapeHtml(msg);
        if (errors && Object.keys(errors).length) {
            html += '<ul class="mb-0 mt-2">';
            Object.keys(errors).forEach(function (field) {
                html += '<li><strong>' + escapeHtml(field) + ':</strong> ' + escapeHtml(errors[field]) + '</li>';
            });
            html += '</ul>';
        }
        html += '<button type="button" class="vs-alert-dismiss" onclick="this.closest(\'.vs-alert\').remove()">×</button></div>';
        sigModalAlert.innerHTML = html;
    }
    function smClearAlert() { sigModalAlert.innerHTML = ''; }

    function smResetForm() {
        sigModalForm.reset();
        document.getElementById('smSignatoryId').value = '';
        document.getElementById('smCurrentSignatureWrap').style.display = 'none';
        document.getElementById('smRemoveSignature').checked = false;
        document.getElementById('smAutoRemoveBg').checked = true;
        var dOther = document.getElementById('smDegreeOther');
        if (dOther) { dOther.value = ''; dOther.style.display = 'none'; }
        smRefreshSelects();
    }

    function smPopulate(sig) {
        document.getElementById('smSignatoryId').value = sig.signatory_id || '';
        smFieldIds.forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            var val = sig[smFieldToName[id]];
            el.value = (val === null || val === undefined) ? '' : val;
        });

        applyDegreeValue(sig.degree || '');
        smRefreshSelects();

        var wrap = document.getElementById('smCurrentSignatureWrap');
        var img  = document.getElementById('smCurrentSignaturePreview');
        if (sig.signature_url) {
            img.src = sig.signature_url + '?t=' + Date.now();
            wrap.style.display = '';
        } else {
            wrap.style.display = 'none';
        }
    }

    var smDegreeInput = document.getElementById('smDegree');
    var smDegreeOther = document.getElementById('smDegreeOther');

    function knownDegreeOption(value) {
        if (!smDegreeInput) return false;
        return Array.from(smDegreeInput.options).some(function (o) { return o.value === value; });
    }

    function applyDegreeValue(value) {
        if (!smDegreeInput || !smDegreeOther) return;
        if (value && !knownDegreeOption(value)) {
            smDegreeInput.value = 'Other';
            smDegreeOther.value  = value;
            smDegreeOther.style.display = 'block';
        } else if (value === 'Other') {
            smDegreeOther.value = '';
            smDegreeOther.style.display = 'block';
        } else {
            smDegreeOther.value = '';
            smDegreeOther.style.display = 'none';
        }
    }

    smDegreeInput && smDegreeInput.addEventListener('change', function () {
        if (smDegreeInput.value === 'Other') {
            smDegreeOther.style.display = 'block';
            smDegreeOther.focus();
        } else {
            smDegreeOther.style.display = 'none';
            smDegreeOther.value = '';
        }
    });

    function smOpen(mode, sigId) {
        smClearAlert();
        smInitSelects();
        smResetForm();

        if (mode === 'add') {
            sigModalTitle.textContent = 'Add Signatory';
            smSubmitText.textContent = 'Save';
            sigModal.style.display = 'flex';
            return;
        }

        sigModalTitle.textContent = 'Edit Signatory';
        smSubmitText.textContent = 'Update';
        sigModal.style.display = 'flex';

        fetch(sigFetchUrl + '/' + sigId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    smShowAlert(data.message || 'Failed to load signatory.', 'error');
                    return;
                }
                smPopulate(data.signatory);
            })
            .catch(function () {
                smShowAlert('Failed to load signatory.', 'error');
            });
    }

    function smClose() { sigModal.style.display = 'none'; }

    btnAddSig       && btnAddSig.addEventListener('click', function () { smOpen('add'); });
    sigModalClose   && sigModalClose.addEventListener('click', smClose);
    sigModalCancel  && sigModalCancel.addEventListener('click', smClose);
    sigModal        && sigModal.addEventListener('click', function (e) {
        if (e.target === sigModal) smClose();
    });

    document.querySelectorAll('.js-sig-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            smOpen('edit', btn.getAttribute('data-id'));
        });
    });

    sigModalForm && sigModalForm.addEventListener('submit', function (e) {
        e.preventDefault();
        smClearAlert();

        var fd = new FormData(sigModalForm);
        var csrf = getCsrf();
        if (csrf.name && !fd.get(csrf.name)) {
            fd.append(csrf.name, csrf.token);
        }

        sigSubmitBtn.disabled = true;
        smSubmitText.style.display = 'none';
        smSubmitSpinner.style.display = 'inline-block';

        fetch(sigSaveUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    smClose();
                    location.reload();
                    return;
                }
                smShowAlert(data.message || 'Save failed.', 'error', data.errors);
            })
            .catch(function () {
                smShowAlert('An error occurred while saving.', 'error');
            })
            .finally(function () {
                sigSubmitBtn.disabled = false;
                smSubmitText.style.display = 'inline';
                smSubmitSpinner.style.display = 'none';
            });
    });

    // ── Checkbox + Action Bar ─────────────────────────────────────────────────
    var selectedIds = new Set();
    var actionBar   = document.getElementById('sigActionBar');
    var countLabel  = document.getElementById('sigSelectedCount');

    // Only enabled (i.e. non-archived) checkboxes participate in bulk archive.
    function getSelectableCheckboxes() {
        return Array.prototype.filter.call(
            document.querySelectorAll('.sig-row-check'),
            function (cb) { return !cb.disabled; }
        );
    }

    function updateActionBar() {
        if (countLabel) countLabel.textContent = selectedIds.size;
        if (actionBar)  actionBar.style.display = selectedIds.size > 0 ? 'flex' : 'none';
        updateSelectAllBanner();
    }

    function updateSelectAllBanner() {
        var banner       = document.getElementById('sigSelectAllBanner');
        var bannerText   = document.getElementById('sigSelectAllBannerText');
        var selectLink   = document.getElementById('sigSelectAllMatchingLink');
        var clearLink    = document.getElementById('sigClearLink');
        if (!banner) return;

        var selSize = selectedIds.size;
        if (selSize === 0) { banner.style.display = 'none'; return; }
        banner.style.display = '';

        var totalFiltered = 0;
        var allFilteredIds = [];
        if (window.jQuery && $.fn.DataTable) {
            var tbl = document.getElementById('signatoriesTable');
            if (tbl && $.fn.DataTable.isDataTable(tbl)) {
                var dt = $(tbl).DataTable();
                totalFiltered = dt.rows({ search: 'applied' }).count();
                dt.rows({ search: 'applied' }).every(function () {
                    var node = this.node();
                    var cb = node ? node.querySelector('.sig-row-check') : null;
                    if (cb && !cb.disabled) allFilteredIds.push(cb.value);
                });
            }
        }

        if (bannerText) bannerText.textContent = selSize + ' selected. ' + totalFiltered + ' total matching.';
        var allSelected = allFilteredIds.length > 0 && allFilteredIds.every(function (id) { return selectedIds.has(id); });
        if (selectLink) {
            selectLink.textContent = 'Select all ' + allFilteredIds.length + ' matching across all pages';
            selectLink.style.display = allSelected ? 'none' : '';
        }
        if (clearLink) clearLink.style.display = '';
    }

    function updateCheckAllState(pageIds) {
        var checkAll = document.getElementById('sigCheckAll');
        if (!checkAll) return;
        var n = pageIds.filter(function (id) { return selectedIds.has(id); }).length;
        checkAll.checked       = false;
        checkAll.indeterminate = n > 0;
    }

    function getPageIds() {
        if (!window.jQuery || !$.fn.DataTable) return [];
        var tbl = document.getElementById('signatoriesTable');
        if (!tbl || !$.fn.DataTable.isDataTable(tbl)) return [];
        var ids = [];
        $(tbl).DataTable().rows({ page: 'current' }).nodes().each(function (row) {
            var cb = row.querySelector('.sig-row-check');
            if (cb && !cb.disabled) ids.push(cb.value);
        });
        return ids;
    }

    function syncPageCheckboxes() {
        if (!window.jQuery || !$.fn.DataTable) return;
        var tbl = document.getElementById('signatoriesTable');
        if (!tbl || !$.fn.DataTable.isDataTable(tbl)) return;
        var pageIds = [];
        $(tbl).DataTable().rows({ page: 'current' }).nodes().each(function (row) {
            var cb = row.querySelector('.sig-row-check');
            if (!cb) return;
            cb.checked = selectedIds.has(cb.value);
            row.classList.toggle('vs-row-selected', cb.checked);
            if (!cb.disabled) pageIds.push(cb.value);
        });
        updateCheckAllState(pageIds);
        updateActionBar();
    }

    var _sigTbl = document.getElementById('signatoriesTable');
    if (_sigTbl && window.jQuery) $(_sigTbl).on('draw.dt', syncPageCheckboxes);

    var checkAll = document.getElementById('sigCheckAll');
    checkAll && checkAll.addEventListener('change', function () {
        var tbl = document.getElementById('signatoriesTable');
        var currentNodes = (window.jQuery && tbl && $.fn.DataTable.isDataTable(tbl))
            ? $(tbl).DataTable().rows({ page: 'current' }).nodes().toArray()
            : getSelectableCheckboxes().map(function (cb) { return cb.closest('tr'); });

        var pageIds = [];
        currentNodes.forEach(function (row) {
            var cb = row.querySelector('.sig-row-check');
            if (cb && !cb.disabled) pageIds.push(cb.value);
        });

        var anyOnPageSelected = pageIds.some(function (id) { return selectedIds.has(id); });
        pageIds.forEach(function (id) {
            if (anyOnPageSelected) selectedIds.delete(id);
            else selectedIds.add(id);
        });
        syncPageCheckboxes();
        updateActionBar();
    });

    document.querySelectorAll('.sig-row-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (cb.checked) selectedIds.add(cb.value);
            else selectedIds.delete(cb.value);
            cb.closest('tr').classList.toggle('vs-row-selected', cb.checked);
            updateCheckAllState(getPageIds());
            updateActionBar();
        });
    });

    // ── Select-all-matching + Clear links ────────────────────────────────────
    var sigSelectAllLink = document.getElementById('sigSelectAllMatchingLink');
    var sigClearLink     = document.getElementById('sigClearLink');

    sigSelectAllLink && sigSelectAllLink.addEventListener('click', function (e) {
        e.preventDefault();
        if (!window.jQuery || !$.fn.DataTable) return;
        var tbl = document.getElementById('signatoriesTable');
        if (!tbl || !$.fn.DataTable.isDataTable(tbl)) return;
        $(tbl).DataTable().rows({ search: 'applied' }).every(function () {
            var node = this.node();
            var cb = node ? node.querySelector('.sig-row-check') : null;
            if (cb && !cb.disabled) selectedIds.add(cb.value);
        });
        syncPageCheckboxes();
        updateActionBar();
    });

    sigClearLink && sigClearLink.addEventListener('click', function (e) {
        e.preventDefault();
        selectedIds.clear();
        syncPageCheckboxes();
        updateActionBar();
    });

    // ── Restore (unarchive) ───────────────────────────────────────────────────
    function wireSigRestoreBtn(btn) {
        btn.addEventListener('click', function () {
            var id   = btn.getAttribute('data-id');
            var csrf = getCsrf();
            btn.disabled = true;
            fetch('<?= base_url('signatories/restore') ?>/' + id, {
                method:  'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    csrf.name + '=' + csrf.token,
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    showAlert(data.message || 'Failed.', 'error');
                    btn.disabled = false;
                    return;
                }
                window.location.reload();
            })
            .catch(function () {
                showAlert('An error occurred.', 'error');
                btn.disabled = false;
            });
        });
    }
    document.querySelectorAll('.sig-restore-btn').forEach(wireSigRestoreBtn);

    // ── Bulk archive ─────────────────────────────────────────────────────────
    var btnArchive     = document.getElementById('btnArchiveSelected');
    var sigArchModal   = document.getElementById('sigArchiveModal');
    var sigArchConfirm = document.getElementById('sigArchiveConfirm');
    var sigArchCancel  = document.getElementById('sigArchiveModalCancel');
    var sigArchClose   = document.getElementById('sigArchiveModalClose');
    var sigArchCount   = document.getElementById('sigArchiveCount');
    var sigArchBtnText = document.getElementById('sigArchiveBtnText');
    var sigArchSpinner = document.getElementById('sigArchiveBtnSpinner');

    var pendingSigArchiveId = null;

    function closeSigArchModal() {
        if (sigArchModal) sigArchModal.style.display = 'none';
        pendingSigArchiveId = null;
    }

    sigArchClose  && sigArchClose.addEventListener('click', closeSigArchModal);
    sigArchCancel && sigArchCancel.addEventListener('click', closeSigArchModal);
    sigArchModal  && sigArchModal.addEventListener('click', function (e) {
        if (e.target === sigArchModal) closeSigArchModal();
    });

    btnArchive && btnArchive.addEventListener('click', function () {
        if (!selectedIds.size) return;
        if (sigArchCount) sigArchCount.textContent = selectedIds.size;
        if (sigArchModal) sigArchModal.style.display = 'flex';
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-sig-archive-single');
        if (!btn) return;
        var id = btn.getAttribute('data-id');
        pendingSigArchiveId = id;
        if (sigArchCount) sigArchCount.textContent = '1';
        if (sigArchModal) sigArchModal.style.display = 'flex';
    });

    function sigDtRedraw() {
        if (!window.jQuery || !$.fn.DataTable) return;
        var tbl = document.getElementById('signatoriesTable');
        if (tbl && $.fn.DataTable.isDataTable(tbl)) $(tbl).DataTable().draw(false);
    }

    function applySigArchiveDom(id) {
        var row = document.getElementById('sig-row-' + id);
        if (!row) return;
        row.classList.add('vs-row-archived');
        row.setAttribute('data-archived', '1');
        // Col 7 (is_active sort) is hidden — use DT API so Actions cell isn't clobbered.
        if (window.jQuery && $.fn.DataTable) {
            var tbl = document.getElementById('signatoriesTable');
            if (tbl && $.fn.DataTable.isDataTable(tbl)) {
                $(tbl).DataTable().cell(row, 7).data('0');
            }
        }
        var cb = row.querySelector('.sig-row-check');
        if (cb) { cb.disabled = true; cb.checked = false; }
        var statusBadge = document.getElementById('sig-status-' + id);
        if (statusBadge) { statusBadge.textContent = 'Inactive'; statusBadge.className = 'badge bg-danger'; }
        var ul = row.querySelector('.actions-cell .dropdown-menu');
        if (ul) {
            ul.innerHTML =
                '<li><button type="button" class="dropdown-item js-sig-edit" data-id="' + id + '">Edit</button></li>' +
                '<li><hr class="dropdown-divider"></li>' +
                '<li><button class="dropdown-item sig-restore-btn" data-id="' + id + '" id="sig-restore-' + id + '">Activate</button></li>';
            ul.querySelector('.js-sig-edit').addEventListener('click', function () { smOpen('edit', id); });
            wireSigRestoreBtn(ul.querySelector('.sig-restore-btn'));
        }
    }

    sigArchConfirm && sigArchConfirm.addEventListener('click', function () {
        var csrf = getCsrf();
        var body = csrf.name + '=' + csrf.token;
        var idsToArchive = pendingSigArchiveId ? [pendingSigArchiveId] : Array.from(selectedIds);
        idsToArchive.forEach(function (id) { body += '&ids[]=' + id; });

        sigArchConfirm.disabled = true;
        if (sigArchBtnText) sigArchBtnText.style.display = 'none';
        if (sigArchSpinner) sigArchSpinner.style.display = 'inline-block';

        fetch('<?= base_url('signatories/archive-multiple') ?>', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                closeSigArchModal();
                idsToArchive.forEach(function (id) {
                    applySigArchiveDom(id);
                    selectedIds.delete(id);
                });
                sigDtRedraw();
                updateActionBar();
                showAlert(data.message || 'Deactivated successfully.', 'success');
            } else {
                showAlert(data.message || 'Failed to deactivate.', 'error');
                closeSigArchModal();
            }
        })
        .catch(function () {
            showAlert('An error occurred.', 'error');
            closeSigArchModal();
        })
        .finally(function () {
            sigArchConfirm.disabled = false;
            if (sigArchBtnText) sigArchBtnText.style.display = 'inline';
            if (sigArchSpinner) sigArchSpinner.style.display = 'none';
        });
    });
});

// Position options are server-rendered from DB — no JS population needed.
</script>

<?= $this->endSection() ?>
