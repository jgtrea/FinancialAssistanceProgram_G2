<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
  $prefixOptions = $prefixOptions ?? ['', 'DR.', 'ENGR.', 'HON.', 'MR.', 'MRS.', 'MS.', 'PROF.'];
  $suffixOptions = $suffixOptions ?? ['', 'JR.', 'SR.', 'II', 'III', 'IV', 'V'];
  $degreeOptions = $degreeOptions ?? [
      'None', 'MPA', 'BSc', 'BA',
      'Master', 'MSc', 'MA', 'MBA',
      'Doctorate', 'PhD', 'MD', 'JD', 'LLB', 'DDS', 'EdD',
      'Other',
  ];
  $selectedPrefix = strtoupper((string) ($signatory['prefix'] ?? ''));
  $selectedSuffix = strtoupper((string) ($signatory['suffix'] ?? ''));
  $rawDegree      = (string) ($signatory['degree'] ?? 'None');
  $isCustomDegree = $rawDegree !== '' && !in_array($rawDegree, $degreeOptions, true);
  $selectedDegree = $isCustomDegree ? 'Other' : $rawDegree;
  $degreeOtherValue = $isCustomDegree ? $rawDegree : '';
?>

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
          <select id="prefix" name="prefix" class="vs-input">
            <?php foreach ($prefixOptions as $option): ?>
              <option value="<?= esc($option) ?>" <?= $selectedPrefix === $option ? 'selected' : '' ?>>
                <?= $option === '' ? '-- Select --' : esc($option) ?>
              </option>
            <?php endforeach ?>
          </select>
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
          <select id="suffix" name="suffix" class="vs-input">
            <?php foreach ($suffixOptions as $option): ?>
              <option value="<?= esc($option) ?>" <?= $selectedSuffix === $option ? 'selected' : '' ?>>
                <?= $option === '' ? '-- Select --' : esc($option) ?>
              </option>
            <?php endforeach ?>
          </select>
        </div>

        <div>
          <label class="vs-label" for="degree">Degree</label>
          <select id="degree" name="degree" class="vs-input">
            <?php foreach ($degreeOptions as $option): ?>
              <option value="<?= esc($option) ?>" <?= $selectedDegree === $option ? 'selected' : '' ?>>
                <?= esc($option) ?>
              </option>
            <?php endforeach ?>
          </select>
          <input id="degree_other" name="degree_other" type="text"
                 class="vs-input mt-2" placeholder="Specify degree"
                 value="<?= esc($degreeOtherValue, 'attr') ?>"
                 style="display:<?= $selectedDegree === 'Other' ? 'block' : 'none' ?>">
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

<script>
(function () {
  var sel = document.getElementById('degree');
  var oth = document.getElementById('degree_other');
  if (!sel || !oth) return;
  sel.addEventListener('change', function () {
    if (sel.value === 'Other') {
      oth.style.display = 'block';
      oth.focus();
    } else {
      oth.style.display = 'none';
      oth.value = '';
    }
  });
}());
</script>

<?= $this->endSection() ?>
