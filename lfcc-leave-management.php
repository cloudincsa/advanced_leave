<?php
/**
 * Plugin Name: LFCC Leave Management System
 * Plugin URI: https://littlefallschristiancentre.org
 * Description: Comprehensive staff leave management system with admin backend, email templates, and subdomain frontend access.
 * Version: 1.0.0
 * Author: Little Falls Christian Centre
 * Author URI: https://littlefallschristiancentre.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lfcc-leave-management
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LFCC_LEAVE_VERSION', '1.0.0');
define('LFCC_LEAVE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LFCC_LEAVE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('LFCC_LEAVE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main LFCC Leave Management Plugin Class
 */
class LFCC_Leave_Management {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('LFCC_Leave_Management', 'uninstall'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        add_action('wp_ajax_lfcc_leave_ajax', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_nopriv_lfcc_leave_ajax', array($this, 'handle_ajax_request'));
        add_action('template_redirect', array($this, 'handle_subdomain_access'));
        add_action('admin_init', array($this, 'check_subdomain_access'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core includes
        require_once LFCC_LEAVE_PLUGIN_PATH . 'includes/class-logger.php';
        require_once LFCC_LEAVE_PLUGIN_PATH . 'includes/class-cleanup.php';
        require_once LFCC_LEAVE_PLUGIN_PATH . 'includes/class-database.php';
        require_once LFCC_LEAVE_PLUGIN_PATH . 'includes/class-settings.php';
        require_once LFCC_LEAVE_PLUGIN_PATH . 'includes/class-email-handler.php';
        require_once LFCC_LEAVE_PLUGIN_PATH . 'includes/class-user-manager.php';
        require_once LFCC_LEAVE_PLUGIN_PATH . 'includes/class-api.php';
        
        // Initialize core classes
        LFCC_Leave_Logger::get_instance();
        LFCC_Leave_Database::get_instance();
        LFCC_Leave_Settings::get_instance();
        LFCC_Leave_Email_Handler::get_instance();
        LFCC_Leave_User_Manager::get_instance();
        LFCC_Leave_API::get_instance();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('lfcc-leave-management', false, dirname(LFCC_LEAVE_PLUGIN_BASENAME) . '/languages');
        
        // Initialize database
        LFCC_Leave_Database::get_instance()->init();
        
        // Initialize settings
        LFCC_Leave_Settings::get_instance()->init();
        
        // Initialize frontend if subdomain access
        if ($this->is_subdomain_access()) {
            $this->load_frontend_template();
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('LFCC Leave Management', 'lfcc-leave-management'),
            __('Leave Management', 'lfcc-leave-management'),
            'manage_options',
            'lfcc-leave-management',
            array($this, 'admin_dashboard_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // Sub-menu pages
        add_submenu_page(
            'lfcc-leave-management',
            __('Dashboard', 'lfcc-leave-management'),
            __('Dashboard', 'lfcc-leave-management'),
            'manage_options',
            'lfcc-leave-management',
            array($this, 'admin_dashboard_page')
        );
        
        add_submenu_page(
            'lfcc-leave-management',
            __('Settings', 'lfcc-leave-management'),
            __('Settings', 'lfcc-leave-management'),
            'manage_options',
            'lfcc-leave-settings',
            array($this, 'admin_settings_page')
        );
        
        add_submenu_page(
            'lfcc-leave-management',
            __('Email Templates', 'lfcc-leave-management'),
            __('Email Templates', 'lfcc-leave-management'),
            'manage_options',
            'lfcc-leave-email-templates',
            array($this, 'admin_email_templates_page')
        );
        
        add_submenu_page(
            'lfcc-leave-management',
            __('User Management', 'lfcc-leave-management'),
            __('User Management', 'lfcc-leave-management'),
            'manage_options',
            'lfcc-leave-users',
            array($this, 'admin_user_management_page')
        );
        
        add_submenu_page(
            'lfcc-leave-management',
            __('Leave Requests', 'lfcc-leave-management'),
            __('Leave Requests', 'lfcc-leave-management'),
            'manage_options',
            'lfcc-leave-requests',
            array($this, 'admin_leave_requests_page')
        );
        
        add_submenu_page(
            'lfcc-leave-management',
            __('System Logs', 'lfcc-leave-management'),
            __('System Logs', 'lfcc-leave-management'),
            'manage_options',
            'lfcc-leave-logs',
            array($this, 'admin_logs_page')
        );
        
        add_submenu_page(
            'lfcc-leave-management',
            __('Diagnostics', 'lfcc-leave-management'),
            __('Diagnostics', 'lfcc-leave-management'),
            'manage_options',
            'lfcc-leave-diagnostics',
            array($this, 'admin_diagnostics_page')
        );
    }
    
    /**
     * Admin dashboard page
     */
    public function admin_dashboard_page() {
        require_once LFCC_LEAVE_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * Admin settings page
     */
    public function admin_settings_page() {
        require_once LFCC_LEAVE_PLUGIN_PATH . 'admin/views/settings.php';
    }
    
    /**
     * Admin email templates page
     */
    public function admin_email_templates_page() {
        require_once LFCC_LEAVE_PLUGIN_PATH . 'admin/views/email-templates.php';
    }
    
    /**
     * Admin user management page
     */
    public function admin_user_management_page() {
        require_once LFCC_LEAVE_PLUGIN_PATH . 'admin/views/user-management.php';
    }
    
    /**
     * Admin leave requests page
     */
    public function admin_leave_requests_page() {
        require_once LFCC_LEAVE_PLUGIN_PATH . 'admin/views/leave-requests.php';
    }
    
    /**
     * Admin logs page
     */
    public function admin_logs_page() {
        require_once LFCC_LEAVE_PLUGIN_PATH . 'admin/views/logs.php';
    }
    
    /**
     * Admin diagnostics page
     */
    public function admin_diagnostics_page() {
        require_once LFCC_LEAVE_PLUGIN_PATH . 'admin/views/diagnostics.php';
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'lfcc-leave') !== false) {
            wp_enqueue_style('lfcc-leave-admin-css', LFCC_LEAVE_PLUGIN_URL . 'assets/css/admin.css', array(), LFCC_LEAVE_VERSION);
            wp_enqueue_script('lfcc-leave-admin-js', LFCC_LEAVE_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), LFCC_LEAVE_VERSION, true);
            
            // Enqueue WordPress media uploader
            wp_enqueue_media();
            
            // Enqueue TinyMCE for email templates
            wp_enqueue_editor();
            
            // Localize script for AJAX
            wp_localize_script('lfcc-leave-admin-js', 'lfcc_leave_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lfcc_leave_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'lfcc-leave-management'),
                    'saving' => __('Saving...', 'lfcc-leave-management'),
                    'saved' => __('Saved!', 'lfcc-leave-management'),
                    'error' => __('Error occurred. Please try again.', 'lfcc-leave-management')
                )
            ));
        }
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_enqueue_scripts() {
        if ($this->is_subdomain_access()) {
            wp_enqueue_style('lfcc-leave-frontend-css', LFCC_LEAVE_PLUGIN_URL . 'assets/css/frontend.css', array(), LFCC_LEAVE_VERSION);
            wp_enqueue_script('lfcc-leave-frontend-js', LFCC_LEAVE_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), LFCC_LEAVE_VERSION, true);
            
            // Localize script for AJAX
            wp_localize_script('lfcc-leave-frontend-js', 'lfcc_leave_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lfcc_leave_frontend_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'lfcc-leave-management'),
                    'error' => __('Error occurred. Please try again.', 'lfcc-leave-management'),
                    'success' => __('Success!', 'lfcc-leave-management')
                )
            ));
        }
    }
    
    /**
     * Handle AJAX requests
     */
    public function handle_ajax_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lfcc_leave_nonce') && !wp_verify_nonce($_POST['nonce'], 'lfcc_leave_frontend_nonce')) {
            wp_die(__('Security check failed', 'lfcc-leave-management'));
        }
        
        $action = sanitize_text_field($_POST['lfcc_action']);
        
        switch ($action) {
            case 'save_settings':
                $this->handle_save_settings();
                break;
            case 'save_email_template':
                $this->handle_save_email_template();
                break;
            case 'manage_user':
                $this->handle_manage_user();
                break;
            case 'manage_leave_request':
                $this->handle_manage_leave_request();
                break;
            case 'frontend_login':
                $this->handle_frontend_login();
                break;
            case 'frontend_register':
                $this->handle_frontend_register();
                break;
            case 'frontend_submit_leave':
                $this->handle_frontend_submit_leave();
                break;
            default:
                wp_die(__('Invalid action', 'lfcc-leave-management'));
        }
    }
    
    /**
     * Check if current request is subdomain access
     */
    private function is_subdomain_access() {
        $subdomain_enabled = LFCC_Leave_Settings::get_option('subdomain_enabled', 'no');
        if ($subdomain_enabled !== 'yes') {
            return false;
        }
        
        $subdomain_name = LFCC_Leave_Settings::get_option('subdomain_name', '');
        if (empty($subdomain_name)) {
            return false;
        }
        
        $current_host = $_SERVER['HTTP_HOST'] ?? '';
        $main_host = parse_url(get_site_url(), PHP_URL_HOST);
        
        // Remove www. prefix if present
        $main_host = preg_replace('/^www\./', '', $main_host);
        
        $expected_subdomain_host = $subdomain_name . '.' . $main_host;
        
        return $current_host === $expected_subdomain_host;
    }
    
    /**
     * Handle subdomain access
     */
    public function handle_subdomain_access() {
        if ($this->is_subdomain_access()) {
            // Prevent WordPress from loading the normal theme
            add_filter('template_include', function() {
                return LFCC_LEAVE_PLUGIN_PATH . 'frontend/template.php';
            }, 99);
            
            // Set a flag that we're in subdomain mode
            define('LFCC_SUBDOMAIN_MODE', true);
        }
    }
    
    /**
     * Check subdomain access and enforce requirement
     */
    public function check_subdomain_access() {
        // Skip check if not in admin area
        if (!is_admin()) {
            // Handle frontend subdomain redirects
            $subdomain_enabled = LFCC_Leave_Settings::get_option('subdomain_enabled', 'no');
            $subdomain_name = LFCC_Leave_Settings::get_option('subdomain_name', '');
            
            if ($subdomain_enabled === 'yes' && !empty($subdomain_name) && !$this->is_subdomain_access()) {
                $current_path = $_SERVER['REQUEST_URI'] ?? '/';
                
                // Only redirect if accessing leave-related pages
                if (strpos($current_path, 'leave') !== false || isset($_GET['lfcc_leave'])) {
                    $main_host = parse_url(get_site_url(), PHP_URL_HOST);
                    $main_host = preg_replace('/^www\./', '', $main_host);
                    $subdomain_url = 'https://' . $subdomain_name . '.' . $main_host;
                    
                    wp_redirect($subdomain_url . $current_path);
                    exit;
                }
            }
            return;
        }
        
        // Only show admin notices on the plugin's settings page
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'toplevel_page_lfcc-leave-settings') {
            return;
        }
        
        // Check if subdomain is configured
        $subdomain_enabled = LFCC_Leave_Settings::get_option('subdomain_enabled', 'no');
        $subdomain_name = LFCC_Leave_Settings::get_option('subdomain_name', '');
        
        // Only show notice if BOTH conditions are true: not enabled AND name is empty
        // This ensures we don't show the notice if either one is configured
        if ($subdomain_enabled !== 'yes' && empty($subdomain_name)) {
            // Check if notice was dismissed
            $notice_dismissed = get_user_meta(get_current_user_id(), 'lfcc_subdomain_notice_dismissed', true);
            if ($notice_dismissed !== 'yes') {
                add_action('admin_notices', array($this, 'subdomain_required_notice'));
            }
        }
    }
    
    /**
     * Load frontend template
     */
    public function load_frontend_template() {
        // This will be called when subdomain is accessed
        // The actual template loading is handled in handle_subdomain_access
    }
    
    /**
     * Show admin notice for subdomain requirement
     */
    public function subdomain_required_notice() {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php _e('LFCC Leave Management:', 'lfcc-leave-management'); ?></strong>
                <?php _e('Subdomain configuration is required for the leave management system to function properly.', 'lfcc-leave-management'); ?>
                <a href="<?php echo admin_url('admin.php?page=lfcc-leave-settings'); ?>" class="button button-primary">
                    <?php _e('Configure Subdomain', 'lfcc-leave-management'); ?>
                </a>
            </p>
            <p>
                <em><?php _e('Please set up your subdomain (e.g., leave.yourcompany.com) in the plugin settings to enable staff access to the leave management system.', 'lfcc-leave-management'); ?></em>
            </p>
        </div>
        <?php
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $logger = LFCC_Leave_Logger::get_instance();
        $logger->info('Plugin activation started');
        
        try {
            // Create database tables
            LFCC_Leave_Database::get_instance()->create_tables();
            $logger->info('Database tables created successfully');
            
            // Set default options using the Settings class
            LFCC_Leave_Settings::set_default_options();
            $logger->info('Default options set successfully');
            
            // Create default email templates
            $this->create_default_email_templates();
            $logger->info('Default email templates created');
            
            // Create default admin user if none exists
            $this->create_default_admin_user();
            $logger->info('Default admin user created');
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Set activation flag for subdomain setup reminder
            update_option('lfcc_leave_activation_time', time());
            
            $logger->info('Plugin activation completed successfully');
            
        } catch (Exception $e) {
            $logger->critical('Plugin activation failed', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            // Deactivate plugin on critical error
            deactivate_plugins(LFCC_LEAVE_PLUGIN_BASENAME);
            wp_die(__('LFCC Leave Management plugin activation failed. Please check the error logs.', 'lfcc-leave-management'));
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        $logger = LFCC_Leave_Logger::get_instance();
        $logger->info('Plugin deactivation started');
        
        // Perform deactivation cleanup (keeps data)
        $cleanup = LFCC_Leave_Cleanup::get_instance();
        $cleanup->deactivation_cleanup();
        
        $logger->info('Plugin deactivation completed');
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Check if we have permission to uninstall
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Check if uninstall is called from WordPress
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }
        
        try {
            // Load required dependencies
            require_once LFCC_LEAVE_PLUGIN_PATH . 'includes/class-logger.php';
            require_once LFCC_LEAVE_PLUGIN_PATH . 'includes/class-database.php';
            require_once LFCC_LEAVE_PLUGIN_PATH . 'includes/class-cleanup.php';
            
            // Perform complete cleanup
            LFCC_Leave_Cleanup::uninstall();
            
        } catch (Exception $e) {
            // Log error but don't fail the uninstall
            error_log('LFCC Leave Management uninstall error: ' . $e->getMessage());
            
            // Perform basic cleanup if full cleanup fails
            self::basic_cleanup();
        }
    }
    
    /**
     * Basic cleanup fallback
     */
    private static function basic_cleanup() {
        global $wpdb;
        
        // Remove database tables
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
        
        // Clear scheduled events
        wp_clear_scheduled_hook('lfcc_leave_daily_cleanup');
        wp_clear_scheduled_hook('lfcc_leave_weekly_reports');
        wp_clear_scheduled_hook('lfcc_leave_email_queue_process');
    }
    
    /**
     * Create default email templates
     */
    private function create_default_email_templates() {
        $templates = array(
            'welcome' => array(
                'subject' => 'Welcome to {{organization_name}} Leave Management',
                'content' => '<h2>Welcome {{first_name}}!</h2><p>Your account has been created for the {{organization_name}} leave management system.</p><p><strong>Login Details:</strong><br>Username: {{username}}<br>Temporary Password: {{password}}</p><p>Please log in at: <a href="{{login_url}}">{{login_url}}</a></p><p>We recommend changing your password after your first login.</p><p>Best regards,<br>{{organization_name}} HR Team</p>'
            ),
            'leave_request_notification' => array(
                'subject' => 'New Leave Request from {{full_name}}',
                'content' => '<h2>New Leave Request</h2><p><strong>Employee:</strong> {{full_name}} ({{department}})</p><p><strong>Leave Type:</strong> {{leave_type}}</p><p><strong>Dates:</strong> {{start_date}} to {{end_date}} ({{total_days}} days)</p><p><strong>Reason:</strong> {{reason}}</p><p>Please review and approve/reject this request in the admin panel.</p>'
            ),
            'leave_approved' => array(
                'subject' => 'Leave Request Approved - {{leave_type}}',
                'content' => '<h2>Leave Request Approved</h2><p>Dear {{first_name}},</p><p>Your {{leave_type}} request has been approved.</p><p><strong>Details:</strong><br>Dates: {{start_date}} to {{end_date}}<br>Total Days: {{total_days}}</p><p>Remaining Leave Balance: {{leave_balance}} days</p><p>Have a great time off!</p><p>Best regards,<br>{{organization_name}} HR Team</p>'
            ),
            'leave_rejected' => array(
                'subject' => 'Leave Request Rejected - {{leave_type}}',
                'content' => '<h2>Leave Request Rejected</h2><p>Dear {{first_name}},</p><p>Unfortunately, your {{leave_type}} request has been rejected.</p><p><strong>Details:</strong><br>Dates: {{start_date}} to {{end_date}}<br>Total Days: {{total_days}}</p><p><strong>Reason for Rejection:</strong> {{rejection_reason}}</p><p>Please contact HR if you have any questions.</p><p>Best regards,<br>{{organization_name}} HR Team</p>'
            ),
            'password_reset' => array(
                'subject' => 'Password Reset - {{organization_name}} Leave Management',
                'content' => '<h2>Password Reset</h2><p>Dear {{first_name}},</p><p>Your password has been reset for the leave management system.</p><p><strong>New Temporary Password:</strong> {{password}}</p><p>Please log in at: <a href="{{login_url}}">{{login_url}}</a></p><p>We recommend changing this password after logging in.</p><p>Best regards,<br>{{organization_name}} HR Team</p>'
            )
        );
        
        foreach ($templates as $template_id => $template_data) {
            LFCC_Leave_Settings::update_option('email_template_' . $template_id . '_subject', $template_data['subject']);
            LFCC_Leave_Settings::update_option('email_template_' . $template_id, $template_data['content']);
        }
    }
    
    /**
     * Create default admin user
     */
    private function create_default_admin_user() {
        $db = LFCC_Leave_Database::get_instance();
        
        // Check if any admin users exist
        $admin_users = $db->get_users_by_role('admin');
        
        if (empty($admin_users)) {
            // Create default admin user
            $admin_data = array(
                'username' => 'lfcc_admin',
                'email' => get_option('admin_email'),
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'first_name' => 'LFCC',
                'last_name' => 'Administrator',
                'role' => 'admin',
                'status' => 'active',
                'department' => 'Administration',
                'annual_leave' => 25,
                'sick_leave' => 15,
                'personal_leave' => 10,
                'emergency_leave' => 5
            );
            
            $db->create_user($admin_data);
        }
    }
    
    /**
     * Handle save settings AJAX
     */
    private function handle_save_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'lfcc-leave-management')));
        }
        
        $settings = $_POST['settings'] ?? array();
        
        foreach ($settings as $key => $value) {
            LFCC_Leave_Settings::update_option(sanitize_key($key), sanitize_text_field($value));
        }
        
        wp_send_json_success(array('message' => __('Settings saved successfully!', 'lfcc-leave-management')));
    }
    
    /**
     * Handle save email template AJAX
     */
    private function handle_save_email_template() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'lfcc-leave-management')));
        }
        
        $template_id = sanitize_text_field($_POST['template_id']);
        $subject = sanitize_text_field($_POST['subject']);
        $content = wp_kses_post($_POST['content']);
        
        LFCC_Leave_Settings::update_option('email_template_' . $template_id . '_subject', $subject);
        LFCC_Leave_Settings::update_option('email_template_' . $template_id, $content);
        
        wp_send_json_success(array('message' => __('Email template saved successfully!', 'lfcc-leave-management')));
    }
    
    /**
     * Handle manage user AJAX
     */
    private function handle_manage_user() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'lfcc-leave-management')));
        }
        
        $user_manager = LFCC_Leave_User_Manager::get_instance();
        $action = sanitize_text_field($_POST['user_action']);
        
        switch ($action) {
            case 'create':
                $result = $user_manager->create_user($_POST['user_data']);
                break;
            case 'update':
                $result = $user_manager->update_user($_POST['user_id'], $_POST['user_data']);
                break;
            case 'delete':
                $result = $user_manager->delete_user($_POST['user_id']);
                break;
            default:
                wp_send_json_error(array('message' => __('Invalid user action.', 'lfcc-leave-management')));
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array('message' => __('User action completed successfully!', 'lfcc-leave-management')));
        }
    }
    
    /**
     * Handle manage leave request AJAX
     */
    private function handle_manage_leave_request() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'lfcc-leave-management')));
        }
        
        // Leave request management logic here
        wp_send_json_success(array('message' => __('Leave request action completed successfully!', 'lfcc-leave-management')));
    }
    
    /**
     * Handle frontend login AJAX
     */
    private function handle_frontend_login() {
        // Frontend login logic handled by API class
        LFCC_Leave_API::get_instance()->handle_login();
    }
    
    /**
     * Handle frontend register AJAX
     */
    private function handle_frontend_register() {
        // Frontend registration logic handled by API class
        LFCC_Leave_API::get_instance()->handle_register();
    }
    
    /**
     * Handle frontend submit leave AJAX
     */
    private function handle_frontend_submit_leave() {
        // Frontend leave submission logic handled by API class
        LFCC_Leave_API::get_instance()->handle_submit_leave_request();
    }
}

// Initialize the plugin
function lfcc_leave_management_init() {
    return LFCC_Leave_Management::get_instance();
}

// Hook into WordPress
add_action('plugins_loaded', 'lfcc_leave_management_init');

// Add settings link to plugin page
function lfcc_leave_management_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=lfcc-leave-settings') . '">' . __('Settings', 'lfcc-leave-management') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . LFCC_LEAVE_PLUGIN_BASENAME, 'lfcc_leave_management_settings_link');

// Add subdomain requirement check
function lfcc_leave_check_subdomain_requirement() {
    $subdomain_url = LFCC_Leave_Settings::get_option('subdomain_url', '');
    
    if (empty($subdomain_url) && is_admin() && current_user_can('manage_options')) {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'lfcc-leave') !== false) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php _e('Subdomain Required:', 'lfcc-leave-management'); ?></strong>
                    <?php _e('The LFCC Leave Management system requires a subdomain to be configured. Please set up your subdomain (e.g., leave.yourcompany.com) to enable the leave management functionality.', 'lfcc-leave-management'); ?>
                </p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=lfcc-leave-settings'); ?>" class="button button-primary">
                        <?php _e('Configure Subdomain Now', 'lfcc-leave-management'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
}
add_action('admin_notices', 'lfcc_leave_check_subdomain_requirement');

