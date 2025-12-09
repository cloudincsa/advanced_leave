<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(LFCC_Leave_Settings::get_option('organization_name', 'LFCC')); ?> - Leave Management</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo LFCC_LEAVE_PLUGIN_URL; ?>assets/images/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo LFCC_LEAVE_PLUGIN_URL; ?>assets/css/frontend.css?v=<?php echo LFCC_LEAVE_VERSION; ?>">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="lfcc-frontend-body">
    <div id="lfcc-app" class="lfcc-app-container">
        <!-- Loading Screen -->
        <div id="lfcc-loading" class="lfcc-loading-screen">
            <div class="lfcc-loading-spinner">
                <div class="spinner"></div>
                <p><?php _e('Loading...', 'lfcc-leave-management'); ?></p>
            </div>
        </div>
        
        <!-- Header -->
        <header class="lfcc-header">
            <div class="lfcc-header-container">
                <div class="lfcc-logo">
                    <?php 
                    $logo_url = LFCC_Leave_Settings::get_option('organization_logo', '');
                    if (!empty($logo_url)): 
                    ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(LFCC_Leave_Settings::get_option('organization_name')); ?>" class="logo-image">
                    <?php else: ?>
                        <div class="logo-text">
                            <h1><?php echo esc_html(LFCC_Leave_Settings::get_option('organization_name', 'LFCC')); ?></h1>
                            <span><?php _e('Leave Management', 'lfcc-leave-management'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <nav class="lfcc-nav" id="main-nav" style="display: none;">
                    <ul class="nav-menu">
                        <li><a href="#dashboard" class="nav-link active" data-page="dashboard">
                            <i class="fas fa-tachometer-alt"></i> <?php _e('Dashboard', 'lfcc-leave-management'); ?>
                        </a></li>
                        <li><a href="#leave-request" class="nav-link" data-page="leave-request">
                            <i class="fas fa-plus-circle"></i> <?php _e('Request Leave', 'lfcc-leave-management'); ?>
                        </a></li>
                        <li><a href="#my-requests" class="nav-link" data-page="my-requests">
                            <i class="fas fa-list"></i> <?php _e('My Requests', 'lfcc-leave-management'); ?>
                        </a></li>
                        <li><a href="#calendar" class="nav-link" data-page="calendar">
                            <i class="fas fa-calendar"></i> <?php _e('Calendar', 'lfcc-leave-management'); ?>
                        </a></li>
                        <li><a href="#profile" class="nav-link" data-page="profile">
                            <i class="fas fa-user"></i> <?php _e('Profile', 'lfcc-leave-management'); ?>
                        </a></li>
                    </ul>
                </nav>
                
                <div class="lfcc-user-menu" id="user-menu" style="display: none;">
                    <div class="user-info">
                        <span class="user-name" id="current-user-name"></span>
                        <span class="user-role" id="current-user-role"></span>
                    </div>
                    <div class="user-actions">
                        <button type="button" class="btn-logout" id="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> <?php _e('Logout', 'lfcc-leave-management'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="lfcc-mobile-menu-toggle" id="mobile-menu-toggle" style="display: none;">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="lfcc-main">
            <!-- Login Page -->
            <div id="login-page" class="lfcc-page active">
                <div class="lfcc-login-container">
                    <div class="lfcc-login-card">
                        <div class="login-header">
                            <h2><?php _e('Welcome Back', 'lfcc-leave-management'); ?></h2>
                            <p><?php _e('Sign in to access your leave management dashboard', 'lfcc-leave-management'); ?></p>
                        </div>
                        
                        <form id="login-form" class="lfcc-form">
                            <div class="form-group">
                                <label for="login-username"><?php _e('Username', 'lfcc-leave-management'); ?></label>
                                <div class="input-group">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="login-username" name="username" required 
                                           placeholder="<?php _e('Enter your username', 'lfcc-leave-management'); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="login-password"><?php _e('Password', 'lfcc-leave-management'); ?></label>
                                <div class="input-group">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" id="login-password" name="password" required 
                                           placeholder="<?php _e('Enter your password', 'lfcc-leave-management'); ?>">
                                    <button type="button" class="password-toggle" data-target="login-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="remember-me" name="remember">
                                    <span class="checkmark"></span>
                                    <?php _e('Remember me', 'lfcc-leave-management'); ?>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-full">
                                <i class="fas fa-sign-in-alt"></i>
                                <?php _e('Sign In', 'lfcc-leave-management'); ?>
                            </button>
                        </form>
                        
                        <div class="login-footer">
                            <?php if (LFCC_Leave_Settings::get_option('enable_user_registration') === 'yes'): ?>
                                <p>
                                    <?php _e('Don\'t have an account?', 'lfcc-leave-management'); ?>
                                    <a href="#register" id="show-register"><?php _e('Register here', 'lfcc-leave-management'); ?></a>
                                </p>
                            <?php endif; ?>
                            <p>
                                <a href="#forgot-password" id="show-forgot-password"><?php _e('Forgot your password?', 'lfcc-leave-management'); ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Registration Page -->
            <?php if (LFCC_Leave_Settings::get_option('enable_user_registration') === 'yes'): ?>
            <div id="register-page" class="lfcc-page">
                <div class="lfcc-login-container">
                    <div class="lfcc-login-card">
                        <div class="login-header">
                            <h2><?php _e('Create Account', 'lfcc-leave-management'); ?></h2>
                            <p><?php _e('Register for access to the leave management system', 'lfcc-leave-management'); ?></p>
                        </div>
                        
                        <form id="register-form" class="lfcc-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="reg-first-name"><?php _e('First Name', 'lfcc-leave-management'); ?> *</label>
                                    <input type="text" id="reg-first-name" name="first_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="reg-last-name"><?php _e('Last Name', 'lfcc-leave-management'); ?> *</label>
                                    <input type="text" id="reg-last-name" name="last_name" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="reg-email"><?php _e('Email Address', 'lfcc-leave-management'); ?> *</label>
                                <input type="email" id="reg-email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="reg-username"><?php _e('Username', 'lfcc-leave-management'); ?> *</label>
                                <input type="text" id="reg-username" name="username" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="reg-password"><?php _e('Password', 'lfcc-leave-management'); ?> *</label>
                                    <div class="input-group">
                                        <input type="password" id="reg-password" name="password" required>
                                        <button type="button" class="password-toggle" data-target="reg-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="reg-confirm-password"><?php _e('Confirm Password', 'lfcc-leave-management'); ?> *</label>
                                    <input type="password" id="reg-confirm-password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="reg-department"><?php _e('Department', 'lfcc-leave-management'); ?></label>
                                <input type="text" id="reg-department" name="department">
                            </div>
                            
                            <div class="form-group">
                                <label for="reg-phone"><?php _e('Phone Number', 'lfcc-leave-management'); ?></label>
                                <input type="tel" id="reg-phone" name="phone">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-full">
                                <i class="fas fa-user-plus"></i>
                                <?php _e('Create Account', 'lfcc-leave-management'); ?>
                            </button>
                        </form>
                        
                        <div class="login-footer">
                            <p>
                                <?php _e('Already have an account?', 'lfcc-leave-management'); ?>
                                <a href="#login" id="show-login"><?php _e('Sign in here', 'lfcc-leave-management'); ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Forgot Password Page -->
            <div id="forgot-password-page" class="lfcc-page">
                <div class="lfcc-login-container">
                    <div class="lfcc-login-card">
                        <div class="login-header">
                            <h2><?php _e('Reset Password', 'lfcc-leave-management'); ?></h2>
                            <p><?php _e('Enter your email address to receive a password reset link', 'lfcc-leave-management'); ?></p>
                        </div>
                        
                        <form id="forgot-password-form" class="lfcc-form">
                            <div class="form-group">
                                <label for="forgot-email"><?php _e('Email Address', 'lfcc-leave-management'); ?></label>
                                <div class="input-group">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" id="forgot-email" name="email" required 
                                           placeholder="<?php _e('Enter your email address', 'lfcc-leave-management'); ?>">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-full">
                                <i class="fas fa-paper-plane"></i>
                                <?php _e('Send Reset Link', 'lfcc-leave-management'); ?>
                            </button>
                        </form>
                        
                        <div class="login-footer">
                            <p>
                                <a href="#login" id="back-to-login"><?php _e('Back to Sign In', 'lfcc-leave-management'); ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Page -->
            <div id="dashboard-page" class="lfcc-page lfcc-dashboard">
                <div class="page-header">
                    <h1><?php _e('Dashboard', 'lfcc-leave-management'); ?></h1>
                    <p class="page-subtitle"><?php _e('Welcome back! Here\'s an overview of your leave status.', 'lfcc-leave-management'); ?></p>
                </div>
                
                <div class="dashboard-grid">
                    <!-- Leave Balance Cards -->
                    <div class="dashboard-section">
                        <h2><?php _e('Leave Balance', 'lfcc-leave-management'); ?></h2>
                        <div class="balance-cards" id="balance-cards">
                            <!-- Cards will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Recent Requests -->
                    <div class="dashboard-section">
                        <h2><?php _e('Recent Requests', 'lfcc-leave-management'); ?></h2>
                        <div class="recent-requests" id="recent-requests">
                            <!-- Requests will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="dashboard-section">
                        <h2><?php _e('Quick Actions', 'lfcc-leave-management'); ?></h2>
                        <div class="quick-actions">
                            <button class="action-btn" data-page="leave-request">
                                <i class="fas fa-plus-circle"></i>
                                <span><?php _e('Request Leave', 'lfcc-leave-management'); ?></span>
                            </button>
                            <button class="action-btn" data-page="calendar">
                                <i class="fas fa-calendar"></i>
                                <span><?php _e('View Calendar', 'lfcc-leave-management'); ?></span>
                            </button>
                            <button class="action-btn" data-page="my-requests">
                                <i class="fas fa-list"></i>
                                <span><?php _e('My Requests', 'lfcc-leave-management'); ?></span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Upcoming Leave -->
                    <div class="dashboard-section">
                        <h2><?php _e('Upcoming Leave', 'lfcc-leave-management'); ?></h2>
                        <div class="upcoming-leave" id="upcoming-leave">
                            <!-- Upcoming leave will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Leave Request Page -->
            <div id="leave-request-page" class="lfcc-page">
                <div class="page-header">
                    <h1><?php _e('Request Leave', 'lfcc-leave-management'); ?></h1>
                    <p class="page-subtitle"><?php _e('Submit a new leave request for approval.', 'lfcc-leave-management'); ?></p>
                </div>
                
                <div class="page-content">
                    <form id="leave-request-form" class="lfcc-form leave-form">
                        <div class="form-section">
                            <h3><?php _e('Leave Details', 'lfcc-leave-management'); ?></h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="leave-type"><?php _e('Leave Type', 'lfcc-leave-management'); ?> *</label>
                                    <select id="leave-type" name="leave_type" required>
                                        <option value=""><?php _e('Select leave type', 'lfcc-leave-management'); ?></option>
                                        <option value="annual"><?php _e('Annual Leave', 'lfcc-leave-management'); ?></option>
                                        <option value="sick"><?php _e('Sick Leave', 'lfcc-leave-management'); ?></option>
                                        <option value="personal"><?php _e('Personal Leave', 'lfcc-leave-management'); ?></option>
                                        <option value="emergency"><?php _e('Emergency Leave', 'lfcc-leave-management'); ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Available Days', 'lfcc-leave-management'); ?></label>
                                    <div class="available-days" id="available-days">
                                        <span class="days-count">-</span>
                                        <span class="days-label"><?php _e('days remaining', 'lfcc-leave-management'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="start-date"><?php _e('Start Date', 'lfcc-leave-management'); ?> *</label>
                                    <input type="date" id="start-date" name="start_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="end-date"><?php _e('End Date', 'lfcc-leave-management'); ?> *</label>
                                    <input type="date" id="end-date" name="end_date" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label><?php _e('Total Days', 'lfcc-leave-management'); ?></label>
                                <div class="total-days-display">
                                    <span id="total-days-count">0</span>
                                    <span><?php _e('days', 'lfcc-leave-management'); ?></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="leave-reason"><?php _e('Reason for Leave', 'lfcc-leave-management'); ?></label>
                                <textarea id="leave-reason" name="reason" rows="4" 
                                          placeholder="<?php _e('Please provide a brief reason for your leave request...', 'lfcc-leave-management'); ?>"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                <?php _e('Submit Request', 'lfcc-leave-management'); ?>
                            </button>
                            <button type="button" class="btn btn-secondary" data-page="dashboard">
                                <i class="fas fa-times"></i>
                                <?php _e('Cancel', 'lfcc-leave-management'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- My Requests Page -->
            <div id="my-requests-page" class="lfcc-page">
                <div class="page-header">
                    <h1><?php _e('My Leave Requests', 'lfcc-leave-management'); ?></h1>
                    <p class="page-subtitle"><?php _e('View and manage your leave request history.', 'lfcc-leave-management'); ?></p>
                </div>
                
                <div class="page-content">
                    <div class="requests-filters">
                        <div class="filter-group">
                            <label for="requests-status-filter"><?php _e('Status', 'lfcc-leave-management'); ?></label>
                            <select id="requests-status-filter">
                                <option value="all"><?php _e('All Statuses', 'lfcc-leave-management'); ?></option>
                                <option value="pending"><?php _e('Pending', 'lfcc-leave-management'); ?></option>
                                <option value="approved"><?php _e('Approved', 'lfcc-leave-management'); ?></option>
                                <option value="rejected"><?php _e('Rejected', 'lfcc-leave-management'); ?></option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="requests-type-filter"><?php _e('Type', 'lfcc-leave-management'); ?></label>
                            <select id="requests-type-filter">
                                <option value="all"><?php _e('All Types', 'lfcc-leave-management'); ?></option>
                                <option value="annual"><?php _e('Annual', 'lfcc-leave-management'); ?></option>
                                <option value="sick"><?php _e('Sick', 'lfcc-leave-management'); ?></option>
                                <option value="personal"><?php _e('Personal', 'lfcc-leave-management'); ?></option>
                                <option value="emergency"><?php _e('Emergency', 'lfcc-leave-management'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="requests-list" id="requests-list">
                        <!-- Requests will be populated by JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Calendar Page -->
            <div id="calendar-page" class="lfcc-page">
                <div class="page-header">
                    <h1><?php _e('Leave Calendar', 'lfcc-leave-management'); ?></h1>
                    <p class="page-subtitle"><?php _e('View team leave schedule to plan your time off.', 'lfcc-leave-management'); ?></p>
                </div>
                
                <div class="page-content">
                    <div class="calendar-controls">
                        <button type="button" class="btn btn-secondary" id="prev-month">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h2 id="calendar-title"></h2>
                        <button type="button" class="btn btn-secondary" id="next-month">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    
                    <div class="calendar-container">
                        <div id="leave-calendar" class="leave-calendar">
                            <!-- Calendar will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <div class="calendar-legend">
                        <div class="legend-item">
                            <span class="legend-color annual"></span>
                            <span><?php _e('Annual Leave', 'lfcc-leave-management'); ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color sick"></span>
                            <span><?php _e('Sick Leave', 'lfcc-leave-management'); ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color personal"></span>
                            <span><?php _e('Personal Leave', 'lfcc-leave-management'); ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color emergency"></span>
                            <span><?php _e('Emergency Leave', 'lfcc-leave-management'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Page -->
            <div id="profile-page" class="lfcc-page">
                <div class="page-header">
                    <h1><?php _e('My Profile', 'lfcc-leave-management'); ?></h1>
                    <p class="page-subtitle"><?php _e('Manage your personal information and account settings.', 'lfcc-leave-management'); ?></p>
                </div>
                
                <div class="page-content">
                    <form id="profile-form" class="lfcc-form profile-form">
                        <div class="form-section">
                            <h3><?php _e('Personal Information', 'lfcc-leave-management'); ?></h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="profile-first-name"><?php _e('First Name', 'lfcc-leave-management'); ?></label>
                                    <input type="text" id="profile-first-name" name="first_name">
                                </div>
                                <div class="form-group">
                                    <label for="profile-last-name"><?php _e('Last Name', 'lfcc-leave-management'); ?></label>
                                    <input type="text" id="profile-last-name" name="last_name">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="profile-email"><?php _e('Email Address', 'lfcc-leave-management'); ?></label>
                                    <input type="email" id="profile-email" name="email">
                                </div>
                                <div class="form-group">
                                    <label for="profile-phone"><?php _e('Phone Number', 'lfcc-leave-management'); ?></label>
                                    <input type="tel" id="profile-phone" name="phone">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="profile-address"><?php _e('Address', 'lfcc-leave-management'); ?></label>
                                <textarea id="profile-address" name="address" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3><?php _e('Change Password', 'lfcc-leave-management'); ?></h3>
                            
                            <div class="form-group">
                                <label for="current-password"><?php _e('Current Password', 'lfcc-leave-management'); ?></label>
                                <div class="input-group">
                                    <input type="password" id="current-password" name="current_password">
                                    <button type="button" class="password-toggle" data-target="current-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new-password"><?php _e('New Password', 'lfcc-leave-management'); ?></label>
                                    <div class="input-group">
                                        <input type="password" id="new-password" name="new_password">
                                        <button type="button" class="password-toggle" data-target="new-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="confirm-new-password"><?php _e('Confirm New Password', 'lfcc-leave-management'); ?></label>
                                    <input type="password" id="confirm-new-password" name="confirm_new_password">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                <?php _e('Save Changes', 'lfcc-leave-management'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="lfcc-footer">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html(LFCC_Leave_Settings::get_option('organization_name', 'LFCC')); ?>. <?php _e('All rights reserved.', 'lfcc-leave-management'); ?></p>
                <p><?php _e('Leave Management System', 'lfcc-leave-management'); ?> v<?php echo LFCC_LEAVE_VERSION; ?></p>
            </div>
        </footer>
    </div>
    
    <!-- Notification Container -->
    <div id="notification-container" class="notification-container"></div>
    
    <!-- Modals -->
    <div id="edit-request-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php _e('Edit Leave Request', 'lfcc-leave-management'); ?></h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="edit-request-form" class="lfcc-form">
                    <!-- Form content will be populated by JavaScript -->
                </form>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="<?php echo LFCC_LEAVE_PLUGIN_URL; ?>assets/js/frontend.js?v=<?php echo LFCC_LEAVE_VERSION; ?>"></script>
    
    <script>
        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            window.LFCCLeaveApp = new LFCCLeaveManagement({
                ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('lfcc_leave_frontend_nonce'); ?>',
                strings: {
                    loading: '<?php _e('Loading...', 'lfcc-leave-management'); ?>',
                    error: '<?php _e('An error occurred. Please try again.', 'lfcc-leave-management'); ?>',
                    success: '<?php _e('Success!', 'lfcc-leave-management'); ?>',
                    confirm: '<?php _e('Are you sure?', 'lfcc-leave-management'); ?>',
                    loginRequired: '<?php _e('Please log in to continue.', 'lfcc-leave-management'); ?>',
                    sessionExpired: '<?php _e('Your session has expired. Please log in again.', 'lfcc-leave-management'); ?>',
                    invalidCredentials: '<?php _e('Invalid username or password.', 'lfcc-leave-management'); ?>',
                    requestSubmitted: '<?php _e('Leave request submitted successfully!', 'lfcc-leave-management'); ?>',
                    profileUpdated: '<?php _e('Profile updated successfully!', 'lfcc-leave-management'); ?>',
                    passwordChanged: '<?php _e('Password changed successfully!', 'lfcc-leave-management'); ?>',
                    requestUpdated: '<?php _e('Leave request updated successfully!', 'lfcc-leave-management'); ?>',
                    requestDeleted: '<?php _e('Leave request deleted successfully!', 'lfcc-leave-management'); ?>'
                },
                settings: {
                    dateFormat: '<?php echo LFCC_Leave_Settings::get_option('date_format', 'Y-m-d'); ?>',
                    timeFormat: '<?php echo LFCC_Leave_Settings::get_option('time_format', 'H:i'); ?>',
                    weekendCountsAsLeave: <?php echo LFCC_Leave_Settings::get_option('weekend_counts_as_leave', 'yes') === 'yes' ? 'true' : 'false'; ?>,
                    allowLeaveEditing: <?php echo LFCC_Leave_Settings::get_option('allow_leave_editing', 'yes') === 'yes' ? 'true' : 'false'; ?>,
                    requireReapprovalOnEdit: <?php echo LFCC_Leave_Settings::get_option('require_reapproval_on_edit', 'yes') === 'yes' ? 'true' : 'false'; ?>
                }
            });
        });
    </script>
</body>
</html>

