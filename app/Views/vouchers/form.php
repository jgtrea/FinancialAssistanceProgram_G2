<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php helper('form') ?>

<div class="vs-page-header mb-4">
  <div>
    <h4 class="vs-page-title"><?= esc($title) ?></h4>
    <p class="vs-page-sub">Enter student and voucher details.</p>
  </div>
  <a href="<?= site_url('admin/vouchers') ?>" class="vs-btn vs-btn-outline">Back to vouchers</a>
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

      <div class="vs-grid vs-grid-2 gap-3">

        <!-- Voucher Info -->
        <div>
          <label class="vs-label" for="voucher_no">Voucher No.</label>
          <input id="voucher_no" name="voucher_no" type="text"
                 class="vs-input <?= ($validation && $validation->hasError('voucher_no')) ? 'vs-input-error' : '' ?>"
                 value="<?= old('voucher_no', $voucher['voucher_no'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="voucher_date">Voucher Date</label>
          <input id="voucher_date" name="voucher_date" type="date"
                 class="vs-input <?= ($validation && $validation->hasError('voucher_date')) ? 'vs-input-error' : '' ?>"
                 value="<?= old('voucher_date', $voucher['voucher_date'] ?? '') ?>">
        </div>

        <!-- Name fields -->
        <div>
          <label class="vs-label" for="first_name">First Name</label>
          <input id="first_name" name="first_name" type="text"
                 class="vs-input <?= ($validation && $validation->hasError('first_name')) ? 'vs-input-error' : '' ?>"
                 value="<?= old('first_name', $voucher['first_name'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="middle_name">Middle Name</label>
          <input id="middle_name" name="middle_name" type="text" class="vs-input"
                 value="<?= old('middle_name', $voucher['middle_name'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="last_name">Last Name</label>
          <input id="last_name" name="last_name" type="text"
                 class="vs-input <?= ($validation && $validation->hasError('last_name')) ? 'vs-input-error' : '' ?>"
                 value="<?= old('last_name', $voucher['last_name'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="suffix">Suffix</label>
          <input id="suffix" name="suffix" type="text" class="vs-input"
                 value="<?= old('suffix', $voucher['suffix'] ?? '') ?>" placeholder="e.g. Jr., III">
        </div>

        <!-- Academic Info -->
        <div>
          <label class="vs-label" for="gender">Gender</label>
          <select id="gender" name="gender" class="vs-input">
            <?php $gender = old('gender', $voucher['gender'] ?? '') ?>
            <option value="">-- Select --</option>
            <option value="Male"   <?= $gender === 'Male'   ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
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
          <input id="contact_number" name="contact_number" type="text" class="vs-input"
                 value="<?= old('contact_number', $voucher['contact_number'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="junior_high_school">Junior High School</label>
          <input id="junior_high_school" name="junior_high_school" type="text" class="vs-input"
                 value="<?= old('junior_high_school', $voucher['junior_high_school'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="preferred_senior_high_school">Preferred Senior High School</label>
          <input id="preferred_senior_high_school" name="preferred_senior_high_school" type="text"
                 class="vs-input <?= ($validation && $validation->hasError('preferred_senior_high_school')) ? 'vs-input-error' : '' ?>"
                 value="<?= old('preferred_senior_high_school', $voucher['preferred_senior_high_school'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="remarks_status">Remarks</label>
          <input id="remarks_status" name="remarks_status" type="text" class="vs-input"
                 value="<?= old('remarks_status', $voucher['remarks_status'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="school_year">School Year</label>
          <input id="school_year" name="school_year" type="text" class="vs-input"
                 class="vs-input <?= ($validation && $validation->hasError('school_year')) ? 'vs-input-error' : '' ?>"
                 value="<?= old('school_year', $voucher['school_year'] ?? '') ?>"
                 placeholder="e.g. 2025-2026">
        </div>

        <div>
          <label class="vs-label" for="eligibility_status">Eligibility</label>
          <select id="eligibility_status" name="eligibility_status" class="vs-input">
            <?php $eligibility = old('eligibility_status', $voucher['eligibility_status'] ?? 'eligible') ?>
            <option value="eligible"     <?= $eligibility === 'eligible'     ? 'selected' : '' ?>>Eligible</option>
            <option value="not_eligible" <?= $eligibility === 'not_eligible' ? 'selected' : '' ?>>Not Eligible</option>
          </select>
        </div>

        <div>
          <label class="vs-label" for="voucher_status">Voucher Status</label>
          <select id="voucher_status" name="voucher_status" class="vs-input">
            <?php $status = old('voucher_status', $voucher['voucher_status'] ?? 'not_generated') ?>
            <option value="not_generated" <?= $status === 'not_generated' ? 'selected' : '' ?>>Not Generated</option>
            <option value="generated"     <?= $status === 'generated'     ? 'selected' : '' ?>>Generated</option>
          </select>
        </div>

      </div>

      <div class="mt-4 d-flex gap-2">
        <a href="<?= site_url('admin/vouchers') ?>" class="vs-btn vs-btn-outline">Cancel</a>
        <button type="submit" class="vs-btn vs-btn-primary"><?= esc($title) ?></button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
