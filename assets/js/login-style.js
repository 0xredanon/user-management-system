(() => {
    "use strict";

    const messages = {
        wrong_password: "رمز عبور اشتباه است.",
        empty_fields: "لطفاً تمام فیلدها را پر کنید.",
        server: "مشکلی در سرور پیش آمد، لطفاً دوباره تلاش کنید.",
        password_mismatch: "رمز عبور و تکرار آن مطابقت ندارد.",
        user_exists: "این نام کاربری یا ایمیل قبلاً ثبت شده است.",
        registered: "ثبت‌نام با موفقیت انجام شد. حالا وارد شوید.",
        registration_failed: "ثبت‌نام با مشکل مواجه شد. لطفاً اطلاعات دیگری امتحان کنید.",
        login_success: "در حال انتقال..."
    };

    const REDIRECT_URL = "../views/dashboard.php";
    const SUCCESS_DURATION = 2000;
    const ERROR_DURATION = 5000;

    const toast = document.getElementById("toast");
    const icon = document.getElementById("toast-icon");
    const message = document.getElementById("toast-message");
    const close = document.getElementById("toast-close");
    const progress = document.getElementById("toast-progress");
    const progressBar = document.getElementById("toast-progress-bar");

    // اگر Toast داخل صفحه وجود نداشت
    if (
        !toast ||
        !icon ||
        !message ||
        !close ||
        !progress ||
        !progressBar
    ) {
        return;
    }

    let hideTimer = null;
    let redirectTimer = null;

    function hideToast() {
        clearTimeout(hideTimer);
        clearTimeout(redirectTimer);

        toast.classList.remove("show");
        progress.hidden = true;
        progressBar.style.animation = "none";
    }

    function showToast({
        type = "error",
        messageText = "",
        duration = ERROR_DURATION,
        redirect = null
    }) {
        hideToast();

        toast.classList.remove("toast--success", "toast--error");
        toast.classList.add(type === "success" ? "toast--success" : "toast--error");

        icon.textContent = type === "success" ? "✓" : "!";
        message.textContent = messageText;

        if (redirect) {
            progress.hidden = false;

            progressBar.style.animation = "none";
            void progressBar.offsetWidth;

            progressBar.style.setProperty("--toast-duration", `${duration}ms`);
            progressBar.style.animation = `toast-shrink ${duration}ms linear forwards`;

            redirectTimer = setTimeout(() => {
                window.location.href = redirect;
            }, duration);
        } else {
            progress.hidden = true;

            hideTimer = setTimeout(() => {
                toast.classList.remove("show");
            }, duration);
        }

        requestAnimationFrame(() => {
            toast.classList.add("show");
        });
    }

    close.addEventListener("click", hideToast);

    const params = new URLSearchParams(window.location.search);

    const error = params.get("error");
    const success = params.get("success");

    if (success) {

        const redirect =
            success === "registered"
                ? "login.php"
                : REDIRECT_URL;

        showToast({
            type: "success",
            messageText: messages[success] || messages.login_success,
            duration: SUCCESS_DURATION,
            redirect
        });

    } else if (error && messages[error]) {

        showToast({
            type: "error",
            messageText: messages[error]
        });

    }

    // پاک کردن Query String
    if (window.location.search.length > 0) {
        history.replaceState({}, document.title, window.location.pathname);
    }

})();