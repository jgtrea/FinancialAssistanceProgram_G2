<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<h3>Vouchers</h3>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<table class="table table-bordered table-striped mt-3">
    <thead>
        <tr>
            <th>Voucher No.</th>
            <th>Date</th>
            <th>Recipient</th>
            <th>Senior High School</th>
            <th>Amount</th>
            <th>School Year</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($vouchers)): ?>
            <?php foreach ($vouchers as $voucher): ?>
                <tr>
                    <td><?= esc($voucher['voucher_no']) ?></td>
                    <td><?= esc($voucher['voucher_date']) ?></td>
                    <td><?= esc($voucher['recipient_name']) ?></td>
                    <td><?= esc($voucher['senior_high_school']) ?></td>
                    <td>₱<?= number_format($voucher['amount'], 2) ?></td>
                    <td><?= esc($voucher['school_year']) ?></td>
                    <td><?= esc($voucher['voucher_status']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="text-center">No vouchers found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?= $this->endSection() ?>