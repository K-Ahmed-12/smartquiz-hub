<?php
require_once '../config/config.php';

// Require admin access
requireAdmin();

$errors = [];
$success = '';

// Get quiz ID from URL
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = sanitizeInput($_POST['question_text']);
    $question_type = sanitizeInput($_POST['question_type']);
    $marks = (int)$_POST['marks'];
    $quiz_id = (int)$_POST['quiz_id'];
    
    // Validation
    if (empty($question_text)) {
        $errors[] = 'Question text is required';
    }
    
    if (!in_array($question_type, ['multiple_choice', 'true_false', 'short_answer', 'fill_in_blank'])) {
        $errors[] = 'Invalid question type';
    }
    
    if ($marks <= 0) {
        $errors[] = 'Marks must be greater than 0';
    }
    
    if ($quiz_id <= 0) {
        $errors[] = 'Invalid quiz ID';
    }
    
    // Validate options for multiple choice and true/false
    if ($question_type == 'multiple_choice') {
        $options = $_POST['options'] ?? [];
        $correct_options = $_POST['correct_options'] ?? [];
        
        if (count($options) < 2) {
            $errors[] = 'Multiple choice questions must have at least 2 options';
        }
        
        if (empty($correct_options)) {
            $errors[] = 'Please select at least one correct answer';
        }
    }
    
    if ($question_type == 'true_false') {
        $correct_answer = $_POST['true_false_answer'] ?? '';
        if (!in_array($correct_answer, ['true', 'false'])) {
            $errors[] = 'Please select the correct answer for true/false question';
        }
    }
    
    if ($question_type == 'short_answer' || $question_type == 'fill_in_blank') {
        $correct_answer = sanitizeInput($_POST['correct_answer'] ?? '');
        if (empty($correct_answer)) {
            $errors[] = 'Please provide the correct answer';
        }
    }
    
    // Create question if no errors
    if (empty($errors)) {
        try {
            $db = getDB();
            
            // Get next order number
            $stmt = $db->prepare("SELECT COALESCE(MAX(order_number), 0) + 1 as next_order FROM questions WHERE quiz_id = ?");
            $stmt->execute([$quiz_id]);
            $next_order = $stmt->fetch()['next_order'];
            
            // Insert question
            $stmt = $db->prepare("
                INSERT INTO questions (quiz_id, question_text, question_type, marks, order_number)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$quiz_id, $question_text, $question_type, $marks, $next_order]);
            $question_id = $db->lastInsertId();
            
            // Insert options based on question type
            if ($question_type == 'multiple_choice') {
                $options = $_POST['options'];
                $correct_options = $_POST['correct_options'] ?? [];
                
                for ($i = 0; $i < count($options); $i++) {
                    if (!empty($options[$i])) {
                        $is_correct = in_array($i, $correct_options) ? 1 : 0;
                        $stmt = $db->prepare("
                            INSERT INTO question_options (question_id, option_text, is_correct, option_order)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$question_id, $options[$i], $is_correct, $i + 1]);
                    }
                }
            } elseif ($question_type == 'true_false') {
                $correct_answer = $_POST['true_false_answer'];
                
                // Add True option
                $stmt = $db->prepare("
                    INSERT INTO question_options (question_id, option_text, is_correct, option_order)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$question_id, 'True', ($correct_answer == 'true' ? 1 : 0), 1]);
                
                // Add False option
                $stmt->execute([$question_id, 'False', ($correct_answer == 'false' ? 1 : 0), 2]);
                
            } elseif ($question_type == 'short_answer' || $question_type == 'fill_in_blank') {
                $correct_answer = $_POST['correct_answer'];
                
                // Store correct answer as an option
                $stmt = $db->prepare("
                    INSERT INTO question_options (question_id, option_text, is_correct, option_order)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$question_id, $correct_answer, 1, 1]);
            }
            
            // Update quiz total marks
            $stmt = $db->prepare("
                UPDATE quizzes 
                SET total_marks = (SELECT SUM(marks) FROM questions WHERE quiz_id = ?)
                WHERE id = ?
            ");
            $stmt->execute([$quiz_id, $quiz_id]);
            
            showAlert('Question added successfully!', 'success');
            
            // Redirect to add another question or back to quiz
            if (isset($_POST['add_another'])) {
                redirect("question-create.php?quiz_id=$quiz_id");
            } else {
                redirect("quiz-edit.php?id=$quiz_id");
            }
            
        } catch(PDOException $e) {
            $errors[] = 'Failed to create question. Please try again.';
        }
    }
}

// Get quiz details
try {
    $db = getDB();
    
    if ($quiz_id > 0) {
        $stmt = $db->prepare("SELECT * FROM quizzes WHERE id = ?");
        $stmt->execute([$quiz_id]);
        $quiz = $stmt->fetch();
        
        if (!$quiz) {
            $errors[] = 'Quiz not found.';
        }
    } else {
        $errors[] = 'No quiz ID provided.';
    }
    
} catch(PDOException $e) {
    $errors[] = 'Failed to load quiz details.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question - Admin Panel - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                        <a class="nav-link active" href="#">
                            <i class="fas fa-question-circle me-1"></i>Add Question
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../dashboard.php">User Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0">Add Question</h1>
                        <?php if (isset($quiz)): ?>
                            <p class="text-muted mb-0">Quiz: <?php echo htmlspecialchars($quiz['title']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="quizzes.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Quizzes
                        </a>
                    </div>
                </div>

                <!-- Display alerts -->
                <?php displayAlert(); ?>

                <!-- Display errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (isset($quiz)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>Create New Question
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="questionForm">
                            <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                            
                            <!-- Question Text -->
                            <div class="mb-3">
                                <label for="question_text" class="form-label">
                                    <i class="fas fa-question me-1"></i>Question Text *
                                </label>
                                <textarea class="form-control" id="question_text" name="question_text" rows="3" 
                                          placeholder="Enter your question here..." required></textarea>
                            </div>

                            <!-- Question Type -->
                            <div class="mb-3">
                                <label for="question_type" class="form-label">
                                    <i class="fas fa-list me-1"></i>Question Type *
                                </label>
                                <select class="form-select" id="question_type" name="question_type" required>
                                    <option value="">Select question type...</option>
                                    <option value="multiple_choice">Multiple Choice</option>
                                    <option value="true_false">True/False</option>
                                    <option value="short_answer">Short Answer</option>
                                    <option value="fill_in_blank">Fill in the Blank</option>
                                </select>
                            </div>

                            <!-- Marks -->
                            <div class="mb-3">
                                <label for="marks" class="form-label">
                                    <i class="fas fa-star me-1"></i>Marks *
                                </label>
                                <input type="number" class="form-control" id="marks" name="marks" 
                                       value="1" min="1" max="100" required>
                            </div>

                            <!-- Multiple Choice Options -->
                            <div id="multiple_choice_options" class="question-type-options" style="display: none;">
                                <h6><i class="fas fa-list-ul me-1"></i>Answer Options</h6>
                                <div id="options_container">
                                    <div class="option-group mb-2">
                                        <div class="input-group">
                                            <div class="input-group-text">
                                                <input type="checkbox" name="correct_options[]" value="0">
                                            </div>
                                            <input type="text" class="form-control" name="options[]" placeholder="Option 1">
                                            <button type="button" class="btn btn-outline-danger remove-option">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="option-group mb-2">
                                        <div class="input-group">
                                            <div class="input-group-text">
                                                <input type="checkbox" name="correct_options[]" value="1">
                                            </div>
                                            <input type="text" class="form-control" name="options[]" placeholder="Option 2">
                                            <button type="button" class="btn btn-outline-danger remove-option">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add_option">
                                    <i class="fas fa-plus me-1"></i>Add Option
                                </button>
                                <small class="form-text text-muted d-block mt-2">
                                    Check the box next to correct answers. You can select multiple correct answers.
                                </small>
                            </div>

                            <!-- True/False Options -->
                            <div id="true_false_options" class="question-type-options" style="display: none;">
                                <h6><i class="fas fa-check-circle me-1"></i>Correct Answer</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="true_false_answer" value="true" id="true_answer">
                                    <label class="form-check-label" for="true_answer">True</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="true_false_answer" value="false" id="false_answer">
                                    <label class="form-check-label" for="false_answer">False</label>
                                </div>
                            </div>

                            <!-- Short Answer/Fill in Blank Options -->
                            <div id="text_answer_options" class="question-type-options" style="display: none;">
                                <h6><i class="fas fa-edit me-1"></i>Correct Answer</h6>
                                <input type="text" class="form-control" name="correct_answer" 
                                       placeholder="Enter the correct answer...">
                                <small class="form-text text-muted">
                                    For fill-in-the-blank questions, use underscores (_____) in your question text to indicate where the answer should go.
                                </small>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Save Question
                                </button>
                                <button type="submit" name="add_another" value="1" class="btn btn-success">
                                    <i class="fas fa-plus me-1"></i>Save & Add Another
                                </button>
                                <a href="quizzes.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const questionTypeSelect = document.getElementById('question_type');
            const optionsSections = document.querySelectorAll('.question-type-options');
            let optionCount = 2;

            // Handle question type change
            questionTypeSelect.addEventListener('change', function() {
                // Hide all options sections
                optionsSections.forEach(section => {
                    section.style.display = 'none';
                });

                // Show relevant options section
                const selectedType = this.value;
                if (selectedType === 'multiple_choice') {
                    document.getElementById('multiple_choice_options').style.display = 'block';
                } else if (selectedType === 'true_false') {
                    document.getElementById('true_false_options').style.display = 'block';
                } else if (selectedType === 'short_answer' || selectedType === 'fill_in_blank') {
                    document.getElementById('text_answer_options').style.display = 'block';
                }
            });

            // Add option functionality
            document.getElementById('add_option').addEventListener('click', function() {
                const container = document.getElementById('options_container');
                const optionGroup = document.createElement('div');
                optionGroup.className = 'option-group mb-2';
                optionGroup.innerHTML = `
                    <div class="input-group">
                        <div class="input-group-text">
                            <input type="checkbox" name="correct_options[]" value="${optionCount}">
                        </div>
                        <input type="text" class="form-control" name="options[]" placeholder="Option ${optionCount + 1}">
                        <button type="button" class="btn btn-outline-danger remove-option">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                container.appendChild(optionGroup);
                optionCount++;
            });

            // Remove option functionality
            document.addEventListener('click', function(e) {
                if (e.target.closest('.remove-option')) {
                    const optionGroup = e.target.closest('.option-group');
                    const container = document.getElementById('options_container');
                    if (container.children.length > 2) {
                        optionGroup.remove();
                    } else {
                        alert('You must have at least 2 options for multiple choice questions.');
                    }
                }
            });
        });
    </script>
</body>
</html>
