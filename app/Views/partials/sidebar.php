<?php
$role    = session()->get('role') ?: 'admin';
$uri     = uri_string();

function isActive(string $segment, string $uri): string {
    return str_starts_with($uri, $segment) ? 'vs-nav-active' : '';
}
?>

<aside class="vs-sidebar" id="sidebar">

  <!-- Brand -->
  <div class="vs-sidebar-brand">
    <div class="vs-sidebar-logo">
      <svg viewBox="0 0 24 24" fill="currentColor">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 13h8v1.5H8V13zm0 3h8v1.5H8V16zm0-6h3v1.5H8V10z"/>
      </svg>
    </div>
    <div class="vs-sidebar-brand-text">
      <span class="vs-sidebar-brand-name">FAP</span>
      <span class="vs-sidebar-brand-sub">Voucher System</span>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="vs-nav">

    <?php if ($role === 'admin'): ?>

    <div class="vs-nav-section">MAIN</div>

    <a href="<?= site_url('admin/dashboard') ?>"
       class="vs-nav-item <?= isActive('admin/dashboard', $uri) ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
      </svg>
      Dashboard
    </a>

    <a href="<?= site_url('admin/students') ?>"
       class="vs-nav-item <?= isActive('admin/students', $uri) ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Students
    </a>

    <a href="<?= site_url('admin/archive') ?>"
       class="vs-nav-item <?= isActive('admin/archive', $uri) ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="21 8 21 21 3 21 3 8"/>
        <rect x="1" y="3" width="22" height="5"/>
        <line x1="10" y1="12" x2="14" y2="12"/>
      </svg>
      Archive
    </a>

    <div class="vs-nav-section">MANAGE</div>

    <a href="<?= site_url('admin/user_management') ?>"
       class="vs-nav-item <?= isActive('admin/user_management', $uri) ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Users
    </a>

    <a href="<?= site_url('admin/audit-logs') ?>"
       class="vs-nav-item <?= isActive('admin/audit-logs', $uri) ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="8" y1="13" x2="16" y2="13"/>
        <line x1="8" y1="17" x2="16" y2="17"/>
        <polyline points="10 9 9 9 8 9"/>
      </svg>
      Audit Logs
    </a>

    <?php else: ?>

    <div class="vs-nav-section">MAIN</div>

    <a href="<?= site_url('user/dashboard') ?>"
       class="vs-nav-item <?= isActive('user/dashboard', $uri) ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
      </svg>
      Dashboard
    </a>

    <a href="<?= site_url('user/students') ?>"
       class="vs-nav-item <?= isActive('user/students', $uri) ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Students
    </a>

    <?php endif ?>

  </nav>

  <!-- Sidebar footer -->
  <div class="vs-sidebar-footer">
    <a href="<?= site_url('logout') ?>" class="vs-nav-item vs-nav-logout">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Logout
    </a>
  </div>

</aside>

<!-- Mobile overlay -->
<div class="vs-sidebar-overlay" id="sidebarOverlay"></div>
