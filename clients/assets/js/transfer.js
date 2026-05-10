// OTP auto-advance, backspace navigation
const otpInputs = document.querySelectorAll(".otp-inputs input");
if (otpInputs.length > 0) {
  otpInputs.forEach((input, i) => {
    input.addEventListener("input", () => {
      if (input.value.length === input.maxLength && otpInputs[i + 1]) {
        otpInputs[i + 1].focus();
      }
    });
    input.addEventListener("keydown", (e) => {
      if (
        e.key === "Backspace" &&
        input.value.length === 0 &&
        otpInputs[i - 1]
      ) {
        otpInputs[i - 1].focus();
      }
    });
  });
}

// amount formatter & fee preview
const amountInput = document.getElementById("amountInput");
const feePreview = document.getElementById("feePreview");
const feeRadios = document.querySelectorAll('input[name="selfFeeBear"]');

function updateFeePreview() {
  if (!amountInput || !feePreview) return;
  const raw = amountInput.value.replace(/[^0-9]/g, "");
  const num = raw ? parseInt(raw, 10) : 0;

  const selfPays =
    document.querySelector('input[name="selfFeeBear"]:checked')?.value === "1";
  const fee = num * 0.05;
  const youPay = selfPays ? num + fee : num;
  const theyGet = selfPays ? num : num - fee;
  const fmt = (v) => Math.round(v).toLocaleString("vi-VN") + " ₫";

  feePreview.style.display = "block";
  feePreview.innerHTML = `
        <div class="summary-row" style="margin-bottom:8px"><span style="color:var(--text-muted)">Transfer amount: </span><span>${fmt(num)}</span></div>
        <div class="summary-row" style="margin-bottom:8px"><span style="color:var(--text-muted)">Fee (5%): </span><span style="color:#ef4444">${fmt(fee)}</span></div>
        <div class="summary-row" style="margin-bottom:8px"><span style="color:var(--text-muted)">Recipient gets: </span><span style="color:#10b981">${fmt(theyGet)}</span></div>
        <div style="border-top:1px solid var(--border-color); padding-top:8px; margin-top:8px; display:flex; justify-content:space-between; font-weight:700">
            <span>You Pay</span><span>${fmt(youPay)}</span>
        </div>`;
}

// Initial call
updateFeePreview();

if (amountInput) {
  amountInput.addEventListener("input", function () {
    const raw = this.value.replace(/[^0-9]/g, "");
    this.value = raw ? parseInt(raw, 10).toLocaleString("vi-VN") : "";
    updateFeePreview();
    // Hide server error when user re-enters amount
    const errBox = document.getElementById("transferError");
    if (errBox) errBox.style.visibility = "hidden";
  });
}
feeRadios.forEach((r) => r.addEventListener("change", updateFeePreview));

// live recipient lookup
const phoneInput = document.getElementById("recipientPhone");
const recipientBadge = document.getElementById("recipientBadge");
if (phoneInput && recipientBadge) {
  let timeout;
  phoneInput.addEventListener("input", function () {
    clearTimeout(timeout);
    // Hide server error when user re-enters phone
    const errBox = document.getElementById("transferError");
    if (errBox) errBox.style.visibility = "hidden";
    const phone = this.value.trim();
    if (phone.length >= 10) {
      timeout = setTimeout(() => {
        fetch("../modules/lookup_user.php?phone=" + encodeURIComponent(phone))
          .then((r) => r.json())
          .then((data) => {
            if (data.found) {
              recipientBadge.innerHTML =
                '<span style="color:#10b981"><i class="fa-solid fa-circle-check"></i> ' +
                data.name +
                "</span>";
            } else {
              recipientBadge.innerHTML =
                '<span style="color:#ef4444"><i class="fa-solid fa-circle-xmark"></i> Recipient not found</span>';
            }
          })
          .catch(() => {});
      }, 500);
    } else {
      recipientBadge.innerHTML = "";
    }
  });
}
// Prevent double submission
const transferForm = document.getElementById("transferForm");
if (transferForm) {
  transferForm.addEventListener("submit", function () {
    if (amountInput) {
      amountInput.value = amountInput.value.replace(/\D/g, "");
    }
    const btn = transferForm.querySelector('button[type="submit"]');
    if (btn) {
      btn.innerText = "Processing...";
      btn.disabled = true;
      btn.style.opacity = "0.7";
      btn.style.cursor = "not-allowed";
    }
  });
}

// Note character counter
const noteInput = document.getElementById("noteInput");
const noteCount = document.getElementById("noteCount");
if (noteInput && noteCount) {
  const updateCount = () => {
    noteCount.innerText = `${noteInput.value.length}/50`;
  };
  noteInput.addEventListener("input", updateCount);
  updateCount(); // Initialize
}

const otpForm = document.getElementById("otpForm");
if (otpForm) {
  otpForm.addEventListener("submit", function () {
    const btn = otpForm.querySelector('button[type="submit"]');
    if (btn) {
      btn.innerText = "Verifying...";
      btn.disabled = true;
      btn.style.opacity = "0.7";
      btn.style.cursor = "not-allowed";
    }
  });
}

// Countdown timer logic
const timerDisplay = document.getElementById("otp-timer");
if (
  timerDisplay &&
  typeof remainingOtpTime !== "undefined" &&
  remainingOtpTime > 0
) {
  let timeLeft = remainingOtpTime;

  function updateTimer() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    timerDisplay.innerText = `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;

    if (timeLeft <= 0) {
      clearInterval(timerInterval);
      timerDisplay.innerText = "Expired";
      timerDisplay.style.color = "#ef4444";
      // Disable inputs if expired
      document
        .querySelectorAll(".otp-inputs input")
        .forEach((i) => (i.disabled = true));

      // Automatically return to beginning after a short delay
      setTimeout(() => {
        window.location.href = "Transfer.php?cancel=1";
      }, 1500);
    }
    timeLeft--;
  }

  updateTimer();
  const timerInterval = setInterval(updateTimer, 1000);
}
