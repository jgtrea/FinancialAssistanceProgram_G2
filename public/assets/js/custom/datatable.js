/* ============================================================
   DATATABLE SHARED INIT — generic table setup + VS search helpers
   ============================================================ */

function initGenericDataTables() {
  if (!window.jQuery || !$.fn.DataTable) return;

  document.querySelectorAll('table.js-data-table').forEach(table => {
    if ($.fn.DataTable.isDataTable(table)) return;

    const nonOrderableTargets = Array.from(table.querySelectorAll('thead th'))
      .map((th, index) => {
        const isActions = th.classList.contains('actions-column')
          || th.textContent.trim().toLowerCase() === 'actions';
        const isCheckCol = th.classList.contains('vs-th-check');
        return (isActions || isCheckCol) ? index : -1;
      })
      .filter(index => index >= 0);

    let order = [];
    if (table.dataset.order) {
      try { order = JSON.parse(table.dataset.order); } catch (e) {}
    }

    let extraColDefs = [];
    if (table.dataset.colDefs) {
      try { extraColDefs = JSON.parse(table.dataset.colDefs); } catch (e) {}
    }

    const mergedColDefs = nonOrderableTargets.length
      ? [{ orderable: false, targets: nonOrderableTargets }, ...extraColDefs]
      : extraColDefs;

    const hasPageSearch = !!table.dataset.pageSearch;

    const dt = $(table).DataTable({
      dom:        window.VS.dtHeaderDom(hasPageSearch) + window.VS.dtBodyDom,
      pageLength: 10,
      lengthMenu: window.VS.dtLengthMenu,
      responsive: true,
      autoWidth:  false,
      processing: true,
      order,
      columnDefs: mergedColDefs,
      language:   window.VS.dtLanguage({
        searchPlaceholder: table.dataset.searchPlaceholder || 'Search...',
        emptyTable:        table.dataset.emptyText || 'No records found.',
      }),
    });

    if (hasPageSearch) {
      const slot = dt.table().container().querySelector('.vs-dt-search-slot');
      if (slot) {
        const pageInput = document.createElement('input');
        pageInput.type = 'text';
        pageInput.id = table.dataset.pageSearch;
        pageInput.className = 'vs-input vs-page-search';
        pageInput.placeholder = 'Enter keyword to search this page';
        pageInput.style.maxWidth = '260px';
        slot.parentElement.insertBefore(pageInput, slot);
        slot.remove();
        window.VS.bindCurrentPageSearch(dt, pageInput);
      }
    }

  });
}

window.VS = window.VS || {};

// ── Shared DataTable config — consume in voucher.js and any future table ──────

// Body + footer DOM (table rows + info/pagination). Same for all tables.
window.VS.dtBodyDom =
  "<'row'<'col-sm-12'tr>>" +
  "<'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>";

// Header DOM: with page-search slot (left) + show-entries (right),
// or show-entries only (right-aligned).
window.VS.dtHeaderDom = function (hasPageSearch) {
  return hasPageSearch
    ? "<'d-flex align-items-center gap-2 mb-3 flex-wrap'<'vs-dt-search-slot'><'ms-auto'l>>"
    : "<'row align-items-center mb-3'<'col-sm-12 text-end'l>>";
};

// Base language object. Override individual keys when needed.
window.VS.dtLanguage = function (overrides) {
  return Object.assign({
    search:      '',
    lengthMenu:  'Show _MENU_ entries',
    info:        'Showing _START_ to _END_ of _TOTAL_',
    paginate:    { previous: '&#8249;', next: '&#8250;' },
    processing:  'Loading...',
  }, overrides || {});
};

// Standard length menus.
window.VS.dtLengthMenu    = [[10, 25, 50, 100, -1],  [10, 25, 50, 100, 'All']];
window.VS.dtLengthMenuSS  = [[10, 25, 50, 100, 250], [10, 25, 50, 100, 250]]; // server-side

// Filters only the currently visible page of a DataTable.
window.VS.bindCurrentPageSearch = function bindCurrentPageSearch(dt, input) {
  if (!dt || !input) return;
  if (input.dataset.currentPageSearchBound === '1') return;
  input.dataset.currentPageSearchBound = '1';

  function normalize(value) {
    return (value || '').toString().toLowerCase().trim();
  }

  function applySearch() {
    const query = normalize(input.value);
    dt.rows({ page: 'current' }).every(function () {
      const row = this.node();
      if (!row) return;
      const extra = normalize(row.dataset.searchExtra || '');
      const text  = normalize(row.textContent) + ' ' + extra;
      row.style.display = !query || text.indexOf(query) !== -1 ? '' : 'none';
    });
  }

  input.addEventListener('input', applySearch);
  dt.on('draw.dt page.dt order.dt length.dt', applySearch);
  applySearch();
};

// Filters across the full set of rows loaded into the DataTable (not just the
// visible page). Use this when the server has pre-loaded a capped slice and the
// in-table input should let the user search the whole slice.
window.VS.bindFullTableSearch = function bindFullTableSearch(dt, input) {
  if (!dt || !input) return;
  if (input.dataset.fullTableSearchBound === '1') return;
  input.dataset.fullTableSearchBound = '1';

  input.addEventListener('input', function () {
    dt.search(input.value).draw();
  });
};

document.addEventListener('DOMContentLoaded', initGenericDataTables);
