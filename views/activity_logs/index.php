<?php
// This view is included from ActivityLogController
// All data should be prepared by the controller
// If accessed directly, redirect to controller
if (!isset($activities) || !isset($totalActivities)) {
    // Data not set, likely accessed directly - redirect to controller
    if (function_exists('redirect')) {
        redirect(rtrim(APP_URL, '/') . '/index.php?controller=ActivityLog&action=index');
    } else {
        header('Location: ' . rtrim(APP_URL, '/') . '/index.php?controller=ActivityLog&action=index');
        exit;
    }
}

// Ensure variables are set
$page = $page ?? 1;
$limit = $limit ?? 20;
$totalPages = $totalPages ?? 1;
$stats = $stats ?? null;
$users = $users ?? [];
$actions = $actions ?? ['created', 'updated', 'deleted', 'login', 'logout', 'registered', 'commented', 'attached'];
$entityTypes = $entityTypes ?? ['project', 'task', 'user', 'comment', 'attachment'];
$filters = $filters ?? [];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Activity Logs</h1>
                    <p class="text-muted">View system activity and audit trail</p>
                </div>
                <a href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=Dashboard&action=dashboard" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Statistics Cards -->
            <?php if ($stats): ?>
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Activities</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_activities'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-history fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Created</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['created_count'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-plus-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Updated</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['updated_count'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-edit fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Deleted</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['deleted_count'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-trash fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Filter Activity Logs</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="<?php echo rtrim(APP_URL, '/'); ?>/index.php" class="row g-3">
                        <input type="hidden" name="controller" value="ActivityLog">
                        <input type="hidden" name="action" value="index">
                        <div class="col-md-3">
                            <label class="form-label">User</label>
                            <select class="form-select" name="user_id">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo (isset($filters['user_id']) && $filters['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['username'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Action</label>
                            <select class="form-select" name="action">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?php echo $action; ?>" 
                                            <?php echo (isset($filters['action']) && $filters['action'] == $action) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($action); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Entity Type</label>
                            <select class="form-select" name="entity_type">
                                <option value="">All Types</option>
                                <?php foreach ($entityTypes as $type): ?>
                                    <option value="<?php echo $type; ?>" 
                                            <?php echo (isset($filters['entity_type']) && $filters['entity_type'] == $type) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" class="form-control" name="date_from" 
                                   value="<?php echo $filters['date_from'] ?? ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" class="form-control" name="date_to" 
                                   value="<?php echo $filters['date_to'] ?? ''; ?>">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                    <?php if (!empty($filters)): ?>
                    <div class="mt-3">
                        <a href="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=ActivityLog&action=index" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times me-2"></i>Clear Filters
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activity Logs Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Activity Logs (<?php echo $totalActivities; ?> total)</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($activities)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No activity logs found</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Entity Type</th>
                                        <th>Entity ID</th>
                                        <th>Description</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td>
                                            <small><?php echo formatDate($activity['created_at'], 'Y-m-d H:i:s'); ?></small><br>
                                            <small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($activity['username']): ?>
                                                <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?><br>
                                                <small class="text-muted">@<?php echo htmlspecialchars($activity['username']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">System</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $activity['action'] === 'created' ? 'success' : 
                                                    ($activity['action'] === 'updated' ? 'info' : 
                                                    ($activity['action'] === 'deleted' ? 'danger' : 'secondary')); 
                                            ?>">
                                                <?php echo ucfirst($activity['action']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo ucfirst($activity['entity_type']); ?></td>
                                        <td>
                                            <?php if ($activity['entity_id']): ?>
                                                <?php 
                                                $entityUrl = '';
                                                if ($activity['entity_type'] === 'project') {
                                                    $entityUrl = rtrim(APP_URL, '/') . '/index.php?controller=Project&action=view&id=' . $activity['entity_id'];
                                                } elseif ($activity['entity_type'] === 'task') {
                                                    $entityUrl = rtrim(APP_URL, '/') . '/index.php?controller=Task&action=view&id=' . $activity['entity_id'];
                                                } elseif ($activity['entity_type'] === 'user') {
                                                    $entityUrl = rtrim(APP_URL, '/') . '/index.php?controller=User&action=profile&id=' . $activity['entity_id'];
                                                }
                                                ?>
                                                <?php if ($entityUrl): ?>
                                                    <a href="<?php echo $entityUrl; ?>" class="text-decoration-none">
                                                        #<?php echo $activity['entity_id']; ?>
                                                    </a>
                                                <?php else: ?>
                                                    #<?php echo $activity['entity_id']; ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($activity['description'] ?? '-'); ?></td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($activity['ip_address'] ?? '-'); ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Activity logs pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?controller=ActivityLog&action=index&page=<?php echo $page - 1; ?>&<?php echo http_build_query($filters); ?>">Previous</a>
                                </li>
                                <?php 
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?controller=ActivityLog&action=index&page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?controller=ActivityLog&action=index&page=<?php echo $page + 1; ?>&<?php echo http_build_query($filters); ?>">Next</a>
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

