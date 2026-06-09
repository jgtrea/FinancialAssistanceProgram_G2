/* ============================================================
   USERS PAGE — DataTable, delete modal, toggle status
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  const usersTable = document.getElementById('usersTable');
  if (!usersTable) return;

  $('#usersTable').DataTable({
    pageLength: 25,
    order: [[6, 'desc']],
    columnDefs: [{ orderable: false, targets: [4, 7] }],
    language: { search: '', searchPlaceholder: 'Search users...' },
  });

  // ── Delete modal ─────────────────────────────────────────────────────────────
  const deleteModal   = document.getElementById('deleteModal');
  const deleteClose   = document.getElementById('deleteModalClose');
  const deleteCancel  = document.getElementById('deleteModalCancel');
  const deleteConfirm = document.getElementById('deleteConfirm');
  const deleteNameEl  = document.getElementById('deleteUserName');
  const deleteBtnText = document.getElementById('deleteBtnText');
  const deleteSpinner = document.getElementById('deleteBtnSpinner');

  let deleteTargetId = null;

  document.querySelectorAll('.vs-delete-user').forEach(btn => {
    btn.addEventListener('click', function () {
      deleteTargetId = this.dataset.id;
      if (deleteNameEl) deleteNameEl.textContent = this.dataset.name;
      if (deleteModal)  deleteModal.style.display = 'flex';
    });
  });

  const closeDeleteModal = () => { if (deleteModal) deleteModal.style.display = 'none'; };
  if (deleteClose)  deleteClose.addEventListener('click',  closeDeleteModal);
  if (deleteCancel) deleteCancel.addEventListener('click', closeDeleteModal);
  deleteModal && deleteModal.addEventListener('click', e => { if (e.target === deleteModal) closeDeleteModal(); });

  if (deleteConfirm) {
    deleteConfirm.addEventListener('click', async function () {
      if (!deleteTargetId) return;

      deleteBtnText && (deleteBtnText.style.display = 'none');
      deleteSpinner && (deleteSpinner.style.display  = 'inline-block');
      deleteConfirm.disabled = true;

      const csrf     = getCsrfToken();
      const formData = new FormData();
      formData.append(csrf.name, csrf.token);

      const base = window.location.pathname.split('/users')[0];
      const url  = base + '/users/delete/' + deleteTargetId;

      try {
        const res  = await fetch(url, ajaxOptions({ method: 'POST', body: formData }));
        const data = await res.json();
        closeDeleteModal();

        if (data.success) {
          showAlert(data.message, 'success');
          const dt  = $('#usersTable').DataTable();
          const btn = document.querySelector(`.vs-delete-user[data-id="${deleteTargetId}"]`);
          if (btn) dt.row(btn.closest('tr')).remove().draw();
        } else {
          showAlert(data.message || 'Delete failed.', 'error');
        }
      } catch (err) {
        showAlert('An error occurred.', 'error');
        console.error(err);
      } finally {
        deleteBtnText && (deleteBtnText.style.display = 'inline');
        deleteSpinner && (deleteSpinner.style.display  = 'none');
        deleteConfirm.disabled = false;
      }
    });
  }

  // ── Toggle active status ─────────────────────────────────────────────────────
  document.querySelectorAll('.vs-toggle-status').forEach(btn => {
    btn.addEventListener('click', async function () {
      const id       = this.dataset.id;
      const csrf     = getCsrfToken();
      const formData = new FormData();
      formData.append(csrf.name, csrf.token);

      const base = window.location.pathname.split('/users')[0];
      const url  = base + '/users/toggle-status/' + id;

      this.disabled = true;
      try {
        const res  = await fetch(url, ajaxOptions({ method: 'POST', body: formData }));
        const data = await res.json();

        if (data.success) {
          const active = data.is_active;
          this.textContent = active ? 'Active' : 'Inactive';
          this.className   = 'vs-toggle-status ' + (active ? 'vs-toggle-active' : 'vs-toggle-inactive');
          this.dataset.active = active;
        } else {
          showAlert(data.message || 'Status update failed.', 'error');
        }
      } catch (err) {
        showAlert('An error occurred.', 'error');
      } finally {
        this.disabled = false;
      }
    });
  });

});
