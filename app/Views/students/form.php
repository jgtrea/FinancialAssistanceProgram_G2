<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<h3><?= $student ? 'Edit Student' : 'Add Student' ?></h3>

<div class="mb-3">
    <button type="button" class="btn btn-success">
        Import Excel
    </button>
</div>

<div id="alertBox"></div>

<form id="studentForm">
    <?= csrf_field() ?>

    <input type="hidden" name="student_id" value="<?= esc($student['student_id'] ?? '') ?>">

    <div class="row">
        <div class="col-md-6 mb-3">
            <label>Voucher No.</label>
            <input type="text" name="voucher_no" class="form-control"
                   value="<?= esc($student['voucher_no'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Voucher Date</label>
            <input type="date" name="voucher_date" class="form-control"
                   value="<?= esc($student['voucher_date'] ?? '') ?>">
        </div>

        <div class="col-md-3 mb-3">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" required
                   value="<?= esc($student['first_name'] ?? '') ?>">
        </div>

        <div class="col-md-3 mb-3">
            <label>Middle Name</label>
            <input type="text" name="middle_name" class="form-control"
                   value="<?= esc($student['middle_name'] ?? '') ?>">
        </div>

        <div class="col-md-3 mb-3">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" required
                   value="<?= esc($student['last_name'] ?? '') ?>">
        </div>

        <div class="col-md-3 mb-3">
            <label>Suffix</label>
            <input type="text" name="suffix" class="form-control"
                   value="<?= esc($student['suffix'] ?? '') ?>">
        </div>

        <div class="col-md-4 mb-3">
            <label>Rank</label>
            <input type="number" name="rank_no" class="form-control"
                   value="<?= esc($student['rank_no'] ?? '') ?>">
        </div>

        <div class="col-md-4 mb-3">
            <label>GWA</label>
            <input type="number" step="0.01" name="gwa" class="form-control"
                   value="<?= esc($student['gwa'] ?? '') ?>">
        </div>

        <div class="col-md-4 mb-3">
            <label>Gender</label>
            <select name="gender" class="form-control">
                <option value="">Select Gender</option>
                <option value="Male" <?= (($student['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= (($student['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Junior High School</label>
            <input type="text" name="junior_high_school" class="form-control"
                   value="<?= esc($student['junior_high_school'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Preferred Senior High School</label>
            <input type="text" name="preferred_senior_high_school" class="form-control"
                   value="<?= esc($student['preferred_senior_high_school'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Contact Number</label>
            <input type="text" name="contact_number" class="form-control"
                   value="<?= esc($student['contact_number'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Remarks / Status</label>
            <input type="text" name="remarks_status" class="form-control"
                   value="<?= esc($student['remarks_status'] ?? '') ?>">
        </div>

        <div class="col-md-4 mb-3">
            <label>School Year</label>
            <input type="text" name="school_year" class="form-control"
                   value="<?= esc($student['school_year'] ?? '2025-2026') ?>">
        </div>

        <div class="col-md-4 mb-3">
            <label>Eligibility Status</label>
            <select name="eligibility_status" class="form-control">
                <option value="eligible" <?= (($student['eligibility_status'] ?? '') === 'eligible') ? 'selected' : '' ?>>Eligible</option>
                <option value="not_eligible" <?= (($student['eligibility_status'] ?? '') === 'not_eligible') ? 'selected' : '' ?>>Not Eligible</option>
            </select>
        </div>

        <div class="col-md-4 mb-3">
            <label>Voucher Status</label>
            <select name="voucher_status" class="form-control">
                <option value="not_generated" <?= (($student['voucher_status'] ?? '') === 'not_generated') ? 'selected' : '' ?>>Not Generated</option>
                <option value="generated" <?= (($student['voucher_status'] ?? '') === 'generated') ? 'selected' : '' ?>>Generated</option>
            </select>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <?= $student ? 'Update Student' : 'Save Student' ?>
    </button>

    <a href="<?= base_url('/students') ?>" class="btn btn-secondary">Back</a>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
$('#studentForm').on('submit', function(e) {
    e.preventDefault();

    $('#alertBox').html('');

    $.ajax({
        url: "<?= base_url('/students/save') ?>",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",

        success: function(response) {
            if (response.status === 'success') {
                window.location.href = "<?= base_url('/students') ?>";
            } else {
                $('#alertBox').html(
                    '<div class="alert alert-danger">' + response.message + '</div>'
                );
            }
        },

        error: function(xhr) {
            console.log(xhr.responseText);

            $('#alertBox').html(
                '<div class="alert alert-danger">Something went wrong. Check console.</div>'
            );
        }
    });
});
</script>

<?= $this->endSection() ?>