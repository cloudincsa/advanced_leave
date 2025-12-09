<?php
/**
 * LFCC Leave Management Logger
 * Comprehensive error logging and debugging system
 */

if (!defined('ABSPATH')) {
    exit;
}

class LFCC_Leave_Logger {
    
    private static $instance = null;
    private $log_file;
    private $max_log_size;
    private $log_levels;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_logger();
    }
    
    /**
     * Initialize logger settings
     */
    private function init_logger() {
        // Set log file location
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/lfcc-leave-management/logs/';
        
        // Create log directory if it doesn't exist
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Create .htaccess to protect log files
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents($log_dir . '.htaccess', $htaccess_content);
            
            // Create index.php to prevent directory listing
            file_put_contents($log_dir . 'index.php', '<?php // Silence is golden');
        }
        
        $this->log_file = $log_dir . 'lfcc-leave-' . date('Y-m') . '.log';
        $this->max_log_size = 10 * 1024 * 1024; // 10MB
        
        // Define log levels
        $this->log_levels = array(
            'emergency' => 0,
            'alert'     => 1,
            'critical'  => 2,
            'error'     => 3,
            'warning'   => 4,
            'notice'    => 5,
            'info'      => 6,
            'debug'     => 7
        );
    }
    
    /**
     * Log a message with specified level
     */
    public function log($level, $message, $context = array()) {
        // Check if logging is enabled
        if (!LFCC_Leave_Settings::get_option('enable_logging', 'yes')) {
            return;
        }
        
        // Check log level threshold
        $min_level = LFCC_Leave_Settings::get_option('log_level', 'error');
        if ($this->log_levels[$level] > $this->log_levels[$min_level]) {
            return;
        }
        
        // Rotate log if too large
        $this->rotate_log_if_needed();
        
        // Format log entry
        $log_entry = $this->format_log_entry($level, $message, $context);
        
        // Write to log file
        $this->write_to_log($log_entry);
        
        // Send critical errors to admin email if enabled
        if (in_array($level, array('emergency', 'alert', 'critical')) && 
            LFCC_Leave_Settings::get_option('email_critical_errors', 'yes') === 'yes') {
            $this->email_critical_error($level, $message, $context);
        }
    }
    
    /**
     * Emergency: system is unusable
     */
    public function emergency($message, $context = array()) {
        $this->log('emergency', $message, $context);
    }
    
    /**
     * Alert: action must be taken immediately
     */
    public function alert($message, $context = array()) {
        $this->log('alert', $message, $context);
    }
    
    /**
     * Critical: critical conditions
     */
    public function critical($message, $context = array()) {
        $this->log('critical', $message, $context);
    }
    
    /**
     * Error: error conditions
     */
    public function error($message, $context = array()) {
        $this->log('error', $message, $context);
    }
    
    /**
     * Warning: warning conditions
     */
    public function warning($message, $context = array()) {
        $this->log('warning', $message, $context);
    }
    
    /**
     * Notice: normal but significant condition
     */
    public function notice($message, $context = array()) {
        $this->log('notice', $message, $context);
    }
    
    /**
     * Info: informational messages
     */
    public function info($message, $context = array()) {
        $this->log('info', $message, $context);
    }
    
    /**
     * Debug: debug-level messages
     */
    public function debug($message, $context = array()) {
        $this->log('debug', $message, $context);
    }
    
    /**
     * Format log entry
     */
    private function format_log_entry($level, $message, $context) {
        $timestamp = current_time('Y-m-d H:i:s');
        $level = strtoupper($level);
        
        // Get user info
        $user_id = get_current_user_id();
        $user_info = $user_id ? "User:{$user_id}" : 'Guest';
        
        // Get request info
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $request_method = $_SERVER['REQUEST_METHOD'] ?? '';
        $remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Build context string
        $context_string = '';
        if (!empty($context)) {
            $context_string = ' | Context: ' . wp_json_encode($context);
        }
        
        // Format: [TIMESTAMP] LEVEL: MESSAGE | User | IP | Method URI | Context
        $log_entry = sprintf(
            "[%s] %s: %s | %s | %s | %s %s%s\n",
            $timestamp,
            $level,
            $message,
            $user_info,
            $remote_addr,
            $request_method,
            $request_uri,
            $context_string
        );
        
        return $log_entry;
    }
    
    /**
     * Write to log file
     */
    private function write_to_log($log_entry) {
        // Use WordPress filesystem API
        global $wp_filesystem;
        
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        // Append to log file
        if ($wp_filesystem) {
            $existing_content = $wp_filesystem->exists($this->log_file) ? $wp_filesystem->get_contents($this->log_file) : '';
            $wp_filesystem->put_contents($this->log_file, $existing_content . $log_entry, FS_CHMOD_FILE);
        } else {
            // Fallback to PHP file functions
            file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Rotate log file if it's too large
     */
    private function rotate_log_if_needed() {
        if (file_exists($this->log_file) && filesize($this->log_file) > $this->max_log_size) {
            $backup_file = str_replace('.log', '-' . time() . '.log', $this->log_file);
            rename($this->log_file, $backup_file);
            
            // Keep only last 5 backup files
            $this->cleanup_old_logs();
        }
    }
    
    /**
     * Clean up old log files
     */
    private function cleanup_old_logs() {
        $log_dir = dirname($this->log_file);
        $log_files = glob($log_dir . '/lfcc-leave-*.log');
        
        if (count($log_files) > 5) {
            // Sort by modification time
            usort($log_files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $files_to_remove = array_slice($log_files, 0, count($log_files) - 5);
            foreach ($files_to_remove as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Email critical errors to admin
     */
    private function email_critical_error($level, $message, $context) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf('[%s] LFCC Leave Management - %s Error', $site_name, strtoupper($level));
        
        $email_message = sprintf(
            "A %s error occurred in the LFCC Leave Management plugin:\n\n" .
            "Error Level: %s\n" .
            "Message: %s\n" .
            "Time: %s\n" .
            "URL: %s\n" .
            "User: %s\n" .
            "IP Address: %s\n\n" .
            "Context:\n%s\n\n" .
            "Please check the plugin logs for more details.",
            strtoupper($level),
            strtoupper($level),
            $message,
            current_time('Y-m-d H:i:s'),
            home_url($_SERVER['REQUEST_URI'] ?? ''),
            wp_get_current_user()->user_login ?? 'Guest',
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            wp_json_encode($context, JSON_PRETTY_PRINT)
        );
        
        wp_mail($admin_email, $subject, $email_message);
    }
    
    /**
     * Get recent log entries
     */
    public function get_recent_logs($lines = 100, $level_filter = null) {
        if (!file_exists($this->log_file)) {
            return array();
        }
        
        $log_content = file_get_contents($this->log_file);
        $log_lines = explode("\n", $log_content);
        $log_lines = array_filter($log_lines); // Remove empty lines
        
        // Get last N lines
        $recent_lines = array_slice($log_lines, -$lines);
        
        // Filter by level if specified
        if ($level_filter) {
            $level_filter = strtoupper($level_filter);
            $recent_lines = array_filter($recent_lines, function($line) use ($level_filter) {
                return strpos($line, $level_filter . ':') !== false;
            });
        }
        
        // Parse log entries
        $parsed_logs = array();
        foreach ($recent_lines as $line) {
            $parsed_logs[] = $this->parse_log_line($line);
        }
        
        return array_reverse($parsed_logs); // Most recent first
    }
    
    /**
     * Parse a log line into components
     */
    private function parse_log_line($line) {
        // Pattern: [TIMESTAMP] LEVEL: MESSAGE | User | IP | Method URI | Context
        $pattern = '/^\[([^\]]+)\] ([A-Z]+): (.+)$/';
        
        if (preg_match($pattern, $line, $matches)) {
            $parts = explode(' | ', $matches[3]);
            
            return array(
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => $parts[0] ?? '',
                'user' => $parts[1] ?? '',
                'ip' => $parts[2] ?? '',
                'request' => $parts[3] ?? '',
                'context' => $parts[4] ?? '',
                'raw' => $line
            );
        }
        
        return array(
            'timestamp' => '',
            'level' => 'UNKNOWN',
            'message' => $line,
            'user' => '',
            'ip' => '',
            'request' => '',
            'context' => '',
            'raw' => $line
        );
    }
    
    /**
     * Clear all logs
     */
    public function clear_logs() {
        $log_dir = dirname($this->log_file);
        $log_files = glob($log_dir . '/lfcc-leave-*.log');
        
        foreach ($log_files as $file) {
            unlink($file);
        }
        
        $this->info('Log files cleared by admin', array('admin_user' => wp_get_current_user()->user_login));
    }
    
    /**
     * Get log statistics
     */
    public function get_log_stats() {
        $recent_logs = $this->get_recent_logs(1000); // Last 1000 entries
        
        $stats = array(
            'total_entries' => count($recent_logs),
            'by_level' => array(),
            'by_hour' => array(),
            'recent_errors' => 0,
            'file_size' => file_exists($this->log_file) ? filesize($this->log_file) : 0
        );
        
        foreach ($recent_logs as $log) {
            // Count by level
            $level = $log['level'];
            $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
            
            // Count by hour (last 24 hours)
            $log_time = strtotime($log['timestamp']);
            $hour_key = date('Y-m-d H', $log_time);
            $stats['by_hour'][$hour_key] = ($stats['by_hour'][$hour_key] ?? 0) + 1;
            
            // Count recent errors (last hour)
            if (in_array($level, array('ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY')) && 
                $log_time > (time() - 3600)) {
                $stats['recent_errors']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Export logs to file
     */
    public function export_logs($format = 'txt') {
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['path'] . '/';
        
        $filename = 'lfcc-leave-logs-export-' . date('Y-m-d-H-i-s');
        
        switch ($format) {
            case 'csv':
                return $this->export_logs_csv($export_dir . $filename . '.csv');
            case 'json':
                return $this->export_logs_json($export_dir . $filename . '.json');
            default:
                return $this->export_logs_txt($export_dir . $filename . '.txt');
        }
    }
    
    /**
     * Export logs as CSV
     */
    private function export_logs_csv($filepath) {
        $logs = $this->get_recent_logs(5000); // Last 5000 entries
        
        $file = fopen($filepath, 'w');
        if (!$file) {
            return false;
        }
        
        // CSV headers
        fputcsv($file, array('Timestamp', 'Level', 'Message', 'User', 'IP', 'Request', 'Context'));
        
        foreach ($logs as $log) {
            fputcsv($file, array(
                $log['timestamp'],
                $log['level'],
                $log['message'],
                $log['user'],
                $log['ip'],
                $log['request'],
                $log['context']
            ));
        }
        
        fclose($file);
        
        return array(
            'filepath' => $filepath,
            'url' => wp_upload_dir()['url'] . '/' . basename($filepath),
            'size' => filesize($filepath),
            'entries' => count($logs)
        );
    }
    
    /**
     * Export logs as JSON
     */
    private function export_logs_json($filepath) {
        $logs = $this->get_recent_logs(5000); // Last 5000 entries
        
        $export_data = array(
            'export_date' => current_time('Y-m-d H:i:s'),
            'plugin_version' => LFCC_LEAVE_VERSION,
            'site_url' => home_url(),
            'total_entries' => count($logs),
            'logs' => $logs
        );
        
        $json_data = wp_json_encode($export_data, JSON_PRETTY_PRINT);
        
        if (file_put_contents($filepath, $json_data)) {
            return array(
                'filepath' => $filepath,
                'url' => wp_upload_dir()['url'] . '/' . basename($filepath),
                'size' => filesize($filepath),
                'entries' => count($logs)
            );
        }
        
        return false;
    }
    
    /**
     * Export logs as text file
     */
    private function export_logs_txt($filepath) {
        if (file_exists($this->log_file)) {
            copy($this->log_file, $filepath);
            
            return array(
                'filepath' => $filepath,
                'url' => wp_upload_dir()['url'] . '/' . basename($filepath),
                'size' => filesize($filepath),
                'entries' => count(file($filepath))
            );
        }
        
        return false;
    }
    
    /**
     * Log database operations
     */
    public function log_database_operation($operation, $table, $data = array(), $result = null) {
        $context = array(
            'operation' => $operation,
            'table' => $table,
            'data' => $data,
            'result' => $result,
            'query_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
        );
        
        if ($result === false) {
            $this->error("Database operation failed: {$operation} on {$table}", $context);
        } else {
            $this->debug("Database operation: {$operation} on {$table}", $context);
        }
    }
    
    /**
     * Log email operations
     */
    public function log_email_operation($to, $subject, $result, $error = null) {
        $context = array(
            'to' => $to,
            'subject' => $subject,
            'result' => $result,
            'error' => $error
        );
        
        if ($result) {
            $this->info("Email sent successfully", $context);
        } else {
            $this->error("Email sending failed", $context);
        }
    }
    
    /**
     * Log user authentication
     */
    public function log_user_auth($username, $action, $result, $ip = null) {
        $context = array(
            'username' => $username,
            'action' => $action,
            'result' => $result,
            'ip' => $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
        
        if ($result) {
            $this->info("User authentication: {$action} successful for {$username}", $context);
        } else {
            $this->warning("User authentication: {$action} failed for {$username}", $context);
        }
    }
    
    /**
     * Log API requests
     */
    public function log_api_request($endpoint, $method, $response_code, $response_time = null) {
        $context = array(
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $response_code,
            'response_time' => $response_time,
            'user_id' => get_current_user_id()
        );
        
        $level = $response_code >= 400 ? 'error' : 'info';
        $this->log($level, "API request: {$method} {$endpoint} - {$response_code}", $context);
    }
}

