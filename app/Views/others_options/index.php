<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$contextLabels = $contexts ?? ['suffix' => 'SUFFIX', 'prefix' => 'PREFIX', 'degree' => 'DEGREE'];
$ctxKeys       = array_keys($contextLabels);
$options       = $options ?? [];
?>

<div class="vs-page-header mb-3">
  <div>
    <h4 class="vs-page-title"><?= esc($title) ?></h4>
    <p class="vs-page-sub">Manage custom dropdown options for Suffix, Prefix, and Degree fields.</p>
  </div>
</div>

<!-- Toolbar -->
<div class="row g-2 mb-3">
  <div class="col-12 col-lg">
    <input type="text" id="ooSearch" class="form-control vs-advanced-search-input" placeholder="Enter keyword to search (Context, Value)">
  </div>
  <div class="col-6 col-lg-auto">
    <select id="ooFieldFilter" class="js-filter-select" data-placeholder="Select Field" data-no-search="1" data-width="100%" style="min-width:140px">
      <option value=""></option>
      <?php foreach ($contextLabels as $key => $label): ?>
        <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
      <?php endforeach ?>
    </select>
  </div>
  <div class="col-6 col-lg-auto">
    <select id="ooStatusFilter" class="js-filter-select" data-placeholder="Select Status" data-no-search="1" data-width="100%" style="min-width:130px">
      <option value=""></option>
      <option value="active">ACTIVE</option>
      <option value="inactive">INACTIVE</option>
    </select>
  </div>
  <div class="col-auto d-none d-lg-flex align-items-center">
    <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
  </div>
  <div class="col-12 col-lg-auto">
    <div class="row g-2 row-cols-2 row-cols-lg-auto">
      <div class="col">
        <button type="button" class="btn btn-primary w-100" id="ooSearchBtn" style="min-width:90px">Search</button>
      </div>
      <div class="col">
        <button type="button" class="btn btn-danger w-100" id="ooClearBtn" style="min-width:90px">Clear</button>
      </div>
    </div>
  </div>
  <div class="col-auto d-none d-lg-flex align-items-center">
    <span style="color:var(--border);font-size:1.2rem;line-height:1;user-select:none">|</span>
  </div>
  <div class="col-12 col-lg-auto d-grid d-lg-block">
    <button type="button" class="btn btn-success" id="ooOpenAddModal" style="min-width:120px">Add Option</button>
  </div>
</div>

<div class="vs-card">
  <div class="vs-card-body">
    <table id="ooOptionsTable" class="vs-datatable js-data-table vs-mobile-primary" data-mobile-primary="1" data-page-search="ooPageSearch"
           data-order='[[5,"desc"],[4,"asc"],[1,"asc"]]'
           data-col-defs='[{"visible":false,"searchable":false,"targets":[4,5]},{"orderable":false,"targets":[3]},{"orderData":[4],"targets":[0]},{"orderData":[5],"targets":[2]},{"width":"39%","targets":[0]},{"width":"39%","targets":[1]},{"width":"12%","targets":[2]},{"width":"10%","targets":[3]}]'
           style="width:100%">
      <thead>
        <tr>
          <th>Context</th>
          <th>Value</th>
          <th>Status</th>
          <th class="actions-column actions-column--sm">Actions</th>
          <th style="display:none"></th>
          <th style="display:none"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($options as $opt):
          $ctx      = $opt['context'];
          $ctxLabel = $contextLabels[$ctx] ?? strtoupper($ctx);
          $ctxOrder = ($k = array_search($ctx, $ctxKeys)) !== false ? $k : 99;
          $isActive = (int) $opt['is_active'];
          $oid      = (int) $opt['id'];
          $val      = $opt['value'];
        ?>
        <tr id="oo-row-<?= $oid ?>"<?= $isActive ? '' : ' class="vs-row-inactive"' ?>>
          <td><?= esc($ctxLabel) ?></td>
          <td><?= esc($val) ?></td>
          <td>
            <span class="badge <?= $isActive ? 'bg-success' : 'bg-danger' ?>" id="oo-badge-<?= $oid ?>">
              <?= $isActive ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td class="actions-cell">
            <div class="dropdown">
              <button type="button" class="vs-tbl-btn vs-tbl-btn-actions dropdown-toggle"
                      data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">Actions</button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <button type="button" class="dropdown-item oo-edit-btn"
                          data-id="<?= $oid ?>"
                          data-value="<?= esc($val, 'attr') ?>"
                          data-ctx="<?= esc($ctx, 'attr') ?>">Edit</button>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <?php if ($isActive): ?>
                    <button type="button" class="dropdown-item text-danger oo-deactivate-btn"
                            data-id="<?= $oid ?>"
                            data-value="<?= esc($val, 'attr') ?>">Deactivate</button>
                  <?php else: ?>
                    <button type="button" class="dropdown-item text-success oo-activate-btn"
                            data-id="<?= $oid ?>"
                            data-value="<?= esc($val, 'attr') ?>">Activate</button>
                  <?php endif ?>
                </li>
              </ul>
            </div>
          </td>
          <td style="display:none"><?= $ctxOrder ?></td>
          <td style="display:none"><?= $isActive ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Option Modal -->
<div class="modal modal-dialog-centered modal-backdrop" id="ooAddModal" style="display:none">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Option</h5>
        <button type="button" class="btn-close" id="ooAddModalClose" aria-label="Close"></button>
      </div>
      <form id="ooAddForm" novalidate>
        <div class="modal-body">
          <div id="ooAddAlert"></div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label required" for="ooModalContext">Field</label>
              <select id="ooModalContext" name="context" class="vs-input js-filter-select" data-placeholder="Select Field" data-no-search="1" required>
                <option value=""></option>
                <?php foreach ($contextLabels as $key => $label): ?>
                  <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                <?php endforeach ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label required" for="ooModalValue">Value</label>
              <input id="ooModalValue" name="value" type="text" class="vs-input vs-uppercase" placeholder="e.g. ESQ., ATTY." maxlength="255" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="ooAddModalCancel">Close</button>
          <button type="submit" class="btn btn-primary" id="ooAddSubmit">
            <span id="ooAddBtnText">Save</span>
            <span id="ooAddSpinner" class="vs-spinner" style="display:none"></span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Option Modal -->
<div class="modal modal-dialog-centered modal-backdrop" id="ooEditModal" style="display:none">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Option</h5>
        <button type="button" class="btn-close" id="ooEditModalClose" aria-label="Close"></button>
      </div>
      <form id="ooEditForm" novalidate>
        <input type="hidden" id="ooEditId">
        <div class="modal-body">
          <div id="ooEditAlert"></div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label required" for="ooEditContext">Field</label>
              <select id="ooEditContext" name="context" class="vs-input js-filter-select" data-placeholder="Select Field" data-no-search="1" required>
                <option value=""></option>
                <?php foreach ($contextLabels as $key => $label): ?>
                  <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                <?php endforeach ?>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label required" for="ooEditValue">Value</label>
              <input id="ooEditValue" name="value" type="text" class="vs-input vs-uppercase" maxlength="255" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="ooEditModalCancel">Close</button>
          <button type="submit" class="btn btn-primary" id="ooEditSubmit">
            <span id="ooEditBtnText">Update</span>
            <span id="ooEditSpinner" class="vs-spinner" style="display:none"></span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Confirm Modal -->
<div class="modal modal-dialog-centered modal-backdrop" id="ooConfirmModal" style="display:none">
  <div class="modal-dialog" style="max-width:420px">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="ooConfirmTitle">Confirm</h5>
        <button type="button" class="btn-close" id="ooConfirmClose" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="ooConfirmMsg" class="mb-0"></p>
      </div>
      <div class="modal-footer gap-2">
        <button class="btn btn-secondary" id="ooConfirmCancel">Cancel</button>
        <button class="btn btn-danger"    id="ooConfirmOk">Confirm</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var addModal       = document.getElementById('ooAddModal');
  var addForm        = document.getElementById('ooAddForm');
  var addAlert       = document.getElementById('ooAddAlert');
  var addSubmit      = document.getElementById('ooAddSubmit');
  var addBtnText     = document.getElementById('ooAddBtnText');
  var addSpinner     = document.getElementById('ooAddSpinner');
  var editModal      = document.getElementById('ooEditModal');
  var editForm       = document.getElementById('ooEditForm');
  var editAlert      = document.getElementById('ooEditAlert');
  var editId         = document.getElementById('ooEditId');
  var editValueInput = document.getElementById('ooEditValue');
  var editSubmit     = document.getElementById('ooEditSubmit');
  var editBtnText    = document.getElementById('ooEditBtnText');
  var editSpinner    = document.getElementById('ooEditSpinner');
  var confirmModal   = document.getElementById('ooConfirmModal');
  var confirmTitle   = document.getElementById('ooConfirmTitle');
  var confirmMsg     = document.getElementById('ooConfirmMsg');
  var confirmOk      = document.getElementById('ooConfirmOk');
  var confirmCancel  = document.getElementById('ooConfirmCancel');
  var confirmClose   = document.getElementById('ooConfirmClose');
  var searchInput    = document.getElementById('ooSearch');
  var searchBtn      = document.getElementById('ooSearchBtn');
  var clearBtn       = document.getElementById('ooClearBtn');
  var openAddBtn     = document.getElementById('ooOpenAddModal');

  var saveUrl       = '<?= site_url('admin/others-options/save') ?>';
  var editUrl       = '<?= site_url('admin/others-options/edit') ?>/';
  var deactivateUrl = '<?= site_url('admin/others-options/deactivate') ?>/';
  var activateUrl   = '<?= site_url('admin/others-options/activate') ?>/';
  var contextLabels = <?= json_encode($contextLabels) ?>;

  var pendingAction = null;

  /* ── CSRF ─────────────────────────────────────────────── */
  function csrfToken() {
    var m = document.querySelector('meta[name="csrf-token-value"]');
    return m ? m.getAttribute('content') : '';
  }
  function updateCsrf(tok) {
    if (!tok) return;
    if (window.__VS && window.__VS.csrf) window.__VS.csrf.hash = tok;
    var m = document.querySelector('meta[name="csrf-token-value"]');
    if (m) m.content = tok;
  }
  function ajaxHeaders() {
    return {
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-CSRF-TOKEN': csrfToken(),
    };
  }

  /* ── DataTable accessor ───────────────────────────────── */
  function getDt() {
    var tbl = document.getElementById('ooOptionsTable');
    if (!tbl || !window.jQuery || !$.fn.DataTable.isDataTable(tbl)) return null;
    return $(tbl).DataTable();
  }

  /* ── Toolbar search / filter ──────────────────────────── */
  function applySearch() {
    var dt = getDt();
    if (!dt) return;
    var q   = searchInput.value.trim();
    var ctx = window.jQuery ? jQuery('#ooFieldFilter').val() : '';
    var st  = window.jQuery ? jQuery('#ooStatusFilter').val() : '';

    var ctxTxt = (ctx && contextLabels[ctx]) ? '^' + contextLabels[ctx] + '$' : '';
    var stTxt  = st ? '^' + (st === 'active' ? 'Active' : 'Inactive') + '$' : '';

    dt.search(q);
    dt.column(0).search(ctxTxt, true, false);
    dt.column(2).search(stTxt, true, false);
    dt.draw();
  }

  function clearSearch() {
    searchInput.value = '';
    if (window.jQuery) {
      jQuery('#ooFieldFilter').val('').trigger('change.select2');
      jQuery('#ooStatusFilter').val('').trigger('change.select2');
    }
    var dt = getDt();
    if (!dt) return;
    dt.search('');
    dt.column(0).search('');
    dt.column(2).search('');
    dt.draw();
  }

  searchBtn   && searchBtn.addEventListener('click', applySearch);
  clearBtn    && clearBtn.addEventListener('click', clearSearch);
  searchInput && searchInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') applySearch();
  });

  /* ── Confirm modal ────────────────────────────────────── */
  function openConfirm(title, msg, okLabel, okClass, onOk) {
    confirmTitle.textContent   = title;
    confirmMsg.textContent     = msg;
    confirmOk.textContent      = okLabel;
    confirmOk.className        = 'btn ' + okClass;
    pendingAction              = onOk;
    confirmModal.style.display = 'flex';
  }
  function closeConfirm() {
    confirmModal.style.display = 'none';
    pendingAction = null;
  }
  confirmOk.addEventListener('click', function () { if (pendingAction) pendingAction(); closeConfirm(); });
  confirmCancel.addEventListener('click', closeConfirm);
  confirmClose.addEventListener('click', closeConfirm);
  confirmModal.addEventListener('click', function (e) { if (e.target === confirmModal) closeConfirm(); });

  /* ── Add Option modal ─────────────────────────────────── */
  function openAddModal() {
    addAlert.innerHTML = '';
    addForm.reset();
    if (window.jQuery) jQuery('#ooModalContext').val('').trigger('change.select2');
    if (typeof window.initVsSelect2 === 'function') window.initVsSelect2(addModal);
    addModal.style.display = 'flex';
  }
  function closeAddModal() { addModal.style.display = 'none'; }

  openAddBtn && openAddBtn.addEventListener('click', openAddModal);
  document.getElementById('ooAddModalClose').addEventListener('click', closeAddModal);
  document.getElementById('ooAddModalCancel').addEventListener('click', closeAddModal);
  addModal.addEventListener('click', function (e) { if (e.target === addModal) closeAddModal(); });

  addForm.addEventListener('submit', function (e) {
    e.preventDefault();
    addAlert.innerHTML = '';
    var ctx = window.jQuery ? jQuery('#ooModalContext').val() : document.getElementById('ooModalContext').value;
    var val = document.getElementById('ooModalValue').value.trim().toUpperCase();
    if (!ctx) { addAlert.innerHTML = '<div class="vs-alert vs-alert-error mb-2">Please select a field.</div>'; return; }
    if (!val) { addAlert.innerHTML = '<div class="vs-alert vs-alert-error mb-2">Please enter a value.</div>'; return; }

    addSubmit.disabled       = true;
    addBtnText.style.display = 'none';
    addSpinner.style.display = 'inline-block';

    fetch(saveUrl, {
      method: 'POST',
      headers: ajaxHeaders(),
      body: 'context=' + encodeURIComponent(ctx) + '&value=' + encodeURIComponent(val),
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      addSubmit.disabled       = false;
      addBtnText.style.display = 'inline';
      addSpinner.style.display = 'none';
      if (!d.success) {
        addAlert.innerHTML = '<div class="vs-alert vs-alert-error mb-2">' + (d.message || 'Error saving option.') + '</div>';
        return;
      }
      updateCsrf(d.csrf_token);
      closeAddModal();
      toastAndReload(d.message || 'Option added.', 'success');
    })
    .catch(function () {
      addSubmit.disabled       = false;
      addBtnText.style.display = 'inline';
      addSpinner.style.display = 'none';
      addAlert.innerHTML = '<div class="vs-alert vs-alert-error mb-2">Network error.</div>';
    });
  });

  /* ── Edit Option modal ────────────────────────────────── */
  function openEditModal(id, value, ctx) {
    editAlert.innerHTML     = '';
    editId.value            = id;
    editValueInput.value    = value;
    if (window.jQuery) jQuery('#ooEditContext').val(ctx).trigger('change.select2');
    else { var s = document.getElementById('ooEditContext'); if (s) s.value = ctx; }
    if (typeof window.initVsSelect2 === 'function') window.initVsSelect2(editModal);
    editModal.style.display = 'flex';
    setTimeout(function () { editValueInput.focus(); editValueInput.select(); }, 50);
  }
  function closeEditModal() { editModal.style.display = 'none'; }

  document.getElementById('ooEditModalClose').addEventListener('click', closeEditModal);
  document.getElementById('ooEditModalCancel').addEventListener('click', closeEditModal);
  editModal.addEventListener('click', function (e) { if (e.target === editModal) closeEditModal(); });

  editForm.addEventListener('submit', function (e) {
    e.preventDefault();
    editAlert.innerHTML = '';
    var id  = editId.value;
    var ctx = window.jQuery ? jQuery('#ooEditContext').val() : (document.getElementById('ooEditContext') || {}).value || '';
    var val = editValueInput.value.trim().toUpperCase();
    if (!ctx) { editAlert.innerHTML = '<div class="vs-alert vs-alert-error mb-2">Please select a field.</div>'; return; }
    if (!val) { editAlert.innerHTML = '<div class="vs-alert vs-alert-error mb-2">Please enter a value.</div>'; return; }

    editSubmit.disabled       = true;
    editBtnText.style.display = 'none';
    editSpinner.style.display = 'inline-block';

    fetch(editUrl + id, {
      method: 'POST',
      headers: ajaxHeaders(),
      body: 'context=' + encodeURIComponent(ctx) + '&value=' + encodeURIComponent(val),
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      editSubmit.disabled       = false;
      editBtnText.style.display = 'inline';
      editSpinner.style.display = 'none';
      if (!d.success) {
        editAlert.innerHTML = '<div class="vs-alert vs-alert-error mb-2">' + (d.message || 'Error updating option.') + '</div>';
        return;
      }
      updateCsrf(d.csrf_token);
      closeEditModal();
      toastAndReload(d.message || 'Option updated.', 'success');
    })
    .catch(function () {
      editSubmit.disabled       = false;
      editBtnText.style.display = 'inline';
      editSpinner.style.display = 'none';
      editAlert.innerHTML = '<div class="vs-alert vs-alert-error mb-2">Network error.</div>';
    });
  });

  /* ── Delegated actions ────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var editBtn = e.target.closest('.oo-edit-btn');
    if (editBtn) {
      openEditModal(editBtn.dataset.id, editBtn.dataset.value, editBtn.dataset.ctx);
      return;
    }

    var deBtn = e.target.closest('.oo-deactivate-btn');
    if (deBtn) {
      var deId  = deBtn.dataset.id;
      var deVal = deBtn.dataset.value;
      openConfirm(
        'Deactivate Option',
        'Deactivate "' + deVal + '"? It will no longer appear in dropdowns.',
        'Deactivate', 'btn-danger',
        function () {
          deBtn.disabled = true;
          fetch(deactivateUrl + deId, { method: 'POST', headers: ajaxHeaders(), body: '' })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (!d.success) { deBtn.disabled = false; showToast(d.message || 'Error.', 'error'); return; }
            updateCsrf(d.csrf_token);
            toastAndReload(d.message || 'Deactivated.', 'success');
          })
          .catch(function () { deBtn.disabled = false; showToast('Network error.', 'error'); });
        }
      );
      return;
    }

    var acBtn = e.target.closest('.oo-activate-btn');
    if (acBtn) {
      var acId  = acBtn.dataset.id;
      var acVal = acBtn.dataset.value;
      openConfirm(
        'Activate Option',
        'Activate "' + acVal + '"? It will appear in dropdowns again.',
        'Activate', 'btn-success',
        function () {
          acBtn.disabled = true;
          fetch(activateUrl + acId, { method: 'POST', headers: ajaxHeaders(), body: '' })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (!d.success) { acBtn.disabled = false; showToast(d.message || 'Error.', 'error'); return; }
            updateCsrf(d.csrf_token);
            toastAndReload(d.message || 'Activated.', 'success');
          })
          .catch(function () { acBtn.disabled = false; showToast('Network error.', 'error'); });
        }
      );
    }
  });

  /* ── Init Select2 on page load ────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.initVsSelect2 === 'function') window.initVsSelect2(document);
  });
}());
</script>

<?= $this->endSection() ?>
