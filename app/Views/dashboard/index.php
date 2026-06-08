<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
    <div>
        <h4 class="vs-page-title">Dashboard</h4>
        <p class="vs-page-sub">Review Voucher Activity And Recent Student Records.</p>
    </div>
</div>

<div class="vs-stats-grid">
    <div class="vs-stat-card vs-stat-blue">
        <div class="vs-stat-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="M7.5 11.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Zm9-1a3 3 0 1 1 0-6 3 3 0 0 1 0 6ZM2.5 20a5 5 0 0 1 10 0v.5h-10V20Zm11.5.5v-.8a6.3 6.3 0 0 0-1.1-3.6 4.5 4.5 0 0 1 8.6 1.9v2.5H14Z" />
            </svg>
        </div>
        <div>
            <div class="vs-stat-label">Students</div>
            <div class="vs-stat-value"><?= number_format($myVouchers) ?></div>
        </div>
    </div>
    <div class="vs-stat-card vs-stat-green">
        <div class="vs-stat-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v2.1a2.4 2.4 0 0 0 0 4.8v2.1a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-2.1a2.4 2.4 0 0 0 0-4.8V7.5Zm5.5-.3v9.6H12V7.2H9.5Z" />
            </svg>
        </div>
        <div>
            <div class="vs-stat-label">Vouchers Generated</div>
            <div class="vs-stat-value"><?= number_format($generated) ?></div>
        </div>
    </div>
    <div class="vs-stat-card vs-stat-teal">
        <div class="vs-stat-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="M10 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm-7 8a7 7 0 0 1 11.8-5.1l-2.1 2.1-1.7-1.7-1.8 1.8 3.5 3.5 5.8-5.8-1.8-1.8-.1.1A7 7 0 0 1 21 19.6v.4H3Z" />
            </svg>
        </div>
        <div>
            <div class="vs-stat-label">Eligible</div>
            <div class="vs-stat-value"><?= number_format($eligible) ?></div>
        </div>
    </div>
    <div class="vs-stat-card vs-stat-red">
        <div class="vs-stat-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="M10 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm-7 8a7 7 0 0 1 10.3-6.2l-2.1 2.1 1.8 1.8 2.1-2.1 2.1 2.1 1.8-1.8-2.1-2.1A7 7 0 0 1 21 20H3Zm14.7-9.3 1.8 1.8-2.1 2.1 2.1 2.1-1.8 1.8-2.1-2.1-2.1 2.1-1.8-1.8 2.1-2.1-2.1-2.1 1.8-1.8 2.1 2.1 2.1-2.1Z" />
            </svg>
        </div>
        <div>
            <div class="vs-stat-label">Not Eligible</div>
            <div class="vs-stat-value"><?= number_format($notEligible) ?></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="vs-card vs-dashboard-recent-card">
            <div class="vs-card-body">
                <div class="vs-page-header vs-dashboard-table-header mb-3">
                    <div>
                        <h4 class="vs-page-title">Recent Vouchers</h4>
                    </div>
                    <?php $voucherPrefix = session()->get('role') === 'admin' ? 'admin' : 'user'; ?>
                    <a href="<?= site_url($voucherPrefix . '/students') ?>" class="vs-btn vs-btn-outline">See All</a>
                </div>
                <div class="vs-dashboard-table-wrap">
                    <table id="recentVouchersTable" class="vs-datatable vs-dashboard-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Voucher No</th>
                                <th>Student Name</th>
                                <th>Junior High School</th>
                                <th>Status</th>
                                <th>Last Generated</th>
                                <th style="display:none">Name Sort</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentVouchers as $voucher): ?>
                                <?php
                                    $dLn = trim((string) ($voucher['last_name'] ?? ''));
                                    $dFm = implode(' ', array_filter([
                                        trim((string) ($voucher['first_name'] ?? '')),
                                        trim((string) ($voucher['middle_name'] ?? '')),
                                    ]));
                                    $dDn = $dLn !== '' ? $dLn . ($dFm !== '' ? ', ' . $dFm : '') : $dFm;
                                ?>
                                <tr>
                                    <td></td>
                                    <td><?= esc($voucher['voucher_no'] ?: '-') ?></td>
                                    <td class="vs-student-name"><?= esc($dDn ?: 'Unnamed Student') ?></td>
                                    <td><?= esc($voucher['junior_high_school'] ?: '-') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $voucher['voucher_status'] === 'generated' ? 'success' : 'warning' ?>">
                                            <?= esc(ucwords(str_replace('_', ' ', $voucher['voucher_status']))) ?>
                                        </span>
                                    </td>
                                    <td><?= !empty($voucher['generated_at']) ? date('M d, Y', strtotime($voucher['generated_at'])) : '-' ?></td>
                                    <td><?= esc($voucher['name_sort'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var table = document.getElementById('recentVouchersTable');
    if (!table || !window.jQuery || !$.fn.DataTable) return;
    var breakpoint = window.matchMedia ? window.matchMedia('(max-width: 767.98px)') : null;
    var dt = null;
    var currentMode = null;

    function isMobileView() {
        return breakpoint ? breakpoint.matches : window.innerWidth <= 767;
    }

    function mobileDetailsHtml(data) {
        var items = [
            ['Voucher No', data[1] || '-'],
            ['Junior High School', data[3] || '-'],
            ['Status', data[4] || '-'],
            ['Last Generated', data[5] || '-'],
        ];
        return '<div class="vs-dashboard-detail-grid">' + items.map(function (item) {
            return '<div class="vs-dashboard-detail-item">' +
                '<div class="vs-dashboard-detail-label">' + item[0] + '</div>' +
                '<div class="vs-dashboard-detail-value">' + item[1] + '</div>' +
            '</div>';
        }).join('') + '</div>';
    }

    function buildColumnDefs(mobile) {
        var defs = [
            { visible: mobile, orderable: false, targets: 0 },
            { visible: false, targets: [6] },
        ];
        if (mobile) {
            defs.push(
                { className: 'dtr-control', targets: 0 },
                { visible: false, targets: [1, 3, 4, 5] },
                { visible: true, targets: [2] }
            );
        }
        return defs;
    }

    function resetTableDom() {
        table.querySelectorAll('tbody tr.parent').forEach(function (row) {
            row.classList.remove('parent');
        });
        table.querySelectorAll('tbody tr.child').forEach(function (row) {
            row.remove();
        });
        table.querySelectorAll('.dtr-control').forEach(function (cell) {
            cell.classList.remove('dtr-control');
        });
    }

    function initTable() {
        var mobile = isMobileView();
        var mode = mobile ? 'mobile' : 'desktop';
        if (dt && currentMode === mode) return;

        if (dt) {
            dt.rows().every(function () {
                if (this.child && this.child.isShown()) this.child.hide();
            });
            dt.destroy();
            dt = null;
            resetTableDom();
        }

        currentMode = mode;
        dt = $(table).DataTable({
            dom:       "<'row'<'col-sm-12'tr>>",
            paging:    false,
            searching: false,
            info:      false,
            ordering:  true,
            responsive: !mobile,
            autoWidth: false,
            order:     [[5, 'desc']],
            columnDefs: buildColumnDefs(mobile),
        });
    }

    table.addEventListener('click', function (event) {
        if (currentMode !== 'mobile' || !dt) return;
        var control = event.target.closest('td.dtr-control');
        if (!control) return;
        var tr = control.closest('tr');
        var row = dt.row(tr);
        if (!row.length) return;
        if (row.child.isShown()) {
            row.child.hide();
            tr.classList.remove('parent');
            return;
        }
        row.child(mobileDetailsHtml(row.data())).show();
        tr.classList.add('parent');
    });

    initTable();
    if (breakpoint && breakpoint.addEventListener) {
        breakpoint.addEventListener('change', initTable);
    } else if (breakpoint && breakpoint.addListener) {
        breakpoint.addListener(initTable);
    } else {
        window.addEventListener('resize', initTable);
    }
});
</script>

<?= $this->endSection() ?>
