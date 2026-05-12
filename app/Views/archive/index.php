<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h3 class="page-title">Student Archive</h3>
</div>

<div class="table-responsive page-table">
    <table class="table table-bordered table-striped mb-0">
        <thead>
            <tr>
                <th>Voucher No.</th>
                <th>Date</th>
                <th>Full Name</th>
                <th>School Year</th>
                <th>Eligibility</th>
                <th>Voucher</th>
                <th>Reason</th>
                <th>Archived At</th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($archives)): ?>
                <?php foreach ($archives as $archive): ?>
                    <?php
                        $fullName = trim(
                            $archive['first_name'] . ' ' .
                            ($archive['middle_name'] ?? '') . ' ' .
                            $archive['last_name'] . ' ' .
                            ($archive['suffix'] ?? '')
                        );
                    ?>

                    <tr>
                        <td><?= esc($archive['voucher_no']) ?></td>
                        <td><?= esc($archive['voucher_date']) ?></td>
                        <td><?= esc($fullName) ?></td>
                        <td><?= esc($archive['school_year']) ?></td>
                        <td><?= esc($archive['eligibility_status']) ?></td>
                        <td><?= esc($archive['voucher_status']) ?></td>
                        <td><?= esc($archive['archive_reason']) ?></td>
                        <td><?= esc($archive['archived_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No archived students found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
