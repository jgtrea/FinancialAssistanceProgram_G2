/* ============================================================
   GLOBAL PDF STATUS MODAL — wired on every page so the toast
   Status button works after the user navigates away from the
   voucher listing. Modal markup lives in layouts/main.php.
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
  const btnOpenStatus        = document.getElementById('btnOpenStatus');
  const pdfStatusModal       = document.getElementById('pdfStatusModal');
  if (!pdfStatusModal) return;

  const pdfStatusModalClose  = document.getElementById('pdfStatusModalClose');
  const pdfStatusEmpty       = document.getElementById('pdfStatusEmpty');
  const pdfStatusContent     = document.getElementById('pdfStatusContent');
  const pdfStatusJobIdEl     = document.getElementById('pdfStatusJobId');
  const pdfStatusBadge       = document.getElementById('pdfStatusBadge');
  const pdfStatusProgress    = document.getElementById('pdfStatusProgress');
  const pdfStatusErrorLine   = document.getElementById('pdfStatusErrorLine');
  const pdfStatusError       = document.getElementById('pdfStatusError');
  const pdfStatusDownloadWrap= document.getElementById('pdfStatusDownloadWrap');
  const pdfStatusDownload    = document.getElementById('pdfStatusDownload');

  async function refreshPdfStatusModal() {
    const last = getLastJsonPdfJob();
    if (!last || !last.jobId || !last.statusUrl) {
      if (pdfStatusEmpty)   pdfStatusEmpty.style.display = 'block';
      if (pdfStatusContent) pdfStatusContent.style.display = 'none';
      return;
    }

    if (pdfStatusEmpty)   pdfStatusEmpty.style.display = 'none';
    if (pdfStatusContent) pdfStatusContent.style.display = 'block';
    if (pdfStatusJobIdEl) pdfStatusJobIdEl.textContent = '#' + last.jobId;

    try {
      const res  = await fetch(last.statusUrl, ajaxOptions({ method: 'GET' }));
      const data = await res.json();

      const status = data.status || 'unknown';
      if (pdfStatusBadge) {
        pdfStatusBadge.textContent = status;
        pdfStatusBadge.className   = 'vs-badge vs-badge-' + status;
      }

      const p = data.progress || { done: 0, total: 0 };
      if (pdfStatusProgress) {
        pdfStatusProgress.textContent = (p.done || 0) + ' / ' + (p.total || 0)
          + (p.processing ? ' (processing ' + p.processing + ')' : '')
          + (p.queued     ? ' (queued ' + p.queued + ')' : '');
      }

      if (data.error) {
        if (pdfStatusErrorLine) pdfStatusErrorLine.style.display = 'block';
        if (pdfStatusError)     pdfStatusError.textContent = data.error;
      } else {
        if (pdfStatusErrorLine) pdfStatusErrorLine.style.display = 'none';
      }

      if (status === 'done' && data.download_url) {
        if (pdfStatusDownloadWrap) pdfStatusDownloadWrap.style.display = 'block';
        if (pdfStatusDownload)     pdfStatusDownload.href = data.download_url;
      } else {
        if (pdfStatusDownloadWrap) pdfStatusDownloadWrap.style.display = 'none';
      }
    } catch (err) {
      console.error('Status fetch failed:', err);
      if (pdfStatusBadge) {
        pdfStatusBadge.textContent = 'error';
        pdfStatusBadge.className   = 'vs-badge vs-badge-failed';
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
      const txt = (pdfStatusBadge.textContent || '').toLowerCase();
      if (txt === 'done' || txt === 'failed' || txt === 'not_found' || txt === 'forbidden') {
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
    pdfStatusModal.style.display = 'none';
    stopPdfStatusPoll();
  }

  if (btnOpenStatus) {
    btnOpenStatus.addEventListener('click', function () {
      pdfStatusModal.style.display = 'flex';
      startPdfStatusPoll();
    });
  }
  if (pdfStatusModalClose) {
    pdfStatusModalClose.addEventListener('click', closePdfStatusModal);
  }
  pdfStatusModal.addEventListener('click', function (e) {
    if (e.target === pdfStatusModal) closePdfStatusModal();
  });

  window.refreshPdfStatusModal = function () {
    if (pdfStatusModal.style.display !== 'none' && !pdfStatusPollTimer) {
      startPdfStatusPoll();
    } else {
      refreshPdfStatusModal();
    }
  };
});
