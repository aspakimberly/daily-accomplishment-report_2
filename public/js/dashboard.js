(() => {
    const config = window.dashboardConfig || {};
    const userFormOptions = config.userFormOptions || {};
    const initialRole = config.oldValues?.role || config.initialRole || "hr-super-admin";

    const modal = document.querySelector("[data-user-modal]");
    const openButtons = Array.from(document.querySelectorAll("[data-open-user-modal]"));
    const closeButton = document.querySelector("[data-close-user-modal]");
    const form = document.querySelector("[data-user-form]");
    const confirmModal = document.querySelector("[data-confirm-modal]");
    const confirmTitle = document.querySelector("[data-confirm-title]");
    const confirmMessage = document.querySelector("[data-confirm-message]");
    const confirmCancel = document.querySelector("[data-confirm-cancel]");
    const confirmSubmit = document.querySelector("[data-confirm-submit]");

    let pendingForm = null;

    const setConfirmVisible = (visible) => {
        if (confirmModal) {
            confirmModal.classList.toggle("is-visible", visible);
        }
    };

    document.querySelectorAll("[data-confirm-trigger]").forEach((button) => {
        button.addEventListener("click", () => {
            pendingForm = document.getElementById(button.dataset.formId);

            if (!pendingForm) {
                return;
            }

            confirmTitle.textContent = button.dataset.confirmTitle || "Confirm Action";
            confirmMessage.textContent = button.dataset.confirmMessage || "";
            setConfirmVisible(true);
        });
    });

    confirmCancel?.addEventListener("click", () => setConfirmVisible(false));
    confirmSubmit?.addEventListener("click", () => pendingForm?.submit());

    confirmModal?.addEventListener("click", (event) => {
        if (event.target === confirmModal) {
            setConfirmVisible(false);
        }
    });

    if (!modal || !form) {
        return;
    }

    const methodField = form.querySelector("[data-user-form-method]");
    const titleNode = document.querySelector("[data-user-modal-title]");
    const radios = Array.from(form.querySelectorAll("[data-role-radio]"));
    const fieldRows = Object.fromEntries(
        Array.from(form.querySelectorAll("[data-field]")).map((node) => [node.dataset.field, node])
    );

    const fillSelect = (select, placeholder, values, selectedValue) => {
        if (!select) {
            return;
        }

        select.innerHTML = "";

        const firstOption = document.createElement("option");
        firstOption.value = "";
        firstOption.textContent = placeholder;
        select.appendChild(firstOption);

        values.forEach((value) => {
            const option = document.createElement("option");
            option.value = value;
            option.textContent = value;
            option.selected = selectedValue === value;
            select.appendChild(option);
        });
    };

    const applyRole = (roleValue, values = {}) => {
        const roleConfig = userFormOptions[roleValue];

        if (!roleConfig) {
            return;
        }

        Object.entries(fieldRows).forEach(([fieldName, fieldRow]) => {
            const input = fieldRow.querySelector("input, select");
            const shouldShow = roleConfig.fields.includes(fieldName) || fieldName === "name" || fieldName === "email";

            fieldRow.hidden = !shouldShow;

            if (!input) {
                return;
            }

            input.required = roleConfig.fields.includes(fieldName) || fieldName === "name" || fieldName === "email";

            if (!shouldShow) {
                input.value = "";
            } else if (values[fieldName] !== undefined && input.tagName === "INPUT") {
                input.value = values[fieldName] || "";
            }
        });

        fillSelect(form.querySelector('select[name="project"]'), "Project", roleConfig.projectOptions || [], values.project || "");
        fillSelect(form.querySelector('select[name="bureau"]'), "Bureau", roleConfig.bureauOptions || [], values.bureau || "");
        fillSelect(form.querySelector('select[name="division"]'), roleConfig.divisionLabel || "Division", roleConfig.divisionOptions || [], values.division || "");
        fillSelect(form.querySelector('select[name="office"]'), "Office", roleConfig.officeOptions || [], values.office || "");
    };

    const setVisible = (visible) => modal.classList.toggle("is-visible", visible);

    const setCreateMode = () => {
        form.action = form.dataset.storeAction;
        methodField.value = "";
        titleNode.textContent = "Create New User";
        form.reset();

        const defaultRadio = radios.find((radio) => radio.value === initialRole) || radios[0];
        defaultRadio.checked = true;

        applyRole(defaultRadio.value, config.oldValues || {});

        form.querySelector('input[name="name"]').value = config.oldValues?.name || "";
        form.querySelector('input[name="email"]').value = config.oldValues?.email || "";
        form.querySelector('input[name="position"]').value = config.oldValues?.position || "";
        form.querySelector('input[name="institution"]').value = config.oldValues?.institution || "";
    };

    const setEditMode = (userData) => {
        form.action = form.dataset.updateTemplate.replace("__USER__", userData.id);
        methodField.value = "PUT";
        titleNode.textContent = "Edit User";

        form.querySelector('input[name="name"]').value = userData.name || "";
        form.querySelector('input[name="email"]').value = userData.email || "";
        form.querySelector('input[name="position"]').value = userData.position || "";
        form.querySelector('input[name="institution"]').value = userData.institution || "";

        const targetRadio = radios.find((radio) => radio.value === userData.role) || radios[0];
        targetRadio.checked = true;
        applyRole(targetRadio.value, userData);
    };

    openButtons.forEach((button) => {
        button.addEventListener("click", () => {
            if (button.dataset.mode === "edit" && button.dataset.user) {
                setEditMode(JSON.parse(button.dataset.user));
            } else {
                setCreateMode();
            }

            setVisible(true);
        });
    });

    closeButton?.addEventListener("click", () => setVisible(false));
    modal.addEventListener("click", (event) => {
        if (event.target === modal) {
            setVisible(false);
        }
    });

    radios.forEach((radio) => {
        radio.addEventListener("change", () => applyRole(radio.value, {}));
    });

    applyRole(initialRole, config.oldValues || {});
})();
