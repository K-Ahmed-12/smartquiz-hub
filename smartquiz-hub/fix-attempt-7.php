<?php
/**
 * Immediate Fix for Attempt #7
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🚨 Fixing Attempt #7 Right Now</h2>";

try {
    // Direct database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smartquiz_hub", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $attempt_id = 7;
    
    echo "<h3>Step 1: Analyzing Attempt #7</h3>";
    
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
        echo "<p>❌ Attempt #7 not found</p>";
        exit;
    }
    
    echo "<p><strong>Quiz:</strong> {$attempt['title']}</p>";
    echo "<p><strong>Current Score:</strong> {$attempt['score']}/{$attempt['total_marks']}</p>";
    echo "<p><strong>Status:</strong> {$attempt['status']}</p>";
    
    echo "<h3>Step 2: Checking User Answers</h3>";
    
    // Get user answers
    $stmt = $pdo->prepare("
        SELECT ua.*, q.question_text, q.marks, q.question_type
        FROM user_answers ua
        JOIN questions q ON ua.question_id = q.id
        WHERE ua.attempt_id = ?
        ORDER BY q.order_number
    ");
    $stmt->execute([$attempt_id]);
    $user_answers = $stmt->fetchAll();
    
    if (empty($user_answers)) {
        echo "<p>❌ No answers found! This is the problem.</p>";
        
        // Check if there are questions for this quiz
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM questions WHERE quiz_id = ?");
        $stmt->execute([$attempt['quiz_id']]);
        $question_count = $stmt->fetch()['count'];
        
        echo "<p>Quiz has {$question_count} questions but no answers were saved.</p>";
        echo "<p>This means the answer saving mechanism failed.</p>";
        
    } else {
        echo "<p>✅ Found " . count($user_answers) . " answers</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Question</th><th>Answer</th><th>Current Status</th><th>Should Be</th></tr>";
        
        $total_correct = 0;
        
        foreach ($user_answers as $answer) {
            $correct_marks = 0;
            $should_be_correct = false;
            
            if ($answer['selected_option_id']) {
                // Check if this option is correct
                $stmt = $pdo->prepare("
                    SELECT is_correct, option_text 
                    FROM question_options 
                    WHERE id = ?
                ");
                $stmt->execute([$answer['selected_option_id']]);
                $option = $stmt->fetch();
                
                if ($option && $option['is_correct'] == 1) {
                    $should_be_correct = true;
                    $correct_marks = $answer['marks'];
                    $total_correct += $correct_marks;
                }
                
                echo "<tr>";
                echo "<td>" . substr($answer['question_text'], 0, 40) . "...</td>";
                echo "<td>Option {$answer['selected_option_id']}: {$option['option_text']}</td>";
                echo "<td>" . ($answer['is_correct'] ? "Correct" : "Wrong") . " ({$answer['marks_awarded']} marks)</td>";
                echo "<td>" . ($should_be_correct ? "✅ Correct" : "❌ Wrong") . " ({$correct_marks} marks)</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
        
        echo "<p><strong>Calculated Total:</strong> {$total_correct}/{$attempt['total_marks']}</p>";
    }
    
    echo "<h3>Step 3: Apply Fix</h3>";
    
    if (isset($_POST['apply_fix'])) {
        $fixed_score = 0;
        
        // Re-grade each answer
        foreach ($user_answers as $answer) {
            $marks_awarded = 0;
            $is_correct = false;
            
            if ($answer['selected_option_id']) {
                $stmt = $pdo->prepare("SELECT is_correct FROM question_options WHERE id = ?");
                $stmt->execute([$answer['selected_option_id']]);
                $option = $stmt->fetch();
                
                if ($option && $option['is_correct'] == 1) {
                    $is_correct = true;
                    $marks_awarded = $answer['marks'];
                    $fixed_score += $marks_awarded;
                }
                
                // Update user answer
                $stmt = $pdo->prepare("
                    UPDATE user_answers 
                    SET is_correct = ?, marks_awarded = ?
                    WHERE attempt_id = ? AND question_id = ?
                ");
                $stmt->execute([$is_correct ? 1 : 0, $marks_awarded, $attempt_id, $answer['question_id']]);
            }
        }
        
        // Update attempt
        $percentage = ($fixed_score / $attempt['total_marks']) * 100;
        $stmt = $pdo->prepare("
            UPDATE quiz_attempts 
            SET score = ?, percentage = ?
            WHERE id = ?
        ");
        $stmt->execute([$fixed_score, $percentage, $attempt_id]);
        
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px;'>";
        echo "<h4>🎉 FIXED!</h4>";
        echo "<p><strong>New Score:</strong> {$fixed_score}/{$attempt['total_marks']}</p>";
        echo "<p><strong>New Percentage:</strong> " . round($percentage, 1) . "%</p>";
        echo "<p><a href='quiz-result.php?attempt_id={$attempt_id}' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Fixed Result</a></p>";
        echo "</div>";
        
    } else {
        echo "<form method='POST'>";
        echo "<button type='submit' name='apply_fix' value='1' style='background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>";
        echo "🔧 Fix Attempt #7 Now";
        echo "</button>";
        echo "</form>";
    }
    
    echo "<h3>Step 4: Check Why This Keeps Happening</h3>";
    
    // Check if the quiz-interface.php fix is working
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as recent_broken
        FROM quiz_attempts 
        WHERE status = 'completed' 
        AND (score = 0 OR percentage = 0)
        AND start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute();
    $recent_broken = $stmt->fetch()['recent_broken'];
    
    if ($recent_broken > 0) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h4>⚠️ Problem: Quiz Interface Fix Not Working</h4>";
        echo "<p>Found {$recent_broken} recent attempts with 0 scores.</p>";
        echo "<p>The quiz submission process is still not grading properly.</p>";
        echo "<p><strong>Solution:</strong> Need to check if the quiz-interface.php changes were applied correctly.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "<h4>✅ Good: No Recent Broken Attempts</h4>";
        echo "<p>The fix should be working for new quizzes.</p>";
        echo "</div>";
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
h2 { color: #dc3545; margin-bottom: 20px; }
h3 { color: #28a745; margin-top: 25px; margin-bottom: 15px; }
h4 { color: #495057; margin-bottom: 10px; }
table { margin: 15px 0; width: 100%; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
th { background: #e9ecef; font-weight: bold; }
button { cursor: pointer; transition: all 0.3s ease; }
button:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
</style>
