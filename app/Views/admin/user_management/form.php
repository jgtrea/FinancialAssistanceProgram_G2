<?= $this->extend('admin/layouts/main') ?>   
<?= $this->section('content') ?>

<h3><?= isset($user) ? 'Edit User' : 'Add User' ?></h3>

<div id="alertBox"></div>

<form id="userForm" action="<?= base_url('admin/user_management/save') ?>" method="post" data-redirect-url="<?= base_url('admin/user_management') ?>">
    <?= csrf_field() ?>

    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?? '' ?>">

    <div class="row card-main p-4 shadow-soft">
        <div class="col-md-6 mb-3">
            <label class="text-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required value="<?= esc($user['full_name'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label class="text-label">Username</label>
            <input type="text" name="username" class="form-control" required value="<?= esc($user['username'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label class="text-label">Password <?= isset($user) ? '<small class="text-muted">(Leave blank to keep current)</small>' : '' ?></label>
            <input type="password" name="password" class="form-control" <?= isset($user) ? '' : 'required' ?>>
        </div>

        <div class="col-md-3 mb-3">
            <label class="text-label">Role</label>
            <select name="role" class="form-control" required>
                <option value="admin" <?= (($user['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                <option value="staff" <?= (($user['role'] ?? '') === 'staff') ? 'selected' : '' ?>>Staff</option>
                <option value="viewer" <?= (($user['role'] ?? '') === 'viewer') ? 'selected' : '' ?>>Viewer</option>
            </select>
        </div>

        <div class="col-md-3 mb-3">
            <label class="text-label">Account Status</label>
            <select name="is_active" class="form-control">
                <option value="1" <?= (($user['is_active'] ?? '1') == '1') ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= (($user['is_active'] ?? '') == '0') ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <?= isset($user) ? 'Update User' : 'Save User' ?>
        </button>
        <a href="<?= base_url('admin/user_management') ?>" class="btn btn-secondary">Back</a>

        <?php if (isset($user)): ?>
        <button type="button" class="btn btn-danger deleteUserBtn ms-auto"
                data-delete-url="<?= base_url('admin/user_management/delete/' . $user['user_id']) ?>">
            Remove
        </button>
        <?php endif; ?>
    </div>
</form>

<?= $this->endSection() ?>