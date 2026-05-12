<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h3 class="page-title">Students</h3>

    <div class="page-actions">
        <a href="<?= base_url('/students/form') ?>" class="btn btn-primary">Add Student</a>

        <button type="button" class="btn btn-success">
            Import Excel
        </button>
    </div>
</div>

<div class="table-responsive page-table">
    <table class="table table-bordered table-striped mb-0">
        <thead>
            <tr>
                <th>Voucher No.</th>
                <th>Date</th>
                <th>Full Name</th>
                <th>Rank</th>
                <th>GWA</th>
                <th>Gender</th>
                <th>Preferred SHS</th>
                <th>Eligibility</th>
                <th>Voucher</th>
                <th>Contact No.</th>
                <th class="actions-column">Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($students)): ?>
                <?php foreach ($students as $student): ?>
                    <?php
                        $fullName = trim(
                            $student['first_name'] . ' ' .
                            ($student['middle_name'] ?? '') . ' ' .
                            $student['last_name'] . ' ' .
                            ($student['suffix'] ?? '')
                        );
                    ?>

                    <tr>
                        <td><?= esc($student['voucher_no']) ?></td>
                        <td><?= esc($student['voucher_date']) ?></td>
                        <td><?= esc($fullName) ?></td>
                        <td><?= esc($student['rank_no']) ?></td>
                        <td><?= esc($student['gwa']) ?></td>
                        <td><?= esc($student['gender']) ?></td>
                        <td><?= esc($student['preferred_senior_high_school']) ?></td>
                        <td><?= esc($student['eligibility_status']) ?></td>
                        <td><?= esc($student['voucher_status']) ?></td>
                        <td><?= esc($student['contact_number']) ?></td>
                        <td class="actions-cell">
                            <a href="<?= base_url('/students/form/' . $student['student_id']) ?>"
                               class="btn btn-warning btn-sm">
                                Edit
                            </a>

                            <a href="<?= base_url('/students/voucher/' . $student['student_id']) ?>"
                               class="btn btn-success btn-sm">
                                Voucher
                            </a>

                            <button class="btn btn-danger btn-sm archiveBtn"
                                    data-id="<?= $student['student_id'] ?>">
                                Archive
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" class="text-center">No students found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
$(document).on('click', '.archiveBtn', function () {
    let studentId = $(this).data('id');

    if (!confirm('Archive this student?')) {
        return;
    }

    let csrfName = $('meta[name="csrf-token-name"]').attr('content');
    let csrfValue = $('meta[name="csrf-token-value"]').attr('content');

    let postData = {};
    postData[csrfName] = csrfValue;

    $.ajax({
        url: "<?= base_url('/students/archive/') ?>" + studentId,
        type: "POST",
        data: postData,
        dataType: "json",
        success: function (response) {
            alert(response.message);

            if (response.status === 'success') {
                location.reload();
            }
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            alert('Failed to archive student. Check console.');
        }
    });
});
</script>

<?= $this->endSection() ?>
