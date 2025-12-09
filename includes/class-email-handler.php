<?php
/**
 * Email Handler Class
 * Manages all email functionality including templates and SMTP sending
 */

if (!defined('ABSPATH')) {
    exit;
}

class LFCC_Leave_Email_Handler {
    
    private static $instance = null;
    private $db;
    private $settings;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = LFCC_Leave_Database::get_instance();
        $this->settings = LFCC_Leave_Settings::get_instance();
    }
    
    /**
     * Send email using template
     */
    public function send_template_email($template_id, $recipient_email, $recipient_name, $variables = array()) {
        // Get template content and subject
        $template_content = LFCC_Leave_Settings::get_option('email_template_' . $template_id, '');
        $template_subject = LFCC_Leave_Settings::get_option('email_template_' . $template_id . '_subject', '');
        
        if (empty($template_content) || empty($template_subject)) {
            error_log("LFCC Leave Management: Email template '{$template_id}' not found");
            return false;
        }
        
        // Process template variables
        $processed_content = $this->process_template_variables($template_content, $variables);
        $processed_subject = $this->process_template_variables($template_subject, $variables);
        
        // Send email
        $result = $this->send_email($recipient_email, $recipient_name, $processed_subject, $processed_content);
        
        // Log email
        $this->log_email($recipient_email, $recipient_name, $processed_subject, $template_id, $processed_content, $result);
        
        return $result;
    }
    
    /**
     * Send email
     */
    public function send_email($to_email, $to_name, $subject, $content, $is_html = true) {
        $smtp_settings = LFCC_Leave_Settings::get_smtp_settings();
        
        if ($smtp_settings['enabled']) {
            return $this->send_smtp_email($to_email, $to_name, $subject, $content, $is_html);
        } else {
            return $this->send_wp_email($to_email, $to_name, $subject, $content, $is_html);
        }
    }
    
    /**
     * Send email via SMTP
     */
    private function send_smtp_email($to_email, $to_name, $subject, $content, $is_html = true) {
        $smtp_settings = LFCC_Leave_Settings::get_smtp_settings();
        
        // Configure PHPMailer for SMTP
        add_action('phpmailer_init', function($phpmailer) use ($smtp_settings) {
            $phpmailer->isSMTP();
            $phpmailer->Host = $smtp_settings['host'];
            $phpmailer->Port = $smtp_settings['port'];
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $smtp_settings['username'];
            $phpmailer->Password = $smtp_settings['password'];
            
            if ($smtp_settings['encryption'] === 'ssl') {
                $phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtp_settings['encryption'] === 'tls') {
                $phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $phpmailer->setFrom($smtp_settings['from_email'], $smtp_settings['from_name']);
        });
        
        // Set content type
        if ($is_html) {
            add_filter('wp_mail_content_type', function() {
                return 'text/html';
            });
        }
        
        // Send email
        $result = wp_mail($to_email, $subject, $content);
        
        // Reset content type
        if ($is_html) {
            remove_filter('wp_mail_content_type', function() {
                return 'text/html';
            });
        }
        
        return $result;
    }
    
    /**
     * Send email via WordPress default
     */
    private function send_wp_email($to_email, $to_name, $subject, $content, $is_html = true) {
        $smtp_settings = LFCC_Leave_Settings::get_smtp_settings();
        
        // Set from email and name
        add_filter('wp_mail_from', function() use ($smtp_settings) {
            return $smtp_settings['from_email'];
        });
        
        add_filter('wp_mail_from_name', function() use ($smtp_settings) {
            return $smtp_settings['from_name'];
        });
        
        // Set content type
        if ($is_html) {
            add_filter('wp_mail_content_type', function() {
                return 'text/html';
            });
        }
        
        // Send email
        $result = wp_mail($to_email, $subject, $content);
        
        // Reset filters
        remove_filter('wp_mail_from', function() use ($smtp_settings) {
            return $smtp_settings['from_email'];
        });
        
        remove_filter('wp_mail_from_name', function() use ($smtp_settings) {
            return $smtp_settings['from_name'];
        });
        
        if ($is_html) {
            remove_filter('wp_mail_content_type', function() {
                return 'text/html';
            });
        }
        
        return $result;
    }
    
    /**
     * Process template variables
     */
    private function process_template_variables($content, $variables = array()) {
        // Add system variables
        $variables = array_merge($variables, $this->get_system_variables());
        
        // Replace variables in content
        foreach ($variables as $key => $value) {
            $content = str_replace($key, $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Get system variables
     */
    private function get_system_variables() {
        $org_settings = LFCC_Leave_Settings::get_organization_settings();
        $subdomain = LFCC_Leave_Settings::get_option('subdomain_name', 'leave');
        $site_url = get_site_url();
        
        // Construct login URL
        $login_url = $site_url;
        if (!empty($subdomain)) {
            $parsed_url = parse_url($site_url);
            $login_url = $parsed_url['scheme'] . '://' . $subdomain . '.' . $parsed_url['host'];
            if (isset($parsed_url['port'])) {
                $login_url .= ':' . $parsed_url['port'];
            }
        }
        
        return array(
            '{{organization_name}}' => $org_settings['name'],
            '{{organization_email}}' => $org_settings['email'],
            '{{organization_phone}}' => $org_settings['phone'],
            '{{organization_address}}' => $org_settings['address'],
            '{{organization_website}}' => $org_settings['website'],
            '{{login_url}}' => $login_url,
            '{{current_date}}' => date('Y-m-d'),
            '{{current_time}}' => date('H:i:s'),
            '{{current_datetime}}' => date('Y-m-d H:i:s')
        );
    }
    
    /**
     * Get user variables
     */
    public function get_user_variables($user) {
        if (!$user) {
            return array();
        }
        
        return array(
            '{{first_name}}' => $user->first_name,
            '{{last_name}}' => $user->last_name,
            '{{full_name}}' => $user->first_name . ' ' . $user->last_name,
            '{{username}}' => $user->username,
            '{{email}}' => $user->email,
            '{{department}}' => $user->department,
            '{{phone}}' => $user->phone ?? '',
            '{{hire_date}}' => $user->hire_date ?? '',
            '{{annual_leave}}' => $user->annual_leave,
            '{{sick_leave}}' => $user->sick_leave,
            '{{personal_leave}}' => $user->personal_leave,
            '{{emergency_leave}}' => $user->emergency_leave,
            '{{annual_leave_used}}' => $user->annual_leave_used,
            '{{sick_leave_used}}' => $user->sick_leave_used,
            '{{personal_leave_used}}' => $user->personal_leave_used,
            '{{emergency_leave_used}}' => $user->emergency_leave_used,
            '{{annual_leave_remaining}}' => $user->annual_leave - $user->annual_leave_used,
            '{{sick_leave_remaining}}' => $user->sick_leave - $user->sick_leave_used,
            '{{personal_leave_remaining}}' => $user->personal_leave - $user->personal_leave_used,
            '{{emergency_leave_remaining}}' => $user->emergency_leave - $user->emergency_leave_used
        );
    }
    
    /**
     * Get leave request variables
     */
    public function get_leave_request_variables($leave_request) {
        if (!$leave_request) {
            return array();
        }
        
        $leave_types = array(
            'annual' => 'Annual Leave',
            'sick' => 'Sick Leave',
            'personal' => 'Personal Leave',
            'emergency' => 'Emergency Leave'
        );
        
        return array(
            '{{leave_type}}' => $leave_types[$leave_request->leave_type] ?? ucfirst($leave_request->leave_type),
            '{{start_date}}' => date('Y-m-d', strtotime($leave_request->start_date)),
            '{{end_date}}' => date('Y-m-d', strtotime($leave_request->end_date)),
            '{{total_days}}' => $leave_request->total_days,
            '{{reason}}' => $leave_request->reason ?? '',
            '{{status}}' => ucfirst($leave_request->status),
            '{{approved_by}}' => $leave_request->approved_by_name ?? '',
            '{{approved_date}}' => $leave_request->approved_at ? date('Y-m-d', strtotime($leave_request->approved_at)) : '',
            '{{rejected_by}}' => $leave_request->rejected_by_name ?? '',
            '{{rejected_date}}' => $leave_request->rejected_at ? date('Y-m-d', strtotime($leave_request->rejected_at)) : '',
            '{{rejection_reason}}' => $leave_request->rejection_reason ?? '',
            '{{comments}}' => $leave_request->comments ?? ''
        );
    }
    
    /**
     * Send welcome email
     */
    public function send_welcome_email($user, $temporary_password = '') {
        $variables = $this->get_user_variables($user);
        
        if (!empty($temporary_password)) {
            $variables['{{temporary_password}}'] = $temporary_password;
        }
        
        return $this->send_template_email(
            'welcome',
            $user->email,
            $user->first_name . ' ' . $user->last_name,
            $variables
        );
    }
    
    /**
     * Send leave request notification to HR
     */
    public function send_leave_request_notification($leave_request, $user) {
        $hr_users = $this->db->get_all_users();
        $hr_emails = array();
        
        foreach ($hr_users as $hr_user) {
            if ($hr_user->role === 'hr' || $hr_user->role === 'admin') {
                $hr_emails[] = array(
                    'email' => $hr_user->email,
                    'name' => $hr_user->first_name . ' ' . $hr_user->last_name
                );
            }
        }
        
        // If no HR users found, send to organization email
        if (empty($hr_emails)) {
            $org_email = LFCC_Leave_Settings::get_option('organization_email');
            if (!empty($org_email)) {
                $hr_emails[] = array(
                    'email' => $org_email,
                    'name' => 'HR Administrator'
                );
            }
        }
        
        $variables = array_merge(
            $this->get_user_variables($user),
            $this->get_leave_request_variables($leave_request)
        );
        
        $results = array();
        foreach ($hr_emails as $hr_email) {
            $results[] = $this->send_template_email(
                'leave_request_notification',
                $hr_email['email'],
                $hr_email['name'],
                $variables
            );
        }
        
        return !in_array(false, $results, true);
    }
    
    /**
     * Send leave approval notification
     */
    public function send_leave_approval_notification($leave_request, $user, $approved_by_user) {
        $variables = array_merge(
            $this->get_user_variables($user),
            $this->get_leave_request_variables($leave_request)
        );
        
        $variables['{{approved_by}}'] = $approved_by_user->first_name . ' ' . $approved_by_user->last_name;
        $variables['{{approved_date}}'] = date('Y-m-d');
        
        return $this->send_template_email(
            'leave_approved',
            $user->email,
            $user->first_name . ' ' . $user->last_name,
            $variables
        );
    }
    
    /**
     * Send leave rejection notification
     */
    public function send_leave_rejection_notification($leave_request, $user, $rejected_by_user, $rejection_reason) {
        $variables = array_merge(
            $this->get_user_variables($user),
            $this->get_leave_request_variables($leave_request)
        );
        
        $variables['{{rejected_by}}'] = $rejected_by_user->first_name . ' ' . $rejected_by_user->last_name;
        $variables['{{rejected_date}}'] = date('Y-m-d');
        $variables['{{rejection_reason}}'] = $rejection_reason;
        
        return $this->send_template_email(
            'leave_rejected',
            $user->email,
            $user->first_name . ' ' . $user->last_name,
            $variables
        );
    }
    
    /**
     * Send password reset email
     */
    public function send_password_reset_email($user, $temporary_password) {
        $variables = $this->get_user_variables($user);
        $variables['{{temporary_password}}'] = $temporary_password;
        
        return $this->send_template_email(
            'password_reset',
            $user->email,
            $user->first_name . ' ' . $user->last_name,
            $variables
        );
    }
    
    /**
     * Log email
     */
    private function log_email($recipient_email, $recipient_name, $subject, $template_type, $content, $success) {
        $this->db->log_email(array(
            'recipient_email' => $recipient_email,
            'recipient_name' => $recipient_name,
            'subject' => $subject,
            'template_type' => $template_type,
            'content' => $content,
            'status' => $success ? 'sent' : 'failed',
            'error_message' => $success ? '' : 'Email sending failed',
            'sent_at' => $success ? current_time('mysql') : null
        ));
    }
    
    /**
     * Test email configuration
     */
    public function test_email_configuration($test_email) {
        $subject = 'LFCC Leave Management - Email Test';
        $content = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background-color: #d4edda; padding: 20px; text-align: center;">
                <h1 style="color: #155724; margin: 0;">Email Configuration Test</h1>
                <p style="color: #155724; margin: 10px 0 0 0;">LFCC Leave Management System</p>
            </div>
            
            <div style="padding: 30px 20px;">
                <p>This is a test email to verify that your email configuration is working correctly.</p>
                
                <p>If you received this email, your SMTP settings are configured properly.</p>
                
                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p style="margin: 0;"><strong>Test sent at:</strong> ' . current_time('Y-m-d H:i:s') . '</p>
                </div>
                
                <p>Best regards,<br>LFCC Leave Management System</p>
            </div>
        </div>';
        
        return $this->send_email($test_email, 'Test Recipient', $subject, $content, true);
    }
    
    /**
     * Get email logs
     */
    public function get_email_logs($limit = 50) {
        return $this->db->get_email_logs($limit);
    }
    
    /**
     * Preview email template
     */
    public function preview_email_template($template_id, $variables = array()) {
        $template_content = LFCC_Leave_Settings::get_option('email_template_' . $template_id, '');
        $template_subject = LFCC_Leave_Settings::get_option('email_template_' . $template_id . '_subject', '');
        
        if (empty($template_content)) {
            return false;
        }
        
        // Add sample variables if none provided
        if (empty($variables)) {
            $variables = $this->get_sample_variables();
        }
        
        return array(
            'subject' => $this->process_template_variables($template_subject, $variables),
            'content' => $this->process_template_variables($template_content, $variables)
        );
    }
    
    /**
     * Get sample variables for preview
     */
    private function get_sample_variables() {
        return array(
            '{{first_name}}' => 'John',
            '{{last_name}}' => 'Doe',
            '{{full_name}}' => 'John Doe',
            '{{username}}' => 'john.doe',
            '{{email}}' => 'john.doe@example.com',
            '{{department}}' => 'Administration',
            '{{phone}}' => '+27 12 345 6789',
            '{{hire_date}}' => '2024-01-15',
            '{{leave_type}}' => 'Annual Leave',
            '{{start_date}}' => '2024-12-20',
            '{{end_date}}' => '2024-12-31',
            '{{total_days}}' => '10',
            '{{reason}}' => 'Family vacation',
            '{{status}}' => 'Approved',
            '{{annual_leave}}' => '20',
            '{{sick_leave}}' => '10',
            '{{personal_leave}}' => '5',
            '{{emergency_leave}}' => '3',
            '{{annual_leave_used}}' => '5',
            '{{sick_leave_used}}' => '2',
            '{{personal_leave_used}}' => '1',
            '{{emergency_leave_used}}' => '0',
            '{{annual_leave_remaining}}' => '15',
            '{{sick_leave_remaining}}' => '8',
            '{{personal_leave_remaining}}' => '4',
            '{{emergency_leave_remaining}}' => '3',
            '{{approved_by}}' => 'HR Administrator',
            '{{approved_date}}' => '2024-12-15',
            '{{rejection_reason}}' => 'Insufficient leave balance',
            '{{temporary_password}}' => 'TempPass123'
        );
    }
}

