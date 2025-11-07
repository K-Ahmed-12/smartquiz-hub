<?php
/**
 * Test Leaderboard Category Filtering
 */

require_once 'config/config.php';
requireAdmin();

echo "<h2>🧪 Testing Leaderboard Category Filtering</h2>";

try {
    $db = getDB();
    
    // Get all categories
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    echo "<h3>Available Categories:</h3>";
    echo "<ul>";
    foreach ($categories as $category) {
        echo "<li><strong>{$category['name']}</strong> (ID: {$category['id']})</li>";
    }
    echo "</ul>";
    
    // Test each category
    foreach ($categories as $category) {
        echo "<h3>Testing Category: {$category['name']}</h3>";
        
        // Get top performers for this category
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email,
                   COUNT(DISTINCT qa.quiz_id) as quizzes_completed,
                   COUNT(qa.id) as total_attempts,
                   AVG(qa.percentage) as avg_score,
                   MAX(qa.percentage) as best_score,
                   SUM(qa.score) as total_points
            FROM users u
            JOIN quiz_attempts qa ON u.id = qa.user_id
            JOIN quizzes q ON qa.quiz_id = q.id
            WHERE u.role = 'user' AND qa.status = 'completed' AND q.category_id = ?
            GROUP BY u.id
            HAVING avg_score > 0
            ORDER BY avg_score DESC, total_points DESC, quizzes_completed DESC
            LIMIT 5
        ");
        $stmt->execute([$category['id']]);
        $category_leaders = $stmt->fetchAll();
        
        if (empty($category_leaders)) {
            echo "<p>❌ No completed attempts found for this category</p>";
        } else {
            echo "<p>✅ Found " . count($category_leaders) . " top performers:</p>";
            echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
            echo "<tr><th>User</th><th>Avg Score</th><th>Quizzes</th><th>Total Points</th></tr>";
            foreach ($category_leaders as $leader) {
                echo "<tr>";
                echo "<td>{$leader['name']}</td>";
                echo "<td>" . number_format($leader['avg_score'], 1) . "%</td>";
                echo "<td>{$leader['quizzes_completed']}</td>";
                echo "<td>{$leader['total_points']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Test recent achievements for this category
        $stmt = $db->prepare("
            SELECT u.name, q.title, qa.percentage, qa.start_time, c.name as category_name
            FROM quiz_attempts qa
            JOIN users u ON qa.user_id = u.id
            JOIN quizzes q ON qa.quiz_id = q.id
            JOIN categories c ON q.category_id = c.id
            WHERE qa.status = 'completed' 
            AND qa.start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND q.category_id = ?
            ORDER BY qa.percentage DESC, qa.start_time DESC
            LIMIT 3
        ");
        $stmt->execute([$category['id']]);
        $category_achievements = $stmt->fetchAll();
        
        if (empty($category_achievements)) {
            echo "<p>❌ No recent achievements (last 24h) for this category</p>";
        } else {
            echo "<p>✅ Found " . count($category_achievements) . " recent achievements:</p>";
            echo "<ul>";
            foreach ($category_achievements as $achievement) {
                echo "<li>{$achievement['name']}: " . number_format($achievement['percentage'], 1) . "% on {$achievement['title']}</li>";
            }
            echo "</ul>";
        }
        
        echo "<hr>";
    }
    
    echo "<h3>Test Links:</h3>";
    echo "<p>Test the leaderboard with different category filters:</p>";
    echo "<ul>";
    echo "<li><a href='leaderboard.php' target='_blank'>All Categories</a></li>";
    foreach ($categories as $category) {
        echo "<li><a href='leaderboard.php?category={$category['id']}' target='_blank'>{$category['name']} Only</a></li>";
    }
    echo "</ul>";
    
    echo "<h3>Summary:</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<h4>✅ Fixes Applied:</h4>";
    echo "<ul>";
    echo "<li><strong>Top Performers:</strong> Now filters by selected category</li>";
    echo "<li><strong>Recent Achievements:</strong> Now filters by selected category</li>";
    echo "<li><strong>User Stats:</strong> Now calculates based on selected category</li>";
    echo "<li><strong>Visual Indicators:</strong> Category badges show which filter is active</li>";
    echo "<li><strong>Category Names:</strong> Shown in achievement details</li>";
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
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #e9ecef; font-weight: bold; }
ul { margin: 10px 0 20px 20px; }
li { margin: 5px 0; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
hr { margin: 30px 0; border: 1px solid #dee2e6; }
</style>
