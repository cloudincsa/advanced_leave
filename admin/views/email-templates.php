<?php
/**
 * Email Templates Management Page
 * WYSIWYG editor for customizing email templates with variables
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['save_template']) && wp_verify_nonce($_POST['lfcc_email_template_nonce'], 'lfcc_email_template_save')) {
    $template_id = sanitize_text_field($_POST['template_id']);
    $template_subject = sanitize_text_field($_POST['template_subject']);
    $template_content = wp_kses_post($_POST['template_content']);
    
    LFCC_Leave_Settings::update_option('email_template_' . $template_id . '_subject', $template_subject);
    LFCC_Leave_Settings::update_option('email_template_' . $template_id, $template_content);
    
    echo '<div class="notice notice-success"><p>' . __('Email template saved successfully!', 'lfcc-leave-management') . '</p></div>';
}

// Handle preview request
if (isset($_POST['preview_template']) && wp_verify_nonce($_POST['lfcc_email_preview_nonce'], 'lfcc_email_preview')) {
    $template_id = sanitize_text_field($_POST['preview_template_id']);
    $email_handler = LFCC_Leave_Email_Handler::get_instance();
    $preview = $email_handler->preview_email_template($template_id);
}

// Get available templates
$available_templates = array(
    'welcome' => __('Welcome Email', 'lfcc-leave-management'),
    'leave_request_notification' => __('Leave Request Notification (to HR)', 'lfcc-leave-management'),
    'leave_approved' => __('Leave Request Approved', 'lfcc-leave-management'),
    'leave_rejected' => __('Leave Request Rejected', 'lfcc-leave-management'),
    'password_reset' => __('Password Reset', 'lfcc-leave-management')
);

// Get current template
$current_template = isset($_GET['template']) ? sanitize_text_field($_GET['template']) : 'welcome';
if (!array_key_exists($current_template, $available_templates)) {
    $current_template = 'welcome';
}

// Get template content
$template_subject = LFCC_Leave_Settings::get_option('email_template_' . $current_template . '_subject', '');
$template_content = LFCC_Leave_Settings::get_option('email_template_' . $current_template, '');

// Get template variables
$template_variables = LFCC_Leave_Settings::get_email_template_variables();
?>

<div class="wrap">
    <h1><?php _e('Email Templates', 'lfcc-leave-management'); ?></h1>
    
    <div class="lfcc-email-templates-container">
        <!-- Template Selection -->
        <div class="lfcc-template-selector">
            <h2><?php _e('Select Template', 'lfcc-leave-management'); ?></h2>
            <ul class="lfcc-template-list">
                <?php foreach ($available_templates as $template_id => $template_name): ?>
                    <li>
                        <a href="<?php echo admin_url('admin.php?page=lfcc-leave-email-templates&template=' . $template_id); ?>" 
                           class="<?php echo $current_template === $template_id ? 'active' : ''; ?>">
                            <?php echo esc_html($template_name); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Template Editor -->
        <div class="lfcc-template-editor">
            <h2><?php echo esc_html($available_templates[$current_template]); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('lfcc_email_template_save', 'lfcc_email_template_nonce'); ?>
                <input type="hidden" name="template_id" value="<?php echo esc_attr($current_template); ?>" />
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Subject Line', 'lfcc-leave-management'); ?></th>
                        <td>
                            <input type="text" name="template_subject" value="<?php echo esc_attr($template_subject); ?>" class="large-text" />
                            <p class="description"><?php _e('Email subject line (variables can be used)', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Email Content', 'lfcc-leave-management'); ?></th>
                        <td>
                            <?php
                            wp_editor($template_content, 'template_content', array(
                                'textarea_name' => 'template_content',
                                'media_buttons' => true,
                                'textarea_rows' => 20,
                                'teeny' => false,
                                'dfw' => false,
                                'tinymce' => array(
                                    'resize' => false,
                                    'wordpress_adv_hidden' => false,
                                    'add_unload_trigger' => false,
                                    'relative_urls' => false,
                                    'remove_script_host' => false,
                                    'convert_urls' => false,
                                ),
                                'quicktags' => array(
                                    'buttons' => 'em,strong,link,block,del,ins,img,ul,ol,li,code,more,close'
                                )
                            ));
                            ?>
                            <p class="description"><?php _e('Use the variables from the sidebar to personalize your emails', 'lfcc-leave-management'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <div class="lfcc-template-actions">
                    <?php submit_button(__('Save Template', 'lfcc-leave-management'), 'primary', 'save_template', false); ?>
                    
                    <form method="post" action="" style="display: inline-block; margin-left: 10px;">
                        <?php wp_nonce_field('lfcc_email_preview', 'lfcc_email_preview_nonce'); ?>
                        <input type="hidden" name="preview_template_id" value="<?php echo esc_attr($current_template); ?>" />
                        <?php submit_button(__('Preview Template', 'lfcc-leave-management'), 'secondary', 'preview_template', false); ?>
                    </form>
                </div>
            </form>
        </div>
        
        <!-- Variables Sidebar -->
        <div class="lfcc-variables-sidebar">
            <h3><?php _e('Available Variables', 'lfcc-leave-management'); ?></h3>
            <p class="description"><?php _e('Click on any variable to insert it into your template', 'lfcc-leave-management'); ?></p>
            
            <?php foreach ($template_variables as $category => $variables): ?>
                <div class="lfcc-variable-category">
                    <h4><?php echo esc_html(ucwords(str_replace('_', ' ', $category))); ?></h4>
                    <div class="lfcc-variable-list">
                        <?php foreach ($variables as $variable => $description): ?>
                            <div class="lfcc-variable-item" data-variable="<?php echo esc_attr($variable); ?>">
                                <code><?php echo esc_html($variable); ?></code>
                                <span class="description"><?php echo esc_html($description); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="lfcc-template-tips">
                <h4><?php _e('Template Tips', 'lfcc-leave-management'); ?></h4>
                <ul>
                    <li><?php _e('Use HTML for formatting and styling', 'lfcc-leave-management'); ?></li>
                    <li><?php _e('Variables are case-sensitive', 'lfcc-leave-management'); ?></li>
                    <li><?php _e('Test your templates with the preview function', 'lfcc-leave-management'); ?></li>
                    <li><?php _e('Keep mobile devices in mind when designing', 'lfcc-leave-management'); ?></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <?php if (isset($preview) && $preview): ?>
        <div id="lfcc-preview-modal" class="lfcc-modal">
            <div class="lfcc-modal-content">
                <div class="lfcc-modal-header">
                    <h3><?php _e('Email Preview', 'lfcc-leave-management'); ?></h3>
                    <span class="lfcc-modal-close">&times;</span>
                </div>
                <div class="lfcc-modal-body">
                    <div class="lfcc-preview-subject">
                        <strong><?php _e('Subject:', 'lfcc-leave-management'); ?></strong> <?php echo esc_html($preview['subject']); ?>
                    </div>
                    <div class="lfcc-preview-content">
                        <iframe srcdoc="<?php echo esc_attr($preview['content']); ?>" style="width: 100%; height: 500px; border: 1px solid #ddd;"></iframe>
                    </div>
                </div>
                <div class="lfcc-modal-footer">
                    <button type="button" class="button lfcc-modal-close"><?php _e('Close', 'lfcc-leave-management'); ?></button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.lfcc-email-templates-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.lfcc-template-selector {
    flex: 0 0 200px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 15px;
    height: fit-content;
}

.lfcc-template-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.lfcc-template-list li {
    margin: 0 0 5px 0;
}

.lfcc-template-list a {
    display: block;
    padding: 8px 12px;
    text-decoration: none;
    border-radius: 3px;
    transition: background-color 0.2s;
}

.lfcc-template-list a:hover {
    background-color: #f0f0f1;
}

.lfcc-template-list a.active {
    background-color: #0073aa;
    color: #fff;
}

.lfcc-template-editor {
    flex: 1;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.lfcc-template-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.lfcc-variables-sidebar {
    flex: 0 0 300px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 15px;
    height: fit-content;
    max-height: 80vh;
    overflow-y: auto;
}

.lfcc-variable-category {
    margin-bottom: 20px;
}

.lfcc-variable-category h4 {
    margin: 0 0 10px 0;
    padding-bottom: 5px;
    border-bottom: 1px solid #ddd;
    color: #0073aa;
}

.lfcc-variable-item {
    margin-bottom: 8px;
    padding: 5px;
    border-radius: 3px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.lfcc-variable-item:hover {
    background-color: #f0f0f1;
}

.lfcc-variable-item code {
    display: block;
    font-weight: bold;
    color: #d63384;
}

.lfcc-variable-item .description {
    font-size: 11px;
    color: #666;
}

.lfcc-template-tips {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
}

.lfcc-template-tips h4 {
    margin: 0 0 10px 0;
    color: #0073aa;
}

.lfcc-template-tips ul {
    margin: 0;
    padding-left: 20px;
}

.lfcc-template-tips li {
    font-size: 12px;
    margin-bottom: 5px;
    color: #666;
}

/* Modal Styles */
.lfcc-modal {
    display: <?php echo isset($preview) && $preview ? 'block' : 'none'; ?>;
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
    margin: 5% auto;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    width: 80%;
    max-width: 800px;
    max-height: 90vh;
    overflow: hidden;
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
    overflow-y: auto;
}

.lfcc-preview-subject {
    margin-bottom: 15px;
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 3px;
}

.lfcc-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
    background-color: #f9f9f9;
}

@media (max-width: 1200px) {
    .lfcc-email-templates-container {
        flex-direction: column;
    }
    
    .lfcc-template-selector,
    .lfcc-variables-sidebar {
        flex: none;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Variable insertion
    $('.lfcc-variable-item').click(function() {
        var variable = $(this).data('variable');
        
        // Insert into TinyMCE if active
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('template_content') && !tinyMCE.get('template_content').isHidden()) {
            tinyMCE.get('template_content').execCommand('mceInsertContent', false, variable);
        } else {
            // Insert into textarea
            var textarea = $('#template_content');
            var cursorPos = textarea.prop('selectionStart');
            var textBefore = textarea.val().substring(0, cursorPos);
            var textAfter = textarea.val().substring(cursorPos);
            textarea.val(textBefore + variable + textAfter);
            
            // Set cursor position after inserted variable
            textarea.prop('selectionStart', cursorPos + variable.length);
            textarea.prop('selectionEnd', cursorPos + variable.length);
            textarea.focus();
        }
    });
    
    // Modal functionality
    $('.lfcc-modal-close').click(function() {
        $('#lfcc-preview-modal').hide();
    });
    
    $(window).click(function(event) {
        if (event.target.id === 'lfcc-preview-modal') {
            $('#lfcc-preview-modal').hide();
        }
    });
    
    // Auto-save functionality (optional)
    var autoSaveTimer;
    $('#template_content, input[name="template_subject"]').on('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Could implement auto-save here
            console.log('Auto-save triggered');
        }, 5000);
    });
    
    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl+S to save
        if (e.ctrlKey && e.keyCode === 83) {
            e.preventDefault();
            $('input[name="save_template"]').click();
        }
        
        // Escape to close modal
        if (e.keyCode === 27) {
            $('#lfcc-preview-modal').hide();
        }
    });
    
    // Template switching confirmation
    $('.lfcc-template-list a').click(function(e) {
        if ($(this).hasClass('active')) {
            e.preventDefault();
            return false;
        }
        
        // Check if content has been modified
        var originalContent = '<?php echo esc_js($template_content); ?>';
        var currentContent = '';
        
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('template_content') && !tinyMCE.get('template_content').isHidden()) {
            currentContent = tinyMCE.get('template_content').getContent();
        } else {
            currentContent = $('#template_content').val();
        }
        
        if (originalContent !== currentContent) {
            if (!confirm('<?php _e('You have unsaved changes. Are you sure you want to switch templates?', 'lfcc-leave-management'); ?>')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>

