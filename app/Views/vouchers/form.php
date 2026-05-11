<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php helper('form') ?>

<div class="vs-page-header mb-4">
  <div>
    <h4 class="vs-page-title"><?= esc($title) ?></h4>
    <p class="vs-page-sub">Enter voucher details and save to create a new voucher record.</p>
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
        <div>
          <label class="vs-label" for="voucher_no">Voucher No.</label>
          <input id="voucher_no" name="voucher_no" type="text" class="vs-input <?= ($validation && $validation->hasError('voucher_no')) ? 'vs-input-error' : '' ?>" value="<?= old('voucher_no', $voucher['voucher_no'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="voucher_date">Voucher Date</label>
          <input id="voucher_date" name="voucher_date" type="date" class="vs-input <?= ($validation && $validation->hasError('voucher_date')) ? 'vs-input-error' : '' ?>" value="<?= old('voucher_date', $voucher['voucher_date'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="recipient_name">Recipient Name</label>
          <input id="recipient_name" name="recipient_name" type="text" class="vs-input <?= ($validation && $validation->hasError('recipient_name')) ? 'vs-input-error' : '' ?>" value="<?= old('recipient_name', $voucher['recipient_name'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="senior_high_school">Senior High School</label>
          <input id="senior_high_school" name="senior_high_school" type="text" class="vs-input <?= ($validation && $validation->hasError('senior_high_school')) ? 'vs-input-error' : '' ?>" value="<?= old('senior_high_school', $voucher['senior_high_school'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="amount">Amount</label>
          <input id="amount" name="amount" type="number" step="0.01" class="vs-input <?= ($validation && $validation->hasError('amount')) ? 'vs-input-error' : '' ?>" value="<?= old('amount', $voucher['amount'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="amount_in_words">Amount In Words</label>
          <input id="amount_in_words" name="amount_in_words" type="text" class="vs-input <?= ($validation && $validation->hasError('amount_in_words')) ? 'vs-input-error' : '' ?>" value="<?= old('amount_in_words', $voucher['amount_in_words'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="school_year">School Year</label>
          <input id="school_year" name="school_year" type="text" class="vs-input <?= ($validation && $validation->hasError('school_year')) ? 'vs-input-error' : '' ?>" value="<?= old('school_year', $voucher['school_year'] ?? '') ?>">
        </div>

        <div>
          <label class="vs-label" for="voucher_status">Status</label>
          <select id="voucher_status" name="voucher_status" class="vs-input">
            <?php $status = old('voucher_status', $voucher['voucher_status'] ?? 'not_generated') ?>
            <option value="not_generated" <?= $status === 'not_generated' ? 'selected' : '' ?>>Not Generated</option>
            <option value="generated" <?= $status === 'generated' ? 'selected' : '' ?>>Generated</option>
          </select>
        </div>

        <div>
          <label class="vs-label" for="student_id">Student</label>
          <select id="student_id" name="student_id" class="vs-input">
            <option value="">-- Select student (optional) --</option>
            <?php foreach ($students as $student): ?>
              <?php $selected = old('student_id', $voucher['student_id'] ?? '') == $student['student_id'] ? 'selected' : '' ?>
              <option value="<?= esc($student['student_id']) ?>" <?= $selected ?>><?= esc($student['full_name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>

        <div>
          <label class="vs-label" for="signatory_1_id">Signatory 1</label>
          <select id="signatory_1_id" name="signatory_1_id" class="vs-input">
            <option value="">-- Select signatory --</option>
            <?php foreach ($signatories as $signatory): ?>
              <?php $selected = old('signatory_1_id', $voucher['signatory_1_id'] ?? '') == $signatory['signatory_id'] ? 'selected' : '' ?>
              <option value="<?= esc($signatory['signatory_id']) ?>" <?= $selected ?>><?= esc($signatory['full_name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>

        <div>
          <label class="vs-label" for="signatory_2_id">Signatory 2</label>
          <select id="signatory_2_id" name="signatory_2_id" class="vs-input">
            <option value="">-- Select signatory --</option>
            <?php foreach ($signatories as $signatory): ?>
              <?php $selected = old('signatory_2_id', $voucher['signatory_2_id'] ?? '') == $signatory['signatory_id'] ? 'selected' : '' ?>
              <option value="<?= esc($signatory['signatory_id']) ?>" <?= $selected ?>><?= esc($signatory['full_name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>

        <div>
          <label class="vs-label" for="signatory_3_id">Signatory 3</label>
          <select id="signatory_3_id" name="signatory_3_id" class="vs-input">
            <option value="">-- Select signatory --</option>
            <?php foreach ($signatories as $signatory): ?>
              <?php $selected = old('signatory_3_id', $voucher['signatory_3_id'] ?? '') == $signatory['signatory_id'] ? 'selected' : '' ?>
              <option value="<?= esc($signatory['signatory_id']) ?>" <?= $selected ?>><?= esc($signatory['full_name']) ?></option>
            <?php endforeach ?>
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