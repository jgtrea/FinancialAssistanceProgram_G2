<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $role = $role ?? 'admin' ?>

<div class="container-fluid px-4 py-4">

  <!-- Page header -->
  <div class="vs-page-header mb-4">
    <div>
      <h4 class="vs-page-title"><?= esc($title) ?></h4>
      <p class="vs-page-sub">Manage and generate financial assistance vouchers</p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url('admin/vouchers/create') ?>" class="vs-btn vs-btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Voucher
      </a>
      <button class="vs-btn vs-btn-outline" id="btnExport">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export
      </button>
    </div>
  </div>

  <!-- Flash messages -->
  <?php if (session()->getFlashdata('error')): ?>
    <div class="vs-alert vs-alert-error mb-3">
      <?= esc(session()->getFlashdata('error')) ?>
    </div>
  <?php endif ?>
  <?php if (session()->getFlashdata('message')): ?>
    <div class="vs-alert vs-alert-success mb-3">
      <?= esc(session()->getFlashdata('message')) ?>
    </div>
  <?php endif ?>

  <!-- Action bar — shown when rows are selected -->
  <div class="vs-action-bar" id="actionBar" style="display:none">
    <span class="vs-action-bar-count"><span id="selectedCount">0</span> selected</span>
    <div class="d-flex gap-2">
      <button class="vs-btn vs-btn-primary" id="btnGeneratePdf">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
        Generate Vouchers
      </button>
      <button class="vs-btn vs-btn-danger" id="btnArchive">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
        Archive
      </button>
    </div>
  </div>

  <!-- DataTable card -->
  <div class="vs-card">
    <div class="vs-card-body">
      <table id="vouchersTable" class="vs-datatable" style="width:100%">
        <thead>
          <tr>
            <th class="vs-th-check">
              <input type="checkbox" id="checkAll" class="vs-check">
            </th>
            <th>VOC-ID</th>
            <th>Voucher No.</th>
            <th>Recipient</th>
            <th>School</th>
            <th>Amount</th>
            <th>School Year</th>
            <th>Status</th>
            <?php if ($role === 'admin'): ?>
            <th>Created By</th>
            <?php endif ?>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vouchers as $v): ?>
          <tr>
            <td>
              <input type="checkbox" class="vs-check vs-row-check"
                     value="<?= $v['voucher_id'] ?>"
                     data-voucher='<?= json_encode([
                       'id'     => $v['voucher_id'],
                       'no'     => $v['voucher_no'],
                       'name'   => $v['recipient_name'],
                       'status' => $v['voucher_status'],
                     ]) ?>'>
            </td>
            <td><span class="vs-id-badge">VOC-<?= str_pad($v['voucher_id'], 4, '0', STR_PAD_LEFT) ?></span></td>
            <td><?= esc($v['voucher_no']) ?></td>
            <td><?= esc($v['recipient_name']) ?></td>
            <td><?= esc($v['senior_high_school']) ?></td>
            <td class="vs-amount">₱ <?= number_format($v['amount'], 2) ?></td>
            <td><?= esc($v['school_year']) ?></td>
            <td>
              <span class="vs-status-badge vs-status-<?= $v['voucher_status'] ?>">
                <?= ucfirst(str_replace('_', ' ', $v['voucher_status'])) ?>
              </span>
            </td>
            <?php if ($role === 'admin'): ?>
            <td><?= esc($v['created_by_name'] ?? $v['username']) ?></td>
            <?php endif ?>
            <td><?= date('M d, Y', strtotime($v['created_at'])) ?></td>
            <td>
              <div class="d-flex gap-1">
                <a href="<?= site_url(($role === 'admin' ? 'admin' : 'user') . '/vouchers/view/' . $v['voucher_id']) ?>"
                   class="vs-tbl-btn vs-tbl-btn-view">View</a>
                <?php if ($role === 'admin'): ?>
                <a href="<?= site_url('admin/vouchers/edit/' . $v['voucher_id']) ?>"
                   class="vs-tbl-btn vs-tbl-btn-edit">Edit</a>
                <?php endif ?>
              </div>
            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- PDF Progress modal -->
<div class="vs-modal-overlay" id="pdfProgressModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5>Generating PDF</h5>
    </div>
    <div class="vs-modal-body text-center py-4">
      <div class="vs-spinner" style="width:32px;height:32px;display:inline-block"></div>
      <p class="mt-3 mb-0" id="pdfStatusText">Starting PDF generation...</p>
    </div>
  </div>
</div>

<!-- Archive modal -->
<div class="vs-modal-overlay" id="archiveModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5>Archive Vouchers</h5>
      <button class="vs-modal-close" id="archiveModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <p>You are about to archive <strong id="archiveCount">0</strong> voucher(s). This will move them to the archive table.</p>
      <label class="vs-label" for="archiveReason">Reason (optional)</label>
      <input type="text" id="archiveReason" class="vs-input" placeholder="e.g. End of school year">
    </div>
    <div class="vs-modal-footer">
      <button class="vs-btn vs-btn-outline" id="archiveModalCancel">Cancel</button>
      <button class="vs-btn vs-btn-danger" id="archiveConfirm">
        <span id="archiveBtnText">Confirm Archive</span>
        <span id="archiveBtnSpinner" class="vs-spinner" style="display:none"></span>
      </button>
    </div>
  </div>
</div>

<!-- Hidden form for PDF POST -->
<form id="pdfForm" method="POST"
      action="<?= site_url(($role === 'admin' ? 'admin' : 'user') . '/vouchers/generate-pdf') ?>"
      style="display:none">
  <?= csrf_field() ?>
  <div id="pdfInputs"></div>
</form>

<?= $this->endSection() ?>