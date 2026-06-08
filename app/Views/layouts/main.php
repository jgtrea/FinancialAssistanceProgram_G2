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

    <!-- My Account modal -->
    <div class="vs-modal-overlay" id="accountModal" style="display:none">
        <div class="vs-modal" style="max-width:720px;width:95%">
            <div class="vs-modal-header">
                <h5>My Account</h5>
                <button class="vs-modal-close" id="accountModalClose">&times;</button>
            </div>
            <div class="vs-modal-body">
                <div id="accountModalMsg" class="mb-3" style="display:none"></div>
                <form id="accountModalForm" autocomplete="off">
                    <input type="hidden" id="amCsrf" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                    <div class="vs-form-grid vs-form-grid-4">
                        <div>
                            <label class="vs-label required">Username</label>
                            <input id="amUsername" name="username" type="text" class="vs-input" required>
                        </div>
                        <div>
                            <label class="vs-label required">Email</label>
                            <input id="amEmail" name="email" type="email" class="vs-input" required>
                        </div>
                        <div>
                            <label class="vs-label">Role</label>
                            <div id="amRole" class="vs-input" style="background:#f9fafb;cursor:default"></div>
                        </div>
                        <div></div>

                        <div>
                            <label class="vs-label required">First Name</label>
                            <input id="amFirstName" name="first_name" type="text" class="vs-input vs-uppercase" required>
                        </div>
                        <div>
                            <label class="vs-label">Middle Name</label>
                            <input id="amMiddleName" name="middle_name" type="text" class="vs-input vs-uppercase">
                        </div>
                        <div>
                            <label class="vs-label required">Last Name</label>
                            <input id="amLastName" name="last_name" type="text" class="vs-input vs-uppercase" required>
                        </div>
                        <div></div>

                        <div class="vs-span-4">
                            <h2 class="vs-section-title">Change Password</h2>
                        </div>
                        <div>
                            <label class="vs-label">Current Password</label>
                            <input id="amCurrentPw" name="current_password" type="password" class="vs-input" autocomplete="current-password">
                        </div>
                        <div>
                            <label class="vs-label">New Password</label>
                            <input id="amNewPw" name="new_password" type="password" class="vs-input" autocomplete="new-password">
                        </div>
                        <div>
                            <label class="vs-label">Confirm Password</label>
                            <input id="amConfirmPw" name="confirm_password" type="password" class="vs-input" autocomplete="new-password">
                        </div>
                    </div>
                </form>
            </div>
            <div class="vs-modal-footer">
                <button type="button" class="vs-btn vs-btn-outline" id="accountModalCancel">Cancel</button>
                <button type="button" class="vs-btn vs-btn-primary" id="accountModalSave">Save Account</button>
            </div>
        </div>
    </div>

    <?php pre_script('app'); ?>
    <?= $this->renderSection('scripts') ?>
    <script>
    (function () {
        var overlay   = document.getElementById('accountModal');
        var closeBtn  = document.getElementById('accountModalClose');
        var cancelBtn = document.getElementById('accountModalCancel');
        var saveBtn   = document.getElementById('accountModalSave');
        var openBtn   = document.getElementById('btnOpenAccountModal');
        var msgBox    = document.getElementById('accountModalMsg');
        var csrfInput = document.getElementById('amCsrf');

        var dataUrl   = '<?= site_url('profile/data') ?>';
        var updateUrl = '<?= site_url('profile') ?>';

        function openModal() {
            overlay.style.display = 'flex';
            msgBox.style.display = 'none';
            msgBox.className = 'mb-3';
            document.getElementById('amCurrentPw').value = '';
            document.getElementById('amNewPw').value = '';
            document.getElementById('amConfirmPw').value = '';
            fetch(dataUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    document.getElementById('amUsername').value   = d.username   || '';
                    document.getElementById('amEmail').value      = d.email      || '';
                    document.getElementById('amRole').textContent = d.role       || '';
                    document.getElementById('amFirstName').value  = d.first_name || '';
                    document.getElementById('amMiddleName').value = d.middle_name|| '';
                    document.getElementById('amLastName').value   = d.last_name  || '';
                });
        }

        function closeModal() {
            overlay.style.display = 'none';
        }

        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        saveBtn.addEventListener('click', function () {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';

            var fd = new FormData(document.getElementById('accountModalForm'));

            fetch(updateUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    msgBox.style.display = '';
                    if (data.csrf_value) csrfInput.value = data.csrf_value;

                    if (data.success) {
                        msgBox.className = 'mb-3 vs-alert vs-alert-success';
                        msgBox.textContent = data.message;
                        if (data.full_name) {
                            var initial = (data.full_name.trim().charAt(0) || 'U').toUpperCase();
                            openBtn.textContent = initial;
                            openBtn.title = data.full_name;
                        }
                    } else {
                        msgBox.className = 'mb-3 vs-alert vs-alert-error';
                        var msg = data.message || 'An error occurred.';
                        if (data.errors) {
                            msg += ' ' + Object.values(data.errors).join(' ');
                        }
                        msgBox.textContent = msg;
                    }
                })
                .catch(function () {
                    msgBox.style.display = '';
                    msgBox.className = 'mb-3 vs-alert vs-alert-error';
                    msgBox.textContent = 'Network error. Please try again.';
                })
                .finally(function () {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Account';
                });
        });
    })();
    </script>
</body>

</html>
