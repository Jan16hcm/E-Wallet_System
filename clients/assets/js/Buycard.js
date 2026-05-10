const form = document.getElementById('buyCardForm');
if (form) {
    const denomInputs = form.querySelectorAll('input[name="denomination"]');
    const quantitySelect = form.querySelector('select[name="quantity"]');
    const totalDisplay = document.getElementById('totalDisplay');

    function updateTotal() {
        const selectedDenom = form.querySelector('input[name="denomination"]:checked');
        const qty = parseInt(quantitySelect.value);
        if (selectedDenom) {
            const total = parseInt(selectedDenom.value) * qty;
            totalDisplay.innerText = new Intl.NumberFormat('vi-VN').format(total) + ' ₫';
        }
    }

    denomInputs.forEach(input => input.addEventListener('change', updateTotal));
    quantitySelect.addEventListener('change', updateTotal);
}

function copyCode(code, btn) {
    navigator.clipboard.writeText(code).then(() => {
        const originalText = btn.innerText;
        btn.innerText = 'Copied!';
        btn.style.background = '#10b981';
        btn.style.color = 'white';
        setTimeout(() => {
            btn.innerText = originalText;
            btn.style.background = '';
            btn.style.color = '';
        }, 2000);
    });
}
// Prevent double submission
if (form) {
    form.addEventListener('submit', function() {
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.innerText = 'Processing...';
            btn.disabled = true;
            btn.style.opacity = '0.7';
            btn.style.cursor = 'not-allowed';
        }
    });
}
