<!DOCTYPE html>
<html lang="en" class="vh-100">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Biñan City - Login</title>

    <?php pre_style('default_lay'); ?>
    <?php pre_style('sbadmin2'); ?>
</head>

<body class="bg-white vh-100 d-flex align-items-center overflow-hidden">
    <div id="nav-container"></div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-center align-items-end text-right p-5 text-white" 
                                 style="background: url('<?= base_url('images/bg_binan.jpg') ?>'); background-size: cover; background-position: center;">
                                
                                <p class="small mb-0 font-weight-bold" style="letter-spacing: 2px;">WELCOME TO</p>
                                <h1 class="font-weight-bold mb-3" style="font-size: 2.2rem;">Grants and Scholarships Program</h1>
                                <hr class="w-50 border-white" style="opacity: 0.5; margin-right: 0;">
                                <p class="mb-0 mt-2">Login to Access Dashboard</p>
                            </div>
                            
                            <div class="col-lg-6 bg-white">
                                <div class="p-5">

                                    <?php if (session()->getFlashdata('error')): ?>
                                        <div class="alert alert-danger small py-2">
                                            <?= session()->getFlashdata('error') ?>
                                        </div>
                                    <?php endif; ?>

                                    <form class="user" action="<?= base_url('auth_login') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        
                                        <div class="form-group">
                                            <input type="email" name="username" class="form-control form-control-user"
                                                placeholder="Enter Email Address..." required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="password" class="form-control form-control-user"
                                                placeholder="Password" required>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox small">
                                                <input type="checkbox" class="custom-control-input" id="customCheck">
                                                <label class="custom-control-label" for="customCheck">Remember Me</label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block shadow-sm">
                                            Login
                                        </button>
                                    </form>
                                    
                                    <hr>
                                    <div class="text-center">
                                        <a class="small text-decoration-none text-muted" href="#">Forgot Password?</a>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>
                </div>

            </div>
        </div>
    </main>

<script>
    var navType = 'auth';
    var baseUrl = '<?= base_url() ?>';
</script>
<script src="<?= base_url('js/nav.js') ?>"></script>

</body>
</html>
