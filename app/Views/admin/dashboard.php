<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Stat cards -->
<div class="vs-stats-grid">

  <div class="vs-stat-card vs-stat-blue">
    <div class="vs-stat-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
      </svg>
    </div>
    <div class="vs-stat-body">
      <div class="vs-stat-value"><?= number_format($totalVouchers) ?></div>
      <div class="vs-stat-label">Total Students</div>
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
      <div class="vs-stat-value"><?= number_format($generatedCount) ?></div>
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
      <div class="vs-stat-value"><?= number_format($pendingCount) ?></div>
      <div class="vs-stat-label">Pending</div>
    </div>
  </div>

  <div class="vs-stat-card vs-stat-purple">
    <div class="vs-stat-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
    </div>
    <div class="vs-stat-body">
      <div class="vs-stat-value"><?= number_format($totalUsers) ?></div>
      <div class="vs-stat-label">Users</div>
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
      <div class="vs-stat-value"><?= number_format($totalArchived) ?></div>
      <div class="vs-stat-label">Archived</div>
    </div>
  </div>

</div>

<!-- Bottom panels -->
<div class="vs-dashboard-panels">

  <!-- Recent Vouchers -->
  <div class="vs-panel">
    <div class="vs-panel-header">
      <h6 class="vs-panel-title">Recent Students</h6>
      <a href="<?= site_url('admin/vouchers') ?>" class="vs-panel-link">View all</a>
    </div>
    <div class="vs-panel-body">
      <?php if (empty($recentVouchers)): ?>
        <p class="vs-empty">No vouchers yet.</p>
      <?php else: ?>
        <table class="vs-mini-table">
          <thead>
            <tr>
              <th>Voucher No.</th>
              <th>Name</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentVouchers as $v): ?>
            <tr>
              <td><span class="vs-id-badge"><?= esc($v['voucher_no']) ?></span></td>
              <td><?= esc($v['full_name']) ?></td>
              <td>
                <span class="vs-status-badge vs-status-<?= $v['voucher_status'] ?>">
                  <?= ucfirst(str_replace('_', ' ', $v['voucher_status'])) ?>
                </span>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      <?php endif ?>
    </div>
  </div>

  <!-- Recent Audit Logs -->
  <div class="vs-panel">
    <div class="vs-panel-header">
      <h6 class="vs-panel-title">Recent Activity</h6>
      <a href="<?= site_url('admin/audit-logs') ?>" class="vs-panel-link">View all</a>
    </div>
    <div class="vs-panel-body">
      <?php if (empty($recentLogs)): ?>
        <p class="vs-empty">No activity yet.</p>
      <?php else: ?>
        <div class="vs-activity-list">
          <?php foreach ($recentLogs as $log): ?>
          <div class="vs-activity-item">
            <div class="vs-activity-dot"></div>
            <div class="vs-activity-body">
              <div class="vs-activity-action"><?= esc($log['action']) ?></div>
              <div class="vs-activity-meta">
                <?= esc($log['username'] ?? 'System') ?>
                &nbsp;·&nbsp;
                <?= date('M d, g:i A', strtotime($log['created_at'])) ?>
              </div>
            </div>
          </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </div>
  </div>

</div>

<?= $this->endSection() ?>
