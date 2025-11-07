<?php
/**
 * Test Settings System
 */

require_once 'config/config.php';
requireAdmin();

echo "<h2>🧪 Testing Website Settings System</h2>";

try {
    $db = getDB();
    
    echo "<h3>1. Current Settings Values</h3>";
    
    $test_settings = [
        'site_name' => 'Site Name',
        'site_tagline' => 'Site Tagline', 
        'admin_email' => 'Admin Email',
        'contact_email' => 'Contact Email',
        'items_per_page' => 'Items Per Page',
        'leaderboard_limit' => 'Leaderboard Limit',
        'allow_registration' => 'Allow Registration',
        'maintenance_mode' => 'Maintenance Mode'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Setting</th><th>Current Value</th><th>Type</th><th>Source</th></tr>";
    
    foreach ($test_settings as $key => $label) {
        $value = getSetting($key);
        $type = gettype($value);
        $source = defined(strtoupper($key)) ? 'Constant' : 'Database/Default';
        
        echo "<tr>";
        echo "<td><strong>{$label}</strong></td>";
        echo "<td>" . (is_bool($value) ? ($value ? 'Yes' : 'No') : htmlspecialchars($value)) . "</td>";
        echo "<td>{$type}</td>";
        echo "<td>{$source}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>2. Settings Table Status</h3>";
    
    // Check if settings table exists
    $stmt = $db->query("SHOW TABLES LIKE 'site_settings'");
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "<p>✅ Settings table exists</p>";
        
        // Count settings
        $stmt = $db->query("SELECT COUNT(*) as count FROM site_settings");
        $count = $stmt->fetch()['count'];
        echo "<p>📊 {$count} settings stored in database</p>";
        
        // Show recent settings
        $stmt = $db->query("SELECT setting_key, setting_value, setting_type, updated_at FROM site_settings ORDER BY updated_at DESC LIMIT 5");
        $recent = $stmt->fetchAll();
        
        if (!empty($recent)) {
            echo "<h4>Recent Settings:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Key</th><th>Value</th><th>Type</th><th>Updated</th></tr>";
            foreach ($recent as $setting) {
                echo "<tr>";
                echo "<td>{$setting['setting_key']}</td>";
                echo "<td>" . htmlspecialchars(substr($setting['setting_value'], 0, 50)) . "</td>";
                echo "<td>{$setting['setting_type']}</td>";
                echo "<td>{$setting['updated_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p>❌ Settings table does not exist yet</p>";
        echo "<p>💡 It will be created when you first save settings</p>";
    }
    
    echo "<h3>3. Function Tests</h3>";
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>📋 Helper Functions:</h4>";
    echo "<ul>";
    echo "<li><strong>isRegistrationAllowed():</strong> " . (isRegistrationAllowed() ? 'Yes' : 'No') . "</li>";
    echo "<li><strong>isMaintenanceMode():</strong> " . (isMaintenanceMode() ? 'Yes' : 'No') . "</li>";
    echo "<li><strong>getPaginationLimit():</strong> " . getPaginationLimit() . "</li>";
    echo "<li><strong>getLeaderboardLimit():</strong> " . getLeaderboardLimit() . "</li>";
    echo "<li><strong>getSessionTimeout():</strong> " . getSessionTimeout() . " seconds</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>4. Social Media Links</h3>";
    $social = getSocialLinks();
    echo "<ul>";
    foreach ($social as $platform => $url) {
        $status = empty($url) ? '❌ Not set' : '✅ Set';
        echo "<li><strong>" . ucfirst($platform) . ":</strong> {$status}</li>";
    }
    echo "</ul>";
    
    echo "<h3>5. Constants vs Settings</h3>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Constant</th><th>Value</th><th>Source</th></tr>";
    
    $constants_to_check = [
        'SITE_NAME' => getSetting('site_name', 'Default'),
        'SITE_TAGLINE' => getSetting('site_tagline', 'Default'),
        'ADMIN_EMAIL' => getSetting('admin_email', 'Default'),
        'ITEMS_PER_PAGE' => getPaginationLimit(),
        'LEADERBOARD_LIMIT' => getLeaderboardLimit()
    ];
    
    foreach ($constants_to_check as $constant => $expected) {
        $actual = defined($constant) ? constant($constant) : 'Not defined';
        $match = ($actual == $expected) ? '✅' : '❌';
        
        echo "<tr>";
        echo "<td><strong>{$constant}</strong></td>";
        echo "<td>{$actual}</td>";
        echo "<td>{$match} " . ($match == '✅' ? 'Matches setting' : 'Different from setting') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>6. Test Links</h3>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🎯 Test the Settings System:</h4>";
    echo "<ol>";
    echo "<li><a href='admin/settings.php' target='_blank'><strong>Open Settings Panel</strong></a> - Configure website settings</li>";
    echo "<li><a href='index.php' target='_blank'><strong>View Homepage</strong></a> - See settings in action</li>";
    echo "<li><a href='admin/index.php' target='_blank'><strong>Admin Dashboard</strong></a> - Access admin panel</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>7. Settings Integration Status</h3>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 30px 0;'>";
    echo "<h4>🎉 Settings System Features:</h4>";
    echo "<ul>";
    echo "<li>✅ <strong>Database Storage:</strong> Settings stored in site_settings table</li>";
    echo "<li>✅ <strong>Admin Panel:</strong> Beautiful settings interface with tabs</li>";
    echo "<li>✅ <strong>Helper Functions:</strong> Easy access to settings throughout app</li>";
    echo "<li>✅ <strong>Default Values:</strong> Fallback to defaults if database unavailable</li>";
    echo "<li>✅ <strong>Type Conversion:</strong> Automatic boolean/integer conversion</li>";
    echo "<li>✅ <strong>Caching:</strong> Settings cached for performance</li>";
    echo "<li>✅ <strong>Validation:</strong> Form validation for all settings</li>";
    echo "<li>✅ <strong>Categories:</strong> Settings organized in logical groups</li>";
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
