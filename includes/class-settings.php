<?php
/**
 * Settings Management Class
 * Handles all plugin settings and configuration options
 */

if (!defined('ABSPATH')) {
    exit;
}

class LFCC_Leave_Settings {
    
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
    }
    
    public function init() {
        // Initialize settings if needed
    }
    
    /**
     * Get option value
     */
    public static function get_option($option_name, $default = '') {
        $db = LFCC_Leave_Database::get_instance();
        return $db->get_setting($option_name, $default);
    }
    
    /**
     * Update option value
     */
    public static function update_option($option_name, $option_value) {
        $db = LFCC_Leave_Database::get_instance();
        return $db->update_setting($option_name, $option_value);
    }
    
    /**
     * Delete option
     */
    public static function delete_option($option_name) {
        $db = LFCC_Leave_Database::get_instance();
        return $db->delete_setting($option_name);
    }
    
    /**
     * Set default options on plugin activation
     */
    public static function set_default_options() {
        $default_options = self::get_default_options();
        
        foreach ($default_options as $name => $value) {
            // Only set if option doesn't exist
            $existing = self::get_option($name, null);
            if ($existing === null || $existing === '') {
                self::update_option($name, $value);
            }
        }
        
        // Set default email templates
        self::set_default_email_templates();
    }
    
    /**
     * Get default options
     */
    public static function get_default_options() {
        return array(
            // Organization settings
            'organization_name' => 'Little Falls Christian Centre',
            'organization_email' => 'hr@littlefallschristiancentre.org',
            'organization_phone' => '+27 12 345 6789',
            'organization_address' => '123 Church Street, Little Falls, South Africa',
            'organization_website' => 'https://littlefallschristiancentre.org',
            
            // Subdomain settings
            'subdomain_name' => 'leave',
            'subdomain_enabled' => 'yes',
            
            // SMTP settings
            'smtp_enabled' => 'no',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'from_email' => 'noreply@littlefallschristiancentre.org',
            'from_name' => 'LFCC Leave Management',
            
            // Leave settings
            'enable_user_registration' => 'yes',
            'require_admin_approval' => 'yes',
            'weekend_counts_as_leave' => 'yes',
            'allow_leave_editing' => 'yes',
            'require_reapproval_on_edit' => 'yes',
            
            // Default leave allocations
            'default_annual_leave' => '20',
            'default_sick_leave' => '10',
            'default_personal_leave' => '5',
            'default_emergency_leave' => '3',
            
            // Notification settings
            'notify_admin_on_request' => 'yes',
            'notify_user_on_approval' => 'yes',
            'notify_user_on_rejection' => 'yes',
            'send_welcome_email' => 'yes',
            
            // Calendar settings
            'show_calendar_to_all' => 'yes',
            'show_employee_names' => 'yes',
            'calendar_start_day' => '1', // Monday
            
            // Security settings
            'session_timeout' => '480', // 8 hours in minutes
            'password_min_length' => '6',
            'require_password_change' => 'no',
            
            // Display settings
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'timezone' => 'Africa/Johannesburg',
            'items_per_page' => '20'
        );
    }
    
    /**
     * Set default email templates
     */
    public static function set_default_email_templates() {
        $templates = self::get_default_email_templates();
        
        foreach ($templates as $template_id => $template_data) {
            $existing = self::get_option('email_template_' . $template_id, null);
            if ($existing === null || $existing === '') {
                self::update_option('email_template_' . $template_id, $template_data['content']);
                self::update_option('email_template_' . $template_id . '_subject', $template_data['subject']);
            }
        }
    }
    
    /**
     * Get default email templates
     */
    public static function get_default_email_templates() {
        return array(
            'welcome' => array(
                'subject' => 'Welcome to {{organization_name}} Leave Management System',
                'content' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background-color: #f8f9fa; padding: 20px; text-align: center;">
                        <h1 style="color: #333; margin: 0;">Welcome to {{organization_name}}</h1>
                        <p style="color: #666; margin: 10px 0 0 0;">Leave Management System</p>
                    </div>
                    
                    <div style="padding: 30px 20px;">
                        <p>Dear {{first_name}} {{last_name}},</p>
                        
                        <p>Welcome to the {{organization_name}} Leave Management System! Your account has been successfully created.</p>
                        
                        <div style="background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            <h3 style="margin: 0 0 10px 0; color: #333;">Your Account Details:</h3>
                            <p style="margin: 5px 0;"><strong>Username:</strong> {{username}}</p>
                            <p style="margin: 5px 0;"><strong>Email:</strong> {{email}}</p>
                            <p style="margin: 5px 0;"><strong>Department:</strong> {{department}}</p>
                        </div>
                        
                        <div style="background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            <h3 style="margin: 0 0 10px 0; color: #155724;">Your Leave Allocations:</h3>
                            <p style="margin: 5px 0;"><strong>Annual Leave:</strong> {{annual_leave}} days</p>
                            <p style="margin: 5px 0;"><strong>Sick Leave:</strong> {{sick_leave}} days</p>
                            <p style="margin: 5px 0;"><strong>Personal Leave:</strong> {{personal_leave}} days</p>
                            <p style="margin: 5px 0;"><strong>Emergency Leave:</strong> {{emergency_leave}} days</p>
                        </div>
                        
                        <p>You can access the leave management system at: <a href="{{login_url}}" style="color: #007bff;">{{login_url}}</a></p>
                        
                        <p>If you have any questions, please contact HR at {{organization_email}} or {{organization_phone}}.</p>
                        
                        <p>Best regards,<br>{{organization_name}} HR Team</p>
                    </div>
                    
                    <div style="background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666;">
                        <p>{{organization_name}}<br>{{organization_address}}</p>
                    </div>
                </div>'
            ),
            
            'leave_request_notification' => array(
                'subject' => 'New Leave Request from {{first_name}} {{last_name}}',
                'content' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background-color: #fff3cd; padding: 20px; text-align: center;">
                        <h1 style="color: #856404; margin: 0;">New Leave Request</h1>
                        <p style="color: #856404; margin: 10px 0 0 0;">Requires Your Approval</p>
                    </div>
                    
                    <div style="padding: 30px 20px;">
                        <p>Dear HR Administrator,</p>
                        
                        <p>A new leave request has been submitted and requires your approval.</p>
                        
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                            <h3 style="margin: 0 0 15px 0; color: #333;">Request Details:</h3>
                            <p style="margin: 5px 0;"><strong>Employee:</strong> {{first_name}} {{last_name}}</p>
                            <p style="margin: 5px 0;"><strong>Department:</strong> {{department}}</p>
                            <p style="margin: 5px 0;"><strong>Leave Type:</strong> {{leave_type}}</p>
                            <p style="margin: 5px 0;"><strong>Start Date:</strong> {{start_date}}</p>
                            <p style="margin: 5px 0;"><strong>End Date:</strong> {{end_date}}</p>
                            <p style="margin: 5px 0;"><strong>Total Days:</strong> {{total_days}}</p>
                            <p style="margin: 5px 0;"><strong>Reason:</strong> {{reason}}</p>
                        </div>
                        
                        <div style="background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            <h3 style="margin: 0 0 10px 0; color: #333;">Current Leave Balance:</h3>
                            <p style="margin: 5px 0;"><strong>Annual Leave Remaining:</strong> {{annual_leave_remaining}} days</p>
                            <p style="margin: 5px 0;"><strong>Sick Leave Remaining:</strong> {{sick_leave_remaining}} days</p>
                            <p style="margin: 5px 0;"><strong>Personal Leave Remaining:</strong> {{personal_leave_remaining}} days</p>
                            <p style="margin: 5px 0;"><strong>Emergency Leave Remaining:</strong> {{emergency_leave_remaining}} days</p>
                        </div>
                        
                        <p>Please review and approve/reject this request in the admin panel.</p>
                        
                        <p>Best regards,<br>{{organization_name}} Leave Management System</p>
                    </div>
                </div>'
            ),
            
            'leave_approved' => array(
                'subject' => 'Your Leave Request has been Approved',
                'content' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background-color: #d4edda; padding: 20px; text-align: center;">
                        <h1 style="color: #155724; margin: 0;">Leave Request Approved</h1>
                        <p style="color: #155724; margin: 10px 0 0 0;">Your request has been approved</p>
                    </div>
                    
                    <div style="padding: 30px 20px;">
                        <p>Dear {{first_name}} {{last_name}},</p>
                        
                        <p>Great news! Your leave request has been approved.</p>
                        
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                            <h3 style="margin: 0 0 15px 0; color: #333;">Approved Leave Details:</h3>
                            <p style="margin: 5px 0;"><strong>Leave Type:</strong> {{leave_type}}</p>
                            <p style="margin: 5px 0;"><strong>Start Date:</strong> {{start_date}}</p>
                            <p style="margin: 5px 0;"><strong>End Date:</strong> {{end_date}}</p>
                            <p style="margin: 5px 0;"><strong>Total Days:</strong> {{total_days}}</p>
                            <p style="margin: 5px 0;"><strong>Approved By:</strong> {{approved_by}}</p>
                            <p style="margin: 5px 0;"><strong>Approved On:</strong> {{approved_date}}</p>
                        </div>
                        
                        <div style="background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            <h3 style="margin: 0 0 10px 0; color: #333;">Updated Leave Balance:</h3>
                            <p style="margin: 5px 0;"><strong>Annual Leave Remaining:</strong> {{annual_leave_remaining}} days</p>
                            <p style="margin: 5px 0;"><strong>Sick Leave Remaining:</strong> {{sick_leave_remaining}} days</p>
                            <p style="margin: 5px 0;"><strong>Personal Leave Remaining:</strong> {{personal_leave_remaining}} days</p>
                            <p style="margin: 5px 0;"><strong>Emergency Leave Remaining:</strong> {{emergency_leave_remaining}} days</p>
                        </div>
                        
                        <p>Please ensure you have made all necessary arrangements for your absence.</p>
                        
                        <p>If you have any questions, please contact HR at {{organization_email}}.</p>
                        
                        <p>Best regards,<br>{{organization_name}} HR Team</p>
                    </div>
                </div>'
            ),
            
            'leave_rejected' => array(
                'subject' => 'Your Leave Request has been Rejected',
                'content' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background-color: #f8d7da; padding: 20px; text-align: center;">
                        <h1 style="color: #721c24; margin: 0;">Leave Request Rejected</h1>
                        <p style="color: #721c24; margin: 10px 0 0 0;">Your request could not be approved</p>
                    </div>
                    
                    <div style="padding: 30px 20px;">
                        <p>Dear {{first_name}} {{last_name}},</p>
                        
                        <p>We regret to inform you that your leave request has been rejected.</p>
                        
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                            <h3 style="margin: 0 0 15px 0; color: #333;">Request Details:</h3>
                            <p style="margin: 5px 0;"><strong>Leave Type:</strong> {{leave_type}}</p>
                            <p style="margin: 5px 0;"><strong>Start Date:</strong> {{start_date}}</p>
                            <p style="margin: 5px 0;"><strong>End Date:</strong> {{end_date}}</p>
                            <p style="margin: 5px 0;"><strong>Total Days:</strong> {{total_days}}</p>
                            <p style="margin: 5px 0;"><strong>Rejected By:</strong> {{rejected_by}}</p>
                            <p style="margin: 5px 0;"><strong>Rejected On:</strong> {{rejected_date}}</p>
                        </div>
                        
                        <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            <h3 style="margin: 0 0 10px 0; color: #856404;">Reason for Rejection:</h3>
                            <p style="margin: 0; color: #856404;">{{rejection_reason}}</p>
                        </div>
                        
                        <p>If you would like to discuss this decision or submit a revised request, please contact HR at {{organization_email}} or {{organization_phone}}.</p>
                        
                        <p>Best regards,<br>{{organization_name}} HR Team</p>
                    </div>
                </div>'
            ),
            
            'password_reset' => array(
                'subject' => 'Password Reset Request - {{organization_name}}',
                'content' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background-color: #cce5ff; padding: 20px; text-align: center;">
                        <h1 style="color: #004085; margin: 0;">Password Reset</h1>
                        <p style="color: #004085; margin: 10px 0 0 0;">{{organization_name}} Leave Management</p>
                    </div>
                    
                    <div style="padding: 30px 20px;">
                        <p>Dear {{first_name}} {{last_name}},</p>
                        
                        <p>You have requested to reset your password for the {{organization_name}} Leave Management System.</p>
                        
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                            <h3 style="margin: 0 0 15px 0; color: #333;">Your New Temporary Password:</h3>
                            <p style="margin: 0; font-size: 18px; font-weight: bold; color: #007bff;">{{temporary_password}}</p>
                        </div>
                        
                        <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            <p style="margin: 0; color: #856404;"><strong>Important:</strong> Please change this temporary password immediately after logging in for security purposes.</p>
                        </div>
                        
                        <p>You can log in at: <a href="{{login_url}}" style="color: #007bff;">{{login_url}}</a></p>
                        
                        <p>If you did not request this password reset, please contact HR immediately at {{organization_email}}.</p>
                        
                        <p>Best regards,<br>{{organization_name}} HR Team</p>
                    </div>
                </div>'
            )
        );
    }
    
    /**
     * Get all email template variables
     */
    public static function get_email_template_variables() {
        return array(
            'user_variables' => array(
                '{{first_name}}' => 'User\'s first name',
                '{{last_name}}' => 'User\'s last name',
                '{{full_name}}' => 'User\'s full name',
                '{{username}}' => 'User\'s username',
                '{{email}}' => 'User\'s email address',
                '{{department}}' => 'User\'s department',
                '{{phone}}' => 'User\'s phone number',
                '{{hire_date}}' => 'User\'s hire date'
            ),
            'leave_variables' => array(
                '{{leave_type}}' => 'Type of leave (Annual, Sick, Personal, Emergency)',
                '{{start_date}}' => 'Leave start date',
                '{{end_date}}' => 'Leave end date',
                '{{total_days}}' => 'Total days requested',
                '{{reason}}' => 'Reason for leave',
                '{{status}}' => 'Request status (Pending, Approved, Rejected)',
                '{{approved_by}}' => 'Name of person who approved',
                '{{approved_date}}' => 'Date of approval',
                '{{rejected_by}}' => 'Name of person who rejected',
                '{{rejected_date}}' => 'Date of rejection',
                '{{rejection_reason}}' => 'Reason for rejection'
            ),
            'balance_variables' => array(
                '{{annual_leave}}' => 'Annual leave allocation',
                '{{sick_leave}}' => 'Sick leave allocation',
                '{{personal_leave}}' => 'Personal leave allocation',
                '{{emergency_leave}}' => 'Emergency leave allocation',
                '{{annual_leave_used}}' => 'Annual leave used',
                '{{sick_leave_used}}' => 'Sick leave used',
                '{{personal_leave_used}}' => 'Personal leave used',
                '{{emergency_leave_used}}' => 'Emergency leave used',
                '{{annual_leave_remaining}}' => 'Annual leave remaining',
                '{{sick_leave_remaining}}' => 'Sick leave remaining',
                '{{personal_leave_remaining}}' => 'Personal leave remaining',
                '{{emergency_leave_remaining}}' => 'Emergency leave remaining'
            ),
            'organization_variables' => array(
                '{{organization_name}}' => 'Organization name',
                '{{organization_email}}' => 'Organization email',
                '{{organization_phone}}' => 'Organization phone',
                '{{organization_address}}' => 'Organization address',
                '{{organization_website}}' => 'Organization website'
            ),
            'system_variables' => array(
                '{{login_url}}' => 'Frontend login URL',
                '{{current_date}}' => 'Current date',
                '{{current_time}}' => 'Current time',
                '{{temporary_password}}' => 'Temporary password (for password reset)'
            )
        );
    }
    
    /**
     * Remove all plugin options
     */
    public static function remove_all_options() {
        global $wpdb;
        $db = LFCC_Leave_Database::get_instance();
        
        // This will be handled by dropping the settings table
        // in the database class uninstall method
    }
    
    /**
     * Get organization settings
     */
    public static function get_organization_settings() {
        return array(
            'name' => self::get_option('organization_name'),
            'email' => self::get_option('organization_email'),
            'phone' => self::get_option('organization_phone'),
            'address' => self::get_option('organization_address'),
            'website' => self::get_option('organization_website')
        );
    }
    
    /**
     * Get SMTP settings
     */
    public static function get_smtp_settings() {
        return array(
            'enabled' => self::get_option('smtp_enabled') === 'yes',
            'host' => self::get_option('smtp_host'),
            'port' => self::get_option('smtp_port'),
            'username' => self::get_option('smtp_username'),
            'password' => self::get_option('smtp_password'),
            'encryption' => self::get_option('smtp_encryption'),
            'from_email' => self::get_option('from_email'),
            'from_name' => self::get_option('from_name')
        );
    }
    
    /**
     * Get leave settings
     */
    public static function get_leave_settings() {
        return array(
            'enable_user_registration' => self::get_option('enable_user_registration') === 'yes',
            'require_admin_approval' => self::get_option('require_admin_approval') === 'yes',
            'weekend_counts_as_leave' => self::get_option('weekend_counts_as_leave') === 'yes',
            'allow_leave_editing' => self::get_option('allow_leave_editing') === 'yes',
            'require_reapproval_on_edit' => self::get_option('require_reapproval_on_edit') === 'yes',
            'default_annual_leave' => intval(self::get_option('default_annual_leave')),
            'default_sick_leave' => intval(self::get_option('default_sick_leave')),
            'default_personal_leave' => intval(self::get_option('default_personal_leave')),
            'default_emergency_leave' => intval(self::get_option('default_emergency_leave'))
        );
    }
}

