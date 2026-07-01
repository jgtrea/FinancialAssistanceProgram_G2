/* ============================================================
   DATATABLE SHARED INIT — generic table setup + VS search helpers
   ============================================================ */

window.VS = window.VS || {};

window.VS.dtMobileQuery = "(max-width: 767.98px)";
window.VS.mobilePrimaryColumnMeta =
  window.VS.mobilePrimaryColumnMeta || new WeakMap();

window.VS.cacheMobilePrimaryColumns = function cacheMobilePrimaryColumns(
  table,
) {
  if (!table || window.VS.mobilePrimaryColumnMeta.has(table)) return;

  const meta = Array.from(table.querySelectorAll("thead th")).map((th) => ({
    title: (th.textContent || "").trim(),
    isCheck: th.classList.contains("vs-th-check"),
    isHidden: !!(th.style && th.style.display === "none"),
  }));

  window.VS.mobilePrimaryColumnMeta.set(table, meta);
};

window.VS.isMobileTableMode = function isMobileTableMode(table) {
  if (!table || !table.classList.contains("vs-mobile-primary")) return false;
  return window.matchMedia
    ? window.matchMedia(window.VS.dtMobileQuery).matches
    : window.innerWidth <= 767;
};

window.VS.mobilePrimaryColumnDefs = function mobilePrimaryColumnDefs(
  table,
  mobile,
) {
  if (!table || !table.classList.contains("vs-mobile-primary")) return [];
  window.VS.cacheMobilePrimaryColumns(table);

  const isMobile =
    typeof mobile === "boolean" ? mobile : window.VS.isMobileTableMode(table);

  const headers = Array.from(table.querySelectorAll("thead th"));
  headers.forEach((th) => {
    th.classList.remove("all");
    th.classList.remove("none");
  });

  if (!isMobile) return [];

  const primary = parseInt(table.dataset.mobilePrimary || "-1", 10);
  if (primary < 0) return [];
  const checkTargets = (window.VS.mobilePrimaryColumnMeta.get(table) || [])
    .map((meta, index) => (meta.isCheck ? index : -1))
    .filter((index) => index >= 0);
  const visibleTargets = [primary, ...checkTargets];

  const detailTargets = headers
    .map((th, index) => index)
    .filter((index) => !visibleTargets.includes(index));

  if (headers[primary]) headers[primary].classList.add("all");
  checkTargets.forEach((index) => {
    if (headers[index]) headers[index].classList.add("all");
  });
  detailTargets.forEach((index) => {
    if (headers[index]) headers[index].classList.add("none");
  });

  return [
    {
      visible: true,
      className: "all dtr-control",
      orderable: true,
      targets: [primary],
    },
    ...(checkTargets.length
      ? [
          {
            visible: true,
            className: "all vs-mobile-select-cell",
            orderable: false,
            targets: checkTargets,
          },
        ]
      : []),
    { visible: false, className: "none", targets: detailTargets },
  ];
};

window.VS.mobilePrimaryDetailsHtml = function mobilePrimaryDetailsHtml(
  table,
  dt,
  rowData,
) {
  window.VS.cacheMobilePrimaryColumns(table);

  const columnMeta = window.VS.mobilePrimaryColumnMeta.get(table) || [];
  const primary = parseInt(table.dataset.mobilePrimary || "-1", 10);
  const settings = dt && dt.settings ? dt.settings()[0] : null;
  const columns = settings && settings.aoColumns ? settings.aoColumns : [];
  const escapeHtml = (value) =>
    String(value).replace(
      /[&<>"']/g,
      (char) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#039;",
        })[char],
    );
  const attrLabel = (html) => {
    const match = String(html).match(
      /\b(?:aria-label|title)=["']([^"']+)["']/i,
    );
    return match ? match[1].trim() : "";
  };
  const valueForColumn = (index) => {
    if (Array.isArray(rowData)) return rowData[index];
    if (!rowData || typeof rowData !== "object") return "";
    const dataKey = columns[index] && columns[index].mData;
    if (
      typeof dataKey === "string" &&
      Object.prototype.hasOwnProperty.call(rowData, dataKey)
    ) {
      return rowData[dataKey];
    }
    return "";
  };

  const html = columns
    .map((column, index) => {
      const meta = columnMeta[index] || {};
      const title = meta.title || "";
      const rawData = valueForColumn(index);
      const raw = rawData == null ? "" : String(rawData).trim();
      const text = raw
        .replace(/<[^>]*>/g, "")
        .replace(/\s+/g, " ")
        .trim();
      const hasUsefulHtml = /<(img|button|a|span|div|input|select|svg)\b/i.test(
        raw,
      );
      const label = !text ? attrLabel(raw) : "";
      const value = label
        ? '<span class="vs-mobile-detail-inline">' +
          raw +
          "<span>" +
          escapeHtml(label) +
          "</span></span>"
        : raw;

      if (index === primary) return "";
      if (meta.isCheck) return "";
      if (meta.isHidden || (column.bVisible === false && index !== primary)) {
        const dataKey = column.mData;
        if (typeof dataKey === "string" && /sort$/i.test(dataKey)) return "";
        if (!title) return "";
      }
      if (!title) return "";
      if (!raw || (!text && !hasUsefulHtml)) return "";

      return (
        '<div class="vs-mobile-detail-item">' +
        '<div class="vs-mobile-detail-label">' +
        escapeHtml(title) +
        "</div>" +
        '<div class="vs-mobile-detail-value">' +
        value +
        "</div>" +
        "</div>"
      );
    })
    .join("");

  return html ? '<div class="vs-mobile-detail-grid">' + html + "</div>" : "";
};

window.VS.bindMobilePrimaryDetails = function bindMobilePrimaryDetails(
  table,
  getDt,
) {
  if (!table || table.dataset.vsMobileDetailsBound === "1") return;
  table.dataset.vsMobileDetailsBound = "1";

  table.addEventListener(
    "click",
    (event) => {
      const control = event.target.closest("td.dtr-control");
      if (!control || !table.contains(control)) return;

      const dt = getDt && getDt();
      if (!dt || !window.VS.isMobileTableMode(table)) return;

      event.preventDefault();
      event.stopPropagation();

      const tr = control.closest("tr");
      const row = dt.row(tr);
      if (!row || !row.node()) return;

      if (row.child.isShown()) {
        row.child.hide();
        tr.classList.remove("parent");
        return;
      }

      const html = window.VS.mobilePrimaryDetailsHtml(table, dt, row.data());
      if (!html) return;

      row.child(html).show();
      tr.classList.add("parent");
    },
    true,
  );
};

function initGenericDataTables() {
  if (!window.jQuery || !$.fn.DataTable) return;

  document.querySelectorAll("table.js-data-table").forEach((table) => {
    if (table.dataset.vsGenericDtBound === "1") return;
    table.dataset.vsGenericDtBound = "1";

    const state = {
      dt: null,
      mode: null,
    };

    function parseJsonDataset(name, fallback) {
      if (!table.dataset[name]) return fallback;
      try {
        return JSON.parse(table.dataset[name]);
      } catch (e) {
        return fallback;
      }
    }

    function nonOrderableTargets() {
      return Array.from(table.querySelectorAll("thead th"))
        .map((th, index) => {
          const isActions =
            th.classList.contains("actions-column") ||
            th.textContent.trim().toLowerCase() === "actions";
          const isCheckCol = th.classList.contains("vs-th-check");
          return isActions || isCheckCol ? index : -1;
        })
        .filter((index) => index >= 0);
    }

    function resetChildRows() {
      table
        .querySelectorAll("tbody tr.parent")
        .forEach((row) => row.classList.remove("parent"));
      table.querySelectorAll("tbody tr.child").forEach((row) => row.remove());
    }

    function buildDataTable() {
      const mobile = window.VS.isMobileTableMode(table);
      const mode = mobile ? "mobile" : "desktop";
      if (state.dt && state.mode === mode) return;

      if (state.dt) {
        state.dt.rows().every(function () {
          if (this.child && this.child.isShown()) this.child.hide();
        });
        state.dt.destroy();
        state.dt = null;
        resetChildRows();
      } else if ($.fn.DataTable.isDataTable(table)) {
        $(table).DataTable().destroy();
        resetChildRows();
      }

      const order = parseJsonDataset("order", []);
      const extraColDefs = parseJsonDataset("colDefs", []);
      const mobileColDefs = window.VS.mobilePrimaryColumnDefs(table, mobile);
      const noOrderTargets = nonOrderableTargets();
      const mergedColDefs = [
        ...(noOrderTargets.length
          ? [{ orderable: false, targets: noOrderTargets }]
          : []),
        ...mobileColDefs,
        ...extraColDefs,
      ];
      const hasPageSearch = !!table.dataset.pageSearch;

      state.mode = mode;
      state.dt = $(table).DataTable({
        dom: window.VS.dtHeaderDom(hasPageSearch) + window.VS.dtBodyDom,
        pageLength: 10,
        lengthMenu: window.VS.dtLengthMenu,
        responsive: false,
        autoWidth: false,
        processing: true,
        order,
        columnDefs: mergedColDefs,
        language: window.VS.dtLanguage({
          searchPlaceholder: table.dataset.searchPlaceholder || "Search...",
          emptyTable: table.dataset.emptyText || "No records found.",
        }),
      });

      window.VS.bindCustomLengthInput(state.dt);

      if (table.classList.contains("vs-mobile-primary")) {
        const wrapper = state.dt.table().container();
        if (wrapper) wrapper.classList.add("vs-mobile-primary-wrapper");
      }

      if (hasPageSearch) {
        const slot = state.dt
          .table()
          .container()
          .querySelector(".vs-dt-search-slot");
        if (slot) {
          const pageInput = document.createElement("input");
          pageInput.type = "text";
          pageInput.id = table.dataset.pageSearch;
          pageInput.className = "vs-input vs-page-search";
          pageInput.placeholder = "Enter keyword to search this page";
          pageInput.style.maxWidth = "260px";
          slot.parentElement.insertBefore(pageInput, slot);
          slot.remove();
          window.VS.bindCurrentPageSearch(state.dt, pageInput);
        }
      }
    }

    buildDataTable();
    window.VS.bindMobilePrimaryDetails(table, () => state.dt);

    const breakpoint = window.matchMedia
      ? window.matchMedia(window.VS.dtMobileQuery)
      : null;
    if (table.classList.contains("vs-mobile-primary")) {
      if (breakpoint && breakpoint.addEventListener) {
        breakpoint.addEventListener("change", buildDataTable);
      } else if (breakpoint && breakpoint.addListener) {
        breakpoint.addListener(buildDataTable);
      } else {
        window.addEventListener("resize", buildDataTable);
      }
    }
  });
}

// ── Shared DataTable config — consume in voucher.js and any future table ──────

// Body + footer DOM (table rows + info/pagination). Same for all tables.
// vs-dt-table-row gets overflow-x:auto so only the table scrolls, not the search header.
window.VS.dtBodyDom =
  "<'vs-dt-table-row row'<'col-sm-12'tr>>" +
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
  return Object.assign(
    {
      search: "",
      lengthMenu: "Show _MENU_ entries",
      info: "Showing _START_ to _END_ of _TOTAL_",
      paginate: { previous: "&#8249;", next: "&#8250;" },
      processing: "Loading...",
    },
    overrides || {},
  );
};

// Standard length menus.
window.VS.dtLengthMenu = [
  [10, 25, 50, 100, -1],
  [10, 25, 50, 100, "All"],
];
window.VS.dtLengthMenuSS = [
  [10, 25, 50, 100, 250],
  [10, 25, 50, 100, 250],
]; // server-side

// Replaces the DataTables length <select> with a typable <input type="number">.
window.VS.bindCustomLengthInput = function bindCustomLengthInput(dt) {
  if (!dt) return;
  const container = dt.table().container();
  if (!container) return;
  const lengthWrap = container.querySelector(".dataTables_length");
  if (!lengthWrap) return;

  const currentLen = dt.page.len();
  const label = document.createElement("label");
  label.className = "vs-length-label";
  const numInput = document.createElement("input");
  numInput.type = "number";
  numInput.className = "vs-length-input";
  numInput.value = currentLen > 0 ? currentLen : 10;
  numInput.min = 1;
  numInput.max = 500;
  label.append("Show ", numInput, " entries");
  lengthWrap.innerHTML = "";
  lengthWrap.appendChild(label);

  function applyLen() {
    const v = parseInt(numInput.value, 10);
    if (!isNaN(v) && v > 0) dt.page.len(v).draw();
  }
  numInput.addEventListener("change", applyLen);
  numInput.addEventListener("keydown", function (e) {
    if (e.key === "Enter") applyLen();
  });
};

// Filters only the currently visible page of a DataTable.
window.VS.bindCurrentPageSearch = function bindCurrentPageSearch(dt, input) {
  if (!dt || !input) return;
  if (input.dataset.currentPageSearchBound === "1") return;
  input.dataset.currentPageSearchBound = "1";

  function normalize(value) {
    return (value || "").toString().toLowerCase().trim();
  }

  function applySearch() {
    const query = normalize(input.value);
    dt.rows({ page: "current" }).every(function () {
      const row = this.node();
      if (!row) return;
      const extra = normalize(row.dataset.searchExtra || "");
      const text = normalize(row.textContent) + " " + extra;
      row.style.display = !query || text.indexOf(query) !== -1 ? "" : "none";
    });
  }

  input.addEventListener("keydown", function (e) {
    if (e.key === "Enter") applySearch();
  });
  dt.on("draw.dt page.dt order.dt length.dt", applySearch);
};

// Filters across the full set of rows loaded into the DataTable (not just the
// visible page). Use this when the server has pre-loaded a capped slice and the
// in-table input should let the user search the whole slice.
window.VS.bindFullTableSearch = function bindFullTableSearch(dt, input) {
  if (!dt || !input) return;
  if (input.dataset.fullTableSearchBound === "1") return;
  input.dataset.fullTableSearchBound = "1";

  input.addEventListener("input", function () {
    dt.search(input.value).draw();
  });
};

document.addEventListener("DOMContentLoaded", initGenericDataTables);
