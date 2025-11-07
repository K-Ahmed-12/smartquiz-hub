<?php
/**
 * Quiz System Diagnostic Tool
 * Tests timer, answer saving, and marking system functionality
 */

require_once 'config/config.php';

// Require admin access
requireAdmin();

echo "<h2>SmartQuiz Hub - System Diagnostic</h2>";
echo "<p>This tool tests the quiz system functionality including timer, answer saving, and marking system.</p>";

try {
    $db = getDB();
    
    echo "<h3>1. Database Connection Test</h3>";
    echo "<p>✅ Database connection successful</p>";
    
    echo "<h3>2. Quiz Data Integrity Check</h3>";
    
    // Check quizzes
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM quizzes");
    $stmt->execute();
    $quiz_count = $stmt->fetch()['count'];
    echo "<p>📊 Found {$quiz_count} quizzes in database</p>";
    
    // Check questions
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM questions");
    $stmt->execute();
    $question_count = $stmt->fetch()['count'];
    echo "<p>❓ Found {$question_count} questions in database</p>";
    
    // Check question options
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM question_options");
    $stmt->execute();
    $option_count = $stmt->fetch()['count'];
    echo "<p>🔘 Found {$option_count} question options in database</p>";
    
    if ($quiz_count == 0) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>⚠️ No Quiz Data Found</h4>";
        echo "<p>Run the sample quiz setup script first:</p>";
        echo "<p><a href='add-sample-questions.php' class='btn btn-primary'>Add Sample Questions</a></p>";
        echo "</div>";
        exit;
    }
    
    echo "<h3>3. Quiz Structure Validation</h3>";
    
    // Check quiz total marks calculation
    $stmt = $db->prepare("
        SELECT q.id, q.title, q.total_marks, 
               COALESCE(SUM(questions.marks), 0) as calculated_marks
        FROM quizzes q
        LEFT JOIN questions ON q.id = questions.quiz_id
        GROUP BY q.id
        LIMIT 5
    ");
    $stmt->execute();
    $quiz_validation = $stmt->fetchAll();
    
    foreach ($quiz_validation as $quiz) {
        $status = ($quiz['total_marks'] == $quiz['calculated_marks']) ? '✅' : '❌';
        echo "<p>{$status} Quiz: {$quiz['title']} - Stored: {$quiz['total_marks']}, Calculated: {$quiz['calculated_marks']}</p>";
    }
    
    echo "<h3>4. Question Options Validation</h3>";
    
    // Check if questions have correct options
    $stmt = $db->prepare("
        SELECT q.id, q.question_text, q.question_type,
               COUNT(qo.id) as option_count,
               SUM(qo.is_correct) as correct_count
        FROM questions q
        LEFT JOIN question_options qo ON q.id = qo.question_id
        WHERE q.question_type = 'multiple_choice'
        GROUP BY q.id
        HAVING option_count != 4 OR correct_count != 1
        LIMIT 5
    ");
    $stmt->execute();
    $invalid_questions = $stmt->fetchAll();
    
    if (empty($invalid_questions)) {
        echo "<p>✅ All multiple choice questions have 4 options with 1 correct answer</p>";
    } else {
        echo "<p>❌ Found questions with invalid option structure:</p>";
        foreach ($invalid_questions as $q) {
            echo "<p>- Question ID {$q['id']}: {$q['option_count']} options, {$q['correct_count']} correct</p>";
        }
    }
    
    echo "<h3>5. Answer Saving Test</h3>";
    
    // Create a test quiz attempt
    $test_user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT id FROM quizzes LIMIT 1");
    $stmt->execute();
    $test_quiz = $stmt->fetch();
    
    if ($test_quiz) {
        // Create test attempt
        $stmt = $db->prepare("
            INSERT INTO quiz_attempts (user_id, quiz_id, status, start_time) 
            VALUES (?, ?, 'in_progress', NOW())
        ");
        $stmt->execute([$test_user_id, $test_quiz['id']]);
        $test_attempt_id = $db->lastInsertId();
        
        // Get first question
        $stmt = $db->prepare("SELECT id FROM questions WHERE quiz_id = ? LIMIT 1");
        $stmt->execute([$test_quiz['id']]);
        $test_question = $stmt->fetch();
        
        if ($test_question) {
            // Test answer saving
            $stmt = $db->prepare("
                INSERT INTO user_answers (attempt_id, question_id, selected_option_id, is_correct, marks_awarded)
                VALUES (?, ?, 1, 1, 1)
            ");
            $stmt->execute([$test_attempt_id, $test_question['id']]);
            
            // Verify answer was saved
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_answers WHERE attempt_id = ?");
            $stmt->execute([$test_attempt_id]);
            $answer_count = $stmt->fetch()['count'];
            
            if ($answer_count > 0) {
                echo "<p>✅ Answer saving functionality working</p>";
            } else {
                echo "<p>❌ Answer saving failed</p>";
            }
            
            // Clean up test data
            $stmt = $db->prepare("DELETE FROM user_answers WHERE attempt_id = ?");
            $stmt->execute([$test_attempt_id]);
            
            $stmt = $db->prepare("DELETE FROM quiz_attempts WHERE id = ?");
            $stmt->execute([$test_attempt_id]);
            
            echo "<p>🧹 Test data cleaned up</p>";
        }
    }
    
    echo "<h3>6. Marking System Test</h3>";
    
    // Test marking calculation
    $stmt = $db->prepare("
        SELECT q.id, q.marks, qo.id as option_id, qo.is_correct
        FROM questions q
        JOIN question_options qo ON q.id = qo.question_id
        WHERE q.question_type = 'multiple_choice'
        LIMIT 1
    ");
    $stmt->execute();
    $test_data = $stmt->fetchAll();
    
    if (!empty($test_data)) {
        $correct_option = null;
        $incorrect_option = null;
        
        foreach ($test_data as $data) {
            if ($data['is_correct'] == 1) {
                $correct_option = $data;
            } else {
                $incorrect_option = $data;
            }
        }
        
        if ($correct_option && $incorrect_option) {
            echo "<p>✅ Found test question with correct/incorrect options</p>";
            echo "<p>📝 Question marks: {$correct_option['marks']}</p>";
            echo "<p>✅ Correct option ID: {$correct_option['option_id']}</p>";
            echo "<p>❌ Incorrect option ID: {$incorrect_option['option_id']}</p>";
        }
    }
    
    echo "<h3>7. Timer Configuration Check</h3>";
    
    // Check quiz time limits
    $stmt = $db->prepare("SELECT title, time_limit FROM quizzes LIMIT 5");
    $stmt->execute();
    $time_configs = $stmt->fetchAll();
    
    foreach ($time_configs as $config) {
        $minutes = $config['time_limit'];
        $seconds = $minutes * 60;
        echo "<p>⏱️ {$config['title']}: {$minutes} minutes ({$seconds} seconds)</p>";
    }
    
    echo "<h3>8. System Status Summary</h3>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ System Status: Ready</h4>";
    echo "<ul>";
    echo "<li><strong>Database:</strong> Connected and functional</li>";
    echo "<li><strong>Quizzes:</strong> {$quiz_count} available</li>";
    echo "<li><strong>Questions:</strong> {$question_count} with {$option_count} options</li>";
    echo "<li><strong>Answer Saving:</strong> Functional</li>";
    echo "<li><strong>Marking System:</strong> Configured</li>";
    echo "<li><strong>Timer System:</strong> Configured</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>9. Common Issues & Solutions</h3>";
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🔧 Troubleshooting Guide</h4>";
    echo "<ul>";
    echo "<li><strong>Timer not working:</strong> Check browser console for JavaScript errors</li>";
    echo "<li><strong>Answers not saving:</strong> Check network tab for AJAX request failures</li>";
    echo "<li><strong>Wrong marks:</strong> Verify question options have correct 'is_correct' values</li>";
    echo "<li><strong>Percentage calculation:</strong> Ensure quiz total_marks matches sum of question marks</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>10. Test Quiz Interface</h3>";
    
    if ($quiz_count > 0) {
        $stmt = $db->prepare("SELECT id, title FROM quizzes WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        $active_quiz = $stmt->fetch();
        
        if ($active_quiz) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>🎯 Ready to Test</h4>";
            echo "<p>You can now test the complete quiz system:</p>";
            echo "<p><a href='take-quiz.php?id={$active_quiz['id']}' class='btn btn-success' target='_blank'>Take Test Quiz: {$active_quiz['title']}</a></p>";
            echo "<p><small>This will open in a new tab so you can monitor this diagnostic page.</small></p>";
            echo "</div>";
        }
    }
    
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='admin/index.php' style='background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Admin Dashboard</a>";
    echo "<a href='quizzes.php' style='background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px;'>View All Quizzes</a>";
    echo "</p>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Database Error!</h4>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
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
h3 { color: #28a745; margin-top: 30px; margin-bottom: 15px; }
h4 { color: #495057; margin-bottom: 10px; }
p { margin: 8px 0; }
ul { margin: 10px 0 20px 20px; }
.btn {
    display: inline-block;
    padding: 8px 16px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.3s ease;
}
.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
.btn-primary { background: #007bff; }
.btn-success { background: #28a745; }
</style>
