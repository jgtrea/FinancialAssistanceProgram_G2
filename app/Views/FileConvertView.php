<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container mt-4">
    <h4>Import Vouchers / Students</h4>
    <p>Upload an Excel (.xlsx / .xls) file with voucher data.</p>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <form id="standaloneImportForm" action="<?= site_url('import_data') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="excel_file" class="form-label">Excel File</label>
            <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
        </div>
        <button type="submit" class="btn btn-info" id="standaloneImportBtn">Import</button>
        <a href="<?= site_url('admin/vouchers') ?>" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // Import runs on the background worker now — submit via AJAX, show a progress
  // toast, and surface validation rejects from the job result.
  (function () {
    var form = document.getElementById('standaloneImportForm');
    var btn  = document.getElementById('standaloneImportBtn');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (btn) btn.disabled = true;

      fetch(form.action, { method: 'POST', body: new FormData(form) })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.success) { alert(data.message || 'Import failed.'); return; }
          if (data.queued && data.status_url && typeof trackJob === 'function') {
            trackJob('Importing', data.status_url, {
              persist: true,          // survive page navigation
              jobId:   data.job_id,
              doneLabel: function (d) {
                if (d && d.message) return d.message;
                var n = (d && d.result && typeof d.result.imported === 'number') ? d.result.imported : 0;
                return n.toLocaleString() + ' record(s) imported.';
              },
              onError: function (msg) { alert('Import failed: ' + msg); },
            });
            form.reset();
          }
        })
        .catch(function () { alert('An error occurred while uploading. Please try again.'); })
        .finally(function () { if (btn) btn.disabled = false; });
    });
  })();
</script>
<?= $this->endSection() ?>
