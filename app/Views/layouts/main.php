<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-token-value" content="<?= csrf_hash() ?>">
    <title><?= esc($title ?? 'Voucher System') ?></title>
    <?php pre_style('app'); ?>
</head>

<body class="sb-nav-fixed">
    <?= $this->include('partials/topbar') ?>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?= $this->include('partials/sidebar') ?>
        </div>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 py-4">
                    <?= $this->renderSection('content') ?>
                </div>
            </main>

            <?= $this->include('partials/footer') ?>
        </div>
    </div>

    <!-- Global PDF status modal — used by the toast Status button so it works
         from any page after the user navigates away from the voucher listing. -->
    <div class="vs-modal-overlay" id="pdfStatusModal" style="display:none">
      <div class="vs-modal">
        <div class="vs-modal-header">
          <h5>Voucher Generation Status</h5>
          <button class="vs-modal-close" id="pdfStatusModalClose">&times;</button>
        </div>
        <div class="vs-modal-body">
          <p id="pdfStatusEmpty" style="display:none">No recent generation job for this session.</p>
          <div id="pdfStatusContent" style="display:none">
            <p><strong>Job #:</strong> <span id="pdfStatusJobId">-</span></p>
            <p><strong>Status:</strong> <span id="pdfStatusBadge" class="vs-badge">-</span></p>
            <p id="pdfStatusProgressLine"><strong>Progress:</strong> <span id="pdfStatusProgress">0 / 0</span></p>
            <p id="pdfStatusErrorLine" style="display:none; color:#b00020"><strong>Error:</strong> <span id="pdfStatusError"></span></p>
            <div class="mt-3" id="pdfStatusDownloadWrap" style="display:none">
              <a id="pdfStatusDownload" class="vs-btn vs-btn-blue" href="#">
                Download Voucher
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php pre_script('app'); ?>
    <?= $this->renderSection('scripts') ?>
</body>

</html>
