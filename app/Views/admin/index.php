<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid px-4 py-4">
    <div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">User Management</h4>
            <p class="vs-page-sub">Manage staff accounts and system access.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/user_management/form') ?>" class="vs-btn vs-btn-primary">
                <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
                Add New User
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="vs-alert vs-alert-success mb-3">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="vs-alert vs-alert-error mb-3">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div class="vs-card">
        <div class="vs-card-body">
            <table id="userManagementTable" class="vs-datatable js-data-table" data-search-placeholder="Search users..." style="width:100%">
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
                            <a href="<?= base_url('admin/user_management/form/' . $user['user_id']) ?>" class="vs-tbl-btn vs-tbl-btn-edit">
                                Edit
                            </a>
                            <button class="vs-tbl-btn vs-tbl-btn-delete archiveUserBtn"
                                    data-archive-url="<?= base_url('admin/user_management/archive/' . $user['user_id']) ?>">
                                Archive
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
