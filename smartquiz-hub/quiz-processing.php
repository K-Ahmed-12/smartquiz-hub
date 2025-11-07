<?php
/**
 * Quiz Processing Page - Shows loading while calculating results
 */

require_once 'config/config.php';
requireLogin();

$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;

if (!$attempt_id) {
    redirect('dashboard.php');
}

try {
    $db = getDB();
    
    // Verify this attempt belongs to the current user
    $stmt = $db->prepare("
        SELECT qa.*, q.title, q.total_marks
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.id = ? AND qa.user_id = ?
    ");
    $stmt->execute([$attempt_id, $_SESSION['user_id']]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) {
        redirect('dashboard.php');
    }
    
} catch (PDOException $e) {
    redirect('dashboard.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Quiz Results - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .processing-container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        
        .spinner {
            width: 80px;
            height: 80px;
            border: 8px solid #f3f3f3;
            border-top: 8px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 30px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .processing-title {
            color: #333;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .processing-subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .quiz-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .quiz-info h5 {
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            position: relative;
        }
        
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .progress-step::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            right: -50%;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .progress-step:last-child::before {
            display: none;
        }
        
        .progress-step.active::before {
            background: #007bff;
        }
        
        .progress-step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        .progress-step.active .progress-step-icon {
            background: #007bff;
            color: white;
        }
        
        .progress-step.completed .progress-step-icon {
            background: #28a745;
            color: white;
        }
        
        .progress-step-text {
            font-size: 12px;
            color: #6c757d;
        }
        
        .progress-step.active .progress-step-text {
            color: #007bff;
            font-weight: 600;
        }
        
        .estimated-time {
            color: #28a745;
            font-size: 14px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="processing-container">
        <div class="spinner"></div>
        
        <h2 class="processing-title">Processing Your Quiz</h2>
        <p class="processing-subtitle">Please wait while we calculate your results...</p>
        
        <div class="quiz-info">
            <h5><i class="fas fa-clipboard-list me-2"></i><?php echo htmlspecialchars($attempt['title']); ?></h5>
            <p class="mb-0 text-muted">
                <i class="fas fa-clock me-1"></i>
                Completed: <?php echo date('M j, Y g:i A', strtotime($attempt['end_time'])); ?>
            </p>
        </div>
        
        <div class="progress-steps">
            <div class="progress-step completed">
                <div class="progress-step-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="progress-step-text">Quiz Submitted</div>
            </div>
            <div class="progress-step active" id="step-grading">
                <div class="progress-step-icon">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="progress-step-text">Grading Answers</div>
            </div>
            <div class="progress-step" id="step-calculating">
                <div class="progress-step-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="progress-step-text">Calculating Score</div>
            </div>
            <div class="progress-step" id="step-finalizing">
                <div class="progress-step-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="progress-step-text">Finalizing Results</div>
            </div>
        </div>
        
        <p class="estimated-time">
            <i class="fas fa-hourglass-half me-1"></i>
            Estimated time: 3-5 seconds
        </p>
    </div>

    <script>
        let currentStep = 0;
        const steps = ['step-grading', 'step-calculating', 'step-finalizing'];
        const stepTexts = [
            'Checking your answers...',
            'Calculating your score...',
            'Preparing your results...'
        ];
        
        function updateProgress() {
            // Update subtitle text
            document.querySelector('.processing-subtitle').textContent = stepTexts[currentStep];
            
            // Mark current step as completed and move to next
            if (currentStep > 0) {
                const prevStep = document.getElementById(steps[currentStep - 1]);
                prevStep.classList.remove('active');
                prevStep.classList.add('completed');
                prevStep.querySelector('.progress-step-icon').innerHTML = '<i class="fas fa-check"></i>';
            }
            
            // Activate current step
            if (currentStep < steps.length) {
                const currentStepEl = document.getElementById(steps[currentStep]);
                currentStepEl.classList.add('active');
            }
            
            currentStep++;
        }
        
        // Simulate processing steps
        setTimeout(() => updateProgress(), 1000);  // Step 1: Grading
        setTimeout(() => updateProgress(), 2500);  // Step 2: Calculating
        setTimeout(() => updateProgress(), 4000);  // Step 3: Finalizing
        
        // Process the quiz results via AJAX
        setTimeout(() => {
            fetch('process-quiz-results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'attempt_id=<?php echo $attempt_id; ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show completion and redirect
                    document.querySelector('.processing-subtitle').textContent = 'Results ready! Redirecting...';
                    setTimeout(() => {
                        window.location.href = 'quiz-result.php?attempt_id=<?php echo $attempt_id; ?>';
                    }, 1000);
                } else {
                    // Handle error
                    document.querySelector('.processing-subtitle').textContent = 'Error processing results. Redirecting...';
                    setTimeout(() => {
                        window.location.href = 'quiz-result.php?attempt_id=<?php echo $attempt_id; ?>';
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Fallback redirect
                setTimeout(() => {
                    window.location.href = 'quiz-result.php?attempt_id=<?php echo $attempt_id; ?>';
                }, 3000);
            });
        }, 5000); // Start processing after 5 seconds
    </script>
</body>
</html>
