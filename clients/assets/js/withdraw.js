const amountInput = document.getElementById('amountInput');
const feePreview = document.getElementById('feePreview');

if (amountInput && feePreview) {
    amountInput.addEventListener('input', function () {
        let raw = this.value.replace(/[^0-9]/g, '');
        if (!raw) { 
            feePreview.style.display = 'none'; 
            this.value = ''; 
            return; 
        }
        const num = parseInt(raw, 10);
        this.value = num.toLocaleString('vi-VN');

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
    });
}
