<?php
/**
 * Database Check Script
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Check</h2>";

try {
    // Test database connection
    $host = 'localhost';
    $dbname = 'smartquiz_hub';
    $username = 'root';
    $password = '';
    
    echo "<p>Attempting to connect to database...</p>";
    echo "<p>Host: {$host}</p>";
    echo "<p>Database: {$dbname}</p>";
    echo "<p>Username: {$username}</p>";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check if tables exist
    $tables = ['users', 'categories', 'quizzes', 'questions', 'question_options', 'quiz_attempts', 'user_answers'];
    
    echo "<h3>Table Check:</h3>";
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p>✅ Table '$table': {$count} records</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Table '$table': Not found or error</p>";
        }
    }
    
    // Check specific data
    echo "<h3>Data Check:</h3>";
    
    // Check quiz attempts
    $stmt = $pdo->query("SELECT COUNT(*) FROM quiz_attempts WHERE id = 2");
    $attempt_exists = $stmt->fetchColumn();
    
    if ($attempt_exists) {
        echo "<p>✅ Attempt ID 2 exists</p>";
        
        // Get attempt details
        $stmt = $pdo->prepare("SELECT * FROM quiz_attempts WHERE id = 2");
        $stmt->execute();
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h4>Attempt 2 Details:</h4>";
        echo "<ul>";
        foreach ($attempt as $key => $value) {
            echo "<li><strong>{$key}:</strong> {$value}</li>";
        }
        echo "</ul>";
        
        // Check user answers for this attempt
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_answers WHERE attempt_id = 2");
        $stmt->execute();
        $answer_count = $stmt->fetchColumn();
        echo "<p>User answers for attempt 2: {$answer_count}</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Attempt ID 2 does not exist</p>";
        
        // Show available attempts
        $stmt = $pdo->query("SELECT id, quiz_id, user_id, status, score, percentage FROM quiz_attempts ORDER BY id DESC LIMIT 5");
        $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($attempts) {
            echo "<h4>Available Attempts:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Quiz ID</th><th>User ID</th><th>Status</th><th>Score</th><th>Percentage</th></tr>";
            foreach ($attempts as $att) {
                echo "<tr>";
                echo "<td>{$att['id']}</td>";
                echo "<td>{$att['quiz_id']}</td>";
                echo "<td>{$att['user_id']}</td>";
                echo "<td>{$att['status']}</td>";
                echo "<td>{$att['score']}</td>";
                echo "<td>{$att['percentage']}%</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    echo "<h3>Quick Actions:</h3>";
    echo "<p><a href='simple-fix.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Simple Fix</a></p>";
    echo "<p><a href='add-sample-questions.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Add Sample Questions</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Database Connection Failed!</h4>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<h5>Common Solutions:</h5>";
    echo "<ul>";
    echo "<li>Make sure XAMPP is running</li>";
    echo "<li>Start Apache and MySQL in XAMPP Control Panel</li>";
    echo "<li>Check if database 'smartquiz_hub' exists in phpMyAdmin</li>";
    echo "<li>Verify database credentials (host, username, password)</li>";
    echo "</ul>";
    echo "<p><a href='http://localhost/phpmyadmin' target='_blank' style='background: #ffc107; color: black; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Open phpMyAdmin</a></p>";
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
h2 { color: #007bff; }
h3 { color: #28a745; margin-top: 20px; }
h4 { color: #495057; }
table { margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; }
th { background: #e9ecef; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
a { transition: all 0.3s ease; }
a:hover { transform: translateY(-1px); }
</style>
