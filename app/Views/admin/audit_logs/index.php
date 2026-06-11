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
            <p class="vs-page-sub">Track Account Activity And Voucher Changes.</p>
        </div>
    </div>

    <!-- Inline audit filters (Action select + date range). Auto-submits on
         change so the user sees results immediately, matching the Schools
         page quick-filter pattern. -->
    <form method="get" id="auditFilterForm" class="row g-2 align-items-center mb-3">
        <div class="col-12 col-md">
            <input type="text" name="q" class="vs-input vs-advanced-search-input w-100" placeholder="Enter keyword to search (action, description)" value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
        </div>
        <div class="col-6 col-md-2">
            <select name="action" id="auditFilterAction" class="js-filter-select" data-placeholder="Select Action Status" style="width:100%">
                <option></option>
                <?php foreach ($actionOptions as $option): ?>
                    <?php $val = is_array($option) ? ($option['action'] ?? '') : $option ?>
                    <option value="<?= esc($val) ?>" <?= (string) $filterValues['action'] === $val ? 'selected' : '' ?>><?= esc($val) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <input type="date" name="date_from" id="auditFilterDateFrom" class="vs-input w-100" value="<?= esc((string) $filterValues['date_from'], 'attr') ?>" title="Date From">
        </div>
        <div class="col-6 col-md-2">
            <input type="date" name="date_to" id="auditFilterDateTo" class="vs-input w-100" value="<?= esc((string) $filterValues['date_to'], 'attr') ?>" title="Date To">
        </div>
        <div class="col-auto d-none d-md-flex align-items-center">
            <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
        </div>
        <div class="col-6 col-md-2 d-flex gap-2">
            <button type="submit" class="vs-btn vs-btn-primary flex-fill">Search</button>
            <a href="<?= site_url('admin/audit-logs') ?>" class="vs-btn vs-btn-danger flex-fill">Clear</a>
        </div>
    </form>

    <div class="vs-card">
        <div class="vs-card-body">
            <table id="adminAuditLogsTable" class="vs-datatable js-data-table vs-mobile-primary" data-mobile-primary="4" data-page-search="customAuditSearch" data-search-placeholder="Search audit logs..." style="width:100%">
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

<?= $this->endSection() ?>
