<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid px-4 py-4">
    <div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">Audit Logs</h4>
            <p class="vs-page-sub">Track your account activity and voucher changes.</p>
        </div>
    </div>

    <form method="get" action="<?= base_url('/user/audit-logs') ?>" class="vs-card mb-4">
        <div class="vs-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control" value="<?= esc($keyword) ?>" placeholder="Description, action, IP, browser">
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-control">
                        <option value="">All Actions</option>
                        <?php foreach ($actionOptions as $option): ?>
                            <option value="<?= esc($option['action']) ?>" <?= $selectedAction === $option['action'] ? 'selected' : '' ?>>
                                <?= esc($option['action']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= esc($dateFrom ?? '') ?>">
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= esc($dateTo ?? '') ?>">
                </div>

                <div class="col-lg-2 col-md-4">
                    <button type="submit" class="vs-btn vs-btn-primary w-100">Filter</button>
                </div>
            </div>
        </div>
    </form>

    <div class="vs-card">
        <div class="vs-card-body">
            <table id="auditLogsTable" class="vs-datatable js-data-table" data-search-placeholder="Search audit logs..." style="width:100%">
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
                        <tr>
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
</div>

<?= $this->endSection() ?>
