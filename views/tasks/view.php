<?php
// config.php is already loaded in index.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}
include __DIR__ . '/../includes/header.php';

// Get task ID from URL
$task_id = (int)($_GET['id'] ?? 0);

if (!$task_id) {
    header('HTTP/1.1 404 Not Found');
    include '../errors/404.php';
    exit();
}

// Load task data
$taskModel = new Task();
$task = $taskModel->getTaskById($task_id);

if (!$task) {
    header('HTTP/1.1 404 Not Found');
    include '../errors/404.php';
    exit();
}

// Get related data
$projectModel = new Project();
$userModel = new User();
$project = $projectModel->getProjectById($task['project_id']);
$assignedUser = $userModel->getUserById($task['assigned_to']);
$createdBy = $userModel->getUserById($task['created_by']);

// Get comments
$commentModel = new Comment();
$comments = $commentModel->getCommentsByEntity('task', $task_id);

// Get attachments
$attachmentModel = new Attachment();
$attachments = $attachmentModel->getTaskAttachments($task_id);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Task Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><?php echo htmlspecialchars($task['title']); ?></h1>
                    <p class="text-muted mb-0">
                        <a href="../projects/view.php?id=<?php echo $task['project_id']; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($project['name'] ?? 'Unknown Project'); ?>
                        </a>
                    </p>
                </div>
                <div>
                    <a href="edit.php?id=<?php echo $task['id']; ?>" class="btn btn-outline-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit Task
                    </a>
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Tasks
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Task Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Task Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Description</h6>
                                    <p><?php echo nl2br(htmlspecialchars($task['description'] ?? 'No description provided')); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Progress</h6>
                                    <div class="progress mb-3" style="height: 25px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo $task['progress'] ?? 0; ?>%"
                                             aria-valuenow="<?php echo $task['progress'] ?? 0; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $task['progress'] ?? 0; ?>%
                                        </div>
                                    </div>
                                    
                                    <h6>Estimated Hours</h6>
                                    <p><?php echo $task['estimated_hours'] ?? 'Not specified'; ?> hours</p>
                                    
                                    <h6>Actual Hours</h6>
                                    <p><?php echo $task['actual_hours'] ?? 'Not tracked'; ?> hours</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Comments</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($comments)): ?>
                                <p class="text-muted">No comments yet.</p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <?php 
                                    // Build author name from first_name and last_name, fallback to username
                                    $firstName = $comment['first_name'] ?? '';
                                    $lastName = $comment['last_name'] ?? '';
                                    $authorName = trim($firstName . ' ' . $lastName);
                                    if (empty($authorName)) {
                                        $authorName = $comment['username'] ?? 'Unknown';
                                    }
                                    $authorInitial = !empty($authorName) ? strtoupper(substr($authorName, 0, 1)) : '?';
                                    ?>
                                    <div class="comment mb-3">
                                        <div class="d-flex">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <?php echo $authorInitial; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($authorName); ?></h6>
                                                    <small class="text-muted"><?php echo formatDate($comment['created_at'], 'M d, Y H:i'); ?></small>
                                                </div>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['content'] ?? $comment['comment'] ?? '')); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Add Comment Form -->
                            <form method="POST" action="../../index.php?controller=Comment&action=add">
                                <input type="hidden" name="entity_type" value="task">
                                <input type="hidden" name="entity_id" value="<?php echo $task['id']; ?>">
                                <div class="mb-3">
                                    <textarea class="form-control" name="content" rows="3" placeholder="Add a comment..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-comment me-2"></i>Add Comment
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Attachments Section -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Attachments</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($attachments)): ?>
                                <p class="text-muted">No attachments.</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($attachments as $attachment): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-paperclip me-2"></i>
                                                <a href="<?php echo $attachment['file_path']; ?>" target="_blank">
                                                    <?php echo htmlspecialchars($attachment['original_name']); ?>
                                                </a>
                                                <small class="text-muted d-block">
                                                    <?php echo formatFileSize($attachment['file_size']); ?> - 
                                                    <?php echo formatDate($attachment['created_at'], 'M d, Y H:i'); ?>
                                                </small>
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteAttachment(<?php echo $attachment['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Upload Form -->
                            <form method="POST" action="../../index.php?controller=Attachment&action=upload" enctype="multipart/form-data" class="mt-3">
                                <input type="hidden" name="entity_type" value="task">
                                <input type="hidden" name="entity_id" value="<?php echo $task['id']; ?>">
                                <div class="mb-3">
                                    <input type="file" class="form-control" name="file" required>
                                </div>
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-upload me-2"></i>Upload File
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Task Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Task Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>Status</h6>
                                <span class="badge bg-<?php echo $task['status'] === 'completed' ? 'success' : ($task['status'] === 'in_progress' ? 'primary' : ($task['status'] === 'review' ? 'warning' : 'secondary')); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                </span>
                            </div>

                            <div class="mb-3">
                                <h6>Priority</h6>
                                <span class="badge bg-<?php echo $task['priority'] === 'critical' ? 'danger' : ($task['priority'] === 'high' ? 'warning' : ($task['priority'] === 'medium' ? 'info' : 'secondary')); ?>">
                                    <?php echo ucfirst($task['priority']); ?>
                                </span>
                            </div>

                            <div class="mb-3">
                                <h6>Assigned To</h6>
                                <p class="mb-0">
                                    <?php if ($assignedUser): ?>
                                        <i class="fas fa-user me-2"></i>
                                        <?php echo htmlspecialchars($assignedUser['first_name'] . ' ' . $assignedUser['last_name']); ?>
                                        <br><small class="text-muted">@<?php echo htmlspecialchars($assignedUser['username']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Unassigned</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <div class="mb-3">
                                <h6>Created By</h6>
                                <p class="mb-0">
                                    <?php if ($createdBy): ?>
                                        <i class="fas fa-user me-2"></i>
                                        <?php echo htmlspecialchars($createdBy['first_name'] . ' ' . $createdBy['last_name']); ?>
                                        <br><small class="text-muted">@<?php echo htmlspecialchars($createdBy['username']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Unknown</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <div class="mb-3">
                                <h6>Due Date</h6>
                                <p class="mb-0">
                                    <?php if ($task['due_date']): ?>
                                        <i class="fas fa-calendar me-2"></i>
                                        <?php echo formatDate($task['due_date'], 'M d, Y'); ?>
                                        <?php if (strtotime($task['due_date']) < time() && $task['status'] !== 'completed'): ?>
                                            <span class="badge bg-danger ms-2">Overdue</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No due date set</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <div class="mb-3">
                                <h6>Created</h6>
                                <p class="mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo formatDate($task['created_at'], 'M d, Y H:i'); ?>
                                </p>
                            </div>

                            <div class="mb-3">
                                <h6>Last Updated</h6>
                                <p class="mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo formatDate($task['updated_at'], 'M d, Y H:i'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" onclick="updateStatus('in_progress')">
                                    <i class="fas fa-play me-2"></i>Start Task
                                </button>
                                <button class="btn btn-outline-warning" onclick="updateStatus('review')">
                                    <i class="fas fa-eye me-2"></i>Mark for Review
                                </button>
                                <button class="btn btn-outline-success" onclick="updateStatus('completed')">
                                    <i class="fas fa-check me-2"></i>Complete Task
                                </button>
                                <a href="delete.php?id=<?php echo $task['id']; ?>" class="btn btn-outline-danger">
                                    <i class="fas fa-trash me-2"></i>Delete Task
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(status) {
    if (confirm('Are you sure you want to update the task status?')) {
        fetch('../../index.php?controller=Task&action=updateStatus', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'task_id=<?php echo $task['id']; ?>&status=' + status
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to update status: ' + (data.message || 'Unknown error'));
            }
        });
    }
}

function deleteAttachment(attachmentId) {
    if (confirm('Are you sure you want to delete this attachment?')) {
        fetch('../../index.php?controller=Attachment&action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + attachmentId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete attachment: ' + (data.message || 'Unknown error'));
            }
        });
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
