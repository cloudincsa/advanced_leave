<?php
/**
 * Leave Requests Management Page
 * Admin interface for viewing and managing leave requests
 */

if (!defined('ABSPATH')) {
    exit;
}

$db = LFCC_Leave_Database::get_instance();
$email_handler = LFCC_Leave_Email_Handler::get_instance();

// Handle form submissions
if (isset($_POST['action']) && wp_verify_nonce($_POST['lfcc_leave_nonce'], 'lfcc_leave_action')) {
    $action = sanitize_text_field($_POST['action']);
    $request_id = intval($_POST['request_id']);
    
    switch ($action) {
        case 'approve_request':
            $leave_request = $db->get_leave_request($request_id);
            if ($leave_request) {
                $result = $db->update_leave_request($request_id, array(
                    'status' => 'approved',
                    'approved_by' => get_current_user_id(),
                    'approved_at' => current_time('mysql')
                ));
                
                if ($result !== false) {
                    // Update user's leave balance
                    $user = $db->get_user($leave_request->user_id);
                    $leave_type_field = $leave_request->leave_type . '_leave_used';
                    $current_used = $user->{$leave_type_field};
                    $new_used = $current_used + $leave_request->total_days;
                    
                    $db->update_user($leave_request->user_id, array(
                        $leave_type_field => $new_used
                    ));
                    
                    // Send approval notification
                    if (LFCC_Leave_Settings::get_option('notify_user_on_approval') === 'yes') {
                        $current_user = wp_get_current_user();
                        $approved_by_user = (object) array(
                            'first_name' => $current_user->first_name ?: 'HR',
                            'last_name' => $current_user->last_name ?: 'Administrator'
                        );
                        $email_handler->send_leave_approval_notification($leave_request, $user, $approved_by_user);
                    }
                    
                    echo '<div class="notice notice-success"><p>' . __('Leave request approved successfully!', 'lfcc-leave-management') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Failed to approve leave request.', 'lfcc-leave-management') . '</p></div>';
                }
            }
            break;
            
        case 'reject_request':
            $rejection_reason = sanitize_textarea_field($_POST['rejection_reason']);
            $leave_request = $db->get_leave_request($request_id);
            
            if ($leave_request) {
                $result = $db->update_leave_request($request_id, array(
                    'status' => 'rejected',
                    'rejection_reason' => $rejection_reason,
                    'approved_by' => get_current_user_id(),
                    'approved_at' => current_time('mysql')
                ));
                
                if ($result !== false) {
                    // Send rejection notification
                    if (LFCC_Leave_Settings::get_option('notify_user_on_rejection') === 'yes') {
                        $user = $db->get_user($leave_request->user_id);
                        $current_user = wp_get_current_user();
                        $rejected_by_user = (object) array(
                            'first_name' => $current_user->first_name ?: 'HR',
                            'last_name' => $current_user->last_name ?: 'Administrator'
                        );
                        $email_handler->send_leave_rejection_notification($leave_request, $user, $rejected_by_user, $rejection_reason);
                    }
                    
                    echo '<div class="notice notice-success"><p>' . __('Leave request rejected successfully!', 'lfcc-leave-management') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Failed to reject leave request.', 'lfcc-leave-management') . '</p></div>';
                }
            }
            break;
            
        case 'delete_request':
            $result = $db->delete_leave_request($request_id);
            if ($result) {
                echo '<div class="notice notice-success"><p>' . __('Leave request deleted successfully!', 'lfcc-leave-management') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Failed to delete leave request.', 'lfcc-leave-management') . '</p></div>';
            }
            break;
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$leave_type_filter = isset($_GET['leave_type']) ? sanitize_text_field($_GET['leave_type']) : 'all';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

// Build filters array
$filters = array();
if ($status_filter !== 'all') {
    $filters['status'] = $status_filter;
}
if ($user_filter > 0) {
    $filters['user_id'] = $user_filter;
}
if ($leave_type_filter !== 'all') {
    $filters['leave_type'] = $leave_type_filter;
}
if (!empty($date_from)) {
    $filters['start_date'] = $date_from;
}
if (!empty($date_to)) {
    $filters['end_date'] = $date_to;
}

// Get leave requests
$leave_requests = $db->get_leave_requests($filters);
$all_users = $db->get_all_users('all');

// Get statistics
$stats = array(
    'total' => count($db->get_leave_requests()),
    'pending' => count($db->get_leave_requests(array('status' => 'pending'))),
    'approved' => count($db->get_leave_requests(array('status' => 'approved'))),
    'rejected' => count($db->get_leave_requests(array('status' => 'rejected')))
);
?>

<div class="wrap">
    <h1><?php _e('Leave Requests Management', 'lfcc-leave-management'); ?></h1>
    
    <!-- Statistics Cards -->
    <div class="lfcc-stats-cards">
        <div class="lfcc-stat-card">
            <div class="stat-number"><?php echo esc_html($stats['total']); ?></div>
            <div class="stat-label"><?php _e('Total Requests', 'lfcc-leave-management'); ?></div>
        </div>
        <div class="lfcc-stat-card pending">
            <div class="stat-number"><?php echo esc_html($stats['pending']); ?></div>
            <div class="stat-label"><?php _e('Pending', 'lfcc-leave-management'); ?></div>
        </div>
        <div class="lfcc-stat-card approved">
            <div class="stat-number"><?php echo esc_html($stats['approved']); ?></div>
            <div class="stat-label"><?php _e('Approved', 'lfcc-leave-management'); ?></div>
        </div>
        <div class="lfcc-stat-card rejected">
            <div class="stat-number"><?php echo esc_html($stats['rejected']); ?></div>
            <div class="stat-label"><?php _e('Rejected', 'lfcc-leave-management'); ?></div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="lfcc-filters-section">
        <form method="get" action="" class="lfcc-filters-form">
            <input type="hidden" name="page" value="lfcc-leave-requests" />
            
            <div class="lfcc-filters-row">
                <div class="filter-field">
                    <label for="status"><?php _e('Status', 'lfcc-leave-management'); ?></label>
                    <select name="status" id="status">
                        <option value="all" <?php selected($status_filter, 'all'); ?>><?php _e('All Statuses', 'lfcc-leave-management'); ?></option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'lfcc-leave-management'); ?></option>
                        <option value="approved" <?php selected($status_filter, 'approved'); ?>><?php _e('Approved', 'lfcc-leave-management'); ?></option>
                        <option value="rejected" <?php selected($status_filter, 'rejected'); ?>><?php _e('Rejected', 'lfcc-leave-management'); ?></option>
                    </select>
                </div>
                
                <div class="filter-field">
                    <label for="user_id"><?php _e('Employee', 'lfcc-leave-management'); ?></label>
                    <select name="user_id" id="user_id">
                        <option value="0" <?php selected($user_filter, 0); ?>><?php _e('All Employees', 'lfcc-leave-management'); ?></option>
                        <?php foreach ($all_users as $user): ?>
                            <option value="<?php echo esc_attr($user->id); ?>" <?php selected($user_filter, $user->id); ?>>
                                <?php echo esc_html($user->first_name . ' ' . $user->last_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-field">
                    <label for="leave_type"><?php _e('Leave Type', 'lfcc-leave-management'); ?></label>
                    <select name="leave_type" id="leave_type">
                        <option value="all" <?php selected($leave_type_filter, 'all'); ?>><?php _e('All Types', 'lfcc-leave-management'); ?></option>
                        <option value="annual" <?php selected($leave_type_filter, 'annual'); ?>><?php _e('Annual Leave', 'lfcc-leave-management'); ?></option>
                        <option value="sick" <?php selected($leave_type_filter, 'sick'); ?>><?php _e('Sick Leave', 'lfcc-leave-management'); ?></option>
                        <option value="personal" <?php selected($leave_type_filter, 'personal'); ?>><?php _e('Personal Leave', 'lfcc-leave-management'); ?></option>
                        <option value="emergency" <?php selected($leave_type_filter, 'emergency'); ?>><?php _e('Emergency Leave', 'lfcc-leave-management'); ?></option>
                    </select>
                </div>
                
                <div class="filter-field">
                    <label for="date_from"><?php _e('Date From', 'lfcc-leave-management'); ?></label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>" />
                </div>
                
                <div class="filter-field">
                    <label for="date_to"><?php _e('Date To', 'lfcc-leave-management'); ?></label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>" />
                </div>
                
                <div class="filter-actions">
                    <input type="submit" class="button" value="<?php _e('Filter', 'lfcc-leave-management'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=lfcc-leave-requests'); ?>" class="button"><?php _e('Clear', 'lfcc-leave-management'); ?></a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Leave Requests Table -->
    <div class="lfcc-requests-table-section">
        <div class="lfcc-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Employee', 'lfcc-leave-management'); ?></th>
                        <th><?php _e('Leave Type', 'lfcc-leave-management'); ?></th>
                        <th><?php _e('Dates', 'lfcc-leave-management'); ?></th>
                        <th><?php _e('Days', 'lfcc-leave-management'); ?></th>
                        <th><?php _e('Reason', 'lfcc-leave-management'); ?></th>
                        <th><?php _e('Status', 'lfcc-leave-management'); ?></th>
                        <th><?php _e('Submitted', 'lfcc-leave-management'); ?></th>
                        <th><?php _e('Actions', 'lfcc-leave-management'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leave_requests)): ?>
                        <tr>
                            <td colspan="8" class="no-items"><?php _e('No leave requests found.', 'lfcc-leave-management'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leave_requests as $request): ?>
                            <tr class="lfcc-request-row lfcc-status-<?php echo esc_attr($request->status); ?>">
                                <td>
                                    <strong><?php echo esc_html($request->first_name . ' ' . $request->last_name); ?></strong><br>
                                    <small><?php echo esc_html($request->department); ?></small>
                                </td>
                                <td>
                                    <span class="lfcc-leave-type-badge lfcc-type-<?php echo esc_attr($request->leave_type); ?>">
                                        <?php 
                                        $leave_types = array(
                                            'annual' => __('Annual', 'lfcc-leave-management'),
                                            'sick' => __('Sick', 'lfcc-leave-management'),
                                            'personal' => __('Personal', 'lfcc-leave-management'),
                                            'emergency' => __('Emergency', 'lfcc-leave-management')
                                        );
                                        echo esc_html($leave_types[$request->leave_type] ?? ucfirst($request->leave_type));
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="lfcc-date-range">
                                        <div class="start-date"><?php echo esc_html(date('M j, Y', strtotime($request->start_date))); ?></div>
                                        <div class="date-separator">to</div>
                                        <div class="end-date"><?php echo esc_html(date('M j, Y', strtotime($request->end_date))); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="lfcc-days-count"><?php echo esc_html($request->total_days); ?></span>
                                </td>
                                <td>
                                    <div class="lfcc-reason-text">
                                        <?php echo esc_html(wp_trim_words($request->reason, 10)); ?>
                                        <?php if (strlen($request->reason) > 50): ?>
                                            <span class="lfcc-show-full-reason" data-full-reason="<?php echo esc_attr($request->reason); ?>">
                                                [<?php _e('Show more', 'lfcc-leave-management'); ?>]
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="lfcc-status-badge lfcc-status-<?php echo esc_attr($request->status); ?>">
                                        <?php echo esc_html(ucfirst($request->status)); ?>
                                    </span>
                                    <?php if ($request->is_edited): ?>
                                        <div class="lfcc-edited-indicator"><?php _e('(Edited)', 'lfcc-leave-management'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="lfcc-submitted-date">
                                        <?php echo esc_html(date('M j, Y', strtotime($request->created_at))); ?>
                                    </div>
                                    <div class="lfcc-submitted-time">
                                        <?php echo esc_html(date('H:i', strtotime($request->created_at))); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="lfcc-request-actions">
                                        <?php if ($request->status === 'pending'): ?>
                                            <button type="button" class="button button-small button-primary lfcc-approve-btn" 
                                                    data-request-id="<?php echo esc_attr($request->id); ?>">
                                                <?php _e('Approve', 'lfcc-leave-management'); ?>
                                            </button>
                                            <button type="button" class="button button-small lfcc-reject-btn" 
                                                    data-request-id="<?php echo esc_attr($request->id); ?>">
                                                <?php _e('Reject', 'lfcc-leave-management'); ?>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="button button-small lfcc-view-details-btn" 
                                                data-request-id="<?php echo esc_attr($request->id); ?>">
                                            <?php _e('Details', 'lfcc-leave-management'); ?>
                                        </button>
                                        
                                        <form method="post" style="display: inline-block;" 
                                              onsubmit="return confirm('<?php _e('Are you sure you want to delete this leave request?', 'lfcc-leave-management'); ?>');">
                                            <?php wp_nonce_field('lfcc_leave_action', 'lfcc_leave_nonce'); ?>
                                            <input type="hidden" name="action" value="delete_request" />
                                            <input type="hidden" name="request_id" value="<?php echo esc_attr($request->id); ?>" />
                                            <button type="submit" class="button button-small button-link-delete">
                                                <?php _e('Delete', 'lfcc-leave-management'); ?>
                                            </button>
                                        </form>
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

<!-- Approval Modal -->
<div id="lfcc-approve-modal" class="lfcc-modal">
    <div class="lfcc-modal-content">
        <div class="lfcc-modal-header">
            <h3><?php _e('Approve Leave Request', 'lfcc-leave-management'); ?></h3>
            <span class="lfcc-modal-close">&times;</span>
        </div>
        <div class="lfcc-modal-body">
            <p><?php _e('Are you sure you want to approve this leave request?', 'lfcc-leave-management'); ?></p>
            <form method="post" id="approve-form">
                <?php wp_nonce_field('lfcc_leave_action', 'lfcc_leave_nonce'); ?>
                <input type="hidden" name="action" value="approve_request" />
                <input type="hidden" name="request_id" id="approve-request-id" />
            </form>
        </div>
        <div class="lfcc-modal-footer">
            <button type="button" class="button lfcc-modal-close"><?php _e('Cancel', 'lfcc-leave-management'); ?></button>
            <button type="submit" form="approve-form" class="button button-primary"><?php _e('Approve', 'lfcc-leave-management'); ?></button>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="lfcc-reject-modal" class="lfcc-modal">
    <div class="lfcc-modal-content">
        <div class="lfcc-modal-header">
            <h3><?php _e('Reject Leave Request', 'lfcc-leave-management'); ?></h3>
            <span class="lfcc-modal-close">&times;</span>
        </div>
        <div class="lfcc-modal-body">
            <form method="post" id="reject-form">
                <?php wp_nonce_field('lfcc_leave_action', 'lfcc_leave_nonce'); ?>
                <input type="hidden" name="action" value="reject_request" />
                <input type="hidden" name="request_id" id="reject-request-id" />
                
                <div class="form-field">
                    <label for="rejection_reason"><?php _e('Reason for Rejection', 'lfcc-leave-management'); ?> *</label>
                    <textarea name="rejection_reason" id="rejection_reason" rows="4" required 
                              placeholder="<?php _e('Please provide a reason for rejecting this leave request...', 'lfcc-leave-management'); ?>"></textarea>
                </div>
            </form>
        </div>
        <div class="lfcc-modal-footer">
            <button type="button" class="button lfcc-modal-close"><?php _e('Cancel', 'lfcc-leave-management'); ?></button>
            <button type="submit" form="reject-form" class="button button-primary"><?php _e('Reject', 'lfcc-leave-management'); ?></button>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div id="lfcc-details-modal" class="lfcc-modal">
    <div class="lfcc-modal-content">
        <div class="lfcc-modal-header">
            <h3><?php _e('Leave Request Details', 'lfcc-leave-management'); ?></h3>
            <span class="lfcc-modal-close">&times;</span>
        </div>
        <div class="lfcc-modal-body">
            <div id="request-details-content">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
        <div class="lfcc-modal-footer">
            <button type="button" class="button lfcc-modal-close"><?php _e('Close', 'lfcc-leave-management'); ?></button>
        </div>
    </div>
</div>

<style>
.lfcc-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.lfcc-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    border-left: 4px solid #0073aa;
}

.lfcc-stat-card.pending {
    border-left-color: #f57c00;
}

.lfcc-stat-card.approved {
    border-left-color: #2e7d32;
}

.lfcc-stat-card.rejected {
    border-left-color: #d32f2f;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #0073aa;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.lfcc-filters-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.lfcc-filters-row {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-field {
    flex: 1;
    min-width: 150px;
}

.filter-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.filter-field input,
.filter-field select {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.lfcc-requests-table-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.lfcc-table-container {
    overflow-x: auto;
}

.lfcc-leave-type-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.lfcc-type-annual {
    background-color: #e3f2fd;
    color: #1976d2;
}

.lfcc-type-sick {
    background-color: #ffebee;
    color: #d32f2f;
}

.lfcc-type-personal {
    background-color: #f3e5f5;
    color: #7b1fa2;
}

.lfcc-type-emergency {
    background-color: #fff3e0;
    color: #f57c00;
}

.lfcc-date-range {
    font-size: 12px;
}

.date-separator {
    color: #666;
    margin: 2px 0;
}

.lfcc-days-count {
    font-weight: bold;
    color: #0073aa;
    font-size: 16px;
}

.lfcc-reason-text {
    max-width: 200px;
    font-size: 12px;
}

.lfcc-show-full-reason {
    color: #0073aa;
    cursor: pointer;
    text-decoration: underline;
}

.lfcc-status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.lfcc-status-pending {
    background-color: #fff3e0;
    color: #f57c00;
}

.lfcc-status-approved {
    background-color: #e8f5e8;
    color: #2e7d32;
}

.lfcc-status-rejected {
    background-color: #ffebee;
    color: #d32f2f;
}

.lfcc-edited-indicator {
    font-size: 10px;
    color: #666;
    font-style: italic;
    margin-top: 2px;
}

.lfcc-submitted-date {
    font-weight: 600;
}

.lfcc-submitted-time {
    font-size: 11px;
    color: #666;
}

.lfcc-request-actions {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.lfcc-request-actions .button {
    font-size: 11px;
    padding: 4px 8px;
    height: auto;
    line-height: 1.2;
}

/* Modal Styles */
.lfcc-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.lfcc-modal-content {
    background-color: #fff;
    margin: 10% auto;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    width: 90%;
    max-width: 500px;
    display: flex;
    flex-direction: column;
}

.lfcc-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #f9f9f9;
}

.lfcc-modal-header h3 {
    margin: 0;
}

.lfcc-modal-close {
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    color: #666;
}

.lfcc-modal-close:hover {
    color: #000;
}

.lfcc-modal-body {
    padding: 20px;
    flex: 1;
}

.lfcc-modal-body .form-field {
    margin-bottom: 15px;
}

.lfcc-modal-body .form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.lfcc-modal-body .form-field textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 3px;
    resize: vertical;
}

.lfcc-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
    background-color: #f9f9f9;
}

.lfcc-modal-footer .button {
    margin-left: 10px;
}

@media (max-width: 768px) {
    .lfcc-filters-row {
        flex-direction: column;
    }
    
    .filter-field {
        flex: none;
    }
    
    .filter-actions {
        justify-content: center;
    }
    
    .lfcc-stats-cards {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Modal functionality
    $('.lfcc-modal-close').click(function() {
        $(this).closest('.lfcc-modal').hide();
    });
    
    $(window).click(function(event) {
        if ($(event.target).hasClass('lfcc-modal')) {
            $('.lfcc-modal').hide();
        }
    });
    
    // Approve button
    $('.lfcc-approve-btn').click(function() {
        var requestId = $(this).data('request-id');
        $('#approve-request-id').val(requestId);
        $('#lfcc-approve-modal').show();
    });
    
    // Reject button
    $('.lfcc-reject-btn').click(function() {
        var requestId = $(this).data('request-id');
        $('#reject-request-id').val(requestId);
        $('#rejection_reason').val('');
        $('#lfcc-reject-modal').show();
    });
    
    // View details button
    $('.lfcc-view-details-btn').click(function() {
        var requestId = $(this).data('request-id');
        
        // Load request details via AJAX
        $.post(ajaxurl, {
            action: 'lfcc_get_request_details',
            request_id: requestId,
            nonce: '<?php echo wp_create_nonce('lfcc_get_request_details'); ?>'
        }, function(response) {
            if (response.success) {
                $('#request-details-content').html(response.data);
                $('#lfcc-details-modal').show();
            } else {
                alert('<?php _e('Failed to load request details.', 'lfcc-leave-management'); ?>');
            }
        });
    });
    
    // Show full reason
    $('.lfcc-show-full-reason').click(function() {
        var fullReason = $(this).data('full-reason');
        var reasonContainer = $(this).parent();
        
        reasonContainer.html('<div class="full-reason">' + fullReason + '</div>');
    });
    
    // Auto-refresh pending requests (every 30 seconds)
    if ($('.lfcc-status-pending').length > 0) {
        setInterval(function() {
            // Only refresh if we're on the main page (no filters)
            if (window.location.search === '?page=lfcc-leave-requests' || window.location.search === '') {
                location.reload();
            }
        }, 30000);
    }
    
    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Escape to close modals
        if (e.keyCode === 27) {
            $('.lfcc-modal').hide();
        }
    });
});
</script>

