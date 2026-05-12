<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-actions mb-3 d-flex justify-content-between align-items-center">
    <h3>Archived Users</h3>
    <a href="<?= base_url('admin/user_management') ?>" class="btn btn-secondary">Back to Users</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Last Login</th>
                <th class="actions-column">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= esc($user['user_id']) ?></td>
                        <td><?= esc($user['full_name']) ?></td>
                        <td><?= esc($user['username']) ?></td>
                        <td><span class="badge bg-info text-dark"><?= esc($user['role']) ?></span></td>
                        <td><?= $user['last_login'] ?? 'Never' ?></td>
                        <td class="actions-cell">
                            <button class="btn btn-success btn-sm restoreUserBtn"
                                    data-restore-url="<?= base_url('admin/user_management/restore/' . $user['user_id']) ?>">
                                Restore
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No archived users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>