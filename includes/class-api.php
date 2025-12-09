<?php
/**
 * LFCC Leave Management API Endpoints
 * Handles all AJAX requests for both admin and frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class LFCC_Leave_API {
    
    private static $instance = null;
    private $db;
    private $email_handler;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = LFCC_Leave_Database::get_instance();
        $this->email_handler = LFCC_Leave_Email_Handler::get_instance();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Public endpoints (no authentication required)
        add_action('wp_ajax_nopriv_lfcc_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_lfcc_register', array($this, 'handle_register'));
        add_action('wp_ajax_nopriv_lfcc_forgot_password', array($this, 'handle_forgot_password'));
        add_action('wp_ajax_nopriv_lfcc_reset_password', array($this, 'handle_reset_password'));
        
        // Authenticated endpoints
        add_action('wp_ajax_lfcc_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_lfcc_get_user_data', array($this, 'handle_get_user_data'));
        add_action('wp_ajax_lfcc_update_profile', array($this, 'handle_update_profile'));
        add_action('wp_ajax_lfcc_change_password', array($this, 'handle_change_password'));
        
        // Leave request endpoints
        add_action('wp_ajax_lfcc_submit_leave_request', array($this, 'handle_submit_leave_request'));
        add_action('wp_ajax_lfcc_get_leave_requests', array($this, 'handle_get_leave_requests'));
        add_action('wp_ajax_lfcc_update_leave_request', array($this, 'handle_update_leave_request'));
        add_action('wp_ajax_lfcc_delete_leave_request', array($this, 'handle_delete_leave_request'));
        add_action('wp_ajax_lfcc_get_leave_balance', array($this, 'handle_get_leave_balance'));
        add_action('wp_ajax_lfcc_calculate_leave_days', array($this, 'handle_calculate_leave_days'));
        
        // Calendar endpoints
        add_action('wp_ajax_lfcc_get_calendar_data', array($this, 'handle_get_calendar_data'));
        
        // Admin endpoints
        add_action('wp_ajax_lfcc_admin_get_users', array($this, 'handle_admin_get_users'));
        add_action('wp_ajax_lfcc_admin_create_user', array($this, 'handle_admin_create_user'));
        add_action('wp_ajax_lfcc_admin_update_user', array($this, 'handle_admin_update_user'));
        add_action('wp_ajax_lfcc_admin_delete_user', array($this, 'handle_admin_delete_user'));
        add_action('wp_ajax_lfcc_admin_reset_user_password', array($this, 'handle_admin_reset_user_password'));
        
        add_action('wp_ajax_lfcc_admin_get_leave_requests', array($this, 'handle_admin_get_leave_requests'));
        add_action('wp_ajax_lfcc_admin_approve_leave_request', array($this, 'handle_admin_approve_leave_request'));
        add_action('wp_ajax_lfcc_admin_reject_leave_request', array($this, 'handle_admin_reject_leave_request'));
        add_action('wp_ajax_lfcc_get_request_details', array($this, 'handle_get_request_details'));
        
        add_action('wp_ajax_lfcc_export_data', array($this, 'handle_export_data'));
        add_action('wp_ajax_lfcc_get_dashboard_stats', array($this, 'handle_get_dashboard_stats'));
        
        // Email template endpoints
        add_action('wp_ajax_lfcc_preview_email_template', array($this, 'handle_preview_email_template'));
        add_action('wp_ajax_lfcc_send_test_email', array($this, 'handle_send_test_email'));
    }
    
    /**
     * Verify nonce and authenticate user
     */
    private function verify_request($nonce_action = 'lfcc_leave_frontend_nonce', $require_admin = false) {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', $nonce_action)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'lfcc-leave-management')));
            return false;
        }
        
        if ($require_admin && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'lfcc-leave-management')));
            return false;
        }
        
        return true;
    }
    
    /**
     * Authenticate user with custom session
     */
    private function authenticate_user() {
        $session_token = $_COOKIE['lfcc_session'] ?? '';
        if (empty($session_token)) {
            wp_send_json_error(array('message' => __('Authentication required.', 'lfcc-leave-management'), 'code' => 'auth_required'));
            return false;
        }
        
        $user = $this->db->get_user_by_session($session_token);
        if (!$user) {
            wp_send_json_error(array('message' => __('Invalid session.', 'lfcc-leave-management'), 'code' => 'invalid_session'));
            return false;
        }
        
        return $user;
    }
    
    /**
     * Handle user login
     */
    public function handle_login() {
        $this->verify_request();
        
        $username = sanitize_text_field($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => __('Username and password are required.', 'lfcc-leave-management')));
        }
        
        $user = $this->db->get_user_by_username($username);
        if (!$user || !password_verify($password, $user->password_hash)) {
            wp_send_json_error(array('message' => __('Invalid username or password.', 'lfcc-leave-management')));
        }
        
        if ($user->status !== 'active') {
            wp_send_json_error(array('message' => __('Your account is inactive. Please contact an administrator.', 'lfcc-leave-management')));
        }
        
        // Create session
        $session_token = wp_generate_password(32, false);
        $expires = $remember ? time() + (30 * DAY_IN_SECONDS) : time() + (24 * HOUR_IN_SECONDS);
        
        $this->db->create_user_session($user->id, $session_token, $expires);
        
        // Set cookie
        setcookie('lfcc_session', $session_token, $expires, '/', '', is_ssl(), true);
        
        // Update last login
        $this->db->update_user($user->id, array('last_login' => current_time('mysql')));
        
        wp_send_json_success(array(
            'message' => __('Login successful!', 'lfcc-leave-management'),
            'user' => array(
                'id' => $user->id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'department' => $user->department
            )
        ));
    }
    
    /**
     * Handle user registration
     */
    public function handle_register() {
        if (LFCC_Leave_Settings::get_option('enable_user_registration') !== 'yes') {
            wp_send_json_error(array('message' => __('User registration is disabled.', 'lfcc-leave-management')));
        }
        
        $this->verify_request();
        
        $data = array(
            'username' => sanitize_text_field($_POST['username'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
            'department' => sanitize_text_field($_POST['department'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? '')
        );
        
        // Validation
        if (empty($data['username']) || empty($data['email']) || empty($data['password']) || 
            empty($data['first_name']) || empty($data['last_name'])) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'lfcc-leave-management')));
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            wp_send_json_error(array('message' => __('Passwords do not match.', 'lfcc-leave-management')));
        }
        
        if (strlen($data['password']) < 6) {
            wp_send_json_error(array('message' => __('Password must be at least 6 characters long.', 'lfcc-leave-management')));
        }
        
        // Check if username or email already exists
        if ($this->db->get_user_by_username($data['username'])) {
            wp_send_json_error(array('message' => __('Username already exists.', 'lfcc-leave-management')));
        }
        
        if ($this->db->get_user_by_email($data['email'])) {
            wp_send_json_error(array('message' => __('Email address already exists.', 'lfcc-leave-management')));
        }
        
        // Create user
        $user_data = array(
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'department' => $data['department'],
            'phone' => $data['phone'],
            'role' => 'staff',
            'status' => LFCC_Leave_Settings::get_option('auto


_approve_registrations', 'no') === 'yes' ? 'active' : 'pending',
            'annual_leave' => LFCC_Leave_Settings::get_option('default_annual_leave', 20),
            'sick_leave' => LFCC_Leave_Settings::get_option('default_sick_leave', 10),
            'personal_leave' => LFCC_Leave_Settings::get_option('default_personal_leave', 5),
            'emergency_leave' => LFCC_Leave_Settings::get_option('default_emergency_leave', 3),
            'hire_date' => current_time('Y-m-d')
        );
        
        $user_id = $this->db->create_user($user_data);
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Failed to create account. Please try again.', 'lfcc-leave-management')));
        }
        
        // Send welcome email if enabled
        if (LFCC_Leave_Settings::get_option('send_welcome_email') === 'yes') {
            $user = $this->db->get_user($user_id);
            $this->email_handler->send_welcome_email($user, $data['password']);
        }
        
        wp_send_json_success(array(
            'message' => __('Account created successfully! You can now log in.', 'lfcc-leave-management')
        ));
    }
    
    /**
     * Handle forgot password
     */
    public function handle_forgot_password() {
        $this->verify_request();
        
        $email = sanitize_email($_POST['email'] ?? '');
        if (empty($email)) {
            wp_send_json_error(array('message' => __('Email address is required.', 'lfcc-leave-management')));
        }
        
        $user = $this->db->get_user_by_email($email);
        if (!$user) {
            // Don't reveal if email exists or not
            wp_send_json_success(array(
                'message' => __('If an account with that email exists, a password reset link has been sent.', 'lfcc-leave-management')
            ));
        }
        
        // Generate reset token
        $reset_token = wp_generate_password(32, false);
        $expires = time() + HOUR_IN_SECONDS; // 1 hour expiry
        
        $this->db->create_password_reset_token($user->id, $reset_token, $expires);
        
        // Send reset email
        $this->email_handler->send_password_reset_email($user, $reset_token);
        
        wp_send_json_success(array(
            'message' => __('Password reset link has been sent to your email.', 'lfcc-leave-management')
        ));
    }
    
    /**
     * Handle password reset
     */
    public function handle_reset_password() {
        $this->verify_request();
        
        $token = sanitize_text_field($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($token) || empty($password) || empty($confirm_password)) {
            wp_send_json_error(array('message' => __('All fields are required.', 'lfcc-leave-management')));
        }
        
        if ($password !== $confirm_password) {
            wp_send_json_error(array('message' => __('Passwords do not match.', 'lfcc-leave-management')));
        }
        
        if (strlen($password) < 6) {
            wp_send_json_error(array('message' => __('Password must be at least 6 characters long.', 'lfcc-leave-management')));
        }
        
        // Verify token
        $user_id = $this->db->verify_password_reset_token($token);
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid or expired reset token.', 'lfcc-leave-management')));
        }
        
        // Update password
        $result = $this->db->update_user($user_id, array(
            'password_hash' => password_hash($password, PASSWORD_DEFAULT)
        ));
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to update password.', 'lfcc-leave-management')));
        }
        
        // Delete reset token
        $this->db->delete_password_reset_token($token);
        
        wp_send_json_success(array(
            'message' => __('Password updated successfully! You can now log in.', 'lfcc-leave-management')
        ));
    }
    
    /**
     * Handle user logout
     */
    public function handle_logout() {
        $session_token = $_COOKIE['lfcc_session'] ?? '';
        if (!empty($session_token)) {
            $this->db->delete_user_session($session_token);
        }
        
        // Clear cookie
        setcookie('lfcc_session', '', time() - 3600, '/', '', is_ssl(), true);
        
        wp_send_json_success(array(
            'message' => __('Logged out successfully.', 'lfcc-leave-management')
        ));
    }
    
    /**
     * Get current user data
     */
    public function handle_get_user_data() {
        $user = $this->authenticate_user();
        if (!$user) return;
        
        wp_send_json_success(array(
            'user' => array(
                'id' => $user->id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'department' => $user->department,
                'role' => $user->role,
                'hire_date' => $user->hire_date,
                'annual_leave' => $user->annual_leave,
                'sick_leave' => $user->sick_leave,
                'personal_leave' => $user->personal_leave,
                'emergency_leave' => $user->emergency_leave,
                'annual_leave_used' => $user->annual_leave_used,
                'sick_leave_used' => $user->sick_leave_used,
                'personal_leave_used' => $user->personal_leave_used,
                'emergency_leave_used' => $user->emergency_leave_used
            )
        ));
    }
    
    /**
     * Update user profile
     */
    public function handle_update_profile() {
        $user = $this->authenticate_user();
        if (!$user) return;
        
        $data = array(
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'address' => sanitize_textarea_field($_POST['address'] ?? '')
        );
        
        // Validation
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
            wp_send_json_error(array('message' => __('First name, last name, and email are required.', 'lfcc-leave-management')));
        }
        
        // Check if email is already used by another user
        $existing_user = $this->db->get_user_by_email($data['email']);
        if ($existing_user && $existing_user->id !== $user->id) {
            wp_send_json_error(array('message' => __('Email address is already in use.', 'lfcc-leave-management')));
        }
        
        $result = $this->db->update_user($user->id, $data);
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to update profile.', 'lfcc-leave-management')));
        }
        
        wp_send_json_success(array(
            'message' => __('Profile updated successfully!', 'lfcc-leave-management')
        ));
    }
    
    /**
     * Change user password
     */
    public function handle_change_password() {
        $user = $this->authenticate_user();
        if (!$user) return;
        
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            wp_send_json_error(array('message' => __('All password fields are required.', 'lfcc-leave-management')));
        }
        
        if (!password_verify($current_password, $user->password_hash)) {
            wp_send_json_error(array('message' => __('Current password is incorrect.', 'lfcc-leave-management')));
        }
        
        if ($new_password !== $confirm_password) {
            wp_send_json_error(array('message' => __('New passwords do not match.', 'lfcc-leave-management')));
        }
        
        if (strlen($new_password) < 6) {
            wp_send_json_error(array('message' => __('New password must be at least 6 characters long.', 'lfcc-leave-management')));
        }
        
        $result = $this->db->update_user($user->id, array(
            'password_hash' => password_hash($new_password, PASSWORD_DEFAULT)
        ));
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to change password.', 'lfcc-leave-management')));
        }
        
        wp_send_json_success(array(
            'message' => __('Password changed successfully!', 'lfcc-leave-management')
        ));
    }
    
    /**
     * Submit leave request
     */
    public function handle_submit_leave_request() {
        $user = $this->authenticate_user();
        if (!$user) return;
        
        $data = array(
            'leave_type' => sanitize_text_field($_POST['leave_type'] ?? ''),
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date' => sanitize_text_field($_POST['end_date'] ?? ''),
            'reason' => sanitize_textarea_field($_POST['reason'] ?? '')
        );
        
        // Validation
        if (empty($data['leave_type']) || empty($data['start_date']) || empty($data['end_date'])) {
            wp_send_json_error(array('message' => __('Leave type, start date, and end date are required.', 'lfcc-leave-management')));
        }
        
        // Validate dates
        $start_date = strtotime($data['start_date']);
        $end_date = strtotime($data['end_date']);
        
        if ($start_date === false || $end_date === false) {
            wp_send_json_error(array('message' => __('Invalid date format.', 'lfcc-leave-management')));
        }
        
        if ($start_date > $end_date) {
            wp_send_json_error(array('message' => __('Start date cannot be after end date.', 'lfcc-leave-management')));
        }
        
        if ($start_date < strtotime('today')) {
            wp_send_json_error(array('message' => __('Start date cannot be in the past.', 'lfcc-leave-management')));
        }
        
        // Calculate total days
        $total_days = $this->calculate_leave_days($data['start_date'], $data['end_date']);
        
        // Check leave balance
        $leave_type_field = $data['leave_type'] . '_leave';
        $leave_used_field = $data['leave_type'] . '_leave_used';
        
        $available_days = $user->{$leave_type_field} - $user->{$leave_used_field};
        if ($total_days > $available_days) {
            wp_send_json_error(array('message' => sprintf(
                __('Insufficient leave balance. You have %d days available.', 'lfcc-leave-management'),
                $available_days
            )));
        }
        
        // Check for overlapping requests
        $overlapping = $this->db->check_overlapping_leave_requests($user->id, $data['start_date'], $data['end_date']);
        if ($overlapping) {
            wp_send_json_error(array('message' => __('You already have a leave request for this period.', 'lfcc-leave-management')));
        }
        
        // Create leave request
        $request_data = array(
            'user_id' => $user->id,
            'leave_type' => $data['leave_type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'total_days' => $total_days,
            'reason' => $data['reason'],
            'status' => 'pending'
        );
        
        $request_id = $this->db->create_leave_request($request_data);
        if (!$request_id) {
            wp_send_json_error(array('message' => __('Failed to submit leave request.', 'lfcc-leave-management')));
        }
        
        // Send notification to HR
        if (LFCC_Leave_Settings::get_option('notify_hr_on_request') === 'yes') {
            $leave_request = $this->db->get_leave_request($request_id);
            $this->email_handler->send_leave_request_notification($leave_request, $user);
        }
        
        wp_send_json_success(array(
            'message' => __('Leave request submitted successfully!', 'lfcc-leave-management'),
            'request_id' => $request_id
        ));
    }
    
    /**
     * Get user's leave requests
     */
    public function handle_get_leave_requests() {
        $user = $this->authenticate_user();
        if (!$user) return;
        
        $status = sanitize_text_field($_POST['status'] ?? 'all');
        $leave_type = sanitize_text_field($_POST['leave_type'] ?? 'all');
        
        $filters = array('user_id' => $user->id);
        if ($status !== 'all') {
            $filters['status'] = $status;
        }
        if ($leave_type !== 'all') {
            $filters['leave_type'] = $leave_type;
        }
        
        $requests = $this->db->get_leave_requests($filters);
        
        wp_send_json_success(array(
            'requests' => $requests
        ));
    }
    
    /**
     * Update leave request
     */
    public function handle_update_leave_request() {
        $user = $this->authenticate_user();
        if (!$user) return;
        
        $request_id = intval($_POST['request_id'] ?? 0);
        $leave_request = $this->db->get_leave_request($request_id);
        
        if (!$leave_request || $leave_request->user_id !== $user->id) {
            wp_send_json_error(array('message' => __('Leave request not found.', 'lfcc-leave-management')));
        }
        
        // Check if editing is allowed
        if (LFCC_Leave_Settings::get_option('allow_leave_editing', 'yes') !== 'yes') {
            wp_send_json_error(array('message' => __('Leave request editing is not allowed.', 'lfcc-leave-management')));
        }
        
        // Check if request can be edited based on status
        if ($leave_request->status === 'rejected' && LFCC_Leave_Settings::get_option('allow_edit_rejected', 'no') !== 'yes') {
            wp_send_json_error(array('message' => __('Rejected requests cannot be edited.', 'lfcc-leave-management')));
        }
        
        $data = array(
            'leave_type' => sanitize_text_field($_POST['leave_type'] ?? ''),
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date' => sanitize_text_field($_POST['end_date'] ?? ''),
            'reason' => sanitize_textarea_field($_POST['reason'] ?? '')
        );
        
        // Validation (same as submit)
        if (empty($data['leave_type']) || empty($data['start_date']) || empty($data['end_date'])) {
            wp_send_json_error(array('message' => __('Leave type, start date, and end date are required.', 'lfcc-leave-management')));
        }
        
        // Calculate new total days
        $total_days = $this->calculate_leave_days($data['start_date'], $data['end_date']);
        
        // If request was approved, need to adjust leave balance
        if ($leave_request->status === 'approved') {
            // Restore previous days
            $leave_used_field = $leave_request->leave_type . '_leave_used';
            $current_used = $user->{$leave_used_field};
            $new_used = $current_used - $leave_request->total_days;
            
            $this->db->update_user($user->id, array(
                $leave_used_field => max(0, $new_used)
            ));
            
            // Reset status to pending if re-approval is required
            if (LFCC_Leave_Settings::get_option('require_reapproval_on_edit', 'yes') === 'yes') {
                $data['status'] = 'pending';
                $data['is_edited'] = 1;
                $data['approved_by'] = null;
                $data['approved_at'] = null;
            }
        }
        
        $data['total_days'] = $total_days;
        
        $result = $this->db->update_leave_request($request_id, $data);
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to update leave request.', 'lfcc-leave-management')));
        }
        
        wp_send_json_success(array(
            'message' => __('Leave request updated successfully!', 'lfcc-leave-management')
        ));
    }
    
    /**
     * Delete leave request
     */
    public function handle_delete_leave_request() {
        $user = $this->authenticate_user();
        if (!$user) return;
        
        $request_id = intval($_POST['request_id'] ?? 0);
        $leave_request = $this->db->get_leave_request($request_id);
        
        if (!$leave_request || $leave_request->user_id !== $user->id) {
            wp_send_json_error(array('message' => __('Leave request not found.', 'lfcc-leave-management')));
        }
        
        // Check if deletion is allowed
        if ($leave_request->status === 'approved' && LFCC_Leave_Settings::get_option('allow_delete_approved', 'no') !== 'yes') {
            wp_send_json_error(array('message' => __('Approved requests cannot be deleted.', 'lfcc-leave-management')));
        }
        
        // If request was approved, restore leave balance
        if ($leave_request->status === 'approved') {
            $leave_used_field = $leave_request->leave_type . '_leave_used';
            $current_used = $user->{$leave_used_field};
            $new_used = $current_used - $leave_request->total_days;
            
            $this->db->update_user($user->id, array(
                $leave_used_field => max(0, $new_used)
            ));
        }
        
        $result = $this->db->delete_leave_request($request_id);
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete leave request.', 'lfcc-leave-management')));
        }
        
        wp_send_json_success(array(
            'message' => __('Leave request deleted successfully!', 'lfcc-leave-management')
        ));
    }
    
    /**
     * Get leave balance
     */
    public function handle_get_leave_balance() {
        $user = $this->authenticate_user();
        if (!$user) return;
        
        wp_send_json_success(array(
            'balance' => array(
                'annual' => array(
                    'total' => $user->annual_leave,
                    'used' => $user->annual_leave_used,
                    'remaining' => $user->annual_leave - $user->annual_leave_used
                ),
                'sick' => array(
                    'total' => $user->sick_leave,
                    'used' => $user->sick_leave_used,
                    'remaining' => $user->sick_leave - $user->sick_leave_used
                ),
                'personal' => array(
                    'total' => $user->personal_leave,
                    'used' => $user->personal_leave_used,
                    'remaining' => $user->personal_leave - $user->personal_leave_used
                ),
                'emergency' => array(
                    'total' => $user->emergency_leave,
                    'used' => $user->emergency_leave_used,
                    'remaining' => $user->emergency_leave - $user->emergency_leave_used
                )
            )
        ));
    }
    
    /**
     * Calculate leave days between two dates
     */
    public function handle_calculate_leave_days() {
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');
        
        if (empty($start_date) || empty($end_date)) {
            wp_send_json_error(array('message' => __('Start date and end date are required.', 'lfcc-leave-management')));
        }
        
        $total_days = $this->calculate_leave_days($start_date, $end_date);
        
        wp_send_json_success(array(
            'total_days' => $total_days
        ));
    }
    
    /**
     * Get calendar data
     */
    public function handle_get_calendar_data() {
        $user = $this->authenticate_user();
        if (!$user) return;
        
        $year = intval($_POST['year'] ?? date('Y'));
        $month = intval($_POST['month'] ?? date('n'));
        
        // Get approved leave requests for the month
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $leave_requests = $this->db->get_calendar_leave_data($start_date, $end_date);
        
        wp_send_json_success(array(
            'leave_data' => $leave_requests,
            'year' => $year,
            'month' => $month
        ));
    }
    
    /**
     * Calculate leave days between two dates
     */
    private function calculate_leave_days($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day'); // Include end date
        
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);
        
        $total_days = 0;
        $weekend_counts = LFCC_Leave_Settings::get_option('weekend_counts_as_leave', 'yes') === 'yes';
        
        foreach ($period as $date) {
            $day_of_week = $date->format('N'); // 1 (Monday) to 7 (Sunday)
            
            if ($weekend_counts || ($day_of_week < 6)) { // Monday to Friday, or all days if weekends count
                $total_days++;
            }
        }
        
        return $total_days;
    }
    
    // Admin endpoints would continue here...
    // Due to length constraints, I'll create a separate file for admin endpoints
}

