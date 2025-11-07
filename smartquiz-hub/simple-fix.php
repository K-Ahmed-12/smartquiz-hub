<?php
/**
 * Simple Fix for Quiz Result Issue
 */

// Basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Quiz Result Fix</h2>";

try {
    // Direct database connection
    $host = 'localhost';
    $dbname = 'smartquiz_hub';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Database connected successfully</p>";
    
    // Check if attempt_id=2 exists
    $stmt = $pdo->prepare("SELECT * FROM quiz_attempts WHERE id = 2");
    $stmt->execute();
    $attempt = $stmt->fetch();
    
    if (!$attempt) {
        echo "<p>❌ Attempt ID 2 not found</p>";
        exit;
    }
    
    echo "<h3>Current Attempt Data:</h3>";
    echo "<p>Score: {$attempt['score']}</p>";
    echo "<p>Percentage: {$attempt['percentage']}%</p>";
    echo "<p>Status: {$attempt['status']}</p>";
    
    // Get user answers for this attempt
    $stmt = $pdo->prepare("
        SELECT ua.*, q.question_text, q.marks 
        FROM user_answers ua 
        JOIN questions q ON ua.question_id = q.id 
        WHERE ua.attempt_id = 2
    ");
    $stmt->execute();
    $answers = $stmt->fetchAll();
    
    echo "<h3>User Answers:</h3>";
    if (empty($answers)) {
        echo "<p>❌ No answers found for attempt 2</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Question</th><th>Selected Option</th><th>Is Correct</th><th>Marks Awarded</th></tr>";
        foreach ($answers as $answer) {
            echo "<tr>";
            echo "<td>" . substr($answer['question_text'], 0, 50) . "...</td>";
            echo "<td>{$answer['selected_option_id']}</td>";
            echo "<td>" . ($answer['is_correct'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$answer['marks_awarded']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Manual fix for attempt 2
    if (isset($_POST['fix_attempt_2'])) {
        echo "<h3>🔧 Fixing Attempt 2...</h3>";
        
        // Get all questions for this quiz
        $stmt = $pdo->prepare("
            SELECT q.*, ua.selected_option_id
            FROM questions q
            LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.attempt_id = 2
            WHERE q.quiz_id = ?
            ORDER BY q.order_number
        ");
        $stmt->execute([$attempt['quiz_id']]);
        $questions = $stmt->fetchAll();
        
        $total_score = 0;
        
        foreach ($questions as $question) {
            if ($question['selected_option_id']) {
                // Get the correct option for this question
                $stmt = $pdo->prepare("
                    SELECT id, is_correct 
                    FROM question_options 
                    WHERE question_id = ? AND id = ?
                ");
                $stmt->execute([$question['id'], $question['selected_option_id']]);
                $selected_option = $stmt->fetch();
                
                $is_correct = $selected_option && $selected_option['is_correct'] == 1;
                $marks_awarded = $is_correct ? $question['marks'] : 0;
                
                // Update user answer
                $stmt = $pdo->prepare("
                    UPDATE user_answers 
                    SET is_correct = ?, marks_awarded = ? 
                    WHERE attempt_id = 2 AND question_id = ?
                ");
                $stmt->execute([$is_correct ? 1 : 0, $marks_awarded, $question['id']]);
                
                $total_score += $marks_awarded;
                
                echo "<p>Question {$question['id']}: " . ($is_correct ? "✅ Correct" : "❌ Wrong") . " - {$marks_awarded} marks</p>";
            }
        }
        
        // Get quiz total marks
        $stmt = $pdo->prepare("SELECT total_marks FROM quizzes WHERE id = ?");
        $stmt->execute([$attempt['quiz_id']]);
        $quiz_total = $stmt->fetch()['total_marks'];
        
        // Calculate percentage
        $percentage = $quiz_total > 0 ? ($total_score / $quiz_total) * 100 : 0;
        
        // Update attempt
        $stmt = $pdo->prepare("
            UPDATE quiz_attempts 
            SET score = ?, percentage = ?, total_marks = ? 
            WHERE id = 2
        ");
        $stmt->execute([$total_score, $percentage, $quiz_total]);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ Fixed Successfully!</h4>";
        echo "<p>New Score: {$total_score}/{$quiz_total}</p>";
        echo "<p>New Percentage: " . number_format($percentage, 1) . "%</p>";
        echo "<p><a href='quiz-result.php?attempt_id=2' target='_blank'>View Updated Result</a></p>";
        echo "</div>";
    }
    
    // Show fix button if not already fixed
    if (!isset($_POST['fix_attempt_2'])) {
        echo "<form method='POST' style='margin: 20px 0;'>";
        echo "<button type='submit' name='fix_attempt_2' value='1' style='background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px;'>";
        echo "🔧 Fix Attempt 2 Now";
        echo "</button>";
        echo "</form>";
    }
    
    // Show all attempts for debugging
    echo "<h3>All Quiz Attempts:</h3>";
    $stmt = $pdo->prepare("
        SELECT qa.id, qa.score, qa.percentage, qa.status, q.title 
        FROM quiz_attempts qa 
        JOIN quizzes q ON qa.quiz_id = q.id 
        ORDER BY qa.id DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $all_attempts = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Quiz</th><th>Score</th><th>Percentage</th><th>Status</th><th>Action</th></tr>";
    foreach ($all_attempts as $att) {
        echo "<tr>";
        echo "<td>{$att['id']}</td>";
        echo "<td>{$att['title']}</td>";
        echo "<td>{$att['score']}</td>";
        echo "<td>{$att['percentage']}%</td>";
        echo "<td>{$att['status']}</td>";
        echo "<td><a href='quiz-result.php?attempt_id={$att['id']}' target='_blank'>View</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ Database Error!</h4>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure XAMPP MySQL is running and the database 'smartquiz_hub' exists.</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ General Error!</h4>";
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
h2 { color: #007bff; }
h3 { color: #28a745; margin-top: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; }
th { background: #e9ecef; }
button { cursor: pointer; }
button:hover { opacity: 0.9; }
</style>
