<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
  <div>
    <p class="vs-page-sub"><?= $user ? 'Update user account details' : 'Create a new system user account' ?></p>
  </div>
  <a href="<?= site_url('admin/users') ?>" class="vs-btn vs-btn-outline">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
    </svg>
    Back to Users
  </a>
</div>

<div class="vs-form-card">
  <form method="POST"
        action="<?= $user ? site_url('admin/users/update/' . $user['user_id']) : site_url('admin/users/store') ?>">
    <?= csrf_field() ?>

    <div class="vs-form-grid">

      <!-- Full Name -->
      <div class="vs-form-group">
        <label class="vs-label" for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name"
               class="vs-input <?= ($validation && $validation->hasError('full_name')) ? 'vs-input-error' : '' ?>"
               value="<?= old('full_name', $user['full_name'] ?? '') ?>"
               placeholder="e.g. Juan dela Cruz">
        <?php if ($validation && $validation->hasError('full_name')): ?>
          <div class="vs-field-error"><?= $validation->getError('full_name') ?></div>
        <?php endif ?>
      </div>

      <!-- Username -->
      <div class="vs-form-group">
        <label class="vs-label" for="username">Username</label>
        <input type="text" id="username" name="username"
               class="vs-input <?= ($validation && $validation->hasError('username')) ? 'vs-input-error' : '' ?>"
               value="<?= old('username', $user['username'] ?? '') ?>"
               placeholder="e.g. jdelacruz"
               autocomplete="off">
        <?php if ($validation && $validation->hasError('username')): ?>
          <div class="vs-field-error"><?= $validation->getError('username') ?></div>
        <?php endif ?>
      </div>

      <!-- Role -->
      <div class="vs-form-group">
        <label class="vs-label" for="role">Role</label>
        <select id="role" name="role"
                class="vs-input <?= ($validation && $validation->hasError('role')) ? 'vs-input-error' : '' ?>">
          <option value="">Select role...</option>
          <option value="admin" <?= old('role', $user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
          <option value="user"  <?= old('role', $user['role'] ?? '') === 'user'  ? 'selected' : '' ?>>User</option>
        </select>
        <?php if ($validation && $validation->hasError('role')): ?>
          <div class="vs-field-error"><?= $validation->getError('role') ?></div>
        <?php endif ?>
      </div>

      <!-- Status (edit only) -->
      <?php if ($user): ?>
      <div class="vs-form-group">
        <label class="vs-label" for="is_active">Status</label>
        <select id="is_active" name="is_active" class="vs-input">
          <option value="1" <?= ($user['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>Active</option>
          <option value="0" <?= ($user['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <?php endif ?>

      <!-- Password -->
      <div class="vs-form-group">
        <label class="vs-label" for="password">
          Password <?= $user ? '<span class="vs-label-hint">(leave blank to keep current)</span>' : '' ?>
        </label>
        <div class="vs-input-wrap">
          <input type="password" id="password" name="password"
                 class="vs-input <?= ($validation && $validation->hasError('password')) ? 'vs-input-error' : '' ?>"
                 placeholder="<?= $user ? 'Leave blank to keep current' : 'Min. 6 characters' ?>"
                 autocomplete="new-password">
          <button type="button" class="vs-pw-toggle" data-target="password">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <?php if ($validation && $validation->hasError('password')): ?>
          <div class="vs-field-error"><?= $validation->getError('password') ?></div>
        <?php endif ?>
      </div>

      <!-- Confirm Password -->
      <div class="vs-form-group">
        <label class="vs-label" for="confirm_password">Confirm Password</label>
        <div class="vs-input-wrap">
          <input type="password" id="confirm_password" name="confirm_password"
                 class="vs-input <?= ($validation && $validation->hasError('confirm_password')) ? 'vs-input-error' : '' ?>"
                 placeholder="Re-enter password"
                 autocomplete="new-password">
          <button type="button" class="vs-pw-toggle" data-target="confirm_password">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <?php if ($validation && $validation->hasError('confirm_password')): ?>
          <div class="vs-field-error"><?= $validation->getError('confirm_password') ?></div>
        <?php endif ?>
      </div>

    </div><!-- /.vs-form-grid -->

    <div class="vs-form-actions">
      <a href="<?= site_url('admin/users') ?>" class="vs-btn vs-btn-outline">Cancel</a>
      <button type="submit" class="vs-btn vs-btn-primary">
        <?= $user ? 'Update User' : 'Create User' ?>
      </button>
    </div>

  </form>
</div>

<?= $this->endSection() ?>