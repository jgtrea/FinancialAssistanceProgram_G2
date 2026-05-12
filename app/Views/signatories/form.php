<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<h3><?= $signatory ? 'Edit Signatory' : 'Add Signatory' ?></h3>

<form action="<?= base_url('/signatories/save') ?>" method="post">
    <?= csrf_field() ?>

    <input type="hidden" name="signatory_id" value="<?= esc($signatory['signatory_id'] ?? '') ?>">

    <div class="row">
        <div class="col-md-3 mb-3">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" required
                   value="<?= esc($signatory['first_name'] ?? '') ?>">
        </div>

        <div class="col-md-3 mb-3">
            <label>Middle Name</label>
            <input type="text" name="middle_name" class="form-control"
                   value="<?= esc($signatory['middle_name'] ?? '') ?>">
        </div>

        <div class="col-md-3 mb-3">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" required
                   value="<?= esc($signatory['last_name'] ?? '') ?>">
        </div>

        <div class="col-md-3 mb-3">
            <label>Suffix</label>
            <input type="text" name="suffix" class="form-control"
                   value="<?= esc($signatory['suffix'] ?? '') ?>">
        </div>

        <div class="col-md-8 mb-3">
            <label>Position Title</label>
            <input type="text" name="position_title" class="form-control" required
                   value="<?= esc($signatory['position_title'] ?? '') ?>">
        </div>

        <div class="col-md-4 mb-3">
            <label>Status</label>
            <select name="is_active" class="form-control">
                <option value="1" <?= (($signatory['is_active'] ?? 1) == 1) ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= (($signatory['is_active'] ?? 1) == 0) ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
    </div>

    <button class="btn btn-primary">
        <?= $signatory ? 'Update Signatory' : 'Save Signatory' ?>
    </button>

    <a href="<?= base_url('/signatories') ?>" class="btn btn-secondary">Back</a>
</form>

<?= $this->endSection() ?>