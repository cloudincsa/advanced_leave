/**
 * LFCC Leave Management Admin JavaScript
 * Handles admin interface interactions
 */

jQuery(document).ready(function($) {
    
    // Tab switching functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs and content
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        
        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');
        
        // Show corresponding content
        var target = $(this).attr('href');
        $(target).addClass('active');
    });
    
    // SMTP settings toggle
    $('#smtp_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#smtp_settings').show();
        } else {
            $('#smtp_settings').hide();
        }
    });
    
    // Subdomain name preview update
    $('input[name="subdomain_name"]').on('input', function() {
        var subdomainName = $(this).val();
        var currentHost = window.location.hostname.replace(/^www\./, '');
        var preview = subdomainName + '.' + currentHost;
        $('#subdomain-preview').text(preview);
    });
    
    // Form submission handling
    $('form').on('submit', function(e) {
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        
        // Show loading state
        $submitButton.prop('disabled', true);
        $submitButton.val(lfcc_leave_ajax.strings.saving);
        
        // Re-enable button after a delay (form will submit normally)
        setTimeout(function() {
            $submitButton.prop('disabled', false);
            $submitButton.val('Save Changes');
        }, 2000);
    });
    
    // Confirmation dialogs for delete actions
    $('.delete-action').on('click', function(e) {
        if (!confirm(lfcc_leave_ajax.strings.confirm_delete)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Test email functionality
    $('#test-email-btn').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var $emailInput = $('#test_email_address');
        var email = $emailInput.val();
        
        if (!email) {
            alert('Please enter an email address');
            return;
        }
        
        $btn.prop('disabled', true);
        $btn.text('Sending...');
        
        $.ajax({
            url: lfcc_leave_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lfcc_leave_ajax',
                lfcc_action: 'test_email',
                email: email,
                nonce: lfcc_leave_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Test email sent successfully!');
                } else {
                    alert('Failed to send test email: ' + response.data);
                }
            },
            error: function() {
                alert('Error occurred while sending test email');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.text('Send Test Email');
            }
        });
    });
    
    // Auto-save functionality for settings (optional)
    var saveTimeout;
    $('.auto-save').on('change', function() {
        clearTimeout(saveTimeout);
        var $field = $(this);
        
        saveTimeout = setTimeout(function() {
            // Auto-save individual setting
            $.ajax({
                url: lfcc_leave_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'lfcc_leave_ajax',
                    lfcc_action: 'auto_save_setting',
                    setting_name: $field.attr('name'),
                    setting_value: $field.val(),
                    nonce: lfcc_leave_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show brief success indicator
                        $field.addClass('saved');
                        setTimeout(function() {
                            $field.removeClass('saved');
                        }, 1000);
                    }
                }
            });
        }, 1000);
    });
    
    // Initialize tooltips if available
    if (typeof $.fn.tooltip !== 'undefined') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Character counter for text areas
    $('textarea[maxlength]').each(function() {
        var $textarea = $(this);
        var maxLength = $textarea.attr('maxlength');
        var $counter = $('<div class="char-counter"></div>');
        $textarea.after($counter);
        
        function updateCounter() {
            var remaining = maxLength - $textarea.val().length;
            $counter.text(remaining + ' characters remaining');
            
            if (remaining < 50) {
                $counter.addClass('warning');
            } else {
                $counter.removeClass('warning');
            }
        }
        
        $textarea.on('input', updateCounter);
        updateCounter();
    });
    
    // Validate email fields
    $('input[type="email"]').on('blur', function() {
        var $input = $(this);
        var email = $input.val();
        
        if (email && !isValidEmail(email)) {
            $input.addClass('error');
            if (!$input.next('.error-message').length) {
                $input.after('<span class="error-message">Please enter a valid email address</span>');
            }
        } else {
            $input.removeClass('error');
            $input.next('.error-message').remove();
        }
    });
    
    // Email validation helper
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Initialize the first tab as active if none is active
    if (!$('.nav-tab-active').length) {
        $('.nav-tab').first().addClass('nav-tab-active');
        $('.tab-content').first().addClass('active');
    }
    
});

