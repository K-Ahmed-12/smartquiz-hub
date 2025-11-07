<?php
require_once 'config/config.php';

// Get popular quizzes and categories
try {
    $db = getDB();
    
    // Get top 6 popular quizzes
    $stmt = $db->prepare("
        SELECT q.*, c.name as category_name, 
               COUNT(qa.id) as attempt_count,
               AVG(qa.percentage) as avg_score
        FROM quizzes q 
        LEFT JOIN categories c ON q.category_id = c.id 
        LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id 
        WHERE q.is_active = 1 
        GROUP BY q.id 
        ORDER BY attempt_count DESC, q.created_at DESC 
        LIMIT 6
    ");
    $stmt->execute();
    $popular_quizzes = $stmt->fetchAll();
    
    // Get all categories
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $popular_quizzes = [];
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo SITE_TAGLINE; ?></title>
    
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
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quizzes.php">Quizzes</a>
                    </li>
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

    <!-- Hero Section -->
    <section class="hero-section bg-gradient-primary text-white">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="display-4 fw-bold mb-4">
                            Practice. Learn. <span class="text-warning">Improve.</span>
                        </h1>
                        <p class="lead mb-4">
                            Test your knowledge with our interactive quizzes. Track your progress, 
                            compete with others, and enhance your learning experience.
                        </p>
                        <div class="hero-buttons">
                            <?php if (isLoggedIn()): ?>
                                <a href="quizzes.php" class="btn btn-warning btn-lg me-3">
                                    <i class="fas fa-play me-2"></i>Start Quiz
                                </a>
                            <?php else: ?>
                                <a href="register.php" class="btn btn-warning btn-lg me-3">
                                    <i class="fas fa-user-plus me-2"></i>Get Started
                                </a>
                            <?php endif; ?>
                            <a href="#features" class="btn btn-outline-light btn-lg">
                                Learn More
                            </a>
                        </div>
                        
                        <!-- Stats -->
                        <div class="row mt-5">
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="fw-bold">1000+</h3>
                                    <p class="mb-0">Questions</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="fw-bold">50+</h3>
                                    <p class="mb-0">Quizzes</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="fw-bold">500+</h3>
                                    <p class="mb-0">Students</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image text-center">
                        <i class="fas fa-graduation-cap hero-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-5">
                    <h2 class="section-title">Why Choose SmartQuiz Hub?</h2>
                    <p class="section-subtitle">Discover the features that make learning fun and effective</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4>Instant Results</h4>
                        <p>Get immediate feedback with automatic grading and detailed explanations.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4>Progress Tracking</h4>
                        <p>Monitor your improvement with comprehensive analytics and performance insights.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h4>Leaderboards</h4>
                        <p>Compete with others and climb the rankings to showcase your knowledge.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Quizzes Section -->
    <?php if (!empty($popular_quizzes)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-5">
                    <h2 class="section-title">Popular Quizzes</h2>
                    <p class="section-subtitle">Try these trending quizzes loved by our community</p>
                </div>
            </div>
            
            <div class="row g-4">
                <?php foreach ($popular_quizzes as $quiz): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="quiz-card">
                        <div class="quiz-card-header">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($quiz['category_name']); ?></span>
                            <span class="badge bg-secondary"><?php echo ucfirst($quiz['difficulty']); ?></span>
                        </div>
                        <div class="quiz-card-body">
                            <h5 class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></h5>
                            <p class="quiz-description"><?php echo htmlspecialchars(substr($quiz['description'], 0, 100)) . '...'; ?></p>
                            
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
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $quiz['attempt_count']; ?> attempts</span>
                                </div>
                            </div>
                        </div>
                        <div class="quiz-card-footer">
                            <?php if (isLoggedIn()): ?>
                                <a href="take-quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary w-100">
                                    <i class="fas fa-play me-2"></i>Start Quiz
                                </a>
                            <?php else: ?>
                                <a href="login.php?redirect=take-quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Start
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="quizzes.php" class="btn btn-outline-primary btn-lg">
                    View All Quizzes <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Categories Section -->
    <?php if (!empty($categories)): ?>
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-5">
                    <h2 class="section-title">Quiz Categories</h2>
                    <p class="section-subtitle">Explore quizzes across different subjects and topics</p>
                </div>
            </div>
            
            <div class="row g-4">
                <?php foreach ($categories as $category): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="quizzes.php?category=<?php echo $category['id']; ?>" class="category-card">
                        <div class="category-icon">
                            <i class="<?php echo $category['icon']; ?>"></i>
                        </div>
                        <h5><?php echo htmlspecialchars($category['name']); ?></h5>
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="cta-section bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="mb-3">Ready to Test Your Knowledge?</h2>
                    <p class="mb-0">Join thousands of learners and start your quiz journey today!</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Get Started
                        </a>
                    <?php else: ?>
                        <a href="quizzes.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-play me-2"></i>Take a Quiz
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
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
</body>
</html>
