<?php
/**
 * Database Maintenance Script
 * This script ensures data consistency and fixes common issues
 * Run this periodically or after major changes
 */

require_once 'config/config.php';

// Only allow admin access
requireAdmin();

echo "<h2>🔧 Database Maintenance</h2>";

try {
    $db = getDB();
    $fixes_applied = 0;
    
    echo "<h3>1. Fixing Quiz Total Marks</h3>";
    
    // Update all quiz total marks to match sum of question marks
    $stmt = $db->prepare("
        UPDATE quizzes q
        SET total_marks = (
            SELECT COALESCE(SUM(marks), 0)
            FROM questions
            WHERE quiz_id = q.id
        )
    ");
    $stmt->execute();
    $quiz_fixes = $stmt->rowCount();
    echo "<p>✅ Updated {$quiz_fixes} quizzes with correct total marks</p>";
    $fixes_applied += $quiz_fixes;
    
    echo "<h3>2. Fixing Quiz Attempt Total Marks</h3>";
    
    // Update quiz attempts to have correct total marks
    $stmt = $db->prepare("
        UPDATE quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        SET qa.total_marks = q.total_marks
        WHERE qa.total_marks != q.total_marks OR qa.total_marks IS NULL
    ");
    $stmt->execute();
    $attempt_total_fixes = $stmt->rowCount();
    echo "<p>✅ Updated {$attempt_total_fixes} quiz attempts with correct total marks</p>";
    $fixes_applied += $attempt_total_fixes;
    
    echo "<h3>3. Re-grading Quiz Attempts</h3>";
    
    // Get all completed attempts that might need re-grading
    $stmt = $db->prepare("
        SELECT qa.id, qa.quiz_id, qa.score, qa.percentage, q.total_marks
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.status = 'completed'
        ORDER BY qa.id DESC
    ");
    $stmt->execute();
    $attempts = $stmt->fetchAll();
    
    $regraded_count = 0;
    
    foreach ($attempts as $attempt) {
        // Get user answers for this attempt
        $stmt = $db->prepare("
            SELECT ua.question_id, ua.selected_option_id, ua.answer_text, ua.marks_awarded,
                   q.marks as question_marks, q.question_type
            FROM user_answers ua
            JOIN questions q ON ua.question_id = q.id
            WHERE ua.attempt_id = ?
        ");
        $stmt->execute([$attempt['id']]);
        $user_answers = $stmt->fetchAll();
        
        $calculated_score = 0;
        $needs_update = false;
        
        foreach ($user_answers as $answer) {
            $correct_marks = 0;
            
            if ($answer['question_type'] == 'multiple_choice' && $answer['selected_option_id']) {
                // Check if selected option is correct
                $stmt = $db->prepare("
                    SELECT is_correct 
                    FROM question_options 
                    WHERE id = ? AND question_id = ?
                ");
                $stmt->execute([$answer['selected_option_id'], $answer['question_id']]);
                $option = $stmt->fetch();
                
                if ($option && $option['is_correct'] == 1) {
                    $correct_marks = $answer['question_marks'];
                }
            } elseif ($answer['question_type'] == 'true_false' && $answer['answer_text']) {
                // Check true/false answer
                $stmt = $db->prepare("
                    SELECT option_text 
                    FROM question_options 
                    WHERE question_id = ? AND is_correct = 1
                ");
                $stmt->execute([$answer['question_id']]);
                $correct_option = $stmt->fetch();
                
                if ($correct_option) {
                    $correct_answer = strtolower($correct_option['option_text']) == 'true' ? 'true' : 'false';
                    if (strtolower($answer['answer_text']) == $correct_answer) {
                        $correct_marks = $answer['question_marks'];
                    }
                }
            }
            
            $calculated_score += $correct_marks;
            
            // Update user answer if marks are wrong
            if ($answer['marks_awarded'] != $correct_marks) {
                $stmt = $db->prepare("
                    UPDATE user_answers 
                    SET marks_awarded = ?, is_correct = ?
                    WHERE attempt_id = ? AND question_id = ?
                ");
                $stmt->execute([
                    $correct_marks, 
                    $correct_marks > 0 ? 1 : 0, 
                    $attempt['id'], 
                    $answer['question_id']
                ]);
                $needs_update = true;
            }
        }
        
        // Update attempt if score is wrong
        $correct_percentage = $attempt['total_marks'] > 0 ? ($calculated_score / $attempt['total_marks']) * 100 : 0;
        
        if ($attempt['score'] != $calculated_score || abs($attempt['percentage'] - $correct_percentage) > 0.1) {
            $stmt = $db->prepare("
                UPDATE quiz_attempts 
                SET score = ?, percentage = ?
                WHERE id = ?
            ");
            $stmt->execute([$calculated_score, $correct_percentage, $attempt['id']]);
            $needs_update = true;
        }
        
        if ($needs_update) {
            $regraded_count++;
        }
    }
    
    echo "<p>✅ Re-graded {$regraded_count} quiz attempts</p>";
    $fixes_applied += $regraded_count;
    
    echo "<h3>4. Cleaning Up Orphaned Records</h3>";
    
    // Remove user answers for non-existent attempts
    $stmt = $db->prepare("
        DELETE ua FROM user_answers ua
        LEFT JOIN quiz_attempts qa ON ua.attempt_id = qa.id
        WHERE qa.id IS NULL
    ");
    $stmt->execute();
    $orphaned_answers = $stmt->rowCount();
    echo "<p>✅ Removed {$orphaned_answers} orphaned user answers</p>";
    $fixes_applied += $orphaned_answers;
    
    // Remove question options for non-existent questions
    $stmt = $db->prepare("
        DELETE qo FROM question_options qo
        LEFT JOIN questions q ON qo.question_id = q.id
        WHERE q.id IS NULL
    ");
    $stmt->execute();
    $orphaned_options = $stmt->rowCount();
    echo "<p>✅ Removed {$orphaned_options} orphaned question options</p>";
    $fixes_applied += $orphaned_options;
    
    echo "<h3>5. Data Integrity Checks</h3>";
    
    // Check for questions without correct answers
    $stmt = $db->prepare("
        SELECT q.id, q.question_text, q.question_type
        FROM questions q
        LEFT JOIN question_options qo ON q.id = qo.question_id AND qo.is_correct = 1
        WHERE q.question_type IN ('multiple_choice', 'true_false') AND qo.id IS NULL
    ");
    $stmt->execute();
    $questions_without_correct = $stmt->fetchAll();
    
    if (empty($questions_without_correct)) {
        echo "<p>✅ All questions have correct answers</p>";
    } else {
        echo "<p>⚠️ Found " . count($questions_without_correct) . " questions without correct answers:</p>";
        foreach ($questions_without_correct as $q) {
            echo "<p>- Question {$q['id']}: " . substr($q['question_text'], 0, 50) . "...</p>";
        }
    }
    
    // Check for multiple choice questions with wrong number of options
    $stmt = $db->prepare("
        SELECT q.id, q.question_text, COUNT(qo.id) as option_count
        FROM questions q
        LEFT JOIN question_options qo ON q.id = qo.question_id
        WHERE q.question_type = 'multiple_choice'
        GROUP BY q.id
        HAVING option_count != 4
    ");
    $stmt->execute();
    $questions_wrong_options = $stmt->fetchAll();
    
    if (empty($questions_wrong_options)) {
        echo "<p>✅ All multiple choice questions have 4 options</p>";
    } else {
        echo "<p>⚠️ Found " . count($questions_wrong_options) . " questions with wrong number of options:</p>";
        foreach ($questions_wrong_options as $q) {
            echo "<p>- Question {$q['id']}: {$q['option_count']} options</p>";
        }
    }
    
    echo "<h3>📊 Maintenance Summary</h3>";
    
    if ($fixes_applied > 0) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h4>🎉 Maintenance Complete!</h4>";
        echo "<p><strong>Total fixes applied:</strong> {$fixes_applied}</p>";
        echo "<p>Your database is now optimized and consistent.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h4>✅ Database is Healthy!</h4>";
        echo "<p>No issues found. Your database is already in good condition.</p>";
        echo "</div>";
    }
    
    // Show current statistics
    echo "<h3>📈 Current Statistics</h3>";
    
    $stats = [];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM quizzes WHERE is_active = 1");
    $stats['active_quizzes'] = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM questions");
    $stats['total_questions'] = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM quiz_attempts WHERE status = 'completed'");
    $stats['completed_attempts'] = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'instructor')");
    $stats['admin_users'] = $stmt->fetch()['count'];
    
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;'>";
    echo "<h4 style='color: #1976d2; margin: 0;'>{$stats['active_quizzes']}</h4>";
    echo "<p style='margin: 5px 0 0 0;'>Active Quizzes</p>";
    echo "</div>";
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; text-align: center;'>";
    echo "<h4 style='color: #388e3c; margin: 0;'>{$stats['total_questions']}</h4>";
    echo "<p style='margin: 5px 0 0 0;'>Total Questions</p>";
    echo "</div>";
    
    echo "<div style='background: #fff3e0; padding: 15px; border-radius: 8px; text-align: center;'>";
    echo "<h4 style='color: #f57c00; margin: 0;'>{$stats['completed_attempts']}</h4>";
    echo "<p style='margin: 5px 0 0 0;'>Completed Attempts</p>";
    echo "</div>";
    
    echo "<div style='background: #fce4ec; padding: 15px; border-radius: 8px; text-align: center;'>";
    echo "<h4 style='color: #c2185b; margin: 0;'>{$stats['admin_users']}</h4>";
    echo "<p style='margin: 5px 0 0 0;'>Admin Users</p>";
    echo "</div>";
    
    echo "</div>";
    
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='admin/index.php' style='background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Admin Dashboard</a>";
    echo "<a href='quiz-result.php?attempt_id=5' style='background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px;'>Test Fixed Results</a>";
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
p { margin: 8px 0; }
a { transition: all 0.3s ease; }
a:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
</style>
