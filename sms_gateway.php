<?php
/**
 * Simple SMS Gateway (Simulator)
 * Handles "sending" SMS notifications by logging them to the database
 */

require_once 'email_config.php';

class SimpleSMS {
    private $enabled;
    private $provider;
    
    public function __construct() {
        $this->enabled = isSMSEnabled();
        $this->provider = defined('SMS_PROVIDER') ? SMS_PROVIDER : 'simulator';
    }
    
    /**
     * Send SMS message (Simulation)
     * @param string $to Recipient phone number
     * @param string $message SMS message content
     * @return array Result with status and message
     */
    public function sendSMS($to, $message) {
        if (!$this->enabled) {
            return [
                'success' => false,
                'error' => 'SMS notifications are disabled.'
            ];
        }
        
        // In simulation mode, we just return success
        // The actual logging happens in NotificationService
        
        return [
            'success' => true,
            'message' => 'SMS simulated successfully',
            'provider' => $this->provider,
            'recipient' => $to,
            'status' => 'simulated'
        ];
    }
    
    /**
     * Format phone number to E.164 format
     * @param string $phone Phone number
     * @param string $countryCode Default country code (e.g., '91' for India)
     * @return string Formatted phone number
     */
    public static function formatPhoneNumber($phone, $countryCode = '91') {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If already has country code, return with +
        if (strlen($phone) > 10) {
            return '+' . $phone;
        }
        
        // Add country code
        return '+' . $countryCode . $phone;
    }
    
    /**
     * Check if SMS is enabled
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }
}

/**
 * Quick helper function to send SMS
 * @param string $to Recipient phone number
 * @param string $message SMS message
 * @return array Result
 */
function sendSMS($to, $message) {
    $sms = new SimpleSMS();
    return $sms->sendSMS($to, $message);
}