<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $userOptions = [];
    $ipOptions = [];
    foreach ($logs as $log) {
        $userLabel = trim((string) ($log['full_name'] ?? $log['username'] ?? ''));
        if ($userLabel !== '') {
            $userOptions[$userLabel] = $userLabel;
        }
        $ip = trim((string) ($log['ip_address'] ?? ''));
        if ($ip !== '') {
            $ipOptions[$ip] = $ip;
        }
    }
    ksort($userOptions);
    ksort($ipOptions);
?>

<div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">Audit Logs</h4>
            <p class="vs-page-sub">Track account activity and voucher changes.</p>
        </div>
    </div>

    <div class="vs-card">
        <div class="vs-card-body">
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <input type="text" id="auditCustomSearch" class="vs-input" placeholder="Search audit logs..." style="max-width:340px">
                <button type="button" class="vs-btn vs-btn-outline" id="auditBtnOpenFilter">
                    Filters
                    <span id="auditFilterBadge" class="badge bg-primary" style="display:none;margin-left:.35rem"></span>
                </button>
                <div id="auditLengthSlot" class="ms-2"></div>
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
                            <option value="<?= esc($option['action']) ?>"><?= esc($option['action']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="vs-span-2">
                    <label class="vs-label" for="auditFilterUser">User</label>
                    <select id="auditFilterUser" class="vs-input">
                        <option value="">All</option>
                        <?php foreach ($userOptions as $user): ?>
                            <option value="<?= esc($user) ?>"><?= esc($user) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="vs-span-2">
                    <label class="vs-label" for="auditFilterDateFrom">Date From</label>
                    <input type="date" id="auditFilterDateFrom" class="vs-input">
                </div>
                <div class="vs-span-2">
                    <label class="vs-label" for="auditFilterDateTo">Date To</label>
                    <input type="date" id="auditFilterDateTo" class="vs-input">
                </div>
                <div class="vs-span-2">
                    <label class="vs-label" for="auditFilterIp">IP Address</label>
                    <select id="auditFilterIp" class="vs-input">
                        <option value="">All</option>
                        <?php foreach ($ipOptions as $ip): ?>
                            <option value="<?= esc($ip) ?>"><?= esc($ip) ?></option>
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
        var active = {};
        var fields = {
            action: document.getElementById('auditFilterAction'),
            user: document.getElementById('auditFilterUser'),
            dateFrom: document.getElementById('auditFilterDateFrom'),
            dateTo: document.getElementById('auditFilterDateTo'),
            ip: document.getElementById('auditFilterIp'),
        };

        var wrap = table.closest('.dataTables_wrapper');
        var dtSearch = wrap ? wrap.querySelector('.dataTables_filter') : null;
        var dtLength = wrap ? wrap.querySelector('.dataTables_length') : null;
        var lengthSlot = document.getElementById('auditLengthSlot');
        if (dtSearch) dtSearch.style.display = 'none';
        if (dtLength && lengthSlot) lengthSlot.appendChild(dtLength);

        var customSearch = document.getElementById('auditCustomSearch');
        if (customSearch) {
            customSearch.addEventListener('input', function () {
                dt.search(this.value).draw();
            });
        }

        function activeFilterCount() {
            return Object.keys(active).filter(function (key) {
                return active[key] !== undefined && active[key] !== null && String(active[key]).trim() !== '';
            }).length;
        }

        function updateBadge() {
            var badge = document.getElementById('auditFilterBadge');
            if (!badge) return;
            var count = activeFilterCount();
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }

        $.fn.dataTable.ext.search.push(function (settings, rowData, rowIdx) {
            if (settings.nTable.id !== 'adminAuditLogsTable') return true;
            if (activeFilterCount() === 0) return true;

            var row = settings.aoData[rowIdx].nTr;
            if (!row) return true;

            if (active.action && (row.getAttribute('data-action') || '') !== active.action) return false;
            if (active.user && (row.getAttribute('data-user') || '') !== active.user) return false;
            if (active.ip && (row.getAttribute('data-ip') || '') !== active.ip) return false;

            var createdDate = row.getAttribute('data-created-date') || '';
            if ((active.dateFrom || active.dateTo) && !createdDate) return false;
            if (active.dateFrom && createdDate < active.dateFrom) return false;
            if (active.dateTo && createdDate > active.dateTo) return false;

            return true;
        });

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

        applyButton && applyButton.addEventListener('click', function () {
            active = {
                action: fields.action.value,
                user: fields.user.value,
                dateFrom: fields.dateFrom.value,
                dateTo: fields.dateTo.value,
                ip: fields.ip.value,
            };
            updateBadge();
            dt.draw();
            closeFilter();
        });

        clearButton && clearButton.addEventListener('click', function () {
            Object.keys(fields).forEach(function (key) {
                if (fields[key]) fields[key].value = '';
            });
            active = {};
            updateBadge();
            dt.draw();
        });
    }

    initAuditFilters();
}());
</script>

<?= $this->endSection() ?>
