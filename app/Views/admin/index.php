<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="vs-page-header mb-3">
        <div>
            <h4 class="vs-page-title">User Management</h4>
            <p class="vs-page-sub">Manage Staff Accounts And System Access.</p>
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

<form method="get" class="row g-2 align-items-center mb-3">
    <div class="col-12 col-md-3">
        <input type="text" name="q" class="vs-input vs-advanced-search-input w-100" placeholder="Enter keyword to search (name, email)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
    </div>
    <div class="col-6 col-md-2">
        <select id="ufRole" name="role" class="js-filter-select" data-placeholder="ADMIN / USER" data-no-search="1" style="width:100%">
            <option value=""></option>
            <option value="admin" <?= ($filterRole ?? '') === 'admin' ? 'selected' : '' ?>>ADMIN</option>
            <option value="user"  <?= ($filterRole ?? '') === 'user'  ? 'selected' : '' ?>>USER</option>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <select id="ufStatus" name="status" class="js-filter-select" data-placeholder="ACTIVE / INACTIVE" data-no-search="1" style="width:100%">
            <option value=""></option>
            <option value="active"   <?= ($filterStatus ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($filterStatus ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>
    <div class="col-auto d-none d-md-flex align-items-center">
        <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
    </div>
    <div class="col-6 col-md-2 d-flex gap-2">
        <button type="submit" class="vs-btn vs-btn-primary flex-fill">Search</button>
        <a href="<?= site_url('admin/users') ?>" class="vs-btn vs-btn-outline flex-fill" id="userFilterClear">Clear</a>
    </div>
    <div class="col-auto d-none d-md-flex align-items-center">
        <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
    </div>
    <div class="col-6 col-md-2">
        <button type="button" class="vs-btn vs-btn-primary w-100" id="btnAddUser">
            <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
            Add User
        </button>
    </div>
</form>

    <div class="vs-card">
        <div class="vs-card-body">
            <table id="userManagementTable" class="vs-datatable js-data-table vs-mobile-primary" data-mobile-primary="0" data-page-search="customUsersSearch" data-search-placeholder="Search users..." data-order='[[6,"desc"],[1,"asc"]]' data-col-defs='[{"orderable":false,"targets":5},{"visible":false,"targets":6}]' style="width:100%">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="actions-column">Actions</th>
                    <th style="display:none"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php
                        $uid      = (int) $user['user_id'];
                        $isActive = !empty($user['is_active']);
                        $isSelf   = $uid === (int) session()->get('user_id');
                    ?>
                    <tr id="user-row-<?= $uid ?>"
                        data-active="<?= $isActive ? '1' : '0' ?>"
                        data-last-login="<?= !empty($user['last_login']) ? esc(date('Y-m-d', strtotime($user['last_login']))) : '' ?>"
                        <?= !$isActive ? 'class="vs-row-archived"' : '' ?>>
                        <td><?= esc(trim(implode(' ', array_filter([$user['first_name'] ?? '', $user['middle_name'] ?? '', $user['last_name'] ?? ''])))) ?></td>
                        <td><?= esc($user['username'] ?? '') ?></td>
                        <td><?= esc($user['email']) ?></td>
                        <td>
                            <?php
                                $roleColors = ['admin' => '#1e3a8a', 'user' => '#2563eb'];
                                $roleColor  = $roleColors[$user['role']] ?? '#6c757d';
                            ?>
                            <span class="badge" style="background-color:<?= $roleColor ?>"><?= esc(ucfirst($user['role'])) ?></span>
                        </td>
                        <td>
                            <span id="user-status-badge-<?= $uid ?>"
                                  style="color:<?= $isActive ? '#16a34a' : '#9ca3af' ?>;display:inline-flex"
                                  title="<?= $isActive ? 'Active' : 'Inactive' ?>"
                                  aria-label="<?= $isActive ? 'Active' : 'Inactive' ?>">
                                <?= asset_icon($isActive ? 'circle_check' : 'circle_x', ['width' => '18', 'height' => '18']) ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <?php if (!$isSelf): ?>
                            <div class="dropdown">
                                <button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button type="button" class="dropdown-item js-user-edit"
                                                data-id="<?= $uid ?>">Edit</button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button"
                                                class="dropdown-item js-user-toggle<?= $isActive ? ' text-danger' : '' ?>"
                                                data-id="<?= $uid ?>"
                                                data-active="<?= $isActive ? '1' : '0' ?>"
                                                id="user-toggle-<?= $uid ?>">
                                            <?= $isActive ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <?php endif ?>
                        </td>
                        <td style="display:none"><?= $isActive ? '1' : '0' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </div>

<?= pre_modal('users') ?>

<script>
document.addEventListener('vs:modals:ready', function () {
    var csrfName = '<?= csrf_token() ?>';
    var csrfHash = '<?= csrf_hash() ?>';

    var userStatusIcons = {
        active:   <?= json_encode(asset_icon('circle_check', ['width' => '18', 'height' => '18'])) ?>,
        inactive: <?= json_encode(asset_icon('circle_x',     ['width' => '18', 'height' => '18'])) ?>,
    };

    function getCsrf() {
        var meta = document.querySelector('meta[name="csrf-token-value"]');
        return { name: csrfName, token: meta ? meta.getAttribute('content') : csrfHash };
    }

    function showAlert(msg, type) {
        var box = document.getElementById('userAlertBox');
        box.innerHTML = '<div class="vs-alert vs-alert-' + (type || 'success') + ' mb-3">' + msg + '</div>';
        setTimeout(function () { box.innerHTML = ''; }, 4000);
    }

    // ── User Add / Edit modal ──────────────────────────────────────────────
    var userModal       = document.getElementById('userModal');
    var userModalForm   = document.getElementById('userModalForm');
    var userModalTitle  = document.getElementById('userModalTitle');
    var userModalClose  = document.getElementById('userModalClose');
    var userModalCancel = document.getElementById('userModalCancel');
    var userModalAlert  = document.getElementById('userModalAlert');
    var userSubmitBtn   = document.getElementById('userModalSubmit');
    var umSubmitText    = document.getElementById('umSubmitText');
    var umSubmitSpinner = document.getElementById('umSubmitSpinner');
    var btnAddUser      = document.getElementById('btnAddUser');
    var userSaveUrl     = '<?= base_url('admin/user_management/save') ?>';
    var userFetchUrl    = '<?= base_url('admin/user_management/json') ?>';
    var umPasswordLabel = document.getElementById('umPasswordLabel');
    var umPassword      = document.getElementById('umPassword');

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (ch) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[ch];
        });
    }

    function umShowAlert(msg, type, errors) {
        var html = '<div class="vs-alert vs-alert-' + (type || 'error') + ' mb-3">' + escapeHtml(msg);
        if (errors && Object.keys(errors).length) {
            html += '<ul class="mb-0 mt-2">';
            Object.keys(errors).forEach(function (field) {
                html += '<li><strong>' + escapeHtml(field) + ':</strong> ' + escapeHtml(errors[field]) + '</li>';
            });
            html += '</ul>';
        }
        html += '</div>';
        userModalAlert.innerHTML = html;
    }
    function umClearAlert() { userModalAlert.innerHTML = ''; }

    function umResetForm() {
        userModalForm.reset();
        document.getElementById('umUserId').value = '';
    }

    function umPopulate(user) {
        document.getElementById('umUserId').value    = user.user_id    || '';
        document.getElementById('umFirstName').value  = user.first_name  || '';
        document.getElementById('umMiddleName').value = user.middle_name || '';
        document.getElementById('umLastName').value   = user.last_name   || '';
        document.getElementById('umUsername').value   = user.username    || '';
        document.getElementById('umEmail').value     = user.email      || '';
        document.getElementById('umRole').value      = user.role       || 'user';
        umPassword.value = '';
    }

    function umSetPasswordMode(isEdit) {
        if (isEdit) {
            umPasswordLabel.classList.remove('required');
            umPasswordLabel.innerHTML = 'Password <span class="vs-label-hint">(leave blank to keep current)</span>';
            umPassword.required = false;
        } else {
            umPasswordLabel.classList.remove('required');
            umPasswordLabel.innerHTML = 'Password <span class="vs-label-hint">(leave blank for default: pass123)</span>';
            umPassword.required = false;
        }
    }

    function umOpen(mode, userId) {
        umClearAlert();
        umResetForm();

        if (mode === 'add') {
            userModalTitle.textContent = 'Add User';
            umSubmitText.textContent = 'Save User';
            umSetPasswordMode(false);
            userModal.style.display = 'flex';
            return;
        }

        userModalTitle.textContent = 'Edit User';
        umSubmitText.textContent = 'Update User';
        umSetPasswordMode(true);
        userModal.style.display = 'flex';

        fetch(userFetchUrl + '/' + userId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status !== 'success') {
                    umShowAlert(data.message || 'Failed to load user.', 'error');
                    return;
                }
                umPopulate(data.user);
            })
            .catch(function () {
                umShowAlert('Failed to load user.', 'error');
            });
    }

    function umClose() { userModal.style.display = 'none'; }

    btnAddUser       && btnAddUser.addEventListener('click', function () { umOpen('add'); });
    userModalClose   && userModalClose.addEventListener('click', umClose);
    userModalCancel  && userModalCancel.addEventListener('click', umClose);
    userModal        && userModal.addEventListener('click', function (e) {
        if (e.target === userModal) umClose();
    });

    document.querySelectorAll('.js-user-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            umOpen('edit', btn.getAttribute('data-id'));
        });
    });

    userModalForm && userModalForm.addEventListener('submit', function (e) {
        e.preventDefault();
        umClearAlert();

        var fd = new FormData(userModalForm);
        var csrf = getCsrf();
        if (csrf.name && !fd.get(csrf.name)) {
            fd.append(csrf.name, csrf.token);
        }

        userSubmitBtn.disabled = true;
        umSubmitText.style.display = 'none';
        umSubmitSpinner.style.display = 'inline-block';

        fetch(userSaveUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'success') {
                    umClose();
                    location.reload();
                    return;
                }
                umShowAlert(data.message || 'Save failed.', 'error', data.errors);
            })
            .catch(function () {
                umShowAlert('An error occurred while saving.', 'error');
            })
            .finally(function () {
                userSubmitBtn.disabled = false;
                umSubmitText.style.display = 'inline';
                umSubmitSpinner.style.display = 'none';
            });
    });

    // ── Activate / Deactivate toggle ──────────────────────────────────────
    var toggleUrl = '<?= base_url('admin/user_management/toggle') ?>';

    var deactivateOverlay  = document.getElementById('deactivateModal');
    var deactivateConfirm  = document.getElementById('deactivateModalConfirm');
    var deactivateCancel   = document.getElementById('deactivateModalCancel');
    var deactivateClose    = document.getElementById('deactivateModalClose');
    var deactivateBtnText  = document.getElementById('deactivateBtnText');
    var deactivateSpinner  = document.getElementById('deactivateBtnSpinner');
    var _pendingToggleBtn  = null;

    function closeDeactivateModal() {
        if (deactivateOverlay) deactivateOverlay.style.display = 'none';
        if (_pendingToggleBtn) { _pendingToggleBtn.disabled = false; _pendingToggleBtn = null; }
    }

    deactivateClose  && deactivateClose.addEventListener('click',  closeDeactivateModal);
    deactivateCancel && deactivateCancel.addEventListener('click', closeDeactivateModal);
    deactivateOverlay && deactivateOverlay.addEventListener('click', function (e) {
        if (e.target === deactivateOverlay) closeDeactivateModal();
    });

    function doToggle(btn, id) {
        var csrf = getCsrf();
        btn.disabled = true;

        fetch(toggleUrl + '/' + id, {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    csrf.name + '=' + csrf.token,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status !== 'success') {
                showAlert(data.message || 'Failed.', 'error');
                btn.disabled = false;
                return;
            }

            var nowActive = data.is_active === 1 || data.is_active === true;
            var badge = document.getElementById('user-status-badge-' + id);
            var row   = document.getElementById('user-row-' + id);

            btn.setAttribute('data-active', nowActive ? '1' : '0');
            btn.textContent = nowActive ? 'Deactivate' : 'Activate';
            btn.classList.toggle('text-danger', nowActive);

            if (row) {
                row.setAttribute('data-active', nowActive ? '1' : '0');
                if (nowActive) row.classList.remove('vs-row-archived');
                else           row.classList.add('vs-row-archived');
                if (window.jQuery && $.fn.DataTable) {
                    var tbl = document.getElementById('userManagementTable');
                    if (tbl && $.fn.DataTable.isDataTable(tbl)) {
                        var dt = $(tbl).DataTable();
                        dt.cell(row, 6).data(nowActive ? '1' : '0').draw(false);
                    }
                }
            }

            if (badge) {
                badge.innerHTML   = nowActive ? userStatusIcons.active : userStatusIcons.inactive;
                badge.style.color = nowActive ? '#16a34a' : '#9ca3af';
                badge.title       = nowActive ? 'Active' : 'Inactive';
                badge.setAttribute('aria-label', nowActive ? 'Active' : 'Inactive');
            }

            var metaEl = document.querySelector('meta[name="csrf-token-value"]');
            if (metaEl && data.csrf_token) metaEl.setAttribute('content', data.csrf_token);

            btn.disabled = false;
        })
        .catch(function () {
            showAlert('An error occurred.', 'error');
            btn.disabled = false;
        });
    }

    deactivateConfirm && deactivateConfirm.addEventListener('click', function () {
        if (!_pendingToggleBtn) return;
        var btn = _pendingToggleBtn;
        var id  = btn.getAttribute('data-id');
        _pendingToggleBtn = null;

        if (deactivateOverlay) deactivateOverlay.style.display = 'none';
        if (deactivateBtnText)  deactivateBtnText.style.display  = 'none';
        if (deactivateSpinner)  deactivateSpinner.style.display  = 'inline-block';
        deactivateConfirm.disabled = true;

        doToggle(btn, id);

        if (deactivateBtnText)  deactivateBtnText.style.display  = 'inline';
        if (deactivateSpinner)  deactivateSpinner.style.display  = 'none';
        deactivateConfirm.disabled = false;
    });

    document.querySelectorAll('.js-user-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id     = btn.getAttribute('data-id');
            var active = btn.getAttribute('data-active') === '1';

            if (active) {
                // Deactivating — show confirmation modal
                _pendingToggleBtn = btn;
                btn.disabled = true;
                if (deactivateOverlay) deactivateOverlay.style.display = 'flex';
            } else {
                // Activating — no confirmation needed
                doToggle(btn, id);
            }
        });
    });

});

// No auto-submit — user clicks Search button to apply filters.
</script>

<?= $this->endSection() ?>
