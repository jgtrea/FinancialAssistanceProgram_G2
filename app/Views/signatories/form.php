<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
  <div>
    <h4 class="vs-page-title"><?= esc($title) ?></h4>
    <p class="vs-page-sub">Enter signatory details and upload a signature image.</p>
  </div>
  <a href="<?= base_url('/signatories') ?>" class="vs-btn vs-btn-outline">Back to signatories</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
  <div class="vs-alert vs-alert-error mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif ?>

<div class="vs-card">
  <div class="vs-card-body">
    <form action="<?= base_url('/signatories/save') ?>" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="signatory_id" value="<?= esc($signatory['signatory_id'] ?? '') ?>">

      <div class="vs-form-grid vs-form-grid-4">

        <div>
          <label class="vs-label" for="prefix">Prefix</label>
          <input id="prefix" name="prefix" type="text" class="vs-input vs-uppercase"
                 value="<?= esc($signatory['prefix'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label required" for="first_name">First Name</label>
          <input id="first_name" name="first_name" type="text"
                 class="vs-input vs-uppercase" required
                 value="<?= esc($signatory['first_name'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="middle_name">Middle Name</label>
          <input id="middle_name" name="middle_name" type="text"
                 class="vs-input vs-uppercase"
                 value="<?= esc($signatory['middle_name'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label required" for="last_name">Last Name</label>
          <input id="last_name" name="last_name" type="text"
                 class="vs-input vs-uppercase" required
                 value="<?= esc($signatory['last_name'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="suffix">Suffix</label>
          <input id="suffix" name="suffix" type="text"
                 class="vs-input vs-uppercase"
                 value="<?= esc($signatory['suffix'] ?? '') ?>">
        </div>

        <div class="vs-span-2">
          <label class="vs-label required" for="position_title">Position Title</label>
          <input id="position_title" name="position_title" type="text"
                 class="vs-input vs-uppercase" required
                 value="<?= esc($signatory['position_title'] ?? '') ?>">
        </div>

        <div class="vs-span-4">
          <label class="vs-label" for="signature_image">Signature Image</label>
          <input id="signature_image" name="signature_image" type="file"
                 class="vs-input" accept="image/png,image/jpeg,image/jpg,image/webp">
          <small class="text-muted">PNG, JPG, or WEBP — max 2 MB. Leave empty to keep the current image.</small>

          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox"
                   name="auto_remove_bg" value="1" id="autoRemoveBg" checked>
            <label class="form-check-label" for="autoRemoveBg">
              Remove background automatically (best for signatures on plain white paper)
            </label>
          </div>

          <?php if (!empty($signatory['signature_image'])): ?>
            <div class="mt-3">
              <p class="vs-label mb-1">Current Signature</p>
              <img src="<?= base_url('signatories/signature/' . $signatory['signatory_id']) ?>"
                   alt="Current signature"
                   style="max-height:80px;background:#fff;padding:4px;border:1px solid #ddd;border-radius:4px;">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox"
                       name="remove_signature" value="1" id="removeSignature">
                <label class="form-check-label" for="removeSignature">Remove current signature</label>
              </div>
            </div>
          <?php endif ?>
        </div>

      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="vs-btn vs-btn-primary">
          <?= $signatory ? 'Update' : 'Save' ?>
        </button>
        <a href="<?= base_url('/signatories') ?>" class="vs-btn vs-btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
