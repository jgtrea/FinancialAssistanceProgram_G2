<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<h3><?= $signatory ? 'Edit Signatory' : 'Add Signatory' ?></h3>

<?php if (session()->getFlashdata('error')): ?>
    <div class="vs-alert vs-alert-danger mb-3">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<form action="<?= base_url('/signatories/save') ?>" method="post" enctype="multipart/form-data">
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

        <div class="col-md-8 mb-3">
            <label>Signature Image</label>
            <input type="file" name="signature_image" class="form-control"
                   accept="image/png, image/jpeg, image/jpg, image/webp">
            <small class="text-muted">PNG, JPG, or WEBP. Max 2 MB. Leave empty to keep existing.</small>

            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox"
                       name="auto_remove_bg" value="1" id="autoRemoveBg" checked>
                <label class="form-check-label" for="autoRemoveBg">
                    Remove background automatically (best with signatures on plain white paper)
                </label>
            </div>

            <?php if (!empty($signatory['signature_image'])): ?>
                <div class="mt-2">
                    <p class="mb-1"><strong>Current signature:</strong></p>
                    <img src="<?= base_url('signatories/signature/' . $signatory['signatory_id']) ?>"
                         alt="Current signature"
                         style="max-height: 80px; background: #fff; padding: 4px; border: 1px solid #ddd;">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox"
                               name="remove_signature" value="1" id="removeSignature">
                        <label class="form-check-label" for="removeSignature">
                            Remove current signature
                        </label>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <button class="btn btn-primary">
        <?= $signatory ? 'Update Signatory' : 'Save Signatory' ?>
    </button>

    <a href="<?= base_url('/signatories') ?>" class="btn btn-secondary">Back</a>
</form>

<?= $this->endSection() ?>