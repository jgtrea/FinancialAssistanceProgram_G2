<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-token-value" content="<?= csrf_hash() ?>">
    <title><?= $title ?? 'Voucher System' ?></title>
    <?php pre_style('default_lay'); ?>
</head>
<body class="vs-app">

<div id="nav-container"></div>

<main class="container app-content">
    <?= $this->renderSection('content') ?>
</main>

<script>
    var navType = '<?= !session('isLoggedIn') ? 'auth' : (session('role') === 'admin' ? 'admin' : 'user') ?>';
    var baseUrl = '<?= base_url() ?>';
</script>

<?php pre_script('default_lay'); ?>
<?php if (session('isLoggedIn') && session('role') === 'admin'): ?>
    <?= script_tag(base_url('js/users_m.js')) ?>
<?php else: ?>
    <?= script_tag('js/students.js') ?>
<?php endif; ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>
