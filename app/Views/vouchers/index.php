<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $role = $role ?? 'admin' ?>
<?php $prefix = $role === 'admin' ? 'admin' : 'user' ?>

<div class="container-fluid px-4 py-4">

  <div class="vs-page-header mb-4">
    <div>
      <h4 class="vs-page-title"><?= esc($title) ?></h4>
      <p class="vs-page-sub">Manage student financial assistance records.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= site_url($prefix . '/students/create') ?>" class="vs-btn vs-btn-primary">
        <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
        Add Student
      </a>
      <a href="<?= site_url($prefix . '/vouchers') ?>" class="vs-btn vs-btn-outline">
        <?= asset_icon('voucher-add') ?>
        Generate Vouchers
      </a>
      <a href="<?= site_url('import') ?>" class="vs-btn vs-btn-outline">
        <?= asset_icon('import') ?>
        Import
      </a>
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
    <button class="vs-btn vs-btn-danger" id="btnArchive">
      <?= asset_icon('archive') ?>
      Archive Selected
    </button>
  </div>

  <div class="vs-card">
    <div class="vs-card-body">
      <table id="studentsTable" class="vs-datatable" data-search-placeholder="Search students..." style="width:100%">
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
            <td><?= esc($v['voucher_no'] ?: '-') ?></td>
            <td><?= esc($v['full_name']) ?></td>
            <td><?= esc($v['preferred_senior_high_school']) ?></td>
            <td><?= esc($v['school_year']) ?></td>
            <td>
              <span class="vs-status-badge vs-status-<?= esc($v['eligibility_status'], 'attr') ?>">
                <?= esc(ucfirst(str_replace('_', ' ', $v['eligibility_status']))) ?>
              </span>
            </td>
            <td>
              <?= esc((string) ($v['generate_count'] ?? 0)) ?>
            </td>
            <td><?= date('M d, Y', strtotime($v['created_at'])) ?></td>
            <td>
              <div class="d-flex gap-1">
                <a href="<?= site_url($prefix . '/students/view/' . $v['student_id']) ?>" class="vs-tbl-btn vs-tbl-btn-view">View</a>
                <a href="<?= site_url($prefix . '/students/edit/' . $v['student_id']) ?>" class="vs-tbl-btn vs-tbl-btn-edit">Edit</a>
              </div>
            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Archive modal -->
<div class="vs-modal-overlay" id="archiveModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5>Archive Students</h5>
      <button class="vs-modal-close" id="archiveModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <p>You are about to archive <strong id="archiveCount">0</strong> student(s). This will move them to the archive.</p>
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

<form id="archiveForm" action="<?= site_url($prefix . '/vouchers/archive') ?>" style="display:none">
  <?= csrf_field() ?>
</form>

<?= $this->endSection() ?>
