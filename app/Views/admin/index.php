<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">User Management</h4>
            <p class="vs-page-sub">Manage staff accounts and system access.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/user_management/form') ?>" class="vs-btn vs-btn-primary">
                <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
                Add User
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="vs-alert vs-alert-success mb-3">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="vs-alert vs-alert-error mb-3">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div id="userAlertBox"></div>

    <!-- Action bar — appears when rows are checked -->
    <div class="vs-action-bar" id="userActionBar" style="display:none">
        <span class="vs-action-bar-count"><span id="userSelectedCount">0</span> selected</span>
        <div class="ms-auto">
            <button class="vs-btn vs-btn-danger" id="btnArchiveUsers">
                <?= asset_icon('archive') ?>
                Archive Selected
            </button>
        </div>
    </div>

    <div class="vs-card">
        <div class="vs-card-body">
            <table id="userManagementTable" class="vs-datatable js-data-table" data-search-placeholder="Search users..." style="width:100%">
            <thead>
                <tr>
                    <th class="vs-th-check"><input type="checkbox" id="userCheckAll" aria-label="Select all"></th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Last Login</th>
                    <th class="actions-column">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr id="user-row-<?= (int) $user['user_id'] ?>">
                        <td><input type="checkbox" class="user-row-check" value="<?= (int) $user['user_id'] ?>"></td>
                        <td><?= esc($user['username']) ?></td>
                        <td><?= esc($user['email']) ?></td>
                        <td>
                            <?php
                                $roleColors = ['admin' => '#1a5c2e', 'staff' => '#2e9e52', 'viewer' => '#6c757d'];
                                $roleColor  = $roleColors[$user['role']] ?? '#6c757d';
                            ?>
                            <span class="badge" style="background-color:<?= $roleColor ?>">
                                <?= esc(ucfirst($user['role'])) ?>
                            </span>
                        </td>
                        <td><?= !empty($user['last_login']) ? esc(date('M d, Y h:i A', strtotime($user['last_login']))) : 'Never' ?></td>
                        <td class="actions-cell">
                            <a href="<?= base_url('admin/user_management/form/' . $user['user_id']) ?>" class="vs-tbl-btn vs-tbl-btn-edit">
                                Edit
                            </a>
                            <button class="vs-tbl-btn vs-tbl-btn-delete user-archive-btn"
                                    data-id="<?= (int) $user['user_id'] ?>">
                                Archive
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
        return { name: csrfName, token: meta ? meta.getAttribute('content') : csrfHash };
    }

    function showAlert(msg, type) {
        var box = document.getElementById('userAlertBox');
        box.innerHTML = '<div class="vs-alert vs-alert-' + (type || 'success') + ' mb-3">' + msg + '</div>';
        setTimeout(function () { box.innerHTML = ''; }, 4000);
    }

    var selectedIds = new Set();
    var actionBar   = document.getElementById('userActionBar');
    var countLabel  = document.getElementById('userSelectedCount');

    function updateBar() {
        if (countLabel) countLabel.textContent = selectedIds.size;
        if (actionBar)  actionBar.style.display = selectedIds.size > 0 ? 'flex' : 'none';
        var checkAll = document.getElementById('userCheckAll');
        if (checkAll) {
            var all = document.querySelectorAll('.user-row-check');
            checkAll.checked       = all.length > 0 && selectedIds.size >= all.length;
            checkAll.indeterminate = selectedIds.size > 0 && selectedIds.size < all.length;
        }
    }

    var checkAll = document.getElementById('userCheckAll');
    checkAll && checkAll.addEventListener('change', function () {
        document.querySelectorAll('.user-row-check').forEach(function (cb) {
            cb.checked = checkAll.checked;
            if (checkAll.checked) selectedIds.add(cb.value);
            else selectedIds.delete(cb.value);
        });
        updateBar();
    });

    document.querySelectorAll('.user-row-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (cb.checked) selectedIds.add(cb.value);
            else selectedIds.delete(cb.value);
            updateBar();
        });
    });

    var btnArchive = document.getElementById('btnArchiveUsers');
    btnArchive && btnArchive.addEventListener('click', function () {
        if (!selectedIds.size) return;
        if (!confirm('Archive ' + selectedIds.size + ' user(s)?')) return;

        var csrf = getCsrf();
        var body = csrf.name + '=' + csrf.token;
        selectedIds.forEach(function (id) { body += '&ids[]=' + id; });

        btnArchive.disabled = true;

        fetch('<?= base_url('admin/user_management/archive-multiple') ?>', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status === 'success') {
                selectedIds.forEach(function (id) {
                    var row = document.getElementById('user-row-' + id);
                    if (row) row.remove();
                });
                selectedIds.clear();
                updateBar();
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

    // ── Per-row Archive button ────────────────────────────────────────────────
    document.querySelectorAll('.user-archive-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-id');
            if (!confirm('Archive this user?')) return;

            var csrf = getCsrf();
            btn.disabled = true;

            fetch('<?= base_url('admin/user_management/archive') ?>/' + id, {
                method:  'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    csrf.name + '=' + csrf.token,
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    var row = document.getElementById('user-row-' + id);
                    if (row) row.remove();
                    selectedIds.delete(id);
                    updateBar();
                    showAlert(data.message || 'User archived.', 'success');
                } else {
                    showAlert(data.message || 'Failed.', 'error');
                    btn.disabled = false;
                }
            })
            .catch(function () {
                showAlert('An error occurred.', 'error');
                btn.disabled = false;
            });
        });
    });
}());
</script>

<?= $this->endSection() ?>
