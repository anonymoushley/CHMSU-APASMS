# Email Configuration Setup Guide

## Current Issue
The error "Interviewer added but email could not be sent" occurs because the email configuration is not properly set up.

## Solution Options

### Option 1: Configure Gmail SMTP (Recommended)

1. **Update `config/mail_config.php`:**
   ```php
   define('SMTP_USERNAME', 'your-actual-gmail@gmail.com');
   define('SMTP_PASSWORD', 'your-gmail-app-password');
   define('SMTP_FROM_EMAIL', 'your-actual-gmail@gmail.com');
   ```

2. **Create Gmail App Password:**
   - Go to Google Account settings
   - Enable 2-Factor Authentication
   - Generate an App Password for "Mail"
   - Use this 16-character password in the config

### Option 2: Use PHP Built-in Mail (Fallback)
The system now automatically falls back to PHP's built-in mail function if SMTP fails.

### Option 3: Manual Password Distribution
If email continues to fail, the system will display the temporary password on screen for manual distribution.

## Current Status
- ✅ Interviewer is successfully added to database
- ✅ Temporary password is generated (e.g., "Uxu")
- ❌ Email sending fails due to configuration
- ✅ Fallback system provides password on screen

## Next Steps
1. Configure proper email credentials in `config/mail_config.php`
2. Test email sending
3. Or use the displayed temporary password for manual distribution

