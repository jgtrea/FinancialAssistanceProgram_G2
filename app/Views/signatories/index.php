<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">Signatories</h4>
            <p class="vs-page-sub">Manage active voucher signatories.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('/signatories/form') ?>" class="vs-btn vs-btn-primary">
                <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
                Add Signatory
            </a>
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
            Archive Selected
        </button>
    </div>

    <div class="vs-card">
        <div class="vs-card-body">
            <table id="signatoriesTable" class="vs-datatable js-data-table" data-search-placeholder="Search signatories..." style="width:100%">
            <thead>
                <tr>
                    <th class="vs-th-check"><input type="checkbox" id="sigCheckAll" aria-label="Select all"></th>
                    <th>Full Name</th>
                    <th>Position Title</th>
                    <th>Signature</th>
                    <th>Selected</th>
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
                        <td><input type="checkbox" class="sig-row-check" value="<?= $sid ?>"></td>
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
                        <td>
                            <span class="badge <?= $isSelected ? 'bg-success' : 'bg-secondary' ?>"
                                  id="sig-badge-<?= $sid ?>">
                                <?= $isSelected ? 'Selected' : 'Unselected' ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <a href="<?= base_url('/signatories/form/' . $sid) ?>"
                               class="vs-tbl-btn vs-tbl-btn-edit">
                                Edit
                            </a>
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
        document.querySelectorAll('.sig-row-check').forEach(function (cb) {
            cb.checked = checkAll.checked;
            if (checkAll.checked) selectedIds.add(cb.value);
            else selectedIds.delete(cb.value);
        });
        updateActionBar();
    });

    document.querySelectorAll('.sig-row-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (cb.checked) selectedIds.add(cb.value);
            else selectedIds.delete(cb.value);
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
    var btnArchive = document.getElementById('btnArchiveSelected');
    btnArchive && btnArchive.addEventListener('click', function () {
        if (!selectedIds.size) return;
        if (!confirm('Archive ' + selectedIds.size + ' signatory(ies)?')) return;

        var csrf = getCsrf();
        var body = csrf.name + '=' + csrf.token;
        selectedIds.forEach(function (id) { body += '&ids[]=' + id; });

        btnArchive.disabled = true;

        fetch('<?= base_url('signatories/archive-multiple') ?>', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                selectedIds.forEach(function (id) {
                    var row = document.getElementById('sig-row-' + id);
                    if (row) row.remove();
                });
                selectedIds.clear();
                updateActionBar();
                showAlert(data.message || 'Archived successfully.', 'success');
            } else {
                showAlert(data.message || 'Failed to archive.', 'error');
            }
            btnArchive.disabled = false;
        })
        .catch(function () {
            showAlert('An error occurred.', 'error');
            btnArchive.disabled = false;
        });
    });
}());
</script>

<?= $this->endSection() ?>
