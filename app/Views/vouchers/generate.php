<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $role = $role ?? 'admin' ?>
<?php $prefix = $role === 'admin' ? 'admin' : 'user' ?>

<div class="vs-page-header mb-4">
    <div>
      <h4 class="vs-page-title"><?= esc($title) ?></h4>
      <p class="vs-page-sub">Select Students And Generate Printable Voucher PDFs.</p>
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
    <button class="vs-btn vs-btn-blue" id="btnGeneratePdf">
      <?= asset_icon('voucher-add') ?>
      Generate
    </button>
    <button type="button" class="vs-btn vs-btn-outline" id="btnOpenStatus">
      Status
    </button>
    <button type="button" class="vs-btn vs-btn-outline" id="btnOpenExport">
      <?= asset_icon('export') ?>
      Export
    </button>
  </div>

  <form method="get" class="vs-advanced-search vs-advanced-search-outside mb-3">
    <input type="text" name="q" class="vs-input vs-advanced-search-input" placeholder="Enter keyword to search (voucher no, name)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
  </form>

  <div class="vs-card">
    <div class="vs-card-body">
      <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <input type="text" id="customVouchersSearch" class="vs-input vs-page-search" placeholder="Enter keyword to search this page" style="max-width:260px">
      </div>
      <table id="vouchersTable" class="vs-datatable vs-mobile-primary" data-mobile-primary="2" data-search-placeholder="Search vouchers..." style="width:100%">
        <thead>
          <tr>
            <th class="vs-th-check"><input type="checkbox" class="vs-check vs-check-all" aria-label="Select all vouchers"></th>
            <th>Voucher No.</th>
            <th>Name</th>
            <th style="display:none">Name Sort</th>
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
          <?php $notEligible = ($v['eligibility_status'] ?? '') === 'not_eligible' ?>
          <tr id="row-<?= esc($v['student_id'], 'attr') ?>"
              data-eligibility="<?= esc((string) ($v['eligibility_status'] ?? ''), 'attr') ?>">
            <td><input type="checkbox" class="vs-check vs-row-check" value="<?= esc($v['student_id'], 'attr') ?>"<?= $notEligible ? ' disabled title="Not eligible — cannot be selected"' : '' ?>></td>
            <td class="js-voucher-no"><?= esc($v['voucher_no'] ?: '-') ?></td>
            <?php
                $gLn = trim((string) ($v['last_name']   ?? ''));
                $gFm = implode(' ', array_filter([trim((string) ($v['first_name'] ?? '')), trim((string) ($v['middle_name'] ?? ''))]));
                $gDn = $gLn !== '' ? $gLn . ($gFm !== '' ? ', ' . $gFm : '') : $gFm;
            ?>
            <td><?= esc($gDn) ?></td>
            <td style="display:none"><?= esc(trim($gLn . ' ' . $gFm)) ?></td>
            <td><?= esc($v['preferred_senior_high_school']) ?></td>
            <td><?= esc($v['school_year']) ?></td>
            <td>
              <?php $elig = (string) ($v['eligibility_status'] ?? '') ?>
              <?php $eligLabel = $elig === 'eligible' ? 'Eligible' : ($elig === 'not_eligible' ? 'Not eligible' : '—') ?>
              <?php if ($elig === 'eligible' || $elig === 'not_eligible'): ?>
                <span class="vs-eligibility-icon vs-eligibility-icon-<?= esc($elig, 'attr') ?>" title="<?= esc($eligLabel, 'attr') ?>" aria-label="<?= esc($eligLabel, 'attr') ?>">
                  <?= asset_icon($elig === 'eligible' ? 'check' : 'cross') ?>
                </span>
              <?php else: ?>
                <span aria-label="Unknown">—</span>
              <?php endif ?>
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

<form id="pdfForm" method="POST" action="<?= site_url($prefix . '/vouchers/json-generate-pdf') ?>" style="display:none">
  <?= csrf_field() ?>
</form>

<!-- Status modal now lives in layouts/main.php so the toast Status button works on every page. -->

<!-- Export modal -->
<div class="vs-modal-overlay" id="exportModal" style="display:none">
  <div class="vs-modal">
    <div class="vs-modal-header">
      <h5>Export Selected Students</h5>
      <button class="vs-modal-close" id="exportModalClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <p>Choose the file format to export the selected student records.</p>
      <div class="d-flex gap-3 mt-3">
        <a href="<?= site_url('vouchers/export?format=xlsx') ?>" data-export-format="xlsx" class="vs-btn vs-btn-outline flex-fill text-center">
          Excel (.xlsx)
        </a>
        <a href="<?= site_url('vouchers/export?format=csv') ?>" data-export-format="csv" class="vs-btn vs-btn-outline flex-fill text-center">
          CSV (.csv)
        </a>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
