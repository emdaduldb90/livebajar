document.querySelectorAll('[data-category-filter-toggle]').forEach((button) => {
    const panel = document.getElementById(button.getAttribute('aria-controls'));

    if (!panel) return;

    button.addEventListener('click', () => {
        const isExpanded = button.getAttribute('aria-expanded') === 'true';

        button.setAttribute('aria-expanded', String(!isExpanded));
        button.textContent = isExpanded ? 'Show' : 'Hide';
        panel.classList.toggle('hidden', isExpanded);
    });
});
