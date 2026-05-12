<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h3 class="page-title">User Archive</h3>
</div>

<div class="table-responsive page-table">
    <table class="table table-bordered table-striped mb-0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Last Login</th>
                <th>Actions</th>
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
                        <td>
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