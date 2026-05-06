<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<h3>Student Archive</h3>

<table class="table table-bordered table-striped mt-3">
    <thead>
        <tr>
            <th>Voucher No.</th>
            <th>Student Name</th>
            <th>Senior High School</th>
            <th>School Year</th>
            <th>Status</th>
            <th>Reason</th>
            <th>Archived At</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($archives)): ?>
            <?php foreach ($archives as $archive): ?>
                <tr>
                    <td><?= esc($archive['voucher_no']) ?></td>
                    <td><?= esc($archive['recipient_name']) ?></td>
                    <td><?= esc($archive['senior_high_school']) ?></td>
                    <td><?= esc($archive['school_year']) ?></td>
                    <td><?= esc($archive['voucher_status']) ?></td>
                    <td><?= esc($archive['archive_reason']) ?></td>
                    <td><?= esc($archive['archived_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="text-center">No archived students found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?= $this->endSection() ?>