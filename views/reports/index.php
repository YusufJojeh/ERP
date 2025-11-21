<?php
// config.php is already loaded in index.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}

$pageTitle = 'Reports';
$currentPage = 'reports';
$pageDescription = 'Generate and export system reports';

// Include header
include __DIR__ . '/../includes/header.php';

// Get user role for filtering
$userRole = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

// Initialize models
$dashboardController = new DashboardController();
$projectModel = new Project();
$taskModel = new Task();
$userModel = new User();
$activityLogModel = new ActivityLog();

// Get date range filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$filters = [
    'date_from' => $dateFrom,
    'date_to' => $dateTo
];

// Get statistics based on user role
if ($userRole === 'admin') {
    $stats = $dashboardController->getAdminStats();
    $allProjects = $projectModel->getAllProjects(1, 1000, []);
    $allTasks = $taskModel->getAllTasks(1, 1000, []);
} else {
    $stats = $dashboardController->getUserStats($userId);
    $allProjects = $projectModel->getUserProjects($userId);
    $allTasks = $taskModel->getUserTasks($userId, 1, 1000, []);
}

// Get activity statistics
$activityStats = $activityLogModel->getActivityStats($filters);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Reports</h1>
                    <p class="text-muted">Generate and export system reports</p>
                </div>
            </div>

            <!-- Export Controls -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Export Reports</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="<?php echo APP_URL; ?>/index.php?controller=Dashboard&action=exportData" class="row g-3">
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo $dateFrom; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo $dateTo; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="format" class="form-label">Export Format</label>
                            <select class="form-select" id="format" name="format">
                                <option value="csv">CSV</option>
                                <option value="excel">Excel (XLS)</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>Export Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics Summary -->
            <div class="row mb-4">
                <?php if ($userRole === 'admin'): ?>
                    <!-- Admin Statistics -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['users']['total_users'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Projects</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['projects']['active_projects'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Tasks</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['tasks']['total_tasks'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Activities</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $activityStats['total_activities'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-history fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- User Statistics -->
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">My Projects</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['projects']['total'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">My Tasks</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['tasks']['total_tasks'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Completed</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['tasks']['completed_tasks'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Report Sections -->
            <div class="row">
                <!-- Projects Report -->
                <div class="col-xl-6 col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Projects Report</h6>
                            <button class="btn btn-sm btn-primary" onclick="exportProjectsReport()">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Project Name</th>
                                            <th>Status</th>
                                            <th>Progress</th>
                                            <th>Manager</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($allProjects, 0, 10) as $project): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($project['name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusBadgeClass($project['status']); ?>">
                                                    <?php echo ucfirst($project['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $project['progress']; ?>%</td>
                                            <td><?php echo htmlspecialchars($project['manager_name'] ?? '-'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($allProjects) > 10): ?>
                                <p class="text-muted text-center mt-2">
                                    Showing 10 of <?php echo count($allProjects); ?> projects. Export to see all.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tasks Report -->
                <div class="col-xl-6 col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Tasks Report</h6>
                            <button class="btn btn-sm btn-primary" onclick="exportTasksReport()">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Task Title</th>
                                            <th>Status</th>
                                            <th>Priority</th>
                                            <th>Assigned To</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($allTasks, 0, 10) as $task): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($task['title']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $task['status'] === 'completed' ? 'success' : 
                                                        ($task['status'] === 'in_progress' ? 'info' : 'secondary'); 
                                                ?>">
                                                    <?php echo ucfirst($task['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $task['priority'] === 'critical' ? 'danger' : 
                                                        ($task['priority'] === 'high' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <?php echo ucfirst($task['priority']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($task['assigned_username'] ?? '-'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($allTasks) > 10): ?>
                                <p class="text-muted text-center mt-2">
                                    Showing 10 of <?php echo count($allTasks); ?> tasks. Export to see all.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Summary -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Activity Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="h4 text-primary"><?php echo $activityStats['created_count'] ?? 0; ?></div>
                                        <div class="text-muted">Created</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="h4 text-info"><?php echo $activityStats['updated_count'] ?? 0; ?></div>
                                        <div class="text-muted">Updated</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="h4 text-danger"><?php echo $activityStats['deleted_count'] ?? 0; ?></div>
                                        <div class="text-muted">Deleted</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <div class="h4 text-success"><?php echo $activityStats['total_activities'] ?? 0; ?></div>
                                        <div class="text-muted">Total Activities</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportProjectsReport() {
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    const format = 'csv'; // Default to CSV for now
    
    window.location.href = '<?php echo APP_URL; ?>/index.php?controller=Dashboard&action=exportData&format=' + format + '&date_from=' + dateFrom + '&date_to=' + dateTo + '&type=projects';
}

function exportTasksReport() {
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    const format = 'csv'; // Default to CSV for now
    
    window.location.href = '<?php echo APP_URL; ?>/index.php?controller=Dashboard&action=exportData&format=' + format + '&date_from=' + dateFrom + '&date_to=' + dateTo + '&type=tasks';
}

<?php
// Helper function for status badges (same as dashboard)
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'active': return 'success';
        case 'completed': return 'primary';
        case 'on_hold': return 'warning';
        case 'planning': return 'info';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

