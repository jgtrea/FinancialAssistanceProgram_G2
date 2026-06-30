<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
  $prefixOptions  = $prefixOptions  ?? ['', 'DR.', 'ENGR.', 'HON.', 'MR.', 'MRS.', 'MS.', 'PROF.'];
  $suffixOptions  = $suffixOptions  ?? ['', 'JR.', 'SR.', 'II', 'III', 'IV', 'V'];
  $degreeOptions  = $degreeOptions  ?? [
      'None', 'MPA', 'BSc', 'BA',
      'Master', 'MSc', 'MA', 'MBA',
      'Doctorate', 'PhD', 'MD', 'JD', 'LLB', 'DDS', 'EdD',
      'Other',
  ];
  $customPrefixes = $customPrefixes ?? [];
  $customSuffixes = $customSuffixes ?? [];
  $customDegrees  = $customDegrees  ?? [];

  $selectedPrefix = strtoupper((string) ($signatory['prefix'] ?? ''));
  $selectedSuffix = strtoupper((string) ($signatory['suffix'] ?? ''));

  // Prefix: detect custom (not in known list)
  $knownPrefixes   = array_merge(array_filter($prefixOptions), array_map('strtoupper', $customPrefixes));
  $prefixOtherVal  = '';
  $prefixSelectVal = $selectedPrefix;
  if ($selectedPrefix !== '' && !in_array($selectedPrefix, array_merge($knownPrefixes, ['__OTHER__', '']))) {
      $prefixOtherVal  = $selectedPrefix;
      $prefixSelectVal = '__OTHER__';
  }

  // Suffix: detect custom (not in known list)
  $knownSuffixes   = array_merge(array_filter($suffixOptions), array_map('strtoupper', $customSuffixes));
  $suffixOtherVal  = '';
  $suffixSelectVal = $selectedSuffix;
  if ($selectedSuffix !== '' && !in_array($selectedSuffix, array_merge($knownSuffixes, ['__OTHER__', '']))) {
      $suffixOtherVal  = $selectedSuffix;
      $suffixSelectVal = '__OTHER__';
  }

  // Degree: known custom (in others_options) → select directly; unknown custom → show Other input
  $rawDegree        = (string) ($signatory['degree'] ?? 'None');
  $isCustomDegree   = $rawDegree !== '' && !in_array($rawDegree, $degreeOptions, true);
  $isKnownCustom    = $isCustomDegree && in_array($rawDegree, $customDegrees, true);
  $selectedDegree   = ($isCustomDegree && !$isKnownCustom) ? 'Other' : $rawDegree;
  $degreeOtherValue = ($isCustomDegree && !$isKnownCustom) ? $rawDegree : '';
?>

<div class="vs-page-header mb-4">
  <div>
    <h4 class="vs-page-title"><?= esc($title) ?></h4>
    <p class="vs-page-sub">Enter Signatory Details And Upload A Signature Image.</p>
  </div>
  <a href="<?= base_url('/signatories') ?>" class="btn btn-secondary">Back to signatories</a>
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
          <select id="prefix" data-field-name="prefix" <?= $prefixSelectVal !== '__OTHER__' ? 'name="prefix"' : '' ?> class="vs-input js-filter-select" data-placeholder="- SELECT -" data-no-search="1">
            <option value="">None</option>
            <?php foreach ($prefixOptions as $option): if ($option === '') continue; ?>
              <option value="<?= esc($option) ?>" <?= $prefixSelectVal === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
            <?php endforeach ?>
            <?php foreach ($customPrefixes as $cp): if (in_array($cp, $prefixOptions)) continue; ?>
              <option value="<?= esc($cp) ?>" <?= $prefixSelectVal === $cp ? 'selected' : '' ?>><?= esc($cp) ?></option>
            <?php endforeach ?>
            <option value="__OTHER__" <?= $prefixSelectVal === '__OTHER__' ? 'selected' : '' ?>>OTHERS</option>
          </select>
          <div id="prefixOtherWrap" style="<?= $prefixSelectVal === '__OTHER__' ? '' : 'display:none' ?>" class="mt-2">
            <input id="prefixOther" <?= $prefixSelectVal === '__OTHER__' ? 'name="prefix"' : '' ?> type="text" class="vs-input vs-uppercase" placeholder="Other prefix" value="<?= esc($prefixOtherVal) ?>">
          </div>
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
          <select id="suffix" data-field-name="suffix" <?= $suffixSelectVal !== '__OTHER__' ? 'name="suffix"' : '' ?> class="vs-input js-filter-select" data-placeholder="- SELECT -" data-no-search="1">
            <option value="">None</option>
            <?php foreach ($suffixOptions as $option): if ($option === '') continue; ?>
              <option value="<?= esc($option) ?>" <?= $suffixSelectVal === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
            <?php endforeach ?>
            <?php foreach ($customSuffixes as $cs): if (in_array($cs, $suffixOptions)) continue; ?>
              <option value="<?= esc($cs) ?>" <?= $suffixSelectVal === $cs ? 'selected' : '' ?>><?= esc($cs) ?></option>
            <?php endforeach ?>
            <option value="__OTHER__" <?= $suffixSelectVal === '__OTHER__' ? 'selected' : '' ?>>OTHERS</option>
          </select>
          <div id="suffixOtherWrap" style="<?= $suffixSelectVal === '__OTHER__' ? '' : 'display:none' ?>" class="mt-2">
            <input id="suffixOther" <?= $suffixSelectVal === '__OTHER__' ? 'name="suffix"' : '' ?> type="text" class="vs-input vs-uppercase" placeholder="Other suffix" value="<?= esc($suffixOtherVal) ?>">
          </div>
        </div>

        <div>
          <label class="vs-label" for="degree">Degree</label>
          <select id="degree" name="degree" class="vs-input js-filter-select" data-placeholder="TYPE OR SELECT">
            <option></option>
            <?php foreach ($degreeOptions as $option): ?>
              <?php if ($option === 'Other'): ?>
                <?php foreach ($customDegrees as $cd): if (in_array($cd, $degreeOptions)) continue; ?>
                  <option value="<?= esc($cd) ?>" <?= $selectedDegree === $cd ? 'selected' : '' ?>><?= esc($cd) ?></option>
                <?php endforeach ?>
              <?php endif ?>
              <option value="<?= esc($option) ?>" <?= $selectedDegree === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
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
        <button type="submit" class="btn btn-primary">
          <?= $signatory ? 'Update' : 'Save' ?>
        </button>
        <a href="<?= base_url('/signatories') ?>" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof initOtherInput === 'function') {
      initOtherInput('prefix', 'prefixOtherWrap', 'prefixOther');
      initOtherInput('suffix', 'suffixOtherWrap', 'suffixOther');
    }

    var sel = document.getElementById('degree');
    var oth = document.getElementById('degree_other');
    if (!sel || !oth) return;

    function toggleDegree() {
      if (sel.value === 'Other') {
        oth.style.display = 'block';
        oth.focus();
      } else {
        oth.style.display = 'none';
        oth.value = '';
      }
    }

    sel.addEventListener('change', toggleDegree);
    if (window.jQuery) jQuery(sel).on('change.select2', toggleDegree);
  });
}());
</script>

<?= $this->endSection() ?>
