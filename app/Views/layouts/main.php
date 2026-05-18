<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-token-value" content="<?= csrf_hash() ?>">
    <title><?= esc($title ?? 'Voucher System') ?></title>
    <?php pre_style('app'); ?>
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

    <?php pre_script('app'); ?>
    <?= $this->renderSection('scripts') ?>
</body>

</html>
