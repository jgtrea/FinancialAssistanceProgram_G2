/* ============================================================
   GLOBAL PDF STATUS MODAL — wired on every page so the toast
   Status button works after the user navigates away from the
   voucher listing. Modal markup lives in layouts/main.php.
   ============================================================ */

document.addEventListener("DOMContentLoaded", function () {
  const btnOpenStatus = document.getElementById("btnOpenStatus");
  const pdfStatusModal = document.getElementById("pdfStatusModal");
  if (!pdfStatusModal) return;

  const pdfStatusModalClose = document.getElementById("pdfStatusModalClose");
  const pdfStatusEmpty = document.getElementById("pdfStatusEmpty");
  const pdfStatusContent = document.getElementById("pdfStatusContent");
  const pdfStatusJobIdEl = document.getElementById("pdfStatusJobId");
  const pdfStatusBadge = document.getElementById("pdfStatusBadge");
  const pdfStatusProgress = document.getElementById("pdfStatusProgress");
  const pdfStatusErrorLine = document.getElementById("pdfStatusErrorLine");
  const pdfStatusError = document.getElementById("pdfStatusError");
  const pdfStatusDownloadWrap = document.getElementById(
    "pdfStatusDownloadWrap",
  );
  const pdfStatusDownload = document.getElementById("pdfStatusDownload");

  // When the user clicks Status on a SPECIFIC toast, that toast pins the modal
  // to its own job via openPdfStatusFor(). Cleared when the modal closes so the
  // next open falls back to the default newest-pending pick.
  let forcedJob = null;

  // Pick which job the modal shows. Multiple jobs can run at once (a big batch
  // still generating while a small one already finished). Priority:
  //   1. the job the user explicitly opened (forcedJob),
  //   2. the newest job still in the live pending list,
  //   3. the sticky last job (fallback when nothing is pending).
  // getLastJsonPdfJob() alone would pin the modal to an already-downloaded job
  // (server-purged → "not_found") while a real job runs.
  function pickModalJob() {
    if (forcedJob && forcedJob.jobId && forcedJob.statusUrl) {
      return forcedJob;
    }
    const pending =
      typeof getPendingPdfJobs === "function" ? getPendingPdfJobs() : [];
    if (pending && pending.length) {
      return pending.slice().sort(function (a, b) {
        return (b.startedAt || 0) - (a.startedAt || 0);
      })[0];
    }
    return getLastJsonPdfJob();
  }

  async function refreshPdfStatusModal() {
    const last = pickModalJob();
    if (!last || !last.jobId || !last.statusUrl) {
      if (pdfStatusEmpty) pdfStatusEmpty.style.display = "block";
      if (pdfStatusContent) pdfStatusContent.style.display = "none";
      return;
    }

    if (pdfStatusEmpty) pdfStatusEmpty.style.display = "none";
    if (pdfStatusContent) pdfStatusContent.style.display = "block";
    if (pdfStatusJobIdEl) pdfStatusJobIdEl.textContent = "#" + last.jobId;

    try {
      const res = await fetch(last.statusUrl, ajaxOptions({ method: "GET" }));
      const data = await res.json();

      const status = data.status || "unknown";
      if (pdfStatusBadge) {
        pdfStatusBadge.textContent = status;
        pdfStatusBadge.className = "vs-badge vs-badge-" + status;
      }

      const p = data.progress || { done: 0, total: 0 };
      if (pdfStatusProgress) {
        pdfStatusProgress.textContent =
          (p.done || 0) +
          " / " +
          (p.total || 0) +
          (p.processing ? " (processing " + p.processing + ")" : "") +
          (p.queued ? " (queued " + p.queued + ")" : "");
      }

      if (data.error) {
        if (pdfStatusErrorLine) pdfStatusErrorLine.style.display = "block";
        if (pdfStatusError) pdfStatusError.textContent = data.error;
      } else {
        if (pdfStatusErrorLine) pdfStatusErrorLine.style.display = "none";
      }

      if (status === "done" && data.download_url) {
        if (pdfStatusDownloadWrap)
          pdfStatusDownloadWrap.style.display = "block";
        if (pdfStatusDownload) pdfStatusDownload.href = data.download_url;
      } else {
        if (pdfStatusDownloadWrap) pdfStatusDownloadWrap.style.display = "none";
      }
    } catch (err) {
      console.error("Status fetch failed:", err);
      if (pdfStatusBadge) {
        pdfStatusBadge.textContent = "error";
        pdfStatusBadge.className = "vs-badge vs-badge-failed";
      }
    }
  }

  let pdfStatusPollTimer = null;
  const PDF_STATUS_POLL_MS = 3000;

  function stopPdfStatusPoll() {
    if (pdfStatusPollTimer) {
      clearInterval(pdfStatusPollTimer);
      pdfStatusPollTimer = null;
    }
  }

  async function pollPdfStatusOnce() {
    await refreshPdfStatusModal();
    if (pdfStatusBadge) {
      const txt = (pdfStatusBadge.textContent || "").toLowerCase();
      if (
        txt === "done" ||
        txt === "failed" ||
        txt === "not_found" ||
        txt === "forbidden"
      ) {
        stopPdfStatusPoll();
      }
    }
  }

  function startPdfStatusPoll() {
    stopPdfStatusPoll();
    pollPdfStatusOnce();
    pdfStatusPollTimer = setInterval(pollPdfStatusOnce, PDF_STATUS_POLL_MS);
  }

  function closePdfStatusModal() {
    pdfStatusModal.style.display = "none";
    stopPdfStatusPoll();
    forcedJob = null; // next open falls back to the default pick
  }

  if (btnOpenStatus) {
    btnOpenStatus.addEventListener("click", function () {
      forcedJob = null; // the global button = default pick, not a pinned job
      pdfStatusModal.style.display = "flex";
      startPdfStatusPoll();
    });
  }
  if (pdfStatusModalClose) {
    pdfStatusModalClose.addEventListener("click", closePdfStatusModal);
  }
  pdfStatusModal.addEventListener("click", function (e) {
    if (e.target === pdfStatusModal) closePdfStatusModal();
  });

  window.refreshPdfStatusModal = function () {
    if (pdfStatusModal.style.display !== "none" && !pdfStatusPollTimer) {
      startPdfStatusPoll();
    } else {
      refreshPdfStatusModal();
    }
  };

  // Open the modal pinned to a specific job (used by a toast's Status button so
  // each toast opens ITS own job, not whichever is newest).
  window.openPdfStatusFor = function (job) {
    forcedJob = job && job.jobId && job.statusUrl ? job : null;
    pdfStatusModal.style.display = "flex";
    startPdfStatusPoll();
  };
});
