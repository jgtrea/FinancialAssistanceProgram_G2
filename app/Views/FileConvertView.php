<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container mt-4">
    <h4>Import Vouchers / Students</h4>
    <p>Upload an Excel (.xlsx / .xls) file with voucher data.</p>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <form action="<?= site_url('import_data') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="excel_file" class="form-label">Excel File</label>
            <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
        </div>
        <button type="submit" class="btn btn-primary">Import</button>
        <a href="<?= site_url('admin/vouchers') ?>" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?= $this->endSection() ?>