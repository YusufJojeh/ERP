<?php
// config.php is already loaded in index.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
$pageDescription = 'Overview of your projects, tasks, and activities';

// Include header
include __DIR__ . '/../includes/header.php';

// Initialize dashboard controller and get data
$dashboardController = new DashboardController();

// Get user role for filtering data
$userRole = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

// Get statistics based on user role
if ($userRole === 'admin') {
    $stats = $dashboardController->getAdminStats();
} else {
    $stats = $dashboardController->getUserStats($userId);
}

// Get recent activities
$activityLogModel = new ActivityLog();
$recentActivities = $activityLogModel->getRecentActivities(10);

// Get recent projects
$projectModel = new Project();
$recentProjects = $projectModel->getRecentProjects(5);

// Get recent tasks
$taskModel = new Task();
$recentTasks = $taskModel->getRecentTasks(10);

// Get overdue tasks
$overdueTasks = $taskModel->getOverdueTasks($userRole === 'admin' ? null : $userId);

// Get chart data
$chartData = $dashboardController->getChartData($userRole, $userId);
?>

<div class="row">
    <!-- Statistics Cards - Glass & Gradient Design -->
    <div class="col-12">
        <div class="row mb-4">
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <!-- Admin Statistics -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-primary fade-in">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-label">Total Users</div>
                                <div class="stat-value"><?php echo $stats['users']['total_users']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-success fade-in">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-label">Active Projects</div>
                                <div class="stat-value"><?php echo $stats['projects']['active_projects']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-info fade-in">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-label">Total Tasks</div>
                                <div class="stat-value"><?php echo $stats['tasks']['total_tasks']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-warning fade-in">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-label">Completed Tasks</div>
                                <div class="stat-value"><?php echo $stats['tasks']['completed_tasks']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- User Statistics -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-primary fade-in">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-label">My Projects</div>
                                <div class="stat-value"><?php echo $stats['projects']['total']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-success fade-in">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-label">My Tasks</div>
                                <div class="stat-value"><?php echo $stats['tasks']['total_tasks']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-info fade-in">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-label">Completed</div>
                                <div class="stat-value"><?php echo $stats['tasks']['completed_tasks']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-danger fade-in">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-label">Overdue</div>
                                <div class="stat-value"><?php echo $stats['tasks']['overdue_tasks']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Charts Row - Glass Panels -->
<div class="row">
    <!-- Project Status Chart -->
    <div class="col-xl-6 col-lg-6 mb-4">
        <div class="card-glass">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Project Status</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <div class="chart-pie">
                        <canvas id="projectStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Priority Chart -->
    <div class="col-xl-6 col-lg-6 mb-4">
        <div class="card-glass">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Task Priority</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <div class="chart-pie">
                        <canvas id="taskPriorityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Charts Row -->
<div class="row">
    <!-- Task Status Chart -->
    <div class="col-xl-6 col-lg-6 mb-4">
        <div class="card-glass">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Task Status</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <div class="chart-pie">
                        <canvas id="taskStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Activity Chart -->
    <div class="col-xl-6 col-lg-6 mb-4">
        <div class="card-glass">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Daily Activity (Last 30 Days)</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <div class="chart-area">
                        <canvas id="dailyActivityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data Tables Row -->
<div class="row">
    <!-- Recent Activities -->
    <div class="col-xl-6 col-lg-6 mb-4">
        <div class="card-glass">
            <div class="card-header d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold">Recent Activities</h6>
                <a href="<?php echo APP_URL; ?>/views/activity_logs/index.php" class="btn btn-sm btn-gradient">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>User</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivities as $activity): ?>
                            <tr>
                                <td>
                                    <span class="badge-pill badge-<?php echo getActivityBadgeClass($activity['action']); ?>">
                                        <?php echo ucfirst($activity['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst($activity['entity_type']); ?></td>
                                <td><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></td>
                                <td><?php echo timeAgo($activity['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Projects -->
    <div class="col-xl-6 col-lg-6 mb-4">
        <div class="card-glass">
            <div class="card-header d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold">Recent Projects</h6>
                <a href="<?php echo APP_URL; ?>/views/projects/list.php" class="btn btn-sm btn-gradient">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Manager</th>
                                <th>Status</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentProjects as $project): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo APP_URL; ?>/views/projects/view.php?id=<?php echo $project['id']; ?>" 
                                       class="text-decoration-none" style="color: var(--brand-accent);">
                                        <?php echo htmlspecialchars($project['name']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($project['manager_name']); ?></td>
                                <td>
                                    <span class="badge-pill badge-<?php echo getStatusBadgeClass($project['status']); ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $project['progress']; ?>%">
                                            <?php echo $project['progress']; ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overdue Tasks Alert -->
<?php if (!empty($overdueTasks)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Overdue Tasks:</strong> You have <?php echo count($overdueTasks); ?> overdue task(s). 
            <a href="<?php echo APP_URL; ?>/views/tasks/list.php?filter=overdue" class="alert-link" style="color: var(--badge-pending); font-weight: 600;">View overdue tasks</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Export and Filter Controls -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card-glass">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Export & Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo rtrim(APP_URL, '/'); ?>/index.php?controller=Dashboard&action=exportData" class="row g-3">
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="format" class="form-label">Format</label>
                        <select class="form-select" id="format" name="format">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-gradient">
                                <i class="fas fa-download me-2"></i>Export Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Helper functions for badge classes
function getActivityBadgeClass($action) {
    switch ($action) {
        case 'created': return 'completed';
        case 'updated': return 'progress';
        case 'deleted': return 'overdue';
        case 'login': return 'primary';
        case 'logout': return 'pending';
        default: return 'pending';
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'active': return 'progress';
        case 'completed': return 'completed';
        case 'on_hold': return 'pending';
        case 'planning': return 'review';
        case 'cancelled': return 'overdue';
        default: return 'pending';
    }
}

// Include footer
include __DIR__ . '/../includes/footer.php';
?>

<!-- Dashboard JavaScript -->
<script>
// Chart data from PHP - make it globally available
window.chartData = <?php echo json_encode($chartData ?? []); ?>;

// Charts will be initialized by main.js initializeDashboardCharts()
// Theme switching will automatically update chart colors
</script>
