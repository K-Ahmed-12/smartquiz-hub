<?php
require_once 'config/config.php';

$filter = $_GET['filter'] ?? 'overall';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

try {
    $db = getDB();
    
    // Get categories for filter
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    // Build leaderboard query based on filter
    $where_clause = '';
    $params = [];
    
    if ($category_id > 0) {
        $where_clause = 'AND q.category_id = ?';
        $params[] = $category_id;
    }
    
    if ($filter == 'weekly') {
        $where_clause .= ' AND qa.start_time >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
    } elseif ($filter == 'monthly') {
        $where_clause .= ' AND qa.start_time >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
    }
    
    // Get top performers
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email,
               COUNT(DISTINCT qa.quiz_id) as quizzes_completed,
               COUNT(qa.id) as total_attempts,
               AVG(qa.percentage) as avg_score,
               MAX(qa.percentage) as best_score,
               SUM(qa.score) as total_points
        FROM users u
        JOIN quiz_attempts qa ON u.id = qa.user_id
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE u.role = 'user' AND qa.status = 'completed' $where_clause
        GROUP BY u.id
        HAVING avg_score > 0
        ORDER BY avg_score DESC, total_points DESC, quizzes_completed DESC
        LIMIT " . LEADERBOARD_LIMIT
    );
    $stmt->execute($params);
    $leaderboard = $stmt->fetchAll();
    
    // Get current user's rank if logged in
    $user_rank = null;
    $user_stats = null;
    
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        
        // Get user's stats
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT qa.quiz_id) as quizzes_completed,
                   COUNT(qa.id) as total_attempts,
                   AVG(qa.percentage) as avg_score,
                   MAX(qa.percentage) as best_score,
                   SUM(qa.score) as total_points
            FROM quiz_attempts qa
            JOIN quizzes q ON qa.quiz_id = q.id
            WHERE qa.user_id = ? AND qa.status = 'completed' $where_clause
        ");
        $stmt->execute(array_merge([$user_id], $params));
        $user_stats = $stmt->fetch();
        
        // Get user's rank
        if ($user_stats['avg_score'] > 0) {
            $stmt = $db->prepare("
                SELECT COUNT(*) + 1 as rank
                FROM (
                    SELECT u.id, AVG(qa.percentage) as avg_score, SUM(qa.score) as total_points,
                           COUNT(DISTINCT qa.quiz_id) as quizzes_completed
                    FROM users u
                    JOIN quiz_attempts qa ON u.id = qa.user_id
                    JOIN quizzes q ON qa.quiz_id = q.id
                    WHERE u.role = 'user' AND qa.status = 'completed' $where_clause
                    GROUP BY u.id
                    HAVING avg_score > ? OR 
                           (avg_score = ? AND total_points > ?) OR
                           (avg_score = ? AND total_points = ? AND quizzes_completed > ?)
                ) as better_users
            ");
            $stmt->execute(array_merge($params, [
                $user_stats['avg_score'],
                $user_stats['avg_score'], $user_stats['total_points'],
                $user_stats['avg_score'], $user_stats['total_points'], $user_stats['quizzes_completed']
            ]));
            $user_rank = $stmt->fetch()['rank'];
        }
    }
    
    // Get recent achievements (top scores in last 24 hours) - filtered by category if selected
    $achievements_where = "qa.status = 'completed' AND qa.start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $achievements_params = [];
    
    if ($category_id > 0) {
        $achievements_where .= ' AND q.category_id = ?';
        $achievements_params[] = $category_id;
    }
    
    $stmt = $db->prepare("
        SELECT u.name, q.title, qa.percentage, qa.start_time, c.name as category_name
        FROM quiz_attempts qa
        JOIN users u ON qa.user_id = u.id
        JOIN quizzes q ON qa.quiz_id = q.id
        JOIN categories c ON q.category_id = c.id
        WHERE $achievements_where
        ORDER BY qa.percentage DESC, qa.start_time DESC
        LIMIT 5
    ");
    $stmt->execute($achievements_params);
    $recent_achievements = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $leaderboard = [];
    $categories = [];
    $recent_achievements = [];
    $user_rank = null;
    $user_stats = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
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
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="leaderboard.php">Leaderboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <button class="btn btn-link nav-link" id="themeToggle">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                    <?php if (isLoggedIn()): ?>
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
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-light btn-sm ms-2" href="register.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header bg-gradient-primary text-white py-5 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-2">
                        <i class="fas fa-trophy me-3"></i>Leaderboard
                    </h1>
                    <p class="lead mb-0">
                        See how you rank among our top quiz performers!
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <i class="fas fa-medal fa-5x opacity-50"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <!-- User Rank Card (if logged in) -->
        <?php if (isLoggedIn() && $user_stats && $user_stats['avg_score'] > 0): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card user-rank-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <div class="rank-display">
                                    <div class="rank-number">#<?php echo $user_rank; ?></div>
                                    <div class="rank-label">Your Rank</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <h5 class="mb-1"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
                                <p class="text-muted mb-0">That's you!</p>
                            </div>
                            <div class="col-md-7">
                                <div class="row text-center">
                                    <div class="col-3">
                                        <div class="stat-value"><?php echo number_format($user_stats['avg_score'], 1); ?>%</div>
                                        <div class="stat-label">Avg Score</div>
                                    </div>
                                    <div class="col-3">
                                        <div class="stat-value"><?php echo $user_stats['quizzes_completed']; ?></div>
                                        <div class="stat-label">Quizzes</div>
                                    </div>
                                    <div class="col-3">
                                        <div class="stat-value"><?php echo $user_stats['total_attempts']; ?></div>
                                        <div class="stat-label">Attempts</div>
                                    </div>
                                    <div class="col-3">
                                        <div class="stat-value"><?php echo number_format($user_stats['best_score'], 1); ?>%</div>
                                        <div class="stat-label">Best Score</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Time Period</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="filter" id="overall" value="overall" 
                                           <?php echo $filter == 'overall' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="overall">All Time</label>
                                    
                                    <input type="radio" class="btn-check" name="filter" id="monthly" value="monthly"
                                           <?php echo $filter == 'monthly' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="monthly">This Month</label>
                                    
                                    <input type="radio" class="btn-check" name="filter" id="weekly" value="weekly"
                                           <?php echo $filter == 'weekly' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="weekly">This Week</label>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" name="category" id="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Leaderboard -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>Top Performers
                            <?php if ($filter == 'weekly'): ?>
                                <span class="badge bg-primary ms-2">This Week</span>
                            <?php elseif ($filter == 'monthly'): ?>
                                <span class="badge bg-primary ms-2">This Month</span>
                            <?php endif; ?>
                            <?php if ($category_id > 0): ?>
                                <?php 
                                $selected_category = array_filter($categories, function($cat) use ($category_id) {
                                    return $cat['id'] == $category_id;
                                });
                                $selected_category = reset($selected_category);
                                ?>
                                <span class="badge bg-success ms-2">
                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($selected_category['name']); ?>
                                </span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($leaderboard)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-trophy fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No data available</h5>
                                <p class="text-muted">Be the first to complete a quiz and claim the top spot!</p>
                                <a href="quizzes.php" class="btn btn-primary">
                                    <i class="fas fa-play me-2"></i>Take a Quiz
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="leaderboard-list">
                                <?php foreach ($leaderboard as $index => $user): ?>
                                    <div class="leaderboard-item <?php echo $index < 3 ? 'top-performer' : ''; ?> 
                                         <?php echo (isLoggedIn() && $user['id'] == $_SESSION['user_id']) ? 'current-user' : ''; ?>">
                                        <div class="rank-section">
                                            <?php if ($index == 0): ?>
                                                <i class="fas fa-crown text-warning fa-2x"></i>
                                            <?php elseif ($index == 1): ?>
                                                <i class="fas fa-medal text-secondary fa-2x"></i>
                                            <?php elseif ($index == 2): ?>
                                                <i class="fas fa-medal text-warning fa-2x"></i>
                                            <?php else: ?>
                                                <div class="rank-number"><?php echo $index + 1; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="user-info">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($user['name']); ?>
                                                <?php if (isLoggedIn() && $user['id'] == $_SESSION['user_id']): ?>
                                                    <span class="badge bg-info ms-2">You</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo $user['quizzes_completed']; ?> quizzes completed
                                            </small>
                                        </div>
                                        
                                        <div class="stats-section">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div class="stat-value text-primary"><?php echo number_format($user['avg_score'], 1); ?>%</div>
                                                    <div class="stat-label">Avg Score</div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-value text-success"><?php echo number_format($user['best_score'], 1); ?>%</div>
                                                    <div class="stat-label">Best Score</div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-value text-info"><?php echo $user['total_points']; ?></div>
                                                    <div class="stat-label">Points</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Recent Achievements -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-star me-2"></i>Recent Achievements
                            <?php if ($category_id > 0): ?>
                                <span class="badge bg-success ms-2">
                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($selected_category['name']); ?>
                                </span>
                            <?php endif; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_achievements)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-star fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No recent achievements</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_achievements as $achievement): ?>
                                <div class="achievement-item mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="achievement-icon me-3">
                                            <i class="fas fa-star text-warning"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($achievement['name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo number_format($achievement['percentage'], 1); ?>% on 
                                                <?php echo htmlspecialchars($achievement['title']); ?>
                                                <?php if (isset($achievement['category_name'])): ?>
                                                    <span class="badge bg-light text-dark ms-1"><?php echo htmlspecialchars($achievement['category_name']); ?></span>
                                                <?php endif; ?>
                                            </small>
                                            <br>
                                            <small class="text-muted"><?php echo timeAgo($achievement['start_time']); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Call to Action -->
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-rocket fa-3x text-primary mb-3"></i>
                        <h5>Ready to Climb the Ranks?</h5>
                        <p class="text-muted">Take more quizzes to improve your ranking and compete with other learners!</p>
                        <a href="quizzes.php" class="btn btn-primary">
                            <i class="fas fa-play me-2"></i>Browse Quizzes
                        </a>
                        <?php if (!isLoggedIn()): ?>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <a href="register.php">Sign up</a> to track your progress and appear on the leaderboard!
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p class="mb-0"><?php echo SITE_TAGLINE; ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin"></i></a>
                    </div>
                    <p class="mb-0 mt-2">&copy; 2025 <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Auto-submit form on filter change
        document.addEventListener('DOMContentLoaded', function() {
            const filterInputs = document.querySelectorAll('input[name="filter"], select[name="category"]');
            
            filterInputs.forEach(input => {
                input.addEventListener('change', function() {
                    this.form.submit();
                });
            });
        });
    </script>
    
    <style>
        .page-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }
        
        .user-rank-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #007bff;
        }
        
        .rank-display .rank-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .rank-display .rank-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #f1f1f1;
            transition: background-color 0.3s ease;
        }
        
        .leaderboard-item:hover {
            background-color: #f8f9fa;
        }
        
        .leaderboard-item:last-child {
            border-bottom: none;
        }
        
        .leaderboard-item.current-user {
            background-color: #e3f2fd;
            border-left: 4px solid #007bff;
        }
        
        .leaderboard-item.top-performer {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        }
        
        .rank-section {
            width: 60px;
            text-align: center;
            margin-right: 1rem;
        }
        
        .rank-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #495057;
        }
        
        .user-info {
            flex-grow: 1;
            margin-right: 1rem;
        }
        
        .stats-section {
            min-width: 200px;
        }
        
        .stat-value {
            font-size: 1.1rem;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .achievement-icon {
            width: 30px;
            text-align: center;
        }
        
        .achievement-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .achievement-item:last-child {
            border-bottom: none;
        }
        
        .btn-check:checked + .btn-outline-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .card {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 15px 15px 0 0 !important;
        }
        
        @media (max-width: 768px) {
            .leaderboard-item {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            
            .rank-section,
            .user-info {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .stats-section {
                min-width: auto;
                width: 100%;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn-group .btn {
                border-radius: 0.375rem !important;
                margin-bottom: 0.25rem;
            }
        }
    </style>
</body>
</html>
