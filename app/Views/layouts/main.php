<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-token-value" content="<?= csrf_hash() ?>">
    <title><?= esc($title ?? 'Voucher System') ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/city_education_office_seal.png') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= base_url('assets/img/city_education_office_seal.png') ?>">
    <?php pre_style('app'); ?>
    <script>
    window.__VS = {
        csrf: { name: '<?= csrf_token() ?>', hash: '<?= csrf_hash() ?>' },
        urls: {
            profileData:   '<?= site_url('profile/data') ?>',
            profileUpdate: '<?= site_url('profile') ?>',
            voucherExport: '<?= site_url('vouchers/export') ?>',
            schoolExport:  '<?= site_url('admin/schools/export') ?>',
        },
        pageData: {}
    };
    </script>
</head>

<body class="sb-nav-fixed">
    <?= $this->include('partials/topbar') ?>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?= $this->include('partials/sidebar') ?>
        </div>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 py-4">
                    <?= $this->renderSection('content') ?>
                </div>
            </main>

            <?= $this->include('partials/footer') ?>
        </div>
    </div>

    <?= pre_modal('layout') ?>

    <?php pre_script('app'); ?>
    <?= $this->renderSection('scripts') ?>
</body>

</html>
