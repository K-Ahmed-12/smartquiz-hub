<?php
/**
 * Emergency Fix for Quiz Scoring Issue
 * This will immediately fix the scoring problem
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🚨 Emergency Quiz Scoring Fix</h2>";

try {
    // Direct database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smartquiz_hub", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Database connected</p>";
    
    // Get the latest attempt (attempt_id=6 from your screenshot)
    $attempt_id = 6;
    
    echo "<h3>Fixing Attempt ID: {$attempt_id}</h3>";
    
    // Get attempt details
    $stmt = $pdo->prepare("
        SELECT qa.*, q.title, q.total_marks
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.id = ?
    ");
    $stmt->execute([$attempt_id]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) {
        echo "<p>❌ Attempt not found</p>";
        exit;
    }
    
    echo "<p><strong>Quiz:</strong> {$attempt['title']}</p>";
    echo "<p><strong>Current Score:</strong> {$attempt['score']}/{$attempt['total_marks']}</p>";
    
    // Get all questions and user answers for this attempt
    $stmt = $pdo->prepare("
        SELECT 
            q.id as question_id,
            q.question_text,
            q.marks,
            ua.selected_option_id,
            ua.answer_text
        FROM questions q
        LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.attempt_id = ?
        WHERE q.quiz_id = ?
        ORDER BY q.order_number
    ");
    $stmt->execute([$attempt_id, $attempt['quiz_id']]);
    $questions = $stmt->fetchAll();
    
    echo "<h3>Re-grading Questions:</h3>";
    
    $total_correct_score = 0;
    $answered_count = 0;
    
    foreach ($questions as $question) {
        if ($question['selected_option_id'] || $question['answer_text']) {
            $answered_count++;
            $marks_awarded = 0;
            $is_correct = false;
            
            if ($question['selected_option_id']) {
                // Check if the selected option is correct
                $stmt = $pdo->prepare("
                    SELECT is_correct, option_text 
                    FROM question_options 
                    WHERE id = ?
                ");
                $stmt->execute([$question['selected_option_id']]);
                $selected_option = $stmt->fetch();
                
                if ($selected_option && $selected_option['is_correct'] == 1) {
                    $is_correct = true;
                    $marks_awarded = $question['marks'];
                    $total_correct_score += $marks_awarded;
                }
                
                echo "<p>Question {$question['question_id']}: ";
                echo "Selected option {$question['selected_option_id']} ({$selected_option['option_text']}) - ";
                echo ($is_correct ? "✅ Correct ({$marks_awarded} marks)" : "❌ Wrong (0 marks)");
                echo "</p>";
            }
            
            // Update the user answer in database
            $stmt = $pdo->prepare("
                UPDATE user_answers 
                SET is_correct = ?, marks_awarded = ?
                WHERE attempt_id = ? AND question_id = ?
            ");
            $stmt->execute([$is_correct ? 1 : 0, $marks_awarded, $attempt_id, $question['question_id']]);
        }
    }
    
    // Calculate correct percentage
    $correct_percentage = ($total_correct_score / $attempt['total_marks']) * 100;
    
    // Update the quiz attempt
    $stmt = $pdo->prepare("
        UPDATE quiz_attempts 
        SET score = ?, percentage = ?
        WHERE id = ?
    ");
    $stmt->execute([$total_correct_score, $correct_percentage, $attempt_id]);
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4>🎉 Fix Applied!</h4>";
    echo "<p><strong>Questions Answered:</strong> {$answered_count}</p>";
    echo "<p><strong>New Score:</strong> {$total_correct_score}/{$attempt['total_marks']}</p>";
    echo "<p><strong>New Percentage:</strong> " . round($correct_percentage, 1) . "%</p>";
    echo "</div>";
    
    echo "<p><a href='quiz-result.php?attempt_id={$attempt_id}' target='_blank' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>View Fixed Result</a></p>";
    
    // Also fix all other attempts
    echo "<h3>Fixing All Other Attempts:</h3>";
    
    $stmt = $pdo->prepare("
        SELECT id, quiz_id, score, percentage 
        FROM quiz_attempts 
        WHERE status = 'completed' AND (score = 0 OR percentage = 0)
        ORDER BY id DESC
        LIMIT 10
    ");
    $stmt->execute();
    $broken_attempts = $stmt->fetchAll();
    
    $fixed_count = 0;
    
    foreach ($broken_attempts as $broken_attempt) {
        // Get questions for this attempt
        $stmt = $pdo->prepare("
            SELECT 
                q.id as question_id,
                q.marks,
                ua.selected_option_id
            FROM questions q
            LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.attempt_id = ?
            WHERE q.quiz_id = ?
        ");
        $stmt->execute([$broken_attempt['id'], $broken_attempt['quiz_id']]);
        $attempt_questions = $stmt->fetchAll();
        
        $attempt_score = 0;
        
        foreach ($attempt_questions as $aq) {
            if ($aq['selected_option_id']) {
                $stmt = $pdo->prepare("SELECT is_correct FROM question_options WHERE id = ?");
                $stmt->execute([$aq['selected_option_id']]);
                $option = $stmt->fetch();
                
                if ($option && $option['is_correct'] == 1) {
                    $attempt_score += $aq['marks'];
                    
                    // Update user answer
                    $stmt = $pdo->prepare("
                        UPDATE user_answers 
                        SET is_correct = 1, marks_awarded = ?
                        WHERE attempt_id = ? AND question_id = ?
                    ");
                    $stmt->execute([$aq['marks'], $broken_attempt['id'], $aq['question_id']]);
                }
            }
        }
        
        // Get quiz total marks
        $stmt = $pdo->prepare("SELECT total_marks FROM quizzes WHERE id = ?");
        $stmt->execute([$broken_attempt['quiz_id']]);
        $quiz_total = $stmt->fetch()['total_marks'];
        
        $attempt_percentage = ($attempt_score / $quiz_total) * 100;
        
        // Update attempt
        $stmt = $pdo->prepare("
            UPDATE quiz_attempts 
            SET score = ?, percentage = ?
            WHERE id = ?
        ");
        $stmt->execute([$attempt_score, $attempt_percentage, $broken_attempt['id']]);
        
        echo "<p>✅ Fixed attempt {$broken_attempt['id']}: {$attempt_score}/{$quiz_total} (" . round($attempt_percentage, 1) . "%)</p>";
        $fixed_count++;
    }
    
    echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4>🔧 Emergency Fix Complete!</h4>";
    echo "<p><strong>Fixed {$fixed_count} broken attempts</strong></p>";
    echo "<p>All quiz results should now show correct scores!</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ Database Error!</h4>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background: #f8f9fa; 
    line-height: 1.6;
}
h2 { color: #dc3545; margin-bottom: 20px; }
h3 { color: #28a745; margin-top: 25px; margin-bottom: 15px; }
h4 { color: #495057; margin-bottom: 10px; }
p { margin: 8px 0; }
a { transition: all 0.3s ease; }
a:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
</style>
