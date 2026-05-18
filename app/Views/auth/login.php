<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Biñan City - Login</title>

    <?php pre_style('default_lay'); ?>
</head>

<body class="login-screen">
    <nav class="navbar navbar-expand navbar-light bg-white topbar fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold text-uppercase" href="<?= site_url('/') ?>">
                <img src="<?= base_url('images/logo_binan.png') ?>" class="navbar-logo me-2" alt="Biñan City">
                Biñan City
            </a>
        </div>
    </nav>

    <main class="login-shell">
        <section class="login-card-modern">
            <div class="login-visual" style="background-image: linear-gradient(90deg, rgba(8, 32, 24, .86), rgba(8, 32, 24, .42)), url('<?= base_url('images/bg_binan.jpg') ?>');">
                <div class="login-visual-content">
                    <span class="login-eyebrow">Welcome to</span>
                    <h1>Grants and Scholarships Program</h1>
                    <p>Access student assistance records, voucher generation, audit logs, and account management.</p>
                </div>
            </div>

            <div class="login-panel-modern">
                <div class="login-panel-header">
                    <img src="<?= base_url('images/logo_binan.png') ?>" alt="Biñan City" class="login-panel-logo">
                    <div>
                        <h2>Sign in</h2>
                        <p>Use your authorized account to continue.</p>
                    </div>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger small py-2">
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <form class="login-form-modern" action="<?= base_url('auth_login') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="username" class="form-label">Email address</label>
                        <input id="username" type="email" name="username" class="form-control form-control-lg"
                            placeholder="name@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input id="password" type="password" name="password" class="form-control form-control-lg"
                            placeholder="Enter your password" required>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-4 gap-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="customCheck">
                            <label class="form-check-label" for="customCheck">Remember me</label>
                        </div>
                        <a class="small text-decoration-none" href="#">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 login-submit">
                        Login
                    </button>
                </form>
            </div>
        </section>
    </main>

</body>
</html>
