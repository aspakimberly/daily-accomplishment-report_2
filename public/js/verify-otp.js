(() => {
    const form = document.querySelector("[data-otp-form]");

    if (!form) {
        return;
    }

    const hiddenInput = form.querySelector("[data-otp-hidden]");
    const inputs = Array.from(form.querySelectorAll("[data-otp-input]"));
    const timerNode = document.querySelector("[data-otp-timer]");
    const resendButton = document.querySelector("[data-resend-button]");
    const resendAvailableAt = window.otpConfig?.resendAvailableAt ? new Date(window.otpConfig.resendAvailableAt) : null;

    const syncOtpValue = () => {
        hiddenInput.value = inputs.map((input) => input.value.replace(/\D/g, "")).join("");
    };

    inputs.forEach((input, index) => {
        input.addEventListener("input", (event) => {
            const value = event.target.value.replace(/\D/g, "");
            event.target.value = value.slice(-1);
            syncOtpValue();

            if (event.target.value && inputs[index + 1]) {
                inputs[index + 1].focus();
                inputs[index + 1].select();
            }
        });

        input.addEventListener("keydown", (event) => {
            if (event.key === "Backspace" && !event.target.value && inputs[index - 1]) {
                inputs[index - 1].focus();
            }
        });

        input.addEventListener("paste", (event) => {
            event.preventDefault();

            const pasted = event.clipboardData.getData("text").replace(/\D/g, "").slice(0, inputs.length);

            pasted.split("").forEach((digit, pastedIndex) => {
                if (inputs[pastedIndex]) {
                    inputs[pastedIndex].value = digit;
                }
            });

            syncOtpValue();
        });
    });

    const updateTimer = () => {
        if (!timerNode || !resendButton || !resendAvailableAt) {
            return;
        }

        const remainingMs = resendAvailableAt.getTime() - Date.now();

        if (remainingMs <= 0) {
            timerNode.textContent = "0:00";
            resendButton.disabled = false;
            return;
        }

        const totalSeconds = Math.ceil(remainingMs / 1000);
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        timerNode.textContent = `${minutes}:${String(seconds).padStart(2, "0")}`;
        resendButton.disabled = true;
        window.setTimeout(updateTimer, 1000);
    };

    syncOtpValue();
    updateTimer();
})();
