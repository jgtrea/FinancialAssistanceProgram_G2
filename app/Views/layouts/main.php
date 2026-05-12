<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-token-value" content="<?= csrf_hash() ?>">
    <title><?= $title ?? 'Voucher System' ?></title>
    <?php pre_style('sbadmin2') ?>
    
</head>

<body class="app-shell">

<nav class="navbar navbar-dark bg-dark app-navbar">
    <div class="container app-navbar__inner">

        <a class="navbar-brand app-navbar__brand" href="<?= base_url('/') ?>">
            Voucher System
        </a>

        <div class="navbar-nav app-navbar__links">
            <a class="nav-link" href="<?= base_url('/students') ?>">Students</a>
            <a class="nav-link" href="<?= base_url('/vouchers') ?>">Vouchers</a>
            <a class="nav-link" href="<?= base_url('/signatories') ?>">Signatories</a>
            <a class="nav-link" href="<?= base_url('/archive') ?>">Archive</a>
            <a class="nav-link" href="<?= base_url('/audit-logs') ?>">Audit Logs</a>
        </div>

    </div>
</nav>

<main class="container app-content">
    <?= $this->renderSection('content') ?>
</main>
<?= script_tag('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js') ?>
<?= script_tag('https://code.jquery.com/jquery-3.7.1.min.js') ?>
<?= script_tag(base_url('js/students.js')) ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>
