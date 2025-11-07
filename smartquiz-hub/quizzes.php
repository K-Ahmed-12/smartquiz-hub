<?php
require_once 'config/config.php';

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$difficulty_filter = isset($_GET['difficulty']) ? sanitizeInput($_GET['difficulty']) : '';
$search_query = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = ITEMS_PER_PAGE;
$offset = ($page - 1) * $per_page;

try {
    $db = getDB();
    
    // Build WHERE clause
    $where_conditions = ['q.is_active = 1'];
    $params = [];
    
    if ($category_filter > 0) {
        $where_conditions[] = 'q.category_id = ?';
        $params[] = $category_filter;
    }
    
    if (!empty($difficulty_filter)) {
        $where_conditions[] = 'q.difficulty = ?';
        $params[] = $difficulty_filter;
    }
    
    if (!empty($search_query)) {
        $where_conditions[] = '(q.title LIKE ? OR q.description LIKE ?)';
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) as total
        FROM quizzes q 
        LEFT JOIN categories c ON q.category_id = c.id 
        $where_clause
    ";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_quizzes = $stmt->fetch()['total'];
    $total_pages = ceil($total_quizzes / $per_page);
    
    // Get quizzes with pagination
    $sql = "
        SELECT q.*, c.name as category_name, 
               COUNT(qa.id) as attempt_count,
               AVG(qa.percentage) as avg_score,
               (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
        FROM quizzes q 
        LEFT JOIN categories c ON q.category_id = c.id 
        LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.status = 'completed'
        $where_clause
        GROUP BY q.id 
        ORDER BY q.created_at DESC
        LIMIT $per_page OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $quizzes = $stmt->fetchAll();
    
    // Get all categories for filter
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $quizzes = [];
    $categories = [];
    $total_quizzes = 0;
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="quizzes">
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
                        <a class="nav-link active" href="quizzes.php">Quizzes</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php">Leaderboard</a>
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
    <section class="page-header bg-primary text-white py-5 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-2">
                        <i class="fas fa-clipboard-list me-3"></i>All Quizzes
                    </h1>
                    <p class="lead mb-0">
                        Discover and take quizzes across various categories. Test your knowledge and improve your skills!
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="stats-info">
                        <h4><?php echo $total_quizzes; ?></h4>
                        <p class="mb-0">Available Quizzes</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <!-- Alert Container -->
        <div class="alert-container">
            <?php displayAlert(); ?>
        </div>
        
        <!-- Search and Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" id="filterForm" class="row g-3">
                            <!-- Search -->
                            <div class="col-md-4">
                                <div class="position-relative">
                                    <input type="text" class="form-control" id="searchInput" name="search" 
                                           placeholder="Search quizzes..." value="<?php echo htmlspecialchars($search_query); ?>">
                                    <div class="search-results" id="searchResults"></div>
                                </div>
                            </div>
                            
                            <!-- Category Filter -->
                            <div class="col-md-3">
                                <select class="form-select" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Difficulty Filter -->
                            <div class="col-md-3">
                                <select class="form-select" name="difficulty">
                                    <option value="">All Difficulties</option>
                                    <option value="easy" <?php echo $difficulty_filter == 'easy' ? 'selected' : ''; ?>>Easy</option>
                                    <option value="medium" <?php echo $difficulty_filter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="hard" <?php echo $difficulty_filter == 'hard' ? 'selected' : ''; ?>>Hard</option>
                                </select>
                            </div>
                            
                            <!-- Filter Buttons -->
                            <div class="col-md-2">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <a href="quizzes.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="text-center py-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Quiz Grid -->
        <div class="row g-4" id="quizContainer">
            <?php if (empty($quizzes)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted">No quizzes found</h4>
                        <p class="text-muted">Try adjusting your search criteria or browse all quizzes.</p>
                        <a href="quizzes.php" class="btn btn-primary">View All Quizzes</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($quizzes as $quiz): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="quiz-card">
                        <div class="quiz-card-header">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($quiz['category_name']); ?></span>
                            <span class="badge bg-<?php echo $quiz['difficulty'] == 'easy' ? 'success' : ($quiz['difficulty'] == 'medium' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($quiz['difficulty']); ?>
                            </span>
                        </div>
                        <div class="quiz-card-body">
                            <h5 class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></h5>
                            <p class="quiz-description">
                                <?php echo htmlspecialchars(substr($quiz['description'], 0, 120)) . (strlen($quiz['description']) > 120 ? '...' : ''); ?>
                            </p>
                            
                            <div class="quiz-meta">
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $quiz['time_limit']; ?> min</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-star"></i>
                                    <span><?php echo $quiz['total_marks']; ?> marks</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-question-circle"></i>
                                    <span><?php echo $quiz['question_count']; ?> questions</span>
                                </div>
                            </div>
                            
                            <div class="quiz-stats mt-3">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <small class="text-muted">Attempts</small>
                                        <div class="fw-bold"><?php echo $quiz['attempt_count']; ?></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Avg Score</small>
                                        <div class="fw-bold"><?php echo $quiz['avg_score'] ? number_format($quiz['avg_score'], 1) . '%' : 'N/A'; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="quiz-card-footer">
                            <?php if (isLoggedIn()): ?>
                                <button onclick="startQuiz(<?php echo $quiz['id']; ?>)" class="btn btn-primary w-100">
                                    <i class="fas fa-play me-2"></i>Start Quiz
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Start
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="row mt-5">
            <div class="col-12">
                <nav aria-label="Quiz pagination" id="paginationContainer">
                    <ul class="pagination justify-content-center">
                        <!-- Previous -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Next -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center text-muted">
                    Showing <?php echo min($total_quizzes, ($page - 1) * $per_page + 1); ?> to 
                    <?php echo min($total_quizzes, $page * $per_page); ?> of <?php echo $total_quizzes; ?> quizzes
                </div>
            </div>
        </div>
        <?php endif; ?>
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
        // Start quiz function
        function startQuiz(quizId) {
            if (confirm('Are you ready to start this quiz? The timer will begin immediately once you proceed.')) {
                window.location.href = `take-quiz.php?id=${quizId}`;
            }
        }
        
        // Real-time search
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const filterForm = document.getElementById('filterForm');
            
            // Auto-submit form on filter change
            const filterSelects = filterForm.querySelectorAll('select');
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    filterForm.submit();
                });
            });
            
            // Search on Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    filterForm.submit();
                }
            });
        });
    </script>
    
    <style>
        .page-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }
        
        .quiz-stats {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.75rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .meta-item i {
            margin-right: 0.5rem;
            color: #007bff;
            width: 16px;
        }
        
        @media (max-width: 768px) {
            .quiz-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .meta-item {
                justify-content: center;
            }
        }
    </style>
</body>
</html>
