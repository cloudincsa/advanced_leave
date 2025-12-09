<?php
/**
 * Admin Logs View
 * Display and manage plugin logs
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'lfcc-leave-management'));
}

$logger = LFCC_Leave_Logger::get_instance();
$cleanup = LFCC_Leave_Cleanup::get_instance();

// Handle actions
if (isset($_POST['action'])) {
    if (!wp_verify_nonce($_POST['lfcc_logs_nonce'], 'lfcc_logs_action')) {
        wp_die(__('Security check failed.', 'lfcc-leave-management'));
    }
    
    switch ($_POST['action']) {
        case 'clear_logs':
            $logger->clear_logs();
            echo '<div class="notice notice-success"><p>' . __('Logs cleared successfully.', 'lfcc-leave-management') . '</p></div>';
            break;
            
        case 'export_logs':
            $format = sanitize_text_field($_POST['export_format']);
            $result = $logger->export_logs($format);
            if ($result) {
                echo '<div class="notice notice-success"><p>' . 
                     sprintf(__('Logs exported successfully. <a href="%s" target="_blank">Download file</a>', 'lfcc-leave-management'), $result['url']) . 
                     '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Failed to export logs.', 'lfcc-leave-management') . '</p></div>';
            }
            break;
    }
}

// Get log data
$log_level_filter = $_GET['level'] ?? null;
$recent_logs = $logger->get_recent_logs(200, $log_level_filter);
$log_stats = $logger->get_log_stats();
$cleanup_status = $cleanup->get_cleanup_status();
?>

<div class="wrap">
    <h1><?php _e('LFCC Leave Management - System Logs', 'lfcc-leave-management'); ?></h1>
    
    <!-- Log Statistics -->
    <div class="lfcc-admin-grid">
        <div class="lfcc-admin-card">
            <h2><?php _e('Log Statistics', 'lfcc-leave-management'); ?></h2>
            
            <div class="lfcc-stats-grid">
                <div class="lfcc-stat-item">
                    <span class="lfcc-stat-number"><?php echo number_format($log_stats['total_entries']); ?></span>
                    <span class="lfcc-stat-label"><?php _e('Total Entries', 'lfcc-leave-management'); ?></span>
                </div>
                
                <div class="lfcc-stat-item error">
                    <span class="lfcc-stat-number"><?php echo number_format($log_stats['recent_errors']); ?></span>
                    <span class="lfcc-stat-label"><?php _e('Recent Errors', 'lfcc-leave-management'); ?></span>
                </div>
                
                <div class="lfcc-stat-item">
                    <span class="lfcc-stat-number"><?php echo size_format($log_stats['file_size']); ?></span>
                    <span class="lfcc-stat-label"><?php _e('Log File Size', 'lfcc-leave-management'); ?></span>
                </div>
            </div>
            
            <!-- Log Level Distribution -->
            <h3><?php _e('Log Level Distribution', 'lfcc-leave-management'); ?></h3>
            <div class="lfcc-log-levels">
                <?php foreach ($log_stats['by_level'] as $level => $count): ?>
                    <div class="lfcc-log-level-item <?php echo strtolower($level); ?>">
                        <span class="level-name"><?php echo $level; ?></span>
                        <span class="level-count"><?php echo number_format($count); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Log Actions -->
        <div class="lfcc-admin-card">
            <h2><?php _e('Log Management', 'lfcc-leave-management'); ?></h2>
            
            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field('lfcc_logs_action', 'lfcc_logs_nonce'); ?>
                
                <div class="lfcc-form-group">
                    <label><?php _e('Export Logs', 'lfcc-leave-management'); ?></label>
                    <select name="export_format">
                        <option value="txt"><?php _e('Text File (.txt)', 'lfcc-leave-management'); ?></option>
                        <option value="csv"><?php _e('CSV File (.csv)', 'lfcc-leave-management'); ?></option>
                        <option value="json"><?php _e('JSON File (.json)', 'lfcc-leave-management'); ?></option>
                    </select>
                    <button type="submit" name="action" value="export_logs" class="button">
                        <?php _e('Export Logs', 'lfcc-leave-management'); ?>
                    </button>
                </div>
                
                <div class="lfcc-form-group">
                    <button type="submit" name="action" value="clear_logs" class="button button-secondary" 
                            onclick="return confirm('<?php _e('Are you sure you want to clear all logs? This action cannot be undone.', 'lfcc-leave-management'); ?>')">
                        <?php _e('Clear All Logs', 'lfcc-leave-management'); ?>
                    </button>
                </div>
            </form>
            
            <!-- Cleanup Status -->
            <h3><?php _e('Cleanup Status', 'lfcc-leave-management'); ?></h3>
            <div class="lfcc-cleanup-status">
                <p><strong><?php _e('Database Tables:', 'lfcc-leave-management'); ?></strong> 
                   <?php echo count($cleanup_status['database_tables']); ?> tables</p>
                <p><strong><?php _e('Plugin Options:', 'lfcc-leave-management'); ?></strong> 
                   <?php echo $cleanup_status['options_count']; ?> options</p>
                <p><strong><?php _e('User Meta:', 'lfcc-leave-management'); ?></strong> 
                   <?php echo $cleanup_status['user_meta_count']; ?> entries</p>
                <p><strong><?php _e('Scheduled Events:', 'lfcc-leave-management'); ?></strong> 
                   <?php echo count($cleanup_status['scheduled_events']); ?> events</p>
                <p><strong><?php _e('Files Exist:', 'lfcc-leave-management'); ?></strong> 
                   <?php echo $cleanup_status['files_exist'] ? __('Yes', 'lfcc-leave-management') : __('No', 'lfcc-leave-management'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Log Filters -->
    <div class="lfcc-admin-card">
        <h2><?php _e('Recent Log Entries', 'lfcc-leave-management'); ?></h2>
        
        <div class="lfcc-log-filters">
            <a href="<?php echo admin_url('admin.php?page=lfcc-leave-logs'); ?>" 
               class="button <?php echo !$log_level_filter ? 'button-primary' : ''; ?>">
                <?php _e('All Levels', 'lfcc-leave-management'); ?>
            </a>
            
            <?php foreach (array('ERROR', 'WARNING', 'INFO', 'DEBUG') as $level): ?>
                <a href="<?php echo admin_url('admin.php?page=lfcc-leave-logs&level=' . strtolower($level)); ?>" 
                   class="button <?php echo $log_level_filter === strtolower($level) ? 'button-primary' : ''; ?>">
                    <?php echo $level; ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Log Entries Table -->
        <div class="lfcc-log-table-container">
            <?php if (empty($recent_logs)): ?>
                <p><?php _e('No log entries found.', 'lfcc-leave-management'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 150px;"><?php _e('Timestamp', 'lfcc-leave-management'); ?></th>
                            <th style="width: 80px;"><?php _e('Level', 'lfcc-leave-management'); ?></th>
                            <th><?php _e('Message', 'lfcc-leave-management'); ?></th>
                            <th style="width: 100px;"><?php _e('User', 'lfcc-leave-management'); ?></th>
                            <th style="width: 120px;"><?php _e('IP Address', 'lfcc-leave-management'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr class="log-level-<?php echo strtolower($log['level']); ?>">
                                <td><?php echo esc_html($log['timestamp']); ?></td>
                                <td>
                                    <span class="lfcc-log-level-badge <?php echo strtolower($log['level']); ?>">
                                        <?php echo esc_html($log['level']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="lfcc-log-message">
                                        <?php echo esc_html($log['message']); ?>
                                        <?php if (!empty($log['context'])): ?>
                                            <details class="lfcc-log-context">
                                                <summary><?php _e('Context', 'lfcc-leave-management'); ?></summary>
                                                <pre><?php echo esc_html($log['context']); ?></pre>
                                            </details>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo esc_html($log['user']); ?></td>
                                <td><?php echo esc_html($log['ip']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.lfcc-admin-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.lfcc-admin-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.lfcc-admin-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.lfcc-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.lfcc-stat-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 4px solid #0073aa;
}

.lfcc-stat-item.error {
    border-left-color: #dc3232;
}

.lfcc-stat-number {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #23282d;
}

.lfcc-stat-label {
    display: block;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.lfcc-log-levels {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.lfcc-log-level-item {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #ccc;
}

.lfcc-log-level-item.error { border-left-color: #dc3232; }
.lfcc-log-level-item.warning { border-left-color: #ffb900; }
.lfcc-log-level-item.info { border-left-color: #0073aa; }
.lfcc-log-level-item.debug { border-left-color: #666; }

.level-name {
    font-weight: bold;
    font-size: 12px;
}

.level-count {
    background: #666;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 11px;
}

.lfcc-form-group {
    margin-bottom: 15px;
}

.lfcc-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.lfcc-cleanup-status p {
    margin: 5px 0;
    font-size: 14px;
}

.lfcc-log-filters {
    margin-bottom: 20px;
}

.lfcc-log-filters .button {
    margin-right: 10px;
}

.lfcc-log-table-container {
    max-height: 600px;
    overflow-y: auto;
    border: 1px solid #ddd;
}

.lfcc-log-level-badge {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.lfcc-log-level-badge.error {
    background: #dc3232;
    color: white;
}

.lfcc-log-level-badge.warning {
    background: #ffb900;
    color: #23282d;
}

.lfcc-log-level-badge.info {
    background: #0073aa;
    color: white;
}

.lfcc-log-level-badge.debug {
    background: #666;
    color: white;
}

.lfcc-log-message {
    max-width: 400px;
}

.lfcc-log-context {
    margin-top: 10px;
}

.lfcc-log-context summary {
    cursor: pointer;
    color: #0073aa;
    font-size: 12px;
}

.lfcc-log-context pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    font-size: 11px;
    max-height: 200px;
    overflow-y: auto;
}

.log-level-error {
    background-color: #ffeaea !important;
}

.log-level-warning {
    background-color: #fff8e1 !important;
}

@media (max-width: 768px) {
    .lfcc-admin-grid {
        grid-template-columns: 1fr;
    }
    
    .lfcc-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .lfcc-log-table-container {
        overflow-x: auto;
    }
}
</style>

