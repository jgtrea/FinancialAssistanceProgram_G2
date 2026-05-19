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
        <?= asset_icon('students') ?>
        Student Management
      </a>
      <button class="vs-btn vs-btn-outline" id="btnExport">
        <?= asset_icon('download') ?>
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
      <?= asset_icon('voucher-add') ?>
      Generate Vouchers
    </button>
  </div>

  <div class="vs-card">
    <div class="vs-card-body">
      <table id="vouchersTable" class="vs-datatable" style="width:100%">
        <thead>
          <tr>
            <th class="vs-th-check"><input type="checkbox" id="checkAll" class="vs-check"></th>
            <th>Voucher No.</th>
            <th>Name</th>
            <th>Preferred School</th>
            <th>School Year</th>
            <th>Eligibility</th>
            <th>Generate Count</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vouchers as $v): ?>
          <tr id="row-<?= esc($v['student_id'], 'attr') ?>">
            <td><input type="checkbox" class="vs-check vs-row-check" value="<?= esc($v['student_id'], 'attr') ?>"></td>
            <td class="js-voucher-no"><?= esc($v['voucher_no'] ?: '-') ?></td>
            <td><?= esc($v['full_name']) ?></td>
            <td><?= esc($v['preferred_senior_high_school']) ?></td>
            <td><?= esc($v['school_year']) ?></td>
            <td>
              <span class="vs-status-badge vs-status-<?= esc($v['eligibility_status'], 'attr') ?>">
                <?= esc(ucfirst(str_replace('_', ' ', $v['eligibility_status']))) ?>
              </span>
            </td>
            <td>
              <span class="js-generate-count"><?= esc((string) ($v['generate_count'] ?? 0)) ?></span>
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
