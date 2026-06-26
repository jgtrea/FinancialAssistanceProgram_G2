<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $actionOptions = $actionOptions ?? [];

    $filterKeys = ['action', 'date_from', 'date_to'];
    $filterValues = [
        'action'    => $selectedAction ?? '',
        'date_from' => $dateFrom       ?? '',
        'date_to'   => $dateTo         ?? '',
    ];
    $activeFilterCount = count(array_filter($filterValues, static fn ($v) => trim((string) $v) !== ''));
?>

<div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title"><?= session()->get('role') === 'admin' ? 'Audit Logs' : 'My Logs' ?></h4>
            <p class="vs-page-sub">Track Account Activity And Voucher Changes.</p>
        </div>
    </div>

    <form method="get" id="auditFilterForm" class="row g-2 mb-3">
        <?php foreach ($filterKeys as $k): ?>
            <input type="hidden" name="<?= esc($k, 'attr') ?>" value="<?= esc((string) $filterValues[$k], 'attr') ?>">
        <?php endforeach ?>
        <div class="col-12 col-lg">
            <input type="text" name="q" class="form-control vs-advanced-search-input" placeholder="Enter keyword to search (action, description, user)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
        </div>
        <div class="col-12 col-lg-auto d-grid d-lg-block">
            <button type="button" class="btn btn-outline-secondary" style="min-width:90px" id="auditBtnOpenFilter">
                Filters
                <span id="auditFilterBadge" class="badge bg-primary" style="display:<?= $activeFilterCount > 0 ? 'inline-block' : 'none' ?>;margin-left:.35rem"><?= $activeFilterCount > 0 ? esc($activeFilterCount) : '' ?></span>
            </button>
        </div>
        <div class="col-auto d-none d-lg-flex align-items-center">
            <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
        </div>
        <div class="col-12 col-lg-auto">
            <div class="row g-2 row-cols-2 row-cols-lg-auto">
                <div class="col">
                    <button type="submit" class="btn btn-primary w-100" style="min-width:90px">Search</button>
                </div>
                <div class="col">
                    <a href="<?= site_url('user/audit-logs') ?>" class="btn btn-danger w-100 d-block text-center" style="min-width:90px">Clear</a>
                </div>
            </div>
        </div>
    </form>

    <div class="vs-card">
        <div class="vs-card-body">
            <table id="auditLogsTable" class="vs-datatable js-data-table vs-mobile-primary" data-mobile-primary="4" data-page-search="customAuditSearch" data-search-placeholder="Search audit logs..." data-col-defs='[{"className":"text-start","targets":[1,2,3]},{"className":"text-center","targets":[0,4]}]' style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 170px;">Date/Time</th>
                        <th style="width: 200px;">Action</th>
                        <th>Description</th>
                        <th>User Agent</th>
                        <th style="width: 180px;">User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            $createdDate = !empty($log['created_at']) ? date('Y-m-d', strtotime($log['created_at'])) : '';
                            $userLabel = trim((string) ($log['full_name'] ?? $log['username'] ?? ''));
                        ?>
                        <tr class="vs-clickable-row" data-action="<?= esc((string) ($log['action'] ?? ''), 'attr') ?>"
                            data-created-date="<?= esc($createdDate, 'attr') ?>"
                            data-detail-date="<?= !empty($log['created_at']) ? esc(date('M d, Y h:i A', strtotime($log['created_at'])), 'attr') : '-' ?>"
                            data-detail-user="<?= esc(trim(($log['full_name'] ?? $log['email'] ?? '-') . (!empty($log['user_id']) ? ' #' . $log['user_id'] : '')), 'attr') ?>"
                            data-detail-desc="<?= esc((string) $log['description'], 'attr') ?>"
                            data-detail-ua="<?= esc((string) ($log['user_agent'] ?? '-'), 'attr') ?>">
                            <td><?= !empty($log['created_at']) ? esc(date('M d, Y h:i A', strtotime($log['created_at']))) : '-' ?></td>
                            <td><span class="badge text-bg-dark"><?= esc($log['action']) ?></span></td>
                            <td title="<?= esc((string) $log['description'], 'attr') ?>">
                                <?= esc($log['description']) ?>
                                <?php if (!empty($log['student_id']) || !empty($log['voucher_id'])): ?>
                                    <div class="text-muted small">
                                        <?php if (!empty($log['student_id'])): ?>Student ID: <?= esc($log['student_id']) ?><?php endif; ?>
                                        <?php if (!empty($log['student_id']) && !empty($log['voucher_id'])): ?> &middot; <?php endif; ?>
                                        <?php if (!empty($log['voucher_id'])): ?>Voucher ID: <?= esc($log['voucher_id']) ?><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="small" title="<?= esc((string) ($log['user_agent'] ?? ''), 'attr') ?>"><?= esc($log['user_agent']) ?></td>
                            <td>
                                <?= esc($log['full_name'] ?? $log['email'] ?? '-') ?>
                                <?php if (!empty($log['user_id'])): ?>
                                    <span class="text-muted small">#<?= esc($log['user_id']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?= pre_modal('audit') ?>
<script>
window.__VS.pageData = { actionOptions: <?= json_encode($actionOptions) ?> };
</script>

<script>
document.addEventListener('vs:modals:ready', function () {
    var filterForm = document.getElementById('auditFilterForm');
    var fields = {
        action:   document.getElementById('auditFilterAction'),
        dateFrom: document.getElementById('auditFilterDateFrom'),
        dateTo:   document.getElementById('auditFilterDateTo'),
    };
    var filterFieldToParam = {
        action:   'action',
        dateFrom: 'date_from',
        dateTo:   'date_to',
    };

    var modal = document.getElementById('auditFilterModal');
        function openFilter() { if (modal) modal.style.display = 'flex'; }
        function closeFilter() { if (modal) modal.style.display = 'none'; }

        var openButton = document.getElementById('auditBtnOpenFilter');
        var closeButton = document.getElementById('auditFilterModalClose');
        var cancelButton = document.getElementById('auditFilterModalCancel');
        var applyButton = document.getElementById('auditFilterApply');
        var clearButton = document.getElementById('auditFilterClear');

        openButton && openButton.addEventListener('click', openFilter);
        closeButton && closeButton.addEventListener('click', closeFilter);
        cancelButton && cancelButton.addEventListener('click', closeFilter);
        modal && modal.addEventListener('click', function (event) {
            if (event.target === modal) closeFilter();
        });

        // Filters are applied server-side — copy modal values into the hidden
        // inputs in #auditFilterForm and submit. The form GETs the same page
        // with q + filter params; the controller skips the row cap when any
        // filter is active so the result reflects the whole DB.
        function syncFormFromModal() {
            if (!filterForm) return;
            Object.keys(filterFieldToParam).forEach(function (k) {
                var input = filterForm.elements[filterFieldToParam[k]];
                if (input && fields[k]) input.value = fields[k].value;
            });
        }

        applyButton && applyButton.addEventListener('click', function () {
            syncFormFromModal();
            if (filterForm) filterForm.submit();
        });

        clearButton && clearButton.addEventListener('click', function () {
            Object.keys(fields).forEach(function (k) {
                if (fields[k]) fields[k].value = '';
            });
            if (filterForm) {
                Object.keys(filterFieldToParam).forEach(function (k) {
                    var input = filterForm.elements[filterFieldToParam[k]];
                    if (input) input.value = '';
                });
            }
            closeFilter();
            window.location.href = '<?= site_url($resetUrl ?? 'user/audit-logs') ?>';
        });

        // Row click → detail modal
        var detailModal = document.getElementById('auditDetailModal');
        function closeDetail() { if (detailModal) detailModal.style.display = 'none'; }
        var dClose  = document.getElementById('auditDetailModalClose');
        var dCancel = document.getElementById('auditDetailModalCancel');
        dClose  && dClose.addEventListener('click', closeDetail);
        dCancel && dCancel.addEventListener('click', closeDetail);
        detailModal && detailModal.addEventListener('click', function (e) {
            if (e.target === detailModal) closeDetail();
        });

        var auditTable = document.getElementById('auditLogsTable');
        auditTable && auditTable.addEventListener('click', function (e) {
            var row = e.target.closest('tr.vs-clickable-row');
            if (!row || !detailModal) return;
            var set = function (id, val) {
                var el = document.getElementById(id);
                if (el) el.textContent = val || '-';
            };
            set('auditDetailDate',      row.getAttribute('data-detail-date'));
            set('auditDetailAction',    row.getAttribute('data-action'));
            set('auditDetailUser',      row.getAttribute('data-detail-user'));
            set('auditDetailDescription', row.getAttribute('data-detail-desc'));
            set('auditDetailUserAgent', row.getAttribute('data-detail-ua'));
            detailModal.style.display = 'flex';
        });
});
</script>

<?= $this->endSection() ?>
