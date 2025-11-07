<?php
require_once '../config/config.php';

// Require admin access
requireAdmin();

// Get date range from query parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

try {
    $db = getDB();
    
    // Overall Statistics
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
            (SELECT COUNT(*) FROM quizzes WHERE is_active = 1) as total_quizzes,
            (SELECT COUNT(*) FROM questions) as total_questions,
            (SELECT COUNT(*) FROM quiz_attempts WHERE status = 'completed') as total_attempts,
            (SELECT AVG(percentage) FROM quiz_attempts WHERE status = 'completed') as avg_score
    ");
    $stmt->execute();
    $overall_stats = $stmt->fetch();
    
    // Quiz Performance Report
    $stmt = $db->prepare("
        SELECT q.id, q.title, c.name as category_name,
               COUNT(qa.id) as attempt_count,
               AVG(qa.percentage) as avg_score,
               MAX(qa.percentage) as highest_score,
               MIN(qa.percentage) as lowest_score,
               COUNT(DISTINCT qa.user_id) as unique_users
        FROM quizzes q
        LEFT JOIN categories c ON q.category_id = c.id
        LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.status = 'completed'
            AND DATE(qa.start_time) BETWEEN ? AND ?
        GROUP BY q.id
        HAVING attempt_count > 0
        ORDER BY attempt_count DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $quiz_performance = $stmt->fetchAll();
    
    // User Performance Report
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email,
               COUNT(qa.id) as quiz_count,
               AVG(qa.percentage) as avg_score,
               MAX(qa.percentage) as best_score,
               SUM(qa.time_taken) as total_time
        FROM users u
        JOIN quiz_attempts qa ON u.id = qa.user_id
        WHERE qa.status = 'completed' AND DATE(qa.start_time) BETWEEN ? AND ?
        GROUP BY u.id
        ORDER BY avg_score DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $user_performance = $stmt->fetchAll();
    
    // Daily Activity Report
    $stmt = $db->prepare("
        SELECT DATE(start_time) as date,
               COUNT(*) as attempts,
               AVG(percentage) as avg_score,
               COUNT(DISTINCT user_id) as unique_users
        FROM quiz_attempts
        WHERE status = 'completed' AND DATE(start_time) BETWEEN ? AND ?
        GROUP BY DATE(start_time)
        ORDER BY date DESC
        LIMIT 30
    ");
    $stmt->execute([$start_date, $end_date]);
    $daily_activity = $stmt->fetchAll();
    
    // Category Performance Report
    $stmt = $db->prepare("
        SELECT c.name as category_name,
               COUNT(qa.id) as attempt_count,
               AVG(qa.percentage) as avg_score,
               COUNT(DISTINCT q.id) as quiz_count
        FROM categories c
        JOIN quizzes q ON c.id = q.category_id
        LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.status = 'completed'
            AND DATE(qa.start_time) BETWEEN ? AND ?
        GROUP BY c.id
        HAVING attempt_count > 0
        ORDER BY avg_score DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $category_performance = $stmt->fetchAll();
    
    // Recent Activity Feed
    $stmt = $db->prepare("
        SELECT 'quiz_attempt' as type, u.name as user_name, q.title as quiz_title, 
               qa.percentage, qa.start_time as activity_time
        FROM quiz_attempts qa
        JOIN users u ON qa.user_id = u.id
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.status = 'completed' AND DATE(qa.start_time) BETWEEN ? AND ?
        
        UNION ALL
        
        SELECT 'user_registration' as type, u.name as user_name, NULL as quiz_title,
               NULL as percentage, u.created_at as activity_time
        FROM users u
        WHERE u.role = 'user' AND DATE(u.created_at) BETWEEN ? AND ?
        
        ORDER BY activity_time DESC
        LIMIT 20
    ");
    $stmt->execute([$start_date, $end_date, $start_date, $end_date]);
    $recent_activity = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $overall_stats = ['total_users' => 0, 'total_quizzes' => 0, 'total_questions' => 0, 'total_attempts' => 0, 'avg_score' => 0];
    $quiz_performance = [];
    $user_performance = [];
    $daily_activity = [];
    $category_performance = [];
    $recent_activity = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - <?php echo SITE_NAME; ?> Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cog me-2"></i>Admin Panel
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quizzes.php">
                            <i class="fas fa-clipboard-list me-1"></i>Quizzes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="questions.php">
                            <i class="fas fa-question-circle me-1"></i>Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-1"></i>Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-tags me-1"></i>Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home me-1"></i>Back to Site
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-chart-bar me-3"></i>Reports & Analytics
                    </h1>
                    <p class="page-subtitle">Comprehensive insights into your quiz platform performance</p>
                </div>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-success" onclick="exportReport()">
                            <i class="fas fa-download me-1"></i>Export Report
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Overall Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo number_format($overall_stats['total_users']); ?></h4>
                                <p class="mb-0">Total Users</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo number_format($overall_stats['total_quizzes']); ?></h4>
                                <p class="mb-0">Active Quizzes</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-clipboard-list fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo number_format($overall_stats['total_attempts']); ?></h4>
                                <p class="mb-0">Total Attempts</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo number_format($overall_stats['avg_score'], 1); ?>%</h4>
                                <p class="mb-0">Average Score</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-trophy fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Daily Activity Chart -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>Daily Activity Trends
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyActivityChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Category Performance -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tags me-2"></i>Category Performance
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($category_performance)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No data available for selected period</p>
                            </div>
                        <?php else: ?>
                            <canvas id="categoryChart" height="300"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <!-- Quiz Performance Report -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>Top Performing Quizzes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($quiz_performance)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No quiz attempts in selected period</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Quiz</th>
                                            <th>Attempts</th>
                                            <th>Avg Score</th>
                                            <th>Users</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($quiz_performance as $quiz): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($quiz['title']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($quiz['category_name']); ?></small>
                                                </td>
                                                <td><span class="badge bg-primary"><?php echo $quiz['attempt_count']; ?></span></td>
                                                <td>
                                                    <span class="fw-bold <?php echo $quiz['avg_score'] >= 70 ? 'text-success' : ($quiz['avg_score'] >= 50 ? 'text-warning' : 'text-danger'); ?>">
                                                        <?php echo number_format($quiz['avg_score'], 1); ?>%
                                                    </span>
                                                </td>
                                                <td><?php echo $quiz['unique_users']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- User Performance Report -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>Top Performing Users
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_performance)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No user activity in selected period</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Quizzes</th>
                                            <th>Avg Score</th>
                                            <th>Best</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_performance as $user): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                </td>
                                                <td><span class="badge bg-info"><?php echo $user['quiz_count']; ?></span></td>
                                                <td>
                                                    <span class="fw-bold <?php echo $user['avg_score'] >= 70 ? 'text-success' : ($user['avg_score'] >= 50 ? 'text-warning' : 'text-danger'); ?>">
                                                        <?php echo number_format($user['avg_score'], 1); ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">
                                                        <?php echo number_format($user['best_score'], 1); ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row g-4 mt-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activity)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No recent activity in selected period</p>
                            </div>
                        <?php else: ?>
                            <div class="activity-feed">
                                <?php foreach ($recent_activity as $activity): ?>
                                    <div class="activity-item d-flex align-items-center py-3 border-bottom">
                                        <div class="activity-icon me-3">
                                            <?php if ($activity['type'] == 'quiz_attempt'): ?>
                                                <i class="fas fa-clipboard-check text-success"></i>
                                            <?php else: ?>
                                                <i class="fas fa-user-plus text-primary"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-content flex-grow-1">
                                            <?php if ($activity['type'] == 'quiz_attempt'): ?>
                                                <strong><?php echo htmlspecialchars($activity['user_name']); ?></strong>
                                                completed quiz <strong><?php echo htmlspecialchars($activity['quiz_title']); ?></strong>
                                                <?php if ($activity['percentage']): ?>
                                                    with <span class="fw-bold <?php echo $activity['percentage'] >= 70 ? 'text-success' : ($activity['percentage'] >= 50 ? 'text-warning' : 'text-danger'); ?>">
                                                        <?php echo number_format($activity['percentage'], 1); ?>%
                                                    </span> score
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <strong><?php echo htmlspecialchars($activity['user_name']); ?></strong>
                                                registered as a new user
                                            <?php endif; ?>
                                            <div class="text-muted small">
                                                <?php echo date('M j, Y g:i A', strtotime($activity['activity_time'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            initializeDailyActivityChart();
            initializeCategoryChart();
        });
        
        // Daily Activity Chart
        function initializeDailyActivityChart() {
            const ctx = document.getElementById('dailyActivityChart').getContext('2d');
            
            const dailyData = <?php echo json_encode($daily_activity); ?>;
            const labels = dailyData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }).reverse();
            const attempts = dailyData.map(item => item.attempts).reverse();
            const avgScores = dailyData.map(item => parseFloat(item.avg_score)).reverse();
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Quiz Attempts',
                        data: attempts,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        yAxisID: 'y'
                    }, {
                        label: 'Average Score (%)',
                        data: avgScores,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Quiz Attempts'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Average Score (%)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }
        
        // Category Performance Chart
        function initializeCategoryChart() {
            const ctx = document.getElementById('categoryChart');
            if (!ctx) return;
            
            const categoryData = <?php echo json_encode($category_performance); ?>;
            const labels = categoryData.map(item => item.category_name);
            const scores = categoryData.map(item => parseFloat(item.avg_score));
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: scores,
                        backgroundColor: [
                            '#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Export Report Function
        function exportReport() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            // Create a simple CSV export
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "SmartQuiz Hub Report\n";
            csvContent += `Period: ${startDate} to ${endDate}\n\n`;
            csvContent += "Overall Statistics\n";
            csvContent += "Total Users,<?php echo $overall_stats['total_users']; ?>\n";
            csvContent += "Total Quizzes,<?php echo $overall_stats['total_quizzes']; ?>\n";
            csvContent += "Total Attempts,<?php echo $overall_stats['total_attempts']; ?>\n";
            csvContent += "Average Score,<?php echo number_format($overall_stats['avg_score'], 1); ?>%\n";
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `smartquiz_report_${startDate}_${endDate}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
    
    <style>
        .page-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .stat-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            opacity: 0.8;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: none;
        }
        
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .activity-item:last-child {
            border-bottom: none !important;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 50%;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        
        .btn {
            border-radius: 8px;
        }
    </style>
</body>
</html>
