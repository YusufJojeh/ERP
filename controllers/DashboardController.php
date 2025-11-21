<?php
/**
 * Dashboard Controller
 * Handles dashboard statistics, charts, and data aggregation
 */

class DashboardController {
    private $userModel;
    private $projectModel;
    private $taskModel;
    private $activityLogModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->projectModel = new Project();
        $this->taskModel = new Task();
        $this->activityLogModel = new ActivityLog();
    }
    
    /**
     * Display main dashboard
     */
    public function index() {
        requireLogin();
        
        $pageTitle = 'Dashboard';
        $currentPage = 'dashboard';
        
        // Get user role for filtering data
        $userRole = $_SESSION['user_role'];
        $userId = $_SESSION['user_id'];
        
        // Get statistics based on user role
        if ($userRole === 'admin') {
            $stats = $this->getAdminStats();
        } else {
            $stats = $this->getUserStats($userId);
        }
        
        // Get recent activities
        $recentActivities = $this->activityLogModel->getRecentActivities(10);
        
        // Get recent projects
        $recentProjects = $this->projectModel->getRecentProjects(5);
        
        // Get recent tasks
        $recentTasks = $this->taskModel->getRecentTasks(10);
        
        // Get overdue tasks
        $overdueTasks = $this->taskModel->getOverdueTasks($userRole === 'admin' ? null : $userId);
        
        // Get chart data
        $chartData = $this->getChartData($userRole, $userId);
        
        include 'views/dashboard/dashboard.php';
    }
    
    /**
     * Get admin statistics
     */
    public function getAdminStats() {
        $userStats = $this->userModel->getUserStats();
        $projectStats = $this->projectModel->getAllProjectStats();
        $taskStats = $this->taskModel->getTaskStats();
        $activityStats = $this->activityLogModel->getActivityStats();
        
        return [
            'users' => $userStats,
            'projects' => $projectStats,
            'tasks' => $taskStats,
            'activities' => $activityStats
        ];
    }
    
    /**
     * Get user-specific statistics
     */
    public function getUserStats($userId) {
        $userProjects = $this->projectModel->getUserProjects($userId);
        $userTasks = $this->taskModel->getUserTasks($userId);
        $userTaskStats = $this->taskModel->getTaskStats(['assigned_to' => $userId]);
        
        return [
            'projects' => [
                'total' => count($userProjects),
                'active' => count(array_filter($userProjects, function($p) { return $p['status'] === 'active'; })),
                'completed' => count(array_filter($userProjects, function($p) { return $p['status'] === 'completed'; }))
            ],
            'tasks' => $userTaskStats
        ];
    }
    
    /**
     * Get chart data for dashboard
     */
    public function getChartData($userRole, $userId) {
        $data = [];
        
        // Project status chart
        if ($userRole === 'admin') {
            $projectStats = $this->projectModel->getAllProjectStats();
            $data['projectStatus'] = [
                'labels' => ['Active', 'Completed', 'On Hold', 'Planning'],
                'data' => [
                    $projectStats['active_projects'],
                    $projectStats['completed_projects'],
                    $projectStats['on_hold_projects'],
                    $projectStats['total_projects'] - $projectStats['active_projects'] - $projectStats['completed_projects'] - $projectStats['on_hold_projects']
                ]
            ];
        } else {
            $userProjects = $this->projectModel->getUserProjects($userId);
            $active = count(array_filter($userProjects, function($p) { return $p['status'] === 'active'; }));
            $completed = count(array_filter($userProjects, function($p) { return $p['status'] === 'completed'; }));
            $onHold = count(array_filter($userProjects, function($p) { return $p['status'] === 'on_hold'; }));
            $planning = count(array_filter($userProjects, function($p) { return $p['status'] === 'planning'; }));
            
            $data['projectStatus'] = [
                'labels' => ['Active', 'Completed', 'On Hold', 'Planning'],
                'data' => [$active, $completed, $onHold, $planning]
            ];
        }
        
        // Task priority chart
        $taskFilters = $userRole === 'admin' ? [] : ['assigned_to' => $userId];
        $taskStats = $this->taskModel->getTaskStats($taskFilters);
        
        $data['taskPriority'] = [
            'labels' => ['Low', 'Medium', 'High', 'Critical'],
            'data' => [
                $taskStats['low_priority_tasks'],
                $taskStats['medium_priority_tasks'],
                $taskStats['high_priority_tasks'],
                $taskStats['critical_tasks']
            ]
        ];
        
        // Task status chart
        $data['taskStatus'] = [
            'labels' => ['Pending', 'In Progress', 'Review', 'Completed'],
            'data' => [
                $taskStats['pending_tasks'],
                $taskStats['in_progress_tasks'],
                $taskStats['review_tasks'],
                $taskStats['completed_tasks']
            ]
        ];
        
        // Daily activity chart (last 30 days)
        $dailyActivities = $this->activityLogModel->getDailyActivityCounts(30);
        $data['dailyActivity'] = [
            'labels' => array_column($dailyActivities, 'activity_date'),
            'data' => array_column($dailyActivities, 'count')
        ];
        
        return $data;
    }
    
    /**
     * Get dashboard data via AJAX
     */
    public function getDashboardData() {
        requireLogin();
        
        $userRole = $_SESSION['user_role'];
        $userId = $_SESSION['user_id'];
        
        $data = [];
        
        // Get date range filters
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Get filtered statistics
        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];
        
        if ($userRole === 'admin') {
            $data['stats'] = $this->getAdminStats();
        } else {
            $data['stats'] = $this->getUserStats($userId);
        }
        
        $data['charts'] = $this->getChartData($userRole, $userId);
        $data['recentActivities'] = $this->activityLogModel->getActivityLogs(1, 10, $filters);
        
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Export dashboard data
     */
    public function exportData() {
        requireLogin();
        
        $format = $_GET['format'] ?? 'csv';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $type = $_GET['type'] ?? 'all'; // all, projects, tasks
        
        $userRole = $_SESSION['user_role'];
        $userId = $_SESSION['user_id'];
        
        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];
        
        if ($userRole === 'admin') {
            $stats = $this->getAdminStats();
        } else {
            $stats = $this->getUserStats($userId);
        }
        
        $recentActivities = $this->activityLogModel->getActivityLogs(1, 1000, $filters);
        
        // Get projects and tasks data if needed
        $projects = [];
        $tasks = [];
        if ($type === 'projects' || $type === 'all') {
            if ($userRole === 'admin') {
                $projects = $this->projectModel->getAllProjects(1, 10000, []);
            } else {
                $projects = $this->projectModel->getUserProjects($userId, 1, 10000, []);
            }
        }
        
        if ($type === 'tasks' || $type === 'all') {
            if ($userRole === 'admin') {
                $tasks = $this->taskModel->getAllTasks(1, 10000, []);
            } else {
                $tasks = $this->taskModel->getUserTasks($userId, 1, 10000, []);
            }
        }
        
        if ($format === 'pdf') {
            $this->exportToPDF($stats, $recentActivities, $projects, $tasks, $dateFrom, $dateTo, $type);
        } elseif ($format === 'excel' || $format === 'csv') {
            $this->exportToExcel($stats, $recentActivities, $projects, $tasks, $dateFrom, $dateTo, $type, $format);
        }
    }
    
    /**
     * Export data to PDF (HTML-based, can be converted to PDF by browser)
     */
    private function exportToPDF($stats, $activities, $projects, $tasks, $dateFrom, $dateTo, $type) {
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="dashboard_report_' . date('Y-m-d') . '.html"');
        
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Dashboard Report</title>';
        echo '<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; }
            h2 { color: #666; border-bottom: 2px solid #666; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #4e73df; color: white; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .stat-box { display: inline-block; margin: 10px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            .stat-label { font-size: 12px; color: #666; }
            .stat-value { font-size: 24px; font-weight: bold; color: #333; }
        </style></head><body>';
        
        echo '<h1>ERP Dashboard Report</h1>';
        echo '<p><strong>Report Period:</strong> ' . htmlspecialchars($dateFrom) . ' to ' . htmlspecialchars($dateTo) . '</p>';
        echo '<p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        echo '<p><strong>Generated By:</strong> ' . htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) . '</p>';
        
        // Statistics Section
        echo '<h2>Statistics Summary</h2>';
        if ($stats) {
            echo '<div>';
            if (isset($stats['users'])) {
                echo '<div class="stat-box"><div class="stat-label">Total Users</div><div class="stat-value">' . ($stats['users']['total_users'] ?? 0) . '</div></div>';
            }
            if (isset($stats['projects'])) {
                echo '<div class="stat-box"><div class="stat-label">Total Projects</div><div class="stat-value">' . ($stats['projects']['total_projects'] ?? 0) . '</div></div>';
                echo '<div class="stat-box"><div class="stat-label">Active Projects</div><div class="stat-value">' . ($stats['projects']['active_projects'] ?? 0) . '</div></div>';
            }
            if (isset($stats['tasks'])) {
                echo '<div class="stat-box"><div class="stat-label">Total Tasks</div><div class="stat-value">' . ($stats['tasks']['total_tasks'] ?? 0) . '</div></div>';
                echo '<div class="stat-box"><div class="stat-label">Completed Tasks</div><div class="stat-value">' . ($stats['tasks']['completed_tasks'] ?? 0) . '</div></div>';
            }
            echo '</div>';
        }
        
        // Projects Section
        if (($type === 'projects' || $type === 'all') && !empty($projects)) {
            echo '<h2>Projects (' . count($projects) . ')</h2>';
            echo '<table><thead><tr><th>ID</th><th>Name</th><th>Status</th><th>Priority</th><th>Progress</th><th>Manager</th><th>Start Date</th><th>End Date</th></tr></thead><tbody>';
            foreach ($projects as $project) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($project['id']) . '</td>';
                echo '<td>' . htmlspecialchars($project['name']) . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($project['status'])) . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($project['priority'])) . '</td>';
                echo '<td>' . ($project['progress'] ?? 0) . '%</td>';
                echo '<td>' . htmlspecialchars($project['manager_name'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($project['start_date'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($project['end_date'] ?? '-') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        
        // Tasks Section
        if (($type === 'tasks' || $type === 'all') && !empty($tasks)) {
            echo '<h2>Tasks (' . count($tasks) . ')</h2>';
            echo '<table><thead><tr><th>ID</th><th>Title</th><th>Project</th><th>Status</th><th>Priority</th><th>Assigned To</th><th>Due Date</th></tr></thead><tbody>';
            foreach ($tasks as $task) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($task['id']) . '</td>';
                echo '<td>' . htmlspecialchars($task['title']) . '</td>';
                echo '<td>' . htmlspecialchars($task['project_name'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($task['status'])) . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($task['priority'])) . '</td>';
                echo '<td>' . htmlspecialchars($task['assigned_username'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($task['due_date'] ?? '-') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        
        // Activities Section
        if (!empty($activities)) {
            echo '<h2>Recent Activities (' . count($activities) . ')</h2>';
            echo '<table><thead><tr><th>Date</th><th>User</th><th>Action</th><th>Entity Type</th><th>Entity ID</th><th>Description</th></tr></thead><tbody>';
            foreach ($activities as $activity) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($activity['created_at']) . '</td>';
                echo '<td>' . htmlspecialchars($activity['username'] ?? 'System') . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($activity['action'])) . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($activity['entity_type'])) . '</td>';
                echo '<td>' . htmlspecialchars($activity['entity_id'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($activity['description'] ?? '-') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        
        echo '</body></html>';
        exit;
    }
    
    /**
     * Export data to Excel/CSV
     */
    private function exportToExcel($stats, $activities, $projects, $tasks, $dateFrom, $dateTo, $type, $format = 'csv') {
        $filename = 'dashboard_report_' . date('Y-m-d') . '.' . $format;
        
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header information
            fputcsv($output, ['ERP Dashboard Report']);
            fputcsv($output, ['Report Period', $dateFrom . ' to ' . $dateTo]);
            fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
            fputcsv($output, ['Generated By', $_SESSION['first_name'] . ' ' . $_SESSION['last_name']]);
            fputcsv($output, []); // Empty row
            
            // Statistics Section
            fputcsv($output, ['STATISTICS SUMMARY']);
            if ($stats) {
                if (isset($stats['users'])) {
                    fputcsv($output, ['Total Users', $stats['users']['total_users'] ?? 0]);
                }
                if (isset($stats['projects'])) {
                    fputcsv($output, ['Total Projects', $stats['projects']['total_projects'] ?? 0]);
                    fputcsv($output, ['Active Projects', $stats['projects']['active_projects'] ?? 0]);
                    fputcsv($output, ['Completed Projects', $stats['projects']['completed_projects'] ?? 0]);
                }
                if (isset($stats['tasks'])) {
                    fputcsv($output, ['Total Tasks', $stats['tasks']['total_tasks'] ?? 0]);
                    fputcsv($output, ['Completed Tasks', $stats['tasks']['completed_tasks'] ?? 0]);
                    fputcsv($output, ['In Progress Tasks', $stats['tasks']['in_progress_tasks'] ?? 0]);
                    fputcsv($output, ['Pending Tasks', $stats['tasks']['pending_tasks'] ?? 0]);
                }
            }
            fputcsv($output, []); // Empty row
            
            // Projects Section
            if (($type === 'projects' || $type === 'all') && !empty($projects)) {
                fputcsv($output, ['PROJECTS (' . count($projects) . ')']);
                fputcsv($output, ['ID', 'Name', 'Description', 'Status', 'Priority', 'Progress', 'Manager', 'Start Date', 'End Date', 'Budget']);
                foreach ($projects as $project) {
                    fputcsv($output, [
                        $project['id'],
                        $project['name'],
                        $project['description'] ?? '',
                        ucfirst($project['status']),
                        ucfirst($project['priority']),
                        ($project['progress'] ?? 0) . '%',
                        $project['manager_name'] ?? '-',
                        $project['start_date'] ?? '-',
                        $project['end_date'] ?? '-',
                        $project['budget'] ?? '0.00'
                    ]);
                }
                fputcsv($output, []); // Empty row
            }
            
            // Tasks Section
            if (($type === 'tasks' || $type === 'all') && !empty($tasks)) {
                fputcsv($output, ['TASKS (' . count($tasks) . ')']);
                fputcsv($output, ['ID', 'Title', 'Description', 'Project', 'Status', 'Priority', 'Assigned To', 'Due Date', 'Created At']);
                foreach ($tasks as $task) {
                    fputcsv($output, [
                        $task['id'],
                        $task['title'],
                        $task['description'] ?? '',
                        $task['project_name'] ?? '-',
                        ucfirst($task['status']),
                        ucfirst($task['priority']),
                        $task['assigned_username'] ?? '-',
                        $task['due_date'] ?? '-',
                        $task['created_at'] ?? '-'
                    ]);
                }
                fputcsv($output, []); // Empty row
            }
            
            // Activities Section
            if (!empty($activities)) {
                fputcsv($output, ['RECENT ACTIVITIES (' . count($activities) . ')']);
                fputcsv($output, ['Date', 'User', 'Action', 'Entity Type', 'Entity ID', 'Description', 'IP Address']);
                foreach ($activities as $activity) {
                    fputcsv($output, [
                        $activity['created_at'],
                        $activity['username'] ?? 'System',
                        ucfirst($activity['action']),
                        ucfirst($activity['entity_type']),
                        $activity['entity_id'] ?? '-',
                        $activity['description'] ?? '-',
                        $activity['ip_address'] ?? '-'
                    ]);
                }
            }
            
            fclose($output);
        } else {
            // Excel format (HTML table)
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            echo '<html><head><meta charset="UTF-8"></head><body>';
            echo '<h1>ERP Dashboard Report</h1>';
            echo '<p><strong>Report Period:</strong> ' . htmlspecialchars($dateFrom) . ' to ' . htmlspecialchars($dateTo) . '</p>';
            echo '<p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>';
            
            // Statistics
            echo '<h2>Statistics</h2>';
            echo '<table border="1">';
            if ($stats) {
                if (isset($stats['users'])) {
                    echo '<tr><td>Total Users</td><td>' . ($stats['users']['total_users'] ?? 0) . '</td></tr>';
                }
                if (isset($stats['projects'])) {
                    echo '<tr><td>Total Projects</td><td>' . ($stats['projects']['total_projects'] ?? 0) . '</td></tr>';
                    echo '<tr><td>Active Projects</td><td>' . ($stats['projects']['active_projects'] ?? 0) . '</td></tr>';
                }
                if (isset($stats['tasks'])) {
                    echo '<tr><td>Total Tasks</td><td>' . ($stats['tasks']['total_tasks'] ?? 0) . '</td></tr>';
                    echo '<tr><td>Completed Tasks</td><td>' . ($stats['tasks']['completed_tasks'] ?? 0) . '</td></tr>';
                }
            }
            echo '</table>';
            
            // Projects
            if (($type === 'projects' || $type === 'all') && !empty($projects)) {
                echo '<h2>Projects</h2>';
                echo '<table border="1"><tr><th>ID</th><th>Name</th><th>Status</th><th>Priority</th><th>Progress</th></tr>';
                foreach ($projects as $project) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($project['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($project['name']) . '</td>';
                    echo '<td>' . htmlspecialchars(ucfirst($project['status'])) . '</td>';
                    echo '<td>' . htmlspecialchars(ucfirst($project['priority'])) . '</td>';
                    echo '<td>' . ($project['progress'] ?? 0) . '%</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            
            // Tasks
            if (($type === 'tasks' || $type === 'all') && !empty($tasks)) {
                echo '<h2>Tasks</h2>';
                echo '<table border="1"><tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Due Date</th></tr>';
                foreach ($tasks as $task) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($task['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($task['title']) . '</td>';
                    echo '<td>' . htmlspecialchars(ucfirst($task['status'])) . '</td>';
                    echo '<td>' . htmlspecialchars(ucfirst($task['priority'])) . '</td>';
                    echo '<td>' . htmlspecialchars($task['due_date'] ?? '-') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            
            echo '</body></html>';
        }
        
        exit;
    }
}
?>
