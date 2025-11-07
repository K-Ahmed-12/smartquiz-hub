<?php
/**
 * Main Configuration File for SmartQuiz Hub
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration first
require_once __DIR__ . '/database.php';

// Include settings functions
require_once __DIR__ . '/settings.php';

// Site Configuration - Now loaded from database settings
// These are fallback constants if settings system fails
define('SITE_NAME', getSetting('site_name', 'SmartQuiz Hub'));
define('SITE_TAGLINE', getSetting('site_tagline', 'Test Your Knowledge'));
define('SITE_URL', getSetting('site_url', 'http://localhost/smartquiz-hub'));

// Email Configuration
define('ADMIN_EMAIL', getSetting('admin_email', 'admin@smartquizhub.com'));
define('CONTACT_EMAIL', getSetting('contact_email', 'contact@smartquizhub.com'));

// Session Configuration
define('SESSION_TIMEOUT', getSessionTimeout());

// Pagination
define('ITEMS_PER_PAGE', getPaginationLimit());
define('LEADERBOARD_LIMIT', getLeaderboardLimit());

// File Upload Configuration
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Security Configuration
define('PASSWORD_MIN_LENGTH', 6);

// Email Configuration (for password reset)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', ''); // Add your email
define('SMTP_PASSWORD', ''); // Add your app password
define('FROM_EMAIL', 'noreply@smartquizhub.com');
define('FROM_NAME', 'SmartQuiz Hub');

// Pagination constants defined above using settings

// Quiz Configuration
define('DEFAULT_QUIZ_TIME', 30); // minutes
define('AUTO_SAVE_INTERVAL', 30); // seconds

// Database and settings already included above

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'instructor');
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
    
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/dashboard.php');
        exit();
    }
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    } else {
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function showAlert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        echo "<div class='alert alert-{$alert['type']} alert-dismissible fade show' role='alert'>
                {$alert['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        unset($_SESSION['alert']);
    }
}

// Check session timeout
if (isLoggedIn() && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_destroy();
        redirect('login.php?timeout=1');
    }
}

// Update last activity
if (isLoggedIn()) {
    $_SESSION['last_activity'] = time();
}

// Helper function to update quiz total marks
function updateQuizTotalMarks($quiz_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE quizzes 
            SET total_marks = (
                SELECT COALESCE(SUM(marks), 0) 
                FROM questions 
                WHERE quiz_id = ?
            )
            WHERE id = ?
        ");
        $stmt->execute([$quiz_id, $quiz_id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Helper function to recalculate quiz attempt percentage
function recalculateAttemptPercentage($attempt_id) {
    try {
        $db = getDB();
        
        // Get attempt and quiz details
        $stmt = $db->prepare("
            SELECT qa.score, q.total_marks 
            FROM quiz_attempts qa 
            JOIN quizzes q ON qa.quiz_id = q.id 
            WHERE qa.id = ?
        ");
        $stmt->execute([$attempt_id]);
        $data = $stmt->fetch();
        
        if ($data) {
            $percentage = $data['total_marks'] > 0 ? ($data['score'] / $data['total_marks']) * 100 : 0;
            
            $stmt = $db->prepare("
                UPDATE quiz_attempts 
                SET percentage = ?, total_marks = ? 
                WHERE id = ?
            ");
            $stmt->execute([$percentage, $data['total_marks'], $attempt_id]);
            return true;
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}
?>
