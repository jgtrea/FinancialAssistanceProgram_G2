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

$icon = static fn (string $name): string => asset_icon($name, ['class' => 'vs-sidebar-icon', 'width' => null, 'height' => null]);
?>

<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <?php if ($role === 'admin'): ?>
                <a class="nav-link <?= $isActive('admin/dashboard') ?>" href="<?= site_url('admin/dashboard') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('dashboard') ?></div>
                    Dashboard
                </a>                

                <div class="sb-sidenav-menu-heading">Manage</div>
                <a class="nav-link <?= $isActive('admin/students') ?>" href="<?= site_url('admin/students') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('users') ?></div>
                    Students
                </a>
                <a class="nav-link <?= $isActive('admin/schools') ?>" href="<?= site_url('admin/schools') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('school') ?></div>
                    Schools
                </a>
                <a class="nav-link <?= $isActive('signatories') ?>" href="<?= site_url('signatories') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('sign') ?></div>
                    Signatories
                </a>              
                <a class="nav-link <?= $isActive('archive') ?>" href="<?= site_url('archive?type=voucher') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('archive') ?></div>
                    Archive
                </a>                                  

                <div class="sb-sidenav-menu-heading">Generate</div>
                <a class="nav-link <?= $isActive('admin/vouchers') ?>" href="<?= site_url('admin/vouchers') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('voucher') ?></div>
                    Vouchers
                </a>

                <div class="sb-sidenav-menu-heading">System</div>
                <a class="nav-link <?= $isActive('admin/user_management') ?>" href="<?= site_url('admin/user_management') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('users') ?></div>
                    Users
                </a>                
                <a class="nav-link <?= $isActive('admin/audit-logs') ?>" href="<?= site_url('admin/audit-logs') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('logs') ?></div>
                    Audit Logs
                </a>

            <?php else: ?>
                <div class="sb-sidenav-menu-heading">Manage</div>
                <a class="nav-link <?= $isActive('user/students') ?>" href="<?= site_url('user/students') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('users') ?></div>
                    Students
                </a>

                <div class="sb-sidenav-menu-heading">Generate</div>
                <a class="nav-link <?= $isActive('user/vouchers') ?>" href="<?= site_url('user/vouchers') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('voucher') ?></div>
                    Vouchers
                </a>

                <div class="sb-sidenav-menu-heading">System</div>                 
                <a class="nav-link <?= $isActive('user/audit-logs') ?>" href="<?= site_url('user/audit-logs') ?>">
                    <div class="sb-nav-link-icon"><?= $icon('logs') ?></div>
                    My Logs
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="sb-sidenav-footer">
        <div class="small">Signed in as:</div>
        <?= esc(ucfirst($role)) ?>
    </div>
</nav>
