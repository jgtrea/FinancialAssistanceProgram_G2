<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid px-4 py-4">
    <div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">Archive</h4>
            <p class="vs-page-sub">Review and restore archived records.</p>
        </div>
    </div>

    <?php $role = session('role') ?: 'user'; ?>

    <!-- Context Tabs -->
    <ul class="nav nav-tabs mb-4" id="archiveTabs">
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

        <li class="nav-item">
            <a class="nav-link <?= ($type ?? 'user') === 'signatory' ? 'active' : '' ?>"
               href="<?= site_url('archive?type=signatory') ?>">Signatories</a>
        </li>
    </ul>

    <?php if (($type ?? 'user') === 'user'): ?>
        <div class="vs-card">
            <div class="vs-card-body">
                <h4 class="vs-page-title mb-3">Archived Users</h4>
                <table id="archivedUsersTable" class="vs-datatable js-data-table" data-search-placeholder="Search archived users..." style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Archived At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= esc($user['name'] ?? $user['full_name'] ?? '') ?></td>
                                <td><?= esc($user['email'] ?? $user['username'] ?? '') ?></td>
                                <td><?= esc($user['role']) ?></td>
                                <td><?= !empty($user['archived_at']) ? esc(date('M d, Y h:i A', strtotime($user['archived_at']))) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif (($type ?? 'user') === 'signatory'): ?>
        <div class="vs-card">
            <div class="vs-card-body">
                <h4 class="vs-page-title mb-3">Archived Signatories</h4>
                <table id="archivedSignatoriesTable" class="vs-datatable js-data-table" data-search-placeholder="Search archived signatories..." style="width:100%">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Position Title</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($signatories as $signatory): ?>
                            <?php
                                $fullName = trim(
                                    ($signatory['first_name'] ?? '') . ' ' .
                                    ($signatory['middle_name'] ?? '') . ' ' .
                                    ($signatory['last_name'] ?? '') . ' ' .
                                    ($signatory['suffix'] ?? '')
                                );
                            ?>
                            <tr>
                                <td><?= esc($fullName) ?></td>
                                <td><?= esc($signatory['position_title']) ?></td>
                                <td>Inactive</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- Vouchers / Students Archive Table -->
        <div class="vs-card">
            <div class="vs-card-body">
                <h4 class="vs-page-title mb-3">Archived Vouchers / Students</h4>
                <table id="archivedVouchersTable" class="vs-datatable js-data-table" data-search-placeholder="Search archived students..." style="width:100%">
                    <thead>
                        <tr>
                            <th>Voucher No</th>
                            <th>Student Name</th>
                            <th>School</th>
                            <th>Status</th>
                            <th>Archived At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vouchers as $voucher): ?>
                            <tr>
                                <td><?= esc($voucher['voucher_no'] ?: '-') ?></td>
                                <td><?= esc($voucher['full_name']) ?></td>
                                <td><?= esc($voucher['preferred_senior_high_school']) ?></td>
                                <td><?= esc($voucher['voucher_status']) ?></td>
                                <td><?= !empty($voucher['archived_at']) ? esc(date('M d, Y h:i A', strtotime($voucher['archived_at']))) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
