<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="vs-page-header mb-4">
        <div>
            <h4 class="vs-page-title">Archive</h4>
            <p class="vs-page-sub">Review and restore archived records.</p>
        </div>
    </div>

    <form method="get" class="vs-advanced-search vs-advanced-search-outside mb-3">
        <input type="text" name="q" class="vs-input vs-advanced-search-input" placeholder="Advanced search all archived vouchers..." value="<?= esc((string) ($keyword ?? ''), 'attr') ?>">
        <button type="button" class="vs-btn vs-btn-outline" id="btnOpenArchiveFilter">
            Filters
            <span id="archiveFilterBadge" class="badge bg-primary" style="display:none;margin-left:.35rem"></span>
        </button>
    </form>

    <div class="vs-card">
        <div class="vs-card-body">
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <input type="text" id="customArchiveSearch" class="vs-input vs-page-search" placeholder="Search this page..." style="max-width:260px">
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

<script>
(function initArchiveSearch() {
    var tableId = 'archivedVouchersTable';

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
    if (window.VS && window.VS.bindCurrentPageSearch) {
        window.VS.bindCurrentPageSearch(dt, searchInput);
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
    }
}());
</script>

<?= $this->endSection() ?>
