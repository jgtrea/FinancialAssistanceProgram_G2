<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Archive</h1>

    <?php $role = session('role') ?: 'user'; ?>

    <!-- Context Tabs -->
    <ul class="nav nav-tabs mb-3" id="archiveTabs">
        <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($type ?? 'user') === 'user' ? 'active' : '' ?>"
                   href="<?= site_url('archive?type=user') ?>">Users</a>
            </li>
        <?php endif; ?>

        <li class="nav-item">
            <a class="nav-link <?= ($type ?? 'user') === 'voucher' ? 'active' : '' ?>"
               href="<?= site_url('archive?type=voucher') ?>">Students</a>
        </li>
    </ul>

    <?php if (($type ?? 'user') === 'user'): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Archived Users</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Archived At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= esc($user['user_id']) ?></td>
                                    <td><?= esc($user['name']) ?></td>
                                    <td><?= esc($user['email']) ?></td>
                                    <td><?= esc($user['role']) ?></td>
                                    <td><?= esc($user['archived_at']) ?></td>
                                    <td>
                                        <a href="<?= site_url('archive/restore/user/' . $user['user_id']) ?>" class="btn btn-sm btn-success">Restore</a>
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
        </div>

    <?php else: ?>
        <!-- Vouchers / Students Archive Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Archived Vouchers / Students</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Voucher No</th>
                            <th>Student Name</th>
                            <th>School</th>
                            <th>Status</th>
                            <th>Archived At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($vouchers)): ?>
                            <?php foreach ($vouchers as $voucher): ?>
                                <tr>
                                    <td><?= esc($voucher['student_id']) ?></td>
                                    <td><?= esc($voucher['voucher_no']) ?></td>
                                    <td><?= esc($voucher['full_name']) ?></td>
                                    <td><?= esc($voucher['preferred_senior_high_school']) ?></td>
                                    <td><?= esc($voucher['voucher_status']) ?></td>
                                    <td><?= esc($voucher['archived_at']) ?></td>
                                    <td>
                                        <a href="<?= site_url('archive/restore/voucher/' . $voucher['student_id']) ?>" class="btn btn-sm btn-success">Restore</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No archived vouchers/students found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>