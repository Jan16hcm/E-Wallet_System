const themeToggleBtns = document.querySelectorAll(".theme-toggle");
const body = document.body;

const savedTheme = localStorage.getItem("theme");
if (savedTheme !== "dark") {
  body.classList.add("light-theme");
  themeToggleBtns.forEach((b) => {
    const icon = b.querySelector("i");
    if (icon) icon.classList.replace("fa-moon", "fa-sun");
  });
}

themeToggleBtns.forEach((btn) => {
  btn.addEventListener("click", (e) => {
    e.preventDefault();
    body.classList.toggle("light-theme");

    const isLight = body.classList.contains("light-theme");
    localStorage.setItem("theme", isLight ? "light" : "dark");

    themeToggleBtns.forEach((b) => {
      const icon = b.querySelector("i");
      if (icon) {
        if (isLight) {
          icon.classList.replace("fa-moon", "fa-sun");
        } else {
          icon.classList.replace("fa-sun", "fa-moon");
        }
      }
    });
  });
});

const alertError = document.getElementById("alert-error");
const alertSuccess = document.getElementById("alert-success");

const hideAlertsOnInput = () => {
  if (alertError) {
    alertError.style.visibility = "hidden";
    alertError.style.opacity = "0";
  }
  if (alertSuccess) {
    alertSuccess.style.visibility = "hidden";
    alertSuccess.style.opacity = "0";
  }
};

const depositForm = document.getElementById("depositForm");
const submitBtn = document.getElementById("submitBtn");

if (depositForm && submitBtn) {
  depositForm.addEventListener("submit", function () {
    submitBtn.innerText = "Processing...";
    submitBtn.disabled = true;
    submitBtn.style.opacity = "0.7";
    submitBtn.style.cursor = "not-allowed";
  });
}

// Add input listeners to hide alerts
const formInputs = document.querySelectorAll(".deposit-form input");
formInputs.forEach((input) => {
  input.addEventListener("input", hideAlertsOnInput);
});

// Mobile Sidebar Toggle
const sidebar = document.getElementById("sidebar");
const sidebarToggleBtn = document.getElementById("sidebarToggleBtn");
const sidebarOverlay = document.getElementById("sidebarOverlay");

if (sidebarToggleBtn && sidebar && sidebarOverlay) {
  sidebarToggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    sidebarOverlay.classList.toggle("show");
  });

  sidebarOverlay.addEventListener("click", () => {
    sidebar.classList.remove("active");
    sidebarOverlay.classList.remove("show");
  });
}

document.addEventListener("DOMContentLoaded", function () {
  const restrictedLinks = document.querySelectorAll(".restricted-feature");
  restrictedLinks.forEach((link) => {
    link.addEventListener("click", function (event) {
      event.preventDefault();
      alert(
        "This feature is only available for verified accounts. Please wait for verification or update your information.",
      );
    });
  });
});
