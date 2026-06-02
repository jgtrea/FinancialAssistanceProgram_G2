/* ============================================================
   LAYOUT — sidebar toggle, shared init, login page
   ============================================================ */

document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.getElementById("sidebar");
  const toggle = document.getElementById("sidebarToggle");
  const overlay = document.getElementById("sidebarOverlay");

  if (toggle && sidebar) {
    toggle.addEventListener("click", function () {
      sidebar.classList.toggle("vs-sidebar-open");
      overlay && overlay.classList.toggle("vs-overlay-open");
    });
  }

  if (overlay) {
    overlay.addEventListener("click", function () {
      sidebar && sidebar.classList.remove("vs-sidebar-open");
      overlay.classList.remove("vs-overlay-open");
    });
  }

  initPasswordToggles();
  initAlertDismiss();
});

/* ============================================================
   LOGIN PAGE
   ============================================================ */

document.addEventListener("DOMContentLoaded", function () {
  const pwToggle = document.getElementById("pwToggle");
  const pwField = document.getElementById("password");
  const pwShow = document.getElementById("pwIconShow");
  const pwHide = document.getElementById("pwIconHide");

  if (pwToggle && pwField) {
    pwToggle.addEventListener("click", function () {
      const isPass = pwField.type === "password";
      pwField.type = isPass ? "text" : "password";
      if (pwShow) pwShow.style.display = isPass ? "none" : "inline";
      if (pwHide) pwHide.style.display = isPass ? "inline" : "none";
    });
  }

  const loginForm = document.getElementById("loginForm");
  const loginBtn = document.getElementById("loginBtn");
  const loginBtnText = document.getElementById("loginBtnText");
  const loginSpinner = document.getElementById("loginBtnSpinner");

  if (loginForm) {
    loginForm.addEventListener("submit", function () {
      if (loginBtn) loginBtn.disabled = true;
      if (loginBtnText) loginBtnText.style.display = "none";
      if (loginSpinner) loginSpinner.style.display = "inline-block";
    });
  }
});
