(() => {
    const toggles = Array.from(document.querySelectorAll('[data-audit-toggle]'));

    if (!toggles.length) {
        return;
    }

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const targetId = toggle.getAttribute('data-target');
            const detailRow = targetId ? document.getElementById(targetId) : null;

            if (!detailRow) {
                return;
            }

            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            detailRow.hidden = isExpanded;
        });
    });
})();
