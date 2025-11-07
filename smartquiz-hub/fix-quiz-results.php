<?php
/**
 * Fix Quiz Results - Recalculate all quiz attempt scores
 */

require_once 'config/config.php';
requireAdmin();

echo "<h2>Fix Quiz Results - Recalculate Scores</h2>";
echo "<p>This script will recalculate scores for all quiz attempts that have incorrect grading.</p>";

try {
    $db = getDB();
    
    // Get all completed attempts that might need fixing
    $stmt = $db->prepare("
        SELECT qa.id, qa.quiz_id, qa.score, qa.percentage, qa.total_marks, q.title, q.total_marks as correct_total_marks
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.status = 'completed'
        ORDER BY qa.id DESC
    ");
    $stmt->execute();
    $attempts = $stmt->fetchAll();
    
    echo "<h3>Found " . count($attempts) . " completed attempts</h3>";
    
    if (isset($_POST['fix_all'])) {
        echo "<h3>🔧 Fixing All Attempts...</h3>";
        
        $fixed_count = 0;
        
        foreach ($attempts as $attempt) {
            echo "<h4>Processing Attempt #{$attempt['id']} - {$attempt['title']}</h4>";
            
            // Get questions and user answers for this attempt
            $stmt = $db->prepare("
                SELECT q.*, ua.selected_option_id, ua.answer_text,
                       GROUP_CONCAT(
                           CONCAT(qo.id, ':', qo.option_text, ':', qo.is_correct) 
                           ORDER BY qo.option_order SEPARATOR '|'
                       ) as options
                FROM questions q
                LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.attempt_id = ?
                LEFT JOIN question_options qo ON q.id = qo.question_id
                WHERE q.quiz_id = ?
                GROUP BY q.id
                ORDER BY q.order_number
            ");
            $stmt->execute([$attempt['id'], $attempt['quiz_id']]);
            $questions = $stmt->fetchAll();
            
            $total_score = 0;
            $answers_updated = 0;
            
            foreach ($questions as $question) {
                $marks_awarded = 0;
                $is_correct = false;
                
                // Only process if there's an answer
                if ($question['selected_option_id'] || $question['answer_text']) {
                    
                    if ($question['question_type'] == 'multiple_choice' && $question['selected_option_id']) {
                        // Check if selected option is correct
                        if ($question['options']) {
                            foreach (explode('|', $question['options']) as $option_data) {
                                $parts = explode(':', $option_data, 3);
                                if (count($parts) >= 3 && $parts[0] == $question['selected_option_id']) {
                                    if ($parts[2] == '1') {
                                        $is_correct = true;
                                        $marks_awarded = (int)$question['marks'];
                                    }
                                    break;
                                }
                            }
                        }
                    } elseif ($question['question_type'] == 'true_false' && $question['answer_text']) {
                        // For true/false questions
                        if ($question['options']) {
                            foreach (explode('|', $question['options']) as $option_data) {
                                $parts = explode(':', $option_data, 3);
                                if (count($parts) >= 3 && $parts[2] == '1') {
                                    $correct_answer = strtolower($parts[1]) == 'true' ? 'true' : 'false';
                                    if (strtolower($question['answer_text']) == $correct_answer) {
                                        $is_correct = true;
                                        $marks_awarded = (int)$question['marks'];
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Update user answer
                    $stmt = $db->prepare("
                        UPDATE user_answers 
                        SET is_correct = ?, marks_awarded = ?
                        WHERE attempt_id = ? AND question_id = ?
                    ");
                    $stmt->execute([$is_correct ? 1 : 0, $marks_awarded, $attempt['id'], $question['id']]);
                    $answers_updated++;
                }
                
                $total_score += $marks_awarded;
            }
            
            // Calculate percentage
            $correct_total_marks = $attempt['correct_total_marks'];
            $percentage = $correct_total_marks > 0 ? ($total_score / $correct_total_marks) * 100 : 0;
            
            // Update quiz attempt
            $stmt = $db->prepare("
                UPDATE quiz_attempts 
                SET score = ?, percentage = ?, total_marks = ?
                WHERE id = ?
            ");
            $stmt->execute([$total_score, $percentage, $correct_total_marks, $attempt['id']]);
            
            echo "<p>✅ Updated: {$answers_updated} answers, Score: {$total_score}/{$correct_total_marks} ({$percentage}%)</p>";
            $fixed_count++;
        }
        
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>🎉 All Done!</h4>";
        echo "<p>Fixed {$fixed_count} quiz attempts.</p>";
        echo "<p><a href='quiz-result.php?attempt_id=2' target='_blank'>Check Attempt #2 Result</a></p>";
        echo "</div>";
        
    } else {
        // Show preview of what will be fixed
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th>Attempt ID</th><th>Quiz</th><th>Current Score</th><th>Current %</th><th>Current Total</th><th>Correct Total</th><th>Status</th>";
        echo "</tr>";
        
        $needs_fixing = 0;
        
        foreach ($attempts as $attempt) {
            $status = "OK";
            $style = "";
            
            // Check if this attempt needs fixing
            if ($attempt['total_marks'] != $attempt['correct_total_marks']) {
                $status = "Wrong Total Marks";
                $style = "background: #fff3cd;";
                $needs_fixing++;
            } elseif ($attempt['score'] == 0 && $attempt['percentage'] == 0) {
                $status = "Zero Score - Needs Check";
                $style = "background: #f8d7da;";
                $needs_fixing++;
            }
            
            echo "<tr style='{$style}'>";
            echo "<td>{$attempt['id']}</td>";
            echo "<td>" . htmlspecialchars($attempt['title']) . "</td>";
            echo "<td>{$attempt['score']}</td>";
            echo "<td>" . number_format($attempt['percentage'], 1) . "%</td>";
            echo "<td>{$attempt['total_marks']}</td>";
            echo "<td>{$attempt['correct_total_marks']}</td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>📊 Summary</h4>";
        echo "<p><strong>Total Attempts:</strong> " . count($attempts) . "</p>";
        echo "<p><strong>Need Fixing:</strong> {$needs_fixing}</p>";
        echo "</div>";
        
        if ($needs_fixing > 0) {
            echo "<form method='POST'>";
            echo "<input type='hidden' name='fix_all' value='1'>";
            echo "<button type='submit' style='background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px;'>";
            echo "🔧 Fix All {$needs_fixing} Attempts";
            echo "</button>";
            echo "</form>";
            
            echo "<p style='margin-top: 20px;'>";
            echo "<a href='debug-attempt.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Debug Specific Attempt</a>";
            echo "</p>";
        } else {
            echo "<p style='color: #28a745; font-weight: bold;'>✅ All attempts look good!</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ Error!</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; line-height: 1.6; }
h2 { color: #007bff; margin-bottom: 20px; }
h3 { color: #28a745; margin-top: 30px; }
h4 { color: #495057; margin-bottom: 10px; }
table { width: 100%; }
th, td { padding: 10px; text-align: left; border: 1px solid #dee2e6; }
th { background: #e9ecef; font-weight: bold; }
button { cursor: pointer; transition: all 0.3s ease; }
button:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
a { transition: all 0.3s ease; }
a:hover { transform: translateY(-1px); }
</style>
