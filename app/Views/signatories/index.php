<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <h3>Signatories</h3>
    <a href="<?= base_url('/signatories/form') ?>" class="btn btn-primary">Add Signatory</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Full Name</th>
            <th>Position Title</th>
            <th>Status</th>
            <th width="180">Actions</th>
        </tr>
    </thead>

    <tbody>
        <?php if (!empty($signatories)): ?>
            <?php foreach ($signatories as $signatory): ?>
                <?php
                    $fullName = trim(
                        $signatory['first_name'] . ' ' .
                        ($signatory['middle_name'] ?? '') . ' ' .
                        $signatory['last_name'] . ' ' .
                        ($signatory['suffix'] ?? '')
                    );
                ?>

                <tr>
                    <td><?= esc($fullName) ?></td>
                    <td><?= esc($signatory['position_title']) ?></td>
                    <td><?= $signatory['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <a href="<?= base_url('/signatories/form/' . $signatory['signatory_id']) ?>"
                           class="btn btn-warning btn-sm">
                            Edit
                        </a>

                        <?php if ($signatory['is_active']): ?>
                            <form action="<?= base_url('/signatories/deactivate/' . $signatory['signatory_id']) ?>"
                                  method="post"
                                  style="display:inline-block;">
                                <?= csrf_field() ?>
                                <button class="btn btn-danger btn-sm"
                                        onclick="return confirm('Deactivate this signatory?')">
                                    Deactivate
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" class="text-center">No signatories found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?= $this->endSection() ?>