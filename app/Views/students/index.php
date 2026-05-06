    <?= $this->extend('layouts/main') ?>

    <?= $this->section('content') ?>

    <div class="page-actions mb-3">
        <h3>Students</h3>
        <a href="<?= base_url('/students/form') ?>" class="btn btn-primary">Add Student</a>
        <button type="button" class="btn btn-success">
            Import Excel
        </button>
        <!--==================importing HERE==============-->
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
    <table class="table table-bordered table-striped align-middle">
        <thead>
            <tr>
                <th>Voucher No.</th>
                <th>Date</th>
                <th>Full Name</th>
                <th>Rank</th>
                <th>GWA</th>
                <th>Gender</th>
                <th>Preferred SHS</th>
                <th>Status</th>
                <th class="actions-column">Actions</th>
            </tr>
        </thead>
    <tbody>

    <?php if (!empty($students)): ?>

        <?php foreach ($students as $student): ?>

            <tr>
                <td><?= esc($student['voucher_no']) ?></td>
                <td><?= esc($student['voucher_date']) ?></td>
                <td><?= esc($student['full_name']) ?></td>
                <td><?= esc($student['rank_no']) ?></td>
                <td><?= esc($student['gwa']) ?></td>
                <td><?= esc($student['gender']) ?></td>
                <td><?= esc($student['preferred_senior_high_school']) ?></td>
                <td><?= esc($student['eligibility_status']) ?></td>

                <td class="actions-cell">
                    <a href="<?= base_url('/students/form/' . $student['student_id']) ?>"
                    class="btn btn-warning btn-sm">
                        Edit
                    </a>
                    <a href="<?= base_url('/vouchers/create/' . $student['student_id']) ?>" class="btn btn-success btn-sm">
                        Generate
                    </a>
                    <button class="btn btn-danger btn-sm deleteBtn"
                            data-delete-url="<?= base_url('/students/delete/' . $student['student_id']) ?>">
                        Archive
                    </button>
                </td>
            </tr>

        <?php endforeach; ?>

    <?php else: ?>

        <tr>
            <td colspan="9" class="text-center">No students found.</td>
        </tr>

    <?php endif; ?>

    </tbody>
    </table>
    </div>
    <?= $this->endSection() ?>
