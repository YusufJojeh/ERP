/**
 * ERP API Client
 * JavaScript client for API operations
 */

class ERPApi {
    constructor() {
        this.baseUrl = window.location.origin + '/ERP';
        this.apiUrl = this.baseUrl + '/api.php';
    }
    
    /**
     * Make API request
     */
    async request(endpoint, options = {}) {
        const url = `${this.apiUrl}?action=${endpoint}`;
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        const config = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'API request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
    
    // Notification API
    async getNotifications(limit = 10, unreadOnly = false) {
        const params = new URLSearchParams({
            limit: limit,
            unread_only: unreadOnly ? '1' : '0'
        });
        
        return this.request(`notifications?${params}`);
    }
    
    async markNotificationAsRead(notificationId) {
        return this.request('notifications', {
            method: 'POST',
            body: JSON.stringify({ notification_id: notificationId })
        });
    }
    
    async markAllNotificationsAsRead() {
        return this.request('notifications', {
            method: 'POST',
            body: JSON.stringify({ action: 'mark_all' })
        });
    }
    
    async deleteNotification(notificationId) {
        return this.request('notifications', {
            method: 'DELETE',
            body: JSON.stringify({ notification_id: notificationId })
        });
    }
    
    // Task API
    async getTasks(filters = {}) {
        const params = new URLSearchParams(filters);
        return this.request(`tasks?${params}`);
    }
    
    async createTask(taskData) {
        return this.request('tasks', {
            method: 'POST',
            body: JSON.stringify(taskData)
        });
    }
    
    async updateTask(taskId, taskData) {
        return this.request('tasks', {
            method: 'PUT',
            body: JSON.stringify({ id: taskId, ...taskData })
        });
    }
    
    async deleteTask(taskId) {
        return this.request('tasks', {
            method: 'DELETE',
            body: JSON.stringify({ id: taskId })
        });
    }
    
    async updateTaskStatus(taskId, status) {
        return this.request('tasks', {
            method: 'PUT',
            body: JSON.stringify({ 
                id: taskId, 
                action: 'update_status',
                status: status 
            })
        });
    }
    
    // Project API
    async getProjects(filters = {}) {
        const params = new URLSearchParams(filters);
        return this.request(`projects?${params}`);
    }
    
    async createProject(projectData) {
        return this.request('projects', {
            method: 'POST',
            body: JSON.stringify(projectData)
        });
    }
    
    async updateProject(projectId, projectData) {
        return this.request('projects', {
            method: 'PUT',
            body: JSON.stringify({ id: projectId, ...projectData })
        });
    }
    
    async deleteProject(projectId) {
        return this.request('projects', {
            method: 'DELETE',
            body: JSON.stringify({ id: projectId })
        });
    }
    
    // User API
    async getUsers(filters = {}) {
        const params = new URLSearchParams(filters);
        return this.request(`users?${params}`);
    }
    
    async createUser(userData) {
        return this.request('users', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }
    
    async updateUser(userId, userData) {
        return this.request('users', {
            method: 'PUT',
            body: JSON.stringify({ id: userId, ...userData })
        });
    }
    
    async deleteUser(userId) {
        return this.request('users', {
            method: 'DELETE',
            body: JSON.stringify({ id: userId })
        });
    }
    
    // Comment API
    async getComments(entityType, entityId) {
        return this.request(`comments?entity_type=${entityType}&entity_id=${entityId}`);
    }
    
    async createComment(entityType, entityId, content, isInternal = false) {
        return this.request('comments', {
            method: 'POST',
            body: JSON.stringify({
                entity_type: entityType,
                entity_id: entityId,
                content: content,
                is_internal: isInternal
            })
        });
    }
    
    async updateComment(commentId, content) {
        return this.request('comments', {
            method: 'PUT',
            body: JSON.stringify({ id: commentId, content: content })
        });
    }
    
    async deleteComment(commentId) {
        return this.request('comments', {
            method: 'DELETE',
            body: JSON.stringify({ id: commentId })
        });
    }
    
    // Attachment API
    async getAttachments(entityType, entityId) {
        return this.request(`attachments?entity_type=${entityType}&entity_id=${entityId}`);
    }
    
    async uploadAttachment(entityType, entityId, file) {
        const formData = new FormData();
        formData.append('entity_type', entityType);
        formData.append('entity_id', entityId);
        formData.append('file', file);
        formData.append('format', 'json');
        
        return this.request('attachments', {
            method: 'POST',
            body: formData,
            headers: {} // Remove Content-Type header for FormData
        });
    }
    
    async deleteAttachment(attachmentId) {
        return this.request('attachments', {
            method: 'DELETE',
            body: JSON.stringify({ id: attachmentId })
        });
    }
    
    // Activity API
    async getActivityStats(filters = {}) {
        const params = new URLSearchParams(filters);
        return this.request(`activity?${params}`);
    }
    
    // Utility methods
    async downloadFile(attachmentId) {
        window.open(`${this.baseUrl}/index.php?controller=Attachment&action=download&id=${attachmentId}`);
    }
    
    async viewFile(attachmentId) {
        window.open(`${this.baseUrl}/index.php?controller=Attachment&action=view&id=${attachmentId}`);
    }
}

// Create global API instance
window.erpApi = new ERPApi();

// Utility functions for common operations
window.updateTaskStatus = async function(taskId, status) {
    try {
        await erpApi.updateTaskStatus(taskId, status);
        location.reload();
    } catch (error) {
        alert('Failed to update task status: ' + error.message);
    }
};

window.addComment = async function(entityType, entityId, content) {
    try {
        await erpApi.createComment(entityType, entityId, content);
        location.reload();
    } catch (error) {
        alert('Failed to add comment: ' + error.message);
    }
};

window.uploadFile = async function(entityType, entityId, fileInput) {
    const file = fileInput.files[0];
    if (!file) {
        alert('Please select a file');
        return;
    }
    
    try {
        await erpApi.uploadAttachment(entityType, entityId, file);
        location.reload();
    } catch (error) {
        alert('Failed to upload file: ' + error.message);
    }
};

window.markNotificationAsRead = async function(notificationId) {
    try {
        await erpApi.markNotificationAsRead(notificationId);
        // Update UI without reload
        const notification = document.getElementById(`notification-${notificationId}`);
        if (notification) {
            notification.classList.remove('bg-light');
            notification.querySelector('.fw-bold')?.classList.remove('fw-bold');
            notification.querySelector('.badge')?.remove();
        }
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

// Auto-refresh notifications every 30 seconds
setInterval(async () => {
    try {
        const response = await erpApi.getNotifications(1, true);
        const unreadCount = response.data.unread_count;
        
        // Update notification badge
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Failed to refresh notifications:', error);
    }
}, 30000);
