<?php
/**
 * Settings Management Functions
 */

/**
 * Get a setting value by key
 */
function getSetting($key, $default = null) {
    static $settings_cache = null;
    
    // Load settings into cache if not already loaded
    if ($settings_cache === null) {
        $settings_cache = loadAllSettings();
    }
    
    return $settings_cache[$key] ?? $default;
}

/**
 * Load all settings from database
 */
function loadAllSettings() {
    try {
        $db = getDB();
        
        // Check if settings table exists
        $stmt = $db->query("SHOW TABLES LIKE 'site_settings'");
        if (!$stmt->fetch()) {
            return getDefaultSettings();
        }
        
        // Load settings from database
        $stmt = $db->query("SELECT setting_key, setting_value, setting_type FROM site_settings");
        $settings = [];
        
        while ($row = $stmt->fetch()) {
            $value = $row['setting_value'];
            
            // Convert based on type
            switch ($row['setting_type']) {
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'boolean':
                    $value = (bool)$value;
                    break;
                case 'string':
                case 'text':
                default:
                    // Keep as string
                    break;
            }
            
            $settings[$row['setting_key']] = $value;
        }
        
        // Merge with defaults for any missing settings
        return array_merge(getDefaultSettings(), $settings);
        
    } catch (PDOException $e) {
        error_log("Settings loading error: " . $e->getMessage());
        return getDefaultSettings();
    }
}

/**
 * Get default settings
 */
function getDefaultSettings() {
    return [
        'site_name' => 'SmartQuiz Hub',
        'site_tagline' => 'Test Your Knowledge',
        'site_description' => 'An interactive quiz platform for learning and assessment',
        'admin_email' => 'admin@smartquizhub.com',
        'contact_email' => 'contact@smartquizhub.com',
        'site_url' => 'http://localhost/smartquiz-hub',
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
        'items_per_page' => 10,
        'leaderboard_limit' => 20,
        'session_timeout' => 60,
        'allow_registration' => true,
        'require_email_verification' => false,
        'maintenance_mode' => false,
        'google_analytics_id' => '',
        'facebook_url' => '',
        'twitter_url' => '',
        'instagram_url' => '',
        'linkedin_url' => '',
        'footer_text' => '© 2024 SmartQuiz Hub. All rights reserved.',
        'privacy_policy_url' => '',
        'terms_of_service_url' => ''
    ];
}

/**
 * Update a setting
 */
function updateSetting($key, $value, $type = 'string') {
    try {
        $db = getDB();
        
        // Create table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS site_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                setting_type ENUM('string', 'integer', 'boolean', 'text') DEFAULT 'string',
                description TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        $stmt = $db->prepare("
            INSERT INTO site_settings (setting_key, setting_value, setting_type) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            setting_type = VALUES(setting_type)
        ");
        
        return $stmt->execute([$key, $value, $type]);
        
    } catch (PDOException $e) {
        error_log("Setting update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get formatted date
 */
function formatDate($date, $include_time = false) {
    $format = getSetting('date_format', 'Y-m-d');
    if ($include_time) {
        $format .= ' ' . getSetting('time_format', 'H:i:s');
    }
    
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    
    return $date->format($format);
}

/**
 * Check if registration is allowed
 */
function isRegistrationAllowed() {
    return getSetting('allow_registration', true);
}

/**
 * Check if site is in maintenance mode
 */
function isMaintenanceMode() {
    return getSetting('maintenance_mode', false);
}

/**
 * Get social media links
 */
function getSocialLinks() {
    return [
        'facebook' => getSetting('facebook_url', ''),
        'twitter' => getSetting('twitter_url', ''),
        'instagram' => getSetting('instagram_url', ''),
        'linkedin' => getSetting('linkedin_url', '')
    ];
}

/**
 * Get pagination limit
 */
function getPaginationLimit() {
    return getSetting('items_per_page', 10);
}

/**
 * Get leaderboard limit
 */
function getLeaderboardLimit() {
    return getSetting('leaderboard_limit', 20);
}

/**
 * Get session timeout in seconds
 */
function getSessionTimeout() {
    return getSetting('session_timeout', 60) * 60; // Convert minutes to seconds
}

/**
 * Clear settings cache (call after updating settings)
 */
function clearSettingsCache() {
    // This will force reload on next getSetting() call
    static $settings_cache = null;
    $settings_cache = null;
}
?>
