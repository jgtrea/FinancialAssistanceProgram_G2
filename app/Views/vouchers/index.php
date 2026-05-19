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

  <div class="vs-card">
    <div class="vs-card-body">
      <table id="studentsTable" class="vs-datatable js-data-table" data-search-placeholder="Search students..." style="width:100%">
        <thead>
          <tr>
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
          <tr>
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

<?= $this->endSection() ?>
