<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
  <div>
    <p class="vs-page-sub">Complete record of all system actions and events</p>
  </div>
  <a href="<?= site_url('admin/reports') ?>" class="vs-btn vs-btn-outline">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
    </svg>
    Reports
  </a>
</div>

<div class="vs-card">
  <div class="vs-card-body">
    <table id="logsTable" class="vs-datatable" style="width:100%">
      <thead>
        <tr>
          <th>#</th>
          <th>Action</th>
          <th>User</th>
          <th>Voucher</th>
          <th>Description</th>
          <th>IP Address</th>
          <th>Date & Time</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $i => $log): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td>
            <span class="vs-log-action vs-log-<?= strtolower(explode('_', $log['action'])[0]) ?>">
              <?= esc($log['action']) ?>
            </span>
          </td>
          <td>
            <?php if ($log['full_name']): ?>
              <div class="vs-log-user">
                <span class="vs-log-fullname"><?= esc($log['full_name']) ?></span>
                <span class="vs-log-username">@<?= esc($log['username']) ?></span>
              </div>
            <?php else: ?>
              <span class="text-muted">System</span>
            <?php endif ?>
          </td>
          <td>
            <?php if ($log['voucher_no']): ?>
              <span class="vs-id-badge"><?= esc($log['voucher_no']) ?></span>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif ?>
          </td>
          <td class="vs-log-desc"><?= esc($log['description']) ?></td>
          <td><code class="vs-ip"><?= esc($log['ip_address']) ?></code></td>
          <td><?= date('M d, Y g:i:s A', strtotime($log['created_at'])) ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>