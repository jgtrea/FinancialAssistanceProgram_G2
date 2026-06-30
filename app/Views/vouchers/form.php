<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php helper('form') ?>
<?php
  $role = session()->get('role') === 'admin' || strpos($action, '/admin/') !== false ? 'admin' : 'user';
  $prefix = $role === 'admin' ? 'admin' : 'user';
  $suffix = old('suffix', $voucher['suffix'] ?? '');
  $remarks = old('remarks_status', $voucher['remarks_status'] ?? '');
  $customSuffixes = $customSuffixes ?? [];
  $selectedJuniorHighSchool = old('junior_high_school', $voucher['junior_high_school'] ?? '');
  $selectedSeniorHighSchool = old('preferred_senior_high_school', $voucher['preferred_senior_high_school'] ?? '');
?>

<div class="vs-page-header mb-4">
  <div>
    <h4 class="vs-page-title"><?= esc($title) ?></h4>
    <p class="vs-page-sub">Enter Student And Voucher Details.</p>
  </div>
  <a href="<?= site_url($prefix . '/students') ?>" class="btn btn-secondary">Back to Vouchers</a>
</div>

<div class="vs-card">
  <div class="vs-card-body">
    <?php if (isset($validation) && $validation->getErrors()): ?>
      <div class="vs-alert vs-alert-error mb-3">
        <ul>
          <?php foreach ($validation->getErrors() as $error): ?>
            <li><?= esc($error) ?></li>
          <?php endforeach ?>
        </ul>
      </div>
    <?php endif ?>
    <form method="POST" action="<?= esc($action) ?>">
      <?= csrf_field() ?>

      <div class="vs-form-grid vs-form-grid-4">

        <div>
          <label class="vs-label" for="control_no">Control No.</label>
          <input id="control_no" name="control_no" type="text" class="vs-input vs-uppercase"
                 value="<?= old('control_no', $voucher['control_no'] ?? '') ?>">
        </div>

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
          <?php
            $suffixOtherVal = '';
            $suffixSelectVal = $suffix;
            $knownSuffixes = array_merge(['JR.','SR.','II','III','IV','__OTHER__',''], $customSuffixes);
            if ($suffix !== '' && !in_array($suffix, $knownSuffixes)) {
                $suffixOtherVal = $suffix;
                $suffixSelectVal = '__OTHER__';
            }
          ?>
          <select id="suffix" data-field-name="suffix" <?= $suffixSelectVal !== '__OTHER__' ? 'name="suffix"' : '' ?> class="vs-input js-filter-select" data-placeholder="- SELECT -" data-no-search="1">
            <option value="">None</option>
            <option value="JR." <?= $suffixSelectVal === 'JR.' ? 'selected' : '' ?>>JR.</option>
            <option value="SR." <?= $suffixSelectVal === 'SR.' ? 'selected' : '' ?>>SR.</option>
            <option value="II"  <?= $suffixSelectVal === 'II'  ? 'selected' : '' ?>>II</option>
            <option value="III" <?= $suffixSelectVal === 'III' ? 'selected' : '' ?>>III</option>
            <option value="IV"  <?= $suffixSelectVal === 'IV'  ? 'selected' : '' ?>>IV</option>
            <?php foreach ($customSuffixes as $cs): ?>
              <?php if (!in_array($cs, ['JR.','SR.','II','III','IV',''])): ?>
                <option value="<?= esc($cs) ?>" <?= $suffixSelectVal === $cs ? 'selected' : '' ?>><?= esc($cs) ?></option>
              <?php endif ?>
            <?php endforeach ?>
            <option value="__OTHER__" <?= $suffixSelectVal === '__OTHER__' ? 'selected' : '' ?>>OTHERS</option>
          </select>
          <div id="suffixOtherWrap" style="<?= $suffixSelectVal === '__OTHER__' ? '' : 'display:none' ?>" class="mt-2">
            <input id="suffixOther" <?= $suffixSelectVal === '__OTHER__' ? 'name="suffix"' : '' ?> type="text" class="vs-input vs-uppercase" placeholder="Other suffix" value="<?= esc($suffixOtherVal) ?>">
          </div>
        </div>

        <div>
          <label class="vs-label" for="gender">Sex</label>
          <?php $gender = old('gender', $voucher['gender'] ?? '') ?>
          <select id="gender" name="gender" class="vs-input js-filter-select" data-placeholder="MALE / FEMALE" data-no-search="1">
            <option></option>
            <option value="MALE"   <?= $gender === 'MALE'   ? 'selected' : '' ?>>MALE</option>
            <option value="FEMALE" <?= $gender === 'FEMALE' ? 'selected' : '' ?>>FEMALE</option>
          </select>
        </div>

        <div>
          <label class="vs-label" for="gwa">GWA</label>
          <input id="gwa" name="gwa" type="number" step="0.01" class="vs-input"
                 value="<?= old('gwa', $voucher['gwa'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="rank_no">Rank No.</label>
          <input id="rank_no" name="rank_no" type="number" step="any" class="vs-input"
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
          <label class="vs-label" for="remarks_status">Remarks / Status</label>
          <select id="remarks_status" name="remarks_status" class="vs-input js-filter-select" data-placeholder="COMPLETE / INCOMPLETE / OTHERS" data-no-search="1">
            <option></option>
            <option value="COMPLETE"   <?= $remarks === 'COMPLETE'   ? 'selected' : '' ?>>COMPLETE</option>
            <option value="INCOMPLETE" <?= $remarks === 'INCOMPLETE' ? 'selected' : '' ?>>INCOMPLETE</option>
            <option value="OTHERS"     <?= $remarks === 'OTHERS'     ? 'selected' : '' ?>>OTHERS</option>
          </select>
        </div>

        <div>
          <label class="vs-label" for="evaluated_by">Evaluated By</label>
          <input id="evaluated_by" name="evaluated_by" type="text" class="vs-input vs-uppercase"
                 value="<?= old('evaluated_by', $voucher['evaluated_by'] ?? '') ?>">
        </div>

        <div id="otherRemarksWrap" class="vs-span-4" style="<?= in_array($remarks, ['OTHERS','INCOMPLETE']) ? '' : 'display:none' ?>">
          <label class="vs-label" for="other_remarks">Other Remarks</label>
          <textarea id="other_remarks" name="other_remarks" class="vs-input vs-uppercase" maxlength="255" rows="3"
                    style="resize:vertical"><?= esc(old('other_remarks', $voucher['other_remarks'] ?? '')) ?></textarea>
        </div>

        <?php if (false): ?>
        <div>
          <label class="vs-label" for="eligibility_status">Eligibility</label>
          <?php $eligibility = old('eligibility_status', $voucher['eligibility_status'] ?? '') ?>
          <select id="eligibility_status" name="eligibility_status" class="vs-input js-filter-select" data-placeholder="ELIGIBLE / NOT ELIGIBLE" data-no-search="1">
            <option></option>
            <option value="eligible"     <?= $eligibility === 'eligible'     ? 'selected' : '' ?>>ELIGIBLE</option>
            <option value="not_eligible" <?= $eligibility === 'not_eligible' ? 'selected' : '' ?>>NOT ELIGIBLE</option>
          </select>
        </div>
        <?php endif ?>

      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= !empty($voucher['student_id'] ?? null) ? 'Update' : esc($title) ?></button>
        <a href="<?= site_url($prefix . '/students') ?>" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof initOtherInput === 'function') {
            initOtherInput('suffix', 'suffixOtherWrap', 'suffixOther');
        }

        var remarksEl = document.getElementById('remarks_status');
        var wrapEl    = document.getElementById('otherRemarksWrap');
        var textEl    = document.getElementById('other_remarks');

        function toggleOtherRemarks() {
            var val  = String((remarksEl ? remarksEl.value : '') || '').toUpperCase();
            var show = val === 'OTHERS' || val === 'INCOMPLETE';
            if (wrapEl) wrapEl.style.display = show ? '' : 'none';
            if (textEl && !show) textEl.value = '';
        }

        if (remarksEl) {
            remarksEl.addEventListener('change', toggleOtherRemarks);
            if (window.jQuery) jQuery(remarksEl).on('change.select2', toggleOtherRemarks);
        }
    });
}());
</script>
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
