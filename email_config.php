<?php
/**
 * Email Configuration for MedConnect
 * 
 * Instructions:
 * 1. Choose your email provider (Gmail, Outlook, or Custom SMTP)
 * 2. Update the credentials below
 * 3. For Gmail: You must use an "App Password" (not your regular password)
 *    - Go to: https://myaccount.google.com/apppasswords
 *    - Generate an app password and use it below
 * 4. For Outlook/Hotmail: Use your regular password or app password
 */

// =============================================
// EMAIL PROVIDER CONFIGURATION
// =============================================

// Choose your email provider: 'gmail', 'outlook', or 'custom'
define('EMAIL_PROVIDER', 'gmail');

// =============================================
// GMAIL CONFIGURATION
// =============================================
define('GMAIL_USERNAME', 'jewelbiju123@gmail.com');  // Your Gmail address
define('GMAIL_PASSWORD', 'cgerctboxtbuttxt');        // Your Gmail App Password (16 characters, no spaces)
define('GMAIL_FROM_NAME', 'MedConnect');             // Display name for emails

// =============================================
// OUTLOOK/HOTMAIL CONFIGURATION
// =============================================
define('OUTLOOK_USERNAME', 'your-email@outlook.com');  // Your Outlook address
define('OUTLOOK_PASSWORD', 'your-password');            // Your Outlook password
define('OUTLOOK_FROM_NAME', 'MedConnect');              // Display name for emails

// =============================================
// CUSTOM SMTP CONFIGURATION
// =============================================
define('CUSTOM_SMTP_HOST', 'smtp.example.com');     // SMTP server address
define('CUSTOM_SMTP_PORT', 587);                    // SMTP port (usually 587 for TLS, 465 for SSL)
define('CUSTOM_SMTP_USERNAME', 'your-email@example.com');
define('CUSTOM_SMTP_PASSWORD', 'your-password');
define('CUSTOM_SMTP_FROM_EMAIL', 'noreply@example.com');
define('CUSTOM_SMTP_FROM_NAME', 'MedConnect');
define('CUSTOM_SMTP_ENCRYPTION', 'tls');            // 'tls' or 'ssl'

// =============================================
// FUNCTIONS
// =============================================

/**
 * Get SMTP configuration based on selected provider
 * @return array Configuration array with SMTP settings
 */
function getEmailConfig() {
    switch (EMAIL_PROVIDER) {
        case 'gmail':
            return [
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'username' => GMAIL_USERNAME,
                'password' => GMAIL_PASSWORD,
                'from_email' => GMAIL_USERNAME,
                'from_name' => GMAIL_FROM_NAME,
                'encryption' => 'tls'
            ];
        
        case 'outlook':
            return [
                'host' => 'smtp-mail.outlook.com',
                'port' => 587,
                'username' => OUTLOOK_USERNAME,
                'password' => OUTLOOK_PASSWORD,
                'from_email' => OUTLOOK_USERNAME,
                'from_name' => OUTLOOK_FROM_NAME,
                'encryption' => 'tls'
            ];
        
        case 'custom':
            return [
                'host' => CUSTOM_SMTP_HOST,
                'port' => CUSTOM_SMTP_PORT,
                'username' => CUSTOM_SMTP_USERNAME,
                'password' => CUSTOM_SMTP_PASSWORD,
                'from_email' => CUSTOM_SMTP_FROM_EMAIL,
                'from_name' => CUSTOM_SMTP_FROM_NAME,
                'encryption' => CUSTOM_SMTP_ENCRYPTION
            ];
        
        default:
            throw new Exception('Invalid email provider configured');
    }
}

/**
 * Validate email configuration
 * @return bool True if valid, false otherwise
 */
function isEmailConfigured() {
    $config = getEmailConfig();
    
    // Check if credentials are still default/empty
    if (strpos($config['username'], 'your-email') !== false || 
        strpos($config['password'], 'your-password') !== false ||
        strpos($config['password'], 'your-app-password') !== false) {
        return false;
    }
    
    return !empty($config['username']) && !empty($config['password']);
}

// =============================================
// SMS NOTIFICATION CONFIGURATION
// =============================================

/**
 * SMS Notification Settings
 * 
 * Set SMS_ENABLED to true to enable simulated SMS notifications.
 * Messages will be logged to the 'notification_log' table.
 */

define('SMS_ENABLED', true);  // Enable/disable SMS simulation
define('SMS_PROVIDER', 'simulator'); // Options: 'simulator' (standard)

/**
 * Check if SMS is enabled
 * @return bool
 */
function isSMSEnabled() {
    return defined('SMS_ENABLED') && SMS_ENABLED === true;
}
