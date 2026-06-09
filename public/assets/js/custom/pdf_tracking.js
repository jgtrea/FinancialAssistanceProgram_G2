/* ============================================================
   PDF JOB TRACKING — survives page navigation via localStorage
   ============================================================ */

const PDF_JOBS_KEY = 'pendingPdfJobs';
const LAST_JSON_PDF_JOB_KEY = 'lastJsonPdfJob';

function saveLastJsonPdfJob(job) {
  try { localStorage.setItem(LAST_JSON_PDF_JOB_KEY, JSON.stringify(job)); } catch (e) {}
}
function getLastJsonPdfJob() {
  try {
    const raw = localStorage.getItem(LAST_JSON_PDF_JOB_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch (e) { return null; }
}

function getPendingPdfJobs() {
  try {
    const raw = localStorage.getItem(PDF_JOBS_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (e) {
    return [];
  }
}

function savePendingPdfJob(job) {
  const list = getPendingPdfJobs().filter(j => j.jobId !== job.jobId);
  list.push(job);
  localStorage.setItem(PDF_JOBS_KEY, JSON.stringify(list));
}

function removePendingPdfJob(jobId) {
  const list = getPendingPdfJobs().filter(j => j.jobId !== jobId);
  if (list.length) localStorage.setItem(PDF_JOBS_KEY, JSON.stringify(list));
  else             localStorage.removeItem(PDF_JOBS_KEY);
}

// Poll an in-flight PDF job. Calls onDone(downloadUrl) when ready,
// updates the toast as it goes, and clears localStorage on terminal states.
function pollPdfJob(jobId, statusUrl, toast, onDone) {
  const POLL_INTERVAL_MS = 3000;
  const MAX_POLLS        = 200; // ~10 min
  let attempts = 0;

  const tick = async function () {
    attempts++;
    try {
      const res  = await fetch(statusUrl, ajaxOptions({ method: 'GET' }));
      const data = await res.json();

      if (data.status === 'done' && data.download_url) {
        removePendingPdfJob(jobId);
        // Reveal a manual Download link and keep the toast ~5 min, so the user
        // can grab the file if the automatic download below is blocked/fails.
        if (toast) toast.update('PDF #' + jobId + ' ready — downloading…', true, data.download_url);
        if (typeof onDone === 'function') onDone(data.download_url);
        else window.location.href = data.download_url;
        return;
      }

      if (data.status === 'failed') {
        removePendingPdfJob(jobId);
        if (toast) toast.remove();
        showAlert('PDF generation failed: ' + (data.error || 'Unknown error'), 'error');
        return;
      }

      if (data.status === 'forbidden' || data.status === 'not_found') {
        removePendingPdfJob(jobId);
        if (toast) toast.remove();
        showAlert('Unable to access PDF job #' + jobId + '.', 'error');
        return;
      }

      if (attempts >= MAX_POLLS) {
        if (toast) toast.remove();
        showAlert('PDF #' + jobId + ' is still processing. Check back later.', 'warning');
        return;
      }

      // Show live percentage (same UX as the archive toast) instead of elapsed
      // seconds. The PDF status endpoint returns the same chunk-based progress.
      if (toast) toast.update(jobProgressText('Generating PDF #' + jobId, data));
      setTimeout(tick, POLL_INTERVAL_MS);
    } catch (err) {
      console.error('Poll failed:', err);
      if (attempts < MAX_POLLS) {
        setTimeout(tick, POLL_INTERVAL_MS);
      } else {
        if (toast) toast.remove();
        showAlert('Lost connection while polling PDF #' + jobId + '.', 'error');
      }
    }
  };

  setTimeout(tick, POLL_INTERVAL_MS);
}

// Generic background-job poller. Unlike pollPdfJob (which is PDF-specific:
// localStorage tracking + auto-download), this just polls jobs/status/{id} and
// fires callbacks. Used by the archive flows (and any future queued job type).
//   opts.onProgress(data)  — called each non-terminal poll
//   opts.onDone(data)      — status === 'done'
//   opts.onError(msg,data) — failed / forbidden / not_found / timeout / network
function pollJob(statusUrl, opts) {
  opts = opts || {};
  var POLL_INTERVAL_MS = opts.intervalMs || 3000;
  var MAX_POLLS        = opts.maxPolls   || 600; // ~30 min, big archives are slow
  var attempts = 0;

  var fetchOpts = (typeof ajaxOptions === 'function')
    ? ajaxOptions({ method: 'GET' })
    : { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' };

  var tick = function () {
    attempts++;
    fetch(statusUrl, fetchOpts)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.status === 'done') {
          if (opts.onDone) opts.onDone(data);
          return;
        }
        if (data.status === 'failed') {
          if (opts.onError) opts.onError(data.error || 'Job failed.', data);
          return;
        }
        if (data.status === 'forbidden' || data.status === 'not_found') {
          if (opts.onError) opts.onError('Unable to access this job.', data);
          return;
        }
        if (attempts >= MAX_POLLS) {
          if (opts.onError) opts.onError('Still processing. Check back later.', data);
          return;
        }
        if (opts.onProgress) opts.onProgress(data);
        setTimeout(tick, POLL_INTERVAL_MS);
      })
      .catch(function (err) {
        console.error('pollJob failed:', err);
        if (attempts < MAX_POLLS) {
          setTimeout(tick, POLL_INTERVAL_MS);
        } else if (opts.onError) {
          opts.onError('Lost connection while polling.', null);
        }
      });
  };

  setTimeout(tick, POLL_INTERVAL_MS);
}

// Format a "Verb NN%…" progress line from a jobs/status payload. The status
// endpoint reports progress in CHUNKS (batches), not individual students, so we
// surface only the percentage — accurate because chunks are equal-sized — and
// avoid printing chunk counts that look like student counts.
function jobProgressText(verb, data) {
  var p = (data && data.progress) ? data.progress : null;
  if (data && typeof data.progress_percent === 'number') {
    return verb + ' ' + data.progress_percent + '%…';
  }
  if (p && p.total) {
    var done = typeof p.done === 'number' ? p.done : 0;
    return verb + ' ' + Math.floor((done / p.total) * 100) + '%…';
  }
  return verb + '…';
}

// Show a live progress toast for a background ARCHIVE job and poll it to the
// end — same spinner + percentage UX as the generate toast, minus the PDF-only
// Status/Download buttons. callbacks.onDone(data) fires when finished (e.g. to
// reload or remove rows); callbacks.onError(msg) on failure.
function trackArchiveJob(statusUrl, count, callbacks) {
  callbacks = callbacks || {};
  var total = Number(count) || 0;
  var key   = 'arch-' + Date.now();
  var toast = (typeof showPdfToast === 'function')
    ? showPdfToast(jobProgressText('Archiving', { progress: { done: 0, total: total } }),
        key, null, { hideStatus: true, hideDownload: true })
    : null;

  pollJob(statusUrl, {
    onProgress: function (data) {
      if (toast) toast.update(jobProgressText('Archiving', data));
    },
    onDone: function (data) {
      var n = (data && data.result && typeof data.result.archived === 'number') ? data.result.archived : total;
      if (toast) toast.update((Number(n) || 0).toLocaleString() + ' student(s) archived.', true);
      if (callbacks.onDone) callbacks.onDone(data);
    },
    onError: function (msg, data) {
      if (toast) toast.remove();
      if (callbacks.onError) callbacks.onError(msg, data);
    },
  });
}

// On every page load, resume polling for any jobs left pending by another page.
document.addEventListener('DOMContentLoaded', function () {
  const jobs = getPendingPdfJobs();
  jobs.forEach(function (job) {
    const toast = showPdfToast('Generating PDF #' + job.jobId + '...', 'job-' + job.jobId, {
      jobId:     job.jobId,
      statusUrl: job.statusUrl,
    });
    pollPdfJob(job.jobId, job.statusUrl, toast);
  });
});
