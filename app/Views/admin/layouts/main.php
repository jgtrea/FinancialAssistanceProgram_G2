<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-token-value" content="<?= csrf_hash() ?>">
    <title><?= $title ?? 'Voucher System' ?></title>

    <?= link_tag('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css') ?>
    <?= link_tag(base_url('css/app.css')) ?>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">

        <a class="navbar-brand" href="<?= base_url('/') ?>">
            Voucher System
        </a>

        <div class="navbar-nav">
            <a class="nav-link" href="<?= base_url('admin/user_management') ?>">Users</a>
            <a class="nav-link" href="<?= base_url('admin/audit-logs') ?>">Audit Logs</a>
        </div>

    </div>
</nav>

<div class="container mt-4">
    <?= $this->renderSection('content') ?>
</div>

<?= script_tag('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js') ?>
<?= script_tag('https://code.jquery.com/jquery-3.7.1.min.js') ?>
<?= script_tag(base_url('js/users_m.js')) ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>