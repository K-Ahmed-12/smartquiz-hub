<?php
require_once 'config/config.php';

// Require login
requireLogin();

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    
    // Get user statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT qa.quiz_id) as quizzes_taken,
            COUNT(qa.id) as total_attempts,
            AVG(qa.percentage) as avg_score,
            MAX(qa.percentage) as best_score
        FROM quiz_attempts qa 
        WHERE qa.user_id = ? AND qa.status = 'completed'
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    
    // Get recent quiz attempts
    $stmt = $db->prepare("
        SELECT qa.*, q.title, q.total_marks, c.name as category_name
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        LEFT JOIN categories c ON q.category_id = c.id
        WHERE qa.user_id = ?
        ORDER BY qa.start_time DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recent_attempts = $stmt->fetchAll();
    
    // Get user's rank (simplified)
    $stmt = $db->prepare("
        SELECT COUNT(*) + 1 as user_rank
        FROM (
            SELECT user_id, AVG(percentage) as avg_score
            FROM quiz_attempts 
            WHERE status = 'completed'
            GROUP BY user_id
            HAVING avg_score > (
                SELECT AVG(percentage) 
                FROM quiz_attempts 
                WHERE user_id = ? AND status = 'completed'
            )
        ) as better_users
    ");
    $stmt->execute([$user_id]);
    $rank_data = $stmt->fetch();
    $user_rank = $rank_data['user_rank'] ?? 'N/A';
    
    // Get achievements (simplified)
    $achievements = [];
    if ($stats['quizzes_taken'] >= 5) {
        $achievements[] = ['name' => 'Quiz Explorer', 'description' => 'Completed 5+ different quizzes', 'icon' => 'fas fa-compass'];
    }
    if ($stats['best_score'] >= 90) {
        $achievements[] = ['name' => 'High Achiever', 'description' => 'Scored 90% or higher', 'icon' => 'fas fa-trophy'];
    }
    if ($stats['total_attempts'] >= 10) {
        $achievements[] = ['name' => 'Dedicated Learner', 'description' => 'Completed 10+ quiz attempts', 'icon' => 'fas fa-medal'];
    }
    
} catch(PDOException $e) {
    $stats = ['quizzes_taken' => 0, 'total_attempts' => 0, 'avg_score' => 0, 'best_score' => 0];
    $recent_attempts = [];
    $user_rank = 'N/A';
    $achievements = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="dashboard">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-brain me-2"></i><?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quizzes.php">Quizzes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php">Leaderboard</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <button class="btn btn-link nav-link" id="themeToggle">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                            <?php if (isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin/"><i class="fas fa-cog me-2"></i>Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5 pt-4">
        <!-- Alert Container -->
        <div class="alert-container">
            <?php displayAlert(); ?>
        </div>
        
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-card bg-gradient-primary text-white p-4 rounded-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 👋</h2>
                            <p class="mb-0 opacity-75">Ready to challenge yourself with some quizzes today?</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="quizzes.php" class="btn btn-warning btn-lg">
                                <i class="fas fa-play me-2"></i>Take a Quiz
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card bg-primary text-white">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['quizzes_taken']; ?></h3>
                        <p>Quizzes Taken</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card bg-success text-white">
                    <div class="stat-icon">
                        <i class="fas fa-redo"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_attempts']; ?></h3>
                        <p>Total Attempts</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card bg-info text-white">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['avg_score'], 1); ?>%</h3>
                        <p>Average Score</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card bg-warning text-dark">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['best_score'], 1); ?>%</h3>
                        <p>Best Score</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Quiz Attempts -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Quiz Attempts</h5>
                        <a href="quiz-history.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_attempts)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No quiz attempts yet</h6>
                                <p class="text-muted">Start taking quizzes to see your progress here!</p>
                                <a href="quizzes.php" class="btn btn-primary">Browse Quizzes</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Quiz</th>
                                            <th>Category</th>
                                            <th>Score</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_attempts as $attempt): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($attempt['title']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($attempt['category_name']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($attempt['status'] == 'completed'): ?>
                                                    <span class="fw-bold <?php echo $attempt['percentage'] >= 70 ? 'text-success' : ($attempt['percentage'] >= 50 ? 'text-warning' : 'text-danger'); ?>">
                                                        <?php echo number_format($attempt['percentage'], 1); ?>%
                                                    </span>
                                                    <small class="text-muted">(<?php echo $attempt['score']; ?>/<?php echo $attempt['total_marks']; ?>)</small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo timeAgo($attempt['start_time']); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'completed' => 'success',
                                                    'in_progress' => 'warning',
                                                    'abandoned' => 'danger'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $status_class[$attempt['status']]; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $attempt['status'])); ?>
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

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Performance Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Performance Overview</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="performanceChart" width="300" height="200"></canvas>
                    </div>
                </div>

                <!-- Rank Card -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <i class="fas fa-medal fa-3x text-warning mb-3"></i>
                        <h4>Rank #<?php echo $user_rank; ?></h4>
                        <p class="text-muted">Your current ranking</p>
                        <a href="leaderboard.php" class="btn btn-outline-primary btn-sm">View Leaderboard</a>
                    </div>
                </div>

                <!-- Achievements -->
                <?php if (!empty($achievements)): ?>
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-trophy me-2"></i>Achievements</h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($achievements as $achievement): ?>
                        <div class="achievement-item d-flex align-items-center mb-3">
                            <div class="achievement-icon me-3">
                                <i class="<?php echo $achievement['icon']; ?> fa-2x text-warning"></i>
                            </div>
                            <div>
                                <h6 class="mb-1"><?php echo $achievement['name']; ?></h6>
                                <small class="text-muted"><?php echo $achievement['description']; ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Initialize performance chart
        function initializeCharts() {
            const ctx = document.getElementById('performanceChart').getContext('2d');
            
            const avgScore = <?php echo $stats['avg_score']; ?>;
            const remaining = 100 - avgScore;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Average Score', 'Room for Improvement'],
                    datasets: [{
                        data: [avgScore, remaining],
                        backgroundColor: [
                            '#28a745',
                            '#e9ecef'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    cutout: '70%'
                }
            });
        }
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', initializeCharts);
    </script>
    
    <style>
        .welcome-card {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }
        
        .stat-card {
            border-radius: 15px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-right: 1rem;
            opacity: 0.8;
        }
        
        .stat-content h3 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-content p {
            margin-bottom: 0;
            opacity: 0.9;
        }
        
        .achievement-icon {
            width: 50px;
            text-align: center;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 15px 15px 0 0 !important;
        }
    </style>
</body>
</html>
