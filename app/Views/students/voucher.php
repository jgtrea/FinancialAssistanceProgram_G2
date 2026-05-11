<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$fullName = trim(
    $student['first_name'] . ' ' .
    ($student['middle_name'] ?? '') . ' ' .
    $student['last_name'] . ' ' .
    ($student['suffix'] ?? '')
);
?>

<div class="d-flex justify-content-between mb-3">
    <h3>Voucher Preview</h3>

    <div>
        <button class="btn btn-success" id="markGeneratedBtn">
            Mark as Generated
        </button>

        <button class="btn btn-secondary" onclick="window.print()">
            Print
        </button>

        <a href="<?= base_url('/students') ?>" class="btn btn-dark">
            Back
        </a>
    </div>
</div>

<div class="card p-4">
    <h4 class="text-center">BIÑAN CITY GRANTS AND SCHOLARSHIP PROGRAMS</h4>
    <h2 class="text-center">FINANCIAL ASSISTANCE PROGRAM</h2>
    <p class="text-center">CITY ORDINANCE NO. 08-(2022)</p>

    <hr>

    <div class="row mb-3">
        <div class="col-md-6">
            <strong>Voucher No.:</strong> <?= esc($student['voucher_no']) ?>
        </div>
        <div class="col-md-6">
            <strong>Date:</strong> <?= esc($student['voucher_date']) ?>
        </div>
    </div>

    <p><strong>Name of Recipient:</strong> <?= esc($fullName) ?></p>
    <p><strong>Senior High School:</strong> <?= esc($student['preferred_senior_high_school']) ?></p>
    <p><strong>Amount in words:</strong> TEN THOUSAND PESOS ONLY</p>
    <p><strong>Amount:</strong> PHP 10,000.00</p>

    <div class="row text-center mt-5">
        <?php foreach ($signatories as $signatory): ?>
            <?php
            $signatoryName = trim(
                $signatory['first_name'] . ' ' .
                ($signatory['middle_name'] ?? '') . ' ' .
                $signatory['last_name'] . ' ' .
                ($signatory['suffix'] ?? '')
            );
            ?>
            <div class="col-md-4">
                <div style="border-top: 1px solid #000; padding-top: 5px;">
                    <strong><?= esc($signatoryName) ?></strong><br>
                    <small><?= esc($signatory['position_title']) ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
$('#markGeneratedBtn').on('click', function () {
    if (!confirm('Mark this voucher as generated?')) {
        return;
    }

    let csrfName = $('meta[name="csrf-token-name"]').attr('content');
    let csrfValue = $('meta[name="csrf-token-value"]').attr('content');

    let postData = {};
    postData[csrfName] = csrfValue;

    $.ajax({
        url: "<?= base_url('/students/mark-generated/' . $student['student_id']) ?>",
        type: "POST",
        data: postData,
        dataType: "json",
        success: function(response) {
            alert(response.message);

            if (response.status === 'success') {
                window.location.href = "<?= base_url('/students') ?>";
            }
        },
        error: function(xhr) {
            console.log(xhr.responseText);
            alert('Failed to update voucher status.');
        }
    });
});
</script>

<?= $this->endSection() ?>