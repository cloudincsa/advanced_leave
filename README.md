# LFCC Leave Management WordPress Plugin

A comprehensive WordPress plugin that provides a complete staff leave management system with admin backend, customizable email templates, subdomain functionality, and a professional frontend interface.

## 🌟 Features

### **Core Leave Management**
- ✅ Professional login interface with company branding
- ✅ Role-based access control (Staff, HR, Admin)
- ✅ Leave request submission and approval workflow
- ✅ Leave calendar overview with visual scheduling
- ✅ Leave balance tracking with progress indicators

### **Advanced User Management**
- ✅ Complete user creation and editing interface
- ✅ Individual leave day allocation per user
- ✅ Department and role assignment
- ✅ User status management (active/inactive)
- ✅ Bulk user operations and CSV export

### **Email & Communication**
- ✅ WYSIWYG email template editor with variables
- ✅ Automated welcome emails for new users
- ✅ Password reset functionality
- ✅ HR email notifications for new requests
- ✅ Customizable email templates with 15+ variables

### **Leave Request Features**
- ✅ **Edit approved requests** - Users can modify approved leave
- ✅ **Automatic re-approval** - Edited requests reset to pending
- ✅ **Professional edit interface** - Clean modal with validation
- ✅ **Balance validation** - System validates against available leave
- ✅ **Weekend inclusion** - Configurable weekend counting

### **Subdomain Functionality**
- ✅ **Custom subdomain access** (e.g., leave.yourcompany.com)
- ✅ **Automatic subdomain detection and routing**
- ✅ **Separate frontend interface** for staff access
- ✅ **WordPress admin remains on main domain**

### **Comprehensive Reporting**
- ✅ Excel/CSV export for requests, users, and balances
- ✅ Dashboard with statistics and analytics
- ✅ Leave usage reports and trends
- ✅ Automated weekly/monthly reports

## 📋 Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **Server:** Apache or Nginx with mod_rewrite
- **SSL Certificate:** Required for subdomain functionality
- **Email:** SMTP server for email notifications

## 🚀 Installation

### Step 1: Upload Plugin Files

1. **Download** the plugin ZIP file
2. **Extract** the contents to your WordPress plugins directory:
   ```
   /wp-content/plugins/lfcc-leave-management/
   ```
3. **Set permissions** (if needed):
   ```bash
   chmod -R 755 /wp-content/plugins/lfcc-leave-management/
   ```

### Step 2: Activate Plugin

1. Go to **WordPress Admin** → **Plugins**
2. Find **"LFCC Leave Management"**
3. Click **"Activate"**

### Step 3: Initial Configuration

1. Go to **Leave Management** → **Settings**
2. Configure basic settings:
   - Organization name
   - Upload company logo
   - Set default leave allocations
   - Configure email settings

### Step 4: Subdomain Setup

#### Option A: DNS Configuration (Recommended)
1. **Create DNS Record:**
   - Type: CNAME
   - Name: leave (or your preferred subdomain)
   - Value: yourdomain.com
   - TTL: 300

2. **Configure in Plugin:**
   - Go to **Leave Management** → **Settings** → **Subdomain**
   - Enter: `leave.yourdomain.com`
   - Save settings

#### Option B: Server Configuration
If you have server access, add this to your Apache virtual host:
```apache
ServerAlias leave.yourdomain.com
```

Or for Nginx:
```nginx
server_name yourdomain.com leave.yourdomain.com;
```

### Step 5: Email Configuration

1. **SMTP Settings:**
   - Go to **Leave Management** → **Settings** → **Email**
   - Configure SMTP server details
   - Test email functionality

2. **Email Templates:**
   - Go to **Leave Management** → **Email Templates**
   - Customize templates using the WYSIWYG editor
   - Use variables like `{{first_name}}`, `{{leave_balance}}`, etc.

### Step 6: Create Users

1. **Admin Users:**
   - Go to **Leave Management** → **Users**
   - Create HR and Admin accounts
   - Set appropriate leave allocations

2. **Staff Registration:**
   - Enable user registration in settings (optional)
   - Or create staff accounts manually

## ⚙️ Configuration Options

### General Settings
- **Organization Name:** Your company name
- **Company Logo:** Upload your logo (recommended: 200x60px)
- **Date Format:** Choose date display format
- **Time Zone:** Set your organization's timezone

### Leave Settings
- **Default Annual Leave:** Days per year (default: 20)
- **Default Sick Leave:** Days per year (default: 10)
- **Default Personal Leave:** Days per year (default: 5)
- **Default Emergency Leave:** Days per year (default: 3)
- **Weekend Counting:** Include weekends in leave calculations
- **Allow Leave Editing:** Let users edit approved requests
- **Require Re-approval:** Reset status when edited

### Email Settings
- **SMTP Host:** Your email server
- **SMTP Port:** Usually 587 or 465
- **SMTP Username:** Your email account
- **SMTP Password:** Your email password
- **From Name:** Sender name for emails
- **From Email:** Sender email address

### Subdomain Settings
- **Subdomain URL:** Full subdomain URL (e.g., leave.company.com)
- **Enable Subdomain:** Toggle subdomain functionality
- **Redirect Main Domain:** Redirect main domain users to subdomain

## 🎨 Email Template Variables

Use these variables in your email templates:

### User Information
- `{{first_name}}` - User's first name
- `{{last_name}}` - User's last name
- `{{full_name}}` - User's full name
- `{{email}}` - User's email address
- `{{username}}` - User's username
- `{{department}}` - User's department
- `{{phone}}` - User's phone number

### Leave Information
- `{{leave_type}}` - Type of leave requested
- `{{start_date}}` - Leave start date
- `{{end_date}}` - Leave end date
- `{{total_days}}` - Total days requested
- `{{reason}}` - Reason for leave

### Leave Balance
- `{{leave_balance}}` - Remaining leave balance
- `{{annual_leave}}` - Annual leave allocation
- `{{annual_leave_used}}` - Annual leave used
- `{{sick_leave}}` - Sick leave allocation
- `{{personal_leave}}` - Personal leave allocation
- `{{emergency_leave}}` - Emergency leave allocation

### Organization
- `{{organization_name}}` - Organization name
- `{{admin_email}}` - Admin email address
- `{{login_url}}` - Frontend login URL
- `{{website_url}}` - Main website URL

## 🔧 Troubleshooting

### Common Issues

#### Subdomain Not Working
1. **Check DNS:** Verify CNAME record is properly configured
2. **Clear Cache:** Clear any caching plugins
3. **SSL Certificate:** Ensure SSL covers the subdomain
4. **Plugin Settings:** Verify subdomain URL in settings

#### Email Not Sending
1. **SMTP Settings:** Verify server details are correct
2. **Authentication:** Check username/password
3. **Port/Security:** Try different ports (587, 465, 25)
4. **Firewall:** Ensure SMTP ports aren't blocked

#### Database Errors
1. **Permissions:** Check database user permissions
2. **Plugin Reactivation:** Deactivate and reactivate plugin
3. **Manual Installation:** Run database setup manually

#### Login Issues
1. **Session Cookies:** Check if cookies are enabled
2. **SSL Issues:** Ensure consistent HTTP/HTTPS usage
3. **Password Reset:** Use forgot password functionality

### Debug Mode

Enable WordPress debug mode to troubleshoot issues:
```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check debug logs at: `/wp-content/debug.log`

## 📊 Usage Guide

### For Administrators

#### Managing Users
1. **Create Users:** Leave Management → Users → Add New
2. **Set Allocations:** Configure individual leave entitlements
3. **Bulk Operations:** Export user data, reset passwords
4. **User Status:** Activate/deactivate accounts

#### Managing Leave Requests
1. **View Requests:** Leave Management → Leave Requests
2. **Approve/Reject:** Use bulk actions or individual controls
3. **Add Comments:** Provide feedback on rejections
4. **Export Data:** Generate reports for payroll/HR

#### Email Templates
1. **Customize Templates:** Leave Management → Email Templates
2. **Use Variables:** Personalize with user/leave data
3. **Preview Templates:** Test before saving
4. **Send Test Emails:** Verify formatting and delivery

### For Staff Users

#### Accessing the System
1. **Visit Subdomain:** Go to leave.yourcompany.com
2. **Login:** Use provided credentials
3. **Dashboard:** View leave balance and recent requests

#### Requesting Leave
1. **Request Leave:** Click "Request Leave" button
2. **Select Dates:** Choose start and end dates
3. **Choose Type:** Annual, sick, personal, or emergency
4. **Add Reason:** Provide brief explanation
5. **Submit:** Request goes to HR for approval

#### Managing Requests
1. **View Requests:** Check status of all requests
2. **Edit Requests:** Modify approved requests (if enabled)
3. **Cancel Requests:** Delete pending requests
4. **Track Balance:** Monitor remaining leave days

## 🔒 Security Features

- **Secure Authentication:** Password hashing with PHP password_hash()
- **Session Management:** Secure session tokens with expiration
- **CSRF Protection:** WordPress nonces for all forms
- **SQL Injection Prevention:** Prepared statements and sanitization
- **XSS Protection:** Input sanitization and output escaping
- **Role-Based Access:** Proper capability checks
- **Secure Cookies:** HTTPOnly and Secure flags

## 🆘 Support

### Documentation
- **Plugin Settings:** Detailed help text in admin interface
- **Email Templates:** Built-in variable reference
- **User Guide:** Comprehensive usage instructions

### Getting Help
1. **Check Settings:** Verify all configuration options
2. **Review Logs:** Check WordPress debug logs
3. **Test Environment:** Try on staging site first
4. **Plugin Conflicts:** Deactivate other plugins to test

### Common Solutions
- **Clear Cache:** After any configuration changes
- **Update DNS:** Allow 24-48 hours for propagation
- **Check Permissions:** Ensure proper file/folder permissions
- **Verify SSL:** Subdomain must have valid SSL certificate

## 📈 Performance Optimization

### Recommended Settings
- **Caching:** Use caching plugins but exclude subdomain
- **CDN:** Configure CDN to handle subdomain properly
- **Database:** Regular optimization and cleanup
- **Images:** Optimize logo and any uploaded images

### Server Requirements
- **Memory:** Minimum 256MB PHP memory limit
- **Execution Time:** 60 seconds for large operations
- **Upload Size:** 10MB for logo uploads
- **Database:** Regular backups recommended

## 🔄 Updates and Maintenance

### Plugin Updates
- **Backup First:** Always backup before updating
- **Test Environment:** Test updates on staging site
- **Database Backup:** Export leave data before major updates

### Regular Maintenance
- **Database Cleanup:** Remove old sessions and logs
- **Email Queue:** Monitor email sending status
- **User Audit:** Review active users and permissions
- **Leave Balances:** Annual reset procedures

## 📝 Changelog

### Version 1.0.0
- Initial release with full leave management system
- WordPress plugin architecture
- Subdomain functionality
- WYSIWYG email templates
- Comprehensive user management
- Leave request editing with re-approval
- Professional frontend interface
- Mobile-responsive design

---

**© 2024 LFCC Leave Management Plugin. All rights reserved.**

For technical support or customization requests, please contact your system administrator.

