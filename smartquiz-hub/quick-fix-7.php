<?php
/**
 * Quick Fix for Attempt #7 - Simple and Direct
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Quick Fix for Attempt #7</h2>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=smartquiz_hub", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $attempt_id = 7;
    
    // Get attempt info
    $stmt = $pdo->prepare("SELECT * FROM quiz_attempts WHERE id = ?");
    $stmt->execute([$attempt_id]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) {
        echo "<p>❌ Attempt #7 not found</p>";
        exit;
    }
    
    echo "<p><strong>Current Score:</strong> {$attempt['score']}</p>";
    echo "<p><strong>Current Percentage:</strong> {$attempt['percentage']}%</p>";
    
    // Get user answers
    $stmt = $pdo->prepare("
        SELECT ua.*, q.marks 
        FROM user_answers ua 
        JOIN questions q ON ua.question_id = q.id 
        WHERE ua.attempt_id = ?
    ");
    $stmt->execute([$attempt_id]);
    $answers = $stmt->fetchAll();
    
    echo "<p><strong>Found Answers:</strong> " . count($answers) . "</p>";
    
    if (empty($answers)) {
        echo "<p style='color: red;'>❌ No answers found! This is why score is 0.</p>";
        echo "<p>The answer saving mechanism failed during quiz submission.</p>";
    } else {
        // Calculate correct score
        $correct_score = 0;
        
        foreach ($answers as $answer) {
            if ($answer['selected_option_id']) {
                $stmt = $pdo->prepare("SELECT is_correct FROM question_options WHERE id = ?");
                $stmt->execute([$answer['selected_option_id']]);
                $option = $stmt->fetch();
                
                if ($option && $option['is_correct'] == 1) {
                    $correct_score += $answer['marks'];
                    echo "<p>✅ Question {$answer['question_id']}: Correct (+{$answer['marks']} marks)</p>";
                } else {
                    echo "<p>❌ Question {$answer['question_id']}: Wrong (0 marks)</p>";
                }
            }
        }
        
        echo "<p><strong>Calculated Score:</strong> {$correct_score}</p>";
        
        if (isset($_POST['fix'])) {
            // Apply the fix
            foreach ($answers as $answer) {
                if ($answer['selected_option_id']) {
                    $stmt = $pdo->prepare("SELECT is_correct FROM question_options WHERE id = ?");
                    $stmt->execute([$answer['selected_option_id']]);
                    $option = $stmt->fetch();
                    
                    $is_correct = $option && $option['is_correct'] == 1;
                    $marks_awarded = $is_correct ? $answer['marks'] : 0;
                    
                    $stmt = $pdo->prepare("
                        UPDATE user_answers 
                        SET is_correct = ?, marks_awarded = ? 
                        WHERE attempt_id = ? AND question_id = ?
                    ");
                    $stmt->execute([$is_correct ? 1 : 0, $marks_awarded, $attempt_id, $answer['question_id']]);
                }
            }
            
            // Get quiz total marks
            $stmt = $pdo->prepare("SELECT total_marks FROM quizzes WHERE id = ?");
            $stmt->execute([$attempt['quiz_id']]);
            $total_marks = $stmt->fetch()['total_marks'];
            
            $percentage = ($correct_score / $total_marks) * 100;
            
            // Update attempt
            $stmt = $pdo->prepare("
                UPDATE quiz_attempts 
                SET score = ?, percentage = ? 
                WHERE id = ?
            ");
            $stmt->execute([$correct_score, $percentage, $attempt_id]);
            
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
            echo "<h3>✅ FIXED!</h3>";
            echo "<p><strong>New Score:</strong> {$correct_score}/{$total_marks}</p>";
            echo "<p><strong>New Percentage:</strong> " . round($percentage, 1) . "%</p>";
            echo "<p><a href='quiz-result.php?attempt_id={$attempt_id}' target='_blank' style='background: #007bff; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px;'>View Fixed Result</a></p>";
            echo "</div>";
        } else {
            echo "<form method='POST' style='margin: 20px 0;'>";
            echo "<button type='submit' name='fix' value='1' style='background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>";
            echo "🔧 Fix Attempt #7";
            echo "</button>";
            echo "</form>";
        }
    }
    
    // Show all recent attempts
    echo "<h3>Recent Attempts Status:</h3>";
    $stmt = $pdo->prepare("
        SELECT id, score, percentage, status 
        FROM quiz_attempts 
        ORDER BY id DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Attempt ID</th><th>Score</th><th>Percentage</th><th>Status</th></tr>";
    foreach ($recent as $r) {
        $color = ($r['score'] == 0 && $r['status'] == 'completed') ? 'background: #f8d7da;' : '';
        echo "<tr style='{$color}'>";
        echo "<td>{$r['id']}</td>";
        echo "<td>{$r['score']}</td>";
        echo "<td>{$r['percentage']}%</td>";
        echo "<td>{$r['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ Error: " . $e->getMessage() . "</h4>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
h2 { color: #007bff; }
h3 { color: #28a745; }
table { margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; }
th { background: #e9ecef; }
</style>
