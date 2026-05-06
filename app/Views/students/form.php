<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h3><?= $student ? 'Edit Student' : 'Add Student' ?></h3>

<div id="alertBox"></div>

<form id="studentForm" action="<?= base_url('/students/save') ?>" method="post" data-redirect-url="<?= base_url('/students') ?>">
    <?= csrf_field() ?>

    <input type="hidden" name="student_id" value="<?= $student['student_id'] ?? '' ?>">

    <div class="row">
        <div class="col-md-6 mb-3">
            <label>Voucher No.</label>
            <input type="text" name="voucher_no" class="form-control" value="<?= esc($student['voucher_no'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Date</label>
            <input type="date" name="voucher_date" class="form-control" value="<?= esc($student['voucher_date'] ?? '') ?>">
        </div>

        <div class="col-md-12 mb-3">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" required value="<?= esc($student['full_name'] ?? '') ?>">
        </div>

        <div class="col-md-4 mb-3">
            <label>Rank</label>
            <input type="number" name="rank_no" class="form-control" value="<?= esc($student['rank_no'] ?? '') ?>">
        </div>

        <div class="col-md-4 mb-3">
            <label>GWA</label>
            <input type="number" step="0.01" name="gwa" class="form-control" value="<?= esc($student['gwa'] ?? '') ?>">
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
            <input type="text" name="junior_high_school" class="form-control" value="<?= esc($student['junior_high_school'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Preferred Senior High School</label>
            <input type="text" name="preferred_senior_high_school" class="form-control" value="<?= esc($student['preferred_senior_high_school'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Contact Number</label>
            <input type="text" name="contact_number" class="form-control" value="<?= esc($student['contact_number'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Remarks / Status</label>
            <input type="text" name="remarks_status" class="form-control" value="<?= esc($student['remarks_status'] ?? '') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>School Year</label>
            <input type="text" name="school_year" class="form-control" value="<?= esc($student['school_year'] ?? '2025-2026') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label>Eligibility Status</label>
            <select name="eligibility_status" class="form-control">
                <option value="eligible" <?= (($student['eligibility_status'] ?? '') === 'eligible') ? 'selected' : '' ?>>Eligible</option>
                <option value="not_eligible" <?= (($student['eligibility_status'] ?? '') === 'not_eligible') ? 'selected' : '' ?>>Not Eligible</option>
            </select>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <?= $student ? 'Update Student' : 'Save Student' ?>
    </button>

    <a href="<?= base_url('/students') ?>" class="btn btn-secondary">Back</a>
</form>

<?= $this->endSection() ?>
