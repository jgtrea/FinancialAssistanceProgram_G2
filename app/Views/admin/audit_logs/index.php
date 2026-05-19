<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Audit Logs</h3>
    <a href="<?= base_url($resetUrl ?? 'admin/audit-logs') ?>" class="btn btn-secondary btn-sm">Reset</a>
</div>

<form method="get" action="<?= base_url('/admin/audit-logs') ?>" class="border rounded p-3 mb-4">
    <div class="row align-items-end">
        <div class="col-md-4 mb-3">
            <label class="form-label">Search</label>
            <input type="text" name="q" class="form-control" value="<?= esc($keyword) ?>" placeholder="Description, action, IP, browser">
        </div>

        <div class="col-md-3 mb-3">
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

        <div class="col-md-2 mb-3">
            <label class="form-label">From</label>
            <input type="date" name="date_from" class="form-control" value="<?= esc($dateFrom ?? '') ?>">
        </div>

        <div class="col-md-2 mb-3">
            <label class="form-label">To</label>
            <input type="date" name="date_to" class="form-control" value="<?= esc($dateTo ?? '') ?>">
        </div>

        <div class="col-md-2 mb-3">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table id="adminAuditLogsTable" class="table table-bordered table-striped align-middle js-data-table" data-search-placeholder="Search audit logs..." style="width:100%">
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

<?= $this->endSection() ?>
