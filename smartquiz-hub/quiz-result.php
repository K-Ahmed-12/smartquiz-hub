<?php
require_once 'config/config.php';

// Require login
requireLogin();

$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;
$errors = [];
$attempt = null;
$quiz = null;
$results = [];
$total_score = 0;
$total_possible = 0;

if ($attempt_id <= 0) {
    redirect('quizzes.php');
}

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    
    // Get attempt details
    $stmt = $db->prepare("
        SELECT qa.*, q.title, q.description, q.time_limit, q.total_marks, q.allow_retake,
               c.name as category_name, u.name as user_name
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        LEFT JOIN categories c ON q.category_id = c.id
        JOIN users u ON qa.user_id = u.id
        WHERE qa.id = ? AND qa.user_id = ?
    ");
    $stmt->execute([$attempt_id, $user_id]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) {
        showAlert('Quiz attempt not found.', 'danger');
        redirect('quizzes.php');
    }
    
    // If attempt is still in progress, redirect to quiz interface
    if ($attempt['status'] == 'in_progress') {
        redirect("quiz-interface.php?attempt_id=$attempt_id");
    }
    
    // Get questions and answers for grading
    $stmt = $db->prepare("
        SELECT q.*, ua.answer_text, ua.selected_option_id, ua.is_correct, ua.marks_awarded,
               GROUP_CONCAT(
                   CONCAT(qo.id, ':', qo.option_text, ':', qo.is_correct) 
                   ORDER BY qo.option_order SEPARATOR '|'
               ) as options
        FROM questions q
        LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.attempt_id = ?
        LEFT JOIN question_options qo ON q.id = qo.question_id
        WHERE q.quiz_id = ?
        GROUP BY q.id
        ORDER BY q.order_number, q.id
    ");
    $stmt->execute([$attempt_id, $attempt['quiz_id']]);
    $questions_data = $stmt->fetchAll();
    
    // Process results and calculate scores if not already done
    $needs_grading = false;
    foreach ($questions_data as $question) {
        if ($question['answer_text'] !== null || $question['selected_option_id'] !== null) {
            if ($question['marks_awarded'] === null) {
                $needs_grading = true;
                break;
            }
        }
    }
    
    if ($needs_grading) {
        // Auto-grade the quiz with improved logic
        $total_score = 0;
        
        foreach ($questions_data as $question) {
            $marks_awarded = 0;
            $is_correct = false;
            
            // Only grade if there's an answer
            if ($question['answer_text'] !== null || $question['selected_option_id'] !== null) {
                
                if ($question['question_type'] == 'multiple_choice' && $question['selected_option_id']) {
                    // Use direct database query for more reliable option checking
                    $stmt = $db->prepare("
                        SELECT is_correct, option_text 
                        FROM question_options 
                        WHERE id = ? AND question_id = ?
                    ");
                    $stmt->execute([$question['selected_option_id'], $question['id']]);
                    $selected_option = $stmt->fetch();
                    
                    if ($selected_option) {
                        if ($selected_option['is_correct'] == 1) {
                            $is_correct = true;
                            $marks_awarded = (int)$question['marks'];
                        }
                    }
                    
                } elseif ($question['question_type'] == 'true_false' && $question['answer_text']) {
                    // For true/false, get the correct answer directly from database
                    $stmt = $db->prepare("
                        SELECT option_text 
                        FROM question_options 
                        WHERE question_id = ? AND is_correct = 1
                    ");
                    $stmt->execute([$question['id']]);
                    $correct_option = $stmt->fetch();
                    
                    if ($correct_option) {
                        $correct_answer = strtolower($correct_option['option_text']) == 'true' ? 'true' : 'false';
                        if (strtolower($question['answer_text']) == $correct_answer) {
                            $is_correct = true;
                            $marks_awarded = (int)$question['marks'];
                        }
                    }
                    
                } elseif ($question['question_type'] == 'short_answer' && $question['answer_text']) {
                    // Short answer questions need manual grading, but we can set up for it
                    $marks_awarded = 0; // Will be manually graded later
                }
                
                // Update user answer with grading results
                $stmt = $db->prepare("
                    UPDATE user_answers 
                    SET is_correct = ?, marks_awarded = ?
                    WHERE attempt_id = ? AND question_id = ?
                ");
                $stmt->execute([$is_correct ? 1 : 0, $marks_awarded, $attempt_id, $question['id']]);
            }
            
            $total_score += $marks_awarded;
        }
        
        // Get the correct total marks by summing all question marks
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(marks), 0) as total_marks 
            FROM questions 
            WHERE quiz_id = ?
        ");
        $stmt->execute([$attempt['quiz_id']]);
        $calculated_total_marks = $stmt->fetch()['total_marks'];
        
        // Also update the quiz table to ensure consistency
        $stmt = $db->prepare("
            UPDATE quizzes 
            SET total_marks = ? 
            WHERE id = ?
        ");
        $stmt->execute([$calculated_total_marks, $attempt['quiz_id']]);
        
        // Calculate percentage based on calculated total marks
        $percentage = $calculated_total_marks > 0 ? ($total_score / $calculated_total_marks) * 100 : 0;
        
        // Update quiz attempt with final score and correct total marks
        $stmt = $db->prepare("
            UPDATE quiz_attempts 
            SET score = ?, percentage = ?, total_marks = ?
            WHERE id = ?
        ");
        $stmt->execute([$total_score, $percentage, $calculated_total_marks, $attempt_id]);
        
        $attempt['score'] = $total_score;
        $attempt['percentage'] = $percentage;
        $attempt['total_marks'] = $calculated_total_marks;
    } else {
        $total_score = $attempt['score'];
    }
    
    // Re-fetch questions with updated grading
    $stmt = $db->prepare("
        SELECT q.*, ua.answer_text, ua.selected_option_id, ua.is_correct, ua.marks_awarded,
               GROUP_CONCAT(
                   CONCAT(qo.id, ':', qo.option_text, ':', qo.is_correct) 
                   ORDER BY qo.option_order SEPARATOR '|'
               ) as options
        FROM questions q
        LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.attempt_id = ?
        LEFT JOIN question_options qo ON q.id = qo.question_id
        WHERE q.quiz_id = ?
        GROUP BY q.id
        ORDER BY q.order_number, q.id
    ");
    $stmt->execute([$attempt_id, $attempt['quiz_id']]);
    $results = $stmt->fetchAll();
    
    // Ensure we're using the correct total marks
    $total_possible = $attempt['total_marks'];
    
    // Ensure data consistency
    if ($attempt['total_marks'] != $total_possible) {
        // Fix any inconsistency in total marks
        $stmt = $db->prepare("
            UPDATE quiz_attempts 
            SET total_marks = ? 
            WHERE id = ?
        ");
        $stmt->execute([$total_possible, $attempt_id]);
        $attempt['total_marks'] = $total_possible;
    }
    
    // Get user's rank for this quiz
    $stmt = $db->prepare("
        SELECT COUNT(*) + 1 as user_rank
        FROM quiz_attempts
        WHERE quiz_id = ? AND status = 'completed' AND percentage > ?
    ");
    $stmt->execute([$attempt['quiz_id'], $attempt['percentage']]);
    $rank_data = $stmt->fetch();
    $user_rank = $rank_data['user_rank'];
    
} catch(PDOException $e) {
    $errors[] = 'Database error occurred.';
}

// Calculate performance metrics
$correct_answers = 0;
$total_answered = 0;
$short_answer_count = 0;

foreach ($results as $result) {
    if ($result['answer_text'] !== null || $result['selected_option_id'] !== null) {
        $total_answered++;
        if ($result['is_correct']) {
            $correct_answers++;
        }
        if ($result['question_type'] == 'short_answer') {
            $short_answer_count++;
        }
    }
}

$accuracy = $total_answered > 0 ? ($correct_answers / $total_answered) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - <?php echo htmlspecialchars($attempt['title'] ?? 'Quiz'); ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="quizzes.php">
                    <i class="fas fa-list me-1"></i>All Quizzes
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
        
        <?php if ($attempt): ?>
        <!-- Results Header -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="card result-header-card">
                    <div class="card-body text-center">
                        <div class="result-icon mb-3">
                            <?php if ($attempt['percentage'] >= 80): ?>
                                <i class="fas fa-trophy text-warning fa-4x"></i>
                            <?php elseif ($attempt['percentage'] >= 60): ?>
                                <i class="fas fa-medal text-success fa-4x"></i>
                            <?php else: ?>
                                <i class="fas fa-certificate text-info fa-4x"></i>
                            <?php endif; ?>
                        </div>
                        
                        <h1 class="result-title mb-2">
                            <?php if ($attempt['percentage'] >= 80): ?>
                                Excellent Work!
                            <?php elseif ($attempt['percentage'] >= 60): ?>
                                Good Job!
                            <?php else: ?>
                                Quiz Completed
                            <?php endif; ?>
                        </h1>
                        
                        <h3 class="quiz-title text-muted mb-3"><?php echo htmlspecialchars($attempt['title']); ?></h3>
                        
                        <div class="score-display">
                            <div class="score-circle">
                                <canvas id="scoreChart" width="150" height="150"></canvas>
                                <div class="score-text">
                                    <div class="percentage"><?php echo number_format($attempt['percentage'], 1); ?>%</div>
                                    <div class="fraction"><?php echo $total_score; ?>/<?php echo $total_possible; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Summary -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-percentage fa-2x mb-2"></i>
                        <h4><?php echo number_format($attempt['percentage'], 1); ?>%</h4>
                        <p class="mb-0">Final Score</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4><?php echo $correct_answers; ?>/<?php echo $total_answered; ?></h4>
                        <p class="mb-0">Correct Answers</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h4><?php echo formatDuration($attempt['time_taken']); ?></h4>
                        <p class="mb-0">Time Taken</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card bg-warning text-dark">
                    <div class="card-body text-center">
                        <i class="fas fa-trophy fa-2x mb-2"></i>
                        <h4>#<?php echo $user_rank; ?></h4>
                        <p class="mb-0">Your Rank</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Results -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Detailed Results</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($results as $index => $result): ?>
                            <div class="question-result mb-4">
                                <div class="question-header d-flex justify-content-between align-items-start mb-3">
                                    <h6 class="question-number">Question <?php echo $index + 1; ?></h6>
                                    <div class="question-score">
                                        <?php if ($result['answer_text'] !== null || $result['selected_option_id'] !== null): ?>
                                            <span class="badge bg-<?php echo $result['is_correct'] ? 'success' : 'danger'; ?>">
                                                <?php echo $result['marks_awarded']; ?>/<?php echo $result['marks']; ?> marks
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Not Answered</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="question-text mb-3">
                                    <?php echo nl2br(htmlspecialchars($result['question_text'])); ?>
                                </div>
                                
                                <?php if ($result['question_type'] == 'multiple_choice'): ?>
                                    <?php 
                                    $options = [];
                                    if ($result['options']) {
                                        foreach (explode('|', $result['options']) as $option_data) {
                                            $parts = explode(':', $option_data, 3);
                                            if (count($parts) >= 3) {
                                                $options[] = [
                                                    'id' => $parts[0],
                                                    'text' => $parts[1],
                                                    'is_correct' => $parts[2] == '1'
                                                ];
                                            }
                                        }
                                    }
                                    ?>
                                    <div class="options-review">
                                        <?php foreach ($options as $option): ?>
                                            <div class="option-item <?php 
                                                if ($option['is_correct']) echo 'correct-option';
                                                elseif ($option['id'] == $result['selected_option_id']) echo 'selected-option';
                                            ?>">
                                                <div class="d-flex align-items-center">
                                                    <div class="option-indicator me-3">
                                                        <?php if ($option['is_correct']): ?>
                                                            <i class="fas fa-check-circle text-success"></i>
                                                        <?php elseif ($option['id'] == $result['selected_option_id']): ?>
                                                            <i class="fas fa-times-circle text-danger"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-circle text-muted"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="option-text">
                                                        <?php echo htmlspecialchars($option['text']); ?>
                                                        <?php if ($option['is_correct']): ?>
                                                            <span class="badge bg-success ms-2">Correct Answer</span>
                                                        <?php elseif ($option['id'] == $result['selected_option_id']): ?>
                                                            <span class="badge bg-danger ms-2">Your Answer</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                <?php elseif ($result['question_type'] == 'true_false'): ?>
                                    <div class="true-false-review">
                                        <?php
                                        $correct_answer = null;
                                        if ($result['options']) {
                                            foreach (explode('|', $result['options']) as $option_data) {
                                                $parts = explode(':', $option_data, 3);
                                                if (count($parts) >= 3 && $parts[2] == '1') {
                                                    $correct_answer = strtolower($parts[1]) == 'true' ? 'true' : 'false';
                                                    break;
                                                }
                                            }
                                        }
                                        ?>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="answer-option <?php echo $result['answer_text'] == 'true' ? 'selected' : ''; ?> <?php echo $correct_answer == 'true' ? 'correct' : ''; ?>">
                                                    <i class="fas fa-check text-success me-2"></i>True
                                                    <?php if ($correct_answer == 'true'): ?>
                                                        <span class="badge bg-success ms-2">Correct</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="answer-option <?php echo $result['answer_text'] == 'false' ? 'selected' : ''; ?> <?php echo $correct_answer == 'false' ? 'correct' : ''; ?>">
                                                    <i class="fas fa-times text-danger me-2"></i>False
                                                    <?php if ($correct_answer == 'false'): ?>
                                                        <span class="badge bg-success ms-2">Correct</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($result['answer_text']): ?>
                                            <div class="mt-2">
                                                <strong>Your Answer:</strong> <?php echo ucfirst($result['answer_text']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                <?php elseif ($result['question_type'] == 'short_answer'): ?>
                                    <div class="short-answer-review">
                                        <div class="your-answer">
                                            <strong>Your Answer:</strong>
                                            <div class="answer-text">
                                                <?php if ($result['answer_text']): ?>
                                                    <?php echo nl2br(htmlspecialchars($result['answer_text'])); ?>
                                                <?php else: ?>
                                                    <em class="text-muted">No answer provided</em>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($result['marks_awarded'] === null && $result['answer_text']): ?>
                                            <div class="alert alert-info mt-2">
                                                <i class="fas fa-info-circle me-2"></i>
                                                This answer requires manual grading by an instructor.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($index < count($results) - 1): ?>
                                <hr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Performance Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Performance Breakdown</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="performanceChart" width="300" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($attempt['allow_retake']): ?>
                                <a href="take-quiz.php?id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-redo me-2"></i>Retake Quiz
                                </a>
                            <?php endif; ?>
                            <a href="quizzes.php" class="btn btn-outline-primary">
                                <i class="fas fa-list me-2"></i>Browse More Quizzes
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                            </a>
                            <button onclick="window.print()" class="btn btn-outline-info">
                                <i class="fas fa-print me-2"></i>Print Results
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Quiz Info -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Quiz Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="quiz-info-item">
                            <strong>Category:</strong> <?php echo htmlspecialchars($attempt['category_name']); ?>
                        </div>
                        <div class="quiz-info-item">
                            <strong>Time Limit:</strong> <?php echo $attempt['time_limit']; ?> minutes
                        </div>
                        <div class="quiz-info-item">
                            <strong>Total Questions:</strong> <?php echo count($results); ?>
                        </div>
                        <div class="quiz-info-item">
                            <strong>Completed:</strong> <?php echo date('M j, Y g:i A', strtotime($attempt['end_time'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            initializeScoreChart();
            initializePerformanceChart();
        });
        
        function initializeScoreChart() {
            const ctx = document.getElementById('scoreChart').getContext('2d');
            const percentage = <?php echo $attempt['percentage']; ?>;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [percentage, 100 - percentage],
                        backgroundColor: [
                            percentage >= 80 ? '#28a745' : percentage >= 60 ? '#ffc107' : '#dc3545',
                            '#e9ecef'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '75%'
                }
            });
        }
        
        function initializePerformanceChart() {
            const ctx = document.getElementById('performanceChart').getContext('2d');
            
            const correctAnswers = <?php echo $correct_answers; ?>;
            const incorrectAnswers = <?php echo $total_answered - $correct_answers; ?>;
            const unanswered = <?php echo count($results) - $total_answered; ?>;
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Correct', 'Incorrect', 'Unanswered'],
                    datasets: [{
                        data: [correctAnswers, incorrectAnswers, unanswered],
                        backgroundColor: [
                            '#28a745',
                            '#dc3545',
                            '#6c757d'
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
    </script>
    
    <style>
        .result-header-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .score-display {
            position: relative;
            display: inline-block;
        }
        
        .score-circle {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto;
        }
        
        .score-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .percentage {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .fraction {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .stat-card {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        
        .question-result {
            border-left: 4px solid #dee2e6;
            padding-left: 1rem;
        }
        
        .option-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .option-item.correct-option {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .option-item.selected-option:not(.correct-option) {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        
        .answer-option {
            padding: 0.75rem;
            border-radius: 8px;
            background: #f8f9fa;
            text-align: center;
        }
        
        .answer-option.correct {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .answer-option.selected:not(.correct) {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        
        .answer-text {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            border-left: 4px solid #007bff;
        }
        
        .quiz-info-item {
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .quiz-info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        @media print {
            .navbar, .card-header, .btn, .alert {
                display: none !important;
            }
            
            .container {
                max-width: 100% !important;
            }
        }
        
        @media (max-width: 768px) {
            .score-circle {
                width: 120px;
                height: 120px;
            }
            
            .percentage {
                font-size: 1.5rem;
            }
            
            .stat-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</body>
</html>
