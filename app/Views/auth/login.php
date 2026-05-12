<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bi&ntilde;an City</title>
    <?php pre_style('sbadmin2'); ?>
</head>
<body class="login-page" style="--login-bg: url('<?= base_url('images/bg_binan.jpg') ?>');">

    <nav class="navbar navbar-light login-navbar">
        <div class="d-flex align-items-center">
            <img src="<?= base_url('images/logo_binan.png') ?>"
                 alt="Bi&ntilde;an City Logo"
                 class="login-navbar__logo">
            <span class="navbar-brand mb-0 h1 login-navbar__brand">Bi&ntilde;an City</span>
        </div>
    </nav>

    <main class="container-fluid login-main">
        <div class="row justify-content-center w-100 align-items-center login-panel">
            <div class="col-lg-6">
                <div class="login-copy">
                    <p class="inter-normal login-copy__eyebrow">WELCOME TO</p>
                    <h1 class="playfair-display-header login-copy__title">Grants and Scholarships Program</h1>
                    <hr class="login-copy__rule">
                    <p class="login-copy__subtitle">Login to Access Dashboard</p>
                </div>
            </div>

            <div class="col-lg-5">
                <section class="login-card">
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger mb-3">
                            <?= session()->getFlashdata('error') ?>
                        </div>
                    <?php endif; ?>

                    <form class="user" action="<?= base_url('auth_login') ?>" method="POST">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <input type="text" name="username"
                                class="form-control form-control-user"
                                placeholder="Enter your Email"
                                autocomplete="username"
                                required>
                        </div>

                        <div class="form-group">
                            <input type="password" name="password"
                                class="form-control form-control-user"
                                placeholder="Enter your Password"
                                autocomplete="current-password"
                                required>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox small">
                                <input type="checkbox" class="custom-control-input" id="customCheck">
                                <label class="custom-control-label" for="customCheck">Remember Me</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-user btn-block">
                            Login
                        </button>
                    </form>

                    <hr>

                    <div class="text-center">
                        <a href="#" class="small text-decoration-none text-muted">Forgot Password?</a>
                    </div>
                </section>
            </div>
        </div>
    </main>

</body>
</html>
