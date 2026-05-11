<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
  <div>
    <h4 class="vs-page-title">Voucher Details</h4>
    <p class="vs-page-sub">Review the voucher data and export or edit as needed.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= site_url('admin/vouchers') ?>" class="vs-btn vs-btn-outline">Back to vouchers</a>
    <a href="<?= site_url('admin/vouchers/edit/' . $voucher['voucher_id']) ?>" class="vs-btn vs-btn-primary">Edit</a>
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
        <strong>Date</strong>
        <p><?= esc($voucher['voucher_date']) ?></p>
      </div>
      <div>
        <strong>Recipient</strong>
        <p><?= esc($voucher['recipient_name']) ?></p>
      </div>
      <div>
        <strong>School</strong>
        <p><?= esc($voucher['senior_high_school']) ?></p>
      </div>
      <div>
        <strong>Amount</strong>
        <p>₱ <?= number_format($voucher['amount'], 2) ?></p>
      </div>
      <div>
        <strong>Amount in Words</strong>
        <p><?= esc($voucher['amount_in_words']) ?></p>
      </div>
      <div>
        <strong>School Year</strong>
        <p><?= esc($voucher['school_year']) ?></p>
      </div>
      <div>
        <strong>Status</strong>
        <p><?= esc(ucfirst(str_replace('_', ' ', $voucher['voucher_status']))) ?></p>
      </div>
      <div>
        <strong>Student</strong>
        <p><?= esc($voucher['student_name'] ?? '—') ?></p>
      </div>
      <div>
        <strong>Signatory 1</strong>
        <p><?= esc($voucher['sig1_name'] ?? '—') ?></p>
      </div>
      <div>
        <strong>Signatory 2</strong>
        <p><?= esc($voucher['sig2_name'] ?? '—') ?></p>
      </div>
      <div>
        <strong>Signatory 3</strong>
        <p><?= esc($voucher['sig3_name'] ?? '—') ?></p>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>