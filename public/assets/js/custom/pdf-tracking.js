/* ============================================================
   PDF JOB TRACKING — survives page navigation via localStorage
   ============================================================ */

const PDF_JOBS_KEY = "pendingPdfJobs";
const LAST_JSON_PDF_JOB_KEY = "lastJsonPdfJob";

function saveLastJsonPdfJob(job) {
  try {
    localStorage.setItem(LAST_JSON_PDF_JOB_KEY, JSON.stringify(job));
  } catch (e) {}
}
function getLastJsonPdfJob() {
  try {
    const raw = localStorage.getItem(LAST_JSON_PDF_JOB_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch (e) {
    return null;
  }
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
  const list = getPendingPdfJobs().filter((j) => j.jobId !== job.jobId);
  list.push(job);
  localStorage.setItem(PDF_JOBS_KEY, JSON.stringify(list));
}

function removePendingPdfJob(jobId) {
  const list = getPendingPdfJobs().filter((j) => j.jobId !== jobId);
  if (list.length) localStorage.setItem(PDF_JOBS_KEY, JSON.stringify(list));
  else localStorage.removeItem(PDF_JOBS_KEY);
}

// Poll an in-flight PDF job. Calls onDone(downloadUrl) when ready,
// updates the toast as it goes, and clears localStorage on terminal states.
function pollPdfJob(jobId, statusUrl, toast, onDone) {
  const POLL_INTERVAL_MS = 3000;
  const MAX_POLLS = 200; // ~10 min
  let attempts = 0;

  const tick = async function () {
    attempts++;
    try {
      const res = await fetch(statusUrl, ajaxOptions({ method: "GET" }));
      const data = await res.json();

      if (data.status === "done" && data.download_url) {
        removePendingPdfJob(jobId);
        if (toast) toast.update("PDF ready! Downloading...", true);
        if (typeof onDone === "function") onDone(data.download_url);
        else window.location.href = data.download_url;
        return;
      }

      if (data.status === "failed") {
        removePendingPdfJob(jobId);
        if (toast) toast.remove();
        showAlert(
          "PDF generation failed: " + (data.error || "Unknown error"),
          "error",
        );
        return;
      }

      if (data.status === "forbidden" || data.status === "not_found") {
        removePendingPdfJob(jobId);
        if (toast) toast.remove();
        showAlert("Unable to access PDF job #" + jobId + ".", "error");
        return;
      }

      if (attempts >= MAX_POLLS) {
        if (toast) toast.remove();
        showAlert(
          "PDF #" + jobId + " is still processing. Check back later.",
          "warning",
        );
        return;
      }

      const elapsed = Math.round((attempts * POLL_INTERVAL_MS) / 1000);
      if (toast)
        toast.update("Generating PDF #" + jobId + "... (" + elapsed + "s)");
      setTimeout(tick, POLL_INTERVAL_MS);
    } catch (err) {
      console.error("Poll failed:", err);
      if (attempts < MAX_POLLS) {
        setTimeout(tick, POLL_INTERVAL_MS);
      } else {
        if (toast) toast.remove();
        showAlert("Lost connection while polling PDF #" + jobId + ".", "error");
      }
    }
  };

  setTimeout(tick, POLL_INTERVAL_MS);
}

// On every page load, resume polling for any jobs left pending by another page.
document.addEventListener("DOMContentLoaded", function () {
  const jobs = getPendingPdfJobs();
  jobs.forEach(function (job) {
    const toast = showPdfToast("Generating PDF #" + job.jobId + "...");
    pollPdfJob(job.jobId, job.statusUrl, toast);
  });
});
