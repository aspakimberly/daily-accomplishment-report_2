(() => {
    const modal = document.querySelector("[data-signout-modal]");
    const openButton = document.querySelector("[data-open-signout-modal]");
    const closeButton = document.querySelector("[data-close-signout-modal]");

    if (!modal || !openButton || !closeButton) {
        return;
    }

    const setVisible = (visible) => modal.classList.toggle("is-visible", visible);

    openButton.addEventListener("click", () => setVisible(true));
    closeButton.addEventListener("click", () => setVisible(false));

    modal.addEventListener("click", (event) => {
        if (event.target === modal) {
            setVisible(false);
        }
    });
})();

(() => {
    const avatarInput = document.querySelector("[data-avatar-input]");
    const signatureInput = document.querySelector("[data-signature-input]");
    const profileForm = document.querySelector(".profile-update-shell");

    let avatarObjectUrl = null;
    let signatureObjectUrl = null;
    let isSubmitting = false;

    const submitProfileForm = () => {
        if (!profileForm || isSubmitting) {
            return;
        }

        isSubmitting = true;
        profileForm.requestSubmit();
    };

    const previewFile = (input, options) => {
        if (!input) {
            return;
        }

        input.addEventListener("change", () => {
            const file = input.files && input.files[0] ? input.files[0] : null;

            if (!file || !file.type.startsWith("image/")) {
                return;
            }

            const objectUrl = URL.createObjectURL(file);
            let preview = document.querySelector(options.previewSelector);

            if (!preview) {
                preview = document.createElement("img");
                preview.className = options.previewClassName;
                preview.alt = options.altText;
                preview.setAttribute(options.previewDataAttribute, "");
                options.createPreview(preview);
            }

            preview.src = objectUrl;

            const placeholder = options.placeholderSelector
                ? document.querySelector(options.placeholderSelector)
                : null;

            if (placeholder) {
                placeholder.remove();
            }

            if (options.onNewUrl) {
                options.onNewUrl(objectUrl);
            }

            if (options.autoSubmit) {
                submitProfileForm();
            }
        });
    };

    previewFile(avatarInput, {
        previewSelector: "[data-avatar-preview]",
        previewClassName: "avatar-preview",
        altText: "Profile preview",
        previewDataAttribute: "data-avatar-preview",
        placeholderSelector: "[data-avatar-placeholder]",
        createPreview: (preview) => {
            const avatarUpload = avatarInput.closest(".avatar-upload");

            if (!avatarUpload) {
                return;
            }

            avatarUpload.insertBefore(preview, avatarInput);
        },
        onNewUrl: (objectUrl) => {
            if (avatarObjectUrl) {
                URL.revokeObjectURL(avatarObjectUrl);
            }

            avatarObjectUrl = objectUrl;
        },
        autoSubmit: true,
    });

    previewFile(signatureInput, {
        previewSelector: "[data-signature-preview]",
        previewClassName: "signature-preview",
        altText: "Signature preview",
        previewDataAttribute: "data-signature-preview",
        placeholderSelector: "",
        createPreview: (preview) => {
            const identityCopy = signatureInput.closest(".identity-copy");

            if (!identityCopy) {
                return;
            }

            identityCopy.appendChild(preview);
        },
        onNewUrl: (objectUrl) => {
            if (signatureObjectUrl) {
                URL.revokeObjectURL(signatureObjectUrl);
            }

            signatureObjectUrl = objectUrl;
        },
        autoSubmit: true,
    });

    window.addEventListener("beforeunload", () => {
        if (avatarObjectUrl) {
            URL.revokeObjectURL(avatarObjectUrl);
        }

        if (signatureObjectUrl) {
            URL.revokeObjectURL(signatureObjectUrl);
        }
    });
})();
