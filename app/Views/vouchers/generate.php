<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $role = $role ?? 'admin' ?>
<?php $prefix = $role === 'admin' ? 'admin' : 'user' ?>

<div class="container-fluid px-4 py-4">

  <div class="vs-page-header mb-4">
    <div>
      <h4 class="vs-page-title"><?= esc($title) ?></h4>
      <p class="vs-page-sub">Select students and generate printable voucher PDFs.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url($prefix . '/students') ?>" class="vs-btn vs-btn-outline">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 18a5 5 0 0 0-10 0"/><circle cx="12" cy="8" r="4"/><path d="M4 22h16"/></svg>
        Student Management
      </a>
      <button class="vs-btn vs-btn-outline" id="btnExport">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export
      </button>
    </div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="vs-alert vs-alert-error mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif ?>
  <?php if (session()->getFlashdata('message')): ?>
    <div class="vs-alert vs-alert-success mb-3"><?= esc(session()->getFlashdata('message')) ?></div>
  <?php endif ?>

  <div class="vs-action-bar" id="actionBar" style="display:none">
    <span class="vs-action-bar-count"><span id="selectedCount">0</span> selected</span>
    <button class="vs-btn vs-btn-primary" id="btnGeneratePdf">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
      Generate Vouchers
    </button>
  </div>

  <div class="vs-card">
    <div class="vs-card-body">
      <table id="vouchersTable" class="vs-datatable" style="width:100%">
        <thead>
          <tr>
            <th class="vs-th-check"><input type="checkbox" id="checkAll" class="vs-check"></th>
            <th>Student ID</th>
            <th>Voucher No.</th>
            <th>Name</th>
            <th>Preferred School</th>
            <th>School Year</th>
            <th>Eligibility</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vouchers as $v): ?>
          <tr id="row-<?= esc($v['student_id'], 'attr') ?>">
            <td><input type="checkbox" class="vs-check vs-row-check" value="<?= esc($v['student_id'], 'attr') ?>"></td>
            <td><span class="vs-id-badge">STD-<?= str_pad($v['student_id'], 4, '0', STR_PAD_LEFT) ?></span></td>
            <td><?= esc($v['voucher_no']) ?></td>
            <td><?= esc($v['full_name']) ?></td>
            <td><?= esc($v['preferred_senior_high_school']) ?></td>
            <td><?= esc($v['school_year']) ?></td>
            <td>
              <span class="vs-status-badge vs-status-<?= esc($v['eligibility_status'], 'attr') ?>">
                <?= esc(ucfirst(str_replace('_', ' ', $v['eligibility_status']))) ?>
              </span>
            </td>
            <td>
              <span class="vs-status-badge vs-status-<?= esc($v['voucher_status'], 'attr') ?>">
                <?= esc(ucfirst(str_replace('_', ' ', $v['voucher_status']))) ?>
              </span>
            </td>
            <td><?= date('M d, Y', strtotime($v['created_at'])) ?></td>
            <td>
              <a href="<?= site_url($prefix . '/students/view/' . $v['student_id']) ?>" class="vs-tbl-btn vs-tbl-btn-view">View</a>
            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<form id="pdfForm" method="POST" action="<?= site_url($prefix . '/vouchers/generate-pdf') ?>" style="display:none">
  <?= csrf_field() ?>
</form>

<?= $this->endSection() ?>
