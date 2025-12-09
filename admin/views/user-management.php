<?php
/**
 * User Management Page
 * Admin interface for managing users and their leave allocations
 */

if (!defined('ABSPATH')) {
    exit;
}

$db = LFCC_Leave_Database::get_instance();
$email_handler = LFCC_Leave_Email_Handler::get_instance();

// Handle form submissions
if (isset($_POST['action']) && wp_verify_nonce($_POST['lfcc_user_nonce'], 'lfcc_user_action')) {
    $action = sanitize_text_field($_POST['action']);
    
    switch ($action) {
        case 'create_user':
            $user_data = array(
                'username' => sanitize_text_field($_POST['username']),
                'email' => sanitize_email($_POST['email']),
                'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'department' => sanitize_text_field($_POST['department']),
                'role' => sanitize_text_field($_POST['role']),
                'status' => sanitize_text_field($_POST['status']),
                'annual_leave' => intval($_POST['annual_leave']),
                'sick_leave' => intval($_POST['sick_leave']),
                'personal_leave' => intval($_POST['personal_leave']),
                'emergency_leave' => intval($_POST['emergency_leave']),
                'phone' => sanitize_text_field($_POST['phone']),
                'address' => sanitize_textarea_field($_POST['address']),
                'hire_date' => sanitize_text_field($_POST['hire_date'])
            );
            
            $result = $db->create_user($user_data);
            if ($result) {
                // Send welcome email if enabled
                if (LFCC_Leave_Settings::get_option('send_welcome_email') === 'yes') {
                    $user = $db->get_user_by_username($user_data['username']);
                    $email_handler->send_welcome_email($user, $_POST['password']);
                }
                echo '<div class="notice notice-success"><p>' . __('User created successfully!', 'lfcc-leave-management') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Failed to create user. Username or email may already exist.', 'lfcc-leave-management') . '</p></div>';
            }
            break;
            
        case 'update_user':
            $user_id = intval($_POST['user_id']);
            $user_data = array(
                'email' => sanitize_email($_POST['email']),
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'department' => sanitize_text_field($_POST['department']),
                'role' => sanitize_text_field($_POST['role']),
                'status' => sanitize_text_field($_POST['status']),
                'annual_leave' => intval($_POST['annual_leave']),
                'sick_leave' => intval($_POST['sick_leave']),
                'personal_leave' => intval($_POST['personal_leave']),
                'emergency_leave' => intval($_POST['emergency_leave']),
                'phone' => sanitize_text_field($_POST['phone']),
                'address' => sanitize_textarea_field($_POST['address']),
                'hire_date' => sanitize_text_field($_POST['hire_date'])
            );
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                $user_data['password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            $result = $db->update_user($user_id, $user_data);
            if ($result !== false) {
                echo '<div class="notice notice-success"><p>' . __('User updated successfully!', 'lfcc-leave-management') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Failed to update user.', 'lfcc-leave-management') . '</p></div>';
            }
            break;
            
        case 'delete_user':
            $user_id = intval($_POST['user_id']);
            $result = $db->delete_user($user_id);
            if ($result) {
                echo '<div class="notice notice-success"><p>' . __('User deleted successfully!', 'lfcc-leave-management') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Failed to delete user.', 'lfcc-leave-management') . '</p></div>';
            }
            break;
            
        case 'reset_password':
            $user_id = intval($_POST['user_id']);
            $user = $db->get_user($user_id);
            if ($user) {
                $temp_password = wp_generate_password(12, false);
                $result = $db->update_user($user_id, array('password_hash' => password_hash($temp_password, PASSWORD_DEFAULT)));
                
                if ($result !== false) {
                    $email_handler->send_password_reset_email($user, $temp_password);
                    echo '<div class="notice notice-success"><p>' . __('Password reset and email sent to user!', 'lfcc-leave-management') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Failed to reset password.', 'lfcc-leave-management') . '</p></div>';
                }
            }
            break;
    }
}

// Get users for display
$users = $db->get_all_users('all');
$current_user_edit = null;

if (isset($_GET['edit_user'])) {
    $edit_user_id = intval($_GET['edit_user']);
    $current_user_edit = $db->get_user($edit_user_id);
}
?>

<div class="wrap">
    <h1><?php _e('User Management', 'lfcc-leave-management'); ?></h1>
    
    <div class="lfcc-user-management-container">
        <!-- Add/Edit User Form -->
        <div class="lfcc-user-form-section">
            <h2><?php echo $current_user_edit ? __('Edit User', 'lfcc-leave-management') : __('Add New User', 'lfcc-leave-management'); ?></h2>
            
            <form method="post" action="" class="lfcc-user-form">
                <?php wp_nonce_field('lfcc_user_action', 'lfcc_user_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo $current_user_edit ? 'update_user' : 'create_user'; ?>" />
                <?php if ($current_user_edit): ?>
                    <input type="hidden" name="user_id" value="<?php echo esc_attr($current_user_edit->id); ?>" />
                <?php endif; ?>
                
                <div class="lfcc-form-grid">
                    <div class="lfcc-form-section">
                        <h3><?php _e('Basic Information', 'lfcc-leave-management'); ?></h3>
                        
                        <div class="lfcc-form-row">
                            <div class="lfcc-form-field">
                                <label for="username"><?php _e('Username', 'lfcc-leave-management'); ?> *</label>
                                <input type="text" name="username" id="username" 
                                       value="<?php echo $current_user_edit ? esc_attr($current_user_edit->username) : ''; ?>" 
                                       <?php echo $current_user_edit ? 'readonly' : 'required'; ?> />
                            </div>
                            <div class="lfcc-form-field">
                                <label for="email"><?php _e('Email', 'lfcc-leave-management'); ?> *</label>
                                <input type="email" name="email" id="email" 
                                       value="<?php echo $current_user_edit ? esc_attr($current_user_edit->email) : ''; ?>" required />
                            </div>
                        </div>
                        
                        <div class="lfcc-form-row">
                            <div class="lfcc-form-field">
                                <label for="first_name"><?php _e('First Name', 'lfcc-leave-management'); ?> *</label>
                                <input type="text" name="first_name" id="first_name" 
                                       value="<?php echo $current_user_edit ? esc_attr($current_user_edit->first_name) : ''; ?>" required />
                            </div>
                            <div class="lfcc-form-field">
                                <label for="last_name"><?php _e('Last Name', 'lfcc-leave-management'); ?> *</label>
                                <input type="text" name="last_name" id="last_name" 
                                       value="<?php echo $current_user_edit ? esc_attr($current_user_edit->last_name) : ''; ?>" required />
                            </div>
                        </div>
                        
                        <div class="lfcc-form-row">
                            <div class="lfcc-form-field">
                                <label for="password"><?php echo $current_user_edit ? __('New Password (leave blank to keep current)', 'lfcc-leave-management') : __('Password', 'lfcc-leave-management'); ?> <?php echo !$current_user_edit ? '*' : ''; ?></label>
                                <input type="password" name="password" id="password" <?php echo !$current_user_edit ? 'required' : ''; ?> />
                            </div>
                            <div class="lfcc-form-field">
                                <label for="phone"><?php _e('Phone', 'lfcc-leave-management'); ?></label>
                                <input type="text" name="phone" id="phone" 
                                       value="<?php echo $current_user_edit ? esc_attr($current_user_edit->phone) : ''; ?>" />
                            </div>
                        </div>
                        
                        <div class="lfcc-form-row">
                            <div class="lfcc-form-field full-width">
                                <label for="address"><?php _e('Address', 'lfcc-leave-management'); ?></label>
                                <textarea name="address" id="address" rows="3"><?php echo $current_user_edit ? esc_textarea($current_user_edit->address) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="lfcc-form-section">
                        <h3><?php _e('Work Information', 'lfcc-leave-management'); ?></h3>
                        
                        <div class="lfcc-form-row">
                            <div class="lfcc-form-field">
                                <label for="department"><?php _e('Department', 'lfcc-leave-management'); ?></label>
                                <input type="text" name="department" id="department" 
                                       value="<?php echo $current_user_edit ? esc_attr($current_user_edit->department) : ''; ?>" />
                            </div>
                            <div class="lfcc-form-field">
                                <label for="hire_date"><?php _e('Hire Date', 'lfcc-leave-management'); ?></label>
                                <input type="date" name="hire_date" id="hire_date" 
                                       value="<?php echo $current_user_edit ? esc_attr($current_user_edit->hire_date) : ''; ?>" />
                            </div>
                        </div>
                        
                        <div class="lfcc-form-row">
                            <div class="lfcc-form-field">
                                <label for="role"><?php _e('Role', 'lfcc-leave-management'); ?> *</label>
                                <select name="role" id="role" required>
                                    <option value="staff" <?php echo ($current_user_edit && $current_user_edit->role === 'staff') ? 'selected' : ''; ?>><?php _e('Staff', 'lfcc-leave-management'); ?></option>
                                    <option value="hr" <?php echo ($current_user_edit && $current_user_edit->role === 'hr') ? 'selected' : ''; ?>><?php _e('HR', 'lfcc-leave-management'); ?></option>
                                    <option value="admin" <?php echo ($current_user_edit && $current_user_edit->role === 'admin') ? 'selected' : ''; ?>><?php _e('Admin', 'lfcc-leave-management'); ?></option>
                                </select>
                            </div>
                            <div class="lfcc-form-field">
                                <label for="status"><?php _e('Status', 'lfcc-leave-management'); ?> *</label>
                                <select name="status" id="status" required>
                                    <option value="active" <?php echo ($current_user_edit && $current_user_edit->status === 'active') ? 'selected' : ''; ?>><?php _e('Active', 'lfcc-leave-management'); ?></option>
                                    <option value="inactive" <?php echo ($current_user_edit && $current_user_edit->status === 'inactive') ? 'selected' : ''; ?>><?php _e('Inactive', 'lfcc-leave-management'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="lfcc-form-section">
                        <h3><?php _e('Leave Allocations', 'lfcc-leave-management'); ?></h3>
                        
                        <div class="lfcc-form-row">
                            <div class="lfcc-form-field">
                                <label for="annual_leave"><?php _e('Annual Leave', 'lfcc-leave-management'); ?></label>
                                <input type="number" name="annual_leave" id="annual_leave" min="0" 
                                       value="<?php echo $current_user_edit ? esc_attr($current_user_edit->annual_leave) : LFCC_Leave_Settings::get_option('default_annual_leave', 20); ?>" />
                                <span class="description"><?php _e('days per year', 'lfcc-leave-management'); ?></span>
                            </div>
                            <div class="lfcc-form-field">
                                <label for="sick_leave"><?php _e('Sick Leave', 'lfcc-leave-management'); ?></label>
                                <input type="number" name="sick_leave" id="sick_leave" min="0" 
                                       value="<?php echo $current_user_edit ? esc_attr($current_user_edit->sick_leave) : LFCC_Leave_Settings::get_option('default_sick_leave', 10); ?>" />
                                <span class="description"><?php _e('days per year', 'lfcc-leave-management'); ?></span>
                            </div>
                        </div>
                        
                        <div class="lfcc-form-row">
                            <div class="lfcc-form-field">
                                <label for="personal_leave"><?php _e('Personal Leave', 'lfcc-leave-management'); ?></label>
                                <input type="number" name="personal_leave" id="personal_leave" min="0" 
                                       value="<?php echo $current_user_edit ? esc_attr($current_user_edit->personal_leave) : LFCC_Leave_Settings::get_option('default_personal_leave', 5); ?>" />
                                <span class="description"><?php _e('days per year', 'lfcc-leave-management'); ?></span>
                            </div>
                            <div class="lfcc-form-field">
                                <label for="emergency_leave"><?php _e('Emergency Leave', 'lfcc-leave-management'); ?></label>
                                <input type="number" name="emergency_leave" id="emergency_leave" min="0" 
                                       value="<?php echo $current_user_edit ? esc_attr($current_user_edit->emergency_leave) : LFCC_Leave_Settings::get_option('default_emergency_leave', 3); ?>" />
                                <span class="description"><?php _e('days per year', 'lfcc-leave-management'); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($current_user_edit): ?>
                        <div class="lfcc-leave-usage-summary">
                            <h4><?php _e('Current Usage', 'lfcc-leave-management'); ?></h4>
                            <div class="lfcc-usage-grid">
                                <div class="lfcc-usage-item">
                                    <span class="label"><?php _e('Annual Used:', 'lfcc-leave-management'); ?></span>
                                    <span class="value"><?php echo esc_html($current_user_edit->annual_leave_used); ?> / <?php echo esc_html($current_user_edit->annual_leave); ?></span>
                                </div>
                                <div class="lfcc-usage-item">
                                    <span class="label"><?php _e('Sick Used:', 'lfcc-leave-management'); ?></span>
                                    <span class="value"><?php echo esc_html($current_user_edit->sick_leave_used); ?> / <?php echo esc_html($current_user_edit->sick_leave); ?></span>
                                </div>
                                <div class="lfcc-usage-item">
                                    <span class="label"><?php _e('Personal Used:', 'lfcc-leave-management'); ?></span>
                                    <span class="value"><?php echo esc_html($current_user_edit->personal_leave_used); ?> / <?php echo esc_html($current_user_edit->personal_leave); ?></span>
                                </div>
                                <div class="lfcc-usage-item">
                                    <span class="label"><?php _e('Emergency Used:', 'lfcc-leave-management'); ?></span>
                                    <span class="value"><?php echo esc_html($current_user_edit->emergency_leave_used); ?> / <?php echo esc_html($current_user_edit->emergency_leave); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="lfcc-form-actions">
                    <?php submit_button($current_user_edit ? __('Update User', 'lfcc-leave-management') : __('Create User', 'lfcc-leave-management'), 'primary', 'submit', false); ?>
                    
                    <?php if ($current_user_edit): ?>
                        <a href="<?php echo admin_url('admin.php?page=lfcc-leave-users'); ?>" class="button"><?php _e('Cancel', 'lfcc-leave-management'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Users List -->
        <div class="lfcc-users-list-section">
            <h2><?php _e('Existing Users', 'lfcc-leave-management'); ?></h2>
            
            <div class="lfcc-users-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'lfcc-leave-management'); ?></th>
                            <th><?php _e('Email', 'lfcc-leave-management'); ?></th>
                            <th><?php _e('Department', 'lfcc-leave-management'); ?></th>
                            <th><?php _e('Role', 'lfcc-leave-management'); ?></th>
                            <th><?php _e('Status', 'lfcc-leave-management'); ?></th>
                            <th><?php _e('Leave Balance', 'lfcc-leave-management'); ?></th>
                            <th><?php _e('Actions', 'lfcc-leave-management'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="no-items"><?php _e('No users found.', 'lfcc-leave-management'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?></strong><br>
                                        <small><?php echo esc_html($user->username); ?></small>
                                    </td>
                                    <td><?php echo esc_html($user->email); ?></td>
                                    <td><?php echo esc_html($user->department); ?></td>
                                    <td>
                                        <span class="lfcc-role-badge lfcc-role-<?php echo esc_attr($user->role); ?>">
                                            <?php echo esc_html(ucfirst($user->role)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="lfcc-status-badge lfcc-status-<?php echo esc_attr($user->status); ?>">
                                            <?php echo esc_html(ucfirst($user->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="lfcc-leave-balance-mini">
                                            <div class="balance-item">
                                                <span class="label">A:</span>
                                                <span class="value"><?php echo ($user->annual_leave - $user->annual_leave_used); ?>/<?php echo $user->annual_leave; ?></span>
                                            </div>
                                            <div class="balance-item">
                                                <span class="label">S:</span>
                                                <span class="value"><?php echo ($user->sick_leave - $user->sick_leave_used); ?>/<?php echo $user->sick_leave; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="lfcc-user-actions">
                                            <a href="<?php echo admin_url('admin.php?page=lfcc-leave-users&edit_user=' . $user->id); ?>" 
                                               class="button button-small"><?php _e('Edit', 'lfcc-leave-management'); ?></a>
                                            
                                            <form method="post" style="display: inline-block;" 
                                                  onsubmit="return confirm('<?php _e('Are you sure you want to reset this user\'s password?', 'lfcc-leave-management'); ?>');">
                                                <?php wp_nonce_field('lfcc_user_action', 'lfcc_user_nonce'); ?>
                                                <input type="hidden" name="action" value="reset_password" />
                                                <input type="hidden" name="user_id" value="<?php echo esc_attr($user->id); ?>" />
                                                <button type="submit" class="button button-small"><?php _e('Reset Password', 'lfcc-leave-management'); ?></button>
                                            </form>
                                            
                                            <?php if ($user->role !== 'admin'): ?>
                                            <form method="post" style="display: inline-block;" 
                                                  onsubmit="return confirm('<?php _e('Are you sure you want to delete this user? This action cannot be undone.', 'lfcc-leave-management'); ?>');">
                                                <?php wp_nonce_field('lfcc_user_action', 'lfcc_user_nonce'); ?>
                                                <input type="hidden" name="action" value="delete_user" />
                                                <input type="hidden" name="user_id" value="<?php echo esc_attr($user->id); ?>" />
                                                <button type="submit" class="button button-small button-link-delete"><?php _e('Delete', 'lfcc-leave-management'); ?></button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.lfcc-user-management-container {
    display: flex;
    gap: 30px;
    margin-top: 20px;
}

.lfcc-user-form-section {
    flex: 0 0 500px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    height: fit-content;
}

.lfcc-users-list-section {
    flex: 1;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.lfcc-form-grid {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.lfcc-form-section h3 {
    margin: 0 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid #ddd;
    color: #0073aa;
}

.lfcc-form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.lfcc-form-field {
    flex: 1;
}

.lfcc-form-field.full-width {
    flex: 1 1 100%;
}

.lfcc-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.lfcc-form-field input,
.lfcc-form-field select,
.lfcc-form-field textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.lfcc-form-field .description {
    font-size: 12px;
    color: #666;
    margin-top: 3px;
    display: block;
}

.lfcc-leave-usage-summary {
    margin-top: 15px;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 3px;
}

.lfcc-leave-usage-summary h4 {
    margin: 0 0 10px 0;
}

.lfcc-usage-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.lfcc-usage-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
}

.lfcc-usage-item .label {
    font-weight: 600;
}

.lfcc-usage-item .value {
    color: #0073aa;
    font-weight: 600;
}

.lfcc-form-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.lfcc-users-table-container {
    overflow-x: auto;
}

.lfcc-role-badge,
.lfcc-status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.lfcc-role-staff {
    background-color: #e3f2fd;
    color: #1976d2;
}

.lfcc-role-hr {
    background-color: #fff3e0;
    color: #f57c00;
}

.lfcc-role-admin {
    background-color: #fce4ec;
    color: #c2185b;
}

.lfcc-status-active {
    background-color: #e8f5e8;
    color: #2e7d32;
}

.lfcc-status-inactive {
    background-color: #ffebee;
    color: #d32f2f;
}

.lfcc-leave-balance-mini {
    font-size: 12px;
}

.lfcc-leave-balance-mini .balance-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2px;
}

.lfcc-leave-balance-mini .label {
    font-weight: 600;
    color: #666;
}

.lfcc-leave-balance-mini .value {
    color: #0073aa;
    font-weight: 600;
}

.lfcc-user-actions {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.lfcc-user-actions .button {
    font-size: 11px;
    padding: 4px 8px;
    height: auto;
    line-height: 1.2;
}

@media (max-width: 1200px) {
    .lfcc-user-management-container {
        flex-direction: column;
    }
    
    .lfcc-user-form-section {
        flex: none;
    }
}

@media (max-width: 768px) {
    .lfcc-form-row {
        flex-direction: column;
    }
    
    .lfcc-usage-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Form validation
    $('.lfcc-user-form').on('submit', function(e) {
        var username = $('#username').val().trim();
        var email = $('#email').val().trim();
        var firstName = $('#first_name').val().trim();
        var lastName = $('#last_name').val().trim();
        var password = $('#password').val();
        var isEdit = $('input[name="action"]').val() === 'update_user';
        
        if (!username || !email || !firstName || !lastName) {
            alert('<?php _e('Please fill in all required fields.', 'lfcc-leave-management'); ?>');
            e.preventDefault();
            return false;
        }
        
        if (!isEdit && !password) {
            alert('<?php _e('Password is required for new users.', 'lfcc-leave-management'); ?>');
            e.preventDefault();
            return false;
        }
        
        // Email validation
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('<?php _e('Please enter a valid email address.', 'lfcc-leave-management'); ?>');
            e.preventDefault();
            return false;
        }
        
        // Username validation (only for new users)
        if (!isEdit) {
            var usernameRegex = /^[a-zA-Z0-9._-]+$/;
            if (!usernameRegex.test(username)) {
                alert('<?php _e('Username can only contain letters, numbers, dots, underscores, and hyphens.', 'lfcc-leave-management'); ?>');
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Auto-generate username from first and last name
    $('#first_name, #last_name').on('input', function() {
        if ($('input[name="action"]').val() === 'create_user') {
            var firstName = $('#first_name').val().toLowerCase().replace(/[^a-z0-9]/g, '');
            var lastName = $('#last_name').val().toLowerCase().replace(/[^a-z0-9]/g, '');
            
            if (firstName && lastName) {
                $('#username').val(firstName + '.' + lastName);
            }
        }
    });
    
    // Password strength indicator
    $('#password').on('input', function() {
        var password = $(this).val();
        var strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        var strengthText = '';
        var strengthClass = '';
        
        switch (strength) {
            case 0:
            case 1:
                strengthText = '<?php _e('Weak', 'lfcc-leave-management'); ?>';
                strengthClass = 'weak';
                break;
            case 2:
            case 3:
                strengthText = '<?php _e('Medium', 'lfcc-leave-management'); ?>';
                strengthClass = 'medium';
                break;
            case 4:
            case 5:
                strengthText = '<?php _e('Strong', 'lfcc-leave-management'); ?>';
                strengthClass = 'strong';
                break;
        }
        
        // Remove existing strength indicator
        $('#password').next('.password-strength').remove();
        
        if (password.length > 0) {
            $('#password').after('<div class="password-strength ' + strengthClass + '">' + strengthText + '</div>');
        }
    });
});
</script>

<style>
.password-strength {
    font-size: 12px;
    margin-top: 3px;
    padding: 2px 5px;
    border-radius: 2px;
}

.password-strength.weak {
    background-color: #ffebee;
    color: #d32f2f;
}

.password-strength.medium {
    background-color: #fff3e0;
    color: #f57c00;
}

.password-strength.strong {
    background-color: #e8f5e8;
    color: #2e7d32;
}
</style>

