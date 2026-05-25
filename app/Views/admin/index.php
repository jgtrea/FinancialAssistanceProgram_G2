<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">User Management</h4>
            <p class="vs-page-sub">Manage staff accounts and system access.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="vs-btn vs-btn-primary" id="btnAddUser">
                <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
                Add User
            </button>
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
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <input type="text" id="customUserSearch" class="vs-input" placeholder="Search users..." style="max-width:340px">
                <button type="button" class="vs-btn vs-btn-outline" id="btnOpenUserFilter">
                    Filters
                    <span id="userFilterBadge" class="badge bg-primary" style="display:none;margin-left:.35rem"></span>
                </button>
                <label class="vs-length-label ms-auto">Show <input type="number" id="userLengthInput" class="vs-length-input" value="10" min="1" max="500"> entries</label>
            </div>
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
                                $roleColors = ['admin' => '#1a5c2e', 'user' => '#2e9e52'];
                                $roleColor  = $roleColors[$user['role']] ?? '#6c757d';
                            ?>
                            <span class="badge" style="background-color:<?= $roleColor ?>">
                                <?= esc(ucfirst($user['role'])) ?>
                            </span>
                        </td>
                        <td><?= !empty($user['last_login']) ? esc(date('M d, Y h:i A', strtotime($user['last_login']))) : 'Never' ?></td>
                        <td class="actions-cell">
                            <button type="button"
                                    class="vs-tbl-btn vs-tbl-btn-edit js-user-edit"
                                    data-id="<?= (int) $user['user_id'] ?>">
                                Edit
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </div>

<!-- User Archive Confirmation modal -->
<div class="vs-modal-overlay" id="userArchiveModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5>Archive Users</h5>
      <button class="vs-modal-close" id="userArchiveModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <p>You are about to archive <strong id="userArchiveCount">0</strong> user(s). This will move them to the archive.</p>
      <label class="vs-label" for="userArchiveReason">Reason (optional)</label>
      <input type="text" id="userArchiveReason" class="vs-input" placeholder="e.g. Account deactivation">
    </div>
    <div class="vs-modal-footer">
      <button class="vs-btn vs-btn-outline" id="userArchiveModalCancel">Cancel</button>
      <button class="vs-btn vs-btn-danger" id="userArchiveConfirm">
        <span id="userArchiveBtnText">Confirm Archive</span>
        <span id="userArchiveBtnSpinner" class="vs-spinner" style="display:none"></span>
      </button>
    </div>
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
      <div>
        <label class="vs-label" for="ufRole">Role</label>
        <select id="ufRole" class="vs-input">
          <option value="">All</option>
          <option value="admin">Admin</option>
          <option value="user">User</option>
        </select>
      </div>
    </div>
    <div class="vs-modal-footer">
      <button type="button" class="vs-btn vs-btn-outline" id="userFilterClear">Clear</button>
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
            <select id="umRole" name="role" class="vs-input" required>
              <option value="admin">Admin</option>
              <option value="user">User</option>
            </select>
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

    var btnArchive      = document.getElementById('btnArchiveUsers');
    var userArchModal   = document.getElementById('userArchiveModal');
    var userArchConfirm = document.getElementById('userArchiveConfirm');
    var userArchCancel  = document.getElementById('userArchiveModalCancel');
    var userArchClose   = document.getElementById('userArchiveModalClose');
    var userArchCount   = document.getElementById('userArchiveCount');
    var userArchReason  = document.getElementById('userArchiveReason');
    var userArchBtnText = document.getElementById('userArchiveBtnText');
    var userArchSpinner = document.getElementById('userArchiveBtnSpinner');

    function closeUserArchModal() {
        if (userArchModal) userArchModal.style.display = 'none';
        if (userArchReason) userArchReason.value = '';
    }

    userArchClose  && userArchClose.addEventListener('click', closeUserArchModal);
    userArchCancel && userArchCancel.addEventListener('click', closeUserArchModal);
    userArchModal  && userArchModal.addEventListener('click', function (e) {
        if (e.target === userArchModal) closeUserArchModal();
    });

    btnArchive && btnArchive.addEventListener('click', function () {
        if (!selectedIds.size) return;
        if (userArchCount) userArchCount.textContent = selectedIds.size;
        if (userArchModal) userArchModal.style.display = 'flex';
    });

    userArchConfirm && userArchConfirm.addEventListener('click', function () {
        var reason = userArchReason ? userArchReason.value.trim() : '';
        var csrf = getCsrf();
        var body = csrf.name + '=' + csrf.token;
        selectedIds.forEach(function (id) { body += '&ids[]=' + id; });
        if (reason) body += '&reason=' + encodeURIComponent(reason);

        userArchConfirm.disabled = true;
        if (userArchBtnText) userArchBtnText.style.display = 'none';
        if (userArchSpinner) userArchSpinner.style.display = 'inline-block';

        fetch('<?= base_url('admin/user_management/archive-multiple') ?>', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status === 'success') {
                closeUserArchModal();
                selectedIds.forEach(function (id) {
                    var row = document.getElementById('user-row-' + id);
                    if (row) row.remove();
                });
                selectedIds.clear();
                updateBar();
                showAlert(data.message || 'Archived successfully.', 'success');
            } else {
                showAlert(data.message || 'Failed to archive.', 'error');
                closeUserArchModal();
            }
        })
        .catch(function () {
            showAlert('An error occurred.', 'error');
            closeUserArchModal();
        })
        .finally(function () {
            userArchConfirm.disabled = false;
            if (userArchBtnText) userArchBtnText.style.display = 'inline';
            if (userArchSpinner) userArchSpinner.style.display = 'none';
        });
    });

}());

// ── Custom search + filter for users table ────────────────────────────
(function initUserSearch() {
    var table = document.getElementById('userManagementTable');
    if (!table || !window.jQuery || !$.fn.DataTable.isDataTable(table)) {
        return setTimeout(initUserSearch, 50);
    }
    var dt = $(table).DataTable();
    var dtWrap = table.closest('.dataTables_wrapper');

    var dtSearch = dtWrap ? dtWrap.querySelector('.dataTables_filter') : null;
    if (dtSearch) dtSearch.style.display = 'none';

    var dtLength = dtWrap ? dtWrap.querySelector('.dataTables_length') : null;
    if (dtLength) dtLength.style.display = 'none';

    var lenInput = document.getElementById('userLengthInput');
    if (lenInput) {
        function applyUserLen() {
            var v = parseInt(lenInput.value, 10);
            if (!isNaN(v) && v > 0) dt.page.len(v).draw();
        }
        lenInput.addEventListener('change', applyUserLen);
        lenInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') applyUserLen(); });
    }

    var searchInput = document.getElementById('customUserSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            dt.search(this.value).draw();
        });
    }

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

    btnOpen   && btnOpen.addEventListener('click', openFilter);
    btnClose  && btnClose.addEventListener('click', closeFilter);
    btnCancel && btnCancel.addEventListener('click', closeFilter);
    filterModal && filterModal.addEventListener('click', function (e) {
        if (e.target === filterModal) closeFilter();
    });

    btnClear && btnClear.addEventListener('click', function () {
        if (ufRole) ufRole.value = '';
        if (filterBadge) { filterBadge.textContent = ''; filterBadge.style.display = 'none'; }
        dt.column(3).search('').draw();
        closeFilter();
    });

    btnApply && btnApply.addEventListener('click', function () {
        var val = ufRole ? ufRole.value : '';
        var count = val ? 1 : 0;
        if (filterBadge) {
            filterBadge.textContent = count || '';
            filterBadge.style.display = count ? '' : 'none';
        }
        if (val === 'admin') {
            dt.column(3).search('^Admin$', true, false).draw();
        } else if (val === 'user') {
            dt.column(3).search('^User$', true, false).draw();
        } else {
            dt.column(3).search('').draw();
        }
        closeFilter();
    });
}());
</script>

<?= $this->endSection() ?>
