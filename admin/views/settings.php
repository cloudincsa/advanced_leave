<?php
/**
 * Admin Settings Page
 * Main settings configuration for the LFCC Leave Management plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['lfcc_settings_nonce'], 'lfcc_settings_save')) {
    
    // FIRST: Handle subdomain_enabled checkbox explicitly
    $subdomain_enabled_value = isset($_POST['subdomain_enabled']) ? 'yes' : 'no';
    LFCC_Leave_Settings::update_option('subdomain_enabled', $subdomain_enabled_value);
    
    // Also save directly to WordPress options as absolute backup
    update_option('lfcc_leave_subdomain_enabled', $subdomain_enabled_value);
    
    $settings_to_save = array(
        // Organization settings
        'organization_name',
        'organization_email',
        'organization_phone',
        'organization_address',
        'organization_website',
        
        // Subdomain settings
        'subdomain_name',
        // Note: subdomain_enabled is handled separately as a checkbox
        
        // SMTP settings
        // Note: smtp_enabled is handled separately as a checkbox
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'from_email',
        'from_name',
        
        // Leave settings
        // Note: checkboxes are handled separately below
        'default_annual_leave',
        'default_sick_leave',
        'default_personal_leave',
        'default_emergency_leave',
        
        // Notification settings
        // Note: checkboxes are handled separately below
        
        // Calendar settings
        'calendar_start_day',
        
        // Security settings
        'session_timeout',
        'password_min_length',
        
        // Display settings
        'date_format',
        'time_format',
        'timezone',
        'items_per_page'
    );
    
    // Save regular text/select fields
    foreach ($settings_to_save as $setting) {
        if (isset($_POST[$setting])) {
            $value = sanitize_text_field($_POST[$setting]);
            $result = LFCC_Leave_Settings::update_option($setting, $value);
            
            // Debug logging
            $logger = LFCC_Leave_Logger::get_instance();
            $logger->info("Settings save attempt", array(
                'setting' => $setting,
                'value' => $value,
                'result' => $result ? 'success' : 'failed'
            ));
        }
    }
    
    // Handle checkbox values separately
    // Checkboxes are only in $_POST when checked, so we need to explicitly set to 'no' when unchecked
    $checkbox_settings = array('subdomain_enabled', 'smtp_enabled', 'enable_user_registration', 
                              'require_admin_approval', 'weekend_counts_as_leave', 'allow_leave_editing',
                              'require_reapproval_on_edit', 'notify_admin_on_request', 'notify_user_on_approval',
                              'notify_user_on_rejection', 'send_welcome_email', 'show_calendar_to_all',
                              'show_employee_names', 'require_password_change');
    
    foreach ($checkbox_settings as $checkbox) {
        $value = isset($_POST[$checkbox]) ? 'yes' : 'no';
        $result = LFCC_Leave_Settings::update_option($checkbox, $value);
        
        // Debug logging
        $logger = LFCC_Leave_Logger::get_instance();
        $logger->info("Checkbox save", array(
            'setting' => $checkbox,
            'value' => $value,
            'in_post' => isset($_POST[$checkbox]) ? 'yes' : 'no',
            'result' => $result ? 'success' : 'failed'
        ));
    }
    
    // Verify subdomain settings were saved correctly
    $saved_subdomain_enabled = LFCC_Leave_Settings::get_option('subdomain_enabled', 'no');
    $saved_subdomain_name = LFCC_Leave_Settings::get_option('subdomain_name', '');
    
    $success_message = __('Settings saved successfully!', 'lfcc-leave-management');
    $success_message .= '<br><strong>Subdomain Status:</strong> ' . ($saved_subdomain_enabled === 'yes' ? '✓ ENABLED' : '✗ DISABLED');
    if ($saved_subdomain_enabled === 'yes' && !empty($saved_subdomain_name)) {
        $success_message .= ' - ' . sprintf(__('Configured as: %s', 'lfcc-leave-management'), 
            '<strong>' . esc_html($saved_subdomain_name) . '.' . esc_html(parse_url(get_site_url(), PHP_URL_HOST)) . '</strong>');
    }
    
    echo '<div class="notice notice-success"><p>' . $success_message . '</p></div>';
}

// Test email functionality
if (isset($_POST['test_email']) && wp_verify_nonce($_POST['lfcc_test_email_nonce'], 'lfcc_test_email')) {
    $test_email = sanitize_email($_POST['test_email_address']);
    if (!empty($test_email)) {
        $email_handler = LFCC_Leave_Email_Handler::get_instance();
        $result = $email_handler->test_email_configuration($test_email);
        
        if ($result) {
            echo '<div class="notice notice-success"><p>' . __('Test email sent successfully!', 'lfcc-leave-management') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to send test email. Please check your SMTP settings.', 'lfcc-leave-management') . '</p></div>';
        }
    }
}

// Get current settings
$current_settings = array();
$default_options = LFCC_Leave_Settings::get_default_options();

foreach ($default_options as $setting => $default_value) {
    $current_settings[$setting] = LFCC_Leave_Settings::get_option($setting, $default_value);
}
?>

<div class="wrap">
    <h1><?php _e('LFCC Leave Management Settings', 'lfcc-leave-management'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('lfcc_settings_save', 'lfcc_settings_nonce'); ?>
        
        <div class="lfcc-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#organization" class="nav-tab nav-tab-active"><?php _e('Organization', 'lfcc-leave-management'); ?></a>
                <a href="#subdomain" class="nav-tab"><?php _e('Subdomain', 'lfcc-leave-management'); ?></a>
                <a href="#email" class="nav-tab"><?php _e('Email Settings', 'lfcc-leave-management'); ?></a>
                <a href="#leave" class="nav-tab"><?php _e('Leave Settings', 'lfcc-leave-management'); ?></a>
                <a href="#notifications" class="nav-tab"><?php _e('Notifications', 'lfcc-leave-management'); ?></a>
                <a href="#calendar" class="nav-tab"><?php _e('Calendar', 'lfcc-leave-management'); ?></a>
                <a href="#security" class="nav-tab"><?php _e('Security', 'lfcc-leave-management'); ?></a>
                <a href="#display" class="nav-tab"><?php _e('Display', 'lfcc-leave-management'); ?></a>
            </nav>
            
            <!-- Organization Settings -->
            <div id="organization" class="tab-content active">
                <h2><?php _e('Organization Information', 'lfcc-leave-management'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Organization Name', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="text" name="organization_name" value="<?php echo esc_attr($current_settings['organization_name']); ?>" class="regular-text" />
                            <p class="description"><?php _e('The name of your organization', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Organization Email', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="email" name="organization_email" value="<?php echo esc_attr($current_settings['organization_email']); ?>" class="regular-text" />
                            <p class="description"><?php _e('Main contact email for your organization', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Organization Phone', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="text" name="organization_phone" value="<?php echo esc_attr($current_settings['organization_phone']); ?>" class="regular-text" />
                            <p class="description"><?php _e('Main contact phone number', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Organization Address', 'lfcc-leave-management'); ?></th>
                        <td>
                            <textarea name="organization_address" rows="3" class="large-text"><?php echo esc_textarea($current_settings['organization_address']); ?></textarea>
                            <p class="description"><?php _e('Physical address of your organization', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Organization Website', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="url" name="organization_website" value="<?php echo esc_attr($current_settings['organization_website']); ?>" class="regular-text" />
                            <p class="description"><?php _e('Your organization\'s website URL', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Subdomain Settings -->
            <div id="subdomain" class="tab-content">
                <h2><?php _e('Subdomain Configuration', 'lfcc-leave-management'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Subdomain Access', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="subdomain_enabled" value="yes" <?php checked($current_settings['subdomain_enabled'], 'yes'); ?> />
                                <?php _e('Enable frontend access via subdomain', 'lfcc-leave-management'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Subdomain Name', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="text" name="subdomain_name" value="<?php echo esc_attr($current_settings['subdomain_name']); ?>" class="regular-text" />
                            <p class="description">
                                <?php _e('Enter the subdomain name (e.g., "leave" for leave.littlefalls.co.za)', 'lfcc-leave-management'); ?><br>
                                <strong><?php _e('Current URL:', 'lfcc-leave-management'); ?></strong> 
                                <span id="subdomain-preview"><?php 
                                    $host = parse_url(get_site_url(), PHP_URL_HOST);
                                    // Remove www. prefix if present
                                    $host = preg_replace('/^www\./', '', $host);
                                    echo esc_html($current_settings['subdomain_name']) . '.' . esc_html($host);
                                ?></span>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div class="lfcc-info-box">
                    <h3><?php _e('DNS Configuration Required', 'lfcc-leave-management'); ?></h3>
                    <p><?php _e('To use subdomain access, you need to configure your DNS settings:', 'lfcc-leave-management'); ?></p>
                    <ol>
                        <li><?php _e('Create a CNAME record pointing your subdomain to your main domain', 'lfcc-leave-management'); ?></li>
                        <li><?php _e('Ensure your web server is configured to handle the subdomain', 'lfcc-leave-management'); ?></li>
                        <li><?php _e('Test the subdomain access after DNS propagation', 'lfcc-leave-management'); ?></li>
                    </ol>
                </div>
            </div>
            
            <!-- Email Settings -->
            <div id="email" class="tab-content">
                <h2><?php _e('Email Configuration', 'lfcc-leave-management'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable SMTP', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="smtp_enabled" value="yes" <?php checked($current_settings['smtp_enabled'], 'yes'); ?> id="smtp_enabled" />
                                <?php _e('Use SMTP for sending emails', 'lfcc-leave-management'); ?>
                            </label>
                            <p class="description"><?php _e('Enable this to use custom SMTP settings instead of WordPress default mail', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <div id="smtp_settings" style="<?php echo $current_settings['smtp_enabled'] === 'yes' ? '' : 'display: none;'; ?>">
                    <h3><?php _e('SMTP Server Settings', 'lfcc-leave-management'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('SMTP Host', 'lfcc-leave-management'); ?></th>
                            <td>
                                <input type="text" name="smtp_host" value="<?php echo esc_attr($current_settings['smtp_host']); ?>" class="regular-text" />
                                <p class="description"><?php _e('SMTP server hostname (e.g., smtp.gmail.com)', 'lfcc-leave-management'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('SMTP Port', 'lfcc-leave-management'); ?></th>
                            <td>
                                <input type="number" name="smtp_port" value="<?php echo esc_attr($current_settings['smtp_port']); ?>" class="small-text" />
                                <p class="description"><?php _e('SMTP port (587 for TLS, 465 for SSL, 25 for no encryption)', 'lfcc-leave-management'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('SMTP Username', 'lfcc-leave-management'); ?></th>
                            <td>
                                <input type="text" name="smtp_username" value="<?php echo esc_attr($current_settings['smtp_username']); ?>" class="regular-text" />
                                <p class="description"><?php _e('SMTP authentication username', 'lfcc-leave-management'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('SMTP Password', 'lfcc-leave-management'); ?></th>
                            <td>
                                <input type="password" name="smtp_password" value="<?php echo esc_attr($current_settings['smtp_password']); ?>" class="regular-text" />
                                <p class="description"><?php _e('SMTP authentication password', 'lfcc-leave-management'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Encryption', 'lfcc-leave-management'); ?></th>
                            <td>
                                <select name="smtp_encryption">
                                    <option value="none" <?php selected($current_settings['smtp_encryption'], 'none'); ?>><?php _e('None', 'lfcc-leave-management'); ?></option>
                                    <option value="tls" <?php selected($current_settings['smtp_encryption'], 'tls'); ?>><?php _e('TLS', 'lfcc-leave-management'); ?></option>
                                    <option value="ssl" <?php selected($current_settings['smtp_encryption'], 'ssl'); ?>><?php _e('SSL', 'lfcc-leave-management'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <h3><?php _e('Email From Settings', 'lfcc-leave-management'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('From Email', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="email" name="from_email" value="<?php echo esc_attr($current_settings['from_email']); ?>" class="regular-text" />
                            <p class="description"><?php _e('Email address that emails will be sent from', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('From Name', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="text" name="from_name" value="<?php echo esc_attr($current_settings['from_name']); ?>" class="regular-text" />
                            <p class="description"><?php _e('Name that emails will be sent from', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Test Email Configuration', 'lfcc-leave-management'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Send Test Email', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="email" name="test_email_address" placeholder="test@example.com" class="regular-text" />
                            <?php wp_nonce_field('lfcc_test_email', 'lfcc_test_email_nonce'); ?>
                            <input type="submit" name="test_email" class="button" value="<?php _e('Send Test Email', 'lfcc-leave-management'); ?>" />
                            <p class="description"><?php _e('Send a test email to verify your configuration', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Leave Settings -->
            <div id="leave" class="tab-content">
                <h2><?php _e('Leave Management Settings', 'lfcc-leave-management'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('User Registration', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_user_registration" value="yes" <?php checked($current_settings['enable_user_registration'], 'yes'); ?> />
                                <?php _e('Allow users to register themselves', 'lfcc-leave-management'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Admin Approval', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="require_admin_approval" value="yes" <?php checked($current_settings['require_admin_approval'], 'yes'); ?> />
                                <?php _e('Require admin approval for new user registrations', 'lfcc-leave-management'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Weekend Calculation', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="weekend_counts_as_leave" value="yes" <?php checked($current_settings['weekend_counts_as_leave'], 'yes'); ?> />
                                <?php _e('Count weekends as leave days', 'lfcc-leave-management'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Leave Editing', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="allow_leave_editing" value="yes" <?php checked($current_settings['allow_leave_editing'], 'yes'); ?> />
                                <?php _e('Allow users to edit their leave requests', 'lfcc-leave-management'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Re-approval Required', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="require_reapproval_on_edit" value="yes" <?php checked($current_settings['require_reapproval_on_edit'], 'yes'); ?> />
                                <?php _e('Require re-approval when leave requests are edited', 'lfcc-leave-management'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Default Leave Allocations', 'lfcc-leave-management'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Annual Leave', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="number" name="default_annual_leave" value="<?php echo esc_attr($current_settings['default_annual_leave']); ?>" class="small-text" min="0" />
                            <?php _e('days per year', 'lfcc-leave-management'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Sick Leave', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="number" name="default_sick_leave" value="<?php echo esc_attr($current_settings['default_sick_leave']); ?>" class="small-text" min="0" />
                            <?php _e('days per year', 'lfcc-leave-management'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Personal Leave', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="number" name="default_personal_leave" value="<?php echo esc_attr($current_settings['default_personal_leave']); ?>" class="small-text" min="0" />
                            <?php _e('days per year', 'lfcc-leave-management'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Emergency Leave', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="number" name="default_emergency_leave" value="<?php echo esc_attr($current_settings['default_emergency_leave']); ?>" class="small-text" min="0" />
                            <?php _e('days per year', 'lfcc-leave-management'); ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Notification Settings -->
            <div id="notifications" class="tab-content">
                <h2><?php _e('Email Notifications', 'lfcc-leave-management'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Admin Notifications', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="notify_admin_on_request" value="yes" <?php checked($current_settings['notify_admin_on_request'], 'yes'); ?> />
                                <?php _e('Notify administrators when new leave requests are submitted', 'lfcc-leave-management'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('User Notifications', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="notify_user_on_approval" value="yes" <?php checked($current_settings['notify_user_on_approval'], 'yes'); ?> />
                                <?php _e('Notify users when their leave requests are approved', 'lfcc-leave-management'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="notify_user_on_rejection" value="yes" <?php checked($current_settings['notify_user_on_rejection'], 'yes'); ?> />
                                <?php _e('Notify users when their leave requests are rejected', 'lfcc-leave-management'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Welcome Emails', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="send_welcome_email" value="yes" <?php checked($current_settings['send_welcome_email'], 'yes'); ?> />
                                <?php _e('Send welcome email to new users', 'lfcc-leave-management'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Calendar Settings -->
            <div id="calendar" class="tab-content">
                <h2><?php _e('Calendar Display Settings', 'lfcc-leave-management'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Calendar Visibility', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="show_calendar_to_all" value="yes" <?php checked($current_settings['show_calendar_to_all'], 'yes'); ?> />
                                <?php _e('Show leave calendar to all users', 'lfcc-leave-management'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Employee Names', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="show_employee_names" value="yes" <?php checked($current_settings['show_employee_names'], 'yes'); ?> />
                                <?php _e('Show employee names on calendar', 'lfcc-leave-management'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Week Start Day', 'lfcc-leave-management'); ?></th>
                        <td>
                            <select name="calendar_start_day">
                                <option value="0" <?php selected($current_settings['calendar_start_day'], '0'); ?>><?php _e('Sunday', 'lfcc-leave-management'); ?></option>
                                <option value="1" <?php selected($current_settings['calendar_start_day'], '1'); ?>><?php _e('Monday', 'lfcc-leave-management'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Security Settings -->
            <div id="security" class="tab-content">
                <h2><?php _e('Security Settings', 'lfcc-leave-management'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Session Timeout', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="number" name="session_timeout" value="<?php echo esc_attr($current_settings['session_timeout']); ?>" class="small-text" min="30" />
                            <?php _e('minutes', 'lfcc-leave-management'); ?>
                            <p class="description"><?php _e('How long users stay logged in without activity', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Password Requirements', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="number" name="password_min_length" value="<?php echo esc_attr($current_settings['password_min_length']); ?>" class="small-text" min="4" />
                            <?php _e('minimum characters', 'lfcc-leave-management'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Force Password Change', 'lfcc-leave-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="require_password_change" value="yes" <?php checked($current_settings['require_password_change'], 'yes'); ?> />
                                <?php _e('Require users to change password on first login', 'lfcc-leave-management'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Display Settings -->
            <div id="display" class="tab-content">
                <h2><?php _e('Display Settings', 'lfcc-leave-management'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Date Format', 'lfcc-leave-management'); ?></th>
                        <td>
                            <select name="date_format">
                                <option value="Y-m-d" <?php selected($current_settings['date_format'], 'Y-m-d'); ?>>YYYY-MM-DD</option>
                                <option value="d/m/Y" <?php selected($current_settings['date_format'], 'd/m/Y'); ?>>DD/MM/YYYY</option>
                                <option value="m/d/Y" <?php selected($current_settings['date_format'], 'm/d/Y'); ?>>MM/DD/YYYY</option>
                                <option value="d-m-Y" <?php selected($current_settings['date_format'], 'd-m-Y'); ?>>DD-MM-YYYY</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Time Format', 'lfcc-leave-management'); ?></th>
                        <td>
                            <select name="time_format">
                                <option value="H:i" <?php selected($current_settings['time_format'], 'H:i'); ?>>24 Hour (HH:MM)</option>
                                <option value="g:i A" <?php selected($current_settings['time_format'], 'g:i A'); ?>>12 Hour (H:MM AM/PM)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Timezone', 'lfcc-leave-management'); ?></th>
                        <td>
                            <select name="timezone">
                                <option value="Africa/Johannesburg" <?php selected($current_settings['timezone'], 'Africa/Johannesburg'); ?>>Africa/Johannesburg</option>
                                <option value="UTC" <?php selected($current_settings['timezone'], 'UTC'); ?>>UTC</option>
                                <option value="America/New_York" <?php selected($current_settings['timezone'], 'America/New_York'); ?>>America/New_York</option>
                                <option value="Europe/London" <?php selected($current_settings['timezone'], 'Europe/London'); ?>>Europe/London</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Items Per Page', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="number" name="items_per_page" value="<?php echo esc_attr($current_settings['items_per_page']); ?>" class="small-text" min="5" max="100" />
                            <p class="description"><?php _e('Number of items to show per page in lists', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php submit_button(__('Save Settings', 'lfcc-leave-management')); ?>
    </form>
</div>

<style>
.lfcc-settings-tabs .nav-tab-wrapper {
    margin-bottom: 20px;
}

.lfcc-settings-tabs .tab-content {
    display: none;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-top: none;
}

.lfcc-settings-tabs .tab-content.active {
    display: block;
}

.lfcc-info-box {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 4px;
    padding: 15px;
    margin: 20px 0;
}

.lfcc-info-box h3 {
    margin-top: 0;
    color: #0073aa;
}

#subdomain-preview {
    font-weight: bold;
    color: #0073aa;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // SMTP settings toggle
    $('#smtp_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('#smtp_settings').show();
        } else {
            $('#smtp_settings').hide();
        }
    });
    
    // Subdomain preview update
    $('input[name="subdomain_name"]').on('input', function() {
        var subdomain = $(this).val();
        var host = '<?php echo esc_js(preg_replace("/^www\./", "", parse_url(get_site_url(), PHP_URL_HOST))); ?>';
        $('#subdomain-preview').text(subdomain + '.' + host);
    });
});
</script>

