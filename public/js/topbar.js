(() => {
    const menus = Array.from(document.querySelectorAll("[data-notification-menu]"));

    if (!menus.length) {
        return;
    }

    const closeAll = () => {
        menus.forEach((menu) => {
            const toggle = menu.querySelector("[data-notification-toggle]");
            const panel = menu.querySelector("[data-notification-panel]");

            toggle?.setAttribute("aria-expanded", "false");

            if (panel) {
                panel.hidden = true;
            }
        });
    };

    menus.forEach((menu) => {
        const toggle = menu.querySelector("[data-notification-toggle]");
        const panel = menu.querySelector("[data-notification-panel]");

        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener("click", (event) => {
            event.stopPropagation();
            const isOpen = toggle.getAttribute("aria-expanded") === "true";

            closeAll();

            if (!isOpen) {
                toggle.setAttribute("aria-expanded", "true");
                panel.hidden = false;
            }
        });

        panel.addEventListener("click", (event) => event.stopPropagation());
    });

    document.addEventListener("click", () => closeAll());
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeAll();
        }
    });
})();
