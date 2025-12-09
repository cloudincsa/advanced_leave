<?php
/**
 * LFCC Leave Management Cleanup
 * Handles complete plugin cleanup and uninstallation
 */

if (!defined('ABSPATH')) {
    exit;
}

class LFCC_Leave_Cleanup {
    
    private static $instance = null;
    private $logger;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->logger = LFCC_Leave_Logger::get_instance();
    }
    
    /**
     * Complete plugin uninstall cleanup
     */
    public static function uninstall() {
        $cleanup = new self();
        $cleanup->perform_complete_cleanup();
    }
    
    /**
     * Perform complete cleanup
     */
    public function perform_complete_cleanup() {
        $this->logger->info('Starting complete plugin cleanup');
        
        try {
            // 1. Remove database tables
            $this->safe_cleanup_database_tables();
            
            // 2. Remove plugin options
            $this->safe_cleanup_plugin_options();
            
            // 3. Remove uploaded files
            $this->safe_cleanup_uploaded_files();
            
            // 4. Remove user meta data
            $this->safe_cleanup_user_meta();
            
            // 5. Remove scheduled events
            $this->safe_cleanup_scheduled_events();
            
            // 6. Remove transients
            $this->safe_cleanup_transients();
            
            // 7. Remove custom capabilities
            $this->safe_cleanup_custom_capabilities();
            
            // 8. Remove rewrite rules
            $this->safe_cleanup_rewrite_rules();
            
            // 9. Final cleanup log
            $this->logger->info('Plugin cleanup completed successfully');
            
            // 10. Remove log files (last step)
            $this->safe_cleanup_log_files();
            
        } catch (Exception $e) {
            error_log('LFCC Leave Management cleanup error: ' . $e->getMessage());
            // Continue with basic cleanup even if advanced cleanup fails
            $this->basic_database_cleanup();
        }
    }
    
    /**
     * Remove all database tables (safe version)
     */
    private function safe_cleanup_database_tables() {
        try {
            $this->cleanup_database_tables();
        } catch (Exception $e) {
            error_log('Database cleanup error: ' . $e->getMessage());
            $this->basic_database_cleanup();
        }
    }
    
    /**
     * Remove all database tables
     */
    private function cleanup_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'lfcc_leave_users',
            $wpdb->prefix . 'lfcc_leave_requests',
            $wpdb->prefix . 'lfcc_leave_email_logs',
            $wpdb->prefix . 'lfcc_leave_settings'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
            $this->logger->debug("Dropped database table: {$table}");
        }
        
        $this->logger->info('All database tables removed');
    }
    
    /**
     * Remove all plugin options
     */
    private function cleanup_plugin_options() {
        $options = array(
            // Main settings
            'lfcc_leave_settings',
            'lfcc_leave_version',
            'lfcc_leave_activation_time',
            'lfcc_leave_db_version',
            
            // Email templates
            'lfcc_leave_email_templates',
            'lfcc_leave_email_template_welcome',
            'lfcc_leave_email_template_welcome_subject',
            'lfcc_leave_email_template_leave_request_notification',
            'lfcc_leave_email_template_leave_request_notification_subject',
            'lfcc_leave_email_template_leave_approved',
            'lfcc_leave_email_template_leave_approved_subject',
            'lfcc_leave_email_template_leave_rejected',
            'lfcc_leave_email_template_leave_rejected_subject',
            'lfcc_leave_email_template_password_reset',
            'lfcc_leave_email_template_password_reset_subject',
            
            // Individual settings
            'lfcc_leave_organization_name',
            'lfcc_leave_company_logo',
            'lfcc_leave_subdomain_url',
            'lfcc_leave_default_annual_leave',
            'lfcc_leave_default_sick_leave',
            'lfcc_leave_default_personal_leave',
            'lfcc_leave_default_emergency_leave',
            'lfcc_leave_weekend_counts_as_leave',
            'lfcc_leave_allow_leave_editing',
            'lfcc_leave_require_reapproval_on_edit',
            'lfcc_leave_send_welcome_email',
            'lfcc_leave_notify_hr_on_request',
            'lfcc_leave_notify_user_on_approval',
            'lfcc_leave_notify_user_on_rejection',
            'lfcc_leave_enable_user_registration',
            'lfcc_leave_auto_approve_registrations',
            'lfcc_leave_email_from_name',
            'lfcc_leave_email_from_address',
            'lfcc_leave_smtp_host',
            'lfcc_leave_smtp_port',
            'lfcc_leave_smtp_username',
            'lfcc_leave_smtp_password',
            'lfcc_leave_smtp_encryption',
            'lfcc_leave_enable_logging',
            'lfcc_leave_log_level',
            'lfcc_leave_email_critical_errors',
            
            // Cache and temporary options
            'lfcc_leave_cache_version',
            'lfcc_leave_last_cleanup',
            'lfcc_leave_maintenance_mode',
            'lfcc_leave_update_notice_dismissed'
        );
        
        foreach ($options as $option) {
            delete_option($option);
            delete_site_option($option); // For multisite
            $this->logger->debug("Removed option: {$option}");
        }
        
        // Remove options with dynamic names
        $this->cleanup_dynamic_options();
        
        $this->logger->info('All plugin options removed');
    }
    
    /**
     * Remove options with dynamic names
     */
    private function cleanup_dynamic_options() {
        global $wpdb;
        
        // Remove options that start with plugin prefix
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'lfcc_leave_%'");
        
        // For multisite
        if (is_multisite()) {
            $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE 'lfcc_leave_%'");
        }
        
        $this->logger->debug('Removed dynamic options');
    }
    
    /**
     * Remove uploaded files and directories
     */
    private function cleanup_uploaded_files() {
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/lfcc-leave-management/';
        
        if (is_dir($plugin_upload_dir)) {
            $this->remove_directory_recursive($plugin_upload_dir);
            $this->logger->info("Removed upload directory: {$plugin_upload_dir}");
        }
        
        // Remove any files in main upload directory with plugin prefix
        $plugin_files = glob($upload_dir['basedir'] . '/lfcc-leave-*');
        foreach ($plugin_files as $file) {
            if (is_file($file)) {
                unlink($file);
                $this->logger->debug("Removed file: {$file}");
            }
        }
    }
    
    /**
     * Remove directory and all contents recursively
     */
    private function remove_directory_recursive($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($path)) {
                $this->remove_directory_recursive($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Remove user meta data related to plugin
     */
    private function cleanup_user_meta() {
        global $wpdb;
        
        $meta_keys = array(
            'lfcc_leave_user_id',
            'lfcc_leave_last_login',
            'lfcc_leave_login_attempts',
            'lfcc_leave_password_reset_token',
            'lfcc_leave_password_reset_expires',
            'lfcc_leave_email_notifications',
            'lfcc_leave_dashboard_preferences',
            'lfcc_leave_session_token'
        );
        
        foreach ($meta_keys as $meta_key) {
            $wpdb->delete($wpdb->usermeta, array('meta_key' => $meta_key));
            $this->logger->debug("Removed user meta: {$meta_key}");
        }
        
        // Remove meta keys with dynamic names
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'lfcc_leave_%'");
        
        $this->logger->info('All user meta data removed');
    }
    
    /**
     * Remove scheduled events
     */
    private function cleanup_scheduled_events() {
        $scheduled_events = array(
            'lfcc_leave_daily_cleanup',
            'lfcc_leave_weekly_reports',
            'lfcc_leave_monthly_reports',
            'lfcc_leave_annual_leave_reset',
            'lfcc_leave_email_queue_process',
            'lfcc_leave_session_cleanup',
            'lfcc_leave_log_rotation',
            'lfcc_leave_backup_database',
            'lfcc_leave_send_reminders'
        );
        
        foreach ($scheduled_events as $event) {
            wp_clear_scheduled_hook($event);
            $this->logger->debug("Cleared scheduled event: {$event}");
        }
        
        $this->logger->info('All scheduled events removed');
    }
    
    /**
     * Remove transients
     */
    private function cleanup_transients() {
        global $wpdb;
        
        // Remove transients with plugin prefix
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lfcc_leave_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lfcc_leave_%'");
        
        // For multisite
        if (is_multisite()) {
            $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_lfcc_leave_%'");
            $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_timeout_lfcc_leave_%'");
        }
        
        $this->logger->info('All transients removed');
    }
    
    /**
     * Remove custom capabilities
     */
    private function cleanup_custom_capabilities() {
        $capabilities = array(
            'lfcc_manage_leave_requests',
            'lfcc_approve_leave_requests',
            'lfcc_manage_users',
            'lfcc_view_reports',
            'lfcc_manage_settings',
            'lfcc_manage_email_templates',
            'lfcc_export_data',
            'lfcc_view_logs'
        );
        
        // Remove capabilities from all roles
        $roles = wp_roles();
        
        foreach ($roles->roles as $role_name => $role_info) {
            $role = get_role($role_name);
            
            if ($role) {
                foreach ($capabilities as $capability) {
                    $role->remove_cap($capability);
                    $this->logger->debug("Removed capability {$capability} from role {$role_name}");
                }
            }
        }
        
        $this->logger->info('All custom capabilities removed');
    }
    
    /**
     * Remove rewrite rules
     */
    private function cleanup_rewrite_rules() {
        // Remove any custom rewrite rules
        delete_option('rewrite_rules');
        flush_rewrite_rules();
        
        $this->logger->info('Rewrite rules flushed');
    }
    
    /**
     * Remove log files (done last)
     */
    private function cleanup_log_files() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/lfcc-leave-management/logs/';
        
        if (is_dir($log_dir)) {
            $this->remove_directory_recursive($log_dir);
        }
    }
    
    /**
     * Partial cleanup for deactivation (keeps data)
     */
    public function deactivation_cleanup() {
        $this->logger->info('Starting deactivation cleanup');
        
        // Only remove temporary data, keep user data and settings
        $this->cleanup_scheduled_events();
        $this->cleanup_transients();
        $this->cleanup_rewrite_rules();
        
        // Clear any active sessions
        $this->cleanup_active_sessions();
        
        $this->logger->info('Deactivation cleanup completed');
    }
    
    /**
     * Clear active user sessions
     */
    private function cleanup_active_sessions() {
        // Since we don't have a sessions table in the current implementation,
        // just clear any session-related user meta
        global $wpdb;
        
        try {
            $wpdb->delete($wpdb->usermeta, array('meta_key' => 'lfcc_leave_session_token'));
            $this->logger->debug('Cleared session-related user meta');
        } catch (Exception $e) {
            error_log('Session cleanup error: ' . $e->getMessage());
        }
    }
    
    /**
     * Emergency cleanup (for corrupted installations)
     */
    public function emergency_cleanup() {
        $this->logger->emergency('Starting emergency cleanup');
        
        try {
            // Force remove everything without checks
            $this->force_cleanup_database();
            $this->force_cleanup_files();
            $this->force_cleanup_options();
            
            $this->logger->emergency('Emergency cleanup completed');
            
        } catch (Exception $e) {
            error_log('LFCC Leave Management emergency cleanup failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Force cleanup database (emergency)
     */
    private function force_cleanup_database() {
        global $wpdb;
        
        // Get all tables with plugin prefix
        $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}lfcc_%'", ARRAY_N);
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table[0]}");
        }
    }
    
    /**
     * Force cleanup files (emergency)
     */
    private function force_cleanup_files() {
        $upload_dir = wp_upload_dir();
        $plugin_dirs = glob($upload_dir['basedir'] . '/*lfcc*', GLOB_ONLYDIR);
        
        foreach ($plugin_dirs as $dir) {
            $this->remove_directory_recursive($dir);
        }
        
        // Remove any files with plugin name
        $plugin_files = glob($upload_dir['basedir'] . '/*lfcc*');
        foreach ($plugin_files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Force cleanup options (emergency)
     */
    private function force_cleanup_options() {
        global $wpdb;
        
        // Remove all options with plugin prefix
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%lfcc%'");
        
        if (is_multisite()) {
            $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '%lfcc%'");
        }
        
        // Remove user meta
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '%lfcc%'");
    }
    
    /**
     * Get cleanup status
     */
    public function get_cleanup_status() {
        global $wpdb;
        
        $status = array(
            'database_tables' => array(),
            'options_count' => 0,
            'user_meta_count' => 0,
            'files_exist' => false,
            'scheduled_events' => array(),
            'transients_count' => 0
        );
        
        // Check database tables
        $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}lfcc_%'", ARRAY_N);
        foreach ($tables as $table) {
            $status['database_tables'][] = $table[0];
        }
        
        // Check options
        $options_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'lfcc_leave_%'");
        $status['options_count'] = intval($options_count);
        
        // Check user meta
        $user_meta_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key LIKE 'lfcc_leave_%'");
        $status['user_meta_count'] = intval($user_meta_count);
        
        // Check files
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/lfcc-leave-management/';
        $status['files_exist'] = is_dir($plugin_upload_dir);
        
        // Check scheduled events
        $crons = _get_cron_array();
        foreach ($crons as $timestamp => $cron) {
            foreach ($cron as $hook => $events) {
                if (strpos($hook, 'lfcc_leave_') === 0) {
                    $status['scheduled_events'][] = $hook;
                }
            }
        }
        
        // Check transients
        $transients_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_lfcc_leave_%'");
        $status['transients_count'] = intval($transients_count);
        
        return $status;
    }
    
    /**
     * Verify cleanup completion
     */
    public function verify_cleanup() {
        $status = $this->get_cleanup_status();
        
        $is_clean = (
            empty($status['database_tables']) &&
            $status['options_count'] === 0 &&
            $status['user_meta_count'] === 0 &&
            !$status['files_exist'] &&
            empty($status['scheduled_events']) &&
            $status['transients_count'] === 0
        );
        
        return array(
            'is_clean' => $is_clean,
            'status' => $status
        );
    }
    
    /**
     * Safe wrapper methods for cleanup operations
     */
    private function safe_cleanup_plugin_options() {
        try {
            $this->cleanup_plugin_options();
        } catch (Exception $e) {
            error_log('Plugin options cleanup error: ' . $e->getMessage());
        }
    }
    
    private function safe_cleanup_uploaded_files() {
        try {
            $this->cleanup_uploaded_files();
        } catch (Exception $e) {
            error_log('File cleanup error: ' . $e->getMessage());
        }
    }
    
    private function safe_cleanup_user_meta() {
        try {
            $this->cleanup_user_meta();
        } catch (Exception $e) {
            error_log('User meta cleanup error: ' . $e->getMessage());
        }
    }
    
    private function safe_cleanup_scheduled_events() {
        try {
            $this->cleanup_scheduled_events();
        } catch (Exception $e) {
            error_log('Scheduled events cleanup error: ' . $e->getMessage());
        }
    }
    
    private function safe_cleanup_transients() {
        try {
            $this->cleanup_transients();
        } catch (Exception $e) {
            error_log('Transients cleanup error: ' . $e->getMessage());
        }
    }
    
    private function safe_cleanup_custom_capabilities() {
        try {
            $this->cleanup_custom_capabilities();
        } catch (Exception $e) {
            error_log('Capabilities cleanup error: ' . $e->getMessage());
        }
    }
    
    private function safe_cleanup_rewrite_rules() {
        try {
            $this->cleanup_rewrite_rules();
        } catch (Exception $e) {
            error_log('Rewrite rules cleanup error: ' . $e->getMessage());
        }
    }
    
    private function safe_cleanup_log_files() {
        try {
            $this->cleanup_log_files();
        } catch (Exception $e) {
            error_log('Log files cleanup error: ' . $e->getMessage());
        }
    }
    
    /**
     * Basic database cleanup fallback
     */
    private function basic_database_cleanup() {
        global $wpdb;
        
        try {
            // Remove only the core tables we know exist
            $tables = array(
                $wpdb->prefix . 'lfcc_leave_users',
                $wpdb->prefix . 'lfcc_leave_requests',
                $wpdb->prefix . 'lfcc_leave_email_logs',
                $wpdb->prefix . 'lfcc_leave_settings'
            );
            
            foreach ($tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS {$table}");
            }
            
            // Remove basic options
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'lfcc_leave_%'");
            
        } catch (Exception $e) {
            error_log('Basic database cleanup error: ' . $e->getMessage());
        }
    }
}

