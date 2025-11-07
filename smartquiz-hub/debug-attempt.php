<?php
/**
 * Debug Quiz Attempt - Check what's wrong with attempt_id=2
 */

require_once 'config/config.php';
requireAdmin();

$attempt_id = 2; // The problematic attempt

echo "<h2>Debug Quiz Attempt ID: {$attempt_id}</h2>";

try {
    $db = getDB();
    
    echo "<h3>1. Quiz Attempt Details</h3>";
    $stmt = $db->prepare("
        SELECT qa.*, q.title, q.total_marks as quiz_total_marks
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.id = ?
    ");
    $stmt->execute([$attempt_id]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) {
        echo "<p>❌ Attempt not found!</p>";
        exit;
    }
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    foreach ($attempt as $key => $value) {
        echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>2. User Answers</h3>";
    $stmt = $db->prepare("
        SELECT ua.*, q.question_text, q.marks as question_marks
        FROM user_answers ua
        JOIN questions q ON ua.question_id = q.id
        WHERE ua.attempt_id = ?
        ORDER BY q.order_number
    ");
    $stmt->execute([$attempt_id]);
    $user_answers = $stmt->fetchAll();
    
    if (empty($user_answers)) {
        echo "<p>❌ No user answers found!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Question</th><th>Answer Text</th><th>Selected Option ID</th><th>Is Correct</th><th>Marks Awarded</th><th>Question Marks</th></tr>";
        foreach ($user_answers as $answer) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars(substr($answer['question_text'], 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars($answer['answer_text']) . "</td>";
            echo "<td>" . htmlspecialchars($answer['selected_option_id']) . "</td>";
            echo "<td>" . ($answer['is_correct'] ? 'YES' : 'NO') . "</td>";
            echo "<td>" . htmlspecialchars($answer['marks_awarded']) . "</td>";
            echo "<td>" . htmlspecialchars($answer['question_marks']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>3. Question Options Analysis</h3>";
    $stmt = $db->prepare("
        SELECT q.id as question_id, q.question_text, q.marks,
               qo.id as option_id, qo.option_text, qo.is_correct,
               ua.selected_option_id, ua.is_correct as user_is_correct, ua.marks_awarded
        FROM questions q
        JOIN question_options qo ON q.id = qo.question_id
        LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.attempt_id = ?
        WHERE q.quiz_id = ?
        ORDER BY q.order_number, qo.option_order
    ");
    $stmt->execute([$attempt_id, $attempt['quiz_id']]);
    $question_options = $stmt->fetchAll();
    
    $current_question = null;
    foreach ($question_options as $row) {
        if ($current_question != $row['question_id']) {
            if ($current_question !== null) echo "</table><br>";
            $current_question = $row['question_id'];
            echo "<h4>Question {$row['question_id']}: " . htmlspecialchars(substr($row['question_text'], 0, 60)) . "...</h4>";
            echo "<p><strong>Question Marks:</strong> {$row['marks']}</p>";
            echo "<p><strong>User Selected:</strong> {$row['selected_option_id']}</p>";
            echo "<p><strong>User Correct:</strong> " . ($row['user_is_correct'] ? 'YES' : 'NO') . "</p>";
            echo "<p><strong>Marks Awarded:</strong> {$row['marks_awarded']}</p>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Option ID</th><th>Option Text</th><th>Is Correct</th><th>Selected?</th></tr>";
        }
        
        $selected = ($row['option_id'] == $row['selected_option_id']) ? 'YES' : 'NO';
        $correct_class = $row['is_correct'] ? 'style="background: #d4edda;"' : '';
        $selected_class = ($row['option_id'] == $row['selected_option_id']) ? 'style="background: #fff3cd;"' : '';
        
        echo "<tr {$correct_class} {$selected_class}>";
        echo "<td>{$row['option_id']}</td>";
        echo "<td>" . htmlspecialchars($row['option_text']) . "</td>";
        echo "<td>" . ($row['is_correct'] ? 'YES' : 'NO') . "</td>";
        echo "<td>{$selected}</td>";
        echo "</tr>";
    }
    if ($current_question !== null) echo "</table>";
    
    echo "<h3>4. Manual Recalculation</h3>";
    
    // Manual recalculation
    $stmt = $db->prepare("
        SELECT q.*, ua.selected_option_id,
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
    $stmt->execute([$attempt_id, $attempt['quiz_id']]);
    $questions_for_grading = $stmt->fetchAll();
    
    $manual_total = 0;
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Question</th><th>Selected Option</th><th>Correct Option</th><th>Is Correct?</th><th>Marks</th></tr>";
    
    foreach ($questions_for_grading as $q) {
        $marks_awarded = 0;
        $is_correct = false;
        $correct_option_id = null;
        $correct_option_text = '';
        
        if ($q['options']) {
            foreach (explode('|', $q['options']) as $option_data) {
                $parts = explode(':', $option_data, 3);
                if (count($parts) >= 3) {
                    if ($parts[2] == '1') {
                        $correct_option_id = $parts[0];
                        $correct_option_text = $parts[1];
                    }
                    if ($parts[0] == $q['selected_option_id'] && $parts[2] == '1') {
                        $is_correct = true;
                        $marks_awarded = (int)$q['marks'];
                    }
                }
            }
        }
        
        $manual_total += $marks_awarded;
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars(substr($q['question_text'], 0, 40)) . "...</td>";
        echo "<td>ID: {$q['selected_option_id']}</td>";
        echo "<td>ID: {$correct_option_id} ({$correct_option_text})</td>";
        echo "<td>" . ($is_correct ? 'YES' : 'NO') . "</td>";
        echo "<td>{$marks_awarded}/{$q['marks']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h4>Manual Calculation Result:</h4>";
    echo "<p><strong>Total Score:</strong> {$manual_total}/{$attempt['quiz_total_marks']}</p>";
    echo "<p><strong>Percentage:</strong> " . (($manual_total / $attempt['quiz_total_marks']) * 100) . "%</p>";
    
    echo "<h3>5. Fix the Grading</h3>";
    
    echo "<form method='POST'>";
    echo "<input type='hidden' name='fix_grading' value='1'>";
    echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Fix This Attempt's Grading</button>";
    echo "</form>";
    
    if (isset($_POST['fix_grading'])) {
        echo "<h4>Fixing grading...</h4>";
        
        // Re-grade this specific attempt
        $total_score = 0;
        
        foreach ($questions_for_grading as $question) {
            $marks_awarded = 0;
            $is_correct = false;
            
            if ($question['selected_option_id']) {
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
                
                // Update user answer
                $stmt = $db->prepare("
                    UPDATE user_answers 
                    SET is_correct = ?, marks_awarded = ?
                    WHERE attempt_id = ? AND question_id = ?
                ");
                $stmt->execute([$is_correct ? 1 : 0, $marks_awarded, $attempt_id, $question['id']]);
                
                $total_score += $marks_awarded;
            }
        }
        
        // Update quiz attempt
        $percentage = ($total_score / $attempt['quiz_total_marks']) * 100;
        $stmt = $db->prepare("
            UPDATE quiz_attempts 
            SET score = ?, percentage = ?, total_marks = ?
            WHERE id = ?
        ");
        $stmt->execute([$total_score, $percentage, $attempt['quiz_total_marks'], $attempt_id]);
        
        echo "<p>✅ <strong>Fixed!</strong> New score: {$total_score}/{$attempt['quiz_total_marks']} ({$percentage}%)</p>";
        echo "<p><a href='quiz-result.php?attempt_id={$attempt_id}' target='_blank'>View Updated Result</a></p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background: #e9ecef; }
h2 { color: #007bff; }
h3 { color: #28a745; margin-top: 30px; }
h4 { color: #495057; }
</style>
