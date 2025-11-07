<?php
require_once 'config/config.php';

// Require login
requireLogin();

$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;
$errors = [];
$attempt = null;
$quiz = null;
$questions = [];
$user_answers = [];

if ($attempt_id <= 0) {
    redirect('quizzes.php');
}

try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    
    // Get attempt details
    $stmt = $db->prepare("
        SELECT qa.*, q.title, q.description, q.time_limit, q.total_marks, q.randomize_questions,
               c.name as category_name
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        LEFT JOIN categories c ON q.category_id = c.id
        WHERE qa.id = ? AND qa.user_id = ? AND qa.status = 'in_progress'
    ");
    $stmt->execute([$attempt_id, $user_id]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) {
        showAlert('Quiz attempt not found or already completed.', 'danger');
        redirect('quizzes.php');
    }
    
    // Check if time has expired
    $start_time = strtotime($attempt['start_time']);
    $time_limit_seconds = $attempt['time_limit'] * 60;
    $current_time = time();
    $elapsed_time = $current_time - $start_time;
    
    if ($elapsed_time >= $time_limit_seconds) {
        // Auto-submit quiz if time expired
        $stmt = $db->prepare("
            UPDATE quiz_attempts 
            SET status = 'completed', end_time = NOW(), time_taken = ?
            WHERE id = ?
        ");
        $stmt->execute([$time_limit_seconds, $attempt_id]);
        
        showAlert('Time has expired. Your quiz has been automatically submitted.', 'warning');
        redirect("quiz-result.php?attempt_id=$attempt_id");
    }
    
    // Get questions
    $order_clause = $attempt['randomize_questions'] ? 'ORDER BY RAND()' : 'ORDER BY order_number, id';
    $stmt = $db->prepare("
        SELECT q.*, 
               GROUP_CONCAT(
                   CONCAT(qo.id, ':', qo.option_text, ':', qo.is_correct) 
                   ORDER BY qo.option_order SEPARATOR '|'
               ) as options
        FROM questions q
        LEFT JOIN question_options qo ON q.id = qo.question_id
        WHERE q.quiz_id = ?
        GROUP BY q.id
        $order_clause
    ");
    $stmt->execute([$attempt['quiz_id']]);
    $questions = $stmt->fetchAll();
    
    // Get existing user answers
    $stmt = $db->prepare("
        SELECT question_id, answer_text, selected_option_id
        FROM user_answers
        WHERE attempt_id = ?
    ");
    $stmt->execute([$attempt_id]);
    $existing_answers = $stmt->fetchAll();
    
    foreach ($existing_answers as $answer) {
        $user_answers[$answer['question_id']] = [
            'answer_text' => $answer['answer_text'],
            'selected_option_id' => $answer['selected_option_id']
        ];
    }
    
    // Handle AJAX requests for auto-save
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        
        $question_id = (int)$_POST['question_id'];
        $answer_text = $_POST['answer_text'] ?? null;
        $selected_option_id = isset($_POST['selected_option_id']) ? (int)$_POST['selected_option_id'] : null;
        
        try {
            // Delete existing answer
            $stmt = $db->prepare("DELETE FROM user_answers WHERE attempt_id = ? AND question_id = ?");
            $stmt->execute([$attempt_id, $question_id]);
            
            // Insert new answer if provided
            if ($answer_text || $selected_option_id) {
                $stmt = $db->prepare("
                    INSERT INTO user_answers (attempt_id, question_id, answer_text, selected_option_id)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$attempt_id, $question_id, $answer_text, $selected_option_id]);
            }
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to save answer']);
        }
        exit;
    }
    
    // Handle quiz submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_quiz'])) {
        // Save any final answers before submission
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'question_') === 0) {
                $question_id = str_replace('question_', '', $key);
                
                if (is_numeric($question_id)) {
                    // Delete existing answer
                    $stmt = $db->prepare("DELETE FROM user_answers WHERE attempt_id = ? AND question_id = ?");
                    $stmt->execute([$attempt_id, $question_id]);
                    
                    // Insert new answer
                    if (!empty($value)) {
                        if ($value === 'true' || $value === 'false') {
                            // True/false answer
                            $stmt = $db->prepare("
                                INSERT INTO user_answers (attempt_id, question_id, answer_text)
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$attempt_id, $question_id, $value]);
                        } elseif (is_numeric($value)) {
                            // Multiple choice answer (option ID)
                            $stmt = $db->prepare("
                                INSERT INTO user_answers (attempt_id, question_id, selected_option_id)
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$attempt_id, $question_id, $value]);
                        } else {
                            // Text answer
                            $stmt = $db->prepare("
                                INSERT INTO user_answers (attempt_id, question_id, answer_text)
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$attempt_id, $question_id, $value]);
                        }
                    }
                }
            }
        }
        
        // Calculate final time taken
        $final_time_taken = time() - $start_time;
        
        // IMMEDIATELY GRADE THE QUIZ BEFORE MARKING AS COMPLETED
        $total_score = 0;
        
        // Get all questions for this quiz
        $stmt = $db->prepare("
            SELECT q.id, q.marks, q.question_type
            FROM questions q
            WHERE q.quiz_id = ?
            ORDER BY q.order_number
        ");
        $stmt->execute([$quiz['id']]);
        $all_questions = $stmt->fetchAll();
        
        foreach ($all_questions as $question) {
            // Get user's answer for this question
            $stmt = $db->prepare("
                SELECT selected_option_id, answer_text
                FROM user_answers
                WHERE attempt_id = ? AND question_id = ?
            ");
            $stmt->execute([$attempt_id, $question['id']]);
            $user_answer = $stmt->fetch();
            
            $marks_awarded = 0;
            $is_correct = false;
            
            if ($user_answer) {
                if ($question['question_type'] == 'multiple_choice' && $user_answer['selected_option_id']) {
                    // Check if selected option is correct
                    $stmt = $db->prepare("
                        SELECT is_correct
                        FROM question_options
                        WHERE id = ? AND question_id = ?
                    ");
                    $stmt->execute([$user_answer['selected_option_id'], $question['id']]);
                    $option = $stmt->fetch();
                    
                    if ($option && $option['is_correct'] == 1) {
                        $is_correct = true;
                        $marks_awarded = $question['marks'];
                    }
                } elseif ($question['question_type'] == 'true_false' && $user_answer['answer_text']) {
                    // Check true/false answer
                    $stmt = $db->prepare("
                        SELECT option_text
                        FROM question_options
                        WHERE question_id = ? AND is_correct = 1
                    ");
                    $stmt->execute([$question['id']]);
                    $correct_option = $stmt->fetch();
                    
                    if ($correct_option) {
                        $correct_answer = strtolower($correct_option['option_text']) == 'true' ? 'true' : 'false';
                        if (strtolower($user_answer['answer_text']) == $correct_answer) {
                            $is_correct = true;
                            $marks_awarded = $question['marks'];
                        }
                    }
                }
                
                // Update user answer with grading
                $stmt = $db->prepare("
                    UPDATE user_answers
                    SET is_correct = ?, marks_awarded = ?
                    WHERE attempt_id = ? AND question_id = ?
                ");
                $stmt->execute([$is_correct ? 1 : 0, $marks_awarded, $attempt_id, $question['id']]);
                
                $total_score += $marks_awarded;
            }
        }
        
        // Calculate percentage
        $percentage = $quiz['total_marks'] > 0 ? ($total_score / $quiz['total_marks']) * 100 : 0;
        
        // Update attempt status WITH CALCULATED SCORE
        $stmt = $db->prepare("
            UPDATE quiz_attempts 
            SET status = 'completed', end_time = NOW(), time_taken = ?, score = ?, percentage = ?
            WHERE id = ?
        ");
        $stmt->execute([$final_time_taken, $total_score, $percentage, $attempt_id]);
        
        // Redirect to processing page instead of direct results
        redirect("quiz-processing.php?attempt_id=$attempt_id");
    }
    
} catch(PDOException $e) {
    $errors[] = 'Database error occurred.';
}

// Calculate remaining time
$remaining_time = max(0, $time_limit_seconds - $elapsed_time);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($attempt['title'] ?? 'Quiz'); ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="quiz-taking">
    <!-- Fixed Header -->
    <div class="quiz-header fixed-top bg-white shadow-sm">
        <div class="container">
            <div class="row align-items-center py-3">
                <div class="col-md-4">
                    <h5 class="mb-0 text-truncate"><?php echo htmlspecialchars($attempt['title']); ?></h5>
                    <small class="text-muted"><?php echo htmlspecialchars($attempt['category_name']); ?></small>
                </div>
                <div class="col-md-4 text-center">
                    <div class="timer-display" id="timerDisplay">
                        <i class="fas fa-clock text-warning me-2"></i>
                        <span id="timeRemaining"><?php echo formatDuration($remaining_time); ?></span>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="progress-info">
                        <span class="badge bg-primary" id="progressBadge">Question 1 of <?php echo count($questions); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container quiz-container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Question Navigation -->
        <div class="question-nav-container mb-4">
            <div class="question-nav">
                <?php foreach ($questions as $index => $question): ?>
                    <button type="button" class="question-nav-btn" data-question="<?php echo $index; ?>">
                        <?php echo $index + 1; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Questions Container -->
        <div class="questions-container">
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-slide" data-question="<?php echo $index; ?>" style="<?php echo $index === 0 ? '' : 'display: none;'; ?>">
                    <div class="card question-card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Question <?php echo $index + 1; ?> of <?php echo count($questions); ?></h6>
                                <span class="badge bg-success"><?php echo $question['marks']; ?> mark<?php echo $question['marks'] > 1 ? 's' : ''; ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="question-text mb-4">
                                <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                                
                                <?php if ($question['question_image']): ?>
                                    <div class="question-image mt-3">
                                        <img src="<?php echo htmlspecialchars($question['question_image']); ?>" 
                                             alt="Question Image" class="img-fluid rounded">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="answer-section">
                                <?php if ($question['question_type'] == 'multiple_choice'): ?>
                                    <?php 
                                    $options = [];
                                    if ($question['options']) {
                                        foreach (explode('|', $question['options']) as $option_data) {
                                            $parts = explode(':', $option_data, 3);
                                            if (count($parts) >= 3) {
                                                $options[] = [
                                                    'id' => $parts[0],
                                                    'text' => $parts[1],
                                                    'is_correct' => $parts[2]
                                                ];
                                            }
                                        }
                                    }
                                    ?>
                                    <div class="options-container">
                                        <?php foreach ($options as $option): ?>
                                            <div class="form-check option-item">
                                                <input class="form-check-input" type="radio" 
                                                       name="question_<?php echo $question['id']; ?>" 
                                                       id="option_<?php echo $option['id']; ?>"
                                                       value="<?php echo $option['id']; ?>"
                                                       data-question-id="<?php echo $question['id']; ?>"
                                                       <?php echo (isset($user_answers[$question['id']]) && $user_answers[$question['id']]['selected_option_id'] == $option['id']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="option_<?php echo $option['id']; ?>">
                                                    <?php echo htmlspecialchars($option['text']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                <?php elseif ($question['question_type'] == 'true_false'): ?>
                                    <div class="options-container">
                                        <div class="form-check option-item">
                                            <input class="form-check-input" type="radio" 
                                                   name="question_<?php echo $question['id']; ?>" 
                                                   id="true_<?php echo $question['id']; ?>"
                                                   value="true" data-question-id="<?php echo $question['id']; ?>"
                                                   <?php echo (isset($user_answers[$question['id']]) && $user_answers[$question['id']]['answer_text'] == 'true') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="true_<?php echo $question['id']; ?>">
                                                <i class="fas fa-check text-success me-2"></i>True
                                            </label>
                                        </div>
                                        <div class="form-check option-item">
                                            <input class="form-check-input" type="radio" 
                                                   name="question_<?php echo $question['id']; ?>" 
                                                   id="false_<?php echo $question['id']; ?>"
                                                   value="false" data-question-id="<?php echo $question['id']; ?>"
                                                   <?php echo (isset($user_answers[$question['id']]) && $user_answers[$question['id']]['answer_text'] == 'false') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="false_<?php echo $question['id']; ?>">
                                                <i class="fas fa-times text-danger me-2"></i>False
                                            </label>
                                        </div>
                                    </div>
                                    
                                <?php elseif ($question['question_type'] == 'short_answer'): ?>
                                    <div class="short-answer-container">
                                        <textarea class="form-control" 
                                                  name="question_<?php echo $question['id']; ?>"
                                                  data-question-id="<?php echo $question['id']; ?>"
                                                  rows="4" 
                                                  placeholder="Type your answer here..."><?php echo htmlspecialchars($user_answers[$question['id']]['answer_text'] ?? ''); ?></textarea>
                                        <small class="form-text text-muted">Provide a clear and concise answer.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Navigation Controls -->
        <div class="quiz-controls mt-4">
            <div class="row">
                <div class="col-6">
                    <button type="button" class="btn btn-outline-secondary" id="prevBtn" disabled>
                        <i class="fas fa-chevron-left me-2"></i>Previous
                    </button>
                </div>
                <div class="col-6 text-end">
                    <button type="button" class="btn btn-primary" id="nextBtn">
                        Next <i class="fas fa-chevron-right ms-2"></i>
                    </button>
                    <button type="button" class="btn btn-success" id="submitBtn" style="display: none;" data-bs-toggle="modal" data-bs-target="#submitModal">
                        <i class="fas fa-check me-2"></i>Submit Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Confirmation Modal -->
    <div class="modal fade" id="submitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Quiz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to submit your quiz? You cannot change your answers after submission.</p>
                    <div class="alert alert-info">
                        <strong>Summary:</strong>
                        <ul class="mb-0" id="submissionSummary">
                            <li>Answered: <span id="answeredCount">0</span> questions</li>
                            <li>Unanswered: <span id="unansweredCount">0</span> questions</li>
                            <li>Time remaining: <span id="timeRemainingSubmit"></span></li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="submit_quiz" value="1">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Submit Quiz
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Quiz interface variables
        let currentQuestion = 0;
        let totalQuestions = <?php echo count($questions); ?>;
        let timeRemaining = <?php echo $remaining_time; ?>;
        let autoSaveInterval;
        let timerInterval;
        
        // Initialize quiz interface
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Quiz interface initializing...');
            console.log('Time remaining:', timeRemaining, 'seconds');
            console.log('Total questions:', totalQuestions);
            
            initializeQuizTimer();
            initializeAutoSave();
            initializeNavigation();
            updateQuestionNavigation();
            
            // Initial timer display update
            updateTimerDisplay();
            
            console.log('Quiz interface initialized successfully');
        });
        
        // Timer functionality
        function initializeQuizTimer() {
            if (timeRemaining <= 0) {
                autoSubmitQuiz();
                return;
            }
            
            timerInterval = setInterval(function() {
                timeRemaining--;
                
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    autoSubmitQuiz();
                    return;
                }
                
                updateTimerDisplay();
                
                // Warning when 5 minutes left
                if (timeRemaining === 300) {
                    showTimerWarning('Warning: Only 5 minutes remaining!');
                }
                
                // Warning when 1 minute left
                if (timeRemaining === 60) {
                    showTimerWarning('Warning: Only 1 minute remaining!');
                }
            }, 1000);
        }
        
        function updateTimerDisplay() {
            const timeElement = document.getElementById('timeRemaining');
            const timerDisplay = document.getElementById('timerDisplay');
            
            if (timeElement) {
                timeElement.textContent = formatTime(timeRemaining);
            }
            
            // Change timer color when time is running low
            if (timerDisplay) {
                if (timeRemaining <= 60) { // 1 minute
                    timerDisplay.className = 'timer-display text-danger fw-bold';
                } else if (timeRemaining <= 300) { // 5 minutes
                    timerDisplay.className = 'timer-display text-warning fw-bold';
                } else {
                    timerDisplay.className = 'timer-display';
                }
            }
        }
        
        function showTimerWarning(message) {
            // Create a toast notification instead of alert
            const toast = document.createElement('div');
            toast.className = 'alert alert-warning alert-dismissible fade show position-fixed';
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                <i class="fas fa-clock me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(toast);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 5000);
        }
        
        function autoSubmitQuiz() {
            // Save all current answers before auto-submit
            const formElements = document.querySelectorAll('input[type="radio"]:checked, textarea');
            
            // Create form with all current answers
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            
            // Add submit flag
            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'submit_quiz';
            submitInput.value = '1';
            form.appendChild(submitInput);
            
            // Add all current answers to the form
            formElements.forEach(element => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = element.name;
                input.value = element.value;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            
            // Show processing message instead of alert
            document.body.innerHTML = `
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 9999;">
                    <div style="background: white; padding: 40px; border-radius: 15px; text-align: center; max-width: 400px;">
                        <div style="width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #007bff; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                        <h3 style="color: #333; margin-bottom: 10px;">Time's Up!</h3>
                        <p style="color: #666; margin-bottom: 0;">Submitting your quiz automatically...</p>
                    </div>
                </div>
                <style>
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>
            `;
            
            setTimeout(() => {
                form.submit();
            }, 2000);
        }
        
        // Auto-save functionality
        function initializeAutoSave() {
            autoSaveInterval = setInterval(autoSaveAnswers, 30000); // Every 30 seconds
            
            // Save on input change
            document.addEventListener('change', function(e) {
                if (e.target.matches('input[type="radio"], textarea')) {
                    saveAnswer(e.target);
                }
            });
            
            // Save textarea on input (with debounce)
            let textareaTimeout;
            document.addEventListener('input', function(e) {
                if (e.target.matches('textarea')) {
                    clearTimeout(textareaTimeout);
                    textareaTimeout = setTimeout(() => saveAnswer(e.target), 2000);
                }
            });
        }
        
        // Save individual answer
        function saveAnswer(element) {
            const questionId = element.getAttribute('data-question-id');
            let answerText = null;
            let selectedOptionId = null;
            
            if (!questionId) {
                console.error('No question ID found for element:', element);
                return;
            }
            
            if (element.type === 'radio') {
                if (element.checked) {
                    if (element.value === 'true' || element.value === 'false') {
                        answerText = element.value;
                    } else {
                        selectedOptionId = element.value;
                    }
                } else {
                    return; // Don't save unchecked radio buttons
                }
            } else if (element.tagName === 'TEXTAREA') {
                answerText = element.value.trim();
                if (!answerText) {
                    return; // Don't save empty textarea
                }
            }
            
            // Show saving indicator
            showSavingIndicator(true);
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('question_id', questionId);
            formData.append('answer_text', answerText || '');
            formData.append('selected_option_id', selectedOptionId || '');
            
            fetch('quiz-interface.php?attempt_id=<?php echo $attempt_id; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                showSavingIndicator(false);
                if (data.success) {
                    updateQuestionNavigation();
                    showSaveSuccess();
                } else {
                    console.error('Save failed:', data.error);
                    showSaveError();
                }
            })
            .catch(error => {
                showSavingIndicator(false);
                console.error('Auto-save error:', error);
                showSaveError();
            });
        }
        
        function showSavingIndicator(show) {
            let indicator = document.getElementById('savingIndicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'savingIndicator';
                indicator.className = 'position-fixed';
                indicator.style.cssText = 'top: 20px; left: 20px; z-index: 9999;';
                document.body.appendChild(indicator);
            }
            
            if (show) {
                indicator.innerHTML = '<div class="badge bg-info"><i class="fas fa-spinner fa-spin me-1"></i>Saving...</div>';
                indicator.style.display = 'block';
            } else {
                indicator.style.display = 'none';
            }
        }
        
        function showSaveSuccess() {
            const indicator = document.getElementById('savingIndicator');
            if (indicator) {
                indicator.innerHTML = '<div class="badge bg-success"><i class="fas fa-check me-1"></i>Saved</div>';
                indicator.style.display = 'block';
                setTimeout(() => {
                    indicator.style.display = 'none';
                }, 2000);
            }
        }
        
        function showSaveError() {
            const indicator = document.getElementById('savingIndicator');
            if (indicator) {
                indicator.innerHTML = '<div class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Save Failed</div>';
                indicator.style.display = 'block';
                setTimeout(() => {
                    indicator.style.display = 'none';
                }, 3000);
            }
        }
        
        // Auto-save all answers
        function autoSaveAnswers() {
            const inputs = document.querySelectorAll('input[type="radio"]:checked, textarea');
            inputs.forEach(input => {
                if (input.value) {
                    saveAnswer(input);
                }
            });
        }
        
        // Navigation functionality
        function initializeNavigation() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtn = document.getElementById('submitBtn');
            
            prevBtn.addEventListener('click', () => navigateToQuestion(currentQuestion - 1));
            nextBtn.addEventListener('click', () => navigateToQuestion(currentQuestion + 1));
            
            // Question navigation buttons
            document.querySelectorAll('.question-nav-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const questionIndex = parseInt(btn.getAttribute('data-question'));
                    navigateToQuestion(questionIndex);
                });
            });
        }
        
        function navigateToQuestion(index) {
            if (index < 0 || index >= totalQuestions) return;
            
            // Hide all questions first
            document.querySelectorAll('.question-slide').forEach(slide => {
                slide.style.display = 'none';
            });
            
            // Show new question
            currentQuestion = index;
            const targetQuestion = document.querySelector(`.question-slide[data-question="${currentQuestion}"]`);
            if (targetQuestion) {
                targetQuestion.style.display = 'block';
            }
            
            // Update navigation buttons
            document.getElementById('prevBtn').disabled = currentQuestion === 0;
            
            if (currentQuestion === totalQuestions - 1) {
                document.getElementById('nextBtn').style.display = 'none';
                document.getElementById('submitBtn').style.display = 'inline-block';
            } else {
                document.getElementById('nextBtn').style.display = 'inline-block';
                document.getElementById('submitBtn').style.display = 'none';
            }
            
            // Update progress
            document.getElementById('progressBadge').textContent = `Question ${currentQuestion + 1} of ${totalQuestions}`;
            
            // Update question navigation buttons
            updateQuestionNavigation();
            
            // Scroll to top of question
            document.querySelector('.questions-container').scrollIntoView({ behavior: 'smooth' });
        }
        
        function updateQuestionNavigation() {
            document.querySelectorAll('.question-nav-btn').forEach((btn, index) => {
                const questionSlide = document.querySelector(`.question-slide[data-question="${index}"]`);
                
                btn.className = 'question-nav-btn';
                
                if (index === currentQuestion) {
                    btn.classList.add('active');
                } else {
                    // Check if question is answered
                    let hasAnswer = false;
                    
                    if (questionSlide) {
                        const radioAnswer = questionSlide.querySelector('input[type="radio"]:checked');
                        const textareaAnswer = questionSlide.querySelector('textarea');
                        
                        if (radioAnswer || (textareaAnswer && textareaAnswer.value.trim())) {
                            hasAnswer = true;
                        }
                    }
                    
                    if (hasAnswer) {
                        btn.classList.add('answered');
                    }
                }
            });
            
            // Update submission summary
            updateSubmissionSummary();
        }
        
        function updateSubmissionSummary() {
            let answeredCount = 0;
            
            document.querySelectorAll('.question-slide').forEach(slide => {
                const hasRadioAnswer = slide.querySelector('input[type="radio"]:checked');
                const textareaAnswer = slide.querySelector('textarea');
                const hasTextAnswer = textareaAnswer && textareaAnswer.value.trim();
                
                if (hasRadioAnswer || hasTextAnswer) {
                    answeredCount++;
                }
            });
            
            const answeredCountElement = document.getElementById('answeredCount');
            const unansweredCountElement = document.getElementById('unansweredCount');
            const timeRemainingSubmitElement = document.getElementById('timeRemainingSubmit');
            
            if (answeredCountElement) answeredCountElement.textContent = answeredCount;
            if (unansweredCountElement) unansweredCountElement.textContent = totalQuestions - answeredCount;
            if (timeRemainingSubmitElement) timeRemainingSubmitElement.textContent = formatTime(timeRemaining);
        }
        
        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            if (hours > 0) {
                return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            } else {
                return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }
        }
        
        // Prevent accidental page leave
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = '';
        });
    </script>
    
    <style>
        body {
            padding-top: 100px;
            background-color: #f8f9fa;
        }
        
        .quiz-header {
            border-bottom: 1px solid #dee2e6;
        }
        
        .timer-display {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .quiz-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .question-nav-container {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .question-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .question-nav-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #dee2e6;
            background: white;
            border-radius: 50%;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .question-nav-btn:hover {
            border-color: #007bff;
            background: #e3f2fd;
        }
        
        .question-nav-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .question-nav-btn.answered {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        
        .question-card {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        
        .question-text {
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .option-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .option-item:hover {
            background: #e9ecef;
            border-color: #007bff;
        }
        
        .option-item:has(input:checked) {
            background: #e3f2fd;
            border-color: #007bff;
        }
        
        .form-check-input {
            margin-top: 0.25rem;
        }
        
        .form-check-label {
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
        }
        
        .short-answer-container textarea {
            border-radius: 10px;
            border: 2px solid #dee2e6;
            transition: border-color 0.3s ease;
        }
        
        .short-answer-container textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        
        .quiz-controls {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 120px;
            }
            
            .quiz-header .row > div {
                text-align: center !important;
                margin-bottom: 0.5rem;
            }
            
            .question-nav-btn {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
            
            .quiz-controls .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</body>
</html>
