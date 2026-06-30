/* Voucher Add / View / Edit modal — loaded via scripts section after pre_script */
document.addEventListener("DOMContentLoaded", function () {
  var cfg = window.VM_CONFIG || {};
  var saveStudentUrl = cfg.saveUrl || "";
  var fetchStudentUrl = cfg.fetchUrl || "";
  var schoolOptionsUrl = cfg.schoolOptionsUrl || "";

  var voucherModal = document.getElementById("voucherModal");
  var voucherModalForm = document.getElementById("voucherModalForm");
  var voucherModalTitle = document.getElementById("voucherModalTitle");
  var voucherModalClose = document.getElementById("voucherModalClose");
  var voucherModalCancel = document.getElementById("voucherModalCancel");
  var voucherModalAlert = document.getElementById("voucherModalAlert");
  var voucherSubmitBtn = document.getElementById("voucherModalSubmit");
  var vmSubmitText = document.getElementById("vmSubmitText");
  var vmSubmitSpinner = document.getElementById("vmSubmitSpinner");
  var btnAddVoucher = document.getElementById("btnAddVoucher");

  var vmVoucherNoWrap = document.getElementById("vmVoucherNoWrap");
  var vmVoucherNoDisplay = document.getElementById("vmVoucherNoDisplay");
  var vmVoucherDateWrap = document.getElementById("vmVoucherDateWrap");
  var vmLastGeneratedByWrap = document.getElementById("vmLastGeneratedByWrap");
  var vmLastGeneratedByEl = document.getElementById("vmLastGeneratedBy");
  var vmLastGeneratedAtEl = document.getElementById("vmLastGeneratedAt");
  var vmGenerationHistoryDetails = document.getElementById(
    "vmGenerationHistoryDetails",
  );
  var vmGenerationHistoryList = document.getElementById(
    "vmGenerationHistoryList",
  );
  var vmOtherRemarksWrap = document.getElementById("vmOtherRemarksWrap");
  var vmOtherRemarksInput = document.getElementById("vmOtherRemarks");

  var _vmSnapshot = null;

  function vmSnapshotForm() {
    var snap = {};
    vmFieldIds.forEach(function (id) {
      var el = document.getElementById(id);
      snap[id] = el ? el.value : "";
    });
    ["vmSuffixOther", "vmJuniorHsOther", "vmPreferredHsOther"].forEach(function (id) {
      var el = document.getElementById(id);
      snap[id] = el ? el.value : "";
    });
    return JSON.stringify(snap);
  }

  var vmFieldIds = [
    "vmControlNo",
    "vmVoucherDate",
    "vmFirstName",
    "vmMiddleName",
    "vmLastName",
    "vmSuffix",
    "vmGender",
    "vmGwa",
    "vmRankNo",
    "vmContactNumber",
    "vmJuniorHs",
    "vmPreferredHs",
    "vmRemarks",
    "vmOtherRemarks",
    "vmEvaluatedBy" /* 'vmEligibility', */,
  ];
  var vmFieldToName = {
    vmControlNo: "control_no",
    vmEvaluatedBy: "evaluated_by",
    vmVoucherDate: "voucher_date",
    vmFirstName: "first_name",
    vmMiddleName: "middle_name",
    vmLastName: "last_name",
    vmSuffix: "suffix",
    vmGender: "gender",
    vmGwa: "gwa",
    vmRankNo: "rank_no",
    vmContactNumber: "contact_number",
    vmJuniorHs: "junior_high_school",
    vmPreferredHs: "preferred_senior_high_school",
    vmRemarks: "remarks_status",
    vmOtherRemarks: "other_remarks",
    // vmEligibility:   'eligibility_status',
  };

  function escapeHtml(v) {
    return String(v || "").replace(/[&<>"']/g, function (c) {
      return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      }[c];
    });
  }

  function vmFormatDateTime(value) {
    if (!value) return "";
    var normalized = String(value).replace(" ", "T");
    var d = new Date(normalized);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleString(undefined, {
      year: "numeric",
      month: "short",
      day: "2-digit",
      hour: "numeric",
      minute: "2-digit",
    });
  }

  function vmRenderGenerationHistory(student) {
    var history = Array.isArray(student.generation_history)
      ? student.generation_history
      : [];
    var latestAt = student.last_generated_at || student.generated_at || "";

    if (vmLastGeneratedByEl)
      vmLastGeneratedByEl.textContent = student.last_generated_by || "-";
    if (vmLastGeneratedAtEl)
      vmLastGeneratedAtEl.textContent = latestAt
        ? " | " + vmFormatDateTime(latestAt)
        : "";

    if (!vmGenerationHistoryDetails || !vmGenerationHistoryList) return;

    vmGenerationHistoryDetails.style.display = history.length > 0 ? "" : "none";
    vmGenerationHistoryDetails.open = false;

    if (!history.length) {
      vmGenerationHistoryList.innerHTML =
        '<div class="vm-generation-history-empty">No generation history yet.</div>';
      return;
    }

    vmGenerationHistoryList.innerHTML = history
      .map(function (item) {
        var by = item.generated_by || "-";
        var at = vmFormatDateTime(item.generated_at);
        return (
          '<div class="vm-generation-history-item">' +
          '<span class="vm-generation-history-date">' +
          escapeHtml(at) +
          "</span>" +
          '<span class="vm-generation-history-name">' +
          escapeHtml(by) +
          "</span>" +
          "</div>"
        );
      })
      .join("");
  }

  function vmShowAlert(msg, type, errors) {
    var toastMsg = escapeHtml(msg);
    if (errors && Object.keys(errors).length) {
      var fieldMsgs = Object.keys(errors).map(function (f) {
        return escapeHtml(f) + ": " + escapeHtml(errors[f]);
      });
      toastMsg += " — " + fieldMsgs.join("; ");
    }
    showToast(toastMsg, type || "error");
  }

  function vmUpdateCsrfToken(token) {
    if (!token) return;
    var metaValue = document.querySelector('meta[name="csrf-token-value"]');
    if (metaValue) metaValue.content = token;
    document.querySelectorAll('input[name^="csrf"]').forEach(function (input) {
      input.value = token;
    });
  }
  function vmClearAlert() {
    if (voucherModalAlert) voucherModalAlert.innerHTML = "";
  }

  function vmToggleOtherRemarks() {
    var remarksEl = document.getElementById("vmRemarks");
    var val = remarksEl ? String(remarksEl.value || "").toUpperCase() : "";
    var isOther = val === "OTHERS" || val === "INCOMPLETE";
    if (vmOtherRemarksWrap)
      vmOtherRemarksWrap.style.display = isOther ? "" : "none";
    if (vmOtherRemarksInput) {
      vmOtherRemarksInput.required = isOther;
      vmOtherRemarksInput.disabled = !isOther;
      if (!isOther) vmOtherRemarksInput.value = "";
    }
    if (typeof scanRequiredLabels === "function") scanRequiredLabels(voucherModal);
  }

  function vmSetFieldValue(el, value) {
    if (!el) return;
    var v = value === null || value === undefined ? "" : value;
    if (el.tagName === "SELECT" && el.classList.contains("js-school-select")) {
      // Select2 needs the option to exist before setting; add it on the fly.
      if (
        v &&
        !el.querySelector(
          'option[value="' +
            (window.CSS && CSS.escape ? CSS.escape(v) : v) +
            '"]',
        )
      ) {
        var opt = document.createElement("option");
        opt.value = v;
        opt.textContent = v;
        el.appendChild(opt);
      }
      el.value = v;
      if (window.jQuery) $(el).trigger("change.select2");
    } else {
      el.value = v;
    }
  }

  /*
  var remarksOptionsByEligibility = {
    eligible:     ['COMPLETE'],
    not_eligible: ['INCOMPLETE', 'OTHERS'],
  };

  function vmUpdateRemarksOptions(eligValue) {
    var remarksEl = document.getElementById('vmRemarks');
    if (!remarksEl) return;
    var current = remarksEl.value;
    var noElig  = !eligValue;
    var allowed = remarksOptionsByEligibility[eligValue] || [];
    var newVal  = allowed.includes(current) ? current : '';

    if (window.jQuery && $(remarksEl).hasClass('select2-hidden-accessible')) {
      $(remarksEl).empty().append('<option></option>');
      allowed.forEach(function (v) {
        $(remarksEl).append(new Option(v, v, false, v === newVal));
      });
      $(remarksEl).val(newVal).prop('disabled', noElig).trigger('change');
    } else {
      remarksEl.innerHTML = '<option></option>';
      allowed.forEach(function (v) {
        var opt = document.createElement('option');
        opt.value = v; opt.textContent = v;
        if (v === newVal) opt.selected = true;
        remarksEl.appendChild(opt);
      });
      remarksEl.value    = newVal;
      remarksEl.disabled = noElig;
    }
  }

  // Use jQuery delegation so Select2's triggered change events are caught.
  function bindEligibilityChange() {
    if (window.jQuery) {
      $(document).off('change.vmElig', '#vmEligibility')
                 .on('change.vmElig',  '#vmEligibility', function () {
        vmUpdateRemarksOptions(this.value);
      });
    } else {
      document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'vmEligibility') vmUpdateRemarksOptions(e.target.value);
      });
    }
  }
  bindEligibilityChange();
  */

  function vmClearFields() {
    document.getElementById("vmStudentId").value = "";
    vmFieldIds.forEach(function (id) {
      vmSetFieldValue(document.getElementById(id), "");
    });
    if (typeof resetOtherInput === "function") {
      resetOtherInput("vmSuffix", "vmSuffixOtherWrap", "vmSuffixOther");
      resetOtherInput("vmJuniorHs", "vmJuniorHsOtherWrap", "vmJuniorHsOther");
      resetOtherInput("vmPreferredHs", "vmPreferredHsOtherWrap", "vmPreferredHsOther");
    }
    var vmEvaluatedByRo = document.getElementById("vmEvaluatedByRo");
    if (vmEvaluatedByRo) vmEvaluatedByRo.textContent = "—";
    if (vmLastGeneratedByEl) vmLastGeneratedByEl.textContent = "-";
    if (vmLastGeneratedAtEl) vmLastGeneratedAtEl.textContent = "";
    if (vmGenerationHistoryDetails) vmGenerationHistoryDetails.open = false;
    if (vmGenerationHistoryList) vmGenerationHistoryList.innerHTML = "";
    vmToggleOtherRemarks();
  }

  function vmPopulateFields(student) {
    document.getElementById("vmStudentId").value = student.student_id || "";
    vmFieldIds.forEach(function (id) {
      if (id === "vmSuffix") return; // handled below via applySelectOrOther
      var el = document.getElementById(id);
      if (!el) return;
      vmSetFieldValue(el, student[vmFieldToName[id]]);
    });
    if (typeof applySelectOrOther === "function") {
      applySelectOrOther("vmSuffix", "vmSuffixOtherWrap", "vmSuffixOther", student.suffix || "");
    } else {
      var sfxEl = document.getElementById("vmSuffix");
      if (sfxEl) vmSetFieldValue(sfxEl, student.suffix || "");
    }
    var vmEvaluatedByRo = document.getElementById("vmEvaluatedByRo");
    if (vmEvaluatedByRo)
      vmEvaluatedByRo.textContent = student.evaluated_by || "—";
    vmToggleOtherRemarks();
  }

  function vmSetReadOnly(readOnly) {
    vmFieldIds.forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      if (
        el.tagName === "SELECT" &&
        el.classList.contains("js-school-select")
      ) {
        // <select> has no readOnly — disable instead. Form still submits the
        // value because Select2 keeps the option selected when re-enabled.
        el.disabled = readOnly;
        if (window.jQuery)
          $(el).prop("disabled", readOnly).trigger("change.select2");
      } else {
        el.readOnly = readOnly;
      }
    });
    voucherSubmitBtn.style.display = readOnly ? "none" : "inline-flex";
  }

  function vmApplyModeVisibility(mode) {
    var isView = mode === "view";
    if (vmVoucherNoWrap) vmVoucherNoWrap.style.display = isView ? "" : "none";
    if (vmVoucherDateWrap)
      vmVoucherDateWrap.style.display = "";
    if (vmLastGeneratedByWrap)
      vmLastGeneratedByWrap.style.display = isView ? "" : "none";
  }

  function rebuildSelectOptions(select, options, selected) {
    if (!select) return;
    while (select.firstChild) select.removeChild(select.firstChild);
    // Blank option so placeholder shows when nothing selected.
    select.appendChild(document.createElement("option"));
    var seen = {};
    options.forEach(function (item) {
      var value = "";
      var label = "";
      var acronym = "";
      if (item && typeof item === "object") {
        value = String(item.school_id || item.id || item.value || "");
        label = String(item.school_name || item.text || item.label || "");
        acronym = String(item.acronym || "");
      } else {
        value = String(item || "");
        label = value;
      }
      if (!value || seen[value]) return;
      seen[value] = true;
      var opt = document.createElement("option");
      opt.value = value;
      opt.textContent = acronym ? acronym + " - " + (label || value) : (label || value);
      if (acronym) opt.setAttribute("data-acronym", acronym);
      if (selected && value === String(selected)) opt.selected = true;
      select.appendChild(opt);
    });
    // If a selected value isn't in the list, add it as a custom option so
    // Select2 can display it (and so submit keeps the value).
    if (selected && !seen[selected]) {
      var opt = document.createElement("option");
      opt.value = selected;
      opt.textContent = selected;
      opt.selected = true;
      select.appendChild(opt);
    }
  }

  function initOrRefreshSelect2(select) {
    if (!select || typeof $ === "undefined" || !$.fn.select2) return;
    var $sel = $(select);
    if ($sel.hasClass("select2-hidden-accessible")) {
      $sel.trigger("change.select2");
      return;
    }
    $sel.select2({
      tags: true, // allow user to type a NEW school
      placeholder: select.dataset.placeholder || "Type or select",
      allowClear: true,
      width: "100%",
      dropdownParent: $("#voucherModal"),
      minimumResultsForSearch: select.dataset.noSearch === "1" ? Infinity : 0,
    });
  }

  function _appendOthersOption(sel) {
    if (!sel) return;
    if (!Array.from(sel.options).some(function (o) { return o.value === "__OTHER__"; })) {
      var opt = document.createElement("option");
      opt.value = "__OTHER__";
      opt.textContent = "OTHERS";
      sel.appendChild(opt);
    }
  }

  function loadSchoolOptions(selectedJhs, selectedShs) {
    var jhsSel = document.getElementById("vmJuniorHs");
    var shsSel = document.getElementById("vmPreferredHs");

    fetch(schoolOptionsUrl, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        rebuildSelectOptions(
          jhsSel,
          Array.isArray(data.jhs) ? data.jhs : [],
          selectedJhs || "",
        );
        rebuildSelectOptions(
          shsSel,
          Array.isArray(data.shs) ? data.shs : [],
          selectedShs || "",
        );
        _appendOthersOption(jhsSel);
        _appendOthersOption(shsSel);
        initOrRefreshSelect2(jhsSel);
        initOrRefreshSelect2(shsSel);
      })
      .catch(function () {
        _appendOthersOption(jhsSel);
        _appendOthersOption(shsSel);
        initOrRefreshSelect2(jhsSel);
        initOrRefreshSelect2(shsSel);
      });
  }

  // Init Select2 for the non-school dropdowns inside the modal (Suffix, Sex,
  // Remarks, Eligibility). Idempotent — safe to call every open.
  function initModalExtraSelects() {
    if (typeof window.initVsSelect2 === "function") {
      window.initVsSelect2(voucherModal);
    }
    if (typeof initOtherInput === "function") {
      initOtherInput("vmSuffix",     "vmSuffixOtherWrap",     "vmSuffixOther");
      initOtherInput("vmJuniorHs",   "vmJuniorHsOtherWrap",   "vmJuniorHsOther");
      initOtherInput("vmPreferredHs","vmPreferredHsOtherWrap","vmPreferredHsOther");
    }
  }

  function vmOpen(mode, studentId) {
    vmCurrentStudentId = studentId || null;
    _vmSnapshot = null;
    voucherModal.classList.remove("vm-view-mode");
    vmClearAlert();
    vmClearFields();
    vmApplyModeVisibility(mode);

    if (mode === "add") {
      voucherModalTitle.textContent = "Add Student";
      vmSubmitText.textContent = "Save";
      vmSetReadOnly(false);
      document.getElementById("vmVoucherDate").value = new Date()
        .toISOString()
        .slice(0, 10);
      loadSchoolOptions("", "");
      initModalExtraSelects();
      // vmUpdateRemarksOptions('');
      vmUpdateRequiredIndicators();
      voucherModal.style.display = "flex";
      return;
    }

    voucherModalTitle.textContent =
      mode === "edit" ? "Edit" : "View";
    vmSubmitText.textContent = "Update";
    vmSetReadOnly(mode === "view");
    initModalExtraSelects();
    voucherModal.style.display = "flex";

    fetch(fetchStudentUrl + "/" + studentId, ajaxOptions({ method: "GET" }))
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (data.status !== "success") {
          vmShowAlert(data.message || "Failed to load student.", "error");
          return;
        }
        vmPopulateFields(data.student);
        vmUpdateRequiredIndicators();
        if (mode === "edit") {
          setTimeout(function () { _vmSnapshot = vmSnapshotForm(); }, 100);
        }
        if (mode === "view") {
          if (vmVoucherNoDisplay)
            vmVoucherNoDisplay.textContent = data.student.voucher_no || "—";
          vmRenderGenerationHistory(data.student);
        }
        if (mode !== "view") {
          loadSchoolOptions(
            data.student.junior_high_school_id ||
              data.student.junior_high_school ||
              "",
            data.student.preferred_senior_high_school_id ||
              data.student.preferred_senior_high_school ||
              "",
          );
        }
        vmSetReadOnly(mode === "view");
      })
      .catch(function () {
        vmShowAlert("Failed to load student.", "error");
      });
  }

  function vmUpdateRequiredIndicators() {
    if (typeof scanRequiredLabels === 'function') scanRequiredLabels(voucherModal);
  }
  function vmBindRequiredIndicators() {}

  function vmClose() {
    voucherModal.style.display = "none";
    voucherModalForm && voucherModalForm.querySelectorAll(
      "select.select2-hidden-accessible[required]"
    ).forEach(function (sel) { sel.removeAttribute("style"); });
  }

  btnAddVoucher &&
    btnAddVoucher.addEventListener("click", function () {
      vmOpen("add");
    });
  voucherModalClose && voucherModalClose.addEventListener("click", vmClose);
  voucherModalCancel && voucherModalCancel.addEventListener("click", vmClose);
  voucherModal &&
    voucherModal.addEventListener("click", function (e) {
      if (e.target === voucherModal) vmClose();
    });
  if (window.jQuery) {
    $(document)
      .off("change.vmRemarks", "#vmRemarks")
      .on("change.vmRemarks", "#vmRemarks", vmToggleOtherRemarks);
  } else {
    document.addEventListener("change", function (e) {
      if (e.target && e.target.id === "vmRemarks") vmToggleOtherRemarks();
    });
  }

  // Event delegation — works for AJAX-rendered rows (server-side DataTables)
  document.addEventListener("click", function (e) {
    var btn = e.target.closest(".js-voucher-action");
    if (!btn) return;
    vmOpen(btn.getAttribute("data-mode"), btn.getAttribute("data-id"));
  });

  vmBindRequiredIndicators();
  window.vmOpen = vmOpen;

  voucherModalForm &&
    voucherModalForm.addEventListener("submit", function (e) {
      e.preventDefault();
      vmClearAlert();
      vmToggleOtherRemarks();

      if (!voucherModalForm.checkValidity()) {
        // Text/number inputs: use native reportValidity() — tooltip anchors correctly.
        var firstVisibleInvalid = voucherModalForm.querySelector(
          ":invalid:not(.select2-hidden-accessible)"
        );
        if (firstVisibleInvalid) {
          firstVisibleInvalid.reportValidity();
          return;
        }
        // Select2 selects: native select is clipped to 1px so tooltip
        // appears in wrong place. Temporarily pin it via position:fixed to
        // the bottom of the Select2 container, then call reportValidity().
        var invalidSelects = voucherModalForm.querySelectorAll(
          "select.select2-hidden-accessible[required]"
        );
        for (var si = 0; si < invalidSelects.length; si++) {
          var sel = invalidSelects[si];
          if (sel.value) continue;
          var s2container = sel.nextElementSibling;
          var rect = s2container
            ? s2container.getBoundingClientRect()
            : sel.getBoundingClientRect();
          sel.setAttribute(
            "style",
            "position:fixed!important;top:" +
              Math.round(rect.bottom - 1) +
              "px!important;left:" +
              Math.round(rect.left) +
              "px!important;width:" +
              Math.round(rect.width) +
              "px!important;height:1px!important;" +
              "opacity:0!important;pointer-events:none!important;" +
              "clip:auto!important;clip-path:none!important;" +
              "-webkit-clip-path:none!important;overflow:visible!important;" +
              "margin:0!important;border:0!important;z-index:9999!important;"
          );
          sel.reportValidity();
          return;
        }
        return;
      }

      if (_vmSnapshot !== null && vmCurrentStudentId && vmSnapshotForm() === _vmSnapshot) {
        vmClose();
        showToast("No changes were made.", "info");
        return;
      }

      if (typeof refreshCsrfToken === "function") refreshCsrfToken();
      var fd = new FormData(voucherModalForm);
      var csrf = getCsrfToken && getCsrfToken();
      if (csrf && csrf.name && !fd.get(csrf.name))
        fd.append(csrf.name, csrf.token);

      voucherSubmitBtn.disabled = true;
      vmSubmitText.style.display = "none";
      vmSubmitSpinner.style.display = "inline-block";

      fetch(saveStudentUrl, ajaxOptions({ method: "POST", body: fd }))
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          vmUpdateCsrfToken(data.csrf_token);
          if (typeof refreshCsrfToken === "function") refreshCsrfToken();
          if (data.status === "success") {
            vmClose();
            toastAndReload(data.message || "Student saved successfully.", "success");
            return;
          }
          vmShowAlert(data.message || "Save failed.", "error", data.errors);
        })
        .catch(function () {
          vmShowAlert("An error occurred while saving.", "error");
        })
        .finally(function () {
          voucherSubmitBtn.disabled = false;
          vmSubmitText.style.display = "inline";
          vmSubmitSpinner.style.display = "none";
        });
    });
});
