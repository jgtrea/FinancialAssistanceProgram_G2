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

$icon = static function (string $name): string {
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
        'students'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M21 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'voucher'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/>',
        'sign'      => '<path d="M16 3l5 5L8 21H3v-5L16 3z"/><path d="M14 5l5 5"/>',
        'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'archive'   => '<rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/>',
        'logs'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h8"/>',
    ];

    return '<svg class="vs-sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$name] ?? '') . '</svg>';
};
?>

<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <div class="sb-sidenav-menu-heading">Main</div>

            <?php if ($role === 'admin'): ?>
                <a class="nav-link <?= $isActive('admin/dashboard') ?>" href="<?= site_url('admin/dashboard') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('dashboard') ?></div>
                    Dashboard
                </a>
                <a class="nav-link <?= $isActive('admin/students') ?>" href="<?= site_url('admin/students') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('students') ?></div>
                    Students
                </a>
                <a class="nav-link <?= $isActive('admin/vouchers') ?>" href="<?= site_url('admin/vouchers') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('voucher') ?></div>
                    Vouchers
                </a>
                <a class="nav-link <?= $isActive('signatories') ?>" href="<?= site_url('signatories') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('sign') ?></div>
                    Signatories
                </a>

                <div class="sb-sidenav-menu-heading">Manage</div>
                <a class="nav-link <?= $isActive('admin/user_management') ?>" href="<?= site_url('admin/user_management') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('users') ?></div>
                    Users
                </a>
                <a class="nav-link <?= $isActive('archive') ?>" href="<?= site_url('archive?type=user') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('archive') ?></div>
                    Archive
                </a>
                <a class="nav-link <?= $isActive('admin/audit-logs') ?>" href="<?= site_url('admin/audit-logs') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('logs') ?></div>
                    Audit Logs
                </a>
            <?php else: ?>
                <a class="nav-link <?= $isActive('user/dashboard') ?>" href="<?= site_url('user/dashboard') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('dashboard') ?></div>
                    Dashboard
                </a>
                <a class="nav-link <?= $isActive('user/students') ?>" href="<?= site_url('user/students') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('students') ?></div>
                    Students
                </a>
                <a class="nav-link <?= $isActive('user/vouchers') ?>" href="<?= site_url('user/vouchers') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('voucher') ?></div>
                    Vouchers
                </a>
                <a class="nav-link <?= $isActive('signatories') ?>" href="<?= site_url('signatories') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('sign') ?></div>
                    Signatories
                </a>
                <a class="nav-link <?= $isActive('archive') ?>" href="<?= site_url('archive?type=voucher') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('archive') ?></div>
                    Archive
                </a>
                <a class="nav-link <?= $isActive('user/audit-logs') ?>" href="<?= site_url('user/audit-logs') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('logs') ?></div>
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
