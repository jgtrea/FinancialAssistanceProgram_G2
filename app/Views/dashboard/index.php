<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">Dashboard</h4>
            <p class="vs-page-sub">Review voucher activity and recent student records.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $myVouchers ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Vouchers Generated</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($generated) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-pending shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-pending text-uppercase mb-1">Pending</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $pending ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-archived shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-archived text-uppercase mb-1">Archived</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $archived ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-archive fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="vs-card">
                <div class="vs-card-body">
                    <div class="vs-page-header mb-3">
                        <div>
                            <h4 class="vs-page-title">Recent Vouchers</h4>
                        </div>
                        <?php $voucherPrefix = session()->get('role') === 'admin' ? 'admin' : 'user'; ?>
                        <a href="<?= site_url($voucherPrefix . '/students') ?>" class="vs-btn vs-btn-outline">See All</a>
                    </div>
                        <table id="recentVouchersTable" class="vs-datatable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Voucher No</th>
                                    <th>Student Name</th>
                                    <th>Junior High School</th>
                                    <th>Status</th>
                                    <th>Last Generated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentVouchers as $voucher): ?>
                                    <tr>
                                        <td><?= esc($voucher['voucher_no']) ?></td>
                                        <td><?= esc($voucher['full_name']) ?></td>
                                        <td><?= esc($voucher['junior_high_school'] ?: '-') ?></td>
                                        <td><span class="badge bg-<?= $voucher['voucher_status'] === 'generated' ? 'success' : 'warning' ?>"><?= esc(ucwords(str_replace('_', ' ', $voucher['voucher_status']))) ?></span></td>
                                        <td><?= !empty($voucher['generated_at']) ? date('M d, Y', strtotime($voucher['generated_at'])) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

<?= $this->endSection() ?>
