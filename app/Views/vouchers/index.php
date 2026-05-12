<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $role = $role ?? 'admin' ?>

<div class="container-fluid px-4 py-4">

  <!-- Page header -->
  <div class="vs-page-header mb-4">
    <div>
      <h4 class="vs-page-title"><?= esc($title) ?></h4>
      <p class="vs-page-sub">Manage student financial assistance records</p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url(($role === 'admin' ? 'admin' : 'user') . '/vouchers/create') ?>" class="vs-btn vs-btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Student
      </a>
      <button class="vs-btn vs-btn-outline" id="btnExport">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export
      </button>
    </div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0" id="vouchersTable">
          <thead class="table-light">
            <tr>
              <th><input type="checkbox" id="selectAll"></th>
              <th>Voucher No</th>
              <th>Full Name</th>
              <th>School</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($vouchers)): ?>
              <?php foreach ($vouchers as $voucher): ?>
                <tr>
                  <td><input type="checkbox" class="voucher-checkbox" value="<?= $voucher['student_id'] ?>"></td>
                  <td><?= esc($voucher['voucher_no']) ?></td>
                  <td>
                    <?= esc(trim($voucher['first_name'] . ' ' . ($voucher['middle_name'] ?? '') . ' ' . $voucher['last_name'] . ' ' . ($voucher['suffix'] ?? ''))) ?>
                  </td>
                  <td><?= esc($voucher['preferred_senior_high_school']) ?></td>
                  <td>
                    <span class="badge bg-<?= $voucher['voucher_status'] == 'generated' ? 'success' : 'warning' ?>">
                      <?= ucfirst($voucher['voucher_status']) ?>
                    </span>
                  </td>
                  <td>
                    <div class="btn-group" role="group">
                      <a href="<?= site_url(($role === 'admin' ? 'admin' : 'user') . '/vouchers/view/' . $voucher['student_id']) ?>" class="btn btn-sm btn-outline-primary" title="View">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                      </a>
                      <a href="<?= site_url(($role === 'admin' ? 'admin' : 'user') . '/vouchers/edit/' . $voucher['student_id']) ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center py-4">No students found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?= $this->endSection() ?>