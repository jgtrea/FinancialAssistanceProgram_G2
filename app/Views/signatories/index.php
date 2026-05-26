<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $prefixOptions = $prefixOptions ?? ['', 'DR.', 'ENGR.', 'HON.', 'MR.', 'MRS.', 'MS.', 'PROF.'];
    $suffixOptions = $suffixOptions ?? ['', 'JR.', 'SR.', 'II', 'III', 'IV', 'V', 'CPA', 'LPT', 'MD', 'PHD'];
?>

<div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">Signatories</h4>
            <p class="vs-page-sub">Manage active voucher signatories.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="vs-btn vs-btn-primary" id="btnAddSignatory">
                <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
                Add Signatory
            </button>
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
            Archive
        </button>
    </div>

    <div class="vs-card">
        <div class="vs-card-body">
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <input type="text" id="customSigSearch" class="vs-input" placeholder="Search signatories..." style="max-width:340px">
                <button type="button" class="vs-btn vs-btn-outline" id="btnOpenSigFilter">
                    Filters
                    <span id="sigFilterBadge" class="badge bg-primary" style="display:none;margin-left:.35rem"></span>
                </button>
                <label class="vs-length-label ms-auto">Show <input type="number" id="sigLengthInput" class="vs-length-input" value="10" min="1" max="500"> entries</label>
            </div>
            <table id="signatoriesTable" class="vs-datatable js-data-table" data-search-placeholder="Search signatories..." style="width:100%">
            <thead>
                <tr>
                    <th class="vs-th-check"><input type="checkbox" class="vs-check" id="sigCheckAll" aria-label="Select all"></th>
                    <th>Full Name</th>
                    <th>Position Title</th>
                    <th data-orderable="false">Signature</th>
                    <th data-orderable="false">Selected</th>
                    <th class="actions-column actions-column--sm">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($signatories as $signatory): ?>
                    <?php
                        $parts = array_filter([
                            $signatory['prefix'] ?? '',
                            $signatory['first_name'],
                            $signatory['middle_name'] ?? '',
                            $signatory['last_name'],
                            $signatory['suffix'] ?? '',
                        ]);
                        $fullName = trim(implode(' ', $parts));
                        $isSelected = !empty($signatory['is_selected']);
                        $sid = (int) $signatory['signatory_id'];
                    ?>

                    <tr id="sig-row-<?= $sid ?>">
                        <td><input type="checkbox" class="vs-check sig-row-check" value="<?= $sid ?>"></td>
                        <td><?= esc($fullName) ?></td>
                        <td><?= esc($signatory['position_title']) ?></td>
                        <td>
                            <?php if (!empty($signatory['signature_image'])): ?>
                                <img src="<?= base_url('signatories/signature/' . $sid) ?>"
                                     alt="Signature of <?= esc($fullName) ?>"
                                     style="max-height: 40px; max-width: 140px;">
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $isSelected ? 'bg-success' : 'bg-secondary' ?>" id="sig-badge-<?= $sid ?>"><?= $isSelected ? 'Selected' : 'Unselected' ?></span></td>
                        <td class="actions-cell">
                            <button type="button"
                                    class="vs-tbl-btn vs-tbl-btn-edit js-sig-edit"
                                    data-id="<?= $sid ?>">
                                Edit
                            </button>
                            <button class="vs-tbl-btn <?= $isSelected ? 'vs-tbl-btn-delete' : 'vs-tbl-btn-view' ?> sig-toggle-btn"
                                    data-id="<?= $sid ?>"
                                    data-selected="<?= $isSelected ? '1' : '0' ?>"
                                    id="sig-toggle-<?= $sid ?>">
                                <?= $isSelected ? 'Unselect' : 'Select' ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </div>

<!-- Signatory Archive Confirmation modal -->
<div class="vs-modal-overlay" id="sigArchiveModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5>Archive Signatories</h5>
      <button class="vs-modal-close" id="sigArchiveModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <p>You are about to archive <strong id="sigArchiveCount">0</strong> signatory(ies). This will move them to the archive.</p>
      <label class="vs-label" for="sigArchiveReason">Reason (optional)</label>
      <input type="text" id="sigArchiveReason" class="vs-input" placeholder="e.g. End of term">
    </div>
    <div class="vs-modal-footer">
      <button class="vs-btn vs-btn-outline" id="sigArchiveModalCancel">Cancel</button>
      <button class="vs-btn vs-btn-danger" id="sigArchiveConfirm">
        <span id="sigArchiveBtnText">Confirm Archive</span>
        <span id="sigArchiveBtnSpinner" class="vs-spinner" style="display:none"></span>
      </button>
    </div>
  </div>
</div>

<!-- Signatories Filter modal -->
<div class="vs-modal-overlay" id="sigFilterModal" style="display:none">
  <div class="vs-modal" style="max-width:400px">
    <div class="vs-modal-header">
      <h5>Filter Signatories</h5>
      <button class="vs-modal-close" id="sigFilterClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <div class="d-flex flex-column gap-3">
        <div>
          <label class="vs-label" for="sfStatus">Selected Status</label>
          <select id="sfStatus" class="vs-input">
            <option value="">All</option>
            <option value="selected">Selected</option>
            <option value="unselected">Unselected</option>
          </select>
        </div>
        <div>
          <label class="vs-label" for="sfPosition">Position Title</label>
          <select id="sfPosition" class="vs-input">
            <option value="">All</option>
          </select>
        </div>
      </div>
    </div>
    <div class="vs-modal-footer">
      <button type="button" class="vs-btn vs-btn-outline" id="sigFilterClear">Clear All</button>
      <button type="button" class="vs-btn vs-btn-outline" id="sigFilterCancel">Cancel</button>
      <button type="button" class="vs-btn vs-btn-primary" id="sigFilterApply">Apply</button>
    </div>
  </div>
</div>

<!-- Signatory Add/Edit modal -->
<div class="vs-modal-overlay" id="signatoryModal" style="display:none">
  <div class="vs-modal" style="max-width:780px">
    <div class="vs-modal-header">
      <h5 id="signatoryModalTitle">Add Signatory</h5>
      <button class="vs-modal-close" id="signatoryModalClose">&times;</button>
    </div>
    <form id="signatoryModalForm" novalidate enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="signatory_id" id="smSignatoryId" value="">

      <div class="vs-modal-body">
        <div id="signatoryModalAlert"></div>

        <div class="vs-form-grid vs-form-grid-4">
          <div>
            <label class="vs-label" for="smPrefix">Prefix</label>
            <select id="smPrefix" name="prefix" class="vs-input">
              <?php foreach ($prefixOptions as $option): ?>
                <option value="<?= esc($option) ?>"><?= $option === '' ? '-- Select --' : esc($option) ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div>
            <label class="vs-label required" for="smFirstName">First Name</label>
            <input id="smFirstName" name="first_name" type="text" class="vs-input" required>
          </div>

          <div>
            <label class="vs-label" for="smMiddleName">Middle Name</label>
            <input id="smMiddleName" name="middle_name" type="text" class="vs-input">
          </div>

          <div>
            <label class="vs-label required" for="smLastName">Last Name</label>
            <input id="smLastName" name="last_name" type="text" class="vs-input" required>
          </div>

          <div>
            <label class="vs-label" for="smSuffix">Suffix</label>
            <select id="smSuffix" name="suffix" class="vs-input">
              <?php foreach ($suffixOptions as $option): ?>
                <option value="<?= esc($option) ?>"><?= $option === '' ? '-- Select --' : esc($option) ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div class="vs-span-2">
            <label class="vs-label required" for="smPositionTitle">Position Title</label>
            <input id="smPositionTitle" name="position_title" type="text" class="vs-input" required>
          </div>

          <div style="grid-column: 1 / -1">
            <label class="vs-label" for="smSignatureImage">Signature Image</label>
            <input id="smSignatureImage" name="signature_image" type="file" class="vs-input" accept="image/png,image/jpeg,image/jpg,image/webp">
            <small class="text-muted">PNG, JPG, or WEBP — max 2 MB. Leave empty to keep the current image.</small>

            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="auto_remove_bg" value="1" id="smAutoRemoveBg" checked>
              <label class="form-check-label" for="smAutoRemoveBg">
                Remove background automatically (best for signatures on plain white paper)
              </label>
            </div>

            <div id="smCurrentSignatureWrap" class="mt-3" style="display:none">
              <p class="vs-label mb-1">Current Signature</p>
              <img id="smCurrentSignaturePreview" src="" alt="Current signature"
                   style="max-height:80px;background:#fff;padding:4px;border:1px solid #ddd;border-radius:4px;">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="remove_signature" value="1" id="smRemoveSignature">
                <label class="form-check-label" for="smRemoveSignature">Remove current signature</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="vs-modal-footer">
        <button type="button" class="vs-btn vs-btn-outline" id="signatoryModalCancel">Close</button>
        <button type="submit" class="vs-btn vs-btn-primary" id="signatoryModalSubmit">
          <span id="smSubmitText">Save</span>
          <span id="smSubmitSpinner" class="vs-spinner" style="display:none"></span>
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
        var box = document.getElementById('sigAlertBox');
        box.innerHTML = '<div class="vs-alert vs-alert-' + (type || 'success') + ' mb-3">' + msg + '</div>';
        setTimeout(function () { box.innerHTML = ''; }, 4000);
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

    var smFieldIds = ['smPrefix', 'smFirstName', 'smMiddleName', 'smLastName', 'smSuffix', 'smPositionTitle'];
    var smFieldToName = {
        smPrefix:        'prefix',
        smFirstName:     'first_name',
        smMiddleName:    'middle_name',
        smLastName:      'last_name',
        smSuffix:        'suffix',
        smPositionTitle: 'position_title',
    };

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
        html += '</div>';
        sigModalAlert.innerHTML = html;
    }
    function smClearAlert() { sigModalAlert.innerHTML = ''; }

    function smResetForm() {
        sigModalForm.reset();
        document.getElementById('smSignatoryId').value = '';
        document.getElementById('smCurrentSignatureWrap').style.display = 'none';
        document.getElementById('smRemoveSignature').checked = false;
        document.getElementById('smAutoRemoveBg').checked = true;
    }

    function smPopulate(sig) {
        document.getElementById('smSignatoryId').value = sig.signatory_id || '';
        smFieldIds.forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            var val = sig[smFieldToName[id]];
            el.value = (val === null || val === undefined) ? '' : val;
        });

        var wrap = document.getElementById('smCurrentSignatureWrap');
        var img  = document.getElementById('smCurrentSignaturePreview');
        if (sig.signature_url) {
            img.src = sig.signature_url + '?t=' + Date.now();
            wrap.style.display = '';
        } else {
            wrap.style.display = 'none';
        }
    }

    function smOpen(mode, sigId) {
        smClearAlert();
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

    function updateActionBar() {
        if (countLabel) countLabel.textContent = selectedIds.size;
        if (actionBar)  actionBar.style.display = selectedIds.size > 0 ? 'flex' : 'none';

        var checkAll = document.getElementById('sigCheckAll');
        if (checkAll) {
            var all = document.querySelectorAll('.sig-row-check');
            checkAll.checked = all.length > 0 && selectedIds.size >= all.length;
            checkAll.indeterminate = selectedIds.size > 0 && selectedIds.size < all.length;
        }
    }

    var checkAll = document.getElementById('sigCheckAll');
    checkAll && checkAll.addEventListener('change', function () {
        var shouldCheck = selectedIds.size === 0;
        document.querySelectorAll('.sig-row-check').forEach(function (cb) {
            cb.checked = shouldCheck;
            if (shouldCheck) selectedIds.add(cb.value);
            else selectedIds.delete(cb.value);
            cb.closest('tr').classList.toggle('vs-row-selected', shouldCheck);
        });
        updateActionBar();
    });

    document.querySelectorAll('.sig-row-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (cb.checked) selectedIds.add(cb.value);
            else selectedIds.delete(cb.value);
            cb.closest('tr').classList.toggle('vs-row-selected', cb.checked);
            updateActionBar();
        });
    });

    // ── Select / Unselect toggle ──────────────────────────────────────────────
    document.querySelectorAll('.sig-toggle-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id       = btn.getAttribute('data-id');
            var selected = btn.getAttribute('data-selected') === '1';
            var action   = selected ? 'deselect' : 'select';
            var csrf     = getCsrf();

            btn.disabled = true;

            fetch('<?= base_url('signatories/status') ?>/' + id + '/' + action, {
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

                var nowSelected = data.selected;
                var badge = document.getElementById('sig-badge-' + id);

                btn.setAttribute('data-selected', nowSelected ? '1' : '0');
                btn.textContent = nowSelected ? 'Unselect' : 'Select';
                btn.className   = 'vs-tbl-btn ' + (nowSelected ? 'vs-tbl-btn-delete' : 'vs-tbl-btn-view') + ' sig-toggle-btn';

                if (badge) {
                    badge.textContent = nowSelected ? 'Selected' : 'Unselected';
                    badge.className   = 'badge ' + (nowSelected ? 'bg-success' : 'bg-secondary');
                }

                /* Refresh CSRF for next request */
                var metaEl = document.querySelector('meta[name="csrf-token-value"]');
                if (metaEl && data.csrf_token) metaEl.setAttribute('content', data.csrf_token);

                btn.disabled = false;
            })
            .catch(function () {
                showAlert('An error occurred.', 'error');
                btn.disabled = false;
            });
        });
    });

    // ── Bulk archive ─────────────────────────────────────────────────────────
    var btnArchive     = document.getElementById('btnArchiveSelected');
    var sigArchModal   = document.getElementById('sigArchiveModal');
    var sigArchConfirm = document.getElementById('sigArchiveConfirm');
    var sigArchCancel  = document.getElementById('sigArchiveModalCancel');
    var sigArchClose   = document.getElementById('sigArchiveModalClose');
    var sigArchCount   = document.getElementById('sigArchiveCount');
    var sigArchReason  = document.getElementById('sigArchiveReason');
    var sigArchBtnText = document.getElementById('sigArchiveBtnText');
    var sigArchSpinner = document.getElementById('sigArchiveBtnSpinner');

    function closeSigArchModal() {
        if (sigArchModal) sigArchModal.style.display = 'none';
        if (sigArchReason) sigArchReason.value = '';
    }

    sigArchClose  && sigArchClose.addEventListener('click', closeSigArchModal);
    sigArchCancel && sigArchCancel.addEventListener('click', closeSigArchModal);
    sigArchModal  && sigArchModal.addEventListener('click', function (e) {
        if (e.target === sigArchModal) closeSigArchModal();
    });

    btnArchive && btnArchive.addEventListener('click', function () {
        if (!selectedIds.size) return;

        var hasSelected = false;
        selectedIds.forEach(function (id) {
            var toggle = document.getElementById('sig-toggle-' + id);
            if (toggle && toggle.getAttribute('data-selected') === '1') hasSelected = true;
        });
        if (hasSelected) {
            showAlert('Cannot archive a signatory that is currently selected. Please unselect it first.', 'error');
            return;
        }

        if (sigArchCount) sigArchCount.textContent = selectedIds.size;
        if (sigArchModal) sigArchModal.style.display = 'flex';
    });

    sigArchConfirm && sigArchConfirm.addEventListener('click', function () {
        var reason = sigArchReason ? sigArchReason.value.trim() : '';
        var csrf = getCsrf();
        var body = csrf.name + '=' + csrf.token;
        selectedIds.forEach(function (id) { body += '&ids[]=' + id; });
        if (reason) body += '&reason=' + encodeURIComponent(reason);

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
                selectedIds.forEach(function (id) {
                    var row = document.getElementById('sig-row-' + id);
                    if (row) row.remove();
                });
                selectedIds.clear();
                updateActionBar();
                showAlert(data.message || 'Archived successfully.', 'success');
            } else {
                showAlert(data.message || 'Failed to archive.', 'error');
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
}());

// ── Custom search + filter for signatories table ──────────────────────
(function initSigSearch() {
    var table = document.getElementById('signatoriesTable');
    if (!table || !window.jQuery || !$.fn.DataTable.isDataTable(table)) {
        return setTimeout(initSigSearch, 50);
    }
    var dt = $(table).DataTable();
    var dtWrap = table.closest('.dataTables_wrapper');

    var dtSearch = dtWrap ? dtWrap.querySelector('.dataTables_filter') : null;
    if (dtSearch) dtSearch.style.display = 'none';

    var dtLength = dtWrap ? dtWrap.querySelector('.dataTables_length') : null;
    if (dtLength) dtLength.style.display = 'none';

    var lenInput = document.getElementById('sigLengthInput');
    if (lenInput) {
        function applySigLen() {
            var v = parseInt(lenInput.value, 10);
            if (!isNaN(v) && v > 0) dt.page.len(v).draw();
        }
        lenInput.addEventListener('change', applySigLen);
        lenInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') applySigLen(); });
    }

    var searchInput = document.getElementById('customSigSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            dt.search(this.value).draw();
        });
    }

    var filterModal = document.getElementById('sigFilterModal');
    var filterBadge = document.getElementById('sigFilterBadge');

    function openFilter()  { if (filterModal) filterModal.style.display = 'flex'; }
    function closeFilter() { if (filterModal) filterModal.style.display = 'none'; }

    var btnOpen   = document.getElementById('btnOpenSigFilter');
    var btnClose  = document.getElementById('sigFilterClose');
    var btnCancel = document.getElementById('sigFilterCancel');
    var btnClear  = document.getElementById('sigFilterClear');
    var btnApply  = document.getElementById('sigFilterApply');
    var sfStatus  = document.getElementById('sfStatus');
    var sfPosition = document.getElementById('sfPosition');

    // Populate Position Title dropdown from table data
    if (sfPosition) {
        var posSet = new Set();
        dt.column(2).data().each(function (val) {
            var p = (val || '').toString().trim();
            if (p) posSet.add(p);
        });
        Array.from(posSet).sort().forEach(function (p) {
            var opt = document.createElement('option');
            opt.value = p; opt.textContent = p;
            sfPosition.appendChild(opt);
        });
    }

    btnOpen   && btnOpen.addEventListener('click', openFilter);
    btnClose  && btnClose.addEventListener('click', closeFilter);
    btnCancel && btnCancel.addEventListener('click', closeFilter);
    filterModal && filterModal.addEventListener('click', function (e) {
        if (e.target === filterModal) closeFilter();
    });

    btnClear && btnClear.addEventListener('click', function () {
        if (sfStatus)   sfStatus.value   = '';
        if (sfPosition) sfPosition.value = '';
        if (filterBadge) { filterBadge.textContent = ''; filterBadge.style.display = 'none'; }
        dt.column(2).search('').column(4).search('').draw();
    });

    btnApply && btnApply.addEventListener('click', function () {
        var statusVal   = sfStatus   ? sfStatus.value   : '';
        var positionVal = sfPosition ? sfPosition.value : '';
        var count = [statusVal, positionVal].filter(Boolean).length;
        if (filterBadge) {
            filterBadge.textContent = count || '';
            filterBadge.style.display = count ? '' : 'none';
        }
        var statusSearch = statusVal === 'selected'   ? '^Selected$'
                         : statusVal === 'unselected' ? '^Unselected$'
                         : '';
        var useRegex = statusSearch !== '';
        dt.column(4).search(statusSearch, useRegex, false)
          .column(2).search(positionVal)
          .draw();
        closeFilter();
    });
}());
</script>

<?= $this->endSection() ?>
