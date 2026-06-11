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
            <h4 class="vs-page-title">Audit Logs</h4>
            <p class="vs-page-sub">Track Your Account Activity And Voucher Changes.</p>
        </div>
    </div>

    <form method="get" id="auditFilterForm" class="row g-2 align-items-center mb-3">
        <?php foreach ($filterKeys as $k): ?>
            <input type="hidden" name="<?= esc($k, 'attr') ?>" value="<?= esc((string) $filterValues[$k], 'attr') ?>">
        <?php endforeach ?>
        <div class="col-12 col-md">
            <input type="text" name="q" class="vs-input vs-advanced-search-input w-100" placeholder="Enter keyword to search (action, description)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
        </div>
        <div class="col-6 col-md-auto">
            <button type="button" class="vs-btn vs-btn-outline w-100" id="auditBtnOpenFilter">
                Filters
                <span id="auditFilterBadge" class="badge bg-primary" style="display:<?= $activeFilterCount > 0 ? 'inline-block' : 'none' ?>;margin-left:.35rem"><?= $activeFilterCount > 0 ? esc($activeFilterCount) : '' ?></span>
            </button>
        </div>
        <div class="col-auto d-none d-md-flex align-items-center">
            <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
        </div>
        <div class="col-6 col-md-2 d-flex gap-2">
            <button type="submit" class="vs-btn vs-btn-primary flex-fill">Search</button>
            <a href="<?= site_url('user/audit-logs') ?>" class="vs-btn vs-btn-danger flex-fill">Clear</a>
        </div>
    </form>

    <div class="vs-card">
        <div class="vs-card-body">
            <table id="auditLogsTable" class="vs-datatable js-data-table vs-mobile-primary" data-mobile-primary="4" data-page-search="customAuditSearch" data-search-placeholder="Search audit logs..." style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 170px;">Date/Time</th>
                        <th style="width: 170px;">Action</th>
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
                        <tr data-action="<?= esc((string) ($log['action'] ?? ''), 'attr') ?>"
                            data-created-date="<?= esc($createdDate, 'attr') ?>">
                            <td><?= !empty($log['created_at']) ? esc(date('M d, Y h:i A', strtotime($log['created_at']))) : '-' ?></td>
                            <td><span class="badge text-bg-dark"><?= esc($log['action']) ?></span></td>
                            <td>
                                <?= esc($log['description']) ?>
                                <?php if (!empty($log['student_id']) || !empty($log['voucher_id'])): ?>
                                    <div class="text-muted small">
                                        <?php if (!empty($log['student_id'])): ?>Student ID: <?= esc($log['student_id']) ?><?php endif; ?>
                                        <?php if (!empty($log['student_id']) && !empty($log['voucher_id'])): ?> &middot; <?php endif; ?>
                                        <?php if (!empty($log['voucher_id'])): ?>Voucher ID: <?= esc($log['voucher_id']) ?><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= esc($log['user_agent']) ?></td>
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
});
</script>

<?= $this->endSection() ?>
