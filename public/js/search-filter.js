(() => {
    const forms = Array.from(document.querySelectorAll('[data-search-filter-form]'));

    if (!forms.length) {
        return;
    }

    forms.forEach((form) => {
        const searchInput = form.querySelector('[data-live-search]');
        const filterSelect = form.querySelector('[data-live-filter]');
        const dateInputs = Array.from(form.querySelectorAll('[data-date-input]'));
        const quickFilterInput = form.querySelector('[data-quick-filter-input]');
        const quickFilterButtons = Array.from(form.querySelectorAll('[data-quick-filter-button]'));
        let timer = null;

        const submitForm = () => {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }

            form.submit();
        };

        searchInput?.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => submitForm(), 320);
        });

        filterSelect?.addEventListener('change', () => {
            window.clearTimeout(timer);
            submitForm();
        });

        dateInputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (quickFilterInput) {
                    quickFilterInput.value = '';
                }

                quickFilterButtons.forEach((button) => button.classList.remove('is-active'));
            });
        });

        quickFilterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (quickFilterInput) {
                    quickFilterInput.value = button.dataset.quickFilterValue || '';
                }

                submitForm();
            });
        });
    });
})();
