/* ============================================================
   DATATABLE SHARED INIT — generic table setup + VS search helpers
   ============================================================ */

function initGenericDataTables() {
  if (!window.jQuery || !$.fn.DataTable) return;

  // No 'f' — built-in search replaced by the injected external input below.
  const controlsDom =
    "<'row align-items-center mb-3'<'col-sm-12 text-end'l>>" +
    "<'row'<'col-sm-12'tr>>" +
    "<'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>";

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

    $(table).DataTable({
      dom: controlsDom,
      pageLength: 10,
      lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
      responsive: true,
      autoWidth: false,
      processing: true,
      order,
      columnDefs: mergedColDefs,
      language: {
        search: '',
        searchPlaceholder: table.dataset.searchPlaceholder || 'Search...',
        emptyTable: table.dataset.emptyText || 'No records found.',
        lengthMenu: 'Show _MENU_ entries',
        info: 'Showing _START_ to _END_ of _TOTAL_',
        processing: 'Loading...',
      },
    });
  });
}

window.VS = window.VS || {};

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
      const text = normalize(row.textContent);
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
