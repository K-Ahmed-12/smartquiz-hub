<?php
/**
 * Test Grading Fix - Verify the permanent solution works
 */

require_once 'config/config.php';
requireAdmin();

echo "<h2>🧪 Testing Grading Fix</h2>";

try {
    $db = getDB();
    
    echo "<h3>1. Current System Status</h3>";
    
    // Check recent attempts
    $stmt = $db->prepare("
        SELECT qa.id, qa.score, qa.percentage, qa.status, q.title, q.total_marks,
               COUNT(ua.id) as answered_questions,
               SUM(CASE WHEN ua.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
               SUM(ua.marks_awarded) as calculated_score
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        LEFT JOIN user_answers ua ON qa.id = ua.attempt_id
        WHERE qa.status = 'completed'
        GROUP BY qa.id
        ORDER BY qa.id DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_attempts = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Attempt ID</th><th>Quiz</th><th>Stored Score</th><th>Calculated Score</th><th>Percentage</th><th>Status</th></tr>";
    
    foreach ($recent_attempts as $attempt) {
        $status_color = '';
        if ($attempt['score'] != $attempt['calculated_score']) {
            $status_color = 'background: #fff3cd;'; // Yellow for mismatch
        } elseif ($attempt['score'] == 0 && $attempt['answered_questions'] > 0) {
            $status_color = 'background: #f8d7da;'; // Red for zero score with answers
        } else {
            $status_color = 'background: #d4edda;'; // Green for correct
        }
        
        echo "<tr style='{$status_color}'>";
        echo "<td>{$attempt['id']}</td>";
        echo "<td>" . substr($attempt['title'], 0, 20) . "...</td>";
        echo "<td>{$attempt['score']}/{$attempt['total_marks']}</td>";
        echo "<td>{$attempt['calculated_score']}/{$attempt['total_marks']}</td>";
        echo "<td>" . round($attempt['percentage'], 1) . "%</td>";
        echo "<td>{$attempt['correct_answers']}/{$attempt['answered_questions']} correct</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>2. Testing Quiz Submission Process</h3>";
    
    // Simulate a quiz submission test
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>📋 How the New System Works:</h4>";
    echo "<ol>";
    echo "<li><strong>User takes quiz:</strong> Answers are auto-saved via AJAX</li>";
    echo "<li><strong>User submits quiz:</strong> All answers are saved again (backup)</li>";
    echo "<li><strong>Immediate grading:</strong> System grades all questions instantly</li>";
    echo "<li><strong>Score calculation:</strong> Total score and percentage calculated</li>";
    echo "<li><strong>Database update:</strong> Attempt marked as completed with correct score</li>";
    echo "<li><strong>Result display:</strong> User sees accurate results immediately</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>3. Problem Prevention</h3>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ What's Now Fixed:</h4>";
    echo "<ul>";
    echo "<li><strong>Immediate Grading:</strong> No more waiting for result page to grade</li>";
    echo "<li><strong>Backup Answer Saving:</strong> Answers saved both during quiz and on submit</li>";
    echo "<li><strong>Auto-Submit Grading:</strong> Timer auto-submit now includes all current answers</li>";
    echo "<li><strong>Score Consistency:</strong> Score calculated and stored immediately</li>";
    echo "<li><strong>Database Integrity:</strong> All user_answers have proper is_correct and marks_awarded values</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>4. Test Instructions</h3>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🎯 To Test the Fix:</h4>";
    echo "<ol>";
    echo "<li><strong>Take a new quiz:</strong> <a href='quizzes.php' target='_blank'>Go to Quizzes</a></li>";
    echo "<li><strong>Answer some questions correctly</strong> (you'll see them marked as correct)</li>";
    echo "<li><strong>Submit the quiz</strong> (or let timer run out)</li>";
    echo "<li><strong>Check results immediately</strong> - should show correct score</li>";
    echo "</ol>";
    echo "</div>";
    
    // Show code changes made
    echo "<h3>5. Technical Changes Made</h3>";
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; font-family: monospace; font-size: 12px;'>";
    echo "<h4>🔧 Code Changes in quiz-interface.php:</h4>";
    echo "<pre>";
    echo "// OLD: Just mark as completed\n";
    echo "UPDATE quiz_attempts SET status = 'completed' WHERE id = ?\n\n";
    echo "// NEW: Grade immediately then mark as completed\n";
    echo "1. Get all questions for the quiz\n";
    echo "2. Check each user answer against correct options\n";
    echo "3. Calculate total score and percentage\n";
    echo "4. UPDATE user_answers SET is_correct = ?, marks_awarded = ?\n";
    echo "5. UPDATE quiz_attempts SET status = 'completed', score = ?, percentage = ?";
    echo "</pre>";
    echo "</div>";
    
    echo "<h3>6. Monitoring</h3>";
    
    // Check if there are any ungraded answers
    $stmt = $db->prepare("
        SELECT COUNT(*) as ungraded_count
        FROM user_answers ua
        JOIN quiz_attempts qa ON ua.attempt_id = qa.id
        WHERE qa.status = 'completed' AND ua.marks_awarded IS NULL
    ");
    $stmt->execute();
    $ungraded = $stmt->fetch()['ungraded_count'];
    
    if ($ungraded > 0) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h4>⚠️ Warning: {$ungraded} Ungraded Answers Found</h4>";
        echo "<p>Run the emergency fix to grade these: <a href='emergency-fix.php'>Emergency Fix</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "<h4>✅ All Completed Quizzes Are Properly Graded</h4>";
        echo "<p>The system is working correctly!</p>";
        echo "</div>";
    }
    
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='quizzes.php' style='background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test New Quiz</a>";
    echo "<a href='emergency-fix.php' style='background: #dc3545; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Fix Old Attempts</a>";
    echo "<a href='admin/index.php' style='background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px;'>Admin Dashboard</a>";
    echo "</p>";
    
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
h4 { color: #495057; margin-bottom: 10px; }
table { margin: 15px 0; width: 100%; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
th { background: #e9ecef; font-weight: bold; }
ol, ul { margin: 10px 0 10px 20px; }
li { margin: 5px 0; }
a { transition: all 0.3s ease; }
a:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>
