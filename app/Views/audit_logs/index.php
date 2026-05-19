<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h3 class="page-title">Audit Logs</h3>

    <div class="page-actions">
        <a href="<?= base_url($resetUrl ?? 'user/audit-logs') ?>" class="btn btn-secondary btn-sm">Reset</a>
    </div>
</div>

<form method="get" action="<?= base_url('/user/audit-logs') ?>" class="filter-panel">
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

<div class="table-responsive page-table">
    <table id="auditLogsTable" class="table table-bordered table-striped align-middle js-data-table" data-search-placeholder="Search audit logs..." style="width:100%">
        <thead>
            <tr>
                <th style="width: 80px;">ID</th>
                <th style="width: 170px;">Date/Time</th>
                <th style="width: 180px;">User</th>
                <th style="width: 130px;">Student ID</th>
                <th style="width: 130px;">Voucher ID</th>
                <th style="width: 170px;">Action</th>
                <th>Description</th>
                <th style="width: 150px;">IP Address</th>
                <th>User Agent</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= esc($log['audit_id']) ?></td>
                    <td><?= esc($log['created_at'] ?? '-') ?></td>
                    <td>
                        <?= esc($log['full_name'] ?? $log['username'] ?? '-') ?>
                        <?php if (!empty($log['user_id'])): ?>
                            <span class="text-muted small">#<?= esc($log['user_id']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($log['student_id'] ?? '-') ?></td>
                    <td><?= esc($log['voucher_id'] ?? '-') ?></td>
                    <td><span class="badge text-bg-dark"><?= esc($log['action']) ?></span></td>
                    <td><?= esc($log['description']) ?></td>
                    <td><?= esc($log['ip_address']) ?></td>
                    <td class="small"><?= esc($log['user_agent']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
