<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid px-4 py-4">
    <div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">Signatories</h4>
            <p class="vs-page-sub">Manage active voucher signatories.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('/signatories/form') ?>" class="vs-btn vs-btn-primary">
                <?= asset_icon('add', ['stroke-width' => '2.5']) ?>
                Add Signatory
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="vs-alert vs-alert-success mb-3">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <div class="vs-card">
        <div class="vs-card-body">
            <table id="signatoriesTable" class="vs-datatable js-data-table" data-search-placeholder="Search signatories..." style="width:100%">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Position Title</th>
                    <th>Signature</th>
                    <th>Status</th>
                    <th class="actions-column actions-column--sm">Actions</th>
                </tr>
            </thead>

            <tbody>
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
                        <td>
                            <?php if (!empty($signatory['signature_image'])): ?>
                                <img src="<?= base_url('signatories/signature/' . $signatory['signatory_id']) ?>"
                                     alt="Signature of <?= esc($fullName) ?>"
                                     style="max-height: 40px; max-width: 140px;">
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $signatory['is_active'] ? 'Active' : 'Inactive' ?></td>
                        <td class="actions-cell">
                            <a href="<?= base_url('/signatories/form/' . $signatory['signatory_id']) ?>"
                               class="vs-tbl-btn vs-tbl-btn-edit">
                                Edit
                            </a>

                            <?php if ($signatory['is_active']): ?>
                                <form action="<?= base_url('/signatories/deactivate/' . $signatory['signatory_id']) ?>"
                                      method="post"
                                      class="inline-form">
                                    <?= csrf_field() ?>
                                    <button class="vs-tbl-btn vs-tbl-btn-delete"
                                            onclick="return confirm('Archive this signatory?')">
                                        Archive
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
