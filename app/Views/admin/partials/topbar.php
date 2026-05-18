<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="<?= site_url('admin/dashboard') ?>">FAP Admin</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0 text-white" id="sidebarToggle" type="button" aria-label="Toggle navigation">
        <span aria-hidden="true">&#9776;</span>
    </button>

    <div class="ms-auto me-3 d-flex align-items-center gap-2 text-white-50 small">
        <span><?= esc(session('full_name') ?? session('username') ?? 'Admin') ?></span>
        <a class="btn btn-outline-light btn-sm" href="<?= site_url('logout') ?>">Logout</a>
    </div>
</nav>
