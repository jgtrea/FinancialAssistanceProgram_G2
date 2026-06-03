<?= $this->extend('layouts/main') ?>   
<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
    <div>
        <h4 class="vs-page-title"><?= isset($user) ? 'Edit User' : 'Add User' ?></h4>
        <p class="vs-page-sub">Manage Login Details And Account Access.</p>
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
            <label class="vs-label required">Username</label>
            <input type="text" name="full_name" class="vs-input" required autocomplete="username" autocapitalize="none" spellcheck="false" value="<?= esc($user['username'] ?? '') ?>">
        </div>

        <div>
            <label class="vs-label required">Email</label>
            <input type="email" name="username" class="vs-input" required autocomplete="email" autocapitalize="none" spellcheck="false" value="<?= esc($user['email'] ?? '') ?>">
        </div>

        <div>
            <label class="vs-label<?= isset($user) ? '' : ' required' ?>">Password <?= isset($user) ? '<span class="vs-label-hint">(leave blank to keep current)</span>' : '' ?></label>
            <input type="password" name="password" class="vs-input" autocomplete="new-password" autocapitalize="none" spellcheck="false" <?= isset($user) ? '' : 'required' ?>>
        </div>

        <div>
            <label class="vs-label required">Role</label>
            <select name="role" class="vs-input js-filter-select" data-placeholder="ADMIN / USER" data-no-search="1" required>
                <option></option>
                <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>ADMIN</option>
                <option value="user"  <?= ($user['role'] ?? '') === 'user'  ? 'selected' : '' ?>>USER</option>
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
