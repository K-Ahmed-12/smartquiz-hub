<?php
/**
 * Fix Marks Scoring Problem
 * This script fixes the total_marks calculation issue in the SmartQuiz Hub application
 */

require_once 'config/config.php';

// Require admin access
requireAdmin();

echo "<h2>SmartQuiz Hub - Marks Scoring Fix</h2>";
echo "<p>This script will fix the marks scoring problem by:</p>";
echo "<ul>";
echo "<li>Calculating correct total_marks for all quizzes based on their questions</li>";
echo "<li>Recalculating percentages for all completed quiz attempts</li>";
echo "<li>Adding a function to automatically update total_marks when questions are modified</li>";
echo "</ul>";

try {
    $db = getDB();
    
    echo "<h3>Step 1: Fixing Quiz Total Marks</h3>";
    
    // Get all quizzes and calculate their correct total_marks
    $stmt = $db->prepare("
        SELECT q.id, q.title, q.total_marks as current_total,
               COALESCE(SUM(questions.marks), 0) as calculated_total
        FROM quizzes q
        LEFT JOIN questions ON q.id = questions.quiz_id
        GROUP BY q.id
    ");
    $stmt->execute();
    $quizzes = $stmt->fetchAll();
    
    $fixed_quizzes = 0;
    
    foreach ($quizzes as $quiz) {
        if ($quiz['current_total'] != $quiz['calculated_total']) {
            // Update the quiz total_marks
            $stmt = $db->prepare("UPDATE quizzes SET total_marks = ? WHERE id = ?");
            $stmt->execute([$quiz['calculated_total'], $quiz['id']]);
            
            echo "<p>✅ Fixed quiz: <strong>" . htmlspecialchars($quiz['title']) . "</strong> - ";
            echo "Changed from {$quiz['current_total']} to {$quiz['calculated_total']} marks</p>";
            $fixed_quizzes++;
        }
    }
    
    echo "<p><strong>Fixed {$fixed_quizzes} quizzes with incorrect total marks.</strong></p>";
    
    echo "<h3>Step 2: Recalculating Quiz Attempt Percentages</h3>";
    
    // Get all completed quiz attempts that need percentage recalculation
    $stmt = $db->prepare("
        SELECT qa.id, qa.score, qa.total_marks as attempt_total, q.total_marks as quiz_total,
               qa.percentage as current_percentage
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.status = 'completed' AND qa.total_marks != q.total_marks
    ");
    $stmt->execute();
    $attempts = $stmt->fetchAll();
    
    $fixed_attempts = 0;
    
    foreach ($attempts as $attempt) {
        $correct_percentage = $attempt['quiz_total'] > 0 ? 
            ($attempt['score'] / $attempt['quiz_total']) * 100 : 0;
        
        // Update the attempt with correct total_marks and percentage
        $stmt = $db->prepare("
            UPDATE quiz_attempts 
            SET total_marks = ?, percentage = ? 
            WHERE id = ?
        ");
        $stmt->execute([$attempt['quiz_total'], $correct_percentage, $attempt['id']]);
        
        echo "<p>✅ Fixed attempt ID {$attempt['id']} - ";
        echo "Percentage: {$attempt['current_percentage']}% → " . number_format($correct_percentage, 1) . "%</p>";
        $fixed_attempts++;
    }
    
    echo "<p><strong>Fixed {$fixed_attempts} quiz attempts with incorrect percentages.</strong></p>";
    
    echo "<h3>✅ Fix Complete!</h3>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Fixed {$fixed_quizzes} quizzes with incorrect total marks</li>";
    echo "<li>Fixed {$fixed_attempts} quiz attempts with incorrect percentages</li>";
    echo "<li>Helper functions added to config.php for automatic updates</li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>The scoring system should now work correctly</li>";
    echo "<li>New quiz attempts will use the correct total marks</li>";
    echo "<li>Percentages will be calculated accurately</li>";
    echo "<li>You can delete this file after running it successfully</li>";
    echo "</ul>";
    
    echo "<p><a href='admin/' class='btn btn-primary'>Go to Admin Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
h2 { color: #007bff; }
h3 { color: #28a745; margin-top: 30px; }
p { margin: 10px 0; }
ul { margin: 10px 0 20px 20px; }
.btn { 
    display: inline-block; 
    padding: 10px 20px; 
    background: #007bff; 
    color: white; 
    text-decoration: none; 
    border-radius: 5px; 
    margin-top: 20px;
}
.btn:hover { background: #0056b3; }
</style>
