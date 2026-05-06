    <?= $this->extend('layouts/main') ?>

    <?= $this->section('content') ?>

    <div class="page-actions mb-3">
        <h3>Students</h3>
        <a href="<?= base_url('/students/form') ?>" class="btn btn-primary">Add Student</a>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
            Import Excel
        </button>

        <!-- Import Modal -->
        <div class="modal fade" id="importModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Import Excel File</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">

                        <?php if (session()->getFlashdata('error')): ?>
                            <div class="alert alert-danger">
                                ❌ <?= session()->getFlashdata('error') ?>
                            </div>
                        <?php endif; ?>

                        <p class="text-muted small">Expected columns: <code>voucher_no, voucher_date, full_name, rank_no, gwa, gender, junior_high_school, preferred_shs, contact_number, remarks</code></p>

                        <form action="<?= base_url('import_data') ?>" method="POST" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Excel File (.xlsx, .xls)</label>
                                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Import</button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
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
