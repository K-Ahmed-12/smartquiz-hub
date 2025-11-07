<?php
/**
 * Fix Scoring Bug - Direct Fix for Quiz Results
 * This script fixes the specific issue where correct answers show 0 marks
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Direct database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=smartquiz_hub", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Fixing Quiz Scoring Bug</h2>";
    echo "<p>✅ Database connected</p>";
    
    // Get the problematic attempt (attempt_id=5 from your screenshot)
    $attempt_id = 5;
    
    echo "<h3>Analyzing Attempt ID: {$attempt_id}</h3>";
    
    // Get attempt details
    $stmt = $pdo->prepare("
        SELECT qa.*, q.title, q.total_marks as quiz_total_marks
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
    echo "<p><strong>Current Percentage:</strong> {$attempt['percentage']}%</p>";
    
    // Get all questions and user answers for this attempt
    $stmt = $pdo->prepare("
        SELECT 
            q.id as question_id,
            q.question_text,
            q.marks,
            ua.selected_option_id,
            ua.is_correct as current_is_correct,
            ua.marks_awarded as current_marks_awarded
        FROM questions q
        LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.attempt_id = ?
        WHERE q.quiz_id = ?
        ORDER BY q.order_number
    ");
    $stmt->execute([$attempt_id, $attempt['quiz_id']]);
    $questions = $stmt->fetchAll();
    
    echo "<h3>Question Analysis:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Question</th><th>Selected Option</th><th>Current Status</th><th>Current Marks</th><th>Should Be</th></tr>";
    
    $total_correct_score = 0;
    $questions_to_fix = [];
    
    foreach ($questions as $question) {
        if ($question['selected_option_id']) {
            // Check if the selected option is actually correct
            $stmt = $pdo->prepare("
                SELECT is_correct, option_text 
                FROM question_options 
                WHERE id = ?
            ");
            $stmt->execute([$question['selected_option_id']]);
            $selected_option = $stmt->fetch();
            
            $should_be_correct = $selected_option && $selected_option['is_correct'] == 1;
            $should_get_marks = $should_be_correct ? $question['marks'] : 0;
            
            if ($should_be_correct) {
                $total_correct_score += $question['marks'];
            }
            
            // Check if this needs fixing
            $needs_fix = ($question['current_is_correct'] != $should_be_correct) || 
                        ($question['current_marks_awarded'] != $should_get_marks);
            
            if ($needs_fix) {
                $questions_to_fix[] = [
                    'question_id' => $question['question_id'],
                    'should_be_correct' => $should_be_correct,
                    'should_get_marks' => $should_get_marks
                ];
            }
            
            $status_color = $needs_fix ? 'background: #f8d7da;' : 'background: #d4edda;';
            
            echo "<tr style='{$status_color}'>";
            echo "<td>" . substr($question['question_text'], 0, 50) . "...</td>";
            echo "<td>{$question['selected_option_id']} ({$selected_option['option_text']})</td>";
            echo "<td>" . ($question['current_is_correct'] ? 'Correct' : 'Wrong') . " ({$question['current_marks_awarded']} marks)</td>";
            echo "<td>{$question['current_marks_awarded']}</td>";
            echo "<td>" . ($should_be_correct ? 'Correct' : 'Wrong') . " ({$should_get_marks} marks)</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    echo "<h3>Summary:</h3>";
    echo "<p><strong>Questions needing fix:</strong> " . count($questions_to_fix) . "</p>";
    echo "<p><strong>Correct total score should be:</strong> {$total_correct_score}/{$attempt['quiz_total_marks']}</p>";
    echo "<p><strong>Correct percentage should be:</strong> " . round(($total_correct_score / $attempt['quiz_total_marks']) * 100, 1) . "%</p>";
    
    // Fix button
    if (isset($_POST['fix_now'])) {
        echo "<h3>🔧 Applying Fix...</h3>";
        
        // Fix each question
        foreach ($questions_to_fix as $fix) {
            $stmt = $pdo->prepare("
                UPDATE user_answers 
                SET is_correct = ?, marks_awarded = ?
                WHERE attempt_id = ? AND question_id = ?
            ");
            $stmt->execute([
                $fix['should_be_correct'] ? 1 : 0,
                $fix['should_get_marks'],
                $attempt_id,
                $fix['question_id']
            ]);
            
            echo "<p>✅ Fixed question {$fix['question_id']}</p>";
        }
        
        // Update the quiz attempt
        $correct_percentage = ($total_correct_score / $attempt['quiz_total_marks']) * 100;
        
        $stmt = $pdo->prepare("
            UPDATE quiz_attempts 
            SET score = ?, percentage = ?, total_marks = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $total_correct_score,
            $correct_percentage,
            $attempt['quiz_total_marks'],
            $attempt_id
        ]);
        
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h4>🎉 Fix Applied Successfully!</h4>";
        echo "<p><strong>New Score:</strong> {$total_correct_score}/{$attempt['quiz_total_marks']}</p>";
        echo "<p><strong>New Percentage:</strong> " . round($correct_percentage, 1) . "%</p>";
        echo "<p><a href='quiz-result.php?attempt_id={$attempt_id}' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Fixed Result</a></p>";
        echo "</div>";
        
    } else if (count($questions_to_fix) > 0) {
        echo "<form method='POST' style='margin: 20px 0;'>";
        echo "<button type='submit' name='fix_now' value='1' style='background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>";
        echo "🔧 Fix This Attempt Now";
        echo "</button>";
        echo "</form>";
    } else {
        echo "<p style='color: #28a745; font-weight: bold;'>✅ This attempt looks correct already!</p>";
    }
    
    // Show other attempts that might need fixing
    echo "<h3>Other Recent Attempts:</h3>";
    $stmt = $pdo->prepare("
        SELECT qa.id, qa.score, qa.percentage, qa.status, q.title, q.total_marks
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.status = 'completed'
        ORDER BY qa.id DESC
        LIMIT 10
    ");
    $stmt->execute();
    $all_attempts = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Quiz</th><th>Score</th><th>Percentage</th><th>Status</th><th>Action</th></tr>";
    
    foreach ($all_attempts as $att) {
        $suspicious = ($att['score'] == 0 && $att['percentage'] == 0) ? 'background: #fff3cd;' : '';
        echo "<tr style='{$suspicious}'>";
        echo "<td>{$att['id']}</td>";
        echo "<td>" . substr($att['title'], 0, 20) . "...</td>";
        echo "<td>{$att['score']}/{$att['total_marks']}</td>";
        echo "<td>" . round($att['percentage'], 1) . "%</td>";
        echo "<td>{$att['status']}</td>";
        echo "<td>";
        echo "<a href='quiz-result.php?attempt_id={$att['id']}' target='_blank' style='margin-right: 5px;'>View</a>";
        if ($att['score'] == 0 && $att['percentage'] == 0) {
            echo "<a href='?attempt_id={$att['id']}' style='color: #dc3545;'>Fix</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Handle different attempt IDs
    if (isset($_GET['attempt_id']) && $_GET['attempt_id'] != $attempt_id) {
        $new_attempt_id = (int)$_GET['attempt_id'];
        echo "<script>window.location.href = '?attempt_id={$new_attempt_id}';</script>";
    }
    
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
h2 { color: #007bff; margin-bottom: 20px; }
h3 { color: #28a745; margin-top: 25px; margin-bottom: 15px; }
table { margin: 15px 0; width: 100%; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
th { background: #e9ecef; font-weight: bold; }
button { cursor: pointer; transition: all 0.3s ease; }
button:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
a { transition: all 0.3s ease; }
a:hover { transform: translateY(-1px); }
</style>
