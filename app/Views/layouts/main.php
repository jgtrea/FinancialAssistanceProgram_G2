<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-token-value" content="<?= csrf_hash() ?>">
    <title><?= $title ?? 'Voucher System' ?></title>
    <?php
    pre_style('default_lay');
    pre_style('sbadmin2');
    ?>
</head>

<body class="bg-white">

<div id="nav-container"></div>

<div class="container mt-4">
    <?= $this->renderSection('content') ?>
</div>

<script>
    var navType = '<?= !session('isLoggedIn') ? 'auth' : (session('role') === 'admin' ? 'admin' : 'user') ?>';
    var baseUrl = '<?= base_url() ?>';
</script>

<?= script_tag('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js') ?>
<?= script_tag('https://code.jquery.com/jquery-3.7.1.min.js') ?>
<?php if (session('isLoggedIn') && session('role') === 'admin'): ?>
    <?= script_tag(base_url('js/users_m.js')) ?>
<?php else: ?>
    <?= script_tag('js/students.js') ?>
<?php endif; ?>
<?= script_tag('js/nav.js') ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>