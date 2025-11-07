<?php
require_once 'config/config.php';

// Require login
requireLogin();

$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$quiz = null;
$questions = [];
$attempt = null;

if ($quiz_id <= 0) {
    redirect('quizzes.php');
}

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    
    // Get quiz details
    $stmt = $db->prepare("
        SELECT q.*, c.name as category_name,
               (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
        FROM quizzes q 
        LEFT JOIN categories c ON q.category_id = c.id 
        WHERE q.id = ? AND q.is_active = 1
    ");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        showAlert('Quiz not found or not available.', 'danger');
        redirect('quizzes.php');
    }
    
    // Check if user has an active attempt
    $stmt = $db->prepare("
        SELECT * FROM quiz_attempts 
        WHERE user_id = ? AND quiz_id = ? AND status = 'in_progress'
        ORDER BY start_time DESC LIMIT 1
    ");
    $stmt->execute([$user_id, $quiz_id]);
    $existing_attempt = $stmt->fetch();
    
    // Handle form submission (start new quiz or continue existing)
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'start_new') {
                // Mark existing attempt as abandoned if exists
                if ($existing_attempt) {
                    $stmt = $db->prepare("UPDATE quiz_attempts SET status = 'abandoned' WHERE id = ?");
                    $stmt->execute([$existing_attempt['id']]);
                }
                
                // Update quiz total_marks first
                updateQuizTotalMarks($quiz_id);
                
                // Get the updated total_marks
                $stmt = $db->prepare("SELECT total_marks FROM quizzes WHERE id = ?");
                $stmt->execute([$quiz_id]);
                $updated_quiz = $stmt->fetch();
                
                // Create new attempt with correct total_marks
                $stmt = $db->prepare("
                    INSERT INTO quiz_attempts (user_id, quiz_id, total_marks) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$user_id, $quiz_id, $updated_quiz['total_marks']]);
                $attempt_id = $db->lastInsertId();
                
                redirect("quiz-interface.php?attempt_id=$attempt_id");
                
            } elseif ($_POST['action'] == 'continue' && $existing_attempt) {
                redirect("quiz-interface.php?attempt_id=" . $existing_attempt['id']);
            }
        }
    }
    
    // Get quiz questions for preview
    $stmt = $db->prepare("
        SELECT id, question_text, question_type, marks 
        FROM questions 
        WHERE quiz_id = ? 
        ORDER BY order_number, id
    ");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $errors[] = 'Database error occurred.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title'] ?? 'Quiz'); ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-brain me-2"></i><?php echo SITE_NAME; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="quizzes.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Quizzes
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($quiz): ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Quiz Header -->
                <div class="card quiz-header-card mb-4">
                    <div class="card-body text-center">
                        <div class="quiz-category mb-2">
                            <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($quiz['category_name']); ?></span>
                            <span class="badge bg-<?php echo $quiz['difficulty'] == 'easy' ? 'success' : ($quiz['difficulty'] == 'medium' ? 'warning' : 'danger'); ?> fs-6">
                                <?php echo ucfirst($quiz['difficulty']); ?>
                            </span>
                        </div>
                        
                        <h1 class="quiz-title mb-3"><?php echo htmlspecialchars($quiz['title']); ?></h1>
                        <p class="quiz-description text-muted mb-4"><?php echo htmlspecialchars($quiz['description']); ?></p>
                        
                        <!-- Quiz Stats -->
                        <div class="row quiz-stats">
                            <div class="col-md-3 col-6">
                                <div class="stat-item">
                                    <i class="fas fa-question-circle text-primary"></i>
                                    <div class="stat-value"><?php echo count($questions); ?></div>
                                    <div class="stat-label">Questions</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="stat-item">
                                    <i class="fas fa-clock text-warning"></i>
                                    <div class="stat-value"><?php echo $quiz['time_limit']; ?></div>
                                    <div class="stat-label">Minutes</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="stat-item">
                                    <i class="fas fa-star text-success"></i>
                                    <div class="stat-value"><?php echo $quiz['total_marks']; ?></div>
                                    <div class="stat-label">Total Marks</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="stat-item">
                                    <i class="fas fa-redo text-info"></i>
                                    <div class="stat-value"><?php echo $quiz['allow_retake'] ? 'Yes' : 'No'; ?></div>
                                    <div class="stat-label">Retake Allowed</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="card instructions-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Instructions</h5>
                    </div>
                    <div class="card-body">
                        <ul class="instruction-list">
                            <li><i class="fas fa-check text-success me-2"></i>Read each question carefully before answering</li>
                            <li><i class="fas fa-check text-success me-2"></i>You have <strong><?php echo $quiz['time_limit']; ?> minutes</strong> to complete this quiz</li>
                            <li><i class="fas fa-check text-success me-2"></i>Your answers will be auto-saved every 30 seconds</li>
                            <li><i class="fas fa-check text-success me-2"></i>You can navigate between questions using the navigation buttons</li>
                            <li><i class="fas fa-check text-success me-2"></i>Make sure to submit your quiz before time runs out</li>
                            <?php if (!$quiz['allow_retake']): ?>
                            <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>You can only take this quiz <strong>once</strong></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Question Preview -->
                <div class="card question-preview-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Question Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $question_types = ['multiple_choice' => 0, 'true_false' => 0, 'short_answer' => 0];
                            foreach ($questions as $question) {
                                $question_types[$question['question_type']]++;
                            }
                            ?>
                            <div class="col-md-4">
                                <div class="question-type-stat">
                                    <i class="fas fa-list-ul text-primary"></i>
                                    <span class="count"><?php echo $question_types['multiple_choice']; ?></span>
                                    <span class="label">Multiple Choice</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="question-type-stat">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span class="count"><?php echo $question_types['true_false']; ?></span>
                                    <span class="label">True/False</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="question-type-stat">
                                    <i class="fas fa-edit text-info"></i>
                                    <span class="count"><?php echo $question_types['short_answer']; ?></span>
                                    <span class="label">Short Answer</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card action-card">
                    <div class="card-body text-center">
                        <?php if ($existing_attempt): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                You have an incomplete attempt for this quiz. You can continue where you left off or start fresh.
                            </div>
                            
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="continue">
                                <button type="submit" class="btn btn-success btn-lg me-3">
                                    <i class="fas fa-play me-2"></i>Continue Previous Attempt
                                </button>
                            </form>
                            
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="start_new">
                                <button type="submit" class="btn btn-warning btn-lg" 
                                        onclick="return confirm('This will abandon your previous attempt. Are you sure?')">
                                    <i class="fas fa-refresh me-2"></i>Start New Attempt
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="start_new">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-play me-2"></i>Start Quiz Now
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="quizzes.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Quiz List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .quiz-header-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .quiz-title {
            color: #2c3e50;
            font-weight: 700;
        }
        
        .quiz-stats {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
        }
        
        .stat-item i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .instruction-list {
            list-style: none;
            padding: 0;
        }
        
        .instruction-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .instruction-list li:last-child {
            border-bottom: none;
        }
        
        .question-type-stat {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .question-type-stat i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .question-type-stat .count {
            display: block;
            font-size: 1.25rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .question-type-stat .label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .card {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-radius: 15px;
        }
        
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .btn-lg {
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            border-radius: 25px;
        }
        
        @media (max-width: 768px) {
            .quiz-stats {
                padding: 1rem;
            }
            
            .stat-item {
                padding: 0.5rem;
            }
            
            .stat-value {
                font-size: 1.25rem;
            }
            
            .btn-lg {
                display: block;
                width: 100%;
                margin-bottom: 1rem;
            }
        }
    </style>
</body>
</html>
