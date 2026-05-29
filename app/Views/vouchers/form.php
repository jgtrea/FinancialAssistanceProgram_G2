<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php helper('form') ?>
<?php
  $role = session()->get('role') === 'admin' || strpos($action, '/admin/') !== false ? 'admin' : 'user';
  $prefix = $role === 'admin' ? 'admin' : 'user';
  $suffix = old('suffix', $voucher['suffix'] ?? '');
  $remarks = old('remarks_status', $voucher['remarks_status'] ?? '');
  $selectedJuniorHighSchool = old('junior_high_school', $voucher['junior_high_school'] ?? '');
  $selectedSeniorHighSchool = old('preferred_senior_high_school', $voucher['preferred_senior_high_school'] ?? '');
?>

<div class="vs-page-header mb-4">
  <div>
    <h4 class="vs-page-title"><?= esc($title) ?></h4>
    <p class="vs-page-sub">Enter student and voucher details.</p>
  </div>
  <a href="<?= site_url($prefix . '/students') ?>" class="vs-btn vs-btn-outline">Back to Vouchers</a>
</div>

<?php if (isset($validation) && $validation->getErrors()): ?>
  <div class="vs-alert vs-alert-error mb-3">
    <ul>
      <?php foreach ($validation->getErrors() as $error): ?>
        <li><?= esc($error) ?></li>
      <?php endforeach ?>
    </ul>
  </div>
<?php endif ?>

<div class="vs-card">
  <div class="vs-card-body">
    <form method="POST" action="<?= esc($action) ?>">
      <?= csrf_field() ?>

      <div class="vs-form-grid vs-form-grid-4">

        <div>
          <label class="vs-label required" for="voucher_date">Voucher Date</label>
          <input id="voucher_date" name="voucher_date" type="date"
                 class="vs-input <?= ($validation && $validation->hasError('voucher_date')) ? 'vs-input-error' : '' ?>"
                 value="<?= old('voucher_date', $voucher['voucher_date'] ?? '') ?>" required>
        </div>

        <div>
          <label class="vs-label required" for="first_name">First Name</label>
          <input id="first_name" name="first_name" type="text"
                 class="vs-input vs-uppercase <?= ($validation && $validation->hasError('first_name')) ? 'vs-input-error' : '' ?>"
                 value="<?= old('first_name', $voucher['first_name'] ?? '') ?>" required>
        </div>

        <div>
          <label class="vs-label" for="middle_name">Middle Name</label>
          <input id="middle_name" name="middle_name" type="text" class="vs-input vs-uppercase"
                 value="<?= old('middle_name', $voucher['middle_name'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label required" for="last_name">Last Name</label>
          <input id="last_name" name="last_name" type="text"
                 class="vs-input vs-uppercase <?= ($validation && $validation->hasError('last_name')) ? 'vs-input-error' : '' ?>"
                 value="<?= old('last_name', $voucher['last_name'] ?? '') ?>" required>
        </div>

        <div>
          <label class="vs-label" for="suffix">Suffix</label>
          <select id="suffix" name="suffix" class="vs-input">
            <option value="" <?= $suffix === '' ? 'selected' : '' ?>>-- Select --</option>
            <?php foreach (['JR.' => 'Jr.', 'SR.' => 'Sr.', 'II' => 'II', 'III' => 'III', 'IV' => 'IV'] as $value => $label): ?>
              <option value="<?= esc($value) ?>" <?= strtoupper($suffix) === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach ?>
          </select>
        </div>

        <div>
          <label class="vs-label" for="gender">Sex</label>
          <select id="gender" name="gender" class="vs-input">
            <?php $gender = old('gender', $voucher['gender'] ?? '') ?>
            <option value="">-- Select --</option>
            <option value="MALE"   <?= strtoupper($gender) === 'MALE'   ? 'selected' : '' ?>>Male</option>
            <option value="FEMALE" <?= strtoupper($gender) === 'FEMALE' ? 'selected' : '' ?>>Female</option>
          </select>
        </div>

        <div>
          <label class="vs-label" for="gwa">GWA</label>
          <input id="gwa" name="gwa" type="number" step="0.01" class="vs-input"
                 value="<?= old('gwa', $voucher['gwa'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="rank_no">Rank No.</label>
          <input id="rank_no" name="rank_no" type="number" class="vs-input"
                 value="<?= old('rank_no', $voucher['rank_no'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="contact_number">Contact Number</label>
          <input id="contact_number" name="contact_number" type="text" class="vs-input vs-uppercase"
                 value="<?= old('contact_number', $voucher['contact_number'] ?? '') ?>">
        </div>

        <div class="vs-span-2">
          <label class="vs-label required" for="junior_high_school">Junior High School</label>
          <div class="vs-school-picker" id="jhs-picker">
            <div class="vs-school-picker-wrap">
              <input type="text" id="junior_high_school" name="junior_high_school"
                     class="vs-input vs-uppercase <?= ($validation && $validation->hasError('junior_high_school')) ? 'vs-input-error' : '' ?>"
                     value="<?= esc($selectedJuniorHighSchool) ?>"
                     placeholder="Type school name..." autocomplete="off" required>
              <div class="vs-school-picker-actions">
                <button type="button" class="vs-school-picker-toggle" title="Show schools"><?= asset_icon('dropdown') ?></button>
              </div>
            </div>
            <ul class="vs-school-picker-list">
              <?php foreach (($juniorHighSchools ?? []) as $school): ?>
                <li data-value="<?= esc($school['school_name'] ?? '') ?>"><?= esc($school['school_name'] ?? '') ?></li>
              <?php endforeach ?>
              <li class="vs-school-picker-no-results" style="display:none">No results found</li>
            </ul>
          </div>
        </div>

        <div class="vs-span-2">
          <label class="vs-label required" for="preferred_senior_high_school">Preferred Senior High School</label>
          <div class="vs-school-picker" id="shs-picker">
            <div class="vs-school-picker-wrap">
              <input type="text" id="preferred_senior_high_school" name="preferred_senior_high_school"
                     class="vs-input vs-uppercase <?= ($validation && $validation->hasError('preferred_senior_high_school')) ? 'vs-input-error' : '' ?>"
                     value="<?= esc($selectedSeniorHighSchool) ?>"
                     placeholder="Type school name..." autocomplete="off" required>
              <div class="vs-school-picker-actions">
                <button type="button" class="vs-school-picker-toggle" title="Show schools"><?= asset_icon('dropdown') ?></button>
              </div>
            </div>
            <ul class="vs-school-picker-list">
              <?php foreach (($seniorHighSchools ?? []) as $school): ?>
                <li data-value="<?= esc($school['school_name'] ?? '') ?>"><?= esc($school['school_name'] ?? '') ?></li>
              <?php endforeach ?>
              <li class="vs-school-picker-no-results" style="display:none">No results found</li>
            </ul>
          </div>
        </div>

        <div>
          <label class="vs-label" for="remarks_status">Remarks</label>
          <select id="remarks_status" name="remarks_status" class="vs-input">
            <option value="" <?= $remarks === '' ? 'selected' : '' ?>>-- Select --</option>
            <?php foreach (['PASSED' => 'Passed', 'FOR REVIEW' => 'For Review', 'FAILED' => 'Failed'] as $value => $label): ?>
              <option value="<?= esc($value) ?>" <?= strtoupper($remarks) === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach ?>
          </select>
        </div>

        <div>
          <label class="vs-label required" for="school_year">School Year</label>
          <input id="school_year" name="school_year" type="text"
                 class="vs-input <?= ($validation && $validation->hasError('school_year')) ? 'vs-input-error' : '' ?>"
                 value="<?= old('school_year', $voucher['school_year'] ?? '') ?>"
                 placeholder="e.g. 2025-2026" required>
        </div>

        <div>
          <label class="vs-label" for="eligibility_status">Eligibility</label>
          <select id="eligibility_status" name="eligibility_status" class="vs-input">
            <?php $eligibility = old('eligibility_status', $voucher['eligibility_status'] ?? '') ?>
            <option value="">-- Select --</option>
            <option value="eligible"     <?= $eligibility === 'eligible'     ? 'selected' : '' ?>>Eligible</option>
            <option value="not_eligible" <?= $eligibility === 'not_eligible' ? 'selected' : '' ?>>Not Eligible</option>
          </select>
        </div>

      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="vs-btn vs-btn-primary"><?= !empty($voucher['student_id'] ?? null) ? 'Update' : esc($title) ?></button>
        <a href="<?= site_url($prefix . '/students') ?>" class="vs-btn vs-btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
    function initPicker(id) {
        var picker  = document.getElementById(id);
        if (!picker) return;
        var input   = picker.querySelector('input');
        var toggle  = picker.querySelector('.vs-school-picker-toggle');
        var list    = picker.querySelector('.vs-school-picker-list');
        var items   = list ? Array.from(list.querySelectorAll('li[data-value]')) : [];
        var noRes   = list ? list.querySelector('.vs-school-picker-no-results') : null;

        function open()  { if (list) list.classList.add('open'); }
        function close() { if (list) list.classList.remove('open'); }

        function filter(q) {
            q = (q || '').trim().toUpperCase();
            var any = false;
            items.forEach(function (li) {
                var show = !q || li.getAttribute('data-value').toUpperCase().indexOf(q) !== -1;
                li.classList.toggle('vs-sp-hidden', !show);
                if (show) any = true;
            });
            if (noRes) noRes.style.display = any ? 'none' : '';
        }

        input.addEventListener('input', function () { filter(input.value); open(); });

        toggle.addEventListener('click', function () {
            if (list.classList.contains('open')) { close(); }
            else { filter(input.value); open(); input.focus(); }
        });

        items.forEach(function (li) {
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                input.value = li.getAttribute('data-value');
                close();
            });
        });

        document.addEventListener('click', function (e) {
            if (!picker.contains(e.target)) close();
        });
    }

    initPicker('jhs-picker');
    initPicker('shs-picker');
}());
</script>
<?= $this->endSection() ?>
