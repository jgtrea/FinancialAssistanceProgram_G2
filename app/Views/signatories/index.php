<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Signatories</h3>
    <?php if ($signatory): ?>
        <a href="<?= base_url('/signatories') ?>" class="btn btn-secondary btn-sm">Cancel Edit</a>
    <?php endif; ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<form action="<?= base_url('/signatories/save') ?>" method="post" class="border rounded p-3 mb-4">
    <?= csrf_field() ?>

    <input type="hidden" name="signatory_id" value="<?= esc($signatory['signatory_id'] ?? '') ?>">

    <div class="row align-items-end">
        <div class="col-md-4 mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required value="<?= esc(old('full_name', $signatory['full_name'] ?? '')) ?>">
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Position Title</label>
            <input type="text" name="position_title" class="form-control" required value="<?= esc(old('position_title', $signatory['position_title'] ?? '')) ?>">
        </div>

        <div class="col-md-3 mb-3">
            <label class="form-label">Signature Image Path</label>
            <input type="text" name="signature_image" class="form-control" value="<?= esc(old('signature_image', $signatory['signature_image'] ?? '')) ?>">
        </div>

        <div class="col-md-1 mb-3">
            <div class="form-check">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive"
                    <?= (int) old('is_active', $signatory['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="isActive">Active</label>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <?= $signatory ? 'Update Signatory' : 'Add Signatory' ?>
    </button>
</form>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Position Title</th>
                <th>Signature Image</th>
                <th>Status</th>
                <th style="width: 220px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($signatories)): ?>
                <?php foreach ($signatories as $item): ?>
                    <tr>
                        <td><?= esc($item['full_name']) ?></td>
                        <td><?= esc($item['position_title']) ?></td>
                        <td><?= esc($item['signature_image'] ?: '-') ?></td>
                        <td>
                            <span class="badge <?= (int) $item['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= (int) $item['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <a href="<?= base_url('/signatories/edit/' . $item['signatory_id']) ?>" class="btn btn-warning btn-sm">Edit</a>

                            <?php if ((int) $item['is_active'] === 1): ?>
                                <form action="<?= base_url('/signatories/status/' . $item['signatory_id'] . '/deactivate') ?>" method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-secondary btn-sm">Deactivate</button>
                                </form>
                            <?php else: ?>
                                <form action="<?= base_url('/signatories/status/' . $item['signatory_id'] . '/activate') ?>" method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No signatories found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
