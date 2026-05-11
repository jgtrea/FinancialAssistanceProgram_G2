<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
  <div>
    <p class="vs-page-sub">Manage system user accounts and permissions</p>
  </div>
  <a href="<?= site_url('admin/users/create') ?>" class="vs-btn vs-btn-primary">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
      <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Add User
  </a>
</div>

<?php if (session()->getFlashdata('message')): ?>
  <div class="vs-alert vs-alert-success mb-3"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="vs-alert vs-alert-error mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif ?>

<div class="vs-card">
  <div class="vs-card-body">
    <table id="usersTable" class="vs-datatable" style="width:100%">
      <thead>
        <tr>
          <th>ID</th>
          <th>Full Name</th>
          <th>Username</th>
          <th>Role</th>
          <th>Status</th>
          <th>Last Login</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><span class="vs-id-badge">USR-<?= str_pad($u['user_id'], 4, '0', STR_PAD_LEFT) ?></span></td>
          <td><?= esc($u['full_name'] ?? '—') ?></td>
          <td><?= esc($u['username']) ?></td>
          <td>
            <span class="vs-role-badge vs-role-<?= $u['role'] ?>">
              <?= ucfirst($u['role']) ?>
            </span>
          </td>
          <td>
            <button class="vs-toggle-status <?= $u['is_active'] ? 'vs-toggle-active' : 'vs-toggle-inactive' ?>"
                    data-id="<?= $u['user_id'] ?>"
                    data-active="<?= $u['is_active'] ?>">
              <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
            </button>
          </td>
          <td><?= $u['last_login'] ? date('M d, Y g:i A', strtotime($u['last_login'])) : '—' ?></td>
          <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= site_url('admin/users/edit/' . $u['user_id']) ?>"
                 class="vs-tbl-btn vs-tbl-btn-edit">Edit</a>
              <?php if ((int)$u['user_id'] !== (int)session()->get('user_id')): ?>
              <button class="vs-tbl-btn vs-tbl-btn-delete vs-delete-user"
                      data-id="<?= $u['user_id'] ?>"
                      data-name="<?= esc($u['username']) ?>">Delete</button>
              <?php endif ?>
            </div>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Delete confirm modal -->
<div class="vs-modal-overlay" id="deleteModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5>Delete User</h5>
      <button class="vs-modal-close" id="deleteModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <p>Are you sure you want to delete <strong id="deleteUserName"></strong>? This action cannot be undone.</p>
    </div>
    <div class="vs-modal-footer">
      <button class="vs-btn vs-btn-outline" id="deleteModalCancel">Cancel</button>
      <button class="vs-btn vs-btn-danger" id="deleteConfirm">
        <span id="deleteBtnText">Delete</span>
        <span id="deleteBtnSpinner" class="vs-spinner" style="display:none"></span>
      </button>
    </div>
  </div>
</div>

<?= $this->endSection() ?>