<?php
/**
 * Test Quiz Navigation - Debug Script
 * Use this to test if quiz navigation is working properly
 */

require_once 'config/config.php';

echo "<h2>Quiz Navigation Test</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";

try {
    $db = getDB();
    
    // Get a sample quiz with questions
    $stmt = $db->prepare("
        SELECT q.*, c.name as category_name 
        FROM quizzes q 
        LEFT JOIN categories c ON q.category_id = c.id 
        WHERE q.is_active = 1 
        ORDER BY q.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $quiz = $stmt->fetch();
    
    if ($quiz) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>✅ Sample Quiz Found</h3>";
        echo "<p><strong>Quiz:</strong> {$quiz['title']}</p>";
        echo "<p><strong>Category:</strong> {$quiz['category_name']}</p>";
        echo "<p><strong>Time Limit:</strong> {$quiz['time_limit']} minutes</p>";
        echo "</div>";
        
        // Get questions for this quiz
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
            ORDER BY q.order_number
        ");
        $stmt->execute([$quiz['id']]);
        $questions = $stmt->fetchAll();
        
        if ($questions) {
            echo "<div style='background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>✅ Questions Found: " . count($questions) . "</h3>";
            
            foreach ($questions as $index => $question) {
                echo "<div style='margin: 10px 0; padding: 10px; background: white; border-radius: 5px;'>";
                echo "<p><strong>Q" . ($index + 1) . ":</strong> " . htmlspecialchars(substr($question['question_text'], 0, 100)) . "...</p>";
                echo "<p><strong>Type:</strong> " . $question['question_type'] . " | <strong>Marks:</strong> " . $question['marks'] . "</p>";
                
                if ($question['options']) {
                    $options = explode('|', $question['options']);
                    echo "<p><strong>Options:</strong> " . count($options) . " options available</p>";
                }
                echo "</div>";
            }
            echo "</div>";
            
            // Test navigation
            echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>🧪 Navigation Test</h3>";
            echo "<p>The quiz navigation should work as follows:</p>";
            echo "<ul>";
            echo "<li>Click question numbers to jump between questions</li>";
            echo "<li>Use Previous/Next buttons to navigate sequentially</li>";
            echo "<li>Questions should hide/show properly</li>";
            echo "<li>Selected answers should be preserved</li>";
            echo "<li>Navigation buttons should update correctly</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "<div style='text-center; margin: 20px 0;'>";
            echo "<a href='take-quiz.php?id={$quiz['id']}' class='btn btn-primary' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Test This Quiz</a>";
            echo "<a href='quizzes.php' class='btn btn-secondary' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Browse All Quizzes</a>";
            echo "</div>";
            
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>❌ No Questions Found</h3>";
            echo "<p>This quiz doesn't have any questions yet.</p>";
            echo "<p><a href='add-sample-data.php'>Add Sample Questions</a></p>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>❌ No Quiz Found</h3>";
        echo "<p>No active quizzes found in the database.</p>";
        echo "<p><strong>To fix this:</strong></p>";
        echo "<ol>";
        echo "<li><a href='setup.php'>Run database setup</a></li>";
        echo "<li><a href='add-sample-data.php'>Add sample quizzes and questions</a></li>";
        echo "<li>Or create a quiz through the admin panel</li>";
        echo "</ol>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ Database Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please ensure the database is set up properly.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Navigation Fix Applied</h3>";
echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>✅ Fixed Issues:</strong></p>";
echo "<ul>";
echo "<li><strong>Question Display:</strong> Fixed selector to properly show/hide questions</li>";
echo "<li><strong>Navigation Buttons:</strong> Improved question navigation button functionality</li>";
echo "<li><strong>Answer Detection:</strong> Better detection of answered questions</li>";
echo "<li><strong>Smooth Scrolling:</strong> Added smooth scroll to question when navigating</li>";
echo "<li><strong>Error Prevention:</strong> Added null checks to prevent JavaScript errors</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4>What Was Fixed:</h4>";
echo "<ol>";
echo "<li><strong>Selector Issue:</strong> Changed from <code>[data-question=\"\${index}\"]</code> to <code>.question-slide[data-question=\"\${index}\"]</code></li>";
echo "<li><strong>Navigation Logic:</strong> Hide all questions first, then show target question</li>";
echo "<li><strong>Answer Detection:</strong> Improved logic to detect if questions are answered</li>";
echo "<li><strong>Null Safety:</strong> Added checks to prevent errors when elements don't exist</li>";
echo "</ol>";
echo "</div>";

echo "<p style='margin-top: 20px;'>";
echo "<a href='quizzes.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Quizzes</a>";
echo "<a href='add-sample-data.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Add Sample Data</a>";
echo "<a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Homepage</a>";
echo "</p>";

echo "</div>";
?>
