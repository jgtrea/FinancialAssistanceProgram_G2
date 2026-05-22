<?= $this->extend('layouts/main') ?>   
<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
    <div>
        <h4 class="vs-page-title"><?= isset($user) ? 'Edit User' : 'Add User' ?></h4>
        <p class="vs-page-sub">Manage login details and account access.</p>
    </div>
    <a href="<?= base_url('admin/user_management') ?>" class="vs-btn vs-btn-outline">Back to users</a>
</div>

<div id="alertBox"></div>

<form id="userForm" action="<?= base_url('admin/user_management/save') ?>" method="post" data-redirect-url="<?= base_url('admin/user_management') ?>">
    <?= csrf_field() ?>

    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?? '' ?>">

    <div class="vs-card">
      <div class="vs-card-body">
        <div class="vs-form-grid vs-form-grid-4">
        <div class="vs-span-2">
            <label class="vs-label">Full Name</label>
            <input type="text" name="full_name" class="vs-input vs-uppercase" required value="<?= esc($user['full_name'] ?? '') ?>">
        </div>

        <div>
            <label class="vs-label">Username</label>
            <input type="email" name="username" class="vs-input" required value="<?= esc($user['username'] ?? '') ?>">
        </div>

        <div>
            <label class="vs-label">Password <?= isset($user) ? '<span class="vs-label-hint">(leave blank to keep current)</span>' : '' ?></label>
            <input type="password" name="password" class="vs-input" <?= isset($user) ? '' : 'required' ?>>
        </div>

        <div>
            <label class="vs-label">Role</label>
            <select name="role" class="vs-input" required>
                <option value="admin" <?= (($user['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                <option value="staff" <?= (($user['role'] ?? '') === 'staff') ? 'selected' : '' ?>>Staff</option>
                <option value="viewer" <?= (($user['role'] ?? '') === 'viewer') ? 'selected' : '' ?>>Viewer</option>
            </select>
        </div>

        <div>
            <label class="vs-label">Account Status</label>
            <select name="is_active" class="vs-input">
                <option value="1" <?= (($user['is_active'] ?? '1') == '1') ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= (($user['is_active'] ?? '') == '0') ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        </div>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="vs-btn vs-btn-primary">
            <?= isset($user) ? 'Update User' : 'Save User' ?>
        </button>
        <a href="<?= base_url('admin/user_management') ?>" class="vs-btn vs-btn-outline">Cancel</a>
    </div>
</form>

<?= $this->endSection() ?>
