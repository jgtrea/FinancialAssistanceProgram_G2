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
            <p class="vs-page-sub">Track your account activity and voucher changes.</p>
        </div>
    </div>

    <form method="get" id="auditFilterForm" class="vs-advanced-search vs-advanced-search-outside mb-3">
        <input type="text" name="q" class="vs-input vs-advanced-search-input" placeholder="Enter keyword to search (action, description, etc.)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
        <button type="button" class="vs-btn vs-btn-outline" id="auditBtnOpenFilter">
            Filters
            <span id="auditFilterBadge" class="badge bg-primary" style="display:<?= $activeFilterCount > 0 ? 'inline-block' : 'none' ?>;margin-left:.35rem"><?= $activeFilterCount > 0 ? esc($activeFilterCount) : '' ?></span>
        </button>
        <?php foreach ($filterKeys as $k): ?>
            <input type="hidden" name="<?= esc($k, 'attr') ?>" value="<?= esc((string) $filterValues[$k], 'attr') ?>">
        <?php endforeach ?>
    </form>

    <div class="vs-card">
        <div class="vs-card-body">
            <table id="auditLogsTable" class="vs-datatable js-data-table" data-page-search="customAuditSearch" data-search-placeholder="Search audit logs..." style="width:100%">
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

<div class="vs-modal-overlay" id="auditFilterModal" style="display:none">
    <div class="vs-modal" style="max-width:680px">
        <div class="vs-modal-header">
            <h5>Advanced Filters</h5>
            <button class="vs-modal-close" id="auditFilterModalClose">&times;</button>
        </div>
        <div class="vs-modal-body">
            <div class="vs-form-grid vs-form-grid-4">
                <div class="vs-span-4">
                    <label class="vs-label" for="auditFilterAction">Action</label>
                    <input list="auditFilterAction-list" id="auditFilterAction" class="vs-input" placeholder="All" value="<?= esc((string) $filterValues['action'], 'attr') ?>">
                    <datalist id="auditFilterAction-list">
                        <?php foreach ($actionOptions as $option): ?>
                            <?php $val = is_array($option) ? ($option['action'] ?? '') : $option ?>
                            <option value="<?= esc($val) ?>">
                        <?php endforeach ?>
                    </datalist>
                </div>
                <div class="vs-span-2">
                    <label class="vs-label" for="auditFilterDateFrom">Date From</label>
                    <input type="date" id="auditFilterDateFrom" class="vs-input" value="<?= esc((string) $filterValues['date_from'], 'attr') ?>">
                </div>
                <div class="vs-span-2">
                    <label class="vs-label" for="auditFilterDateTo">Date To</label>
                    <input type="date" id="auditFilterDateTo" class="vs-input" value="<?= esc((string) $filterValues['date_to'], 'attr') ?>">
                </div>
            </div>
        </div>
        <div class="vs-modal-footer">
            <button class="vs-btn vs-btn-outline" id="auditFilterClear">Clear All</button>
            <button class="vs-btn vs-btn-outline" id="auditFilterModalCancel">Cancel</button>
            <button class="vs-btn vs-btn-primary" id="auditFilterApply">Apply Filters</button>
        </div>
    </div>
</div>

<script>
(function () {
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
                filterForm.submit();
            }
        });
}());
</script>

<?= $this->endSection() ?>
