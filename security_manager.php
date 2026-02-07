<?php
/**
 * Security Manager
 * Centralized security functions and audit logging
 */

require_once 'db.php';

class SecurityManager {
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Log access to sensitive resources
     */
    public function logAccess($userId, $userRole, $action, $resourceType = null, $resourceId = null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUrl = $_SERVER['REQUEST_URI'] ?? '';
        $sessionId = session_id();
        
        $stmt = $this->conn->prepare("
            INSERT INTO access_logs (
                user_id, user_role, action, resource_type, resource_id,
                ip_address, user_agent, request_method, request_url, session_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "isssssssss",
            $userId, $userRole, $action, $resourceType, $resourceId,
            $ipAddress, $userAgent, $requestMethod, $requestUrl, $sessionId
        );
        
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Log compliance events
     */
    public function logComplianceEvent($eventType, $userId, $affectedUserId, $description, $metadata = []) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $metadataJson = json_encode($metadata);
        
        $stmt = $this->conn->prepare("
            INSERT INTO compliance_events (
                event_type, user_id, affected_user_id, event_description,
                event_metadata, ip_address
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "siisss",
            $eventType, $userId, $affectedUserId, $description,
            $metadataJson, $ipAddress
        );
        
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Check if user has permission for resource
     */
    public function checkPermission($userId, $userRole, $resourceType, $resourceId, $action = 'view') {
        // Define role-based access control rules
        $permissions = [
            'patient' => [
                'consultation' => ['view_own', 'create'],
                'prescription' => ['view_own'],
                'appointment' => ['view_own', 'create', 'cancel'],
                'payment' => ['view_own', 'create']
            ],
            'doctor' => [
                'consultation' => ['view_assigned', 'update_assigned', 'create_notes'],
                'prescription' => ['create', 'view_own'],
                'appointment' => ['view_assigned', 'confirm', 'cancel'],
                'patient_history' => ['view_assigned']
            ],
            'pharmacy' => [
                'prescription' => ['view_routed', 'fulfill'],
                'prescription_order' => ['create', 'update_own'],
                'inventory' => ['manage_own']
            ],
            'admin' => ['*'] // Admin has all permissions
        ];
        
        // Admin bypass
        if ($userRole === 'admin') {
            return true;
        }
        
        // Check if resource type exists for role
        if (!isset($permissions[$userRole][$resourceType])) {
            $this->logAccess($userId, $userRole, "PERMISSION_DENIED: $resourceType.$action", $resourceType, $resourceId);
            return false;
        }
        
        $allowedActions = $permissions[$userRole][$resourceType];
        
        // Check if action is allowed
        $allowed = in_array($action, $allowedActions) || in_array('*', $allowedActions);
        
        if ($allowed) {
            // Additional ownership checks for "_own" and "_assigned" actions
            if (strpos($action, '_own') !== false) {
                // Verify ownership
                $owned = $this->verifyOwnership($userId, $resourceType, $resourceId);
                if (!$owned) {
                    $this->logAccess($userId, $userRole, "OWNERSHIP_VIOLATION: $resourceType.$resourceId", $resourceType, $resourceId);
                    return false;
                }
            } elseif (strpos($action, '_assigned') !== false) {
                // Verify assignment
                $assigned = $this->verifyAssignment($userId, $resourceType, $resourceId, $userRole);
                if (!$assigned) {
                    $this->logAccess($userId, $userRole, "ASSIGNMENT_VIOLATION: $resourceType.$resourceId", $resourceType, $resourceId);
                    return false;
                }
            }
        } else {
            $this->logAccess($userId, $userRole, "PERMISSION_DENIED: $resourceType.$action", $resourceType, $resourceId);
        }
        
        return $allowed;
    }
    
    /**
     * Verify resource ownership
     */
    private function verifyOwnership($userId, $resourceType, $resourceId) {
        $ownershipQueries = [
            'consultation' => "SELECT id FROM consultations WHERE id = ? AND patient_id = ?",
            'prescription' => "SELECT id FROM prescriptions_v2 WHERE id = ? AND patient_id = ?",
            'appointment' => "SELECT id FROM appointments WHERE id = ? AND patient_id = ?",
            'payment' => "SELECT id FROM payment_transactions WHERE id = ? AND user_id = ?"
        ];
        
        if (!isset($ownershipQueries[$resourceType])) {
            return false;
        }
        
        $stmt = $this->conn->prepare($ownershipQueries[$resourceType]);
        $stmt->bind_param("ii", $resourceId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Verify resource assignment (for doctors/pharmacies)
     */
    private function verifyAssignment($userId, $resourceType, $resourceId, $userRole) {
        if ($userRole === 'doctor') {
            $queries = [
                'consultation' => "SELECT id FROM consultations WHERE id = ? AND doctor_id = ?",
                'appointment' => "SELECT id FROM appointments WHERE id = ? AND doctor_id = ?",
                'patient_history' => "SELECT c.id FROM consultations c WHERE c.patient_id = ? AND c.doctor_id = ?"
            ];
            
            if (!isset($queries[$resourceType])) {
                return false;
            }
            
            $stmt = $this->conn->prepare($queries[$resourceType]);
            $stmt->bind_param("ii", $resourceId, $userId);
            
        } elseif ($user Role === 'pharmacy') {
            $queries = [
                'prescription' => "SELECT id FROM prescriptions_v2 WHERE id = ? AND pharmacy_id = ?",
                'prescription_order' => "SELECT id FROM prescription_orders WHERE id = ? AND pharmacy_id = ?"
            ];
            
            if (!isset($queries[$resourceType])) {
                return false;
            }
            
            $stmt = $this->conn->prepare($queries[$resourceType]);
            $stmt->bind_param("ii", $resourceId, $userId);
        } else {
            return false;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encrypt($data, $key = null) {
        if ($key === null) {
            $key = getenv('ENCRYPTION_KEY') ?: 'default-key-change-in-production'; // Use environment variable in production
        }
        
        $cipher = "AES-256-CBC";
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
        
        // Combine IV and encrypted data
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt($encryptedData, $key = null) {
        if ($key === null) {
            $key = getenv('ENCRYPTION_KEY') ?: 'default-key-change-in-production';
        }
        
        $cipher = "AES-256-CBC";
        $ivLength = openssl_cipher_iv_length($cipher);
        
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
    }
}

/**
 * Helper function to get security manager instance
 */
function getSecurityManager() {
    global $conn;
    return new SecurityManager($conn);
}