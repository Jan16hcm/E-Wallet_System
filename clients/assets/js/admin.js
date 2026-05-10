function switchTab(tabId) {
  const url = new URL(window.location);
  url.searchParams.set("tab", tabId);
  window.location.href = url.toString();
}

const themeToggleBtns = document.querySelectorAll(".theme-toggle");
const body = document.body;

themeToggleBtns.forEach((btn) => {
  btn.addEventListener("click", (e) => {
    e.preventDefault();
    body.classList.toggle("light-theme");
    themeToggleBtns.forEach((b) => {
      const icon = b.querySelector("i");
      if (icon) {
        if (body.classList.contains("light-theme")) {
          icon.classList.replace("fa-moon", "fa-sun");
        } else {
          icon.classList.replace("fa-sun", "fa-moon");
        }
      }
    });
  });
});
