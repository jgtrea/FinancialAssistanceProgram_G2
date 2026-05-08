<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biñan City</title>
    <?php pre_style('default_lay'); ?>
</head>
<body class="bg-light">

    <nav class="navbar navbar-light bg-white px-4 shadow-sm">
        <span class="navbar-brand mb-0 h1 fw-bold" style="color: var(--biñan-green);">Biñan City</span>
    </nav>

    <div class="container d-flex align-items-center min-vh-100">
        <div class="row w-100 shadow-soft border rounded-4 overflow-hidden bg-white mx-auto" style="max-width: 900px;">
            
            <!-- Left Side: Login Form -->
            <div class="col-md-6 p-5">
                <h2 class="playfair-display-header mb-4" style="color: var(--gray-800);">Log in to your account</h2>
                
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger rounded-3 mb-3">
                        <?= session()->getFlashdata('error') ?>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('auth_login') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="text-label mb-1">Username</label>
                        <input type="text" name="username" class="form-control rounded-pill bg-light border-0 px-3" style="height: 45px;" placeholder="Enter your username" required>
                    </div>
                    <div class="mb-3">
                        <label class="text-label mb-1">Password</label>
                        <input type="password" name="password" class="form-control rounded-pill bg-light border-0 px-3" style="height: 45px;" placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit" class="btn-base btn-blue w-100 rounded-pill py-2 mt-2">Login</button>

                    <div class="text-end mt-3">
                        <a href="#" class="text-decoration-none text-muted small">Forgot Password?</a>
                    </div>
                </form>
            </div>

            <div class="col-md-6 bg-gradient-green p-5 d-flex flex-column justify-content-center text-white text-center text-md-start">
                <h1 class="playfair-display-header fw-bold mb-4">Grants and Scholarships Program</h1>
                <p class="lead" style="font-size: 0.95rem; opacity: 0.9;">Stop waiting for checks and start focusing on your goals. Our platform turns traditional financial aid into instant opportunity through a secure, digital voucher system.</p>
            </div>

        </div>
    </div>

</body>
</html>