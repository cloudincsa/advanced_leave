<?php
/**
 * LFCC Leave Management User Manager
 * Handles user management operations and integrations
 */

if (!defined('ABSPATH')) {
    exit;
}

class LFCC_Leave_User_Manager {
    
    private static $instance = null;
    private $db;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = LFCC_Leave_Database::get_instance();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // WordPress user integration hooks
        add_action('wp_login', array($this, 'sync_wp_user_login'), 10, 2);
        add_action('user_register', array($this, 'sync_new_wp_user'), 10, 1);
        add_action('profile_update', array($this, 'sync_wp_user_update'), 10, 2);
        add_action('delete_user', array($this, 'handle_wp_user_deletion'), 10, 1);
        
        // Custom user management hooks
        add_action('lfcc_user_created', array($this, 'handle_user_created'), 10, 2);
        add_action('lfcc_user_updated', array($this, 'handle_user_updated'), 10, 2);
        add_action('lfcc_user_deleted', array($this, 'handle_user_deleted'), 10, 1);
        
        // Admin interface hooks
        add_action('admin_init', array($this, 'handle_admin_actions'));
        add_action('wp_ajax_lfcc_bulk_user_action', array($this, 'handle_bulk_user_action'));
        add_action('wp_ajax_lfcc_export_users', array($this, 'handle_export_users'));
        add_action('wp_ajax_lfcc_import_users', array($this, 'handle_import_users'));
    }
    
    /**
     * Create a new user with leave allocations
     */
    public function create_user($user_data) {
        // Validate required fields
        $required_fields = array('username', 'email', 'first_name', 'last_name', 'password_hash');
        foreach ($required_fields as $field) {
            if (empty($user_data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Field %s is required.', 'lfcc-leave-management'), $field));
            }
        }
        
        // Check for existing username or email
        if ($this->db->get_user_by_username($user_data['username'])) {
            return new WP_Error('username_exists', __('Username already exists.', 'lfcc-leave-management'));
        }
        
        if ($this->db->get_user_by_email($user_data['email'])) {
            return new WP_Error('email_exists', __('Email address already exists.', 'lfcc-leave-management'));
        }
        
        // Set default values
        $defaults = array(
            'role' => 'staff',
            'status' => 'active',
            'department' => '',
            'phone' => '',
            'address' => '',
            'hire_date' => current_time('Y-m-d'),
            'annual_leave' => LFCC_Leave_Settings::get_option('default_annual_leave', 20),
            'sick_leave' => LFCC_Leave_Settings::get_option('default_sick_leave', 10),
            'personal_leave' => LFCC_Leave_Settings::get_option('default_personal_leave', 5),
            'emergency_leave' => LFCC_Leave_Settings::get_option('default_emergency_leave', 3),
            'annual_leave_used' => 0,
            'sick_leave_used' => 0,
            'personal_leave_used' => 0,
            'emergency_leave_used' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $user_data = wp_parse_args($user_data, $defaults);
        
        // Create user in database
        $user_id = $this->db->create_user($user_data);
        
        if ($user_id) {
            // Trigger user created action
            do_action('lfcc_user_created', $user_id, $user_data);
            
            // Log user creation
            $this->log_user_action('created', $user_id, $user_data);
            
            return $user_id;
        }
        
        return new WP_Error('creation_failed', __('Failed to create user.', 'lfcc-leave-management'));
    }
    
    /**
     * Update user information
     */
    public function update_user($user_id, $user_data) {
        $existing_user = $this->db->get_user($user_id);
        if (!$existing_user) {
            return new WP_Error('user_not_found', __('User not found.', 'lfcc-leave-management'));
        }
        
        // Check email uniqueness if email is being changed
        if (isset($user_data['email']) && $user_data['email'] !== $existing_user->email) {
            $existing_email_user = $this->db->get_user_by_email($user_data['email']);
            if ($existing_email_user && $existing_email_user->id !== $user_id) {
                return new WP_Error('email_exists', __('Email address already exists.', 'lfcc-leave-management'));
            }
        }
        
        // Add updated timestamp
        $user_data['updated_at'] = current_time('mysql');
        
        // Update user in database
        $result = $this->db->update_user($user_id, $user_data);
        
        if ($result !== false) {
            // Trigger user updated action
            do_action('lfcc_user_updated', $user_id, $user_data);
            
            // Log user update
            $this->log_user_action('updated', $user_id, $user_data);
            
            return true;
        }
        
        return new WP_Error('update_failed', __('Failed to update user.', 'lfcc-leave-management'));
    }
    
    /**
     * Delete user and handle cleanup
     */
    public function delete_user($user_id) {
        $user = $this->db->get_user($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', __('User not found.', 'lfcc-leave-management'));
        }
        
        // Check if user has pending leave requests
        $pending_requests = $this->db->get_leave_requests(array(
            'user_id' => $user_id,
            'status' => 'pending'
        ));
        
        if (!empty($pending_requests)) {
            return new WP_Error('has_pending_requests', __('Cannot delete user with pending leave requests.', 'lfcc-leave-management'));
        }
        
        // Archive or delete leave requests based on settings
        $archive_requests = LFCC_Leave_Settings::get_option('archive_requests_on_user_deletion', 'yes') === 'yes';
        
        if ($archive_requests) {
            // Archive leave requests
            $this->db->archive_user_leave_requests($user_id);
        } else {
            // Delete leave requests
            $this->db->delete_user_leave_requests($user_id);
        }
        
        // Delete user sessions
        $this->db->delete_user_sessions($user_id);
        
        // Delete user
        $result = $this->db->delete_user($user_id);
        
        if ($result) {
            // Trigger user deleted action
            do_action('lfcc_user_deleted', $user_id);
            
            // Log user deletion
            $this->log_user_action('deleted', $user_id, array('username' => $user->username));
            
            return true;
        }
        
        return new WP_Error('deletion_failed', __('Failed to delete user.', 'lfcc-leave-management'));
    }
    
    /**
     * Get users with filtering and pagination
     */
    public function get_users($args = array()) {
        $defaults = array(
            'status' => 'all',
            'role' => 'all',
            'department' => 'all',
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return $this->db->get_users_filtered($args);
    }
    
    /**
     * Get user statistics
     */
    public function get_user_statistics() {
        return array(
            'total_users' => $this->db->count_users(),
            'active_users' => $this->db->count_users(array('status' => 'active')),
            'inactive_users' => $this->db->count_users(array('status' => 'inactive')),
            'staff_users' => $this->db->count_users(array('role' => 'staff')),
            'hr_users' => $this->db->count_users(array('role' => 'hr')),
            'admin_users' => $this->db->count_users(array('role' => 'admin')),
            'users_with_pending_requests' => $this->db->count_users_with_pending_requests(),
            'users_on_leave_today' => $this->db->count_users_on_leave_today()
        );
    }
    
    /**
     * Reset user leave balances (typically done annually)
     */
    public function reset_leave_balances($user_ids = null, $leave_types = null) {
        if ($user_ids === null) {
            $user_ids = $this->db->get_all_user_ids();
        }
        
        if ($leave_types === null) {
            $leave_types = array('annual', 'sick', 'personal', 'emergency');
        }
        
        $reset_count = 0;
        
        foreach ($user_ids as $user_id) {
            $reset_data = array();
            
            foreach ($leave_types as $leave_type) {
                $reset_data[$leave_type . '_leave_used'] = 0;
            }
            
            if (!empty($reset_data)) {
                $result = $this->db->update_user($user_id, $reset_data);
                if ($result !== false) {
                    $reset_count++;
                }
            }
        }
        
        // Log the reset operation
        $this->log_user_action('leave_balance_reset', 0, array(
            'affected_users' => $reset_count,
            'leave_types' => $leave_types,
            'reset_date' => current_time('mysql')
        ));
        
        return $reset_count;
    }
    
    /**
     * Bulk update users
     */
    public function bulk_update_users($user_ids, $update_data) {
        $updated_count = 0;
        $errors = array();
        
        foreach ($user_ids as $user_id) {
            $result = $this->update_user($user_id, $update_data);
            
            if (is_wp_error($result)) {
                $errors[] = sprintf(__('User ID %d: %s', 'lfcc-leave-management'), $user_id, $result->get_error_message());
            } else {
                $updated_count++;
            }
        }
        
        return array(
            'updated' => $updated_count,
            'errors' => $errors
        );
    }
    
    /**
     * Export users to CSV
     */
    public function export_users_csv($args = array()) {
        $users = $this->get_users($args);
        
        if (empty($users)) {
            return new WP_Error('no_users', __('No users found to export.', 'lfcc-leave-management'));
        }
        
        $filename = 'lfcc-users-export-' . date('Y-m-d-H-i-s') . '.csv';
        $filepath = wp_upload_dir()['path'] . '/' . $filename;
        
        $file = fopen($filepath, 'w');
        
        if (!$file) {
            return new WP_Error('file_error', __('Could not create export file.', 'lfcc-leave-management'));
        }
        
        // CSV headers
        $headers = array(
            'ID', 'Username', 'Email', 'First Name', 'Last Name', 'Department', 'Role', 'Status',
            'Phone', 'Hire Date', 'Annual Leave', 'Annual Used', 'Sick Leave', 'Sick Used',
            'Personal Leave', 'Personal Used', 'Emergency Leave', 'Emergency Used', 'Created At'
        );
        
        fputcsv($file, $headers);
        
        // User data
        foreach ($users as $user) {
            $row = array(
                $user->id,
                $user->username,
                $user->email,
                $user->first_name,
                $user->last_name,
                $user->department,
                $user->role,
                $user->status,
                $user->phone,
                $user->hire_date,
                $user->annual_leave,
                $user->annual_leave_used,
                $user->sick_leave,
                $user->sick_leave_used,
                $user->personal_leave,
                $user->personal_leave_used,
                $user->emergency_leave,
                $user->emergency_leave_used,
                $user->created_at
            );
            
            fputcsv($file, $row);
        }
        
        fclose($file);
        
        return array(
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => wp_upload_dir()['url'] . '/' . $filename,
            'count' => count($users)
        );
    }
    
    /**
     * Sync WordPress user login with leave management system
     */
    public function sync_wp_user_login($user_login, $user) {
        // Check if user exists in leave management system
        $leave_user = $this->db->get_user_by_email($user->user_email);
        
        if (!$leave_user && LFCC_Leave_Settings::get_option('auto_create_from_wp_users', 'no') === 'yes') {
            // Auto-create leave management user from WordPress user
            $user_data = array(
                'username' => $user->user_login,
                'email' => $user->user_email,
                'first_name' => $user->first_name ?: '',
                'last_name' => $user->last_name ?: '',
                'password_hash' => $user->user_pass,
                'role' => 'staff',
                'status' => 'active'
            );
            
            $this->create_user($user_data);
        }
    }
    
    /**
     * Handle WordPress user registration
     */
    public function sync_new_wp_user($user_id) {
        if (LFCC_Leave_Settings::get_option('auto_create_from_wp_users', 'no') !== 'yes') {
            return;
        }
        
        $wp_user = get_user_by('id', $user_id);
        if (!$wp_user) {
            return;
        }
        
        $user_data = array(
            'username' => $wp_user->user_login,
            'email' => $wp_user->user_email,
            'first_name' => $wp_user->first_name ?: '',
            'last_name' => $wp_user->last_name ?: '',
            'password_hash' => $wp_user->user_pass,
            'role' => 'staff',
            'status' => 'active'
        );
        
        $this->create_user($user_data);
    }
    
    /**
     * Log user actions for audit trail
     */
    private function log_user_action($action, $user_id, $data = array()) {
        $log_entry = array(
            'action' => $action,
            'user_id' => $user_id,
            'admin_user_id' => get_current_user_id(),
            'data' => wp_json_encode($data),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => current_time('mysql')
        );
        
        $this->db->log_user_action($log_entry);
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle bulk actions from admin interface
        if (isset($_POST['lfcc_bulk_action']) && isset($_POST['lfcc_user_ids'])) {
            $this->process_bulk_action($_POST['lfcc_bulk_action'], $_POST['lfcc_user_ids']);
        }
    }
    
    /**
     * Process bulk actions
     */
    private function process_bulk_action($action, $user_ids) {
        if (!wp_verify_nonce($_POST['lfcc_bulk_nonce'], 'lfcc_bulk_users')) {
            wp_die(__('Security check failed.', 'lfcc-leave-management'));
        }
        
        $user_ids = array_map('intval', (array) $user_ids);
        
        switch ($action) {
            case 'activate':
                $this->bulk_update_users($user_ids, array('status' => 'active'));
                break;
                
            case 'deactivate':
                $this->bulk_update_users($user_ids, array('status' => 'inactive'));
                break;
                
            case 'reset_passwords':
                $this->bulk_reset_passwords($user_ids);
                break;
                
            case 'reset_leave_balances':
                $this->reset_leave_balances($user_ids);
                break;
                
            case 'delete':
                $this->bulk_delete_users($user_ids);
                break;
        }
    }
    
    /**
     * Bulk reset passwords
     */
    private function bulk_reset_passwords($user_ids) {
        $email_handler = LFCC_Leave_Email_Handler::get_instance();
        $reset_count = 0;
        
        foreach ($user_ids as $user_id) {
            $user = $this->db->get_user($user_id);
            if ($user) {
                $temp_password = wp_generate_password(12, false);
                $result = $this->update_user($user_id, array(
                    'password_hash' => password_hash($temp_password, PASSWORD_DEFAULT)
                ));
                
                if (!is_wp_error($result)) {
                    $email_handler->send_password_reset_email($user, $temp_password);
                    $reset_count++;
                }
            }
        }
        
        return $reset_count;
    }
    
    /**
     * Bulk delete users
     */
    private function bulk_delete_users($user_ids) {
        $deleted_count = 0;
        
        foreach ($user_ids as $user_id) {
            $result = $this->delete_user($user_id);
            if (!is_wp_error($result)) {
                $deleted_count++;
            }
        }
        
        return $deleted_count;
    }
    
    /**
     * Handle AJAX bulk user action
     */
    public function handle_bulk_user_action() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'lfcc-leave-management')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'lfcc_bulk_users')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'lfcc-leave-management')));
        }
        
        $action = sanitize_text_field($_POST['action']);
        $user_ids = array_map('intval', $_POST['user_ids']);
        
        $result = $this->process_bulk_action($action, $user_ids);
        
        wp_send_json_success(array(
            'message' => sprintf(__('Bulk action completed. %d users affected.', 'lfcc-leave-management'), $result),
            'affected_count' => $result
        ));
    }
    
    /**
     * Handle AJAX export users
     */
    public function handle_export_users() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'lfcc-leave-management')));
        }
        
        $args = array(
            'status' => sanitize_text_field($_POST['status'] ?? 'all'),
            'role' => sanitize_text_field($_POST['role'] ?? 'all'),
            'department' => sanitize_text_field($_POST['department'] ?? 'all')
        );
        
        $result = $this->export_users_csv($args);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
}

