(() => {
    const closeOtherDetails = (current) => {
        document.querySelectorAll('.action-dropdown').forEach((dropdown) => {
            if (dropdown !== current) {
                dropdown.removeAttribute('open');
            }
        });
    };

    document.querySelectorAll('.action-dropdown').forEach((dropdown) => {
        dropdown.addEventListener('toggle', () => {
            if (dropdown.open) {
                closeOtherDetails(dropdown);
            }
        });
    });

    document.addEventListener('click', (event) => {
        const clickedInside = event.target.closest('.action-dropdown');
        if (!clickedInside) {
            closeOtherDetails(null);
        }
    });

    document.querySelectorAll('.js-confirm-action').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm-message') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
})();
