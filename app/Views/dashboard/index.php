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
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Eligible</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $eligible ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Not Eligible</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $notEligible ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
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
                    <table id="recentVouchersTable" class="vs-datatable js-data-table"
                           data-order='[[5,"asc"]]'
                           data-col-defs='[{"orderData":[5],"targets":[1]},{"visible":false,"targets":[5]}]'
                           width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Voucher No</th>
                                <th>Student Name</th>
                                <th>Junior High School</th>
                                <th>Status</th>
                                <th>Last Generated</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentVouchers as $voucher): ?>
                                <?php
                                    $dLn = trim((string) ($voucher['last_name']   ?? ''));
                                    $dFm = implode(' ', array_filter([trim((string) ($voucher['first_name'] ?? '')), trim((string) ($voucher['middle_name'] ?? ''))]));
                                    $dDn = $dLn !== '' ? $dLn . ($dFm !== '' ? ', ' . $dFm : '') : $dFm;
                                ?>
                                <tr>
                                    <td><?= esc($voucher['voucher_no']) ?></td>
                                    <td><?= esc($dDn) ?></td>
                                    <td><?= esc($voucher['junior_high_school'] ?: '-') ?></td>
                                    <td><span class="badge bg-<?= $voucher['voucher_status'] === 'generated' ? 'success' : 'warning' ?>"><?= esc(ucwords(str_replace('_', ' ', $voucher['voucher_status']))) ?></span></td>
                                    <td><?= !empty($voucher['generated_at']) ? date('M d, Y', strtotime($voucher['generated_at'])) : '-' ?></td>
                                    <td><?= esc($voucher['name_sort'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= $this->endSection() ?>
