(() => {
    const searchInput = document.getElementById('reports-search-input');
    const statusFilter = document.getElementById('reports-status-filter');
    const pendingOnlyButton = document.getElementById('reports-pending-only');
    const rows = Array.from(document.querySelectorAll('.report-row'));
    const emptyRow = document.getElementById('reports-empty-client');

    const closeOtherDetails = (current) => {
        document.querySelectorAll('.report-action-dropdown').forEach((dropdown) => {
            if (dropdown !== current) {
                dropdown.removeAttribute('open');
            }
        });
    };

    document.querySelectorAll('.report-action-dropdown').forEach((dropdown) => {
        dropdown.addEventListener('toggle', () => {
            if (dropdown.open) {
                closeOtherDetails(dropdown);
            }
        });
    });

    document.addEventListener('click', (event) => {
        const clickedInside = event.target.closest('.report-action-dropdown');
        if (!clickedInside) {
            closeOtherDetails(null);
        }
    });

    const applyLiveFilter = () => {
        if (!searchInput || !statusFilter || rows.length === 0 || !emptyRow) {
            return;
        }

        const query = searchInput.value.trim().toLowerCase();
        const selectedStatus = statusFilter.value;
        let visibleCount = 0;

        rows.forEach((row) => {
            const rowText = row.dataset.filterText || '';
            const rowStatus = row.dataset.filterStatus || '';
            const matchQuery = query === '' || rowText.includes(query);
            const matchStatus = selectedStatus === '' || rowStatus === selectedStatus;
            const showRow = matchQuery && matchStatus;

            row.classList.toggle('hidden', !showRow);
            if (showRow) {
                visibleCount += 1;
            }
        });

        emptyRow.classList.toggle('hidden', visibleCount > 0);
    };

    if (searchInput && statusFilter && rows.length > 0 && emptyRow) {
        searchInput.addEventListener('input', applyLiveFilter);
        statusFilter.addEventListener('change', applyLiveFilter);
        applyLiveFilter();
    }

    if (pendingOnlyButton && statusFilter) {
        pendingOnlyButton.addEventListener('click', () => {
            statusFilter.value = statusFilter.value === 'pending' ? '' : 'pending';
            applyLiveFilter();
        });
    }

    document.querySelectorAll('.js-confirm-action').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm-message') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
})();
