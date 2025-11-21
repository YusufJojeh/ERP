/**
 * Toast Notification System
 * Advanced Project & Task Management System
 */

class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = [];
        this.maxToasts = 5;
        this.defaultDuration = 5000; // 5 seconds
        this.init();
    }

    /**
     * Initialize toast container
     */
    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.createContainer();
            });
        } else {
            this.createContainer();
        }
    }

    /**
     * Create toast container
     */
    createContainer() {
        // Check if body exists
        if (!document.body) {
            // Wait a bit and try again
            setTimeout(() => this.createContainer(), 10);
            return;
        }

        // Create container if it doesn't exist
        if (!document.querySelector('.toast-container')) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.querySelector('.toast-container');
        }
    }

    /**
     * Show toast notification
     * @param {string} type - success, error, warning, info
     * @param {string} title - Toast title
     * @param {string} message - Toast message
     * @param {number} duration - Duration in milliseconds (0 = no auto-close)
     */
    show(type, title, message, duration = null) {
        // Ensure container is ready
        if (!this.container) {
            this.createContainer();
            // If still not ready, wait a bit
            if (!this.container) {
                setTimeout(() => this.show(type, title, message, duration), 50);
                return;
            }
        }

        const toastDuration = duration !== null ? duration : this.defaultDuration;
        
        // Remove oldest toast if max reached
        if (this.toasts.length >= this.maxToasts) {
            this.remove(this.toasts[0]);
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        // Get icon based on type
        const icon = this.getIcon(type);
        
        // Create toast HTML
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="${icon}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${this.escapeHtml(title)}</div>
                <div class="toast-message">${this.escapeHtml(message)}</div>
            </div>
            <button class="toast-close" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
            ${toastDuration > 0 ? '<div class="toast-progress" style="animation-duration: ' + toastDuration + 'ms;"></div>' : ''}
        `;

        // Add to container
        if (this.container) {
            this.container.appendChild(toast);
        }
        this.toasts.push(toast);

        // Trigger show animation
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Close button handler
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => {
            this.remove(toast);
        });

        // Auto-remove if duration is set
        if (toastDuration > 0) {
            setTimeout(() => {
                this.remove(toast);
            }, toastDuration);
        }

        return toast;
    }

    /**
     * Remove toast
     * @param {HTMLElement} toast - Toast element to remove
     */
    remove(toast) {
        if (!toast || !toast.parentNode) return;

        const index = this.toasts.indexOf(toast);
        if (index > -1) {
            this.toasts.splice(index, 1);
        }

        toast.classList.remove('show');
        toast.classList.add('hide');

        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    /**
     * Remove all toasts
     */
    removeAll() {
        this.toasts.forEach(toast => {
            this.remove(toast);
        });
    }

    /**
     * Get icon for toast type
     * @param {string} type - Toast type
     * @returns {string} Icon class
     */
    getIcon(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Success toast
     */
    success(title, message, duration = null) {
        return this.show('success', title, message, duration);
    }

    /**
     * Error toast
     */
    error(title, message, duration = null) {
        return this.show('error', title, message, duration);
    }

    /**
     * Warning toast
     */
    warning(title, message, duration = null) {
        return this.show('warning', title, message, duration);
    }

    /**
     * Info toast
     */
    info(title, message, duration = null) {
        return this.show('info', title, message, duration);
    }
}

// Create global instance (will be initialized when DOM is ready)
let toastManager = null;

// Initialize toast manager when DOM is ready
function initToastManager() {
    if (!toastManager) {
        toastManager = new ToastManager();
    }
}

// Initialize immediately if DOM is already ready, otherwise wait
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initToastManager);
} else {
    initToastManager();
}

// Global helper functions
window.showToast = function(type, title, message, duration) {
    if (!toastManager) initToastManager();
    return toastManager ? toastManager.show(type, title, message, duration) : null;
};

window.showSuccess = function(title, message, duration) {
    if (!toastManager) initToastManager();
    return toastManager ? toastManager.success(title, message, duration) : null;
};

window.showError = function(title, message, duration) {
    if (!toastManager) initToastManager();
    return toastManager ? toastManager.error(title, message, duration) : null;
};

window.showWarning = function(title, message, duration) {
    if (!toastManager) initToastManager();
    return toastManager ? toastManager.warning(title, message, duration) : null;
};

window.showInfo = function(title, message, duration) {
    if (!toastManager) initToastManager();
    return toastManager ? toastManager.info(title, message, duration) : null;
};

// Auto-show toasts from PHP session/flash messages
document.addEventListener('DOMContentLoaded', function() {
    // Ensure toast manager is initialized
    if (!toastManager) {
        initToastManager();
    }

    // Wait a bit to ensure container is ready
    setTimeout(function() {
        // Check for flash messages in session
        if (typeof window.flashMessages !== 'undefined' && toastManager) {
            window.flashMessages.forEach(function(msg) {
                toastManager.show(msg.type || 'info', msg.title || 'Notification', msg.message, msg.duration);
            });
        }

        // Check for URL parameters (for redirect messages)
        const urlParams = new URLSearchParams(window.location.search);
        const success = urlParams.get('success');
        const error = urlParams.get('error');
        const warning = urlParams.get('warning');
        const info = urlParams.get('info');

        if (success && toastManager) {
            toastManager.success('Success', decodeURIComponent(success));
            // Clean URL
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, '', url);
        }

        if (error && toastManager) {
            toastManager.error('Error', decodeURIComponent(error));
            const url = new URL(window.location);
            url.searchParams.delete('error');
            window.history.replaceState({}, '', url);
        }

        if (warning && toastManager) {
            toastManager.warning('Warning', decodeURIComponent(warning));
            const url = new URL(window.location);
            url.searchParams.delete('warning');
            window.history.replaceState({}, '', url);
        }

        if (info && toastManager) {
            toastManager.info('Info', decodeURIComponent(info));
            const url = new URL(window.location);
            url.searchParams.delete('info');
            window.history.replaceState({}, '', url);
        }
    }, 100);
});

