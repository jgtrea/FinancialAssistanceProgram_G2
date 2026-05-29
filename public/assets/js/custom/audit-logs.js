/* ============================================================
   AUDIT LOGS PAGE — DataTable init
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  const logsTable = document.getElementById('logsTable');
  if (!logsTable) return;

  $('#logsTable').DataTable({
    dom:
      "<'row align-items-center mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 text-md-end'l>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row align-items-center mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
    order: [[6, 'desc']],
    columnDefs: [
      { orderable: false, targets: [4] },
      { width: '160px',  targets: [6] },
    ],
    language: {
      search: '',
      searchPlaceholder: 'Search logs...',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_ to _END_ of _TOTAL_',
    },
  });

});
