<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">Archive</h4>
            <p class="vs-page-sub">Review and restore archived records.</p>
        </div>
    </div>

    <?php $role = session('role') ?: 'guest'; ?>

    <!-- Context Tabs -->
    <ul class="nav nav-tabs mb-4" id="archiveTabs">
        <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($type ?? '') === 'user' ? 'active' : '' ?>"
                   href="<?= site_url('archive?type=user') ?>">Users</a>
            </li>
        <?php endif; ?>

        <li class="nav-item">
            <a class="nav-link <?= ($type ?? '') === 'voucher' ? 'active' : '' ?>"
               href="<?= site_url('archive?type=voucher') ?>">Vouchers</a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= ($type ?? '') === 'signatory' ? 'active' : '' ?>"
               href="<?= site_url('archive?type=signatory') ?>">Signatories</a>
        </li>
    </ul>

    <?php if (($type ?? 'user') === 'user'): ?>
        <div class="vs-card">
            <div class="vs-card-body">
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                    <input type="text" id="customArchiveSearch" class="vs-input" placeholder="Search archived users..." style="max-width:340px">
                    <button type="button" class="vs-btn vs-btn-outline" id="btnOpenArchiveFilter">
                        Filters
                        <span id="archiveFilterBadge" class="badge bg-primary" style="display:none;margin-left:.35rem"></span>
                    </button>
                    <label class="vs-length-label ms-auto">Show <input type="number" id="archiveLengthInput" class="vs-length-input" value="10" min="1" max="500"> entries</label>
                </div>
                <table id="archivedUsersTable" class="vs-datatable js-data-table" data-search-placeholder="Search archived users..." style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Archived At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr data-archived-date="<?= !empty($user['updated_at']) ? esc(date('Y-m-d', strtotime($user['updated_at']))) : '' ?>">
                                <td><?= esc($user['username'] ?? '') ?></td>
                                <td><?= esc($user['email'] ?? '') ?></td>
                                <td><?= esc(ucfirst($user['role'])) ?></td>
                                <td><?= !empty($user['updated_at']) ? esc(date('M d, Y h:i A', strtotime($user['updated_at']))) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif (($type ?? 'user') === 'signatory'): ?>
        <div class="vs-card">
            <div class="vs-card-body">
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                    <input type="text" id="customArchiveSearch" class="vs-input" placeholder="Search archived signatories..." style="max-width:340px">
                    <button type="button" class="vs-btn vs-btn-outline" id="btnOpenArchiveFilter">
                        Filters
                        <span id="archiveFilterBadge" class="badge bg-primary" style="display:none;margin-left:.35rem"></span>
                    </button>
                    <label class="vs-length-label ms-auto">Show <input type="number" id="archiveLengthInput" class="vs-length-input" value="10" min="1" max="500"> entries</label>
                </div>
                <table id="archivedSignatoriesTable" class="vs-datatable js-data-table" data-search-placeholder="Search archived signatories..." style="width:100%">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Position Title</th>
                            <th>Archived At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($signatories as $signatory): ?>
                            <?php
                                $fullName = trim(
                                    ($signatory['first_name'] ?? '') . ' ' .
                                    ($signatory['middle_name'] ?? '') . ' ' .
                                    ($signatory['last_name'] ?? '') . ' ' .
                                    ($signatory['suffix'] ?? '')
                                );
                            ?>
                            <tr data-archived-date="<?= !empty($signatory['updated_at']) ? esc(date('Y-m-d', strtotime($signatory['updated_at']))) : '' ?>">
                                <td><?= esc($fullName) ?></td>
                                <td><?= esc($signatory['position_title']) ?></td>
                                <td><?= !empty($signatory['updated_at']) ? esc(date('M d, Y h:i A', strtotime($signatory['updated_at']))) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- Vouchers / Students Archive Table -->
        <div class="vs-card">
            <div class="vs-card-body">
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                    <input type="text" id="customArchiveSearch" class="vs-input" placeholder="Search archived vouchers..." style="max-width:340px">
                    <button type="button" class="vs-btn vs-btn-outline" id="btnOpenArchiveFilter">
                        Filters
                        <span id="archiveFilterBadge" class="badge bg-primary" style="display:none;margin-left:.35rem"></span>
                    </button>
                    <label class="vs-length-label ms-auto">Show <input type="number" id="archiveLengthInput" class="vs-length-input" value="10" min="1" max="500"> entries</label>
                </div>
                <table id="archivedVouchersTable" class="vs-datatable js-data-table" data-search-placeholder="Search archived vouchers..." style="width:100%">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>School</th>
                            <th>Archived At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vouchers as $voucher): ?>
                            <tr data-archived-date="<?= !empty($voucher['archived_at']) ? esc(date('Y-m-d', strtotime($voucher['archived_at']))) : '' ?>">
                                <td><?= esc($voucher['full_name']) ?></td>
                                <td><?= esc($voucher['preferred_senior_high_school']) ?></td>
                                <td><?= !empty($voucher['archived_at']) ? esc(date('M d, Y h:i A', strtotime($voucher['archived_at']))) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<!-- Archive filter modals — rendered only when the matching tab is active -->
<?php if (($type ?? 'user') === 'user'): ?>
<div class="vs-modal-overlay" id="archiveFilterModal" style="display:none">
  <div class="vs-modal" style="max-width:400px">
    <div class="vs-modal-header">
      <h5>Filter Archived Users</h5>
      <button class="vs-modal-close" id="archiveFilterClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <div class="vs-form-grid vs-form-grid-2">
        <div class="vs-span-2">
          <label class="vs-label" for="afRole">Role</label>
          <select id="afRole" class="vs-input">
            <option value="">All</option>
            <option value="Admin">Admin</option>
            <option value="User">User</option>
          </select>
        </div>
        <div>
          <label class="vs-label" for="afDateFrom">Archived From</label>
          <input type="date" id="afDateFrom" class="vs-input">
        </div>
        <div>
          <label class="vs-label" for="afDateTo">Archived To</label>
          <input type="date" id="afDateTo" class="vs-input">
        </div>
      </div>
    </div>
    <div class="vs-modal-footer">
      <button type="button" class="vs-btn vs-btn-outline" id="archiveFilterClear">Clear All</button>
      <button type="button" class="vs-btn vs-btn-outline" id="archiveFilterCancel">Cancel</button>
      <button type="button" class="vs-btn vs-btn-primary" id="archiveFilterApply">Apply</button>
    </div>
  </div>
</div>
<?php elseif (($type ?? 'user') === 'signatory'): ?>
<div class="vs-modal-overlay" id="archiveFilterModal" style="display:none">
  <div class="vs-modal" style="max-width:400px">
    <div class="vs-modal-header">
      <h5>Filter Archived Signatories</h5>
      <button class="vs-modal-close" id="archiveFilterClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <div class="vs-form-grid vs-form-grid-2">
        <div class="vs-span-2">
          <label class="vs-label" for="afPosition">Position Title</label>
          <select id="afPosition" class="vs-input">
            <option value="">All</option>
          </select>
        </div>
        <div>
          <label class="vs-label" for="afDateFrom">Archived From</label>
          <input type="date" id="afDateFrom" class="vs-input">
        </div>
        <div>
          <label class="vs-label" for="afDateTo">Archived To</label>
          <input type="date" id="afDateTo" class="vs-input">
        </div>
      </div>
    </div>
    <div class="vs-modal-footer">
      <button type="button" class="vs-btn vs-btn-outline" id="archiveFilterClear">Clear All</button>
      <button type="button" class="vs-btn vs-btn-outline" id="archiveFilterCancel">Cancel</button>
      <button type="button" class="vs-btn vs-btn-primary" id="archiveFilterApply">Apply</button>
    </div>
  </div>
</div>
<?php else: ?>
<div class="vs-modal-overlay" id="archiveFilterModal" style="display:none">
  <div class="vs-modal" style="max-width:480px">
    <div class="vs-modal-header">
      <h5>Filter Archived Vouchers</h5>
      <button class="vs-modal-close" id="archiveFilterClose">&times;</button>
    </div>
    <div class="vs-modal-body">
      <div class="vs-form-grid vs-form-grid-2">
        <div class="vs-span-2">
          <label class="vs-label" for="afSchool">School</label>
          <select id="afSchool" class="vs-input">
            <option value="">All</option>
          </select>
        </div>
        <div>
          <label class="vs-label" for="afDateFrom">Archived From</label>
          <input type="date" id="afDateFrom" class="vs-input">
        </div>
        <div>
          <label class="vs-label" for="afDateTo">Archived To</label>
          <input type="date" id="afDateTo" class="vs-input">
        </div>
      </div>
    </div>
    <div class="vs-modal-footer">
      <button type="button" class="vs-btn vs-btn-outline" id="archiveFilterClear">Clear All</button>
      <button type="button" class="vs-btn vs-btn-outline" id="archiveFilterCancel">Cancel</button>
      <button type="button" class="vs-btn vs-btn-primary" id="archiveFilterApply">Apply</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
(function initArchiveSearch() {
    var tableId = <?php
        $t = $type ?? 'user';
        if ($t === 'user')          echo "'archivedUsersTable'";
        elseif ($t === 'signatory') echo "'archivedSignatoriesTable'";
        else                        echo "'archivedVouchersTable'";
    ?>;

    var table = document.getElementById(tableId);
    if (!table || !window.jQuery || !$.fn.DataTable.isDataTable(table)) {
        return setTimeout(initArchiveSearch, 50);
    }
    var dt = $(table).DataTable();
    var dtWrap = table.closest('.dataTables_wrapper');

    var dtSearch = dtWrap ? dtWrap.querySelector('.dataTables_filter') : null;
    if (dtSearch) dtSearch.style.display = 'none';

    var dtLength = dtWrap ? dtWrap.querySelector('.dataTables_length') : null;
    if (dtLength) dtLength.style.display = 'none';

    var lenInput = document.getElementById('archiveLengthInput');
    if (lenInput) {
        function applyArchiveLen() {
            var v = parseInt(lenInput.value, 10);
            if (!isNaN(v) && v > 0) dt.page.len(v).draw();
        }
        lenInput.addEventListener('change', applyArchiveLen);
        lenInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') applyArchiveLen(); });
    }

    var searchInput = document.getElementById('customArchiveSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            dt.search(this.value).draw();
        });
    }

    var filterModal = document.getElementById('archiveFilterModal');
    var filterBadge = document.getElementById('archiveFilterBadge');

    if (filterModal) {
        function openFilter()  { filterModal.style.display = 'flex'; }
        function closeFilter() { filterModal.style.display = 'none'; }

        var btnOpen   = document.getElementById('btnOpenArchiveFilter');
        var btnClose  = document.getElementById('archiveFilterClose');
        var btnCancel = document.getElementById('archiveFilterCancel');
        var btnClear  = document.getElementById('archiveFilterClear');
        var btnApply  = document.getElementById('archiveFilterApply');

        btnOpen   && btnOpen.addEventListener('click', openFilter);
        btnClose  && btnClose.addEventListener('click', closeFilter);
        btnCancel && btnCancel.addEventListener('click', closeFilter);
        filterModal.addEventListener('click', function (e) {
            if (e.target === filterModal) closeFilter();
        });

        var activeFilters = {};

        function countActive() {
            return Object.keys(activeFilters).filter(function (k) {
                return activeFilters[k] && String(activeFilters[k]).trim() !== '';
            }).length;
        }

        function updateBadge() {
            var n = countActive();
            if (filterBadge) { filterBadge.textContent = n || ''; filterBadge.style.display = n ? '' : 'none'; }
        }

        <?php if (($type ?? 'user') === 'user'): ?>
        // ── Users: Role + Archived At date range ─────────────────────────────
        var roleSel = document.getElementById('afRole');

        $.fn.dataTable.ext.search.push(function (settings, rowData, rowIdx) {
            if (settings.nTable.id !== 'archivedUsersTable') return true;
            var row = settings.aoData[rowIdx].nTr;
            if (!row) return true;
            var date = row.getAttribute('data-archived-date') || '';
            if (activeFilters.dateFrom && date < activeFilters.dateFrom) return false;
            if (activeFilters.dateTo   && date > activeFilters.dateTo)   return false;
            return true;
        });

        btnApply && btnApply.addEventListener('click', function () {
            var role = roleSel ? roleSel.value : '';
            activeFilters = {
                role:     role,
                dateFrom: (document.getElementById('afDateFrom') || {}).value || '',
                dateTo:   (document.getElementById('afDateTo')   || {}).value || '',
            };
            updateBadge();
            dt.column(2).search(role ? ('^' + role + '$') : '', true, false).draw();
            closeFilter();
        });

        btnClear && btnClear.addEventListener('click', function () {
            if (roleSel) roleSel.value = '';
            var dfEl = document.getElementById('afDateFrom');
            var dtEl = document.getElementById('afDateTo');
            if (dfEl) dfEl.value = '';
            if (dtEl) dtEl.value = '';
            activeFilters = {};
            updateBadge();
            dt.column(2).search('').draw();
        });

        <?php elseif (($type ?? 'user') === 'signatory'): ?>
        // ── Signatories: Position Title + Archived At date range ─────────────
        var posSel = document.getElementById('afPosition');
        if (posSel) {
            var posSet = new Set();
            dt.column(1).data().each(function (val) {
                var p = (val || '').toString().trim();
                if (p) posSet.add(p);
            });
            Array.from(posSet).sort().forEach(function (p) {
                var opt = document.createElement('option');
                opt.value = p; opt.textContent = p;
                posSel.appendChild(opt);
            });
        }

        $.fn.dataTable.ext.search.push(function (settings, rowData, rowIdx) {
            if (settings.nTable.id !== 'archivedSignatoriesTable') return true;
            var row = settings.aoData[rowIdx].nTr;
            if (!row) return true;
            var date = row.getAttribute('data-archived-date') || '';
            if (activeFilters.dateFrom && date < activeFilters.dateFrom) return false;
            if (activeFilters.dateTo   && date > activeFilters.dateTo)   return false;
            return true;
        });

        btnApply && btnApply.addEventListener('click', function () {
            var pos = posSel ? posSel.value : '';
            activeFilters = {
                position: pos,
                dateFrom: (document.getElementById('afDateFrom') || {}).value || '',
                dateTo:   (document.getElementById('afDateTo')   || {}).value || '',
            };
            updateBadge();
            dt.column(1).search(pos).draw();
            closeFilter();
        });

        btnClear && btnClear.addEventListener('click', function () {
            if (posSel) posSel.value = '';
            var dfEl = document.getElementById('afDateFrom');
            var dtEl = document.getElementById('afDateTo');
            if (dfEl) dfEl.value = '';
            if (dtEl) dtEl.value = '';
            activeFilters = {};
            updateBadge();
            dt.column(1).search('').draw();
        });

        <?php else: ?>
        // ── Vouchers: School + Archived At date range ─────────────────────────
        $.fn.dataTable.ext.search.push(function (settings, rowData, rowIdx) {
            if (settings.nTable.id !== 'archivedVouchersTable') return true;
            var row = settings.aoData[rowIdx].nTr;
            if (!row) return true;
            var date = row.getAttribute('data-archived-date') || '';
            if (activeFilters.dateFrom && date < activeFilters.dateFrom) return false;
            if (activeFilters.dateTo   && date > activeFilters.dateTo)   return false;
            return true;
        });

        var schoolSel = document.getElementById('afSchool');
        if (schoolSel) {
            var schoolSet = new Set();
            dt.column(1).data().each(function (val) {
                var s = (val || '').toString().trim();
                if (s && s !== '-') schoolSet.add(s);
            });
            Array.from(schoolSet).sort().forEach(function (s) {
                var opt = document.createElement('option');
                opt.value = s; opt.textContent = s;
                schoolSel.appendChild(opt);
            });
        }

        btnApply && btnApply.addEventListener('click', function () {
            var school = schoolSel ? schoolSel.value : '';
            activeFilters = {
                school:   school,
                dateFrom: (document.getElementById('afDateFrom') || {}).value || '',
                dateTo:   (document.getElementById('afDateTo')   || {}).value || '',
            };
            dt.column(1).search(school).draw();
            updateBadge();
            closeFilter();
        });

        btnClear && btnClear.addEventListener('click', function () {
            if (schoolSel) schoolSel.value = '';
            var dfEl = document.getElementById('afDateFrom');
            var dtEl = document.getElementById('afDateTo');
            if (dfEl) dfEl.value = '';
            if (dtEl) dtEl.value = '';
            activeFilters = {};
            dt.column(1).search('').draw();
            updateBadge();
        });
        <?php endif; ?>
    }
}());
</script>

<?= $this->endSection() ?>
