<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
  $errors = $errors ?? [];
  $hasError = static fn(string $field): bool => !empty($errors[$field]);
  $err = static fn(string $field): string => !empty($errors[$field]) ? '<div class="vs-field-error">' . esc($errors[$field]) . '</div>' : '';
?>

<div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
  <div>
    <h1 class="vs-page-title mb-1">My Account</h1>
    <p class="vs-page-sub mb-0">Manage your profile, username, email, and password.</p>
  </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="vs-alert vs-alert-error mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('message')): ?>
  <div class="vs-alert vs-alert-success mb-3"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif ?>

<form method="post" action="<?= site_url('profile') ?>" class="vs-card" autocomplete="off">
  <?= csrf_field() ?>
  <div class="vs-card-body">
    <div class="vs-form-grid vs-form-grid-4">
      <div>
        <label class="vs-label required" for="username">Username</label>
        <input id="username" name="username" type="text" class="vs-input <?= $hasError('username') ? 'vs-input-error' : '' ?>" value="<?= esc(old('username', $user['username'] ?? ''), 'attr') ?>" required>
        <?= $err('username') ?>
      </div>
      <div>
        <label class="vs-label required" for="email">Email</label>
        <input id="email" name="email" type="email" class="vs-input <?= $hasError('email') ? 'vs-input-error' : '' ?>" value="<?= esc(old('email', $user['email'] ?? ''), 'attr') ?>" required>
        <?= $err('email') ?>
      </div>
      <div>
        <label class="vs-label">Role</label>
        <div class="vs-input" style="background:#f9fafb;cursor:default"><?= esc(ucfirst((string) ($user['role'] ?? ''))) ?></div>
      </div>
      <div></div>

      <div>
        <label class="vs-label required" for="first_name">First Name</label>
        <input id="first_name" name="first_name" type="text" class="vs-input vs-uppercase <?= $hasError('first_name') ? 'vs-input-error' : '' ?>" value="<?= esc(old('first_name', $user['first_name'] ?? ''), 'attr') ?>" required>
        <?= $err('first_name') ?>
      </div>
      <div>
        <label class="vs-label" for="middle_name">Middle Name</label>
        <input id="middle_name" name="middle_name" type="text" class="vs-input vs-uppercase <?= $hasError('middle_name') ? 'vs-input-error' : '' ?>" value="<?= esc(old('middle_name', $user['middle_name'] ?? ''), 'attr') ?>">
        <?= $err('middle_name') ?>
      </div>
      <div>
        <label class="vs-label required" for="last_name">Last Name</label>
        <input id="last_name" name="last_name" type="text" class="vs-input vs-uppercase <?= $hasError('last_name') ? 'vs-input-error' : '' ?>" value="<?= esc(old('last_name', $user['last_name'] ?? ''), 'attr') ?>" required>
        <?= $err('last_name') ?>
      </div>
      <div></div>

      <div class="vs-span-4">
        <h2 class="vs-section-title">Change Password</h2>
      </div>
      <div>
        <label class="vs-label" for="current_password">Current Password</label>
        <input id="current_password" name="current_password" type="password" class="vs-input <?= $hasError('current_password') ? 'vs-input-error' : '' ?>" autocomplete="current-password">
        <?= $err('current_password') ?>
      </div>
      <div>
        <label class="vs-label" for="new_password">New Password</label>
        <input id="new_password" name="new_password" type="password" class="vs-input <?= $hasError('new_password') ? 'vs-input-error' : '' ?>" autocomplete="new-password">
        <?= $err('new_password') ?>
      </div>
      <div>
        <label class="vs-label" for="confirm_password">Confirm Password</label>
        <input id="confirm_password" name="confirm_password" type="password" class="vs-input <?= $hasError('confirm_password') ? 'vs-input-error' : '' ?>" autocomplete="new-password">
        <?= $err('confirm_password') ?>
      </div>
    </div>
  </div>
  <div class="vs-modal-footer">
    <a href="<?= site_url((session('role') === 'admin' ? 'admin' : 'user') . '/dashboard') ?>" class="vs-btn vs-btn-outline">Cancel</a>
    <button type="submit" class="vs-btn vs-btn-primary">Save Account</button>
  </div>
</form>

<?= $this->endSection() ?>
