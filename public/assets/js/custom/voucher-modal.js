/* Voucher Add / View / Edit modal — loaded via scripts section after pre_script */
(function () {
  var cfg              = window.VM_CONFIG || {};
  var saveStudentUrl   = cfg.saveUrl          || '';
  var fetchStudentUrl  = cfg.fetchUrl         || '';
  var schoolOptionsUrl = cfg.schoolOptionsUrl || '';

  var voucherModal       = document.getElementById('voucherModal');
  var voucherModalForm   = document.getElementById('voucherModalForm');
  var voucherModalTitle  = document.getElementById('voucherModalTitle');
  var voucherModalClose  = document.getElementById('voucherModalClose');
  var voucherModalCancel = document.getElementById('voucherModalCancel');
  var voucherModalAlert  = document.getElementById('voucherModalAlert');
  var voucherSubmitBtn   = document.getElementById('voucherModalSubmit');
  var vmSubmitText       = document.getElementById('vmSubmitText');
  var vmSubmitSpinner    = document.getElementById('vmSubmitSpinner');
  var btnAddVoucher      = document.getElementById('btnAddVoucher');

  var vmVoucherNoWrap       = document.getElementById('vmVoucherNoWrap');
  var vmVoucherNoDisplay    = document.getElementById('vmVoucherNoDisplay');
  var vmVoucherDateWrap     = document.getElementById('vmVoucherDateWrap');
  var vmSchoolYearWrap      = document.getElementById('vmSchoolYearWrap');
  var vmLastGeneratedByWrap = document.getElementById('vmLastGeneratedByWrap');
  var vmLastGeneratedByEl   = document.getElementById('vmLastGeneratedBy');
  var vmLastGeneratedAtEl   = document.getElementById('vmLastGeneratedAt');
  var vmGenerationHistoryDetails = document.getElementById('vmGenerationHistoryDetails');
  var vmGenerationHistoryList = document.getElementById('vmGenerationHistoryList');

  var vmFieldIds = [
    'vmVoucherDate', 'vmFirstName', 'vmMiddleName', 'vmLastName',
    'vmSuffix', 'vmGender', 'vmGwa', 'vmRankNo', 'vmContactNumber',
    'vmJuniorHs', 'vmPreferredHs', 'vmRemarks', 'vmSchoolYear', 'vmEligibility',
  ];
  var vmFieldToName = {
    vmVoucherDate:   'voucher_date',
    vmFirstName:     'first_name',
    vmMiddleName:    'middle_name',
    vmLastName:      'last_name',
    vmSuffix:        'suffix',
    vmGender:        'gender',
    vmGwa:           'gwa',
    vmRankNo:        'rank_no',
    vmContactNumber: 'contact_number',
    vmJuniorHs:      'junior_high_school',
    vmPreferredHs:   'preferred_senior_high_school',
    vmRemarks:       'remarks_status',
    vmSchoolYear:    'school_year',
    vmEligibility:   'eligibility_status',
  };

  function escapeHtml(v) {
    return String(v || '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
    });
  }

  function vmFormatDateTime(value) {
    if (!value) return '';
    var normalized = String(value).replace(' ', 'T');
    var d = new Date(normalized);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleString(undefined, {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: 'numeric',
      minute: '2-digit'
    });
  }

  function vmRenderGenerationHistory(student) {
    var history = Array.isArray(student.generation_history) ? student.generation_history : [];
    var latestAt = student.last_generated_at || student.generated_at || '';

    if (vmLastGeneratedByEl) vmLastGeneratedByEl.textContent = student.last_generated_by || '-';
    if (vmLastGeneratedAtEl) vmLastGeneratedAtEl.textContent = latestAt ? ' | ' + vmFormatDateTime(latestAt) : '';

    if (!vmGenerationHistoryDetails || !vmGenerationHistoryList) return;

    vmGenerationHistoryDetails.style.display = history.length > 1 ? '' : 'none';
    vmGenerationHistoryDetails.open = false;

    if (!history.length) {
      vmGenerationHistoryList.innerHTML = '<div class="vm-generation-history-empty">No generation history yet.</div>';
      return;
    }

    vmGenerationHistoryList.innerHTML = history.map(function (item) {
      var by = item.generated_by || '-';
      var at = vmFormatDateTime(item.generated_at);
      var source = item.source ? String(item.source).replace(/_/g, ' ') : '';
      return '<div class="vm-generation-history-item">'
        + '<strong>' + escapeHtml(by) + '</strong>'
        + '<span>' + escapeHtml(at) + (source ? ' - ' + escapeHtml(source) : '') + '</span>'
        + '</div>';
    }).join('');
  }

  function vmShowAlert(msg, type, errors) {
    var html = '<div class="vs-alert vs-alert-' + (type || 'error') + ' mb-3">' + escapeHtml(msg);
    if (errors && Object.keys(errors).length) {
      html += '<ul class="mb-0 mt-2">';
      Object.keys(errors).forEach(function (f) {
        html += '<li><strong>' + escapeHtml(f) + ':</strong> ' + escapeHtml(errors[f]) + '</li>';
      });
      html += '</ul>';
    }
    html += '</div>';
    voucherModalAlert.innerHTML = html;
  }
  function vmClearAlert() { voucherModalAlert.innerHTML = ''; }

  function vmClearFields() {
    document.getElementById('vmStudentId').value = '';
    vmFieldIds.forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.value = '';
    });
    document.getElementById('vmEligibility').value = 'eligible';
    if (vmLastGeneratedByEl) vmLastGeneratedByEl.textContent = '-';
    if (vmLastGeneratedAtEl) vmLastGeneratedAtEl.textContent = '';
    if (vmGenerationHistoryDetails) vmGenerationHistoryDetails.open = false;
    if (vmGenerationHistoryList) vmGenerationHistoryList.innerHTML = '';
  }

  function vmPopulateFields(student) {
    document.getElementById('vmStudentId').value = student.student_id || '';
    vmFieldIds.forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      var val = student[vmFieldToName[id]];
      el.value = (val === null || val === undefined) ? '' : val;
    });
  }

  function vmSetReadOnly(readOnly) {
    vmFieldIds.forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.readOnly = readOnly;
    });
    voucherSubmitBtn.style.display = readOnly ? 'none' : 'inline-flex';
  }

  function vmApplyModeVisibility(mode) {
    var isView = mode === 'view';
    if (vmVoucherNoWrap)       vmVoucherNoWrap.style.display       = isView ? ''     : 'none';
    if (vmVoucherDateWrap)     vmVoucherDateWrap.style.display     = isView ? 'none' : '';
    if (vmSchoolYearWrap)      vmSchoolYearWrap.style.display      = isView ? 'none' : '';
    if (vmLastGeneratedByWrap) vmLastGeneratedByWrap.style.display = isView ? ''     : 'none';
  }

  function loadSchoolOptions(selectedJhs, selectedShs) {
    fetch(schoolOptionsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var jhsDl    = document.getElementById('dl-jhs');
        var shsDl    = document.getElementById('dl-shs');
        var jhsInput = document.getElementById('vmJuniorHs');
        var shsInput = document.getElementById('vmPreferredHs');
        if (jhsDl && Array.isArray(data.jhs)) {
          jhsDl.innerHTML = '';
          data.jhs.forEach(function (name) {
            var opt = document.createElement('option');
            opt.value = name;
            jhsDl.appendChild(opt);
          });
        }
        if (shsDl && Array.isArray(data.shs)) {
          shsDl.innerHTML = '';
          data.shs.forEach(function (name) {
            var opt = document.createElement('option');
            opt.value = name;
            shsDl.appendChild(opt);
          });
        }
        if (jhsInput) jhsInput.value = selectedJhs || '';
        if (shsInput) shsInput.value = selectedShs || '';
      })
      .catch(function () {});
  }

  function vmOpen(mode, studentId) {
    vmClearAlert();
    vmClearFields();
    vmApplyModeVisibility(mode);

    if (mode === 'add') {
      voucherModalTitle.textContent = 'Add Voucher';
      vmSubmitText.textContent      = 'Save Voucher';
      vmSetReadOnly(false);
      document.getElementById('vmVoucherDate').value = new Date().toISOString().slice(0, 10);
      loadSchoolOptions('', '');
      voucherModal.style.display = 'flex';
      return;
    }

    voucherModalTitle.textContent = mode === 'edit' ? 'Edit Voucher' : 'View Voucher';
    vmSubmitText.textContent      = 'Update Voucher';
    vmSetReadOnly(mode === 'view');
    voucherModal.style.display = 'flex';

    fetch(fetchStudentUrl + '/' + studentId, ajaxOptions({ method: 'GET' }))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.status !== 'success') {
          vmShowAlert(data.message || 'Failed to load student.', 'error');
          return;
        }
        vmPopulateFields(data.student);
        if (mode === 'view') {
          if (vmVoucherNoDisplay) vmVoucherNoDisplay.textContent = data.student.voucher_no || '—';
          vmRenderGenerationHistory(data.student);
        }
        if (mode !== 'view') {
          loadSchoolOptions(
            data.student.junior_high_school || '',
            data.student.preferred_senior_high_school || ''
          );
        }
        vmSetReadOnly(mode === 'view');
      })
      .catch(function () {
        vmShowAlert('Failed to load student.', 'error');
      });
  }

  function vmClose() { voucherModal.style.display = 'none'; }

  btnAddVoucher      && btnAddVoucher.addEventListener('click',      function () { vmOpen('add'); });
  voucherModalClose  && voucherModalClose.addEventListener('click',  vmClose);
  voucherModalCancel && voucherModalCancel.addEventListener('click', vmClose);
  voucherModal       && voucherModal.addEventListener('click',       function (e) { if (e.target === voucherModal) vmClose(); });

  // Event delegation — works for AJAX-rendered rows (server-side DataTables)
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-voucher-action');
    if (!btn) return;
    vmOpen(btn.getAttribute('data-mode'), btn.getAttribute('data-id'));
  });

  window.vmOpen = vmOpen;

  voucherModalForm && voucherModalForm.addEventListener('submit', function (e) {
    e.preventDefault();
    vmClearAlert();

    var fd = new FormData(voucherModalForm);
    var csrf = getCsrfToken && getCsrfToken();
    if (csrf && csrf.name && !fd.get(csrf.name)) fd.append(csrf.name, csrf.token);

    voucherSubmitBtn.disabled    = true;
    vmSubmitText.style.display   = 'none';
    vmSubmitSpinner.style.display = 'inline-block';

    fetch(saveStudentUrl, ajaxOptions({ method: 'POST', body: fd }))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.status === 'success') { vmClose(); location.reload(); return; }
        vmShowAlert(data.message || 'Save failed.', 'error', data.errors);
      })
      .catch(function () {
        vmShowAlert('An error occurred while saving.', 'error');
      })
      .finally(function () {
        voucherSubmitBtn.disabled    = false;
        vmSubmitText.style.display   = 'inline';
        vmSubmitSpinner.style.display = 'none';
      });
  });
}());
