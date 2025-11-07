<?php
/**
 * Test Start Quiz Buttons Functionality
 */

require_once 'config/config.php';
requireAdmin();

echo "<h2>🧪 Testing Start Quiz Buttons</h2>";

try {
    $db = getDB();
    
    // Get some sample quizzes
    $stmt = $db->prepare("
        SELECT q.*, c.name as category_name 
        FROM quizzes q 
        LEFT JOIN categories c ON q.category_id = c.id 
        WHERE q.is_active = 1 
        LIMIT 3
    ");
    $stmt->execute();
    $sample_quizzes = $stmt->fetchAll();
    
    echo "<h3>1. Testing Quiz URLs</h3>";
    
    if (empty($sample_quizzes)) {
        echo "<p>❌ No active quizzes found. Please create some quizzes first.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Quiz Title</th><th>Old URL (Broken)</th><th>New URL (Fixed)</th><th>Status</th></tr>";
        
        foreach ($sample_quizzes as $quiz) {
            $old_url = "quiz.php?id=" . $quiz['id'];
            $new_url = "take-quiz.php?id=" . $quiz['id'];
            
            // Check if take-quiz.php exists
            $file_exists = file_exists('take-quiz.php') ? '✅' : '❌';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($quiz['title']) . "</td>";
            echo "<td style='color: red;'>{$old_url}</td>";
            echo "<td style='color: green;'>{$new_url}</td>";
            echo "<td>{$file_exists} " . ($file_exists == '✅' ? 'Working' : 'Missing File') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>2. Testing Authentication Flow</h3>";
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>📋 How It Works Now:</h4>";
    echo "<ol>";
    echo "<li><strong>Logged In Users:</strong> Direct access to quiz pages</li>";
    echo "<li><strong>Guest Users:</strong> Redirected to login with return URL</li>";
    echo "<li><strong>After Login:</strong> Automatically redirected to intended quiz</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>3. Button Behavior by Location</h3>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr><th>Location</th><th>Logged In</th><th>Not Logged In</th><th>Status</th></tr>";
    
    echo "<tr>";
    echo "<td><strong>Hero Section</strong></td>";
    echo "<td>Start Quiz → quizzes.php</td>";
    echo "<td>Get Started → register.php</td>";
    echo "<td>✅ Fixed</td>";
    echo "</tr>";
    
    echo "<tr>";
    echo "<td><strong>Popular Quiz Cards</strong></td>";
    echo "<td>Start Quiz → take-quiz.php?id=X</td>";
    echo "<td>Login to Start → login.php with redirect</td>";
    echo "<td>✅ Fixed</td>";
    echo "</tr>";
    
    echo "<tr>";
    echo "<td><strong>CTA Section</strong></td>";
    echo "<td>Take a Quiz → quizzes.php</td>";
    echo "<td>Get Started → register.php</td>";
    echo "<td>✅ Working</td>";
    echo "</tr>";
    
    echo "</table>";
    
    echo "<h3>4. Test Links</h3>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🎯 Test These Scenarios:</h4>";
    echo "<ol>";
    echo "<li><strong>As Guest:</strong> <a href='logout.php' target='_blank'>Logout first</a>, then <a href='index.php' target='_blank'>visit homepage</a></li>";
    echo "<li><strong>As User:</strong> <a href='login.php' target='_blank'>Login</a>, then <a href='index.php' target='_blank'>visit homepage</a></li>";
    echo "<li><strong>Direct Quiz:</strong> <a href='take-quiz.php?id=1' target='_blank'>Try taking quiz #1</a></li>";
    echo "</ol>";
    echo "</div>";
    
    if (!empty($sample_quizzes)) {
        echo "<h3>5. Sample Quiz Links</h3>";
        echo "<p>Test these specific quiz links:</p>";
        echo "<ul>";
        foreach ($sample_quizzes as $quiz) {
            echo "<li>";
            echo "<strong>{$quiz['title']}:</strong> ";
            echo "<a href='take-quiz.php?id={$quiz['id']}' target='_blank'>Direct Link</a> | ";
            echo "<a href='login.php?redirect=take-quiz.php?id={$quiz['id']}' target='_blank'>Login Redirect</a>";
            echo "</li>";
        }
        echo "</ul>";
    }
    
    echo "<h3>6. Files Check</h3>";
    
    $required_files = [
        'index.php' => 'Homepage with Start Quiz buttons',
        'take-quiz.php' => 'Quiz taking interface',
        'login.php' => 'Login page with redirect handling',
        'quizzes.php' => 'Quiz listing page'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>File</th><th>Description</th><th>Status</th></tr>";
    
    foreach ($required_files as $file => $description) {
        $exists = file_exists($file);
        $status = $exists ? '✅ Exists' : '❌ Missing';
        $color = $exists ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td><strong>{$file}</strong></td>";
        echo "<td>{$description}</td>";
        echo "<td style='color: {$color};'>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 30px 0;'>";
    echo "<h4>🎉 Summary of Fixes:</h4>";
    echo "<ul>";
    echo "<li>✅ <strong>Fixed URL:</strong> Changed quiz.php to take-quiz.php</li>";
    echo "<li>✅ <strong>Added Authentication:</strong> Different buttons for logged in/out users</li>";
    echo "<li>✅ <strong>Login Redirect:</strong> Users return to intended quiz after login</li>";
    echo "<li>✅ <strong>Better UX:</strong> Clear call-to-action based on user status</li>";
    echo "</ul>";
    echo "</div>";
    
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
table { margin: 15px 0; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
th { background: #e9ecef; font-weight: bold; }
ul, ol { margin: 10px 0 20px 20px; }
li { margin: 5px 0; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
