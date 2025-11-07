<?php
/**
 * Backend Quiz Results Processor
 * This script does the heavy lifting of calculating quiz results
 */

require_once 'config/config.php';
requireLogin();

header('Content-Type: application/json');

$attempt_id = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : 0;

if (!$attempt_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid attempt ID']);
    exit;
}

try {
    $db = getDB();
    
    // Verify this attempt belongs to the current user
    $stmt = $db->prepare("
        SELECT qa.*, q.title, q.total_marks
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.id = ? AND qa.user_id = ?
    ");
    $stmt->execute([$attempt_id, $_SESSION['user_id']]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) {
        echo json_encode(['success' => false, 'error' => 'Attempt not found']);
        exit;
    }
    
    // Add a small delay to show the loading process
    sleep(1);
    
    // STEP 1: Get all questions for this quiz
    $stmt = $db->prepare("
        SELECT q.id, q.marks, q.question_type
        FROM questions q
        WHERE q.quiz_id = ?
        ORDER BY q.order_number
    ");
    $stmt->execute([$attempt['quiz_id']]);
    $questions = $stmt->fetchAll();
    
    $total_score = 0;
    $correct_answers = 0;
    $total_questions = count($questions);
    
    // STEP 2: Process each question
    foreach ($questions as $question) {
        // Get user's answer for this question
        $stmt = $db->prepare("
            SELECT selected_option_id, answer_text
            FROM user_answers
            WHERE attempt_id = ? AND question_id = ?
        ");
        $stmt->execute([$attempt_id, $question['id']]);
        $user_answer = $stmt->fetch();
        
        $marks_awarded = 0;
        $is_correct = false;
        
        if ($user_answer) {
            if ($question['question_type'] == 'multiple_choice' && $user_answer['selected_option_id']) {
                // Check if selected option is correct
                $stmt = $db->prepare("
                    SELECT is_correct
                    FROM question_options
                    WHERE id = ? AND question_id = ?
                ");
                $stmt->execute([$user_answer['selected_option_id'], $question['id']]);
                $option = $stmt->fetch();
                
                if ($option && $option['is_correct'] == 1) {
                    $is_correct = true;
                    $marks_awarded = $question['marks'];
                    $correct_answers++;
                }
                
            } elseif ($question['question_type'] == 'true_false' && $user_answer['answer_text']) {
                // Check true/false answer
                $stmt = $db->prepare("
                    SELECT option_text
                    FROM question_options
                    WHERE question_id = ? AND is_correct = 1
                ");
                $stmt->execute([$question['id']]);
                $correct_option = $stmt->fetch();
                
                if ($correct_option) {
                    $correct_answer = strtolower($correct_option['option_text']) == 'true' ? 'true' : 'false';
                    if (strtolower($user_answer['answer_text']) == $correct_answer) {
                        $is_correct = true;
                        $marks_awarded = $question['marks'];
                        $correct_answers++;
                    }
                }
                
            } elseif ($question['question_type'] == 'short_answer' && $user_answer['answer_text']) {
                // Short answer questions need manual grading
                // For now, we'll leave them as 0 marks
                $marks_awarded = 0;
            }
            
            // Update user answer with grading results
            $stmt = $db->prepare("
                UPDATE user_answers
                SET is_correct = ?, marks_awarded = ?
                WHERE attempt_id = ? AND question_id = ?
            ");
            $stmt->execute([$is_correct ? 1 : 0, $marks_awarded, $attempt_id, $question['id']]);
            
            $total_score += $marks_awarded;
        }
    }
    
    // Add another small delay
    sleep(1);
    
    // STEP 3: Calculate final percentage and update attempt
    $percentage = $attempt['total_marks'] > 0 ? ($total_score / $attempt['total_marks']) * 100 : 0;
    
    // Update the quiz attempt with final results
    $stmt = $db->prepare("
        UPDATE quiz_attempts
        SET score = ?, percentage = ?, status = 'completed'
        WHERE id = ?
    ");
    $stmt->execute([$total_score, $percentage, $attempt_id]);
    
    // STEP 4: Calculate user's rank for this quiz
    $stmt = $db->prepare("
        SELECT COUNT(*) + 1 as user_rank
        FROM quiz_attempts
        WHERE quiz_id = ? AND status = 'completed' AND percentage > ?
    ");
    $stmt->execute([$attempt['quiz_id'], $percentage]);
    $rank_data = $stmt->fetch();
    $user_rank = $rank_data['user_rank'];
    
    // Final delay to complete the process
    sleep(1);
    
    // Return success with summary data
    echo json_encode([
        'success' => true,
        'data' => [
            'total_score' => $total_score,
            'total_marks' => $attempt['total_marks'],
            'percentage' => round($percentage, 1),
            'correct_answers' => $correct_answers,
            'total_questions' => $total_questions,
            'user_rank' => $user_rank,
            'quiz_title' => $attempt['title']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Quiz processing error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error occurred during processing'
    ]);
} catch (Exception $e) {
    error_log("Quiz processing error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'An error occurred during processing'
    ]);
}
?>
