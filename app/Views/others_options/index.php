<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
  <div>
    <h4 class="vs-page-title"><?= esc($title) ?></h4>
    <p class="vs-page-sub">Manage custom dropdown options for Suffix, Prefix, Degree, JHS, and SHS fields.</p>
  </div>
</div>

<div id="ooAlertBox" class="mb-3"></div>

<?php
$contextLabels = $contexts ?? [
    'suffix' => 'Suffix',
    'prefix' => 'Prefix',
    'degree' => 'Degree',
    'jhs'    => 'Junior High School',
    'shs'    => 'Senior High School',
];
?>

<!-- Add Option Panel -->
<div class="vs-card mb-4">
  <div class="vs-card-body">
    <h6 class="mb-3">Add New Option</h6>
    <div class="d-flex gap-2 align-items-end flex-wrap">
      <div>
        <label class="vs-label" for="ooContext">Field</label>
        <select id="ooContext" class="vs-input js-filter-select" style="min-width:200px" data-no-search="1" data-placeholder="Select field">
          <option value=""></option>
          <?php foreach ($contextLabels as $key => $label): ?>
            <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div>
        <label class="vs-label" for="ooValue">Value</label>
        <input id="ooValue" type="text" class="vs-input vs-uppercase" style="min-width:220px" placeholder="e.g. ESQ., ATTY." maxlength="255">
      </div>
      <button id="ooAddBtn" class="btn btn-primary">Add Option</button>
    </div>
  </div>
</div>

<!-- Options Tables -->
<?php foreach ($contextLabels as $ctx => $label): ?>
  <?php $rows = $grouped[$ctx] ?? [] ?>
  <div class="vs-card mb-3" id="oo-section-<?= esc($ctx) ?>">
    <div class="vs-card-body">
      <h6 class="mb-3"><?= esc($label) ?></h6>
      <?php if (empty($rows)): ?>
        <p class="text-muted mb-0">No custom options yet.</p>
      <?php else: ?>
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Value</th>
              <th style="width:100px">Actions</th>
            </tr>
          </thead>
          <tbody id="oo-tbody-<?= esc($ctx) ?>">
            <?php foreach ($rows as $row): ?>
              <tr id="oo-row-<?= (int)$row['id'] ?>">
                <td><?= esc($row['value']) ?></td>
                <td>
                  <button class="btn btn-sm btn-danger oo-delete-btn"
                          data-id="<?= (int)$row['id'] ?>"
                          data-value="<?= esc($row['value'], 'attr') ?>">Delete</button>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      <?php endif ?>
    </div>
  </div>
<?php endforeach ?>

<script>
(function () {
  var alertBox  = document.getElementById('ooAlertBox');
  var addBtn    = document.getElementById('ooAddBtn');
  var ctxEl     = document.getElementById('ooContext');
  var valEl     = document.getElementById('ooValue');
  var saveUrl   = '<?= site_url('admin/others-options/save') ?>';
  var deleteUrl = '<?= site_url('admin/others-options/delete') ?>/';
  var csrf      = function () {
    var m = document.querySelector('meta[name="csrf-token-value"]');
    return m ? m.content : '';
  };

  function showAlert(msg, type) {
    alertBox.innerHTML = '<div class="vs-alert vs-alert-' + (type || 'error') + ' mb-2">' + msg + '</div>';
    setTimeout(function () { alertBox.innerHTML = ''; }, 4000);
  }

  addBtn.addEventListener('click', function () {
    var ctx = ctxEl ? ctxEl.value : '';
    var val = valEl.value.trim().toUpperCase();
    if (!ctx) { showAlert('Please select a field.'); return; }
    if (!val) { showAlert('Please enter a value.'); return; }

    addBtn.disabled = true;
    fetch(saveUrl, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'context=' + encodeURIComponent(ctx) + '&value=' + encodeURIComponent(val) + '&csrf_token=' + encodeURIComponent(csrf()),
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      addBtn.disabled = false;
      if (!d.success) { showAlert(d.message || 'Error saving option.'); return; }
      if (d.csrf_token) {
        var m = document.querySelector('meta[name="csrf-token-value"]');
        if (m) m.content = d.csrf_token;
      }
      showAlert('Option added successfully.', 'success');
      valEl.value = '';
      // Add row to table
      var tbody = document.getElementById('oo-tbody-' + ctx);
      var section = document.getElementById('oo-section-' + ctx);
      if (section) {
        // Reload page for simplicity so the new row appears properly
        setTimeout(function () { window.location.reload(); }, 800);
      }
    })
    .catch(function () { addBtn.disabled = false; showAlert('Network error.'); });
  });

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.oo-delete-btn');
    if (!btn) return;
    var id  = btn.dataset.id;
    var val = btn.dataset.value;
    if (!confirm('Delete "' + val + '"?')) return;
    btn.disabled = true;
    fetch(deleteUrl + id, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(csrf()),
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (!d.success) { btn.disabled = false; showAlert(d.message || 'Error deleting option.'); return; }
      if (d.csrf_token) {
        var m = document.querySelector('meta[name="csrf-token-value"]');
        if (m) m.content = d.csrf_token;
      }
      var row = document.getElementById('oo-row-' + id);
      if (row) row.remove();
      showAlert('Option deleted.', 'success');
    })
    .catch(function () { btn.disabled = false; showAlert('Network error.'); });
  });

  // Initialize Select2 for context select
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.initVsSelect2 === 'function') window.initVsSelect2(document);
  });
}());
</script>

<?= $this->endSection() ?>
