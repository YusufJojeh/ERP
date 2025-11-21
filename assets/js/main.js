/**
 * Main JavaScript for Advanced Project & Task Management System
 */

// Global variables
let currentUser = {
    id: null,
    role: null,
    name: null
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme(); // Initialize theme (light mode only)
    initializeApp();
    setupEventListeners();
    loadUserData();
    initializeScrollAnimations(); // Initialize scroll animations
});

/**
 * Initialize Theme (Light Mode Only)
 */
function initializeTheme() {
    const html = document.documentElement;
    
    // Force light mode only
    html.setAttribute('data-theme', 'light');
    
    // Update charts if they exist (using light mode colors)
    if (typeof Chart !== 'undefined') {
        Chart.helpers.each(Chart.instances, (chart) => {
            updateChartColors(chart);
            chart.update('none');
        });
    }
}

/**
 * Initialize scroll animations using Intersection Observer
 */
function initializeScrollAnimations() {
    const animatedElements = document.querySelectorAll('.animate-on-scroll, .fade-in');
    
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        animatedElements.forEach(el => observer.observe(el));
    } else {
        // Fallback for browsers without Intersection Observer
        animatedElements.forEach(el => el.classList.add('animated'));
    }
}

/**
 * Initialize the application
 */
function initializeApp() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Initialize sidebar toggle for mobile
    initializeSidebar();
    
    // Initialize form validations
    initializeFormValidations();
    
    // Initialize AJAX forms
    initializeAjaxForms();
    
    // Initialize number counter animations for stat cards
    initializeNumberCounters();
    
    // Initialize chart loading animations
    initializeChartAnimations();
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Global click handlers
    document.addEventListener('click', function(e) {
        // Handle delete confirmations
        if (e.target.matches('[data-confirm]')) {
            const message = e.target.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        }

        // Handle AJAX links
        if (e.target.matches('[data-ajax]')) {
            e.preventDefault();
            handleAjaxLink(e.target);
        }
    });

    // Handle form submissions
    document.addEventListener('submit', function(e) {
        if (e.target.matches('[data-ajax-form]')) {
            e.preventDefault();
            handleAjaxForm(e.target);
        }
    });

    // Handle real-time updates
    if (typeof(EventSource) !== "undefined") {
        initializeRealTimeUpdates();
    }
}

/**
 * Load user data from session
 */
function loadUserData() {
    // This would typically be loaded from a server endpoint
    // For now, we'll use data attributes if available
    const userDataElement = document.querySelector('[data-user-data]');
    if (userDataElement) {
        try {
            currentUser = JSON.parse(userDataElement.getAttribute('data-user-data'));
        } catch (e) {
            console.error('Error parsing user data:', e);
        }
    }
}

/**
 * Sidebar removed - navigation in navbar only
 */
function initializeSidebar() {
    // Sidebar functionality removed
}

/**
 * Initialize number counter animations for stat cards
 */
function initializeNumberCounters() {
    const statValues = document.querySelectorAll('.stat-value');
    
    statValues.forEach(statValue => {
        const targetValue = parseInt(statValue.textContent);
        if (isNaN(targetValue)) return;
        
        let currentValue = 0;
        const increment = targetValue / 30; // 30 frames for smooth animation
        const duration = 300; // 300ms as per requirements
        const frameTime = duration / 30;
        
        const counter = setInterval(() => {
            currentValue += increment;
            if (currentValue >= targetValue) {
                statValue.textContent = targetValue;
                clearInterval(counter);
            } else {
                statValue.textContent = Math.floor(currentValue);
            }
        }, frameTime);
    });
}

/**
 * Initialize chart loading animations
 */
function initializeChartAnimations() {
    const chartContainers = document.querySelectorAll('.chart-container');
    
    chartContainers.forEach(container => {
        container.style.opacity = '0';
        container.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
            container.style.transition = 'all 300ms cubic-bezier(0.4, 0, 0.2, 1)';
            container.style.opacity = '1';
            container.style.transform = 'scale(1)';
        }, 100);
    });
}

/**
 * Initialize form validations
 */
function initializeFormValidations() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

/**
 * Initialize AJAX forms
 */
function initializeAjaxForms() {
    const ajaxForms = document.querySelectorAll('[data-ajax-form]');
    
    ajaxForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleAjaxForm(form);
        });
    });
}

/**
 * Handle AJAX form submission
 */
function handleAjaxForm(form) {
    const formData = new FormData(form);
    const url = form.action || window.location.href;
    const method = form.method || 'POST';
    
    // Show loading state
    const submitBtn = form.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    
    fetch(url, {
        method: method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message || 'Operation completed successfully');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            }
        } else {
            showNotification('error', data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred while processing your request');
    })
    .finally(() => {
        // Reset button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

/**
 * Handle AJAX link clicks
 */
function handleAjaxLink(link) {
    const url = link.href;
    const method = link.getAttribute('data-method') || 'GET';
    
    fetch(url, {
        method: method,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message || 'Operation completed successfully');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            }
        } else {
            showNotification('error', data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred while processing your request');
    });
}

/**
 * Show notification
 */
function showNotification(type, message, duration = 5000) {
    const alertClass = `alert-${type}`;
    const iconClass = getNotificationIcon(type);
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="${iconClass} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after duration
    setTimeout(() => {
        if (notification.parentNode) {
            const bsAlert = new bootstrap.Alert(notification);
            bsAlert.close();
        }
    }, duration);
}

/**
 * Get notification icon based on type
 */
function getNotificationIcon(type) {
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    return icons[type] || 'fas fa-info-circle';
}

/**
 * Initialize real-time updates
 */
function initializeRealTimeUpdates() {
    // This would connect to a WebSocket or Server-Sent Events
    // For now, we'll just set up the structure
    console.log('Real-time updates initialized');
}

/**
 * Format date for display
 */
function formatDate(dateString, format = 'MMM dd, yyyy') {
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'short',
        day: '2-digit'
    };
    return date.toLocaleDateString('en-US', options);
}

/**
 * Format time ago
 */
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
    if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' days ago';
    if (diffInSeconds < 31536000) return Math.floor(diffInSeconds / 2592000) + ' months ago';
    
    return Math.floor(diffInSeconds / 31536000) + ' years ago';
}

/**
 * Debounce function
 */
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

/**
 * Throttle function
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('success', 'Copied to clipboard');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('success', 'Copied to clipboard');
    }
}

/**
 * Export data to CSV
 */
function exportToCSV(data, filename) {
    const csv = convertToCSV(data);
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

/**
 * Convert data to CSV format
 */
function convertToCSV(data) {
    if (!data || data.length === 0) return '';
    
    const headers = Object.keys(data[0]);
    const csvContent = [
        headers.join(','),
        ...data.map(row => headers.map(header => `"${row[header] || ''}"`).join(','))
    ].join('\n');
    
    return csvContent;
}

/**
 * Search functionality
 */
function initializeSearch() {
    const searchInputs = document.querySelectorAll('[data-search]');
    
    searchInputs.forEach(input => {
        const targetSelector = input.getAttribute('data-search');
        const targets = document.querySelectorAll(targetSelector);
        
        input.addEventListener('input', debounce(function() {
            const searchTerm = this.value.toLowerCase();
            
            targets.forEach(target => {
                const text = target.textContent.toLowerCase();
                const shouldShow = text.includes(searchTerm);
                target.style.display = shouldShow ? '' : 'none';
            });
        }, 300));
    });
}

/**
 * Initialize search on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeSearch();
});

/**
 * Global error handler
 */
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    showNotification('error', 'An unexpected error occurred');
});

/**
 * Handle unhandled promise rejections
 */
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    showNotification('error', 'An unexpected error occurred');
});

/**
 * Read CSS Variable
 */
function cssVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

/**
 * Chart.js Integration with CSS Variables
 */
function initializeChartsWithTheme() {
    if (typeof Chart === 'undefined') return;
    
    // Set default Chart.js colors from CSS Variables
    Chart.defaults.color = cssVar('--text');
    Chart.defaults.borderColor = cssVar('--border');
    
    // Update chart colors helper
    window.updateChartColors = function(chart) {
        const ctx = chart.ctx;
        const brandPrimary = cssVar('--brand-primary');
        const brandAccent = cssVar('--brand-accent');
        const brandHighlight = cssVar('--brand-highlight');
        
        // Create gradient function
        const createGradient = (ctx, color1, color2) => {
            const gradient = ctx.createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, color1);
            gradient.addColorStop(1, color2);
            return gradient;
        };
        
        // Update datasets with new colors
        chart.data.datasets.forEach((dataset, index) => {
            if (dataset.type === 'line' || dataset.type === 'bar') {
                if (index === 0) {
                    dataset.borderColor = brandAccent;
                    dataset.backgroundColor = (c) => {
                        const g = c.chart.ctx.createLinearGradient(0, 0, 0, 200);
                        g.addColorStop(0, 'rgba(34, 193, 195, 0.35)');
                        g.addColorStop(1, 'rgba(110, 86, 207, 0.05)');
                        return g;
                    };
                }
            } else if (dataset.type === 'doughnut' || dataset.type === 'pie') {
                dataset.backgroundColor = [
                    brandPrimary,
                    brandAccent,
                    brandHighlight,
                    cssVar('--badge-completed'),
                    cssVar('--badge-pending')
                ];
            }
        });
        
        // Update scales
        if (chart.options.scales) {
            Object.keys(chart.options.scales).forEach(scaleKey => {
                const scale = chart.options.scales[scaleKey];
                if (scale.grid) {
                    scale.grid.color = cssVar('--border');
                }
                if (scale.ticks) {
                    scale.ticks.color = cssVar('--text');
                }
            });
        }
    };
}

/**
 * Initialize Dashboard Charts with Theme Support
 */
function initializeDashboardCharts() {
    if (typeof Chart === 'undefined' || !window.chartData) return;
    
    const chartData = window.chartData;
    const brandPrimary = cssVar('--brand-primary');
    const brandAccent = cssVar('--brand-accent');
    const brandHighlight = cssVar('--brand-highlight');
    
    // Project Status Chart
    if (chartData.projectStatus && document.getElementById('projectStatusChart')) {
        const ctx = document.getElementById('projectStatusChart').getContext('2d');
        window.projectStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.projectStatus.labels || [],
                datasets: [{
                    data: chartData.projectStatus.data || [0, 0, 0, 0],
                    backgroundColor: [
                        brandPrimary,
                        brandAccent,
                        brandHighlight,
                        cssVar('--badge-completed')
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: cssVar('--text'),
                            padding: 15
                        }
                    }
                }
            }
        });
    }
    
    // Task Priority Chart
    if (chartData.taskPriority && document.getElementById('taskPriorityChart')) {
        const ctx = document.getElementById('taskPriorityChart').getContext('2d');
        window.taskPriorityChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.taskPriority.labels || ['Low', 'Medium', 'High', 'Critical'],
                datasets: [{
                    data: chartData.taskPriority.data || [0, 0, 0, 0],
                    backgroundColor: [
                        brandAccent,
                        brandPrimary,
                        brandHighlight,
                        cssVar('--badge-overdue')
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: cssVar('--text'),
                            padding: 15
                        }
                    }
                }
            }
        });
    }
    
    // Task Status Chart
    if (chartData.taskStatus && document.getElementById('taskStatusChart')) {
        const ctx = document.getElementById('taskStatusChart').getContext('2d');
        window.taskStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.taskStatus.labels || [],
                datasets: [{
                    data: chartData.taskStatus.data || [0, 0, 0, 0],
                    backgroundColor: [
                        cssVar('--muted'),
                        cssVar('--badge-completed'),
                        cssVar('--badge-pending'),
                        brandAccent
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: cssVar('--text'),
                            padding: 15
                        }
                    }
                }
            }
        });
    }
    
    // Daily Activity Chart
    if (chartData.dailyActivity && document.getElementById('dailyActivityChart')) {
        const ctx = document.getElementById('dailyActivityChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0, 'rgba(34, 193, 195, 0.35)');
        gradient.addColorStop(1, 'rgba(110, 86, 207, 0.05)');
        
        window.dailyActivityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.dailyActivity.labels || [],
                datasets: [{
                    label: 'Activities',
                    data: chartData.dailyActivity.data || [],
                    borderColor: brandAccent,
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 6
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: cssVar('--text')
                        }
                    },
                    y: {
                        grid: {
                            color: cssVar('--border')
                        },
                        ticks: {
                            color: cssVar('--text'),
                            callback: (v) => v + ''
                        },
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

// Initialize charts when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeChartsWithTheme();
    if (document.getElementById('projectStatusChart')) {
        initializeDashboardCharts();
    }
});

// Export functions for global use
window.ERP = {
    showNotification,
    formatDate,
    timeAgo,
    copyToClipboard,
    exportToCSV,
    debounce,
    throttle,
    cssVar,
    updateChartColors: window.updateChartColors
};
