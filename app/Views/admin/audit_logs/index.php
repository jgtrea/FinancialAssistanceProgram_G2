<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    // Dropdown source lists come from the controller (full DB, not the loaded slice).
    $actionOptions = $actionOptions ?? [];
    $userOptions   = $userOptions   ?? [];
    $ipOptions     = $ipOptions     ?? [];

    $filterKeys = ['action', 'user', 'ip', 'date_from', 'date_to'];
    $filterValues = [
        'action'    => $selectedAction ?? '',
        'user'      => $selectedUser   ?? '',
        'ip'        => $selectedIp     ?? '',
        'date_from' => $dateFrom       ?? '',
        'date_to'   => $dateTo         ?? '',
    ];
    $activeFilterCount = count(array_filter($filterValues, static fn ($v) => trim((string) $v) !== ''));
?>

<div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">Audit Logs</h4>
            <p class="vs-page-sub">Track account activity and voucher changes.</p>
        </div>
    </div>

    <form method="get" id="auditFilterForm" class="vs-advanced-search vs-advanced-search-outside mb-3">
        <input type="text" name="q" class="vs-input vs-advanced-search-input" placeholder="Advanced search all audit logs..." value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
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
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <input type="text" id="auditCustomSearch" class="vs-input vs-page-search" placeholder="Search this page..." style="max-width:260px">
                <label class="vs-length-label ms-auto">Show <input type="number" id="auditLengthInput" class="vs-length-input" value="10" min="1" max="500"> entries</label>
            </div>
            <table id="adminAuditLogsTable" class="vs-datatable js-data-table" data-search-placeholder="Search audit logs..." style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 170px;">Date/Time</th>
                        <th style="width: 170px;">Action</th>
                        <th>Description</th>
                        <th style="width: 150px;">IP Address</th>
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
                            data-created-date="<?= esc($createdDate, 'attr') ?>"
                            data-ip="<?= esc((string) ($log['ip_address'] ?? ''), 'attr') ?>"
                            data-user="<?= esc($userLabel, 'attr') ?>">
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
                            <td><?= esc($log['ip_address']) ?></td>
                            <td class="small"><?= esc($log['user_agent']) ?></td>
                            <td>
                                <?= esc($log['full_name'] ?? $log['username'] ?? '-') ?>
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
                <div class="vs-span-2">
                    <label class="vs-label" for="auditFilterAction">Action</label>
                    <select id="auditFilterAction" class="vs-input">
                        <option value="">All</option>
                        <?php foreach ($actionOptions as $option): ?>
                            <?php $val = is_array($option) ? ($option['action'] ?? '') : $option ?>
                            <option value="<?= esc($val) ?>" <?= $filterValues['action'] === $val ? 'selected' : '' ?>><?= esc($val) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="vs-span-2">
                    <label class="vs-label" for="auditFilterUser">User</label>
                    <select id="auditFilterUser" class="vs-input">
                        <option value="">All</option>
                        <?php foreach ($userOptions as $user): ?>
                            <option value="<?= esc($user) ?>" <?= $filterValues['user'] === $user ? 'selected' : '' ?>><?= esc($user) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="vs-span-2">
                    <label class="vs-label" for="auditFilterDateFrom">Date From</label>
                    <input type="date" id="auditFilterDateFrom" class="vs-input" value="<?= esc((string) $filterValues['date_from'], 'attr') ?>">
                </div>
                <div class="vs-span-2">
                    <label class="vs-label" for="auditFilterDateTo">Date To</label>
                    <input type="date" id="auditFilterDateTo" class="vs-input" value="<?= esc((string) $filterValues['date_to'], 'attr') ?>">
                </div>
                <div class="vs-span-2">
                    <label class="vs-label" for="auditFilterIp">IP Address</label>
                    <select id="auditFilterIp" class="vs-input">
                        <option value="">All</option>
                        <?php foreach ($ipOptions as $ip): ?>
                            <option value="<?= esc($ip) ?>" <?= $filterValues['ip'] === $ip ? 'selected' : '' ?>><?= esc($ip) ?></option>
                        <?php endforeach ?>
                    </select>
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
    function initAuditFilters() {
        var table = document.getElementById('adminAuditLogsTable');
        if (!table || !window.jQuery || !$.fn.DataTable.isDataTable(table)) {
            return setTimeout(initAuditFilters, 50);
        }

        var dt = $(table).DataTable();
        var filterForm = document.getElementById('auditFilterForm');
        var fields = {
            action:   document.getElementById('auditFilterAction'),
            user:     document.getElementById('auditFilterUser'),
            dateFrom: document.getElementById('auditFilterDateFrom'),
            dateTo:   document.getElementById('auditFilterDateTo'),
            ip:       document.getElementById('auditFilterIp'),
        };
        var filterFieldToParam = {
            action:   'action',
            user:     'user',
            ip:       'ip',
            dateFrom: 'date_from',
            dateTo:   'date_to',
        };

        var wrap = table.closest('.dataTables_wrapper');
        var dtSearch = wrap ? wrap.querySelector('.dataTables_filter') : null;
        var dtLength = wrap ? wrap.querySelector('.dataTables_length') : null;
        if (dtSearch) dtSearch.style.display = 'none';
        if (dtLength) dtLength.style.display = 'none';

        var lenInput = document.getElementById('auditLengthInput');
        if (lenInput) {
            function applyAuditLen() {
                var v = parseInt(lenInput.value, 10);
                if (!isNaN(v) && v > 0) dt.page.len(v).draw();
            }
            lenInput.addEventListener('change', applyAuditLen);
            lenInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') applyAuditLen(); });
        }

        // In-page search filters across ALL loaded rows (server caps at ~1000;
        // see AuditLogController::LISTING_DEFAULT_LIMIT).
        var customSearch = document.getElementById('auditCustomSearch');
        if (window.VS && window.VS.bindFullTableSearch) {
            window.VS.bindFullTableSearch(dt, customSearch);
        }

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
    }

    initAuditFilters();
}());
</script>

<?= $this->endSection() ?>
