<?php
// config.php is already loaded in index.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}

$pageTitle = 'Notifications';
$currentPage = 'notifications';
$pageDescription = 'View and manage your notifications';

// Include header
include __DIR__ . '/../includes/header.php';

// Get notifications data
$notificationModel = new Notification();
$user_id = $_SESSION['user_id'];
$limit = (int)($_GET['limit'] ?? 50);
$page = (int)($_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;
$unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === '1';

$notifications = $notificationModel->getUserNotifications($user_id, $limit, $unread_only);
$unread_count = $notificationModel->getUnreadCount($user_id);
// Get all notifications for pagination count
$allNotifications = $notificationModel->getUserNotifications($user_id, 0, false);
$totalNotifications = count($allNotifications);
$totalPages = ceil($totalNotifications / $limit);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Notifications</h1>
                    <p class="text-muted">Manage your notifications</p>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($unread_count > 0): ?>
                    <button type="button" class="btn btn-success" id="markAllReadBtn">
                        <i class="fas fa-check-double me-2"></i>Mark All as Read
                    </button>
                    <?php endif; ?>
                    <a href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=Dashboard&action=dashboard" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Filter Notifications</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Filter</label>
                            <select class="form-select" name="unread_only" onchange="this.form.submit()">
                                <option value="0" <?php echo !$unread_only ? 'selected' : ''; ?>>All Notifications</option>
                                <option value="1" <?php echo $unread_only ? 'selected' : ''; ?>>Unread Only</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Limit</label>
                            <select class="form-select" name="limit" onchange="this.form.submit()">
                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        Notifications 
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger"><?php echo $unread_count; ?> unread</span>
                        <?php endif; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No notifications found</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item <?php echo $notification['is_read'] == 0 ? 'list-group-item-primary' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <h6 class="mb-0 me-2">
                                                    <?php echo htmlspecialchars($notification['title']); ?>
                                                    <?php if ($notification['is_read'] == 0): ?>
                                                        <span class="badge bg-danger ms-2">New</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <span class="badge bg-<?php 
                                                    echo $notification['type'] === 'success' ? 'success' : 
                                                        ($notification['type'] === 'warning' ? 'warning' : 
                                                        ($notification['type'] === 'error' ? 'danger' : 'info')); 
                                                ?>">
                                                    <?php echo ucfirst($notification['type']); ?>
                                                </span>
                                            </div>
                                            <p class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo timeAgo($notification['created_at']); ?>
                                                <?php if ($notification['related_entity_type'] && $notification['related_entity_id']): ?>
                                                    | Related: <?php echo ucfirst($notification['related_entity_type']); ?> #<?php echo $notification['related_entity_id']; ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <?php if ($notification['is_read'] == 0): ?>
                                                <button type="button" class="btn btn-sm btn-success mark-read-btn" 
                                                        data-notification-id="<?php echo $notification['id']; ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-danger delete-notification-btn" 
                                                    data-notification-id="<?php echo $notification['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Notifications pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&unread_only=<?php echo $unread_only ? 1 : 0; ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&unread_only=<?php echo $unread_only ? 1 : 0; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&unread_only=<?php echo $unread_only ? 1 : 0; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark notification as read
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-notification-id');
            markAsRead(notificationId);
        });
    });

    // Delete notification
    document.querySelectorAll('.delete-notification-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this notification?')) {
                const notificationId = this.getAttribute('data-notification-id');
                deleteNotification(notificationId);
            }
        });
    });

    // Mark all as read
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            if (confirm('Mark all notifications as read?')) {
                markAllAsRead();
            }
        });
    }
});

function markAsRead(notificationId) {
    fetch('<?php echo APP_URL; ?>/index.php?controller=Notification&action=markAsRead', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to mark notification as read');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

function markAllAsRead() {
    fetch('<?php echo APP_URL; ?>/index.php?controller=Notification&action=markAllAsRead', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to mark all notifications as read');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

function deleteNotification(notificationId) {
    fetch('<?php echo APP_URL; ?>/index.php?controller=Notification&action=delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to delete notification');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

