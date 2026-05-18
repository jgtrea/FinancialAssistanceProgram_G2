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
    <div class="vs-detail-grid">
      <div>
        <strong>Voucher No.</strong>
        <p><?= esc($voucher['voucher_no']) ?></p>
      </div>
      <div>
        <strong>Voucher Date</strong>
        <p><?= esc($voucher['voucher_date']) ?></p>
      </div>
      <div>
        <strong>Full Name</strong>
        <p><?= esc($voucher['full_name']) ?></p>
      </div>
      <div>
        <strong>First Name</strong>
        <p><?= esc($voucher['first_name']) ?></p>
      </div>
      <div>
        <strong>Middle Name</strong>
        <p><?= esc($voucher['middle_name'] ?: '—') ?></p>
      </div>
      <div>
        <strong>Last Name</strong>
        <p><?= esc($voucher['last_name']) ?></p>
      </div>
      <div>
        <strong>Suffix</strong>
        <p><?= esc($voucher['suffix'] ?: '—') ?></p>
      </div>
      <div>
        <strong>Gender</strong>
        <p><?= esc($voucher['gender'] ?: '—') ?></p>
      </div>
      <div>
        <strong>GWA</strong>
        <p><?= $voucher['gwa'] !== null ? esc($voucher['gwa']) : '—' ?></p>
      </div>
      <div>
        <strong>Rank No.</strong>
        <p><?= $voucher['rank_no'] !== null ? esc($voucher['rank_no']) : '—' ?></p>
      </div>
      <div>
        <strong>Contact Number</strong>
        <p><?= esc($voucher['contact_number'] ?: '—') ?></p>
      </div>
      <div>
        <strong>Junior High School</strong>
        <p><?= esc($voucher['junior_high_school'] ?: '—') ?></p>
      </div>
      <div>
        <strong>Preferred Senior High School</strong>
        <p><?= esc($voucher['preferred_senior_high_school']) ?></p>
      </div>
      <div>
        <strong>Remarks</strong>
        <p><?= esc($voucher['remarks_status'] ?: '—') ?></p>
      </div>
      <div>
        <strong>School Year</strong>
        <p><?= esc($voucher['school_year']) ?></p>
      </div>
      <div>
        <strong>Eligibility</strong>
        <p><?= esc(ucfirst(str_replace('_', ' ', $voucher['eligibility_status']))) ?></p>
      </div>
      <div>
        <strong>Voucher Status</strong>
        <p><?= esc(ucfirst(str_replace('_', ' ', $voucher['voucher_status']))) ?></p>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
