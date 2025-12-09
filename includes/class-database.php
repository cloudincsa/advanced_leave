<?php
/**
 * Database Management Class
 * Handles all database operations for the LFCC Leave Management plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class LFCC_Leave_Database {
    
    private static $instance = null;
    private $wpdb;
    
    // Table names
    public $users_table;
    public $leave_requests_table;
    public $email_logs_table;
    public $settings_table;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Define table names
        $this->users_table = $wpdb->prefix . 'lfcc_leave_users';
        $this->leave_requests_table = $wpdb->prefix . 'lfcc_leave_requests';
        $this->email_logs_table = $wpdb->prefix . 'lfcc_leave_email_logs';
        $this->settings_table = $wpdb->prefix . 'lfcc_leave_settings';
    }
    
    public function init() {
        // Database initialization if needed
    }
    
    /**
     * Create all plugin tables
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Users table
        $users_sql = "CREATE TABLE {$this->users_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            username varchar(50) NOT NULL UNIQUE,
            email varchar(100) NOT NULL UNIQUE,
            password_hash varchar(255) NOT NULL,
            first_name varchar(50) NOT NULL,
            last_name varchar(50) NOT NULL,
            department varchar(100) DEFAULT '',
            role enum('staff', 'hr', 'admin') DEFAULT 'staff',
            status enum('active', 'inactive') DEFAULT 'active',
            annual_leave int(11) DEFAULT 20,
            sick_leave int(11) DEFAULT 10,
            personal_leave int(11) DEFAULT 5,
            emergency_leave int(11) DEFAULT 3,
            annual_leave_used int(11) DEFAULT 0,
            sick_leave_used int(11) DEFAULT 0,
            personal_leave_used int(11) DEFAULT 0,
            emergency_leave_used int(11) DEFAULT 0,
            phone varchar(20) DEFAULT '',
            address text DEFAULT '',
            hire_date date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_username (username),
            KEY idx_email (email),
            KEY idx_status (status)
        ) $charset_collate;";
        
        // Leave requests table
        $requests_sql = "CREATE TABLE {$this->leave_requests_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            leave_type enum('annual', 'sick', 'personal', 'emergency') NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            total_days int(11) NOT NULL,
            reason text DEFAULT '',
            status enum('pending', 'approved', 'rejected') DEFAULT 'pending',
            approved_by int(11) DEFAULT NULL,
            approved_at datetime DEFAULT NULL,
            rejection_reason text DEFAULT '',
            comments text DEFAULT '',
            is_edited tinyint(1) DEFAULT 0,
            original_request_id int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_status (status),
            KEY idx_leave_type (leave_type),
            KEY idx_dates (start_date, end_date),
            FOREIGN KEY (user_id) REFERENCES {$this->users_table}(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Email logs table
        $email_logs_sql = "CREATE TABLE {$this->email_logs_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            recipient_email varchar(100) NOT NULL,
            recipient_name varchar(100) DEFAULT '',
            subject varchar(255) NOT NULL,
            template_type varchar(50) NOT NULL,
            content text NOT NULL,
            status enum('sent', 'failed', 'pending') DEFAULT 'pending',
            error_message text DEFAULT '',
            sent_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_recipient (recipient_email),
            KEY idx_status (status),
            KEY idx_template_type (template_type)
        ) $charset_collate;";
        
        // Settings table
        $settings_sql = "CREATE TABLE {$this->settings_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            option_name varchar(100) NOT NULL UNIQUE,
            option_value longtext DEFAULT '',
            autoload enum('yes', 'no') DEFAULT 'yes',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_option_name (option_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($users_sql);
        dbDelta($requests_sql);
        dbDelta($email_logs_sql);
        dbDelta($settings_sql);
        
        // Insert default admin user if not exists
        $this->create_default_admin();
        
        // Insert default settings
        $this->insert_default_settings();
    }
    
    /**
     * Drop all plugin tables
     */
    public function drop_tables() {
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->email_logs_table}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->leave_requests_table}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->users_table}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->settings_table}");
    }
    
    /**
     * Create default admin user
     */
    private function create_default_admin() {
        $existing_admin = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->users_table} WHERE role = %s LIMIT 1",
                'admin'
            )
        );
        
        if (!$existing_admin) {
            $this->wpdb->insert(
                $this->users_table,
                array(
                    'username' => 'admin',
                    'email' => 'admin@littlefallschristiancentre.org',
                    'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                    'first_name' => 'System',
                    'last_name' => 'Administrator',
                    'department' => 'Administration',
                    'role' => 'admin',
                    'status' => 'active',
                    'annual_leave' => 25,
                    'sick_leave' => 15,
                    'personal_leave' => 10,
                    'emergency_leave' => 5
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d')
            );
        }
    }
    
    /**
     * Insert default settings
     */
    private function insert_default_settings() {
        $default_settings = array(
            'organization_name' => 'Little Falls Christian Centre',
            'organization_email' => 'hr@littlefallschristiancentre.org',
            'organization_phone' => '+27 12 345 6789',
            'organization_address' => '123 Church Street, Little Falls, South Africa',
            'subdomain_name' => 'leave',
            'subdomain_enabled' => 'yes',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'from_email' => 'noreply@littlefallschristiancentre.org',
            'from_name' => 'LFCC Leave Management',
            'enable_user_registration' => 'yes',
            'require_admin_approval' => 'yes',
            'weekend_counts_as_leave' => 'yes',
            'default_annual_leave' => '20',
            'default_sick_leave' => '10',
            'default_personal_leave' => '5',
            'default_emergency_leave' => '3'
        );
        
        foreach ($default_settings as $name => $value) {
            $existing = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT id FROM {$this->settings_table} WHERE option_name = %s",
                    $name
                )
            );
            
            if (!$existing) {
                $this->wpdb->insert(
                    $this->settings_table,
                    array(
                        'option_name' => $name,
                        'option_value' => $value
                    ),
                    array('%s', '%s')
                );
            }
        }
    }
    
    /**
     * Get user by ID
     */
    public function get_user($user_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->users_table} WHERE id = %d",
                $user_id
            )
        );
    }
    
    /**
     * Get user by username
     */
    public function get_user_by_username($username) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->users_table} WHERE username = %s",
                $username
            )
        );
    }
    
    /**
     * Get user by email
     */
    public function get_user_by_email($email) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->users_table} WHERE email = %s",
                $email
            )
        );
    }
    
    /**
     * Get all users
     */
    public function get_all_users($status = 'active') {
        if ($status === 'all') {
            return $this->wpdb->get_results(
                "SELECT * FROM {$this->users_table} ORDER BY first_name, last_name"
            );
        }
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->users_table} WHERE status = %s ORDER BY first_name, last_name",
                $status
            )
        );
    }
    
    /**
     * Create new user
     */
    public function create_user($user_data) {
        return $this->wpdb->insert($this->users_table, $user_data);
    }
    
    /**
     * Update user
     */
    public function update_user($user_id, $user_data) {
        return $this->wpdb->update(
            $this->users_table,
            $user_data,
            array('id' => $user_id),
            null,
            array('%d')
        );
    }
    
    /**
     * Delete user
     */
    public function delete_user($user_id) {
        return $this->wpdb->delete(
            $this->users_table,
            array('id' => $user_id),
            array('%d')
        );
    }
    
    /**
     * Get leave request by ID
     */
    public function get_leave_request($request_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT lr.*, u.first_name, u.last_name, u.email, u.department 
                 FROM {$this->leave_requests_table} lr 
                 JOIN {$this->users_table} u ON lr.user_id = u.id 
                 WHERE lr.id = %d",
                $request_id
            )
        );
    }
    
    /**
     * Get leave requests
     */
    public function get_leave_requests($filters = array()) {
        $where_clauses = array();
        $where_values = array();
        
        if (!empty($filters['user_id'])) {
            $where_clauses[] = "lr.user_id = %d";
            $where_values[] = $filters['user_id'];
        }
        
        if (!empty($filters['status'])) {
            $where_clauses[] = "lr.status = %s";
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['leave_type'])) {
            $where_clauses[] = "lr.leave_type = %s";
            $where_values[] = $filters['leave_type'];
        }
        
        if (!empty($filters['start_date'])) {
            $where_clauses[] = "lr.start_date >= %s";
            $where_values[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where_clauses[] = "lr.end_date <= %s";
            $where_values[] = $filters['end_date'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT lr.*, u.first_name, u.last_name, u.email, u.department 
                FROM {$this->leave_requests_table} lr 
                JOIN {$this->users_table} u ON lr.user_id = u.id 
                {$where_sql}
                ORDER BY lr.created_at DESC";
        
        if (!empty($where_values)) {
            return $this->wpdb->get_results(
                $this->wpdb->prepare($sql, $where_values)
            );
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Create leave request
     */
    public function create_leave_request($request_data) {
        return $this->wpdb->insert($this->leave_requests_table, $request_data);
    }
    
    /**
     * Update leave request
     */
    public function update_leave_request($request_id, $request_data) {
        return $this->wpdb->update(
            $this->leave_requests_table,
            $request_data,
            array('id' => $request_id),
            null,
            array('%d')
        );
    }
    
    /**
     * Delete leave request
     */
    public function delete_leave_request($request_id) {
        return $this->wpdb->delete(
            $this->leave_requests_table,
            array('id' => $request_id),
            array('%d')
        );
    }
    
    /**
     * Log email
     */
    public function log_email($email_data) {
        return $this->wpdb->insert($this->email_logs_table, $email_data);
    }
    
    /**
     * Get email logs
     */
    public function get_email_logs($limit = 50) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->email_logs_table} ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
    }
    
    /**
     * Get setting
     */
    public function get_setting($option_name, $default = '') {
        $value = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT option_value FROM {$this->settings_table} WHERE option_name = %s",
                $option_name
            )
        );
        
        return $value !== null ? $value : $default;
    }
    
    /**
     * Update setting
     */
    public function update_setting($option_name, $option_value) {
        $existing = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->settings_table} WHERE option_name = %s",
                $option_name
            )
        );
        
        if ($existing) {
            return $this->wpdb->update(
                $this->settings_table,
                array('option_value' => $option_value),
                array('option_name' => $option_name),
                array('%s'),
                array('%s')
            );
        } else {
            return $this->wpdb->insert(
                $this->settings_table,
                array(
                    'option_name' => $option_name,
                    'option_value' => $option_value
                ),
                array('%s', '%s')
            );
        }
    }
    
    /**
     * Delete setting
     */
    public function delete_setting($option_name) {
        return $this->wpdb->delete(
            $this->settings_table,
            array('option_name' => $option_name),
            array('%s')
        );
    }
    
    /**
     * Get dashboard statistics
     */
    public function get_dashboard_stats() {
        $stats = array();
        
        // Total users
        $stats['total_users'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->users_table} WHERE status = 'active'"
        );
        
        // Pending requests
        $stats['pending_requests'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->leave_requests_table} WHERE status = 'pending'"
        );
        
        // Approved requests this month
        $stats['approved_this_month'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->leave_requests_table} 
             WHERE status = 'approved' AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
             AND YEAR(created_at) = YEAR(CURRENT_DATE())"
        );
        
        // Total requests
        $stats['total_requests'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->leave_requests_table}"
        );
        
        return $stats;
    }
}

