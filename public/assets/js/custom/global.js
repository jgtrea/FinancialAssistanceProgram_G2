/* ============================================================
   GLOBAL UTILITIES
   ============================================================ */

function getCsrfToken() {
  const metaName = document.querySelector('meta[name="csrf-token-name"]');
  const metaValue = document.querySelector('meta[name="csrf-token-value"]');
  const cookieMatch = document.cookie.match(
    /(?:^|;\s*)csrf_cookie_name=([^;]+)/,
  );
  const cookieToken = cookieMatch ? decodeURIComponent(cookieMatch[1]) : "";

  if (metaName && metaValue) {
    if (cookieToken && metaValue.content !== cookieToken) {
      metaValue.content = cookieToken;
    }
    return { name: metaName.content, token: cookieToken || metaValue.content };
  }

  const input = document.querySelector('input[name^="csrf"]');
  if (input) return { name: input.name, token: cookieToken || input.value };

  return { name: "csrf_token", token: "" };
}

function refreshCsrfToken() {
  const cookieMatch = document.cookie.match(
    /(?:^|;\s*)csrf_cookie_name=([^;]+)/,
  );
  if (!cookieMatch) return;
  const newToken = decodeURIComponent(cookieMatch[1]);
  document.querySelectorAll('input[name^="csrf"]').forEach((input) => {
    input.value = newToken;
  });
  const metaValue = document.querySelector('meta[name="csrf-token-value"]');
  if (metaValue) metaValue.content = newToken;
}

// Each concurrent PDF job gets its OWN toast node, stacked in a shared
// container. Previously every job reused a single #pdfToast element, so the
// first job to finish called remove() and deleted the shared node out from
// under any still-running job (e.g. a small job downloading wiped the toast of
// a 50k batch still generating). `key` scopes the node; pass a stable unique
// value per job. Calling again with the same key reuses that job's node.
//
// `job` ({ jobId, statusUrl }) scopes this toast's Status button to its own
// job, so clicking Status on one toast opens the modal for THAT job rather than
// whichever job happens to be newest. May be set later via the returned
// setJob() once the job_id is known (the generate request returns it async).
function showPdfToast(message, key, job, opts) {
  // How long a finished toast lingers before auto-removing, so the user can
  // still hit the manual Download link if the automatic download didn't fire.
  const FINISHED_LINGER_MS = 5 * 60 * 1000;
  let currentJob = job || null;
  // opts.hideStatus / opts.hideDownload — hide the PDF-only buttons so the same
  // toast can show progress for non-PDF jobs (e.g. archive) without a Status
  // button that would open the empty PDF-status modal.
  opts = opts || {};

  let stack = document.getElementById("pdfToastStack");
  if (!stack) {
    stack = document.createElement("div");
    stack.id = "pdfToastStack";
    stack.style.cssText = [
      "position:fixed",
      "bottom:24px",
      "right:24px",
      "z-index:9999",
      "display:flex",
      "flex-direction:column-reverse",
      "gap:10px",
    ].join(";");
    document.body.appendChild(stack);
  }

  const nodeId =
    "pdfToast-" +
    (key != null
      ? key
      : "t" + Date.now() + Math.random().toString(36).slice(2));
  let toast = document.getElementById(nodeId);
  if (!toast) {
    toast = document.createElement("div");
    toast.id = nodeId;
    toast.style.cssText = [
      "background:#1e293b",
      "color:#f8fafc",
      "border-radius:10px",
      "padding:14px 20px",
      "display:flex",
      "align-items:center",
      "gap:12px",
      "box-shadow:0 4px 20px rgba(0,0,0,.35)",
      "font-size:14px",
      "min-width:220px",
      "transition:opacity .3s ease",
    ].join(";");
    stack.appendChild(toast);
  }
  toast.style.opacity = "1";
  // No Status button — both the generate and archive toasts now just show the
  // message + (for downloads) a Download link + close. The Download anchor uses
  // margin-left:auto so it sits flush right when revealed.
  toast.innerHTML =
    '<div class="vs-spinner" style="width:16px;height:16px;flex-shrink:0"></div>' +
    '<span class="pdfToastMsg">' +
    message +
    "</span>" +
    '<a class="pdfToastDownload" style="display:none;margin-left:auto;background:#16a34a;color:#fff;border-radius:6px;padding:4px 10px;font-size:12px;text-decoration:none;cursor:pointer">Download</a>' +
    '<button type="button" class="pdfToastClose" style="margin-left:auto;background:none;color:#94a3b8;border:none;font-size:16px;line-height:1;cursor:pointer;padding:0 2px">&times;</button>';

  if (opts.hideDownload) {
    const db = toast.querySelector(".pdfToastDownload");
    if (db) db.remove();
  }

  function removeToast() {
    toast.style.opacity = "0";
    setTimeout(function () {
      if (toast.parentNode) toast.remove();
      // Drop the empty stack so it doesn't linger as a stray fixed element.
      if (stack.parentNode && stack.children.length === 0) stack.remove();
    }, 300);
  }

  const closeBtn = toast.querySelector(".pdfToastClose");
  if (closeBtn) closeBtn.addEventListener("click", removeToast);

  return {
    // update(msg)                 → change the text only.
    // update(msg, true)           → mark done, fade out after 2s (no download).
    // update(msg, true, url)      → mark done, reveal a manual Download link,
    //                               and linger ~5 min before auto-removing.
    update: function (msg, done, downloadUrl) {
      const el = toast.querySelector(".pdfToastMsg");
      if (el) el.textContent = msg;
      if (!done) return;

      const spinner = toast.querySelector(".vs-spinner");
      if (spinner) spinner.style.display = "none";

      if (downloadUrl) {
        const dl = toast.querySelector(".pdfToastDownload");
        if (dl) {
          dl.href = downloadUrl;
          dl.style.display = "inline-block";
        }
        setTimeout(removeToast, FINISHED_LINGER_MS);
      } else {
        setTimeout(removeToast, 2000);
      }
    },
    setJob: function (j) {
      currentJob = j || currentJob;
    },
    remove: removeToast,
  };
}

// Toast types:
//   'success' (green)  — add, update, import, export, activate/restore
//   'error'   (red)    — deactivate, archive, failures
//   'info'    (blue)   — cannot-do notices ("select at least one...", limits, etc.)
//   'warning' (amber)  — edge-case caution (rarely used)
function showToast(message, type, opts) {
  opts = opts || {};
  var bgMap = { success: "#15803d", error: "#b91c1c", info: "#b91c1c", warning: "#b45309" };
  var bg = bgMap[type] || bgMap.info;
  var autoMs = (type === "success") ? 10000 : 15000;

  var stack = document.getElementById("pdfToastStack");
  if (!stack) {
    stack = document.createElement("div");
    stack.id = "pdfToastStack";
    stack.style.cssText = "position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column-reverse;gap:10px;max-width:360px;";
    document.body.appendChild(stack);
  }

  var toast = document.createElement("div");
  toast.style.cssText = "background:" + bg + ";color:#fff;border-radius:10px;padding:14px 20px;" +
    "display:flex;align-items:center;gap:12px;box-shadow:0 4px 20px rgba(0,0,0,.35);" +
    "font-size:14px;min-width:220px;max-width:360px;transition:opacity .3s ease;";
  toast.innerHTML = '<span style="flex:1">' + message + '</span>' +
    '<button type="button" style="background:none;color:rgba(255,255,255,.6);border:none;' +
    'font-size:18px;line-height:1;cursor:pointer;padding:0 2px;flex-shrink:0">&times;</button>';
  stack.appendChild(toast);

  function removeToast() {
    toast.style.opacity = "0";
    setTimeout(function () {
      if (toast.parentNode) toast.remove();
      if (stack.parentNode && stack.children.length === 0) stack.remove();
    }, 300);
  }

  toast.querySelector("button").addEventListener("click", removeToast);
  if (!opts.persist) setTimeout(removeToast, autoMs);
}
window.showToast = showToast;

// Persist a toast across a page reload via sessionStorage.
// Call instead of location.reload() when you need to show a result after reload.
window.toastAndReload = function (msg, type) {
  try { sessionStorage.setItem("__pendingToast", JSON.stringify({ msg: msg, type: type })); } catch (e) {}
  location.reload();
};

// Fire any pending post-reload toast immediately on load.
(function () {
  var raw = sessionStorage.getItem("__pendingToast");
  if (!raw) return;
  try {
    var p = JSON.parse(raw);
    sessionStorage.removeItem("__pendingToast");
    function fire() { showToast(p.msg, p.type); }
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fire);
    } else {
      fire();
    }
  } catch (e) {}
})();

function showAlert(message, type, opts) {
  showToast(message, type || "success", opts || {});
}

function scanRequiredLabels(container) {
  (container || document).querySelectorAll('label.form-label.required[for]').forEach(function (lbl) {
    var el = document.getElementById(lbl.getAttribute('for'));
    if (!el) return;
    el.value ? lbl.classList.add('vm-filled') : lbl.classList.remove('vm-filled');
  });
}

document.addEventListener('input', function (e) {
  if (!e.target || !e.target.id) return;
  var lbl = document.querySelector('label.form-label.required[for="' + e.target.id + '"]');
  if (!lbl) return;
  e.target.value ? lbl.classList.add('vm-filled') : lbl.classList.remove('vm-filled');
});
document.addEventListener('change', function (e) {
  if (!e.target || !e.target.id) return;
  var lbl = document.querySelector('label.form-label.required[for="' + e.target.id + '"]');
  if (!lbl) return;
  e.target.value ? lbl.classList.add('vm-filled') : lbl.classList.remove('vm-filled');
});

function ajaxOptions(options = {}) {
  refreshCsrfToken();
  return {
    ...options,
    headers: {
      "X-Requested-With": "XMLHttpRequest",
      ...(options.headers || {}),
    },
  };
}

function initPasswordToggles() {
  document.querySelectorAll(".vs-pw-toggle").forEach((btn) => {
    btn.addEventListener("click", function () {
      const field = document.getElementById(this.dataset.target || "password");
      if (field) field.type = field.type === "password" ? "text" : "password";
    });
  });
}

function initAlertDismiss() {
  document.querySelectorAll(".vs-alert:not(.vs-alert-static)").forEach((el) => {
    if (el.querySelector(".vs-alert-dismiss")) return;
    var btn = document.createElement("button");
    btn.type = "button";
    btn.className = "vs-alert-dismiss";
    btn.setAttribute("aria-label", "Dismiss");
    btn.textContent = "×";
    btn.addEventListener("click", function () { el.remove(); });
    el.appendChild(btn);
  });
}

/**
 * Initialise Select2 on every `.js-filter-select` and `.js-school-select`.
 * Idempotent — re-runs safely after AJAX-rebuilt modals. Anchors the Select2
 * dropdown inside the nearest .vs-modal so it stacks above the modal overlay.
 */
window.initVsSelect2 = function initVsSelect2(root) {
  if (typeof $ === "undefined" || !$.fn.select2) return;
  var scope = root || document;
  $(scope)
    .find(".js-filter-select, .js-school-select")
    .each(function () {
      var $el = $(this);
      if ($el.hasClass("select2-hidden-accessible")) return;
      var $modal = $el.closest(".vs-modal-overlay");
      var noSearch = this.dataset.noSearch === "1";
      var isSchoolSelect = $el.hasClass("js-school-select");
      var allowTags = isSchoolSelect || this.dataset.tags === "1";
      $el.select2({
        tags: allowTags,
        placeholder: this.dataset.placeholder || "All",
        allowClear: true,
        width: "100%",
        dropdownParent: $modal.length ? $modal : $(document.body),
        minimumResultsForSearch: noSearch ? Infinity : 0,
        matcher: function (params, data) {
          if (!params.term || params.term.trim() === "") return data;
          var term = params.term.toUpperCase();
          var text = (data.text || "").toUpperCase();
          var acronym = ($(data.element).data("acronym") || "").toUpperCase();
          if (text.indexOf(term) !== -1 || acronym.indexOf(term) !== -1)
            return data;
          return null;
        },
        createTag: allowTags
          ? function (params) {
              var term = params.term.trim();
              if (!term) return null;
              // Prevent duplicate: check if term matches existing option (case-insensitive).
              var termUpper = term.toUpperCase();
              var isDuplicate = false;
              $(this.options.dropdownParent || "body")
                .find("select")
                .addBack("select")
                .each(function () {
                  // Check against the actual select element's options.
                });
              // Check the select element's current options.
              var $select = $el;
              $select.find("option").each(function () {
                if (
                  ($(this).val() || "").toUpperCase() === termUpper ||
                  ($(this).text() || "").toUpperCase() === termUpper
                ) {
                  isDuplicate = true;
                }
              });
              if (isDuplicate) return null;
              return { id: term, text: term, newTag: true };
            }
          : undefined,
      });
    });
};

document.addEventListener("DOMContentLoaded", function () {
  if (typeof window.initVsSelect2 === "function")
    window.initVsSelect2(document);
  initAlertDismiss();
});

/* ── "Others" select pattern helpers ────────────────────────────────────── */
// Selects that support a custom-value "Others" option carry data-field-name="fieldname".
// When value="__OTHER__", the name attr swaps to the text input so FormData
// submits the typed value under the original field name.

window.initOtherInput = function (selectId, wrapperId, inputId) {
  var sel  = document.getElementById(selectId);
  var wrap = document.getElementById(wrapperId);
  var inp  = document.getElementById(inputId);
  if (!sel || !wrap || !inp) return;
  var fieldName = sel.dataset.fieldName || '';

  function toggle() {
    var isOther = sel.value === '__OTHER__';
    wrap.style.display = isOther ? '' : 'none';
    if (isOther) {
      if (fieldName) { sel.removeAttribute('name'); inp.name = fieldName; }
      setTimeout(function () { inp.focus(); }, 0);
    } else {
      if (fieldName) { sel.name = fieldName; inp.removeAttribute('name'); }
      inp.value = '';
    }
  }

  sel.addEventListener('change', toggle);
  if (window.jQuery) jQuery(sel).on('change.select2', toggle);
};

window.applySelectOrOther = function (selectId, wrapperId, inputId, value) {
  var sel  = document.getElementById(selectId);
  var wrap = document.getElementById(wrapperId);
  var inp  = document.getElementById(inputId);
  if (!sel) return;
  var fieldName = sel.dataset.fieldName || '';
  var val = value || '';
  var hasOpt = val === '' || Array.from(sel.options).some(function (o) {
    return o.value === val && o.value !== '__OTHER__';
  });
  if (val && !hasOpt) {
    sel.value = '__OTHER__';
    if (wrap) wrap.style.display = '';
    if (inp && fieldName) { inp.name = fieldName; inp.value = val; }
    if (fieldName) sel.removeAttribute('name');
  } else {
    sel.value = val;
    if (wrap) wrap.style.display = 'none';
    if (inp) { inp.removeAttribute('name'); inp.value = ''; }
    if (fieldName) sel.name = fieldName;
  }
  if (window.jQuery) jQuery(sel).trigger('change.select2');
};

window.resetOtherInput = function (selectId, wrapperId, inputId) {
  window.applySelectOrOther(selectId, wrapperId, inputId, '');
};

// Allow Bootstrap dropdowns to overflow .vs-card (overflow:hidden) while open.
document.addEventListener("show.bs.dropdown", function (e) {
  e.target.closest(".vs-card")?.classList.add("vs-card--dropdown-open");
});
document.addEventListener("hidden.bs.dropdown", function (e) {
  e.target.closest(".vs-card")?.classList.remove("vs-card--dropdown-open");
});
