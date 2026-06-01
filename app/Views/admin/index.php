<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="vs-page-header mb-3">
        <div>
            <h4 class="vs-page-title">User Management</h4>
            <p class="vs-page-sub">Manage staff accounts and system access.</p>
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
        <div class="ms-auto d-flex gap-2">
            <button class="vs-btn vs-btn-success" id="btnActivateUsers">
                <?= asset_icon('circle_check', ['width' => '18', 'height' => '18']) ?>
                Activate
            </button>
            <button class="vs-btn vs-btn-danger" id="btnDeactivateUsers">
                <?= asset_icon('circle_x', ['width' => '18', 'height' => '18']) ?>
                Deactivate
            </button>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
        <form method="get" class="vs-advanced-search vs-advanced-search-outside">
            <input type="text" name="q" class="vs-input vs-advanced-search-input" placeholder="Enter keyword to search (username, email, etc.)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
            <button type="button" class="vs-btn vs-btn-outline" id="btnOpenUserFilter">
                Filters
                <span id="userFilterBadge" class="badge bg-primary" style="display:none;margin-left:.35rem"></span>
            </button>
        </form>
        <div class="ms-auto d-flex gap-2">
            <button type="button" class="vs-btn vs-btn-primary" id="btnAddUser">
                <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
                Add User
            </button>
        </div>
    </div>

    <div class="vs-card">
        <div class="vs-card-body">
            <table id="userManagementTable" class="vs-datatable js-data-table" data-page-search="customUsersSearch" data-search-placeholder="Search users..." data-order='[[1,"asc"]]' style="width:100%">
            <thead>
                <tr>
                    <th class="vs-th-check"><input type="checkbox" class="vs-check" id="userCheckAll" aria-label="Select all"></th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th class="actions-column">Actions</th>
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
                        <td><input type="checkbox" class="vs-check user-row-check" value="<?= $uid ?>"<?= $isSelf ? ' disabled title="You cannot modify your own account"' : '' ?>></td>
                        <td><?= esc($user['username']) ?></td>
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
                        <td><?= !empty($user['last_login']) ? esc(date('M d, Y h:i A', strtotime($user['last_login']))) : 'Never' ?></td>
                        <td class="actions-cell">
                            <div class="dropdown">
                                <button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button type="button" class="dropdown-item js-user-edit"
                                                data-id="<?= $uid ?>">Edit</button>
                                    </li>
                                    <li>
                                        <button type="button"
                                                class="dropdown-item js-user-toggle"
                                                data-id="<?= $uid ?>"
                                                data-active="<?= $isActive ? '1' : '0' ?>"
                                                id="user-toggle-<?= $uid ?>">
                                            <?= $isActive ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </div>

<!-- Users Filter modal -->
<div class="vs-modal-overlay" id="userFilterModal" style="display:none">
  <div class="vs-modal" style="max-width:400px">
    <div class="vs-modal-header">
      <h5>Filter Users</h5>
      <button class="vs-modal-close" id="userFilterClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <div class="vs-form-grid vs-form-grid-2">
        <div class="vs-span-2">
          <label class="vs-label" for="ufRole">Role</label>
          <input list="ufRole-list" id="ufRole" class="vs-input" placeholder="All">
          <datalist id="ufRole-list">
            <option value="admin">
            <option value="user">
          </datalist>
        </div>
        <div class="vs-span-2">
          <label class="vs-label" for="ufStatus">Status</label>
          <input list="ufStatus-list" id="ufStatus" class="vs-input" placeholder="All">
          <datalist id="ufStatus-list">
            <option value="active">
            <option value="inactive">
          </datalist>
        </div>
        <div>
          <label class="vs-label" for="ufLoginFrom">Last Login From</label>
          <input type="date" id="ufLoginFrom" class="vs-input">
        </div>
        <div>
          <label class="vs-label" for="ufLoginTo">Last Login To</label>
          <input type="date" id="ufLoginTo" class="vs-input">
        </div>
      </div>
    </div>
    <div class="vs-modal-footer">
      <button type="button" class="vs-btn vs-btn-outline" id="userFilterClear">Clear All</button>
      <button type="button" class="vs-btn vs-btn-outline" id="userFilterCancel">Cancel</button>
      <button type="button" class="vs-btn vs-btn-primary" id="userFilterApply">Apply</button>
    </div>
  </div>
</div>

<!-- User Add/Edit modal -->
<div class="vs-modal-overlay" id="userModal" style="display:none">
  <div class="vs-modal" style="max-width:680px">
    <div class="vs-modal-header">
      <h5 id="userModalTitle">Add User</h5>
      <button class="vs-modal-close" id="userModalClose">&times;</button>
    </div>
    <form id="userModalForm" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="user_id" id="umUserId" value="">

      <div class="vs-modal-body">
        <div id="userModalAlert"></div>

        <div class="vs-form-grid vs-form-grid-4">
          <div class="vs-span-2">
            <label class="vs-label required" for="umUsername">Username</label>
            <input type="text" id="umUsername" name="full_name" class="vs-input" required autocomplete="username" autocapitalize="none" spellcheck="false">
          </div>

          <div class="vs-span-2">
            <label class="vs-label required" for="umEmail">Email</label>
            <input type="email" id="umEmail" name="username" class="vs-input" required autocomplete="email" autocapitalize="none" spellcheck="false">
          </div>

          <div class="vs-span-2">
            <label class="vs-label" id="umPasswordLabel" for="umPassword">Password</label>
            <input type="password" id="umPassword" name="password" class="vs-input" autocomplete="new-password" autocapitalize="none" spellcheck="false">
          </div>

          <div class="vs-span-2">
            <label class="vs-label required" for="umRole">Role</label>
            <input list="umRole-list" id="umRole" name="role" class="vs-input" placeholder="-- Select --" required>
            <datalist id="umRole-list">
              <option value="admin">
              <option value="user">
            </datalist>
          </div>
        </div>
      </div>

      <div class="vs-modal-footer">
        <button type="button" class="vs-btn vs-btn-outline" id="userModalCancel">Close</button>
        <button type="submit" class="vs-btn vs-btn-primary" id="userModalSubmit">
          <span id="umSubmitText">Save User</span>
          <span id="umSubmitSpinner" class="vs-spinner" style="display:none"></span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
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
        document.getElementById('umUserId').value = user.user_id || '';
        document.getElementById('umUsername').value = user.username || '';
        document.getElementById('umEmail').value = user.email || '';
        document.getElementById('umRole').value = user.role || 'user';
        umPassword.value = '';
    }

    function umSetPasswordMode(isEdit) {
        if (isEdit) {
            umPasswordLabel.classList.remove('required');
            umPasswordLabel.innerHTML = 'Password <span class="vs-label-hint">(leave blank to keep current)</span>';
            umPassword.required = false;
        } else {
            umPasswordLabel.classList.add('required');
            umPasswordLabel.textContent = 'Password';
            umPassword.required = true;
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

    // ── Checkbox selection + action bar ──────────────────────────────────
    var selectedIds = new Set();
    var actionBar   = document.getElementById('userActionBar');
    var countLabel  = document.getElementById('userSelectedCount');

    function getSelectableChecks() {
        return Array.from(document.querySelectorAll('.user-row-check:not([disabled])'));
    }

    function updateBar() {
        if (countLabel) countLabel.textContent = selectedIds.size;
        if (actionBar)  actionBar.style.display = selectedIds.size > 0 ? 'flex' : 'none';
        var checkAll = document.getElementById('userCheckAll');
        if (checkAll) {
            checkAll.checked       = false;
            checkAll.indeterminate = selectedIds.size > 0;
        }
    }

    var checkAll = document.getElementById('userCheckAll');
    checkAll && checkAll.addEventListener('change', function () {
        var shouldCheck = selectedIds.size === 0;
        getSelectableChecks().forEach(function (cb) {
            cb.checked = shouldCheck;
            if (shouldCheck) selectedIds.add(cb.value);
            else selectedIds.delete(cb.value);
            cb.closest('tr').classList.toggle('vs-row-selected', shouldCheck);
        });
        updateBar();
    });

    document.querySelectorAll('.user-row-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (cb.disabled) return;
            if (cb.checked) selectedIds.add(cb.value);
            else selectedIds.delete(cb.value);
            cb.closest('tr').classList.toggle('vs-row-selected', cb.checked);
            updateBar();
        });
    });

    // ── Bulk activate / deactivate ────────────────────────────────────────
    var activateUrl   = '<?= base_url('admin/user_management/activate-multiple') ?>';
    var deactivateUrl = '<?= base_url('admin/user_management/deactivate-multiple') ?>';

    function bulkStatusRequest(url, newActiveState) {
        if (!selectedIds.size) return;
        var csrf = getCsrf();
        var body = csrf.name + '=' + csrf.token;
        selectedIds.forEach(function (id) { body += '&ids[]=' + id; });

        fetch(url, {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status !== 'success') {
                showAlert(data.message || 'Action failed.', 'error');
                return;
            }
            var nowActive = newActiveState === 1;
            selectedIds.forEach(function (id) {
                var row = document.getElementById('user-row-' + id);
                if (!row) return;
                row.setAttribute('data-active', nowActive ? '1' : '0');
                if (nowActive) row.classList.remove('vs-row-archived');
                else           row.classList.add('vs-row-archived');

                var badge = document.getElementById('user-status-badge-' + id);
                if (badge) {
                    badge.innerHTML   = nowActive ? userStatusIcons.active : userStatusIcons.inactive;
                    badge.style.color = nowActive ? '#16a34a' : '#9ca3af';
                    badge.title       = nowActive ? 'Active' : 'Inactive';
                    badge.setAttribute('aria-label', nowActive ? 'Active' : 'Inactive');
                }

                var toggleBtn = document.getElementById('user-toggle-' + id);
                if (toggleBtn) {
                    toggleBtn.textContent = nowActive ? 'Deactivate' : 'Activate';
                    toggleBtn.setAttribute('data-active', nowActive ? '1' : '0');
                }
            });
            selectedIds.clear();
            updateBar();
            if (window.jQuery && $.fn.DataTable) {
                var tbl = document.getElementById('userManagementTable');
                if (tbl && $.fn.DataTable.isDataTable(tbl)) $(tbl).DataTable().draw(false);
            }
            showAlert(data.message, 'success');
        })
        .catch(function () { showAlert('An error occurred.', 'error'); });
    }

    var btnActivate   = document.getElementById('btnActivateUsers');
    var btnDeactivate = document.getElementById('btnDeactivateUsers');
    btnActivate   && btnActivate.addEventListener('click',   function () { bulkStatusRequest(activateUrl, 1); });
    btnDeactivate && btnDeactivate.addEventListener('click', function () { bulkStatusRequest(deactivateUrl, 0); });

    // ── Activate / Deactivate toggle ──────────────────────────────────────
    var toggleUrl = '<?= base_url('admin/user_management/toggle') ?>';

    document.querySelectorAll('.js-user-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id     = btn.getAttribute('data-id');
            var active = btn.getAttribute('data-active') === '1';
            var csrf   = getCsrf();

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

                btn.setAttribute('data-active', nowActive ? '1' : '0');
                btn.textContent = nowActive ? 'Deactivate' : 'Activate';

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
        });
    });

}());

// ── Filter modal + date-range custom filter for users table ──────────────────
(function initUserSearch() {
    var table = document.getElementById('userManagementTable');
    if (!table || !window.jQuery || !$.fn.DataTable || !$.fn.DataTable.isDataTable(table)) {
        return setTimeout(initUserSearch, 50);
    }
    var dt = $(table).DataTable();

    var filterModal = document.getElementById('userFilterModal');
    var filterBadge = document.getElementById('userFilterBadge');

    function openFilter()  { if (filterModal) filterModal.style.display = 'flex'; }
    function closeFilter() { if (filterModal) filterModal.style.display = 'none'; }

    var btnOpen   = document.getElementById('btnOpenUserFilter');
    var btnClose  = document.getElementById('userFilterClose');
    var btnCancel = document.getElementById('userFilterCancel');
    var btnClear  = document.getElementById('userFilterClear');
    var btnApply  = document.getElementById('userFilterApply');
    var ufRole    = document.getElementById('ufRole');
    var ufStatus  = document.getElementById('ufStatus');
    var ufLoginFrom = document.getElementById('ufLoginFrom');
    var ufLoginTo   = document.getElementById('ufLoginTo');
    var activeUserFilters = {};

    // Custom date-range filter on the Last Login column
    $.fn.dataTable.ext.search.push(function (settings, rowData, rowIdx) {
        if (settings.nTable.id !== 'userManagementTable') return true;
        var row = settings.aoData[rowIdx].nTr;
        if (!row) return true;
        var d = row.getAttribute('data-last-login') || '';
        if (activeUserFilters.loginFrom && d < activeUserFilters.loginFrom) return false;
        if (activeUserFilters.loginTo   && d > activeUserFilters.loginTo)   return false;
        return true;
    });

    btnOpen   && btnOpen.addEventListener('click', openFilter);
    btnClose  && btnClose.addEventListener('click', closeFilter);
    btnCancel && btnCancel.addEventListener('click', closeFilter);
    filterModal && filterModal.addEventListener('click', function (e) {
        if (e.target === filterModal) closeFilter();
    });

    btnClear && btnClear.addEventListener('click', function () {
        if (ufRole)      ufRole.value      = '';
        if (ufStatus)    ufStatus.value    = '';
        if (ufLoginFrom) ufLoginFrom.value = '';
        if (ufLoginTo)   ufLoginTo.value   = '';
        activeUserFilters = {};
        if (filterBadge) { filterBadge.textContent = ''; filterBadge.style.display = 'none'; }
        dt.column(3).search('').column(4).search('').draw();
    });

    btnApply && btnApply.addEventListener('click', function () {
        var roleVal   = ufRole   ? ufRole.value   : '';
        var statusVal = ufStatus ? ufStatus.value : '';
        activeUserFilters = {
            role:      roleVal,
            status:    statusVal,
            loginFrom: ufLoginFrom ? ufLoginFrom.value : '',
            loginTo:   ufLoginTo   ? ufLoginTo.value   : '',
        };
        var count = [roleVal, statusVal, activeUserFilters.loginFrom, activeUserFilters.loginTo].filter(Boolean).length;
        if (filterBadge) {
            filterBadge.textContent = count || '';
            filterBadge.style.display = count ? '' : 'none';
        }
        var roleSearch   = roleVal   === 'admin' ? '^Admin$'    : roleVal   === 'user'     ? '^User$'     : '';
        var statusSearch = statusVal === 'active' ? '^Active$'  : statusVal === 'inactive' ? '^Inactive$' : '';
        dt.column(3).search(roleSearch,   roleSearch   !== '', false)
          .column(4).search(statusSearch, statusSearch !== '', false)
          .draw();
        closeFilter();
    });
}());
</script>

<?= $this->endSection() ?>
