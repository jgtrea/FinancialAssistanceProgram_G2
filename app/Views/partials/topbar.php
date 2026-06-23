<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand vs-brand ps-3 d-flex align-items-center" href="<?= site_url(session('role') === 'admin' ? 'admin/dashboard' : 'user/dashboard') ?>">
        <img src="<?= base_url('assets/img/city_education_office_seal.png') ?>" class="navbar-logo me-2" alt="City Education Office seal">
        <span class="vs-brand-text">
            <span class="vs-brand-title">CEDO</span>
            <span class="vs-brand-sub">Financial Assistance Program</span>
        </span>
    </a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 ms-3 me-4 me-lg-0 text-white" id="sidebarToggle" type="button" aria-label="Toggle navigation">
        <span aria-hidden="true">&#9776;</span>
    </button>

    <div class="ms-auto me-3 d-flex align-items-center gap-3 text-white-50 small">
        <?php
            $fullName = session('full_name') ?? 'User';
            $initial  = strtoupper(mb_substr(trim($fullName), 0, 1) ?: 'U');
        ?>
        <button class="topbar-avatar" id="btnOpenAccountModal" type="button" title="<?= esc($fullName) ?>">
            <?= esc($initial) ?>
        </button>
        <a class="btn btn-outline-light btn-sm" href="<?= site_url('logout') ?>">Logout</a>
    </div>
</nav>
