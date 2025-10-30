# Email Setup Instructions

## Current Issue
Emails cannot be sent because no valid SMTP credentials are configured.

## Solution 1: Gmail SMTP Setup (Recommended)

### Step 1: Create Gmail App Password
1. Go to your Google Account settings
2. Enable 2-Factor Authentication
3. Go to "App passwords" section
4. Generate a new app password for "Mail"
5. Copy the 16-character password (e.g., "abcd efgh ijkl mnop")

### Step 2: Update Configuration
Edit `config/mail_config.php`:
```php
define('SMTP_USERNAME', 'your-actual-gmail@gmail.com');
define('SMTP_PASSWORD', 'your-16-character-app-password');
define('SMTP_FROM_EMAIL', 'your-actual-gmail@gmail.com');
```

### Step 3: Test Email
The system will automatically use Gmail SMTP to send emails.

## Solution 2: Use XAMPP Mail Server

### Step 1: Install Mercury Mail Server
1. Download Mercury Mail Server
2. Install and configure it
3. Set up local mail server

### Step 2: Configure PHP
Edit `php.ini`:
```ini
[mail function]
SMTP = localhost
smtp_port = 25
sendmail_from = noreply@chmsu.edu.ph
```

## Solution 3: Use Third-Party Email Service

### Popular Options:
- **SendGrid** - Professional email service
- **Mailgun** - Developer-friendly
- **Amazon SES** - AWS email service
- **SMTP2GO** - Simple SMTP service

## Current Workaround
The system shows the temporary password on screen for manual distribution, which works perfectly for now.

## Benefits of Email Setup:
- ✅ Automatic password delivery
- ✅ Professional user experience
- ✅ No manual password distribution needed
- ✅ Audit trail of sent emails

