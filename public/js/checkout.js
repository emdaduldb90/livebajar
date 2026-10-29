document.addEventListener('DOMContentLoaded', () => {
    const options = document.querySelectorAll('[data-shipping-option]');
    const shippingAmount = document.querySelector('[data-checkout-shipping]');
    const total = document.querySelector('[data-checkout-total]');

    if (!options.length || !shippingAmount || !total) {
        return;
    }

    const subtotalCents = Number(total.dataset.subtotalCents || 0);
    const discountCents = Number(total.dataset.discountCents || 0);
    const currencySymbol = total.dataset.currencySymbol || '৳';
    const formatAmount = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const updateSummary = () => {
        const selected = document.querySelector('[data-shipping-option]:checked');

        if (!selected) {
            return;
        }

        const feeCents = Number(selected.dataset.feeCents || 0);
        shippingAmount.textContent = selected.dataset.feeLabel || '—';
        total.textContent = `${currencySymbol}${formatAmount.format(Math.max(0, subtotalCents - discountCents + feeCents) / 100)}`;

        options.forEach((option) => {
            const card = option.closest('[data-shipping-card]');
            card?.classList.toggle('border-brandRed', option.checked);
            card?.classList.toggle('ring-1', option.checked);
            card?.classList.toggle('ring-brandRed', option.checked);
        });
    };

    options.forEach((option) => option.addEventListener('change', updateSummary));
    updateSummary();
});
