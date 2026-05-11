<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="vs-login-page">
  <div class="vs-login-wrap">

    <!-- Left branding panel -->
    <div class="vs-login-left">
      <div class="vs-login-brand">
        <div class="vs-brand-icon">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 13h8v1.5H8V13zm0 3h8v1.5H8V16zm0-6h3v1.5H8V10z"/>
          </svg>
        </div>
        <div>
          <div class="vs-login-brand-name">Voucher System</div>
          <div class="vs-login-brand-sub">Senior High School</div>
        </div>
      </div>

      <div class="vs-login-hero">
        <h2>Disbursement &amp; Voucher Management</h2>
        <p>Secure access to manage, generate, and track financial vouchers with full audit trail support.</p>
      </div>

      <div class="vs-login-footnote">
        Authorized personnel only &nbsp;·&nbsp; All actions are logged
      </div>
    </div>

    <!-- Right form panel -->
    <div class="vs-login-right">
      <h1>Sign in</h1>
      <p class="vs-login-subtitle">Enter your credentials to continue</p>

      <?php if (session()->getFlashdata('message')): ?>
        <div class="vs-alert vs-alert-success">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
          <?= esc(session()->getFlashdata('message')) ?>
        </div>
      <?php endif ?>

      <?php if (!empty($error)): ?>
        <div class="vs-alert vs-alert-error">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= esc($error) ?>
        </div>
      <?php endif ?>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="vs-alert vs-alert-error">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= esc(session()->getFlashdata('error')) ?>
        </div>
      <?php endif ?>

      <form action="<?= base_url('/login') ?>" method="POST" id="loginForm">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="vs-label" for="username">Username</label>
          <div class="vs-input-wrap">
            <span class="vs-input-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <input
              type="text"
              id="username"
              name="username"
              class="vs-input <?= ($validation && $validation->hasError('username')) ? 'vs-input-error' : '' ?>"
              placeholder="Enter your username"
              value="<?= set_value('username') ?>"
              autocomplete="username"
              autofocus
            >
          </div>
          <?php if ($validation && $validation->hasError('username')): ?>
            <div class="vs-field-error"><?= $validation->getError('username') ?></div>
          <?php endif ?>
        </div>

        <div class="mb-4">
          <label class="vs-label" for="password">Password</label>
          <div class="vs-input-wrap">
            <span class="vs-input-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input
              type="password"
              id="password"
              name="password"
              class="vs-input <?= ($validation && $validation->hasError('password')) ? 'vs-input-error' : '' ?>"
              placeholder="Enter your password"
              autocomplete="current-password"
            >
            <button type="button" class="vs-pw-toggle" id="pwToggle" title="Show/hide password">
              <svg id="pwIconShow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg id="pwIconHide" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <?php if ($validation && $validation->hasError('password')): ?>
            <div class="vs-field-error"><?= $validation->getError('password') ?></div>
          <?php endif ?>
        </div>

        <button type="submit" class="vs-btn-login" id="loginBtn">
          <span id="loginBtnText">Sign In</span>
          <span id="loginBtnSpinner" class="vs-spinner" style="display:none"></span>
        </button>
      </form>

      <hr class="vs-divider">
      <p class="vs-sys-note">
        This system is for authorized school personnel only.<br>
        Unauthorized access is strictly prohibited.
      </p>
    </div>

  </div>
</div>

<?= $this->endSection() ?>