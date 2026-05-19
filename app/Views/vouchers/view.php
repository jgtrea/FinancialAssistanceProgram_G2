<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $role = $role ?? 'admin' ?>
<?php $prefix = $role === 'admin' ? 'admin' : 'user' ?>

<div class="vs-page-header mb-4">
  <div>
    <h4 class="vs-page-title">Voucher Details</h4>
    <p class="vs-page-sub">Review the student voucher data.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= site_url($prefix . '/students') ?>" class="vs-btn vs-btn-outline">Back to students</a>
    <?php if ($role === 'admin'): ?>
      <a href="<?= site_url('admin/students/edit/' . $voucher['student_id']) ?>" class="vs-btn vs-btn-primary">Edit</a>
    <?php endif ?>
  </div>
</div>

<div class="vs-card">
  <div class="vs-card-body">
    <div class="vs-form-grid vs-form-grid-4">
      <div>
        <label class="vs-label">Voucher No.</label>
        <div class="vs-readonly-field"><?= esc($voucher['voucher_no'] ?: '-') ?></div>
      </div>

      <div>
        <label class="vs-label">Voucher Date</label>
        <div class="vs-readonly-field"><?= esc($voucher['voucher_date']) ?></div>
      </div>

      <div>
        <label class="vs-label">First Name</label>
        <div class="vs-readonly-field"><?= esc($voucher['first_name']) ?></div>
      </div>

      <div>
        <label class="vs-label">Middle Name</label>
        <div class="vs-readonly-field"><?= esc($voucher['middle_name'] ?: '-') ?></div>
      </div>

      <div>
        <label class="vs-label">Last Name</label>
        <div class="vs-readonly-field"><?= esc($voucher['last_name']) ?></div>
      </div>

      <div>
        <label class="vs-label">Suffix</label>
        <div class="vs-readonly-field"><?= esc($voucher['suffix'] ?: '-') ?></div>
      </div>

      <div>
        <label class="vs-label">Gender</label>
        <div class="vs-readonly-field"><?= esc($voucher['gender'] ?: '-') ?></div>
      </div>

      <div>
        <label class="vs-label">GWA</label>
        <div class="vs-readonly-field"><?= $voucher['gwa'] !== null ? esc($voucher['gwa']) : '-' ?></div>
      </div>

      <div>
        <label class="vs-label">Rank No.</label>
        <div class="vs-readonly-field"><?= $voucher['rank_no'] !== null ? esc($voucher['rank_no']) : '-' ?></div>
      </div>

      <div>
        <label class="vs-label">Contact Number</label>
        <div class="vs-readonly-field"><?= esc($voucher['contact_number'] ?: '-') ?></div>
      </div>

      <div class="vs-span-2">
        <label class="vs-label">Junior High School</label>
        <div class="vs-readonly-field"><?= esc($voucher['junior_high_school'] ?: '-') ?></div>
      </div>

      <div class="vs-span-2">
        <label class="vs-label">Preferred Senior High School</label>
        <div class="vs-readonly-field"><?= esc($voucher['preferred_senior_high_school']) ?></div>
      </div>

      <div>
        <label class="vs-label">Remarks</label>
        <div class="vs-readonly-field"><?= esc($voucher['remarks_status'] ?: '-') ?></div>
      </div>

      <div>
        <label class="vs-label">School Year</label>
        <div class="vs-readonly-field"><?= esc($voucher['school_year']) ?></div>
      </div>

      <div>
        <label class="vs-label">Eligibility</label>
        <div class="vs-readonly-field"><?= esc(ucfirst(str_replace('_', ' ', $voucher['eligibility_status']))) ?></div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
