<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3 d-flex align-items-center fw-bold text-uppercase" href="<?= site_url(session('role') === 'admin' ? 'admin/dashboard' : 'user/dashboard') ?>">
        <img src="<?= base_url('assets/img/city_education_office_seal.png') ?>" class="navbar-logo me-2" alt="City Education Office seal">
        CEDO
    </a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0 text-white" id="sidebarToggle" type="button" aria-label="Toggle navigation">
        <span aria-hidden="true">&#9776;</span>
    </button>

    <div class="ms-auto me-3 d-flex align-items-center gap-3 text-white-50 small">
        <span><?= esc(session('full_name') ?? 'User') ?></span>
        <a class="btn btn-outline-light btn-sm" href="<?= site_url('logout') ?>">Logout</a>
    </div>
</nav>
