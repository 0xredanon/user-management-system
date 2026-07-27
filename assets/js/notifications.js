/**
 * Notifications System
 *
 * A premium toast notification system with support for:
 *   - Success, Error, Warning, Info, Loading, Confirmation, Progress
 *   - Auto-dismiss with progress bar
 *   - Custom duration and actions
 *   - Smooth animations
 *
 * Usage:
 *   showNotification('success', 'User created successfully!');
 *   showNotification('error', 'Something went wrong.', { duration: 5000 });
 *   showNotification('loading', 'Saving...', { duration: 0 }); // persistent
 *   showConfirmation('Are you sure?', { onConfirm: () => { ... } });
 */

(() => {
    "use strict";

    // ─── Configuration ───────────────────────────────────────
    const DEFAULT_DURATION = 4000;
    const ERROR_DURATION = 6000;
    const SUCCESS_DURATION = 3000;
    const WARNING_DURATION = 5000;
    const INFO_DURATION = 4000;

    const ICONS = {
        success: '✓',
        error: '!',
        warning: '!',
        info: 'ⓘ',
        loading: '⟳',
        confirm: '?',
    };

    const COLORS = {
        success: { bg: 'rgba(59,178,115,.18)', border: 'rgba(59,178,115,.45)', color: '#3bb273' },
        error:   { bg: 'rgba(214,91,91,.18)',   border: 'rgba(214,91,91,.4)',   color: '#d65b5b' },
        warning: { bg: 'rgba(232,166,89,.18)',  border: 'rgba(232,166,89,.45)',  color: '#e8a659' },
        info:    { bg: 'rgba(79,172,253,.18)',  border: 'rgba(79,172,253,.45)',  color: '#4facfd' },
        loading: { bg: 'rgba(150,150,150,.18)', border: 'rgba(150,150,150,.45)', color: '#969696' },
        confirm: { bg: 'rgba(232,166,89,.18)',  border: 'rgba(232,166,89,.45)',  color: '#e8a659' },
    };

    // ─── State ───────────────────────────────────────────────
    let container = null;
    let toastIdCounter = 0;
    const activeToasts = new Map();

    // ─── Initialization ──────────────────────────────────────
    function init() {
        // Create container if it doesn't exist
        container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
    }

    // Call init when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ─── Main API ────────────────────────────────────────────

    /**
     * Show a notification.
     *
     * @param {string} type - 'success' | 'error' | 'warning' | 'info' | 'loading'
     * @param {string} message - The message to display
     * @param {object} options - { duration, actions, dismissible, id }
     * @returns {string} The toast ID (for manual dismissal)
     */
    function showNotification(type = 'info', message = '', options = {}) {
        if (!container) {
            init();
        }

        const id = options.id || `toast-${++toastIdCounter}`;
        const duration = options.duration !== undefined ? options.duration : getDefaultDuration(type);
        const dismissible = options.dismissible !== false;
        const actions = options.actions || [];

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `notification notification--${type}`;
        toast.id = `notification-${id}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');

        const color = COLORS[type] || COLORS.info;

        // Apply colors via CSS custom properties
        toast.style.setProperty('--toast-bg', color.bg);
        toast.style.setProperty('--toast-border', color.border);
        toast.style.setProperty('--toast-color', color.color);

        // Icon
        const icon = document.createElement('span');
        icon.className = 'notification__icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = ICONS[type] || ICONS.info;

        // Message
        const msg = document.createElement('span');
        msg.className = 'notification__message';
        msg.textContent = message;

        // Progress bar
        const progress = document.createElement('div');
        progress.className = 'notification__progress';
        const progressBar = document.createElement('div');
        progressBar.className = 'notification__progress-bar';
        progress.appendChild(progressBar);

        // Close button
        const close = document.createElement('button');
        close.className = 'notification__close';
        close.setAttribute('aria-label', 'بستن');
        close.innerHTML = '×';

        // Actions
        const actionsContainer = document.createElement('div');
        actionsContainer.className = 'notification__actions';

        actions.forEach(action => {
            const btn = document.createElement('button');
            btn.className = `notification__action notification__action--${action.style || 'primary'}`;
            btn.textContent = action.label;
            btn.onclick = () => {
                if (action.onClick) {
                    action.onClick();
                }
                dismiss(id);
            };
            actionsContainer.appendChild(btn);
        });

        // Assemble
        toast.appendChild(icon);
        toast.appendChild(msg);
        if (actions.length > 0) {
            toast.appendChild(actionsContainer);
        }
        if (dismissible) {
            toast.appendChild(close);
        }
        toast.appendChild(progress);

        // Add to container
        container.appendChild(toast);

        // Store reference
        activeToasts.set(id, { element: toast, timeout: null });

        // Show with animation
        requestAnimationFrame(() => {
            toast.classList.add('notification--show');
        });

        // Setup close button
        if (dismissible) {
            close.addEventListener('click', () => dismiss(id));
        }

        // Auto-dismiss
        if (duration > 0) {
            // Animate progress bar
            progressBar.style.animation = `notification-shrink ${duration}ms linear forwards`;

            const timeout = setTimeout(() => {
                dismiss(id);
            }, duration);

            const toastData = activeToasts.get(id);
            if (toastData) {
                toastData.timeout = timeout;
            }
        }

        return id;
    }

    /**
     * Dismiss a notification by ID.
     *
     * @param {string} id
     */
    function dismiss(id) {
        const toastData = activeToasts.get(id);
        if (!toastData) return;

        // Clear timeout
        if (toastData.timeout) {
            clearTimeout(toastData.timeout);
        }

        // Animate out
        toastData.element.classList.remove('notification--show');
        toastData.element.classList.add('notification--hide');

        // Remove after animation
        setTimeout(() => {
            if (toastData.element.parentNode) {
                toastData.element.parentNode.removeChild(toastData.element);
            }
            activeToasts.delete(id);
        }, 300);
    }

    /**
     * Show a confirmation dialog.
     *
     * @param {string} message
     * @param {object} options - { onConfirm, onCancel, confirmText, cancelText }
     * @returns {string} The toast ID
     */
    function showConfirmation(message, options = {}) {
        const onConfirm = options.onConfirm || (() => {});
        const onCancel = options.onCancel || (() => {});
        const confirmText = options.confirmText || 'تأیید';
        const cancelText = options.cancelText || 'لغو';

        return showNotification('confirm', message, {
            duration: 0, // persistent
            dismissible: false,
            actions: [
                {
                    label: confirmText,
                    style: 'primary',
                    onClick: onConfirm,
                },
                {
                    label: cancelText,
                    style: 'secondary',
                    onClick: onCancel,
                },
            ],
        });
    }

    /**
     * Show a loading notification.
     *
     * @param {string} message
     * @returns {string} The toast ID
     */
    function showLoading(message = 'در حال بارگذاری...') {
        return showNotification('loading', message, {
            duration: 0,
            dismissible: false,
        });
    }

    /**
     * Update a loading notification to success/error.
     *
     * @param {string} id
     * @param {string} type
     * @param {string} message
     */
    function resolveLoading(id, type = 'success', message = '') {
        const toastData = activeToasts.get(id);
        if (!toastData) return;

        // Clear timeout
        if (toastData.timeout) {
            clearTimeout(toastData.timeout);
        }

        // Update type and message
        toastData.element.className = `notification notification--${type}`;
        toastData.element.style.removeProperty('--toast-bg');
        toastData.element.style.removeProperty('--toast-border');
        toastData.element.style.removeProperty('--toast-color');

        const color = COLORS[type] || COLORS.info;
        toastData.element.style.setProperty('--toast-bg', color.bg);
        toastData.element.style.setProperty('--toast-border', color.border);
        toastData.element.style.setProperty('--toast-color', color.color);

        const icon = toastData.element.querySelector('.notification__icon');
        if (icon) {
            icon.textContent = ICONS[type] || ICONS.info;
        }

        const msg = toastData.element.querySelector('.notification__message');
        if (msg) {
            msg.textContent = message;
        }

        // Show dismissible
        toastData.element.classList.add('notification--show');

        // Auto-dismiss
        const duration = getDefaultDuration(type);
        const progressBar = toastData.element.querySelector('.notification__progress-bar');
        if (progressBar) {
            progressBar.style.animation = `notification-shrink ${duration}ms linear forwards`;
        }

        const timeout = setTimeout(() => {
            dismiss(id);
        }, duration);

        toastData.timeout = timeout;
    }

    /**
     * Dismiss all notifications.
     */
    function dismissAll() {
        for (const id of Array.from(activeToasts.keys())) {
            dismiss(id);
        }
    }

    /**
     * Get default duration for a type.
     *
     * @param {string} type
     * @returns {number}
     */
    function getDefaultDuration(type) {
        switch (type) {
            case 'success': return SUCCESS_DURATION;
            case 'error':   return ERROR_DURATION;
            case 'warning': return WARNING_DURATION;
            case 'info':    return INFO_DURATION;
            case 'loading': return 0;
            default:        return DEFAULT_DURATION;
        }
    }

    // ─── Flash Message Integration ───────────────────────────
    /**
     * Process flash messages from the server and show them as notifications.
     * Call this on page load.
     */
    function processFlashMessages() {
        const flash = window._flashMessage;
        if (flash && flash.message) {
            showNotification(flash.type || 'info', flash.message, {
                duration: getDefaultDuration(flash.type || 'info'),
            });
        }
    }

    // Process flash messages on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', processFlashMessages);
    } else {
        processFlashMessages();
    }

    // ─── Global API ──────────────────────────────────────────
    window.showNotification = showNotification;
    window.dismissNotification = dismiss;
    window.dismissAllNotifications = dismissAll;
    window.showConfirmation = showConfirmation;
    window.showLoading = showLoading;
    window.resolveLoading = resolveLoading;

})();
