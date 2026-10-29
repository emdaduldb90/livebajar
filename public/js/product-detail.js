(() => {
    'use strict';

    document.querySelectorAll('[data-product-details]').forEach((details) => {
        const tabs = details.querySelectorAll('[data-product-tab]');
        const panels = details.querySelectorAll('[data-product-panel]');

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const name = tab.dataset.productTab;

                tabs.forEach((item) => {
                    const isActive = item === tab;
                    item.classList.toggle('is-active', isActive);
                    item.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
                panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.productPanel !== name));
            });
        });
    });

    document.querySelectorAll('[data-product-share]').forEach((button) => {
        button.addEventListener('click', async () => {
            const title = button.dataset.shareTitle || document.title;
            const url = button.dataset.shareUrl || window.location.href;

            try {
                if (navigator.share) {
                    await navigator.share({ title, url });
                    return;
                }

                await navigator.clipboard.writeText(url);
                const icon = button.querySelector('i');
                icon?.classList.replace('fa-link', 'fa-check');
                window.setTimeout(() => icon?.classList.replace('fa-check', 'fa-link'), 1600);
            } catch (_) {
                // Closing the native share dialog is not an application error.
            }
        });
    });
})();
