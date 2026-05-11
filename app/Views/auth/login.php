<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biñan City</title>
    <?php pre_style('default_lay'); ?>
    <?php pre_style('sbadmin2'); ?>
</head>
<body style="background-image: url('<?= base_url('images/bg_binan.jpg') ?>'); background-size: cover; background-position: center; background-repeat: no-repeat; min-height: 100vh; margin: 0; padding: 0;">

    <nav class="navbar navbar-light px-4 py-3" style="background: rgba(0,0,0,0.5);">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= base_url('images/logo_binan.png') ?>" 
                 alt="Biñan City Logo"
                 style="width: 45px; max-width: 100%;">
            <span class="navbar-brand mb-0 h1 fw-bold" style="color: white;">Biñan City</span>
        </div>
    </nav>

    <div class="container-fluid d-flex align-items-center justify-content-center min-vh-100">
        <div class="row justify-content-center w-100 align-items-center g-4" style="max-width: 1100px;">

            <!-- Left: Text -->
            <div class="col-lg-6">
                <div style="border-radius: 12px; padding: 3rem 2.5rem; min-height: 500px; display: flex; flex-direction: column; justify-content: center; align-items: flex-end; text-align: right;">
                    <p class="inter-normal" style="color: white; font-weight: bold; margin-bottom: 0.5rem;">WELCOME TO</p>
                    <h2 class="playfair-display-header" style="color: white; font-weight: bold; margin-bottom: 1rem;">Grants and Scholarships Program</h2>
                    <hr style="width: 50%; border-color: white; opacity: 0.75;">
                    <p style="color: white; margin-top: 0.5rem;">Login to Access Dashboard</p>
                </div>
            </div>

            <div class="col-lg-5">
                <div style="background: white; border-radius: 12px; padding: 3rem 2.5rem; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger rounded-3 mb-3">
                            <?= session()->getFlashdata('error') ?>
                        </div>
                    <?php endif; ?>

                    <form class="user" action="<?= base_url('auth_login') ?>" method="POST">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <input type="text" name="username" 
                                class="form-control form-control-user"
                                placeholder="Enter your Email" required>
                        </div>

                        <div class="form-group">
                            <input type="password" name="password" 
                                class="form-control form-control-user"
                                placeholder="Enter your Password" required>
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

                </div>
            </div>

        </div>
    </div>

</body>
</html>