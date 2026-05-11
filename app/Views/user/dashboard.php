<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Stats -->
<div class="vs-stats-grid">

  <div class="vs-stat-card vs-stat-blue">
    <div class="vs-stat-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
      </svg>
    </div>
    <div class="vs-stat-body">
      <div class="vs-stat-value"><?= number_format($myVouchers) ?></div>
      <div class="vs-stat-label">My Vouchers</div>
    </div>
  </div>

  <div class="vs-stat-card vs-stat-green">
    <div class="vs-stat-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
        <polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
    </div>
    <div class="vs-stat-body">
      <div class="vs-stat-value"><?= number_format($generated) ?></div>
      <div class="vs-stat-label">Generated</div>
    </div>
  </div>

  <div class="vs-stat-card vs-stat-amber">
    <div class="vs-stat-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
    </div>
    <div class="vs-stat-body">
      <div class="vs-stat-value"><?= number_format($pending) ?></div>
      <div class="vs-stat-label">Pending</div>
    </div>
  </div>

  <div class="vs-stat-card vs-stat-gray">
    <div class="vs-stat-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="21 8 21 21 3 21 3 8"/>
        <rect x="1" y="3" width="22" height="5"/>
        <line x1="10" y1="12" x2="14" y2="12"/>
      </svg>
    </div>
    <div class="vs-stat-body">
      <div class="vs-stat-value"><?= number_format($archived) ?></div>
      <div class="vs-stat-label">Archived</div>
    </div>
  </div>

</div>

<!-- Recent vouchers -->
<div class="vs-panel">
  <div class="vs-panel-header">
    <h6 class="vs-panel-title">My Recent Vouchers</h6>
    <a href="<?= site_url('user/vouchers') ?>" class="vs-panel-link">View all</a>
  </div>
  <div class="vs-panel-body">
    <?php if (empty($recentVouchers)): ?>
      <p class="vs-empty">No vouchers found. Contact your administrator.</p>
    <?php else: ?>
      <table class="vs-mini-table">
        <thead>
          <tr>
            <th>Voucher No.</th>
            <th>Recipient</th>
            <th>School</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentVouchers as $v): ?>
          <tr>
            <td><span class="vs-id-badge"><?= esc($v['voucher_no']) ?></span></td>
            <td><?= esc($v['recipient_name']) ?></td>
            <td><?= esc($v['senior_high_school']) ?></td>
            <td class="vs-amount">₱<?= number_format($v['amount'], 2) ?></td>
            <td><?= voucher_status_badge($v['voucher_status']) ?></td>
            <td><?= date('M d, Y', strtotime($v['created_at'])) ?></td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    <?php endif ?>
  </div>
</div>

<?= $this->endSection() ?>