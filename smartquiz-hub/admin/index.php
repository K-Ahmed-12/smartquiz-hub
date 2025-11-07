<?php
require_once '../config/config.php';

// Require admin access
requireAdmin();

try {
    $db = getDB();
    
    // Get dashboard statistics
    $stats = [];
    
    // Total users
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch()['total'];
    
    // Total quizzes
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM quizzes");
    $stmt->execute();
    $stats['total_quizzes'] = $stmt->fetch()['total'];
    
    // Total quiz attempts
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM quiz_attempts WHERE status = 'completed'");
    $stmt->execute();
    $stats['total_attempts'] = $stmt->fetch()['total'];
    
    // Average score
    $stmt = $db->prepare("SELECT AVG(percentage) as avg_score FROM quiz_attempts WHERE status = 'completed'");
    $stmt->execute();
    $stats['avg_score'] = $stmt->fetch()['avg_score'] ?? 0;
    
    // Recent activities
    $stmt = $db->prepare("
        SELECT 'quiz_attempt' as type, u.name as user_name, q.title as quiz_title, 
               qa.percentage, qa.start_time as activity_time
        FROM quiz_attempts qa
        JOIN users u ON qa.user_id = u.id
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.status = 'completed'
        
        UNION ALL
        
        SELECT 'user_registration' as type, u.name as user_name, NULL as quiz_title,
               NULL as percentage, u.created_at as activity_time
        FROM users u
        WHERE u.role = 'user'
        
        ORDER BY activity_time DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll();
    
    // Popular quizzes
    $stmt = $db->prepare("
        SELECT q.title, q.id, COUNT(qa.id) as attempt_count, AVG(qa.percentage) as avg_score
        FROM quizzes q
        LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.status = 'completed'
        GROUP BY q.id
        ORDER BY attempt_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $popular_quizzes = $stmt->fetchAll();
    
    // Monthly statistics for chart
    $stmt = $db->prepare("
        SELECT DATE_FORMAT(start_time, '%Y-%m') as month,
               COUNT(*) as attempts,
               AVG(percentage) as avg_score
        FROM quiz_attempts
        WHERE status = 'completed' AND start_time >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(start_time, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute();
    $monthly_stats = $stmt->fetchAll();
    
    // Multi-Admin Overview Data
    // Get all admin users and their activity
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email, u.role, u.created_at, u.is_active,
               COUNT(DISTINCT q.id) as quizzes_created,
               COUNT(DISTINCT qa.id) as total_attempts_on_quizzes,
               MAX(q.created_at) as last_quiz_created
        FROM users u
        LEFT JOIN quizzes q ON u.id = q.created_by
        LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id
        WHERE u.role IN ('admin', 'instructor')
        GROUP BY u.id
        ORDER BY u.role DESC, u.name
    ");
    $stmt->execute();
    $admin_users = $stmt->fetchAll();
    
    // System health metrics
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE is_active = 1) as active_users,
            (SELECT COUNT(*) FROM users WHERE is_active = 0) as inactive_users,
            (SELECT COUNT(*) FROM quizzes WHERE is_active = 1) as active_quizzes,
            (SELECT COUNT(*) FROM quizzes WHERE is_active = 0) as inactive_quizzes,
            (SELECT COUNT(*) FROM quiz_attempts WHERE status = 'in_progress') as ongoing_attempts,
            (SELECT COUNT(*) FROM quiz_attempts WHERE DATE(start_time) = CURDATE()) as today_attempts
    ");
    $stmt->execute();
    $system_health = $stmt->fetch();
    
    // Recent admin activities
    $stmt = $db->prepare("
        SELECT 'quiz_created' as activity_type, u.name as admin_name, q.title as item_name, 
               q.created_at as activity_time, u.role as admin_role
        FROM quizzes q
        JOIN users u ON q.created_by = u.id
        WHERE u.role IN ('admin', 'instructor')
        
        UNION ALL
        
        SELECT 'user_created' as activity_type, u.name as admin_name, 
               CONCAT('User: ', u2.name) as item_name, u2.created_at as activity_time, u.role as admin_role
        FROM users u2
        JOIN users u ON u.role IN ('admin', 'instructor')
        WHERE u2.role = 'user' AND u2.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        ORDER BY activity_time DESC
        LIMIT 10
    ");
    $stmt->execute();
    $admin_activities = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $stats = ['total_users' => 0, 'total_quizzes' => 0, 'total_attempts' => 0, 'avg_score' => 0];
    $recent_activities = [];
    $popular_quizzes = [];
    $monthly_stats = [];
    $admin_users = [];
    $system_health = ['active_users' => 0, 'inactive_users' => 0, 'active_quizzes' => 0, 'inactive_quizzes' => 0, 'ongoing_attempts' => 0, 'today_attempts' => 0];
    $admin_activities = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    
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
                        <a class="nav-link active" href="index.php">
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
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../security-info.php">
                            <i class="fas fa-shield-alt me-1"></i>Security
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
        <!-- Alert Container -->
        <div class="alert-container">
            <?php displayAlert(); ?>
        </div>
        
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 mb-0">Admin Dashboard</h1>
                <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Here's what's happening with your quiz platform.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo number_format($stats['total_users']); ?></h4>
                                <p class="mb-0">Total Users</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-primary bg-opacity-75">
                        <a href="users.php" class="text-white text-decoration-none">
                            <small>View all users <i class="fas fa-arrow-right ms-1"></i></small>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo number_format($stats['total_quizzes']); ?></h4>
                                <p class="mb-0">Total Quizzes</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-clipboard-list fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-success bg-opacity-75">
                        <a href="quizzes.php" class="text-white text-decoration-none">
                            <small>Manage quizzes <i class="fas fa-arrow-right ms-1"></i></small>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo number_format($stats['total_attempts']); ?></h4>
                                <p class="mb-0">Quiz Attempts</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-info bg-opacity-75">
                        <a href="reports.php" class="text-white text-decoration-none">
                            <small>View reports <i class="fas fa-arrow-right ms-1"></i></small>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?php echo number_format($stats['avg_score'], 1); ?>%</h4>
                                <p class="mb-0">Average Score</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-trophy fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-warning bg-opacity-75">
                        <a href="reports.php" class="text-dark text-decoration-none">
                            <small>Detailed analytics <i class="fas fa-arrow-right ms-1"></i></small>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Monthly Statistics Chart -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>Monthly Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="quiz-create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create New Quiz
                            </a>
                            <a href="question-create.php" class="btn btn-success">
                                <i class="fas fa-question me-2"></i>Add Questions
                            </a>
                            <a href="categories.php" class="btn btn-info">
                                <i class="fas fa-tags me-2"></i>Manage Categories
                            </a>
                            <a href="users.php" class="btn btn-warning">
                                <i class="fas fa-user-plus me-2"></i>View Users
                            </a>
                            <a href="reports.php" class="btn btn-secondary">
                                <i class="fas fa-download me-2"></i>Export Reports
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Multi-Admin System Overview -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users-cog me-2"></i>Multi-Admin Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- System Health Indicators -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="mini-stat bg-success bg-opacity-10 p-2 rounded text-center">
                                    <small class="text-success fw-bold"><?php echo $system_health['active_users']; ?></small>
                                    <div class="small text-muted">Active Users</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-stat bg-info bg-opacity-10 p-2 rounded text-center">
                                    <small class="text-info fw-bold"><?php echo $system_health['today_attempts']; ?></small>
                                    <div class="small text-muted">Today's Attempts</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Admin Team List -->
                        <div class="admin-list">
                            <h6 class="small text-muted mb-2">ADMIN TEAM</h6>
                            <?php if (empty($admin_users)): ?>
                                <p class="small text-muted">No admin users found</p>
                            <?php else: ?>
                                <?php foreach ($admin_users as $admin): ?>
                                <div class="admin-item d-flex align-items-center justify-content-between py-2 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <div class="admin-avatar me-2">
                                            <i class="fas fa-user-shield text-<?php echo $admin['role'] == 'admin' ? 'danger' : 'warning'; ?>"></i>
                                        </div>
                                        <div>
                                            <div class="small fw-bold"><?php echo htmlspecialchars($admin['name']); ?></div>
                                            <div class="tiny text-muted">
                                                <?php echo ucfirst($admin['role']); ?> • 
                                                <?php echo $admin['quizzes_created']; ?> quiz<?php echo $admin['quizzes_created'] != 1 ? 'es' : ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?php echo $admin['is_active'] ? 'success' : 'secondary'; ?> badge-sm">
                                            <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- System Status -->
                        <div class="system-status mt-3">
                            <h6 class="small text-muted mb-2">SYSTEM STATUS</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="status-item">
                                        <span class="badge bg-primary badge-sm"><?php echo $system_health['active_quizzes']; ?></span>
                                        <small class="text-muted ms-1">Active Quizzes</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="status-item">
                                        <span class="badge bg-warning badge-sm"><?php echo $system_health['ongoing_attempts']; ?></span>
                                        <small class="text-muted ms-1">Ongoing Tests</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Admin Actions -->
                        <div class="mt-3">
                            <div class="d-grid gap-1">
                                <a href="users.php?role=admin" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-user-cog me-1"></i>Manage Admins
                                </a>
                                <a href="reports.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-chart-line me-1"></i>System Health
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <!-- Recent Activities -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Activities
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No recent activities</p>
                            </div>
                        <?php else: ?>
                            <div class="activity-list">
                                <?php foreach ($recent_activities as $activity): ?>
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
                                                    with <?php echo number_format($activity['percentage'], 1); ?>% score
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <strong><?php echo htmlspecialchars($activity['user_name']); ?></strong>
                                                registered as a new user
                                            <?php endif; ?>
                                            <div class="text-muted small">
                                                <?php echo timeAgo($activity['activity_time']); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Popular Quizzes -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-fire me-2"></i>Popular Quizzes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($popular_quizzes)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No quiz data available</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($popular_quizzes as $quiz): ?>
                                <div class="quiz-item d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($quiz['title']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo $quiz['attempt_count']; ?> attempts
                                            <?php if ($quiz['avg_score']): ?>
                                                • <?php echo number_format($quiz['avg_score'], 1); ?>% avg
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <a href="quiz-edit.php?id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize monthly statistics chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            
            const monthlyData = <?php echo json_encode($monthly_stats); ?>;
            const labels = monthlyData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            });
            const attempts = monthlyData.map(item => item.attempts);
            const avgScores = monthlyData.map(item => parseFloat(item.avg_score));
            
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
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        },
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
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        });
    </script>
    
    <style>
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
        
        .quiz-item:last-child {
            border-bottom: none !important;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .navbar-dark {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        /* Multi-Admin Overview Styles */
        .mini-stat {
            transition: all 0.2s ease;
        }
        
        .mini-stat:hover {
            transform: translateY(-1px);
        }
        
        .admin-item {
            transition: all 0.2s ease;
        }
        
        .admin-item:hover {
            background-color: #f8f9fa;
            border-radius: 5px;
            margin: 0 -8px;
            padding-left: 8px !important;
            padding-right: 8px !important;
        }
        
        .admin-item:last-child {
            border-bottom: none !important;
        }
        
        .admin-avatar {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 50%;
            font-size: 0.8rem;
        }
        
        .tiny {
            font-size: 0.7rem;
        }
        
        .badge-sm {
            font-size: 0.65rem;
            padding: 0.2em 0.4em;
        }
        
        .admin-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .admin-list::-webkit-scrollbar {
            width: 4px;
        }
        
        .admin-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }
        
        .admin-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 2px;
        }
        
        .admin-list::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        .system-status .status-item {
            display: flex;
            align-items: center;
            padding: 0.25rem 0;
        }
    </style>
</body>
</html>
