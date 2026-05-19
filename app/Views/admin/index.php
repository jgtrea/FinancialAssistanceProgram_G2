<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-actions mb-3 d-flex justify-content-between align-items-center">
    <h3>User Management</h3>
    <a href="<?= base_url('admin/user_management/form') ?>" class="btn btn-primary">Add New User</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <table id="userManagementTable" class="table table-bordered table-striped align-middle js-data-table" data-search-placeholder="Search users..." style="width:100%">
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Last Login</th>
                <th class="actions-column">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= esc($user['full_name']) ?></td>
                    <td><?= esc($user['username']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= esc($user['role']) ?></span></td>
                    <td><?= !empty($user['last_login']) ? esc(date('M d, Y h:i A', strtotime($user['last_login']))) : 'Never' ?></td>
                    <td class="actions-cell">
                        <a href="<?= base_url('admin/user_management/form/' . $user['user_id']) ?>" class="btn btn-warning btn-sm">
                            Edit
                        </a>
                        <button class="btn btn-danger btn-sm archiveUserBtn"
                                data-archive-url="<?= base_url('admin/user_management/archive/' . $user['user_id']) ?>">
                            Archive
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
