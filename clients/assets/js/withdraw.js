const amountInput = document.getElementById('amountInput');
const feePreview = document.getElementById('feePreview');

if (amountInput && feePreview) {
    const amountError = document.getElementById('amountError');
    const submitBtn = document.querySelector('#withdrawForm button[type="submit"]');

    amountInput.addEventListener('input', function () {
        let raw = this.value.replace(/[^0-9]/g, '');
        const amountError = document.getElementById('amountError');
        const submitBtn = document.querySelector('#withdrawForm button[type="submit"]');

        if (!raw) { 
            if (amountError) amountError.style.display = 'none';
            this.value = ''; 
            updatePreview(0); // Show zeros instead of hiding
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
            }
            return; 
        }
        const num = parseInt(raw, 10);
        this.value = num.toLocaleString('vi-VN');

        // Validation: Multiple of 50,000
        const isValid = num % 50000 === 0;
        if (amountError) {
            amountError.style.display = isValid ? 'none' : 'flex';
        }
        if (submitBtn) {
            submitBtn.disabled = !isValid;
            submitBtn.style.opacity = isValid ? '1' : '0.5';
            submitBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
        }

        updatePreview(num);
    });

    function updatePreview(num) {
        const fee = num * 0.05;
        const total = num + fee;
        const fmt = v => Math.round(v).toLocaleString('vi-VN') + ' ₫';

        feePreview.style.display = 'block';
        feePreview.innerHTML = `
            <div class="summary-row" style="margin-bottom:8px"><span style="color:var(--text-muted)">Withdraw Amount</span><span>${fmt(num)}</span></div>
            <div class="summary-row" style="margin-bottom:8px"><span style="color:var(--text-muted)">Fee (5%)</span><span style="color:#ef4444">${fmt(fee)}</span></div>
            <div style="border-top:1px solid var(--border-color); padding-top:8px; margin-top:8px; display:flex; justify-content:space-between; font-weight:700">
                <span>Total Deducted</span><span>${fmt(total)}</span>
            </div>
        `;
    }

    // Initialize with 0
    updatePreview(0);
}

// Hide server error when user re-enters any field
function hideWithdrawError() {
    const errBox = document.getElementById('withdrawError');
    if (errBox) errBox.style.display = 'none';
}
['card_num', 'expire', 'cvv', 'amountInput', 'noteInput'].forEach(id => {
    const el = document.getElementById(id) || document.querySelector(`[name="${id}"]`);
    if (el) el.addEventListener('input', hideWithdrawError);
});
// Prevent double submission
const withdrawForm = document.getElementById('withdrawForm');
if (withdrawForm) {
    withdrawForm.addEventListener('submit', function() {
        if (amountInput) {
            amountInput.value = amountInput.value.replace(/\D/g, '');
        }
        const btn = withdrawForm.querySelector('button[type="submit"]');
        if (btn) {
            btn.innerText = 'Processing...';
            btn.disabled = true;
            btn.style.opacity = '0.7';
            btn.style.cursor = 'not-allowed';
        }
    });
}

// Note character counter
const noteInput = document.getElementById('noteInput');
const noteCount = document.getElementById('noteCount');
if (noteInput && noteCount) {
    const updateCount = () => {
        noteCount.innerText = `${noteInput.value.length}/50`;
    };
    noteInput.addEventListener('input', updateCount);
    updateCount(); // Initialize
}
