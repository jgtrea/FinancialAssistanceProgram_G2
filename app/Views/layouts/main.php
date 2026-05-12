<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?? 'Voucher System' ?> — FAP</title>

  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <!-- App styles -->
  <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>
<body class="vs-app">

<?php
$username    = session()->get('username') ?: 'Guest';
$fullName    = session()->get('full_name') ?: 'Guest User';
$displayRole = ucfirst(session()->get('role') ?: 'Admin');
?>

<div class="vs-layout">

  <!-- ── Sidebar ── -->
  <?= $this->include('partials/sidebar') ?>

  <!-- ── Main area ── -->
  <div class="vs-main">

    <!-- Top bar -->
    <header class="vs-topbar">
      <button class="vs-sidebar-toggle" id="sidebarToggle" title="Toggle sidebar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6"  x2="21" y2="6"/>
          <line x1="3" y1="12" x2="21" y2="12"/>
          <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>

      <div class="vs-topbar-title"><?= $title ?? '' ?></div>

      <div class="vs-topbar-right">
        <div class="vs-topbar-user">
          <div class="vs-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
          <div class="vs-topbar-info">
            <span class="vs-topbar-name"><?= esc($fullName) ?></span>
            <span class="vs-topbar-role"><?= esc($displayRole) ?></span>
          </div>
        </div>
        <a href="<?= site_url('logout') ?>" class="vs-topbar-logout" title="Logout">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
        </a>
      </div>
    </header>

    <!-- Page content -->
    <main class="vs-content">
      <?= $this->renderSection('content') ?>
    </main>

  </div><!-- /.vs-main -->

</div><!-- /.vs-layout -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<!-- App scripts -->
<script src="<?= base_url('js/script.js') ?>?v=2"></script>

</body>
</html>
