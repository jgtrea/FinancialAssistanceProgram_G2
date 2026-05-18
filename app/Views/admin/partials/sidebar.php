<?php
$uri = uri_string();

$isActive = static function (string $path) use ($uri): string {
    return str_starts_with($uri, $path) ? 'active' : '';
};
?>

<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <div class="sb-sidenav-menu-heading">Main</div>
            <a class="nav-link <?= $isActive('admin/dashboard') ?>" href="<?= site_url('admin/dashboard') ?>">
                <div class="sb-nav-link-icon">D</div>
                Dashboard
            </a>
            <div class="sb-sidenav-menu-heading">Manage</div>
            <a class="nav-link <?= $isActive('admin/user_management') ?>" href="<?= site_url('admin/user_management') ?>">
                <div class="sb-nav-link-icon">U</div>
                Users
            </a>
            <a class="nav-link <?= $isActive('admin/archived_users') ?>" href="<?= site_url('admin/archived_users') ?>">
                <div class="sb-nav-link-icon">R</div>
                Archived Users
            </a>
            <a class="nav-link <?= $isActive('admin/audit-logs') ?>" href="<?= site_url('admin/audit-logs') ?>">
                <div class="sb-nav-link-icon">L</div>
                Audit Logs
            </a>
        </div>
    </div>
    <div class="sb-sidenav-footer">
        <div class="small">Signed in as:</div>
        <?= esc(session('role') ?? 'admin') ?>
    </div>
</nav>
