// Theme Toggle
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

// Mobile Sidebar Toggle
const sidebar = document.getElementById("sidebar");
const sidebarToggleBtn = document.getElementById("sidebarToggleBtn");
const sidebarOverlay = document.getElementById("sidebarOverlay");

if (sidebarToggleBtn && sidebar && sidebarOverlay) {
  sidebarToggleBtn.addEventListener("click", (e) => {
    e.preventDefault();
    sidebar.classList.toggle("active");
    sidebarOverlay.classList.toggle("show");
  });

  sidebarOverlay.addEventListener("click", () => {
    sidebar.classList.remove("active");
    sidebarOverlay.classList.remove("show");
  });
}

Chart.defaults.color = "#8b92a5";
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

// Balance Chart
const ctxBalanceEl = document.getElementById("balanceChart");
if (ctxBalanceEl) {
    const ctxBalance = ctxBalanceEl.getContext("2d");
    let gradientBlue = ctxBalance.createLinearGradient(0, 0, 0, 150);
    gradientBlue.addColorStop(0, "rgba(59, 130, 246, 0.4)");
    gradientBlue.addColorStop(1, "rgba(59, 130, 246, 0)");

    new Chart(ctxBalance, {
    type: "line",
    data: {
        labels: window.chartData ? window.chartData.labels : ["15", "16", "17", "18", "19", "20"],
        datasets: [
        {
            data: window.chartData ? window.chartData.daily : [100, 150, 130, 200, 180, 250],
            borderColor: "#3b82f6",
            borderWidth: 3,
            backgroundColor: gradientBlue,
            fill: true,
            tension: 0.4,
            pointRadius: 0,
        },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { display: false }, y: { display: false } },
    },
    });
}

// Earnings Chart
const earningsEl = document.getElementById("earningsChart");
if (earningsEl) {
    new Chart(earningsEl, {
    type: "doughnut",
    data: {
        datasets: [
        {
            data: window.chartData ? [window.chartData.earnings, window.chartData.spending] : [58, 42],
            backgroundColor: ["#3b82f6", "#2a2f3e"],
            borderWidth: 0,
            cutout: "80%",
            borderRadius: 20,
        },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        rotation: -90,
        circumference: 180,
        plugins: { tooltip: { enabled: false } },
    },
    });
}

// Spending Chart
const spendingEl = document.getElementById("spendingChart");
if (spendingEl) {
    let gradBar = spendingEl.getContext("2d").createLinearGradient(0, 0, 0, 150);
    gradBar.addColorStop(0, "#3b82f6");
    gradBar.addColorStop(1, "rgba(59, 130, 246, 0.2)");
    new Chart(spendingEl, {
    type: "bar",
    data: {
        labels: window.chartData ? window.chartData.labels.slice(-6) : ["Mon", "Tue", "Wed", "Thu"],
        datasets: [
        { 
            data: window.chartData ? window.chartData.daily.slice(-6).map(v => Math.abs(v)) : [34, 16, 8, 6], 
            backgroundColor: gradBar, 
            borderRadius: 8,
            barThickness: 40
        },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
        x: { 
            display: true, 
            grid: { display: false },
            ticks: { color: "#8b92a5", font: { size: 12 } }
        }, 
        y: { 
            display: false 
        } 
        },
    },
    });
}
