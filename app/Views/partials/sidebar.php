<?php
$role = session('role') ?: 'user';
$uri = uri_string();

$isActive = static function (array|string $paths) use ($uri): string {
    foreach ((array) $paths as $path) {
        if (str_starts_with($uri, $path)) {
            return 'active';
        }
    }

    return '';
};
?>

<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <div class="sb-sidenav-menu-heading">Main</div>

            <?php if ($role === 'admin'): ?>
                <a class="nav-link <?= $isActive('admin/dashboard') ?>" href="<?= site_url('admin/dashboard') ?>">
                    <div class="sb-nav-link-icon">D</div>
                    Dashboard
                </a>
                <a class="nav-link <?= $isActive('admin/vouchers') ?>" href="<?= site_url('admin/vouchers') ?>">
                    <div class="sb-nav-link-icon">S</div>
                    Students
                </a>
                <a class="nav-link <?= $isActive('signatories') ?>" href="<?= site_url('signatories') ?>">
                    <div class="sb-nav-link-icon">G</div>
                    Signatories
                </a>

                <div class="sb-sidenav-menu-heading">Manage</div>
                <a class="nav-link <?= $isActive('admin/user_management') ?>" href="<?= site_url('admin/user_management') ?>">
                    <div class="sb-nav-link-icon">U</div>
                    Users
                </a>
                <a class="nav-link <?= $isActive('archive?type=user') ?>" href="<?= site_url('archive?type=user') ?>">
                    <div class="sb-nav-link-icon">R</div>
                    Archive
                </a>
                <a class="nav-link <?= $isActive('admin/audit-logs') ?>" href="<?= site_url('admin/audit-logs') ?>">
                    <div class="sb-nav-link-icon">L</div>
                    Audit Logs
                </a>
            <?php else: ?>
                <a class="nav-link <?= $isActive('user/vouchers') ?>" href="<?= site_url('user/vouchers') ?>">
                    <div class="sb-nav-link-icon">S</div>
                    Students
                </a>
                <a class="nav-link <?= $isActive('signatories') ?>" href="<?= site_url('signatories') ?>">
                    <div class="sb-nav-link-icon">G</div>
                    Signatories
                </a>
                <a class="nav-link <?= $isActive('archive?type=voucher') ?>" href="<?= site_url('archive?type=voucher') ?>">
                    <div class="sb-nav-link-icon">A</div>
                    Archive
                </a>
                <a class="nav-link <?= $isActive('audit-logs') ?>" href="<?= site_url('audit-logs') ?>">
                    <div class="sb-nav-link-icon">L</div>
                    Audit Logs
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="sb-sidenav-footer">
        <div class="small">Signed in as:</div>
        <?= esc(ucfirst($role)) ?>
    </div>
</nav>
