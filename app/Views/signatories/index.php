<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h3 class="page-title">Signatories</h3>

    <div class="page-actions">
        <a href="<?= base_url('/signatories/form') ?>" class="btn btn-primary">Add Signatory</a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<div class="table-responsive page-table">
    <table class="table table-bordered table-striped mb-0">
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Position Title</th>
                <th>Status</th>
                <th class="actions-column actions-column--sm">Actions</th>
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
                        <td class="actions-cell">
                            <a href="<?= base_url('/signatories/form/' . $signatory['signatory_id']) ?>"
                               class="btn btn-warning btn-sm">
                                Edit
                            </a>

                            <?php if ($signatory['is_active']): ?>
                                <form action="<?= base_url('/signatories/deactivate/' . $signatory['signatory_id']) ?>"
                                      method="post"
                                      class="inline-form">
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
</div>

<?= $this->endSection() ?>
