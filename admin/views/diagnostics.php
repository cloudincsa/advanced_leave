<?php
/**
 * Diagnostics Page
 * Shows current plugin settings and configuration for debugging
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get all settings from database
$default_options = LFCC_Leave_Settings::get_default_options();
$current_settings = array();

foreach ($default_options as $setting => $default_value) {
    $current_settings[$setting] = LFCC_Leave_Settings::get_option($setting, $default_value);
}

// Get subdomain detection info
$current_host = $_SERVER['HTTP_HOST'] ?? 'Unknown';
$main_host = parse_url(get_site_url(), PHP_URL_HOST);
$subdomain_name = LFCC_Leave_Settings::get_option('subdomain_name', '');
$expected_subdomain = $subdomain_name . '.' . preg_replace('/^www\./', '', $main_host);

?>

<div class="wrap">
    <h1><?php _e('LFCC Leave Management - Diagnostics', 'lfcc-leave-management'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('This page shows the current configuration and settings stored in the database. Use this to troubleshoot issues.', 'lfcc-leave-management'); ?></p>
    </div>
    
    <h2><?php _e('Subdomain Configuration', 'lfcc-leave-management'); ?></h2>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Setting', 'lfcc-leave-management'); ?></th>
                <th><?php _e('Value', 'lfcc-leave-management'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong><?php _e('Subdomain Enabled', 'lfcc-leave-management'); ?></strong></td>
                <td>
                    <code><?php echo esc_html($current_settings['subdomain_enabled']); ?></code>
                    <?php if ($current_settings['subdomain_enabled'] === 'yes'): ?>
                        <span style="color: green;">✓ Enabled</span>
                    <?php else: ?>
                        <span style="color: red;">✗ Disabled</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong><?php _e('Subdomain Name', 'lfcc-leave-management'); ?></strong></td>
                <td><code><?php echo esc_html($current_settings['subdomain_name']); ?></code></td>
            </tr>
            <tr>
                <td><strong><?php _e('Expected Subdomain URL', 'lfcc-leave-management'); ?></strong></td>
                <td><code><?php echo esc_html($expected_subdomain); ?></code></td>
            </tr>
            <tr>
                <td><strong><?php _e('Current Host', 'lfcc-leave-management'); ?></strong></td>
                <td><code><?php echo esc_html($current_host); ?></code></td>
            </tr>
            <tr>
                <td><strong><?php _e('Main Site URL', 'lfcc-leave-management'); ?></strong></td>
                <td><code><?php echo esc_html(get_site_url()); ?></code></td>
            </tr>
            <tr>
                <td><strong><?php _e('Is Subdomain Access?', 'lfcc-leave-management'); ?></strong></td>
                <td>
                    <?php if ($current_host === $expected_subdomain && $current_settings['subdomain_enabled'] === 'yes'): ?>
                        <span style="color: green;"><strong>✓ YES</strong> - You are accessing via subdomain</span>
                    <?php else: ?>
                        <span style="color: orange;"><strong>✗ NO</strong> - You are accessing via main domain</span>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>
    
    <h2><?php _e('Organization Settings', 'lfcc-leave-management'); ?></h2>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Setting', 'lfcc-leave-management'); ?></th>
                <th><?php _e('Value', 'lfcc-leave-management'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong><?php _e('Organization Name', 'lfcc-leave-management'); ?></strong></td>
                <td><code><?php echo esc_html($current_settings['organization_name']); ?></code></td>
            </tr>
            <tr>
                <td><strong><?php _e('Organization Email', 'lfcc-leave-management'); ?></strong></td>
                <td><code><?php echo esc_html($current_settings['organization_email']); ?></code></td>
            </tr>
            <tr>
                <td><strong><?php _e('Organization Phone', 'lfcc-leave-management'); ?></strong></td>
                <td><code><?php echo esc_html($current_settings['organization_phone']); ?></code></td>
            </tr>
        </tbody>
    </table>
    
    <h2><?php _e('All Settings (Raw)', 'lfcc-leave-management'); ?></h2>
    <textarea readonly style="width: 100%; height: 400px; font-family: monospace; font-size: 12px;"><?php
        echo "=== LFCC Leave Management Settings Dump ===\n\n";
        foreach ($current_settings as $key => $value) {
            echo str_pad($key, 30) . " => " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
    ?></textarea>
    
    <h2><?php _e('Database Information', 'lfcc-leave-management'); ?></h2>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Table', 'lfcc-leave-management'); ?></th>
                <th><?php _e('Status', 'lfcc-leave-management'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $db = LFCC_Leave_Database::get_instance();
            $tables = array(
                'Settings' => $db->settings_table,
                'Users' => $db->users_table,
                'Leave Requests' => $db->leave_requests_table,
                'Email Logs' => $db->email_logs_table
            );
            
            foreach ($tables as $name => $table_name) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                echo '<tr>';
                echo '<td><strong>' . esc_html($name) . '</strong><br><code>' . esc_html($table_name) . '</code></td>';
                echo '<td>';
                if ($exists) {
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                    echo '<span style="color: green;">✓ Exists</span> (' . $count . ' rows)';
                } else {
                    echo '<span style="color: red;">✗ Missing</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>

