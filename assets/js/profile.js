/**
 * Profile Form Validation & Submission
 * Uses the new notification system for error messages.
 */

(() => {
    "use strict";

    const form = document.getElementById("profileForm");

    if (!form) return;

    const username = form.querySelector('[name="username"]');
    const email = form.querySelector('[name="email"]');
    const currentPassword = form.querySelector('[name="current_password"]');
    const newPassword = form.querySelector('[name="new_password"]');
    const confirmPassword = form.querySelector('[name="confirm_password"]');

    const submitBtn = form.querySelector("button[type='submit']");

    function showError(message) {
        if (typeof showNotification === "function") {
            showNotification("error", message);
        } else {
            alert(message);
        }
    }

    form.addEventListener("submit", function (e) {

        const user = username.value.trim();
        const mail = email.value.trim();

        if (user.length < 3) {
            e.preventDefault();
            showError("نام کاربری باید حداقل ۳ کاراکتر باشد.");
            username.focus();
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(mail)) {
            e.preventDefault();
            showError("ایمیل معتبر نیست.");
            email.focus();
            return;
        }

        const changingPassword =
            currentPassword.value ||
            newPassword.value ||
            confirmPassword.value;

        if (changingPassword) {

            if (!currentPassword.value) {
                e.preventDefault();
                showError("رمز عبور فعلی را وارد کنید.");
                currentPassword.focus();
                return;
            }

            if (newPassword.value.length < 8) {
                e.preventDefault();
                showError("رمز عبور جدید باید حداقل ۸ کاراکتر باشد.");
                newPassword.focus();
                return;
            }

            if (newPassword.value !== confirmPassword.value) {
                e.preventDefault();
                showError("تکراب رمز عبور صحیح نیست.");
                confirmPassword.focus();
                return;
            }

        }

        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = "در حال ذخیره...";

    });

})();
