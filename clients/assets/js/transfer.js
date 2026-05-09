// OTP auto-advance, backspace navigation 
const otpInputs = document.querySelectorAll('.otp-inputs input');
if (otpInputs.length > 0) {
    otpInputs.forEach((input, i) => {
        input.addEventListener('input', () => {
            if (input.value.length === input.maxLength && otpInputs[i + 1]) {
                otpInputs[i + 1].focus();
            }
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && input.value.length === 0 && otpInputs[i - 1]) {
                otpInputs[i - 1].focus();
            }
        });
    });
}

// amount formatter & fee preview
const amountInput = document.getElementById('amountInput');
const feePreview = document.getElementById('feePreview');
const feeRadios = document.querySelectorAll('input[name="selfFeeBear"]');

function updateFeePreview() {
    if (!amountInput || !feePreview) return;
    const raw = amountInput.value.replace(/[^0-9]/g, '');
    if (!raw) { 
        feePreview.style.display = 'none'; 
        return; 
    }
    const num = parseInt(raw);
    const selfPays = document.querySelector('input[name="selfFeeBear"]:checked')?.value === '1';
    const fee = num * 0.05;
    const youPay = selfPays ? num + fee : num;
    const theyGet = selfPays ? num : num - fee;
    const fmt = v => Math.round(v).toLocaleString('vi-VN') + ' ₫';

    feePreview.style.display = 'block';
    feePreview.innerHTML = `
        <div class="summary-row" style="margin-bottom:8px"><span style="color:var(--text-muted)">Transfer amount</span><span>${fmt(num)}</span></div>
        <div class="summary-row" style="margin-bottom:8px"><span style="color:var(--text-muted)">Fee (5%)</span><span style="color:#ef4444">${fmt(fee)}</span></div>
        <div class="summary-row" style="margin-bottom:8px"><span style="color:var(--text-muted)">Recipient gets</span><span style="color:#10b981">${fmt(theyGet)}</span></div>
        <div style="border-top:1px solid var(--border-color); padding-top:8px; margin-top:8px; display:flex; justify-content:space-between; font-weight:700">
            <span>You Pay</span><span>${fmt(youPay)}</span>
        </div>`;
}

if (amountInput) {
    amountInput.addEventListener('input', function () {
        const raw = this.value.replace(/[^0-9]/g, '');
        this.value = raw ? parseInt(raw, 10).toLocaleString('vi-VN') : '';
        updateFeePreview();
    });
}
feeRadios.forEach(r => r.addEventListener('change', updateFeePreview));

// live recipient lookup
const phoneInput = document.getElementById('recipientPhone');
const recipientBadge = document.getElementById('recipientBadge');
if (phoneInput && recipientBadge) {
    let timeout;
    phoneInput.addEventListener('input', function () {
        clearTimeout(timeout);
        const phone = this.value.trim();
        if (phone.length >= 10) {
            timeout = setTimeout(() => {
                fetch('../modules/filter_account.php?phone=' + encodeURIComponent(phone))
                    .then(r => r.json())
                    .then(data => {
                        if (data.found) {
                            recipientBadge.innerHTML =
                                '<span style="color:#10b981"><i class="fa-solid fa-circle-check"></i> ' + data.name + '</span>';
                        } else {
                            recipientBadge.innerHTML =
                                '<span style="color:#ef4444"><i class="fa-solid fa-circle-xmark"></i> Recipient not found</span>';
                        }
                    }).catch(() => {});
            }, 500);
        } else {
            recipientBadge.innerHTML = '';
        }
    });
}
