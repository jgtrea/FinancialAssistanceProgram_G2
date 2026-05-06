<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<h3>Generate Voucher</h3>

<form action="<?= base_url('/vouchers/store') ?>" method="post">
    <?= csrf_field() ?>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label>Voucher No.</label>
            <input type="text" name="voucher_no" class="form-control" value="<?= esc($student['voucher_no']) ?>" required>
        </div>

        <div class="col-md-6 mb-3">
            <label>Voucher Date</label>
            <input type="date" name="voucher_date" class="form-control" value="<?= esc($student['voucher_date']) ?>" required>
        </div>

        <div class="col-md-12 mb-3">
            <label>Recipient Name</label>
            <input type="text" name="recipient_name" class="form-control" value="<?= esc($student['full_name']) ?>" required>
        </div>

        <div class="col-md-12 mb-3">
            <label>Senior High School</label>
            <input type="text" name="senior_high_school" class="form-control" value="<?= esc($student['preferred_senior_high_school']) ?>" required>
        </div>

        <div class="col-md-6 mb-3">
            <label>Amount in Words</label>
            <input type="text" class="form-control" value="TEN THOUSAND PESOS ONLY" readonly>
        </div>

        <div class="col-md-6 mb-3">
            <label>Amount</label>
            <input type="text" class="form-control" value="PHP 10,000.00" readonly>
        </div>

        <div class="col-md-6 mb-3">
            <label>School Year</label>
            <input type="text" name="school_year" class="form-control" value="<?= esc($student['school_year']) ?>" required>
        </div>

        <div class="col-md-4 mb-3">
            <label>Signatory 1</label>
            <select name="signatory_1_id" class="form-control" required>
                <option value="">Select Signatory</option>
                <?php foreach ($signatories as $signatory): ?>
                    <option value="<?= $signatory['signatory_id'] ?>">
                        <?= esc($signatory['full_name']) ?> - <?= esc($signatory['position_title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4 mb-3">
            <label>Signatory 2</label>
            <select name="signatory_2_id" class="form-control" required>
                <option value="">Select Signatory</option>
                <?php foreach ($signatories as $signatory): ?>
                    <option value="<?= $signatory['signatory_id'] ?>">
                        <?= esc($signatory['full_name']) ?> - <?= esc($signatory['position_title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4 mb-3">
            <label>Signatory 3</label>
            <select name="signatory_3_id" class="form-control" required>
                <option value="">Select Signatory</option>
                <?php foreach ($signatories as $signatory): ?>
                    <option value="<?= $signatory['signatory_id'] ?>">
                        <?= esc($signatory['full_name']) ?> - <?= esc($signatory['position_title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <button class="btn btn-success">Generate Voucher</button>
    <a href="<?= base_url('/students') ?>" class="btn btn-secondary">Cancel</a>
</form>

<?= $this->endSection() ?>