<?php
/**
 * Doctor Matcher
 * Intelligent algorithm to match patients with best-fit doctors
 */

require_once 'db.php';
require_once 'location_service.php';

class DoctorMatcher {
    private $conn;
    
    // Weighting factors for matching criteria
    const WEIGHT_SPECIALTY = 0.40;      // 40% - Most important
    const WEIGHT_AVAILABILITY = 0.25;   // 25% - Very important
    const WEIGHT_RATING = 0.15;         // 15% - Important
    const WEIGHT_LANGUAGE = 0.10;       // 10% - Helpful
    const WEIGHT_PROXIMITY = 0.10;      // 10% - Nice to have
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Find best matching doctor for a consultation
     * @param int $consultationId Consultation ID
     * @param string $requiredSpecialty Required specialty
     * @param string $language Preferred language
     * @param float $patientLat Patient latitude (optional)
     * @param float $patientLon Patient longitude (optional)
     * @return array|null Best matching doctor or null
     */
    public function findBestMatch($consultationId, $requiredSpecialty, $language = 'English', $patientLat = null, $patientLon = null, $urgencyLevel = 'routine') {
        // Get all available doctors
        $doctors = $this->getAvailableDoctors($requiredSpecialty);
        
        if (empty($doctors)) {
            return null;
        }
        
        // Score each doctor
        $scoredDoctors = [];
        
        foreach ($doctors as $doctor) {
            $score = $this->calculateDoctorScore(
                $doctor,
                $requiredSpecialty,
                $language,
                $patientLat,
                $patientLon,
                $urgencyLevel
            );
            
            $doctor['match_score'] = $score;
            $scoredDoctors[] = $doctor;
        }
        
        // Sort by score (highest first)
        usort($scoredDoctors, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });
        
        // Return best match
        return $scoredDoctors[0] ?? null;
    }
    
    /**
     * Get all available doctors
     */
    private function getAvailableDoctors($preferredSpecialty = null) {
        $query = "
            SELECT 
                u.id,
                u.full_name,
                d.specialization,
                d.consultation_fee,
                d.languages_spoken,
                d.years_experience,
                d.bio,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(r.id) as review_count,
                l.latitude,
                l.longitude,
                l.city
            FROM users u
            INNER JOIN doctor_profiles d ON u.id = d.user_id
            LEFT JOIN doctor_reviews r ON u.id = r.doctor_id
            LEFT JOIN doctor_locations l ON u.id = l.doctor_id AND l.is_primary = TRUE
            WHERE u.role = 'doctor' 
            AND u.status = 'approved'
            GROUP BY u.id
        ";
        
        $result = $this->conn->query($query);
        $doctors = [];
        
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
        
        return $doctors;
    }
    
    /**
     * Calculate match score for a doctor
     */
    private function calculateDoctorScore($doctor, $requiredSpecialty, $language, $patientLat, $patientLon, $urgencyLevel = 'routine') {
        $score = 0;
        
        // Adjust weights based on urgency
        $weightSpecialty = self::WEIGHT_SPECIALTY;
        $weightAvailability = self::WEIGHT_AVAILABILITY;
        $weightRating = self::WEIGHT_RATING;
        $weightLanguage = self::WEIGHT_LANGUAGE;
        $weightProximity = self::WEIGHT_PROXIMITY;
        
        if ($urgencyLevel !== 'routine') {
            // For urgent/emergency, specialty and availability are even more critical
            $weightSpecialty = 0.45;
            $weightAvailability = 0.35;
            $weightRating = 0.10;
            $weightLanguage = 0.05;
            $weightProximity = 0.05;
        }
        
        // 1. Specialty Match
        $specialtyScore = $this->getSpecialtyScore($doctor['specialization'], $requiredSpecialty);
        $score += $specialtyScore * $weightSpecialty;
        
        // 2. Availability
        $availabilityScore = $this->getAvailabilityScore($doctor['id']);
        $score += $availabilityScore * $weightAvailability;
        
        // 3. Rating & Experience (Combined)
        $ratingScore = $this->getRatingScore($doctor['avg_rating'], $doctor['review_count']);
        // Boost for experience
        $experienceBoost = min(20, ($doctor['years_experience'] ?? 0) * 2);
        $ratingScore = min(100, $ratingScore + $experienceBoost);
        
        $score += $ratingScore * $weightRating;
        
        // 4. Language
        $languageScore = $this->getLanguageScore($doctor['languages_spoken'], $language);
        $score += $languageScore * $weightLanguage;
        
        // 5. Proximity
        $proximityScore = 0;
        if ($patientLat && $patientLon && $doctor['latitude'] && $doctor['longitude']) {
            $distance = LocationService::calculateDistance(
                $patientLat, 
                $patientLon, 
                $doctor['latitude'], 
                $doctor['longitude']
            );
            $proximityScore = LocationService::getProximityScore($distance);
        } else {
            $proximityScore = 50; // Neutral score if no location data
        }
        $score += $proximityScore * $weightProximity;
        
        return round($score, 2);
    }
    
    /**
     * Get specialty match score (0-100)
     */
    private function getSpecialtyScore($doctorSpecialty, $requiredSpecialty) {
        if (empty($requiredSpecialty)) {
            return 50; // Neutral if no specialty required
        }
        
        $docS = strtolower(trim($doctorSpecialty));
        $reqS = strtolower(trim($requiredSpecialty));

        // Exact match
        if ($docS === $reqS) {
            return 100;
        }

        // Fuzzy match (e.g., "dermatology" should match "Dermatologist")
        if (strpos($docS, $reqS) !== false || strpos($reqS, $docS) !== false) {
            return 100;
        }

        // Common stem matching
        $stemReq = rtrim($reqS, 'ist'); // Dermatolog
        if (strpos($docS, $stemReq) !== false) {
            return 95;
        }
        
        // General Physician can handle many cases
        if ($docS === 'general physician' || $docS === 'general practitioner') {
            return 70;
        }
        
        // No match
        return 20;
    }
    
    /**
     * Get availability score (0-100)
     */
    private function getAvailabilityScore($doctorId) {
        // Check if doctor has availability slots today
        $today = date('l'); // Day name (e.g., Monday)
        $currentTime = date('H:i:s');
        
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as slot_count
            FROM doctor_availability
            WHERE doctor_id = ?
            AND day_of_week = ?
            AND is_available = TRUE
            AND start_time <= ?
            AND end_time >= ?
        ");
        
        $stmt->bind_param('isss', $doctorId, $today, $currentTime, $currentTime);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        // If currently available, high score
        if ($row['slot_count'] > 0) {
            return 100;
        }
        
        // Check if has any availability this week
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as slot_count
            FROM doctor_availability
            WHERE doctor_id = ?
            AND is_available = TRUE
        ");
        
        $stmt->bind_param('i', $doctorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['slot_count'] > 0 ? 60 : 30;
    }
    
    /**
     * Get rating score (0-100)
     */
    private function getRatingScore($avgRating, $reviewCount) {
        if ($reviewCount == 0) {
            return 50; // Neutral for new doctors
        }
        
        // Convert 1-5 rating to 0-100 score
        $baseScore = ($avgRating / 5) * 100;
        
        // Boost score for doctors with more reviews (more reliable)
        if ($reviewCount >= 20) {
            $baseScore += 10;
        } elseif ($reviewCount >= 10) {
            $baseScore += 5;
        }
        
        return min(100, $baseScore);
    }
    
    /**
     * Get language match score (0-100)
     */
    private function getLanguageScore($doctorLanguages, $preferredLanguage) {
        if (empty($preferredLanguage)) {
            return 50; // Neutral
        }
        
        $languages = explode(',', strtolower($doctorLanguages));
        $languages = array_map('trim', $languages);
        
        if (in_array(strtolower($preferredLanguage), $languages)) {
            return 100;
        }
        
        // English is common
        if (in_array('english', $languages)) {
            return 70;
        }
        
        return 30;
    }
    
    /**
     * Auto-assign consultation to best matching doctor
     * @param int $consultationId Consultation ID
     * @param string $specialty Required specialty
     * @param string $language Preferred language
     * @return array Result with doctor info or error
     */
    public function autoAssignConsultation($consultationId, $specialty, $language = 'English', $urgencyLevel = 'routine') {
        // Find best match
        $doctor = $this->findBestMatch($consultationId, $specialty, $language, null, null, $urgencyLevel);
        
        if (!$doctor) {
            return [
                'success' => false,
                'error' => 'No available doctors found for this specialty'
            ];
        }
        
        // Assign consultation to doctor
        $stmt = $this->conn->prepare("
            UPDATE consultations
            SET doctor_id = ?,
                matched_specialty = ?,
                auto_assigned = TRUE,
                status = 'assigned',
                assigned_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param('isi', $doctor['id'], $specialty, $consultationId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            return [
                'success' => true,
                'doctor_id' => $doctor['id'],
                'doctor_name' => $doctor['full_name'],
                'specialization' => $doctor['specialization'],
                'match_score' => $doctor['match_score']
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to assign consultation'
            ];
        }
    }
}

/**
 * Helper function to auto-assign consultation
 */
function autoAssignConsultation($consultationId, $specialty, $language = 'English', $urgencyLevel = 'routine') {
    global $conn;
    $matcher = new DoctorMatcher($conn);
    return $matcher->autoAssignConsultation($consultationId, $specialty, $language, $urgencyLevel);
}