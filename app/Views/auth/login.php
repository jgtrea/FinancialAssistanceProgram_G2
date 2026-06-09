<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>City Education Office - Login</title>

    <?php pre_style('default_lay'); ?>
</head>

<body class="login-screen">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark fixed-top">
        <a class="navbar-brand ps-3 d-flex align-items-center fw-bold text-uppercase" href="<?= site_url('/') ?>">
            <img src="<?= base_url('assets/img/city_education_office_seal.png') ?>" class="navbar-logo me-2" alt="City Education Office seal">
            CEDO
        </a>
    </nav>

    <main class="login-shell">
        <section class="login-card-modern">
            <div class="login-visual" style="background-image: linear-gradient(90deg, rgba(7, 59, 120, .9), rgba(7, 120, 154, .5)), url('<?= base_url('assets/img/bg_binan.jpg') ?>');">
                <div class="login-visual-content">
                    <span class="login-eyebrow">Welcome to</span>
                    <h1>Grants and Scholarships Program</h1>
                    <p>Access student assistance records, voucher generation, audit logs, and account management.</p>
                </div>
            </div>

            <div class="login-panel-modern">
                <div class="login-panel-header">
                    <img src="<?= base_url('assets/img/city_education_office_seal.png') ?>" class="login-panel-logo" alt="City Education Office seal">
                    <div>
                        <h2>Sign in</h2>
                        <p>Use your authorized account to continue.</p>
                    </div>
                </div>

                <?php $lockedNotice = $lockedNotice ?? ''; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger small py-2">
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <?php if ($lockedNotice !== ''): ?>
                    <div class="alert alert-warning small py-2">
                        <?= esc($lockedNotice) ?> If that was you, you can log out the other device and continue here.
                    </div>

                    <form class="login-form-modern" action="<?= base_url('auth_login') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="username" value="<?= esc($prefillUser ?? '', 'attr') ?>">
                        <input type="hidden" name="password" value="<?= esc($prefillPass ?? '', 'attr') ?>">
                        <input type="hidden" name="force_login" value="1">

                        <button type="submit" class="btn btn-primary w-100 login-submit mt-3">
                            Log out the other device &amp; continue
                        </button>
                    </form>

                    <a href="<?= base_url('/') ?>" class="btn btn-link w-100 mt-2">Cancel</a>
                <?php else: ?>
                    <form class="login-form-modern" action="<?= base_url('auth_login') ?>" method="POST">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username or Email</label>
                            <input id="username" type="text" name="username" class="form-control"
                                placeholder="email address or username" required autocomplete="username" autocapitalize="none" spellcheck="false">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password" name="password" class="form-control"
                                placeholder="enter your password" required autocomplete="current-password" autocapitalize="none" spellcheck="false">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 login-submit mt-3">
                            Login
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </main>

</body>
</html>
